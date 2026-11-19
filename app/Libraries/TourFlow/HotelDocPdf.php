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
    public string $subTitle = '';
    public string $chip = '';

    public const P = [132, 36, 132];   // #8b1d40
    public const G = [120, 184, 72];   // #78b848
    public const T  = [0, 77, 80];     // #004D50 — tf-hotelreq-r3: the teal of the Hotel Booking Request mock-up
    public const GM = [120, 184, 72];  // #78B848 — the green of the same mock-up

    public function Header()
    {
        $w = $this->getPageWidth();
        if ($this->getPage() === 1) {
            if ($this->kind === 'rooming') {
                $this->SetFillColor(255, 255, 255);
                if ($this->logo) {
                    $this->Image($this->logo, ($w - 64) / 2, 5, 64, 0, 'PNG');
                }
                // tf-hotelreq-r8 (Shantha): no green lines on the Rooming List; the band sits clear of the logo
                $this->SetFillColor(107, 114, 120);
                $this->Rect(0, 29, $w, 16, 'F');
                $this->SetTextColor(255, 255, 255);
                $this->SetFont('helvetica', 'B', 22);
                $this->SetXY(0, 31);
                $this->Cell($w, 12, $this->docTitle, 0, 0, 'C');
            } else {
                // tf-hotelreq-r5 (19 Nov 2026, Shantha's mock-up): a white page — the official logo untouched, a rule, the title in teal,
                // and the state of the request under it
                if ($this->logo) {
                    $this->Image($this->logo, 13, 3.5, 90, 0, 'PNG');
                }
                $this->SetDrawColor(0, 102, 102);
                $this->SetLineWidth(0.4);
                $this->Line(111.8, 5, 111.8, 30);
                $this->SetTextColor(...self::T);
                $this->SetFont('dejavusans', 'B', 21.5);
                $this->SetXY(122, 2.6);
                $this->MultiCell($w - 124, 8.7, $this->docTitle, 0, 'L', false, 1);
                if ($this->chip !== '') {
                    $this->SetFillColor(232, 243, 222);
                    $this->RoundedRect(121.3, 22.8, $w - 121.3 - 10.5, 8.5, 1.6, '1111', 'F');
                    $this->SetFillColor(...self::G);
                    $bx = 126; $by = 24.6;
                    $this->Rect($bx, $by, 0.8, 4.9, 'F'); $this->Rect($bx, $by + 2.9, 6.6, 1, 'F'); $this->Rect($bx + 5.8, $by + 2, 0.8, 2.9, 'F');
                    $this->Rect($bx + 1.3, $by + 1.4, 1.6, 1.2, 'F'); $this->Rect($bx + 3.3, $by + 1.7, 2.5, 1, 'F');
                    $this->SetFont('dejavusans', 'B', 10.5);
                    $this->SetXY(136, 22.8);
                    $this->Cell($w - 148, 8.5, $this->chip, 0, 0, 'L');
                } elseif ($this->subTitle !== '') {
                    $this->SetFont('dejavusans', 'I', 12);
                    $this->SetTextColor(32, 43, 48);
                    $this->SetXY(122.5, 23);
                    $this->Cell($w - 126, 6.5, $this->subTitle, 0, 0, 'L');
                }
                $this->SetDrawColor(...self::G);
                $this->SetLineWidth(1.1);
                $this->Line(8.2, 35.6, $w - 8.2, 35.6);
            }
        } else {
            $this->SetFillColor(...($this->kind === 'rooming' ? [107, 114, 120] : [0, 102, 102]));   // tf-hotelreq-r7: the Rooming List in grey #6B7278; the Booking Request stays teal
            $this->Rect(0, 0, $w, 15, 'F');
            if ($this->kind !== 'rooming') {
                $this->SetFillColor(...self::G);
                $this->Rect(0, 15, $w, 1.2, 'F');
            }
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
        if ($this->kind !== 'rooming') {   // tf-hotelreq-r3: the mock-up's foot — the company, and the page
            $this->SetDrawColor(...self::GM);
            $this->SetLineWidth(0.6);
            $this->Line(9, $this->getPageHeight() - 10.5, $w - 9, $this->getPageHeight() - 10.5);
            $this->SetY(-9.5);
            $this->SetX(9);
            $this->SetFont('dejavusans', 'I', 8.5);
            $this->SetTextColor(32, 43, 48);
            $this->Cell(($w - 18) * 0.6, 6, $this->getPage() > 1 ? $this->docTitle . ' ' . $this->control : '', 0, 0, 'L');
            $this->SetFont('dejavusans', '', 9);
            $this->Cell(($w - 18) * 0.4, 6, $this->getAliasRightShift() . 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'R');
            return;
        }
        $this->SetY(-12);
        $this->SetDrawColor(184, 200, 200);   // the Rooming List's foot rule: grey, not green
        $this->SetLineWidth(0.4);
        $this->Line(12, $this->GetY() - 1, $w - 12, $this->GetY() - 1);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(85, 80, 90);
        $this->Cell(($w - 24) * 0.7, 6, $this->footerLine, 0, 0, 'L');
        $this->Cell(($w - 24) * 0.3, 6, $this->control . '  ·  Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}
