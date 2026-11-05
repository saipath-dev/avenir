<?php

namespace App\Controllers;

use App\Libraries\Help\HelpAccess;
use App\Libraries\Help\HelpAnswer;
use App\Libraries\Help\HelpManual;
use App\Libraries\Help\HelpPdf;
use App\Libraries\Help\HelpVerifier;

/**
 * tf-help-r1 — the in-system User Manual.
 *
 * Every signed-in person may use it (the help routes sit outside the menu permission filter). All
 * endpoints are GET and read-only: they never change a record, approve anything or call another
 * screen's actions. The only files written are the help's own: the question log and the PDF cache
 * under writable/help/.
 */
class HelpController extends BaseController
{
    /** Keep a plain log of the questions asked (no names), to see what the manual is missing. */
    private const LOG_QUESTIONS = true;

    private ?HelpManual $manual = null;
    private ?HelpAccess $access = null;

    private function manual(): HelpManual
    {
        return $this->manual ??= HelpManual::instance();
    }

    private function access(): HelpAccess
    {
        return $this->access ??= new HelpAccess();
    }

    private function json(array $data, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setHeader('Cache-Control', 'no-store')->setJSON($data);
    }

    private function path(): string
    {
        $p = (string) $this->request->getGet('path');
        $p = trim((string) parse_url($p, PHP_URL_PATH), '/');
        return preg_replace('#^index\.php/#', '', $p) ?? '';
    }

    private function pdfLinks(): array
    {
        return ['view' => base_url('help/manual'), 'download' => base_url('help/manual?download=1')];
    }

    // GET help — the manual as a page of its own (the same panel, opened)
    public function index()
    {
        return view('help/index', [
            'base' => base_url(),
            'version' => HelpManual::VERSION . '-' . $this->manual()->fingerprint(),
        ]);
    }

    // GET help/page?path= — what the panel shows when it opens: help for this screen, and who you are
    public function page()
    {
        $m = $this->manual();
        $path = $this->path();
        $ans = new HelpAnswer($m, $this->access());
        $sec = $m->pageSection($path);
        $topics = array_map(fn ($id) => $ans->brief($m->topic($id)), $m->pageTopics($path));
        $sections = [];
        foreach ($m->sections() as $s) {
            $list = [];
            foreach ($m->topics() as $t) {
                if ($t['section'] === $s['id']) {
                    $list[] = $ans->brief($t);
                }
            }
            $sections[] = ['id' => $s['id'], 'title' => $s['title'], 'topics' => $list];
        }
        $check = null;
        if ($this->access()->isAdmin() && is_file(WRITEPATH . 'help/check.json')) {
            $c = json_decode((string) file_get_contents(WRITEPATH . 'help/check.json'), true) ?: [];
            $check = ['at' => $c['checked_at'] ?? '', 'ok' => $c['ok'] ?? 0, 'total' => $c['total'] ?? 0, 'url' => base_url('help/check')];
        }
        return $this->json([
            'status' => 200,
            'path' => $path,
            'section' => $sec ? ['id' => $sec['id'], 'title' => $sec['title'], 'intro_html' => HelpManual::md((string) ($sec['intro'] ?? ''))] : null,
            'page_topics' => $topics,
            'sections' => $sections,
            'examples' => ['Where do I go to approve hotel rates?', 'How do I upload a hotel rate sheet?', 'How do I enter rates manually or correct a rate?', 'Why is a hotel rate not appearing in my inquiry?'],
            'user' => ['group' => $this->access()->groupName(), 'admin' => $this->access()->isAdmin()],
            'checked' => $m->checkedDate(),
            'pdf' => $this->pdfLinks(),
            'check' => $check,
        ]);
    }

    // GET help/ask?q=&path= — answer a question from the checked topics, or say there is none
    public function ask()
    {
        $q = trim(mb_substr((string) $this->request->getGet('q'), 0, 300));
        $path = $this->path();
        if ($q === '') {
            return $this->json(['status' => 400, 'message' => 'Type a question first.'], 400);
        }
        $m = $this->manual();
        $ans = new HelpAnswer($m, $this->access());
        $r = $m->search($q, $path);
        $out = ['status' => 200, 'result' => $r['status'], 'question' => $q, 'pdf' => $this->pdfLinks()];
        if ($r['status'] === 'answer') {
            $out['answer'] = $ans->topic($m->topic($r['ranked'][0]['id']));
            if (!empty($r['also'])) {
                // a question in two parts: the second part's answer too
                $out['also'] = $ans->topic($m->topic($r['also']));
            }
            $rest = array_values(array_filter(array_slice($r['ranked'], 1, 3), static fn ($x) => $x['id'] !== ($r['also'] ?? '')));
            $out['others'] = array_map(fn ($x) => $ans->brief($m->topic($x['id'])), array_slice($rest, 0, 2));
        } elseif ($r['status'] === 'unsure') {
            $out['message'] = 'I am not sure which of these you mean. Choose one:';
            $out['options'] = array_map(fn ($x) => $ans->brief($m->topic($x['id'])), array_slice($r['ranked'], 0, 3));
        } else {
            $out['message'] = 'I do not have checked instructions for that yet. The manual so far covers Hotel Rates. Try other words, open the printable manual, or ask your supervisor.';
            $out['options'] = [];
        }
        $this->log($q, $path, $r);
        return $this->json($out);
    }

    // GET help/topic/{id}?path=
    public function topic(string $id)
    {
        $t = $this->manual()->topic($id);
        if (!$t) {
            return $this->json(['status' => 404, 'message' => 'That help topic does not exist.'], 404);
        }
        return $this->json(['status' => 200, 'result' => 'answer', 'answer' => (new HelpAnswer($this->manual(), $this->access()))->topic($t), 'pdf' => $this->pdfLinks()]);
    }

    // GET help/img/{folder}/{file} — the manual's own pictures (signed-in staff only)
    public function img(string $folder, string $file)
    {
        if (!preg_match('/^[a-z0-9_]+$/', $folder) || !preg_match('/^[A-Za-z0-9._-]+\.(png|jpe?g)$/', $file)) {
            return $this->response->setStatusCode(404);
        }
        $f = APPPATH . 'Help/images/' . $folder . '/' . $file;
        if (!is_file($f)) {
            return $this->response->setStatusCode(404);
        }
        return $this->response->setHeader('Content-Type', str_ends_with($file, '.png') ? 'image/png' : 'image/jpeg')
            ->setHeader('Cache-Control', 'private, max-age=86400')
            ->setBody((string) file_get_contents($f));
    }

    // GET help/manual[?download=1] — the printable manual, rebuilt when the help changes
    public function manualPdf()
    {
        try {
            $file = (new HelpPdf($this->manual()))->path();
        } catch (\Throwable $e) {
            log_message('error', 'help: PDF could not be built: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody('The printable manual could not be made just now. Please tell the System Administrator.');
        }
        $name = 'Avenir-User-Manual-' . $this->manual()->checkedDate() . '.pdf';
        $disp = $this->request->getGet('download') ? 'attachment' : 'inline';
        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', $disp . '; filename="' . $name . '"')
            ->setHeader('Cache-Control', 'private, no-cache')
            ->setBody((string) file_get_contents($file));
    }

    // GET help/check — System Administrator: are all instructions still true for this system?
    public function check()
    {
        if (!$this->access()->isAdmin()) {
            return redirect()->to(base_url('help'));
        }
        $r = (new HelpVerifier($this->manual()))->run();
        return view('help/check', ['r' => $r]);
    }

    private function log(string $q, string $path, array $r): void
    {
        if (!self::LOG_QUESTIONS) {
            return;
        }
        try {
            $dir = WRITEPATH . 'help';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $line = json_encode([
                'at' => date('Y-m-d H:i:s'),
                'group' => $this->access()->groupName(),
                'path' => $path,
                'q' => $q,
                'result' => $r['status'],
                'topic' => $r['ranked'][0]['id'] ?? null,
                'score' => $r['ranked'][0]['score'] ?? 0,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            @file_put_contents($dir . '/questions-' . date('Y-m') . '.log', $line . "\n", FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
        }
    }
}
