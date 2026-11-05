<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\HotelLetter;
use App\Libraries\TourFlow\EmailTemplates;
use App\Libraries\TourFlow\IntakeService;
use App\Libraries\TourFlow\Staff;
use App\Libraries\TourFlow\TourMailer;
use App\Libraries\TourFlow\TourService;

/**
 * tf-tourmail-r1 (10 Oct 2026) — the hotels' and restaurants' mails of a tour: the list before they go, sending them
 * all from the Hotel Desk, the mails as they went (per tour and for all tours), and the "Mails not sent" alert.
 *
 *   GET  tour/intake/intake/(:num)/mail_preview          the stays as Confirm Tour makes them, with their addresses
 *   GET  tour/hotel_bookings/tour/(:num)/mail_plan       a confirmed tour's stays, with their addresses
 *   POST tour/hotel_bookings/tour/(:num)/mail_all        send the requests of the stays still to book (as listed, after OK)
 *   GET  tour/hotel_bookings/mails                       Sent Mails (page)
 *   GET  tour/hotel_bookings/mails/data                  the mails ?tour_id=&state=all|sent|not_sent&q=
 *   GET  tour/hotel_bookings/mails/copy/(:segment)/(:num) the copy of one mail as it went
 *   GET  notification/unsent_mails                        what has not gone — for the top bar (signed in; Hotel Bookings holders)
 * Reading, listing and opening never send anything. Sending happens only on the POST, after the person pressed OK.
 */
class TourMailController extends BaseController
{
    public const HOTEL = 'tour/hotel_bookings';

    private ?array $menuData = null;
    private ?string $actorName = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function user(): string
    {
        return (string) session()->get('username');
    }

    private function userId(): ?int
    {
        $id = (int) session()->get('user_id');
        return $id > 0 ? $id : null;
    }

    private function actorName(): string
    {
        return $this->actorName ??= Staff::displayName($this->db(), $this->userId(), $this->user());
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    private function intakeSvc(): IntakeService
    {
        return (new IntakeService($this->db(), $this->user(), new HotelRateService($this->db(), $this->user())))->withActor($this->userId(), $this->actorName());
    }

    private function tourSvc(): TourService
    {
        return new TourService($this->db(), $this->user(), $this->intakeSvc());
    }

    private function mailer(): TourMailer
    {
        return new TourMailer($this->db(), $this->user(), $this->userId(), $this->actorName());
    }

    private function holds(string $url): bool
    {
        if ($this->menuData === null) {
            try {
                $this->menuData = (new MenuLoader())->loadMenuData();
            } catch (\Throwable $e) {
                // the sidebar's loader could not run for this sign-in: the privilege rows themselves are asked
                $list = [];
                try {
                    $list = $this->db()->query('SELECT DISTINCT url FROM user_priv_q WHERE user_id = ?', [(int) session()->get('user_id')])->getResultArray();
                } catch (\Throwable $e2) {
                    $list = [];
                }
                $this->menuData = ['user_role' => session()->get('group_id'), 'allowed_menu_list' => $list];
            }
        }
        if ((int) ($this->menuData['user_role'] ?? 0) === 1) {
            return true;
        }
        foreach ((array) ($this->menuData['allowed_menu_list'] ?? []) as $m) {
            $u = is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '');
            if (trim((string) $u, '/') === $url) {
                return true;
            }
        }
        return false;
    }

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    private function body(): array
    {
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : ($this->request->getPost() ?: []);
    }

    private function fail(\Throwable $e, int $status = 400)
    {
        return $this->json(['status' => $status, 'message' => $e->getMessage()]);
    }

    /**
     * One stay's booking request as the Hotel Desk sends it (TourDeskController 'send'): the ready-made e-mail's subject
     * and opening, the letter with the rate basis (never a figure) and the logo.
     */
    public static function renderRequest(TourService $svc, $db, int $jobId, array $sender): array
    {
        $doc = $svc->hotelDocument($jobId, 'request', $sender);
        $message = null;
        $subject = $doc['subject'];
        try {
            $fmtD = static fn ($d) => $d ? date('j M Y', strtotime((string) $d)) : '';
            $stay = $doc['stay'];
            $tour = $doc['tour'];
            $vars = [
                'agent' => (string) ($tour['agent_name'] ?? ''), 'agent_name' => (string) ($tour['agent_name'] ?? ''), 'inquiry_no' => (string) ($tour['inquiry_no'] ?? ''), 'tour_no' => (string) ($tour['tour_no'] ?? ''), 'tour_name' => (string) ($tour['tour_name'] ?? ''),
                'dates' => !empty($tour['arrival_date']) && !empty($tour['departure_date']) ? $fmtD($tour['arrival_date']) . ' to ' . $fmtD($tour['departure_date']) : '', 'arrival' => $fmtD($tour['arrival_date'] ?? null), 'departure' => $fmtD($tour['departure_date'] ?? null),
                'nights' => (string) (int) ($stay['nights'] ?? 0), 'pax' => (string) ($stay['pax'] ?? $tour['pax_total'] ?? ''), 'hotel' => (string) ($doc['hotel']['name'] ?? ''), 'check_in' => $fmtD($stay['check_in'] ?? null), 'check_out' => $fmtD($stay['check_out'] ?? null),
                'rooms' => trim(($stay['rooms'] !== null ? (int) $stay['rooms'] . ' room' . ((int) $stay['rooms'] === 1 ? '' : 's') : '') . (!empty($stay['rooms_note']) ? ' — ' . $stay['rooms_note'] : '')), 'meal_plan' => (string) ($stay['meal_plan'] ?? ''),
                'me' => (string) ($sender['name'] ?? ''), 'company' => (string) ($doc['company']['name'] ?? 'Avenir Holidays'), 'today' => date('j M Y'),
            ];
            $start = EmailTemplates::starting(EmailTemplates::filled((new EmailTemplates($db))->forWindow('hotel_request'), $vars), 'hotel_request');
            if ($start) {
                $subject = trim((string) $start['subject']) !== '' ? (string) $start['subject'] : $subject;
                $message = trim((string) $start['message']) !== '' ? (string) $start['message'] : null;
            }
        } catch (\Throwable $e) {
            // the letter's own greeting and subject
        }
        $body = view('pages/tour/hotel_document', [
            'doc' => $doc, 'rate' => null, 'rate_basis' => HotelLetter::rateBasis($db, $svc->hotelJob($jobId)), 'remarks' => null,
            'logo' => HotelLetter::logoUrl(), 'sender' => $sender, 'email' => true, 'message' => $message !== null ? mb_substr(str_replace("\r", '', $message), 0, 4000) : null,
        ]);
        return ['subject' => $subject, 'body' => $body];
    }

    // GET tour/intake/intake/(:num)/mail_preview
    public function intakePreview($id)
    {
        try {
            $it = $this->intakeSvc()->get((int) $id);
            if (!$it) {
                return $this->json(['status' => 404, 'message' => 'Inquiry not found.'], 404);
            }
            return $this->json(['status' => 200, 'data' => $this->mailer()->previewForIntake($it)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET tour/hotel_bookings/tour/(:num)/mail_plan
    public function tourPlan($tourId)
    {
        try {
            $t = $this->db()->query('SELECT id, intake_id, tour_no, stage FROM tf_tour WHERE id = ?', [(int) $tourId])->getRowArray();
            if (!$t) {
                return $this->json(['status' => 404, 'message' => 'Tour not found.'], 404);
            }
            $it = $this->intakeSvc()->get((int) $t['intake_id']) ?: ['id' => (int) $t['intake_id'], 'accom' => []];
            return $this->json(['status' => 200, 'data' => $this->mailer()->planForTour((int) $tourId, $it) + ['tour' => $t]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/hotel_bookings/tour/(:num)/mail_all {beds, cc, stays: [{day_from, send, email, driver, guide}]}
    public function tourSendAll($tourId)
    {
        try {
            $t = $this->db()->query('SELECT id, intake_id, tour_no, stage, archived_at FROM tf_tour WHERE id = ?', [(int) $tourId])->getRowArray();
            if (!$t || $t['archived_at'] !== null || (string) $t['stage'] === 'cancelled') {
                return $this->json(['status' => 400, 'message' => 'This tour is cancelled or archived; nothing was sent.']);
            }
            $svc = $this->tourSvc();
            $db = $this->db();
            $it = $this->intakeSvc()->get((int) $t['intake_id']) ?: ['id' => (int) $t['intake_id'], 'accom' => []];
            $sender = Staff::contact($db, $this->userId(), $this->user());
            $res = $this->mailer()->sendHotels($svc, (int) $tourId, $it, $this->body(), $sender,
                static fn (int $jobId, array $who) => self::renderRequest($svc, $db, $jobId, $who));
            return $this->json(['status' => 200, 'message' => self::resultText($res), 'data' => ['results' => $res]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public static function resultText(array $res): string
    {
        $sent = count(array_filter($res, static fn ($r) => $r['status'] === 'sent'));
        $not = count(array_filter($res, static fn ($r) => $r['status'] === 'not_sent'));
        $kept = count(array_filter($res, static fn ($r) => $r['status'] === 'kept'));
        return 'Hotels: ' . $sent . ' booking request' . ($sent === 1 ? '' : 's') . ' sent' . ($kept ? ', ' . $kept . ' already requested (not sent again)' : '') . ($not ? '; ' . $not . ' NOT sent — see the reasons; they stay on "Mails not sent" until they go' : '') . '.';
    }

    // GET tour/hotel_bookings/mails
    public function page()
    {
        $md = [];
        try {
            $md = (new MenuLoader())->loadMenuData();
        } catch (\Throwable $e) {
            $md = [];
        }
        return view('pages/tour/sent_mails', $md + ['user' => $this->user(), 'page_url' => self::HOTEL, 'me_name' => $this->actorName()]);
    }

    // GET tour/hotel_bookings/mails/data?tour_id=&state=&q=
    public function data()
    {
        try {
            $g = fn (string $k) => (string) ($this->request->getGet($k) ?? '');
            $m = $this->mailer();
            return $this->json(['status' => 200, 'data' => [
                'mails' => $m->log(['tour_id' => (int) $g('tour_id'), 'state' => $g('state') ?: 'all', 'q' => $g('q'), 'limit' => (int) ($g('limit') ?: 300)]),
                'unsent' => count($m->unsent(200)),
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 500);
        }
    }

    // GET tour/hotel_bookings/mails/copy/(hotel|restaurant)/(:num) — the mail as it went, as a page
    public function copy($type, $id)
    {
        try {
            $c = $this->mailer()->copy((string) $type, (int) $id);
            if (!$c) {
                return $this->response->setStatusCode(404)->setBody('Not found.');
            }
            $e = static fn ($v) => esc((string) ($v ?? ''));
            $state = in_array($c['state'], ['sent'], true) ? 'Sent' : (in_array($c['state'], ['recorded', 'other'], true) ? 'Recorded as sent another way' : 'NOT SENT');
            $head = '<div style="font:13px/1.5 Arial,Helvetica,sans-serif;background:#f6f7fa;border-bottom:1px solid #e3e6eb;padding:10px 16px;color:#1d2733">'
                . '<b>' . $e($c['tour_no']) . '</b> · ' . $e($state) . ($c['at'] ? ' · ' . $e($c['at']) : '') . ($c['by'] ? ' · by ' . $e($c['by']) : '')
                . '<br>To: ' . $e($c['to'] ?: '—') . ($c['cc'] ? ' · Cc: ' . $e($c['cc']) : '') . '<br>Subject: <b>' . $e($c['subject'] ?: '—') . '</b>'
                . ($c['error'] ? '<br><span style="color:#b23a32"><b>Why not sent:</b> ' . $e($c['error']) . '</span>' : '') . '</div>';
            $body = (string) ($c['body'] ?? '');
            if ($body === '') {
                $body = '<p style="font:13px Arial;padding:16px;color:#6b7a8c">No copy of the letter was kept for this one (it was not prepared).</p>';
            }
            return $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8')
                ->setBody('<!doctype html><html><head><meta charset="utf-8"><title>' . $e($c['subject'] ?: 'Mail') . '</title></head><body style="margin:0">' . $head . $body . '</body></html>');
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setBody(esc($e->getMessage()));
        }
    }

    // GET notification/unsent_mails — the top bar's red alert: only for a sign-in holding Hotel Bookings; nothing written
    public function alert()
    {
        try {
            if (!$this->holds(self::HOTEL)) {
                return $this->json(['status' => 200, 'data' => ['allowed' => false, 'count' => 0, 'items' => []]]);
            }
            $all = $this->mailer()->unsent(200);
            return $this->json(['status' => 200, 'data' => ['allowed' => true, 'count' => count($all), 'items' => array_slice($all, 0, 20), 'url' => base_url(self::HOTEL . '/mails?state=not_sent')]]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 200, 'data' => ['allowed' => false, 'count' => 0, 'items' => []]]);
        }
    }
}
