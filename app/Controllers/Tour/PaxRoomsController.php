<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\TourFlow\PaxRooms;
use App\Libraries\TourFlow\Staff;

/**
 * tf-paxrooms-r1 (19 Nov 2026) — the Passengers tab of a Tour Inquiry: passengers, the room requirements of each hotel
 * stay, rooms and sharing, the passenger list (Excel) and the rooming list (PDF). Routes sit in the tour/intake group, so
 * the same sign-in and permission filters apply as to the rest of the inquiry. Library: TourFlow\PaxRooms.
 */
class PaxRoomsController extends BaseController
{
    private function db()
    {
        return \Config\Database::connect();
    }

    private function lib(): PaxRooms
    {
        $uid = (int) session()->get('user_id');
        return new PaxRooms($this->db(), Staff::displayName($this->db(), $uid > 0 ? $uid : null, (string) session()->get('username')));
    }

    private function out(array $payload)
    {
        return $this->response->setJSON($payload);
    }

    private function body(): array
    {
        if (stripos((string) $this->request->getHeaderLine('Content-Type'), 'json') === false) {
            return $this->request->getPost() ?: [];
        }
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : [];
    }

    // GET intake/(:num)/paxrooms
    public function data($id)
    {
        try {
            return $this->out(['status' => 200, 'data' => $this->lib()->data((int) $id)]);
        } catch (\Throwable $e) {
            return $this->out(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    // POST intake/(:num)/paxrooms/(:segment)
    public function act($id, $what)
    {
        $id = (int) $id;
        try {
            $lib = $this->lib();
            $b = $this->body();
            $stay = (string) ($b['stay'] ?? '');
            $msg = 'Saved.';
            $extra = [];
            switch ((string) $what) {
                case 'reqs':
                    $lib->saveReqs($id, $stay, (array) ($b['rows'] ?? []));
                    $msg = 'Room requirements saved.';
                    break;
                case 'create_rooms':
                    $n = $lib->createRooms($id, $stay);
                    $msg = $n ? $n . ' room entr' . ($n === 1 ? 'y' : 'ies') . ' created from the requirements.' : 'Every required room already has its entry — nothing was added.';
                    break;
                case 'room':
                    $extra['room_id'] = $lib->saveRoom($id, $stay, !empty($b['id']) ? (int) $b['id'] : null, $b);
                    $msg = 'Room saved.';
                    break;
                case 'room_delete':
                    $lib->deleteRoom($id, (int) ($b['id'] ?? 0));
                    $msg = 'Room removed.';
                    break;
                case 'rooms_category':
                    $n = $lib->roomsCategory($id, $stay, (array) ($b['ids'] ?? []), $b['category'] ?? '');
                    $msg = 'Category changed on ' . $n . ' room' . ($n === 1 ? '' : 's') . '.';
                    break;
                case 'assign':
                    $lib->assign($id, $stay, (int) ($b['room_id'] ?? 0), (array) ($b['pax_ids'] ?? []));
                    $msg = !empty($b['room_id']) ? 'Passengers placed in the room.' : 'Passengers taken out of their room.';
                    break;
                case 'copy':
                    $lib->copyStay($id, (string) ($b['from'] ?? ''), (string) ($b['to'] ?? ''));
                    $msg = 'Arrangement copied.';
                    break;
                case 'pax':
                    $pid = $lib->savePax($id, !empty($b['id']) ? (int) $b['id'] : null, $b);
                    $extra['pax_id'] = $pid;
                    $msg = !empty($b['id']) ? 'Passenger updated.' : 'Passenger added.';
                    // the room alongside the name: an existing room of the stay, or a new one of the type and category staff chose
                    if ($stay !== '' && array_key_exists('room_id', $b)) {
                        $rid = (string) $b['room_id'];
                        if ($rid === 'new') {
                            $rid = $lib->saveRoom($id, $stay, null, ['room_type' => $b['room_type'] ?? '', 'category' => $b['category'] ?? '']);
                        } elseif ((int) $rid > 0 && (isset($b['room_type']) || isset($b['category']))) {
                            $lib->saveRoom($id, $stay, (int) $rid, ['room_type' => $b['room_type'] ?? '', 'category' => $b['category'] ?? '']);
                        }
                        $cur = null;
                        foreach ($lib->data($id)['stays'] as $s) {
                            if ($s['key'] === $stay) {
                                foreach ($s['rooms'] as $r) {
                                    if (in_array($pid, $r['pax'], true)) {
                                        $cur = $r['id'];
                                    }
                                }
                            }
                        }
                        if ((int) $rid !== (int) $cur) {
                            $lib->assign($id, $stay, (int) $rid, [$pid]);
                        }
                    }
                    break;
                case 'pax_delete':
                    $n = $lib->deletePax($id, (array) ($b['ids'] ?? []));
                    $msg = $n . ' passenger' . ($n === 1 ? '' : 's') . ' deleted.';
                    break;
                case 'import':
                    $r = $lib->importPax($id, (array) ($b['rows'] ?? []));
                    $msg = $r['added'] . ' passenger' . ($r['added'] === 1 ? '' : 's') . ' imported.' . ($r['errors'] ? ' ' . implode(' ', $r['errors']) : '');
                    break;
                case 'parse':
                    $f = $this->request->getFile('file');
                    if (!$f || !$f->isValid()) {
                        throw new \InvalidArgumentException('Choose an Excel (.xlsx) or CSV file.');
                    }
                    return $this->out(['status' => 200, 'rows' => PaxRooms::parseFile($f->getTempName(), $f->getClientName())]);
                default:
                    throw new \InvalidArgumentException('Unknown action.');
            }
            return $this->out(['status' => 200, 'message' => $msg, 'data' => $lib->data($id)] + $extra);
        } catch (\Throwable $e) {
            return $this->out(['status' => 400, 'message' => $e->getMessage()]);
        }
    }

    private static function roomOf(array $stay, int $paxId): ?array
    {
        foreach ($stay['rooms'] as $r) {
            if (in_array($paxId, $r['pax'], true)) {
                return $r;
            }
        }
        return null;
    }

    // GET intake/(:num)/paxrooms_export — the passenger list, with each hotel's room beside every name
    public function export($id)
    {
        $d = $this->lib()->data((int) $id);
        $head = ['#', 'Passenger Name (as per passport)', 'Type', 'Nationality', 'Passport No.', 'Gender', 'Date of Birth'];
        foreach ($d['stays'] as $s) {
            $head[] = $s['hotel'] . ' (' . ($s['check_in'] ? date('j M', strtotime($s['check_in'])) : 'day ' . $s['day_from']) . ') — room';
        }
        $rows = [$head];
        foreach ($d['passengers'] as $i => $p) {
            $row = [$i + 1, $p['full_name'], ucfirst((string) ($p['pax_type'] ?: 'adult')), (string) $p['nationality'], (string) $p['passport_no'], (string) $p['gender'], (string) $p['dob']];
            foreach ($d['stays'] as $s) {
                $r = self::roomOf($s, (int) $p['id']);
                $row[] = $r ? trim($r['room_no'] . ' ' . PaxRooms::TYPES[$r['room_type']] . ' ' . (string) $r['category']) : '';
            }
            $rows[] = $row;
        }
        $name = 'Passenger-List-' . preg_replace('/[^A-Za-z0-9]+/', '', (string) $d['inquiry']['inquiry_no']);
        if (!class_exists(\ZipArchive::class)) {
            $csv = "\xEF\xBB\xBF";
            foreach ($rows as $r) {
                $csv .= implode(',', array_map(static fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $r)) . "\r\n";
            }
            return $this->response->setHeader('Content-Type', 'text/csv; charset=UTF-8')->setHeader('Content-Disposition', 'attachment; filename="' . $name . '.csv"')->setBody($csv);
        }
        $x = static fn ($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $col = static function (int $i): string { $s = ''; for ($i++; $i > 0; $i = intdiv($i - 1, 26)) { $s = chr(65 + ($i - 1) % 26) . $s; } return $s; };
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="1" width="5" customWidth="1"/><col min="2" max="2" width="38" customWidth="1"/><col min="3" max="' . count($head) . '" width="20" customWidth="1"/></cols><sheetData>';
        foreach ($rows as $ri => $r) {
            $sheet .= '<row r="' . ($ri + 1) . '">';
            foreach ($r as $ci => $v) {
                $ref = $col($ci) . ($ri + 1);
                $sheet .= is_int($v) ? '<c r="' . $ref . '"><v>' . $v . '</v></c>' : '<c r="' . $ref . '" t="inlineStr"' . ($ri === 0 ? ' s="1"' : '') . '><is><t xml:space="preserve">' . $x($v) . '</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData></worksheet>';
        $tmp = tempnam(sys_get_temp_dir(), 'pl');
        $z = new \ZipArchive();
        $z->open($tmp, \ZipArchive::OVERWRITE);
        $z->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $z->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $z->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Passengers" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $z->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $z->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf/><xf fontId="1" applyFont="1"/></cellXfs></styleSheet>');
        $z->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $z->close();
        $bin = (string) file_get_contents($tmp);
        @unlink($tmp);
        return $this->response->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')->setHeader('Content-Disposition', 'attachment; filename="' . $name . '.xlsx"')->setBody($bin);
    }

    // GET intake/(:num)/paxrooms_pdf?stay=<key>|all — the rooming list of one hotel stay, or of every stay (a page each)
    public function pdf($id)
    {
        $lib = $this->lib();
        $d = $lib->data((int) $id);
        $want = (string) ($this->request->getGet('stay') ?? 'all');
        $e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $names = [];
        foreach ($d['passengers'] as $p) {
            $names[(int) $p['id']] = $p;
        }
        if (!class_exists('\TCPDF')) {
            require_once ROOTPATH . 'vendor/tecnickcom/tcpdf/tcpdf.php';   // in vendor/ but not in composer's class map on this server
        }
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->SetTitle('Rooming List ' . $d['inquiry']['inquiry_no']);
        $P = '#8b1d40';
        $done = 0;
        foreach ($d['stays'] as $s) {
            if ($want !== 'all' && $want !== $s['key']) {
                continue;
            }
            $done++;
            $pdf->AddPage();
            $state = ['requested' => 'Requested arrangement — not yet confirmed by the hotel', 'sent' => 'Requested from the hotel — awaiting confirmation', 'confirmed' => 'Confirmed by the hotel', 'amend' => 'Changed after the hotel booking — amendment to review'][$s['state']];
            $ref = trim(($d['tour']['tour_no'] ?? '') !== '' ? $d['tour']['tour_no'] . ' · ' . $d['inquiry']['inquiry_no'] : (string) $d['inquiry']['inquiry_no']);
            $html = '<table width="100%" cellpadding="0"><tr><td width="60%"><span style="font-size:17pt;color:' . $P . ';font-weight:bold;">ROOMING LIST</span><br><span style="font-size:10pt;">Avenir Holidays (Pvt) Ltd</span></td>'
                . '<td width="40%" align="right" style="font-size:9.5pt;">Reference <b>' . $e($ref) . '</b><br>Printed ' . date('j M Y') . '</td></tr></table>'
                . '<hr style="color:' . $P . ';"><table width="100%" cellpadding="3" style="font-size:10pt;"><tr><td width="55%"><b>' . $e($s['hotel']) . '</b></td><td width="45%" align="right">' . $e($state) . '</td></tr>'
                . '<tr><td>Check-in <b>' . ($s['check_in'] ? date('D j M Y', strtotime($s['check_in'])) : 'day ' . $s['day_from']) . '</b> &nbsp; Check-out <b>' . ($s['check_out'] ? date('D j M Y', strtotime($s['check_out'])) : '—') . '</b></td><td align="right">' . (int) $s['nights'] . ' night' . ((int) $s['nights'] === 1 ? '' : 's') . '</td></tr></table>';
            $rooms = $s['rooms'];
            if (!$rooms) {
                $n = 1;
                foreach ($s['reqs'] as $q) {
                    for ($i = 0; $i < $q['rooms']; $i++) {
                        $rooms[] = ['room_no' => 'R' . str_pad((string) $n++, 2, '0', STR_PAD_LEFT), 'room_type' => $q['room_type'], 'category' => $q['category'], 'pax' => []];
                    }
                }
            }
            $th = 'style="background-color:' . $P . ';color:#ffffff;font-weight:bold;font-size:9pt;"';
            $html .= '<br><table width="100%" cellpadding="4" border="0" style="font-size:9.5pt;"><tr><td width="10%" ' . $th . '>Room</td><td width="13%" ' . $th . '>Room Type</td><td width="19%" ' . $th . '>Category</td><td width="11%" ' . $th . ' align="center">Occupancy</td><td width="47%" ' . $th . '>Guests</td></tr>';
            $counts = [];
            $guests = 0;
            $in = [];
            foreach ($rooms as $k => $r) {
                $g = [];
                foreach ($r['pax'] as $pid) {
                    if (isset($names[$pid])) {
                        $t = strtolower((string) ($names[$pid]['pax_type'] ?: 'adult'));
                        $g[] = $e(str_replace('/', ' ', (string) $names[$pid]['full_name'])) . ($t !== 'adult' ? ' <span style="color:#666666;">(' . $t . ')</span>' : '');
                        $in[$pid] = true;
                    }
                }
                $guests += count($g);
                $w = PaxRooms::TYPES[$r['room_type']] . ($r['category'] ? ' ' . $r['category'] : '');
                $counts[$w] = ($counts[$w] ?? 0) + 1;
                $bg = $k % 2 ? ' style="background-color:#f8f1f3;"' : '';
                $html .= '<tr nobr="true"' . $bg . '><td style="border-bottom:1px solid #dddddd;"><b>' . $e($r['room_no']) . '</b></td><td style="border-bottom:1px solid #dddddd;">' . PaxRooms::TYPES[$r['room_type']] . '</td><td style="border-bottom:1px solid #dddddd;">' . ($r['category'] ? $e($r['category']) : '—')
                    . '</td><td style="border-bottom:1px solid #dddddd;" align="center">' . count($g) . ' / ' . PaxRooms::BEDS[$r['room_type']] . '</td><td style="border-bottom:1px solid #dddddd;">' . ($g ? implode('<br>', $g) : '<span style="color:#888888;">Names to follow</span>') . '</td></tr>';
            }
            if (!$rooms) {
                $html .= '<tr><td colspan="5" style="color:#888888;">No room requirements or rooms have been entered for this hotel yet.</td></tr>';
            }
            $html .= '</table><br><table width="100%" cellpadding="3" style="font-size:10pt;"><tr><td><b>Total rooms: ' . count($rooms) . '</b> &nbsp; ' . $e(implode(', ', array_map(static fn ($w, $n) => $n . ' ' . $w, array_keys($counts), $counts)))
                . '</td><td align="right"><b>Guests named: ' . $guests . '</b> of ' . count($d['passengers']) . '</td></tr></table>';
            if ($s['rooms']) {
                $un = [];
                foreach ($d['passengers'] as $p) {
                    if (!isset($in[(int) $p['id']])) {
                        $un[] = $e(str_replace('/', ' ', (string) $p['full_name']));
                    }
                }
                if ($un) {
                    $html .= '<p style="font-size:9.5pt;color:#a3123a;"><b>Not yet in a room at this hotel:</b> ' . implode(', ', $un) . '</p>';
                }
            }
            $pdf->writeHTML($html, true, false, true, false, '');
        }
        if (!$done) {
            $pdf->AddPage();
            $pdf->writeHTML('<p>No hotel stay on this inquiry yet.</p>');
        }
        return $this->response->setHeader('Content-Type', 'application/pdf')->setHeader('Content-Disposition', 'inline; filename="Rooming-List-' . preg_replace('/[^A-Za-z0-9]+/', '', (string) $d['inquiry']['inquiry_no']) . '.pdf"')->setBody($pdf->Output('rooming.pdf', 'S'));
    }
}
