<?php

namespace App\Libraries\TourFlow;

if (!class_exists('\TCPDF')) {
    require_once ROOTPATH . 'vendor' . DIRECTORY_SEPARATOR . 'tecnickcom' . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'tcpdf.php';
}

/**
 * tf-resdesk-r2 (13 Nov 2026) — the page frame of the two hotel reservation documents (Shantha's mock-ups):
 *   request  — "HOTEL BOOKING REQUEST", A4 portrait: purple band with the logo, green line;
 *   rooming  — "HOTEL ROOMING LIST", A4 landscape: logo, purple band, green lines.
 * Every page after the first repeats a slim band with the title and the Reservation Control No., and every page carries
 * "Page n of m" with the number in the footer — a long rooming list continues onto further pages at the same size.
 */
class HotelDocPdf extends \TCPDF
{
    public string $kind = 'request';
    public string $control = '';
    public string $docTitle = '';
    public ?string $logo = null;
    public string $footerLine = '';

    public const P = [132, 36, 132];   // #842484
    public const G = [120, 184, 72];   // #78b848

    public function Header()
    {
        $w = $this->getPageWidth();
        if ($this->getPage() === 1) {
            if ($this->kind === 'rooming') {
                $this->SetFillColor(255, 255, 255);
                if ($this->logo) {
                    $this->Image($this->logo, ($w - 64) / 2, 5, 64, 0, 'PNG');
                }
                $this->SetFillColor(...self::P);
                $this->Rect(0, 26, $w, 17, 'F');
                $this->SetFillColor(...self::G);
                $this->Rect(0, 24.4, $w, 1.6, 'F');
                $this->Rect(0, 43, $w, 1.6, 'F');
                $this->SetTextColor(255, 255, 255);
                $this->SetFont('helvetica', 'B', 22);
                $this->SetXY(0, 28.5);
                $this->Cell($w, 12, $this->docTitle, 0, 0, 'C');
            } else {
                $this->SetFillColor(...self::P);
                $this->Rect(0, 0, $w, 40, 'F');
                $this->SetFillColor(...self::G);
                $this->Rect(0, 40, $w, 1.8, 'F');
                $this->SetFillColor(255, 255, 255);
                $this->RoundedRect(10, 6, 86, 28, 4, '1111', 'F');
                if ($this->logo) {
                    $this->Image($this->logo, 16, 8.4, 74, 0, 'PNG');
                }
                $this->SetDrawColor(255, 255, 255);
                $this->SetLineWidth(0.6);
                $this->Line(104, 9, 104, 31);
                $this->SetTextColor(255, 255, 255);
                $this->SetFont('helvetica', 'B', 22);
                $this->SetXY(110, 9);
                $this->MultiCell($w - 118, 22, $this->docTitle, 0, 'L', false, 1, '', '', true, 0, false, true, 22, 'M');
            }
        } else {
            $this->SetFillColor(...self::P);
            $this->Rect(0, 0, $w, 15, 'F');
            $this->SetFillColor(...self::G);
            $this->Rect(0, 15, $w, 1.2, 'F');
            $this->SetTextColor(255, 255, 255);
            $this->SetFont('helvetica', 'B', 11);
            $this->SetXY(12, 4);
            $this->Cell(0, 7, $this->docTitle . '  (continued)', 0, 0, 'L');
            $this->SetXY(12, 4);
            $this->Cell($w - 24, 7, 'Reservation Control No. ' . $this->control, 0, 0, 'R');
        }
        $this->SetTextColor(27, 22, 32);
    }

    public function Footer()
    {
        $w = $this->getPageWidth();
        $this->SetY(-12);
        $this->SetDrawColor(...self::G);
        $this->SetLineWidth(0.4);
        $this->Line(12, $this->GetY() - 1, $w - 12, $this->GetY() - 1);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(85, 80, 90);
        $this->Cell(($w - 24) * 0.7, 6, $this->footerLine, 0, 0, 'L');
        $this->Cell(($w - 24) * 0.3, 6, $this->control . '  ·  Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}
