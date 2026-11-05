<?php

namespace App\Libraries\Help;

/**
 * tf-help-r1 — the printable User Manual, made from the same help sections as the in-system panel,
 * so the two always say the same thing. It is rebuilt by itself the first time it is opened after
 * any section, picture or this library changes (the file name carries a fingerprint of them) and
 * kept in writable/help/pdf/. Nothing outside writable/help is written.
 */
class HelpPdf
{
    private const PURPLE = [75, 42, 92];
    private string $font = 'dejavusans';

    public function __construct(private HelpManual $manual)
    {
    }

    /** The current PDF's path, building it when the manual has changed. */
    public function path(): string
    {
        $dir = WRITEPATH . 'help/pdf';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $dir . '/Avenir-User-Manual-' . $this->manual->fingerprint() . '.pdf';
        if (!is_file($file) || filesize($file) < 1000) {
            $this->build($file);
            foreach (glob($dir . '/Avenir-User-Manual-*.pdf') ?: [] as $old) {
                if ($old !== $file) {
                    @unlink($old);
                }
            }
        }
        return $file;
    }

    public function build(string $file): void
    {
        if (!class_exists('\\TCPDF')) {
            // composer's autoloader may not know the library on this server: load it from vendor by hand
            $lib = ROOTPATH . 'vendor/tecnickcom/tcpdf/tcpdf.php';
            if (is_file($lib)) {
                require_once $lib;
            }
        }
        if (!class_exists('\\TCPDF')) {
            throw new \RuntimeException('The PDF library (vendor/tecnickcom/tcpdf) is not installed.');
        }
        $m = $this->manual;
        $pdf = new class ('P', 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF {
            public string $foot = '';
            public function Header(): void
            {
            }
            public function Footer(): void
            {
                $this->SetY(-12);
                $this->SetFont('dejavusans', '', 7.5);
                $this->SetTextColor(120, 120, 120);
                $this->Cell(0, 6, $this->foot . '   ·   page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
            }
        };
        $checked = $m->checkedDate();
        $pdf->foot = 'Avenir Holidays — User Manual · checked against the system ' . $this->day($checked) . ' · ' . HelpManual::VERSION . '-' . $m->fingerprint();
        $pdf->SetCreator('Avenir Holidays');
        $pdf->SetAuthor('Avenir Holidays');
        $pdf->SetTitle('Avenir Holidays — User Manual');
        $pdf->SetMargins(16, 16, 16);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->setImageScale(1.0);
        $pdf->setPrintHeader(false);

        // ---- cover ----
        $pdf->AddPage();
        $pdf->SetFillColor(...self::PURPLE);
        $pdf->Rect(0, 0, 210, 70, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont($this->font, 'B', 26);
        $pdf->SetXY(16, 24);
        $pdf->Cell(0, 12, 'Avenir Holidays', 0, 1);
        $pdf->SetX(16);
        $pdf->SetFont($this->font, '', 18);
        $pdf->Cell(0, 10, 'User Manual', 0, 1);
        $pdf->SetTextColor(40, 30, 50);
        $pdf->SetXY(16, 84);
        $sections = array_values(array_filter($m->sections(), static fn ($s) => ($s['id'] ?? '') !== 'general'));
        $names = implode(', ', array_map(static fn ($s) => (string) $s['title'], $sections));
        $html = '<p style="font-size:11pt">This manual covers: <b>' . htmlspecialchars($names) . '</b>. More sections follow.</p>'
            . '<p style="font-size:10pt">It is made from the same instructions as the <b>User Manual</b> button on every screen of the system, so the two always match. '
            . 'Instructions were checked against the system on <b>' . $this->day($checked) . '</b>.</p>'
            . '<p style="font-size:10pt">Quick help while you work: click <b>User Manual</b> at the bottom right of any screen and ask a question in your own words, for example <i>Where do I go to approve hotel rates?</i></p>'
            . '<p style="font-size:9pt;color:#666">Printed copies go out of date when the system changes. The newest copy is always under <b>User Manual → View PDF</b>.</p>';
        $pdf->writeHTML($html, true, false, true, false, '');

        // ---- sections ----
        $general = $m->sections()['general'] ?? null;
        if ($general) {
            $this->section($pdf, $general, false);
        }
        foreach ($sections as $s) {
            $this->section($pdf, $s, true);
        }

        // ---- contents after the cover ----
        $pdf->addTOCPage();
        $pdf->SetFont($this->font, 'B', 16);
        $pdf->SetTextColor(...self::PURPLE);
        $pdf->Cell(0, 12, 'Contents', 0, 1);
        $pdf->SetTextColor(40, 30, 50);
        $pdf->SetFont($this->font, '', 10);
        $pdf->addTOC(2, $this->font, '.', 'Contents', '', [75, 42, 92]);
        $pdf->endTOCPage();

        $pdf->Output($file, 'F');
    }

    private function section(\TCPDF $pdf, array $s, bool $withGuide): void
    {
        $pdf->AddPage();
        $pdf->Bookmark((string) $s['title'], 0, 0, '', 'B', self::PURPLE);
        $this->h1($pdf, (string) $s['title']);
        if (!empty($s['intro'])) {
            $pdf->writeHTML('<p style="font-size:10.5pt">' . HelpManual::md((string) $s['intro']) . '</p>', true, false, true, false, '');
        }
        if ($withGuide && !empty($s['guide']['stages'])) {
            $pdf->Bookmark((string) ($s['guide']['title'] ?? 'Step by step'), 1, 0, '', '', [60, 60, 60]);
            $this->h2($pdf, (string) ($s['guide']['title'] ?? 'Step by step'));
            if (!empty($s['guide']['intro'])) {
                $pdf->writeHTML('<p style="font-size:10pt">' . HelpManual::md((string) $s['guide']['intro']) . '</p>', true, false, true, false, '');
            }
            $n = 0;
            foreach ($s['guide']['stages'] as $st) {
                $this->h3($pdf, (string) $st['title'], (string) ($st['who'] ?? ''));
                foreach ((array) $st['steps'] as $g) {
                    $n++;
                    $img = !empty($g['image']) ? APPPATH . 'Help/images/' . $g['image'] : '';
                    $imgH = 0.0;
                    if ($img !== '' && is_file($img)) {
                        [$w, $h] = getimagesize($img);
                        $imgH = 178 * $h / max(1, $w);
                    }
                    // keep a step's words and its picture on one page
                    if ($pdf->GetY() + $imgH + 22 > $pdf->getPageHeight() - 18) {
                        $pdf->AddPage();
                    }
                    $pdf->writeHTML('<p style="font-size:10.5pt"><b>Step ' . $n . '. ' . htmlspecialchars((string) $g['title']) . '</b><br>' . HelpManual::md((string) ($g['text'] ?? '')) . '</p>', true, false, true, false, '');
                    if ($imgH > 0) {
                        $pdf->Image($img, 16, $pdf->GetY() + 1, 178, 0, '', '', 'N', true, 150, '', false, false, 1);
                        $pdf->Ln(4);
                    }
                }
            }
        }
        $topics = array_values(array_filter($this->manual->topics(), static fn ($t) => $t['section'] === $s['id']));
        if ($topics) {
            if ($withGuide) {
                $pdf->AddPage();
            }
            $pdf->Bookmark('Questions and answers', 1, 0, '', '', [60, 60, 60]);
            $this->h2($pdf, $withGuide ? 'Questions and answers' : 'Getting help');
            foreach ($topics as $t) {
                $this->topic($pdf, $t);
            }
        }
    }

    private function topic(\TCPDF $pdf, array $t): void
    {
        if ($pdf->GetY() > $pdf->getPageHeight() - 70) {
            $pdf->AddPage();
        }
        $pdf->Bookmark((string) $t['title'], 2, 0, '', '', [90, 90, 90]);
        $needs = [];
        foreach ((array) ($t['needs'] ?? []) as $u) {
            $needs[] = $this->manual->menuName((string) $u);
        }
        $html = '<table cellpadding="5" style="border-left:3px solid #4b2a5c"><tr><td>'
            . '<span style="font-size:12pt;color:#4b2a5c"><b>' . htmlspecialchars((string) $t['title']) . '</b></span><br>'
            . '<span style="font-size:8.5pt;color:#666">'
            . (!empty($t['audience']) ? 'Who: ' . htmlspecialchars((string) $t['audience']) : '')
            . ($needs ? ' · Permission: ' . htmlspecialchars(implode(', ', $needs)) : '')
            . (!empty($t['questions'][0]) ? '<br>Ask: “' . htmlspecialchars((string) $t['questions'][0]) . '”' : '')
            . '</span></td></tr></table>'
            . '<p style="font-size:10pt">' . HelpManual::md((string) ($t['summary'] ?? '')) . '</p>';
        if (!empty($t['steps'])) {
            $html .= '<ol style="font-size:10pt">';
            foreach ($t['steps'] as $st) {
                $extra = '';
                if (!empty($st['needs'])) {
                    $extra = ' <span style="color:#9b3029">(needs ' . htmlspecialchars($this->manual->menuName((string) $st['needs'])) . ')</span>';
                }
                $html .= '<li>' . HelpManual::md((string) $st['text']) . $extra . '</li>';
            }
            $html .= '</ol>';
        }
        foreach ((array) ($t['notes'] ?? []) as $n) {
            $html .= '<p style="font-size:9.5pt;color:#444">Note: ' . HelpManual::md((string) $n) . '</p>';
        }
        if (!empty($t['needs_note'])) {
            $html .= '<p style="font-size:9pt;color:#7a5a00">Without the permission: ' . HelpManual::md((string) $t['needs_note']) . '</p>';
        }
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(2);
    }

    private function h1(\TCPDF $pdf, string $t): void
    {
        $pdf->SetFont($this->font, 'B', 20);
        $pdf->SetTextColor(...self::PURPLE);
        $pdf->Cell(0, 12, $t, 0, 1);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetFont($this->font, '', 10);
    }

    private function h2(\TCPDF $pdf, string $t): void
    {
        $pdf->Ln(2);
        $pdf->SetFont($this->font, 'B', 15);
        $pdf->SetTextColor(...self::PURPLE);
        $pdf->Cell(0, 10, $t, 0, 1);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetFont($this->font, '', 10);
    }

    private function h3(\TCPDF $pdf, string $t, string $who): void
    {
        if ($pdf->GetY() > $pdf->getPageHeight() - 80) {
            $pdf->AddPage();
        }
        $pdf->Ln(2);
        $pdf->SetFillColor(243, 237, 247);
        $pdf->SetFont($this->font, 'B', 12);
        $pdf->SetTextColor(...self::PURPLE);
        $pdf->Cell(0, 9, ' ' . $t, 0, 1, 'L', true);
        if ($who !== '') {
            $pdf->SetFont($this->font, 'I', 9);
            $pdf->SetTextColor(90, 90, 90);
            $pdf->Cell(0, 6, ' Who: ' . $who, 0, 1);
        }
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetFont($this->font, '', 10);
        $pdf->Ln(1);
    }

    private function day(string $ymd): string
    {
        $t = strtotime($ymd);
        return $t ? date('j F Y', $t) : $ymd;
    }
}
