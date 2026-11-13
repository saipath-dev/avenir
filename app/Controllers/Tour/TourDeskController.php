<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Activity;
use App\Libraries\TourFlow\CrewPeople;
use App\Libraries\TourFlow\CrewWarning;
use App\Libraries\TourFlow\Desks;
use App\Libraries\TourFlow\EmailTemplates;
use App\Libraries\TourFlow\HotelMail;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\IntakeService;
use App\Libraries\TourFlow\MealMaster;
use App\Libraries\TourFlow\QuoteService;
use App\Libraries\TourFlow\Reauth;
use App\Libraries\TourFlow\Staff;
use App\Libraries\TourFlow\TourRecord;
use App\Libraries\TourFlow\TourService;

/**
 * The desks after Confirm — three screens, one controller.
 *
 *   tour/hotel_bookings   the Hotel Desk: one row per confirmed tour, opened to its stays and activity lines
 *   tour/transport_ops    the Transport Desk: one row per confirmed tour, opened to its vehicle cards
 *   tour/board            Management's view of every tour, all three lanes
 *
 * A confirmed tour is ONE job on each desk (schema 21). The cards inside it — a stay, an
 * activity line, a vehicle — are its parts, never jobs of their own; the red count on the
 * bar is the number of tours still needing the desk's work, and a desk finishes a tour by
 * marking its work complete, never by removing anything.
 *
 * One menu entry = one privilege, as everywhere else: the route filter lets a group
 * in by the screen's URL, and every action a screen makes sits under that same URL.
 * Which desk sees what is decided by which screen it can open — a hotel card carries
 * no transport and no totals, a transport card carries kilometres and not rupees, and
 * the Board is the only place money from every lane sits together.
 */
class TourDeskController extends BaseController
{
    public const HOTEL     = 'tour/hotel_bookings';
    public const TRANSPORT = 'tour/transport_ops';
    public const BOARD     = 'tour/board';

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

    /** tf-crew-master-r1: "Driver & Guide bank details" — the bank on the payment screens */
    private const CREW_BANK_URL = 'master_files/crew_people/bank';

    private function svc(): TourService
    {
        $db = $this->db();
        $svc = new TourService($db, $this->user(), (new IntakeService($db, $this->user(), new HotelRateService($db, $this->user())))->withActor($this->userId(), $this->actorName()));
        $svc->bankVisible = $this->holds(self::CREW_BANK_URL);
        return $svc;
    }

    /** The filters every desk list shares, read off the query string. */
    private function filters(string $statusKey = 'status', string $default = 'open'): array
    {
        $g = fn (string $k) => (string) ($this->request->getGet($k) ?? '');
        return [$statusKey => $g($statusKey) ?: $default, 'q' => $g('q'), 'handler' => $g('handler'), 'from' => $g('from'), 'to' => $g('to')];
    }

    private function menuData(): array
    {
        if ($this->menuData === null) {
            try {
                $this->menuData = (new MenuLoader())->loadMenuData();
            } catch (\Throwable $e) {
                $this->menuData = ['user_role' => session()->get('group_id'), 'allowed_menu_list' => []];
            }
        }
        return $this->menuData;
    }

    /** Whether the signed-in group may open a URL — read from the same privileges the sidebar reads. */
    private function holds(string $url): bool
    {
        $md = $this->menuData();
        if ((int) ($md['user_role'] ?? 0) === 1) {
            return true;
        }
        foreach ((array) ($md['allowed_menu_list'] ?? []) as $m) {
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

    /** tf-pilot-r2 — a refused action: recorded in the payment desk's audit trail (access_denied) when it is installed, then 403. */
    private function refuse(string $message, string $what)
    {
        try {
            if (class_exists(\App\Libraries\TourPayments\Audit::class)) {
                \App\Libraries\TourPayments\Audit::log($this->db(), ['action' => 'access_denied', 'ref_type' => 'action', 'ref_id' => mb_substr((string) service('uri')->getRoutePath(), 0, 80), 'reason' => mb_substr('privilege: ' . $what, 0, 500), 'new' => ['method' => strtoupper($this->request->getMethod())]]);
            }
        } catch (\Throwable $e) {
        }
        if (strtoupper($this->request->getMethod()) === 'GET' && !$this->request->isAJAX()) {
            return redirect()->to(base_url('403'));
        }
        return $this->json(['status' => 403, 'message' => $message . ' The attempt was recorded.'], 403);
    }

    /** tf-pilot-r2 — may this sign-in allocate (engage crew, set the vehicle, the days worked)? */
    private function canAllocate(): bool
    {
        return $this->holds(Desks::ALLOCATION_URL) || in_array((int) ($this->menuData()['user_role'] ?? 0), Desks::LEGACY_ALLOCATION_GROUPS, true);
    }

    // GET tour/transport_ops/crew_change — tf-pilot-r2: "Crew replacement" is a privilege entry (schema 24) that had no page, so the
    // menu link answered 404. It now opens the Transport Desk, where "Replace…" sits on each engaged driver / guide line of a
    // card — the one replacement workflow there is; nothing is duplicated.
    public function crewChangePage()
    {
        if (!$this->holds(Desks::CREW_CHANGE_URL)) {
            return $this->refuse('Replacing a driver or guide mid-tour needs the Crew replacement privilege.', 'crew replacement (page)');
        }
        return redirect()->to(base_url(self::TRANSPORT) . '?focus=crew_change');
    }

    // GET tour/transport_ops/driver_advance — the same for the "Driver advance" privilege entry (schema 26): the advance and the
    // settlement are on the card's payment section of the Transport Desk.
    public function driverAdvancePage()
    {
        if (!$this->holds(Desks::DRIVER_ADVANCE_URL)) {
            return $this->refuse('Approving, paying and settling a driver advance needs the Driver advance privilege.', 'driver advance (page)');
        }
        return redirect()->to(base_url(self::TRANSPORT) . '?focus=driver_advance');
    }

    private function fail(\Throwable $e, int $status = 400)
    {
        return $this->json(['status' => $status, 'message' => $e->getMessage()]);
    }

    private function activity(): Activity
    {
        return new Activity($this->db(), $this->user(), $this->userId(), $this->actorName());
    }

    /**
     * The card before an action, so the action's change is written field by field — who,
     * when, from what to what — beside the desk's own trail line. A snapshot that cannot
     * be taken costs the action nothing.
     */
    private function before(string $kind, int $id): ?array
    {
        try {
            $a = $this->activity();
            return $kind === 'hotel_job' ? $a->snapshotHotelJob($id) : ($kind === 'service_job' ? $a->snapshotServiceJob($id) : $a->snapshotTransportJob($id));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function after(string $kind, int $id, ?array $before, string $action, ?array $card = null): void
    {
        if ($before === null) {
            return;
        }
        try {
            $a = $this->activity();
            $now = $kind === 'hotel_job' ? $a->snapshotHotelJob($id) : ($kind === 'service_job' ? $a->snapshotServiceJob($id) : $a->snapshotTransportJob($id));
            $a->logDiff($kind, $id, isset($card['intake_id']) ? (int) $card['intake_id'] : null, isset($card['tour_id']) ? (int) $card['tour_id'] : null, $action, $before, $now);
        } catch (\Throwable $e) {
            // the line is a record, never a gate
        }
    }

    private function page(string $view, string $url, array $extra = [])
    {
        $data = $this->menuData();
        $data['user']       = $this->user();
        $data['page_url']   = $url;
        $data['intake_url'] = TourIntakeController::URL;
        $data['installed']  = false;
        // the ⋯ Manage menus show only to a group holding Manage; the handling-agent filter shows to everyone
        $data['can_manage'] = $this->holds(Desks::MANAGE_URL);
        $data['manage_url'] = Desks::MANAGE_URL;
        // tf-walkfix-r1: the other desks, the inquiry and the Board are linked only for a sign-in that may open them
        $data['can_hotel']     = $this->holds(self::HOTEL);
        $data['can_transport'] = $this->holds(self::TRANSPORT);
        $data['can_intake']    = $this->holds(TourIntakeController::URL);
        $data['can_board']     = $this->holds(self::BOARD);
        // tf-tickets-r1: the Ticket Stock screen is linked from the Activities card only to a sign-in that may open it
        $data['can_tickets']   = $this->holds(TicketStockController::URL);
        $data['tickets_url']   = TicketStockController::URL;
        $data['handlers']   = [];
        $data['me_name']    = $this->actorName();   // the name the final warnings show as the person acting
        try {
            $version = Installer::installedVersion($this->db());
            $data['installed'] = $version !== null && $version >= Installer::SCHEMA_VERSION;
            if ($data['installed']) {
                $data['handlers'] = Staff::handlers($this->db());
            }
        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }
        return view($view, $data + $extra);
    }

    // =====================================================================
    //  Hotel Desk
    // =====================================================================

    // GET tour/hotel_bookings
    public function hotel()
    {
        // the contract rate belongs on the card only for a group that also holds Hotel Rates (decision B)
        return $this->page('pages/tour/hotel_bookings', self::HOTEL, ['sees_rates' => $this->holds('master_files/hotel_rate_v2')]);
    }

    /** The filters a desk's tour list reads: which chip, and the ones every list shares. */
    private function tourFilters(): array
    {
        $g = fn (string $k) => (string) ($this->request->getGet($k) ?? '');
        return ['which' => $g('which') ?: 'pending', 'q' => $g('q'), 'handler' => $g('handler'), 'from' => $g('from'), 'to' => $g('to')];
    }

    // GET tour/hotel_bookings/tours?which=pending|completed|cancelled|all&q=&handler=&from=&to= — one row per tour
    public function hotelTours()
    {
        try {
            $svc = $this->svc();
            // tf-resdesk-r2: each row carries "New booking" (not yet opened by this desk) and the hotel confirmation progress
            $rd = new \App\Libraries\TourFlow\ResDesk($this->db());
            return $this->json(['status' => 200, 'data' => ['tours' => $rd->enrichTours($svc->deskTours('hotel', $this->tourFilters())), 'counts' => $svc->deskCounts('hotel'),
                'test_mailbox' => \App\Libraries\TourFlow\TestMailbox::active()]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 500);
        }
    }

    // GET tour/hotel_bookings/tour/(:num)/work — the tour opened: its stays and its activity lines
    public function hotelWork($tourId)
    {
        try {
            $svc = $this->svc();
            $id  = (int) $tourId;
            $rows = $svc->deskTours('hotel', ['which' => 'all']);
            $tour = current(array_filter($rows, static fn ($r) => $r['id'] === $id)) ?: null;
            if (!$tour) {
                return $this->json(['status' => 404, 'message' => 'Tour not found.'], 404);
            }
            // tf-resdesk-r2: opening the tour takes its "New booking" label off (who and when are kept) — it confirms nothing
            $rd = new \App\Libraries\TourFlow\ResDesk($this->db());
            $firstOpen = $rd->markSeen($id, $this->actorName());
            $tour = $rd->enrichTours([$tour])[0];
            return $this->json(['status' => 200, 'data' => [
                'tour'       => $tour,
                'first_open' => $firstOpen,
                'stays'      => $rd->enrichStays($svc->hotelQueue(['status' => 'all', 'tour_id' => $id])),
                'activities' => $svc->serviceQueue(['status' => 'all', 'tour_id' => $id]),
                'reasons'    => $svc->reasons('hotel_problem'),
                'a_reasons'  => $svc->reasons('service_problem'),
                'strip'      => $tour['strip'],
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 500);
        }
    }

    // GET tour/hotel_bookings/data?status=&q=&handler=&from=&to=
    public function hotelData()
    {
        try {
            $svc = $this->svc();
            $items = $svc->hotelQueue($this->filters());
            $strips = [];
            foreach ($items as $j) {
                $tid = (int) $j['tour_id'];
                if (!isset($strips[$tid])) {
                    $strips[$tid] = $svc->stripFor($tid);
                }
            }
            return $this->json(['status' => 200, 'data' => [
                'items'   => $items,
                'counts'  => $svc->hotelCounts(),
                'reasons' => $svc->reasons('hotel_problem'),
                'strips'  => $strips,
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 500);
        }
    }

    // ---- tf-resdesk-r2 (12 Nov 2026): Confirm Booking, the payment request it raises, the documents, the test mailbox ----

    /** The chain's own payment-request handler, called in place (it stays private there and has no route of its own). */
    private function chainRaiser(): callable
    {
        $req = $this->request;
        $res = $this->response;
        return static function (array $payload, string $user) use ($req, $res) {
            $c = new \App\Controllers\Tour\TourAccountsController();
            $c->initController($req, $res, \Config\Services::logger());
            $fn = \Closure::bind(function (array $p, string $u) {
                $key = 'req|' . (string) $p['tourId'] . '|' . (string) $p['hotelAcc'] . '|Hotel';
                return \App\Libraries\TourPayments\PayDesk::withItemLock(\Config\Database::connect(), $key, fn () => $this->paymentRequestFrom(static fn ($k) => $p[$k] ?? null, $u, true))
                    ?? json_encode(['status' => 409, 'message' => 'Another request for this item is being saved at this moment.']);
            }, $c, \App\Controllers\Tour\TourAccountsController::class);
            return $fn($payload, $user);
        };
    }

    private function raisePayment(int $jobId): array
    {
        $svc  = $this->svc();
        $card = $svc->hotelJob($jobId);
        $tour = $this->db()->query('SELECT t.*, i.inquiry_no FROM tf_tour t LEFT JOIN tf_intake i ON i.id = t.intake_id WHERE t.id = ?', [(int) $card['tour_id']])->getRowArray();
        $it   = (new IntakeService($this->db(), $this->user()))->get((int) $card['intake_id']) ?: [];
        $r = (new \App\Libraries\TourFlow\HotelPayLink($this->db(), $this->user()))->raise($card, $tour, $it, $this->chainRaiser());
        try {
            $svc->event((int) $card['intake_id'], (int) $card['tour_id'], 'hotel', $jobId, $r['state'] === 'failed' ? 'payment_not_raised' : 'payment_raised', (string) $card['status'], (string) $card['status'], $r['message']);
        } catch (\Throwable $e) {
        }
        return $r;
    }

    // GET tour/hotel_bookings/lists?list=pending|confirmed|other&q=&arrival=&page=&per= — tf-resdesk-r2: the Reservation Desk lists
    public function resLists()
    {
        try {
            $g = fn ($k) => (string) ($this->request->getGet($k) ?? '');
            $out = (new \App\Libraries\TourFlow\ResDesk($this->db()))->lists(['list' => $g('list'), 'q' => $g('q'), 'arrival' => $g('arrival'), 'from' => $g('from'), 'to' => $g('to'), 'page' => $g('page'), 'per' => $g('per')]);
            return $this->json(['status' => 200, 'data' => $out + ['test_mailbox' => \App\Libraries\TourFlow\TestMailbox::active()]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 500);
        }
    }

    // POST tour/hotel_bookings/card/(:num)/confirm_booking  multipart: ref, with, confirmed_on, note, evidence (file)
    // The hotel answered and confirmed: its reference (and the evidence, when there is a file) is recorded with who and when;
    // only then is the stay's hotel payment request raised to Payment Approval.
    public function confirmBooking($jobId)
    {
        $id = (int) $jobId;
        try {
            $db = $this->db();
            \App\Libraries\TourFlow\HotelBookingDocs::ensure($db);
            $svc = $this->svc();
            $ref = trim((string) ($this->request->getPost('ref') ?? ''));
            $with = trim((string) ($this->request->getPost('with') ?? '')) ?: null;
            $note = trim((string) ($this->request->getPost('note') ?? '')) ?: null;
            $on = trim((string) ($this->request->getPost('confirmed_on') ?? '')) ?: date('Y-m-d');
            if ($ref === '') {
                return $this->json(['status' => 400, 'message' => 'Enter the hotel\'s confirmation reference (number, or the name of the person who confirmed).']);
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $on) || strtotime($on) === false || $on > date('Y-m-d')) {
                return $this->json(['status' => 400, 'message' => 'The confirmation date must be a real date, today or earlier.']);
            }
            $file = $this->request->getFile('evidence');
            $hasFile = $file && $file->getError() !== UPLOAD_ERR_NO_FILE;
            if ($hasFile && !$file->isValid()) {
                return $this->json(['status' => 400, 'message' => 'The evidence file did not arrive: ' . $file->getErrorString()]);
            }
            // tf-resdesk-r2 (13 Nov 2026): the hotel's reply is recorded with its reference — its words, or the file it sent
            $reply = trim(str_replace("\r", '', (string) ($this->request->getPost('reply') ?? '')));
            if ($reply === '' && !$hasFile) {
                return $this->json(['status' => 400, 'message' => 'Record the hotel\'s reply: paste what it wrote, or attach its e-mail / PDF.']);
            }
            $was = $this->before('hotel_job', $id);
            if ($reply !== '') {
                $svc->hotelReply($id, mb_substr($reply, 0, 4000), $this->actorName());
            }
            $card = $svc->hotelConfirm($id, $ref, $with, $note);
            $db->query('UPDATE tf_hotel_job SET hotel_confirmed_on = ? WHERE id = ?', [$on, $id]);
            $docMsg = '';
            if ($hasFile) {
                try {
                    $doc = (new \App\Libraries\TourFlow\HotelBookingDocs($db, $this->actorName()))->addEvidence($id, (int) $card['tour_id'], $file->getTempName(), $file->getClientName(), $file->getClientMimeType(), 'Hotel confirmation ' . $ref);
                    $svc->event((int) $card['intake_id'], (int) $card['tour_id'], 'hotel', $id, 'evidence', 'confirmed', 'confirmed', 'Confirmation evidence kept: ' . $doc['original_name']);
                } catch (\Throwable $e) {
                    $docMsg = ' The evidence file was NOT kept: ' . $e->getMessage() . ' Add it again from the stay.';
                }
            }
            $this->after('hotel_job', $id, $was, 'confirm_booking', $card);
            $pay = $this->raisePayment($id);
            return $this->json(['status' => 200, 'message' => 'Hotel confirmed (ref ' . $ref . ', ' . date('j M Y', strtotime($on)) . ').' . $docMsg . ' ' . $pay['message'], 'data' => ['pay' => $pay]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/hotel_bookings/card/(:num)/raise_payment — a confirmed stay whose payment request could not be raised, tried again
    public function raisePaymentAgain($jobId)
    {
        try {
            $card = $this->svc()->hotelJob((int) $jobId);
            if (!in_array((string) $card['status'], ['confirmed', 'voucher_issued'], true)) {
                return $this->json(['status' => 400, 'message' => 'Only a stay the hotel has confirmed goes to Payment Approval.']);
            }
            $r = $this->raisePayment((int) $jobId);
            return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message'], 'data' => ['pay' => $r]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/hotel_bookings/card/(:num)/evidence  multipart: evidence (file), note — more confirmation evidence on a confirmed stay
    public function addEvidence($jobId)
    {
        try {
            $db = $this->db();
            \App\Libraries\TourFlow\HotelBookingDocs::ensure($db);
            $card = $this->svc()->hotelJob((int) $jobId);
            $file = $this->request->getFile('evidence');
            if (!$file || !$file->isValid()) {
                return $this->json(['status' => 400, 'message' => 'Choose the file to keep.']);
            }
            $doc = (new \App\Libraries\TourFlow\HotelBookingDocs($db, $this->actorName()))->addEvidence((int) $jobId, (int) $card['tour_id'], $file->getTempName(), $file->getClientName(), $file->getClientMimeType(), trim((string) ($this->request->getPost('note') ?? '')) ?: null);
            $this->svc()->event((int) $card['intake_id'], (int) $card['tour_id'], 'hotel', (int) $jobId, 'evidence', (string) $card['status'], (string) $card['status'], 'Confirmation evidence kept: ' . $doc['original_name']);
            return $this->json(['status' => 200, 'message' => 'Kept: ' . $doc['original_name'] . '.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET tour/hotel_bookings/doc/(:num) — a kept document (booking voucher version, confirmation evidence), exactly as kept
    public function hotelDoc($docId)
    {
        $docs = new \App\Libraries\TourFlow\HotelBookingDocs($this->db(), $this->actorName());
        $d = $docs->get((int) $docId);
        $p = $d ? $docs->path($d) : null;
        if (!$p) {
            return $this->response->setStatusCode(404)->setBody('Document not found.');
        }
        $inline = in_array(strtolower(pathinfo((string) $d['original_name'], PATHINFO_EXTENSION)), ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'txt'], true) && !$this->request->getGet('download');
        $name = str_replace(['"', "\r", "\n"], '', (string) $d['original_name']);
        return $this->response->setHeader('Content-Type', $d['mime'] ?: 'application/octet-stream')->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Content-Disposition', ($inline ? 'inline' : 'attachment') . '; filename="' . $name . '"')->setBody(file_get_contents($p));
    }

    // GET tour/hotel_bookings/message/(:num) — the e-mail as it went (or failed): who to, when, subject, attachment, body
    public function hotelMessage($msgId)
    {
        $m = $this->db()->query('SELECT m.*, j.hotel_name, t.tour_no FROM tf_hotel_message m JOIN tf_hotel_job j ON j.id = m.job_id JOIN tf_tour t ON t.id = m.tour_id WHERE m.id = ?', [(int) $msgId])->getRowArray();
        if (!$m) {
            return $this->response->setStatusCode(404)->setBody('Message not found.');
        }
        $doc = null;
        if (!empty($m['doc_id'])) {
            $doc = (new \App\Libraries\TourFlow\HotelBookingDocs($this->db(), $this->actorName()))->get((int) $m['doc_id']);
        }
        // tf-resdesk-r2: the Rooming List that went with the same message
        $more = $this->db()->query("SELECT id, original_name, version FROM tf_hotel_doc WHERE message_id = ? AND kind = 'rooming_list'", [(int) $msgId])->getResultArray();
        return view('pages/tour/hotel_sent_email', ['m' => $m, 'doc' => $doc, 'more' => $more]);
    }

    // GET tour/hotel_bookings/test_mailbox — the local test mailbox (only while it is switched on, on this computer)
    public function testMailbox()
    {
        if (!\App\Libraries\TourFlow\TestMailbox::active()) {
            return $this->response->setStatusCode(404)->setBody('The test mailbox is not switched on here.');
        }
        return view('pages/tour/test_mailbox', ['messages' => \App\Libraries\TourFlow\TestMailbox::messages(), 'fail' => \App\Libraries\TourFlow\TestMailbox::failList()]);
    }

    // GET tour/hotel_bookings/test_mailbox/(:segment)/(:segment) — one caught message's body or attachment
    public function testMailboxFile($id, $name)
    {
        if (!\App\Libraries\TourFlow\TestMailbox::active()) {
            return $this->response->setStatusCode(404);
        }
        $p = \App\Libraries\TourFlow\TestMailbox::file((string) $id, (string) $name);
        if (!$p) {
            return $this->response->setStatusCode(404)->setBody('Not found.');
        }
        $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
        $type = ['html' => 'text/html; charset=utf-8', 'pdf' => 'application/pdf', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'][$ext] ?? 'application/octet-stream';
        return $this->response->setHeader('Content-Type', $type)->setHeader('X-Content-Type-Options', 'nosniff')->setHeader('Content-Disposition', 'inline; filename="' . basename($p) . '"')->setBody(file_get_contents($p));
    }

    // ---- activities & tickets: the same desk, the same privilege (schema 20) ----

    // GET tour/hotel_bookings/activities?status=&q=&handler=&from=&to=
    public function activityData()
    {
        try {
            $svc = $this->svc();
            $items = $svc->serviceQueue($this->filters());
            // the tour's strip once per tour, so the desk sees where the whole tour stands
            $strips = [];
            foreach ($items as $j) {
                $tid = (int) $j['tour_id'];
                if (!isset($strips[$tid])) {
                    $strips[$tid] = $svc->stripFor($tid);
                }
            }
            return $this->json(['status' => 200, 'data' => [
                'items'   => $items,
                'counts'  => $svc->serviceCounts(),
                'reasons' => $svc->reasons('service_problem'),
                'strips'  => $strips,
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 500);
        }
    }

    // GET tour/hotel_bookings/activity/(:num)
    public function activityCard($jobId)
    {
        try {
            $card = $this->svc()->serviceJob((int) $jobId);
            $card['strip'] = $this->svc()->stripFor((int) $card['tour_id']);
            // schema 40: the restaurant booking voucher's send window — the ready-made emails filled in for this meal
            if (!empty($card['meal_booking'])) {
                $card['meal_mail'] = $this->mealMail((int) $jobId, $card);
            }
            return $this->json(['status' => 200, 'data' => $card]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/hotel_bookings/activity/(:num)/(:segment)  request | confirm | not_needed | problem | back | note | tickets | tickets_used | pay_at_gate
    public function activityAction($jobId, $action)
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();
            $id  = (int) $jobId;
            $was = $this->before('service_job', $id);
            switch ((string) $action) {
                case 'request':
                    $card = $svc->serviceRequest($id, $b['note'] ?? null);
                    $msg  = 'Marked as requested. Ask the supplier as usual; record the reference here when it answers.';
                    break;
                // tf-tickets-r1 (schema 37): tickets from the stock by the party size, the tickets used, the entrance paid at the gate
                case 'tickets':
                    $card = $svc->serviceTickets($id, isset($b['attraction_id']) && $b['attraction_id'] !== '' ? (int) $b['attraction_id'] : null, isset($b['count']) && $b['count'] !== '' ? (int) $b['count'] : null, $b['note'] ?? null);
                    $msg  = 'Tickets allocated from stock: ' . $card['ticket_text'] . '. The numbers are on the card, the Board, the record and the driver\'s itinerary copy.';
                    break;
                case 'tickets_used':
                    $card = $svc->serviceTicketsUsed($id);
                    $msg  = 'Tickets marked used.';
                    break;
                case 'pay_at_gate':
                    $card = $svc->servicePayAtGate($id, $b['note'] ?? null);
                    $msg  = 'Marked: paid at the gate on the day.';
                    break;
                case 'confirm':
                    $card = $svc->serviceConfirm($id, (string) ($b['ref'] ?? ''), $b['with'] ?? null, $b['note'] ?? null);
                    $msg  = 'Confirmation recorded.';
                    break;
                case 'not_needed':
                    $card = $svc->serviceNotNeeded($id, $b['note'] ?? null);
                    $msg  = 'Marked: nothing to arrange.';
                    break;
                case 'problem':
                    $card = $svc->serviceProblem($id, (string) ($b['code'] ?? ''), $b['note'] ?? null);
                    $msg  = 'Marked as a problem. It shows on the Tour Board and on the inquiry for the Quotation Desk.';
                    break;
                case 'back':
                    $card = $svc->serviceBack($id, $b['note'] ?? null);
                    $msg  = 'Back to "to book".';
                    break;
                case 'note':
                    $card = $svc->serviceNote($id, $b['note'] ?? null);
                    $msg  = 'Note saved.';
                    break;
                // tf-supmail-r2: the time this line is booked for — a supplier asked to hold something needs the hour
                case 'time':
                    $card = $svc->setServiceTime($id, $b['time'] ?? null, $b['note'] ?? null);
                    $msg  = ($card['on_time'] ?? null) !== null
                        ? 'Booked for ' . substr((string) $card['on_time'], 0, 5) . '. The request waiting on this line has been written again with it.'
                        : 'The time is off the line. A request whose wording asks for a time is held until one is entered.';
                    break;
                // schema 40: booking the restaurant of a Meal Master meal — its booking voucher (the amount in LKR from the Meal Master),
                // emailed to the restaurant from the signed-in person's mailbox, or recorded as sent another way
                case 'meal_voucher': {
                    $mm   = $this->meals();
                    $v    = $mm->issueVoucher($svc, $id, $b['note'] ?? null);
                    $msg  = 'Voucher ' . $v['voucher_no'] . ' issued: LKR ' . number_format((float) $v['amount_lkr'], 2) . '.';
                    if (!empty($b['send'])) {
                        $r = $this->sendMealVoucher($mm, $svc, (int) $v['id'], $b);
                        $msg .= $r['ok'] ? ' Emailed to ' . $r['voucher']['to_email'] . '; the copy is kept on the voucher.' . $r['saved'] : ' Not emailed: ' . $r['error'];
                    }
                    $card = $svc->serviceJob($id);
                    break;
                }
                case 'meal_voucher_send': {
                    $mm = $this->meals();
                    $vid = (int) ($b['voucher_id'] ?? 0);
                    if ((int) ($mm->voucher($vid)['job_id'] ?? 0) !== $id) {
                        return $this->json(['status' => 400, 'message' => 'That voucher is not for this line.'], 400);
                    }
                    $r = $this->sendMealVoucher($mm, $svc, $vid, $b);
                    if (!$r['ok']) {
                        $card = $svc->serviceJob($id);
                        $card['strip'] = $svc->stripFor((int) $r['voucher']['tour_id']);
                        $card['meal_mail'] = $this->mealMail($id, $card);
                        return $this->json(['status' => 400, 'message' => $r['error'], 'data' => $card]);
                    }
                    $card = $svc->serviceJob($id);
                    $msg  = 'Voucher ' . $r['voucher']['voucher_no'] . ' emailed to ' . $r['voucher']['to_email'] . '. The copy is kept on the voucher.' . $r['saved'];
                    break;
                }
                case 'meal_voucher_sent': {
                    $mm = $this->meals();
                    $vid = (int) ($b['voucher_id'] ?? 0);
                    if ((int) ($mm->voucher($vid)['job_id'] ?? 0) !== $id) {
                        return $this->json(['status' => 400, 'message' => 'That voucher is not for this line.'], 400);
                    }
                    $v    = $mm->markSentOther($svc, $vid, $b['note'] ?? null);
                    $card = $svc->serviceJob($id);
                    $msg  = 'Voucher ' . $v['voucher_no'] . ' recorded as sent another way.';
                    break;
                }
                case 'meal_voucher_cancel':
                    $mm = $this->meals();
                    $vid = (int) ($b['voucher_id'] ?? 0);
                    if ((int) ($mm->voucher($vid)['job_id'] ?? 0) !== $id) {
                        return $this->json(['status' => 400, 'message' => 'That voucher is not for this line.'], 400);
                    }
                    $v    = $mm->cancelVoucher($svc, $vid, $b['why'] ?? ($b['note'] ?? null));
                    $card = $svc->serviceJob($id);
                    $msg  = 'Voucher ' . $v['voucher_no'] . ' cancelled; it stays on the card marked so.' . (in_array((string) $v['mail_status'], ['sent', 'other'], true) ? ' It had gone to the restaurant — tell them.' : '') . ' Issue a fresh one when the figures are right.';
                    break;
                default:
                    return $this->json(['status' => 404, 'message' => 'Unknown action.'], 404);
            }
            $this->after('service_job', $id, $was, (string) $action, $card);
            $card['strip'] = $svc->stripFor((int) $card['tour_id']);
            if (!empty($card['meal_booking'])) {
                $card['meal_mail'] = $this->mealMail($id, $card);
            }
            return $this->json(['status' => 200, 'message' => $msg, 'data' => $card]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    private function meals(): MealMaster
    {
        return new MealMaster($this->db(), $this->user(), $this->userId(), $this->actorName());
    }

    /**
     * Email a restaurant booking voucher from the desk: to, cc, subject and message as the window has them (blank: the ready-made
     * email's); on purpose, and only for a Meal Master holder, the address typed goes on the Meal Master as the restaurant's.
     */
    private function sendMealVoucher(MealMaster $mm, TourService $svc, int $voucherId, array $b): array
    {
        $who = Staff::contact($this->db(), $this->userId(), $this->user());
        $user = $this->actorName();
        $mail = ['cc' => $b['cc'] ?? null, 'subject' => $b['subject'] ?? null, 'message' => $b['message'] ?? null];
        if (trim((string) ($b['to'] ?? '')) !== '') {
            $mail['to'] = $b['to'];
        }
        $r = $mm->sendVoucher($svc, $voucherId, $mail, $who, static fn (array $v, string $mode, ?string $message, array $sender) => MealMaster::voucherHtml($v, $mode, $message, $sender, $user));
        $r['saved'] = '';
        if ($r['ok'] && !empty($b['save_master']) && $this->holds(MealMaster::URL)) {
            $said = $mm->saveContact((int) $r['voucher']['meal_id'], ['email' => $r['voucher']['to_email']]);
            $r['saved'] = $said !== null ? ' ' . $said : '';
        }
        return $r;
    }

    /** The send window of a meal's booking voucher: the live voucher's ready-made emails, or (none issued yet) the same with the number to come. */
    private function mealMail(int $jobId, array $card): ?array
    {
        try {
            $mm  = $this->meals();
            $who = Staff::contact($this->db(), $this->userId(), $this->user());
            $live = $card['meal_booking']['live_id'] ?? null;
            if ($live) {
                $v = $mm->voucher((int) $live);
            } else {
                $b = $mm->booking($jobId);
                if ($b['meal'] === null) {
                    return null;
                }
                // the number is given on issue: the window keeps the word, and it is filled in when the email goes
                $v = ['tour_id' => (int) $card['tour_id'], 'restaurant' => $b['meal']['restaurant'], 'meal_type' => $b['meal']['meal_type'], 'on_date' => $card['on_date'] ?? null,
                      'meal_time' => $b['line']['meal_time'] ?? null, 'adults' => $b['adults'], 'children' => $b['children'], 'voucher_no' => '{voucher_no}', 'amount_lkr' => $b['lkr']['total'] ?? null];
            }
            $t = $mm->mailTemplates($v, $who);
            return ['templates' => $t['templates'], 'start' => $t['start'], 'can_send' => !empty($who['can_send']), 'from' => $who['email'] ?? null, 'can_save_master' => $this->holds(MealMaster::URL)];
        } catch (\Throwable $e) {
            return null;
        }
    }

    // GET tour/hotel_bookings/meal_voucher/(:num) — schema 40: a restaurant payment voucher as a page to print
    public function mealVoucherPage($voucherId)
    {
        try {
            $v = $this->meals()->voucher((int) $voucherId);
            $who = Staff::contact($this->db(), $this->userId(), $this->user());
            return $this->response->setStatusCode(200)->setBody(MealMaster::voucherHtml($v, 'page', null, $who, $this->actorName()));
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setBody(esc($e->getMessage()));
        }
    }

    // POST tour/hotel_bookings/tour/(:num)/activity {title, kind, day_no, on_date, pax, note} — a line the desk adds itself
    public function activityAdd($tourId)
    {
        try {
            $card = $this->svc()->addServiceJob((int) $tourId, $this->body());
            return $this->json(['status' => 200, 'message' => 'Added to the desk. It is not carried back into the quotation.', 'data' => $card]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/hotel_bookings/tour/(:num)/complete {on:1|0, why} — HOTEL WORK COMPLETE (every voucher issued,
    // every activity line done): the tour leaves the red Hotel Bookings count. Also answers .../confirmed.
    public function reservationsConfirmed($tourId)
    {
        try {
            $b = $this->body();
            $svc = $this->svc();
            if (array_key_exists('on', $b) && !(int) $b['on']) {
                $t = $svc->unmarkReservationsConfirmed((int) $tourId, $b['why'] ?? null);
                $msg = 'The mark is taken off; the tour is back on the Hotel Bookings count.';
            } else {
                $t = $svc->markReservationsConfirmed((int) $tourId);
                $msg = 'Hotel work complete. The tour leaves the Hotel Bookings count and stays under Completed; the inquiry, the Transport Desk and the Board all show the mark.';
            }
            return $this->json(['status' => 200, 'message' => $msg, 'data' => ['tour_id' => (int) $t['id'], 'tour_no' => $t['tour_no'], 'strip' => $t['strip']]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET tour/hotel_bookings/card/(:num)/document/(:segment)?format=page|email&rate=1&remarks= — the booking
    // request or the voucher, as a page to print, or as the body that goes by email
    public function hotelDocument($jobId, $kind)
    {
        try {
            $g = fn (string $k) => (string) ($this->request->getGet($k) ?? '');
            $d = $this->documentData((int) $jobId, (string) $kind, $g('rate') === '1', $g('remarks'));
            $html = view('pages/tour/hotel_document', $d + ['email' => $g('format') === 'email', 'message' => $g('message') !== '' ? mb_substr($g('message'), 0, 4000) : null]);
            return $this->response->setStatusCode(200)->setBody($html);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setBody(esc($e->getMessage()));
        }
    }


    // ---- tf-transmail-r1: the transport booking requests (supplier, driver, guide) -------------------------

    // GET tour/transport_ops/tour/(:num)/requests — who has to be told, what is prepared, and what is missing
    public function transportRequests($tourId)
    {
        try {
            $tm = new \App\Libraries\TourFlow\TransportMailer($this->db(), $this->user(), $this->actorName());
            if (!\App\Libraries\TourFlow\TransportMailer::ready($this->db())) {
                return $this->json(['status' => 200, 'data' => ['installed' => false, 'parties' => [], 'messages' => []]]);
            }
            $tm->prepare((int) $tourId, false);   // reading the card prepares, never sends: sending is the workflow's own step or a press
            $parties = $tm->parties((int) $tourId);
            return $this->json(['status' => 200, 'data' => [
                'installed' => true,
                'parties'   => $parties,
                'missing'   => array_values(array_filter(array_map(static fn ($p) => $p['missing'], $parties))),
                'messages'  => $tm->messages((int) $tourId),
                'can_send'  => \App\Libraries\TourFlow\TransportMailer::canSend(\App\Libraries\TourFlow\CommsMail::senderFor($this->db(), $this->userId(), $this->user())),
                'sender_note' => \App\Libraries\TourFlow\CommsMail::senderFor($this->db(), $this->userId(), $this->user())['note'] ?? null,
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/transport_ops/request/(:num)/send {to?, cc?} — one prepared request, by e-mail, from this mailbox
    public function transportRequestSend($messageId)
    {
        try {
            $b = $this->body();
            $sender = \App\Libraries\TourFlow\CommsMail::senderFor($this->db(), $this->userId(), $this->user());
            $sender['user_id'] = $this->userId();
            $tm = new \App\Libraries\TourFlow\TransportMailer($this->db(), $this->user(), $this->actorName());
            $r  = $tm->send((int) $messageId, $sender, $b['to'] ?? null, $b['cc'] ?? null);
            return $this->json(['status' => !empty($r['ok']) ? 200 : 400] + $r);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/transport_ops/request/(:num)/record {channel, note, kind} — sent by hand, or an answer came back
    public function transportRequestRecord($messageId)
    {
        try {
            $b = $this->body();
            $tm = new \App\Libraries\TourFlow\TransportMailer($this->db(), $this->user(), $this->actorName());
            $r  = $tm->record((int) $messageId, (string) ($b['channel'] ?? 'whatsapp'), trim((string) ($b['note'] ?? '')), ($b['kind'] ?? 'request') === 'reply' ? 'reply' : 'request');
            return $this->json(['status' => 200] + $r);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /**
     * tf-confirmed-r3: the issued voucher as HTML, for Communications to attach to the confirmed itinerary.
     * The same letter the desk prints — read only, nothing is recorded by asking for it.
     */
    public function voucherHtml(int $jobId): string
    {
        $d = $this->documentData($jobId, 'voucher', false, null);
        return (string) view('pages/tour/hotel_document', $d + ['email' => false, 'message' => null]);
    }

    /** Everything the letter needs, from the record: the document, the sender, the logo, the rate if asked for. */
    private function documentData(int $jobId, string $kind, bool $withRate, ?string $remarks): array
    {
        $svc  = $this->svc();
        $who  = Staff::contact($this->db(), $this->userId(), $this->user());
        $doc  = $svc->hotelDocument($jobId, $kind, $who);
        // tf-letter-r1 (10 Oct 2026): rates are confidential — a letter to a hotel never carries a figure; it names
        // the basis ("As per agreed contract rates", or the tactical / early bird offer applied to the stay)
        $rate  = null;
        $basis = \App\Libraries\TourFlow\HotelLetter::rateBasis($this->db(), $svc->hotelJob($jobId));
        $logo  = \App\Libraries\TourFlow\HotelLetter::logoUrl();
        return ['doc' => $doc, 'rate' => $rate, 'rate_basis' => $basis, 'remarks' => $remarks !== null && trim($remarks) !== '' ? trim($remarks) : null, 'logo' => $logo, 'sender' => $who];
    }

    // GET tour/{desk}/tour/(:num)/details — the tour as the desk needs it: the programme day by
    // day, hotels, passengers, flights, requests, vehicle and crew. Read from the one record.
    public function details($tourId)
    {
        try {
            $d = $this->svc()->details((int) $tourId);
            return $d ? $this->json(['status' => 200, 'data' => $d]) : $this->json(['status' => 404, 'message' => 'Tour not found.'], 404);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET tour/{desk}/tour/(:num)/programme — the same, as a page to print: the driver's sheet, the rooming list
    public function programme($tourId)
    {
        try {
            $d = $this->svc()->details((int) $tourId);
            if (!$d) {
                return $this->response->setStatusCode(404)->setBody('Tour not found.');
            }
            return view('pages/tour/programme', ['details' => $d, 'user' => $this->actorName()]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setBody(esc($e->getMessage()));
        }
    }

    // GET tour/hotel_bookings/strip/(:num) — where the whole tour stands, for any desk card
    public function strip($tourId)
    {
        try {
            $s = $this->svc()->stripFor((int) $tourId);
            return $s ? $this->json(['status' => 200, 'data' => $s]) : $this->json(['status' => 404, 'message' => 'Tour not found.'], 404);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET tour/hotel_bookings/card/(:num)
    public function hotelCard($jobId)
    {
        try {
            $card = $this->svc()->hotelJob((int) $jobId);
            if ($this->holds('master_files/hotel_rate_v2')) {
                $card['rate'] = $this->contractRate($card);
            }
            $card['strip']  = $this->svc()->stripFor((int) $card['tour_id']);
            $card['letter'] = $this->letterFor((int) $jobId);
            return $this->json(['status' => 200, 'data' => $card]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /** What the letter box on a stay starts with: the hotel's address from its supplier record, the subject, whether this account can send. */
    private function letterFor(int $jobId): array
    {
        try {
            $who = Staff::contact($this->db(), $this->userId(), $this->user());
            $doc = $this->svc()->hotelDocument($jobId, 'request', $who);
            $st  = (string) $doc['stay']['status'];
            $subject = $st === 'voucher_issued' ? $this->svc()->hotelDocument($jobId, 'voucher', $who)['subject'] : $doc['subject'];
            // schema 34: the ready-made emails this window offers (the kind's own, then the general ones), filled in for this stay
            $kindCat = $st === 'voucher_issued' ? 'hotel_voucher' : 'hotel_request';
            $tpls = [];
            $start = null;
            try {
                $fmtD = static fn ($d) => $d ? date('j M Y', strtotime((string) $d)) : '';
                $stay = $doc['stay']; $tour = $doc['tour'];
                $vars = [
                    'agent' => (string) ($tour['agent_name'] ?? ''), 'agent_name' => (string) ($tour['agent_name'] ?? ''), 'inquiry_no' => (string) ($tour['inquiry_no'] ?? ''), 'tour_no' => (string) ($tour['tour_no'] ?? ''), 'tour_name' => (string) ($tour['tour_name'] ?? ''),
                    'dates' => !empty($tour['arrival_date']) && !empty($tour['departure_date']) ? $fmtD($tour['arrival_date']) . ' to ' . $fmtD($tour['departure_date']) : '', 'arrival' => $fmtD($tour['arrival_date'] ?? null), 'departure' => $fmtD($tour['departure_date'] ?? null),
                    'nights' => (string) (int) ($stay['nights'] ?? 0), 'pax' => (string) ($stay['pax'] ?? $tour['pax_total'] ?? ''), 'hotel' => (string) ($doc['hotel']['name'] ?? ''), 'check_in' => $fmtD($stay['check_in'] ?? null), 'check_out' => $fmtD($stay['check_out'] ?? null),
                    'rooms' => trim(($stay['rooms'] !== null ? (int) $stay['rooms'] . ' room' . ((int) $stay['rooms'] === 1 ? '' : 's') : '') . (!empty($stay['rooms_note']) ? ' — ' . $stay['rooms_note'] : '')), 'meal_plan' => (string) ($stay['meal_plan'] ?? ''),
                    'me' => (string) ($who['name'] ?? ''), 'company' => (string) ($doc['company']['name'] ?? 'Avenir Holidays'), 'today' => date('j M Y'),
                ];
                $tpls  = EmailTemplates::filled((new EmailTemplates($this->db()))->forWindow($kindCat), $vars);
                $start = EmailTemplates::starting($tpls, $kindCat);
            } catch (\Throwable $e) {
                $tpls = [];
            }
            // tf-walkfix-r1 (point 5): the address goes in once — the one the last letter on this stay went to (an
            // address typed for this booking stands for the booking's next letters), else the Hotel Master's
            // Reservations e-mail, else the supplier record's. The master is read here, never written.
            $last   = $this->lastStayAddress($jobId);
            $master = (string) ($doc['hotel']['reservation_email'] ?? '');
            $to     = ($last['to'] ?? null) ?: ($master !== '' ? $master : ($doc['hotel']['email'] ?: null));
            $src    = ($last['to'] ?? null) ? 'stay' : ($master !== '' ? 'master' : ($doc['hotel']['email'] ? 'supplier' : null));
            return [
                'to' => $to, 'cc' => $src === 'stay' ? (string) ($last['cc'] ?? '') : '', 'subject' => $start ? $start['subject'] : $subject, 'message' => $start ? $start['message'] : '',
                'template_id' => $start ? (int) $start['id'] : 0, 'templates' => $tpls,
                'hotel_email' => $doc['hotel']['email'], 'hotel_phone' => $doc['hotel']['phone'] ?: $doc['hotel']['mobile'],
                'to_source' => $src, 'master_email' => $master !== '' ? $master : null, 'hotel_id' => !empty($doc['stay']['hotel_id']) ? (int) $doc['stay']['hotel_id'] : null,
                // a holder of Hotel Rates may, on purpose, put a typed address on the Hotel Master for every future booking
                'can_master' => $this->holds('master_files/hotel_rate_v2') && !empty($doc['stay']['hotel_id']),
                'can_send' => (bool) $who['can_send'], 'sender_email' => $who['email'],
            ];
        } catch (\Throwable $e) {
            return ['to' => null, 'cc' => '', 'subject' => '', 'message' => '', 'template_id' => 0, 'templates' => [], 'hotel_email' => null, 'hotel_phone' => null, 'can_send' => false, 'sender_email' => null];
        }
    }

    /**
     * The email address the last letter on this stay went to, and its CC — read from the copies kept, nothing new.
     * Null when no letter on the stay has gone by email yet.
     *
     * @return array{to:?string, cc:?string, source:?string}
     */
    private function lastStayAddress(int $jobId): array
    {
        try {
            $r = $this->db()->query("SELECT to_addr, cc_addr FROM tf_hotel_message WHERE job_id = ? AND channel = 'email' AND to_addr IS NOT NULL AND to_addr <> '' ORDER BY id DESC LIMIT 1", [$jobId])->getRowArray();
            return $r ? ['to' => $r['to_addr'], 'cc' => $r['cc_addr'], 'source' => 'stay'] : ['to' => null, 'cc' => null, 'source' => null];
        } catch (\Throwable $e) {
            return ['to' => null, 'cc' => null, 'source' => null];
        }
    }

    /** The published contract rate the quotation used — shown, never changed here. Published plans only. */
    private function contractRate(array $card): ?array
    {
        try {
            if (empty($card['hotel_id']) || empty($card['check_in']) || empty($card['check_out'])) {
                return null;
            }
            $db   = $this->db();
            $it   = $db->query('SELECT market_code FROM tf_intake WHERE id = ?', [(int) $card['intake_id']])->getRowArray();
            $take = new IntakeService($db, $this->user());
            $svc  = new HotelRateService($db, $this->user());
            $last = date('Y-m-d', strtotime((string) $card['check_out'] . ' -1 day'));
            $opts = (!empty($it['market_code']) ? ['market' => $it['market_code']] : []) + ['plan_types' => $take->quotablePlanTypes()];
            $r    = $svc->ratesForRange((int) $card['hotel_id'], (string) $card['check_in'], $last, $opts);
            $plans = array_values(array_filter((array) ($r['plans'] ?? []), static fn ($p) => $take->quotable((string) ($p['plan_type'] ?? ''))));
            if (!$plans) {
                return ['available' => false, 'note' => 'No published contract rate covers these nights for this market.'];
            }
            return [
                'available' => (int) ($r['available_nights'] ?? 0) === (int) ($r['nights'] ?? 0),
                'nights'    => $r['nights'] ?? null,
                'currency'  => $r['currency'] ?? null,
                'plans'     => array_map(static fn ($p) => $p['name'], $plans),
                'totals'    => array_values(array_filter((array) ($r['totals'] ?? []), static fn ($t) => !empty($t['complete']))),
                'missing'   => $r['missing_nights'] ?? [],
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    // POST tour/hotel_bookings/card/(:num)/(:segment)  request | confirm | voucher | problem | back | note
    public function hotelAction($jobId, $action)
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();
            $id  = (int) $jobId;
            $was = (string) $action === 'note' ? null : $this->before('hotel_job', $id);
            switch ((string) $action) {
                case 'request':
                    $card = $svc->hotelRequest($id, $b['note'] ?? null);
                    $msg  = 'Marked as requested. Send the hotel your request as usual; record its reference here when it answers.';
                    break;
                case 'confirm':
                    $card = $svc->hotelConfirm($id, (string) ($b['ref'] ?? ''), $b['with'] ?? null, $b['note'] ?? null);
                    $msg  = 'Confirmation recorded.';
                    break;
                case 'voucher':
                    $card = $svc->hotelVoucher($id, $b['note'] ?? null);
                    $msg  = 'Voucher ' . $card['voucher_no'] . ' issued.';
                    break;
                case 'problem':
                    $card = $svc->hotelProblem($id, (string) ($b['code'] ?? ''), $b['note'] ?? null);
                    $msg  = 'Marked as a problem. It shows on the Tour Board and on the inquiry for the Quotation Desk.';
                    break;
                case 'back':
                    $card = $svc->hotelBack($id, $b['note'] ?? null);
                    $msg  = 'Back to "to book".';
                    break;
                case 'note':
                    $card = $svc->hotelNote($id, $b['note'] ?? null, $b['crew_rooms'] ?? null);
                    $msg  = 'Note saved.';
                    break;
                case 'room_type':
                    // tf-walkfix-r1: the room type as this booking describes it — this stay only; the master is untouched
                    $card = $svc->hotelRoomType($id, $b['text'] ?? null);
                    $msg  = 'Room type on this booking: ' . $card['room_type_text'] . '. The Hotel Master is unchanged.';
                    break;
                case 'send': {
                    // {kind: request|voucher, to, cc, subject, remarks, rate} — the letter goes by email from the
                    // signed-in user's mailbox; the copy sent is kept on the stay, and so is a failure
                    $kind = ($b['kind'] ?? 'request') === 'voucher' ? 'voucher' : 'request';
                    $d    = $this->documentData($id, $kind, !empty($b['rate']), $b['remarks'] ?? null);
                    $msg0 = trim(str_replace("\r", '', (string) ($b['message'] ?? '')));
                    $body = $kind === 'request'
                        // tf-resdesk-r2: the Hotel Booking Request e-mail (reservation control number, no price; the PDF voucher attached)
                        ? view('pages/tour/hotel_booking_email', ['doc' => $d['doc'], 'control' => \App\Libraries\TourFlow\HotelBookingDocs::voucherNo($d['doc']), 'message' => $msg0 !== '' ? mb_substr($msg0, 0, 4000) : null])
                        : view('pages/tour/hotel_document', $d + ['email' => true, 'message' => $msg0 !== '' ? mb_substr($msg0, 0, 4000) : null]);
                    $to   = trim((string) ($b['to'] ?? ''));
                    $cc   = array_values(array_filter(array_map('trim', preg_split('/[;,\s]+/', (string) ($b['cc'] ?? '')) ?: [])));
                    $subject = trim((string) ($b['subject'] ?? '')) ?: $d['doc']['subject'];
                    if (!$d['sender']['can_send']) {
                        return $this->json(['status' => 400, 'message' => 'Your account has no mailbox set up to send from (email, password and host on your user profile). Print the letter, or record that you sent it another way.']);
                    }
                    // tf-resdesk-r2: a booking request carries the Hotel Booking Voucher (PDF), kept with the copy sent
                    $bv = null;
                    $att = [];
                    if ($kind === 'request') {
                        \App\Libraries\TourFlow\HotelBookingDocs::ensure($this->db());
                        $both = (new \App\Libraries\TourFlow\HotelBookingDocs($this->db(), $this->actorName()))->bookingDocs($svc, $id, $d['sender']);
                        $bv = $both['request'];
                        $rlDoc = $both['rooming'];
                        $att = [['path' => $bv['path'], 'name' => $bv['original_name']], ['path' => $rlDoc['path'], 'name' => $rlDoc['original_name']]];
                    }
                    $r = HotelMail::send($to, $subject, $body, $cc, $att);
                    $rlDoc = $rlDoc ?? null;
                    $linkBv = function () use ($bv, $rlDoc, $id) {
                        if ($bv) {
                            $m = $this->db()->query('SELECT MAX(id) AS id FROM tf_hotel_message WHERE job_id = ?', [$id])->getRowArray();
                            if (!empty($m['id'])) {
                                $hd = new \App\Libraries\TourFlow\HotelBookingDocs($this->db(), $this->actorName());
                                $hd->linkMessage((int) $bv['id'], (int) $m['id']);
                                if ($rlDoc) {
                                    $hd->linkMessage((int) $rlDoc['id'], (int) $m['id']);
                                }
                            }
                        }
                    };
                    if (!$r['ok']) {
                        try {
                            $svc->recordHotelMessage($id, $kind, 'email', $to, implode(', ', $cc), $subject, $body, 'failed', $r['error'], $this->actorName());
                            $linkBv();
                        } catch (\Throwable $e) {
                            // the failure is reported either way
                        }
                        return $this->json(['status' => 400, 'message' => $r['error']]);
                    }
                    $card = $kind === 'voucher'
                        ? $svc->hotelVoucherSent($id, 'email', $to, implode(', ', $cc), $subject, $body, $this->actorName())
                        : $svc->hotelRequestSent($id, 'email', $to, implode(', ', $cc), $subject, $body, $this->actorName());
                    $linkBv();
                    $msg  = ($kind === 'voucher' ? 'Voucher' : 'Booking request') . ' sent to ' . $to . '. The copy is kept on the stay.';
                    // tf-walkfix-r1 (point 5): on purpose, and only for a holder of Hotel Rates, the address typed here can
                    // go on the Hotel Master as the hotel's Reservations e-mail — otherwise the master is never touched
                    if (!empty($b['save_master']) && $this->holds('master_files/hotel_rate_v2') && !empty($d['doc']['stay']['hotel_id']) && filter_var($to, FILTER_VALIDATE_EMAIL)) {
                        try {
                            (new HotelRateService($this->db(), $this->user()))->updateHotel((int) $d['doc']['stay']['hotel_id'], ['reservations_email' => $to]);
                            $msg .= ' Saved on the Hotel Master as the hotel\'s Reservations e-mail.';
                        } catch (\Throwable $e) {
                            $msg .= ' (The Hotel Master could not be updated: ' . $e->getMessage() . ')';
                        }
                    }
                    break;
                }
                case 'sent': {
                    // {kind, note} — sent by other means (the hotel's portal, WhatsApp, the phone): recorded, nothing mailed
                    $kind = ($b['kind'] ?? 'request') === 'voucher' ? 'voucher' : 'request';
                    $card = $kind === 'voucher'
                        ? $svc->hotelVoucherSent($id, 'other', null, null, null, $b['note'] ?? null, $this->actorName())
                        : $svc->hotelRequestSent($id, 'other', null, null, null, null, $this->actorName(), $b['note'] ?? null);
                    $msg  = ($kind === 'voucher' ? 'Voucher' : 'Request') . ' recorded as sent.';
                    break;
                }
                case 'reply':
                    $card = $svc->hotelReply($id, $b['text'] ?? null, $this->actorName());
                    $msg  = 'The hotel\'s reply is on the stay.';
                    break;
                default:
                    return $this->json(['status' => 404, 'message' => 'Unknown action.'], 404);
            }
            if ($this->holds('master_files/hotel_rate_v2')) {
                $card['rate'] = $this->contractRate($card);
            }
            $this->after('hotel_job', $id, $was, (string) $action, $card);
            $card['strip']  = $svc->stripFor((int) $card['tour_id']);
            $card['letter'] = $this->letterFor($id);
            return $this->json(['status' => 200, 'message' => $msg, 'data' => $card]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // =====================================================================
    //  Transport Desk
    // =====================================================================

    // GET tour/transport_ops
    public function transport()
    {
        // "Replace mid-tour" shows only to a group holding the Crew replacement privilege (schema 24)
        return $this->page('pages/tour/transport_ops', self::TRANSPORT, ['can_replace' => $this->holds(Desks::CREW_CHANGE_URL), 'crew_change_url' => Desks::CREW_CHANGE_URL,
            // tf-pilot-r2: engaging crew, setting the vehicle and the days worked need the Driver Allocation privilege (Operations)
            'can_allocate' => $this->canAllocate(), 'allocation_url' => Desks::ALLOCATION_URL,
            // the Driver advance privilege (schema 26): approving, adjusting and paying an advance, the settlement adjustments, Settled
            'can_advance' => $this->holds(Desks::DRIVER_ADVANCE_URL), 'driver_advance_url' => Desks::DRIVER_ADVANCE_URL,
            // tf-crew-master-r1: the bank on the payment lines, for the holders of the bank privilege
            'can_bank' => $this->holds(self::CREW_BANK_URL)]);
    }

    // GET tour/transport_ops/tours?which=pending|completed|cancelled|all&q=&handler=&from=&to= — one row per tour
    public function transportTours()
    {
        try {
            $svc = $this->svc();
            return $this->json(['status' => 200, 'data' => ['tours' => $svc->deskTours('transport', $this->tourFilters()), 'counts' => $svc->deskCounts('transport')]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 500);
        }
    }

    // GET tour/transport_ops/tour/(:num)/work — the tour opened: its vehicle cards
    public function transportWork($tourId)
    {
        try {
            $svc = $this->svc();
            $id  = (int) $tourId;
            $rows = $svc->deskTours('transport', ['which' => 'all']);
            $tour = current(array_filter($rows, static fn ($r) => $r['id'] === $id)) ?: null;
            if (!$tour) {
                return $this->json(['status' => 404, 'message' => 'Tour not found.'], 404);
            }
            return $this->json(['status' => 200, 'data' => [
                'tour'     => $tour,
                'vehicles' => $svc->transportQueue(['status' => 'all', 'tour_id' => $id]),
                'strip'    => $tour['strip'],
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 500);
        }
    }

    // POST tour/transport_ops/tour/(:num)/complete {on:1|0, why} — TRANSPORT WORK COMPLETE (every vehicle set,
    // every driver and guide engaged): the tour leaves the red Transport count
    public function transportComplete($tourId)
    {
        try {
            $b = $this->body();
            $svc = $this->svc();
            if (array_key_exists('on', $b) && !(int) $b['on']) {
                $t = $svc->unmarkTransportComplete((int) $tourId, $b['why'] ?? null);
                $msg = 'The mark is taken off; the tour is back on the Transport count.';
            } else {
                $t = $svc->markTransportComplete((int) $tourId);
                $msg = 'Transport work complete. The tour leaves the Transport count and stays under Completed; the inquiry, the Hotel Desk and the Board all show the mark.';
            }
            return $this->json(['status' => 200, 'message' => $msg, 'data' => ['tour_id' => (int) $t['id'], 'tour_no' => $t['tour_no'], 'strip' => $t['strip']]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET tour/transport_ops/data?status=&q=&handler=&from=&to=
    public function transportData()
    {
        try {
            $svc = $this->svc();
            $items = $svc->transportQueue($this->filters());
            // where the whole tour stands, once per tour: the Transport Desk sees the hotels and activities too
            $strips = [];
            foreach ($items as $j) {
                $tid = (int) $j['tour_id'];
                if (!isset($strips[$tid])) {
                    $strips[$tid] = $svc->stripFor($tid);
                }
            }
            return $this->json(['status' => 200, 'data' => [
                'items'  => $items,
                'counts' => $svc->transportCounts(),
                'strips' => $strips,
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 500);
        }
    }

    // GET tour/transport_ops/masters — the vehicle master, the freelancers, the document list
    public function transportMasters()
    {
        try {
            $db  = $this->db();
            $svc = $this->svc();
            $take = new IntakeService($db, $this->user());
            return $this->json(['status' => 200, 'data' => [
                'vehicles'    => $svc->vehicles(),
                'freelancers' => $take->freelancers(),
                // tf-masters-r3: the Drivers & Guides master, the list the crew lines pick from
                'people'      => $svc->crewPeople(),
                // tf-crew-master-r1: a JSD / JSCG / JSNTG (or GUIDE_JP) line lists its own category only — strictly
                'line_categories' => CrewPeople::LINE_CATEGORY,
                'category_names'  => array_map(static fn ($c) => $c['name'], CrewPeople::CATEGORIES),
                // the allocation picker replaces the drop-down once the Driver & Guide Master (schema 44) is installed
                'master_ready'    => (new CrewPeople($db, $this->user()))->masterReady(),
                'doc_types'   => $svc->docTypes(),
            ]]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 200, 'data' => ['vehicles' => [], 'freelancers' => [], 'people' => [], 'doc_types' => [], 'error' => $e->getMessage()]]);
        }
    }

    // GET tour/transport_ops/card/(:num)
    public function transportCard($jobId)
    {
        try {
            $svc  = $this->svc();
            $card = $svc->transportJob((int) $jobId);
            $card['strip'] = $svc->stripFor((int) $card['tour_id']);
            return $this->json(['status' => 200, 'data' => $card]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/transport_ops/card/(:num)/(:segment)  vehicle | status | note | leg | leg/(:num)
    public function transportAction($jobId, $action, $sub = null)
    {
        try {
            if ((string) $action === 'vehicle' && !$this->canAllocate()) {   // tf-pilot-r2: checked before anything is read
                return $this->refuse('Setting the vehicle is an allocation: it needs the Driver Allocation privilege (Operations).', 'allocation (vehicle)');
            }
            $b   = $this->body();
            $svc = $this->svc();
            $id  = (int) $jobId;
            $was = (string) $action === 'note' ? null : $this->before('transport_job', $id);
            switch ((string) $action) {
                case 'vehicle':
                    if (!$this->canAllocate()) {
                        return $this->refuse('Setting the vehicle is an allocation: it needs the Driver Allocation privilege (Operations).', 'allocation (vehicle)');
                    }
                    // tf-taxiq-r7: after a final confirmation the desk has to say why the vehicle changed
                    $card = $svc->allocateVehicle($id, isset($b['vehicle_id']) && $b['vehicle_id'] !== '' ? (int) $b['vehicle_id'] : null, $b['reg'] ?? null, $b['note'] ?? null, $b['change_reason'] ?? null);
                    $msg  = 'Vehicle set.';
                    break;
                case 'status':
                    if ((string) ($b['status'] ?? '') === 'settled' && $svc->hasDriverAdvance() && !$this->holds(Desks::DRIVER_ADVANCE_URL)) {
                        return $this->json(['status' => 403, 'message' => 'Settling the card freezes the driver\'s settlement, which needs the Driver advance privilege (Management, or a group granted it under Settings → Group Privileges).'], 403);
                    }
                    $card = $svc->transportStatus($id, (string) ($b['status'] ?? ''), $b['note'] ?? null, !empty($b['hire_by_hand']));
                    $msg  = 'Now ' . str_replace('_', ' ', (string) $card['status']) . '.';
                    if ((string) $card['status'] === 'settled' && !empty($card['settlement']['frozen'])) {
                        $st  = $card['settlement'];
                        $msg = 'Settled. ' . $st['settlement_no'] . ': payable ' . number_format((float) $st['payable'], 2) . ' − advances ' . number_format((float) $st['advances'], 2) . ' ' . ((float) $st['adjustments'] >= 0 ? '+' : '−') . ' adjustments ' . number_format(abs((float) $st['adjustments']), 2) . ' = balance ' . $st['currency'] . ' ' . number_format((float) $st['balance'], 2) . '. Print the Settlement from the driver\'s pack.';
                    }
                    break;
                case 'advance':
                    // card/(:num)/advance {crew_id, pct, amount, note, adjust_note} — a driver's advance requested against the payable (schema 26)
                    $card = $svc->requestAdvance($id, $b, $this->holds(Desks::DRIVER_ADVANCE_URL));
                    $last = end($card['advances']);
                    $msg  = $last ? $last['advance_no'] . ' requested: ' . $card['money']['currency'] . ' ' . number_format((float) $last['amount'], 2) . ($last['pct'] !== null ? ' (' . rtrim(rtrim(number_format((float) $last['pct'], 2), '0'), '.') . '% of ' . number_format((float) $last['base_total'], 2) . ')' : '') . ' for ' . $last['person_name'] . '. It waits for approval.' : 'Advance requested.';
                    break;
                case 'adjustment':
                    // card/(:num)/adjustment {label, amount, note} — plus or minus on the settlement (the privilege)
                    if (!$this->holds(Desks::DRIVER_ADVANCE_URL)) {
                        return $this->json(['status' => 403, 'message' => 'A settlement adjustment needs the Driver advance privilege.'], 403);
                    }
                    $card = $svc->addAdjustment($id, $b);
                    $msg  = 'Adjustment added; the balance follows.';
                    break;
                case 'note':
                    $card = $svc->transportNote($id, $b['note'] ?? null);
                    $msg  = 'Note saved.';
                    break;
                case 'leg':
                    if ($sub !== null && !empty($b['delete'])) {
                        $card = $svc->deleteLeg($id, (int) $sub);
                        $msg  = 'Leg removed from the schedule. The costed kilometres are unchanged.';
                    } else {
                        $card = $svc->saveLeg($id, $sub === null ? null : (int) $sub, $b);
                        $msg  = $sub === null ? 'Leg added to the schedule.' : 'Leg updated.';
                    }
                    break;
                case 'actual':
                    // card/(:num)/actual/(:num) {km, note} — the kilometres actually driven on one leg, after the tour
                    if ($sub === null) {
                        return $this->json(['status' => 400, 'message' => 'Say which leg the actual kilometres are for.'], 400);
                    }
                    $card  = $svc->saveLegActual($id, (int) $sub, $b['km'] ?? null, $b['note'] ?? null);
                    $typed = isset($b['km']) && $b['km'] !== '' && $b['km'] !== null;
                    $msg   = $typed ? 'Actual kilometres kept. Every later quotation on this sector learns from them.' : 'Actual kilometres cleared.';
                    break;
                default:
                    return $this->json(['status' => 404, 'message' => 'Unknown action.'], 404);
            }
            $this->after('transport_job', $id, $was, (string) $action, $card);
            $card['strip'] = $svc->stripFor((int) $card['tour_id']);
            return $this->json(['status' => 200, 'message' => $msg, 'data' => $card]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/transport_ops/crew/(:num) {supplier_acc, person_name, person_bata, status, note}
    public function crew($crewId)
    {
        try {
            if (!$this->canAllocate()) {
                return $this->refuse('Engaging a driver or guide is an allocation: it needs the Driver Allocation privilege (Operations).', 'allocation (crew)');
            }
            $b = $this->body();
            $svc  = $this->svc();
            $jobId = (int) ($this->db()->query('SELECT job_id FROM tf_transport_crew WHERE id = ?', [(int) $crewId])->getRowArray()['job_id'] ?? 0);
            $was  = $jobId ? $this->before('transport_job', $jobId) : null;
            // tf-taxiq-r7: after a final confirmation the desk has to say why the driver or guide changed
            $card = $svc->engageCrew((int) $crewId, $b['supplier_acc'] ?? null, $b['person_name'] ?? null, $b['person_bata'] ?? null, (string) ($b['status'] ?? 'engaged'), $b['note'] ?? null, isset($b['person_id']) && $b['person_id'] !== '' ? (int) $b['person_id'] : null, !empty($b['acknowledge']), $b['change_reason'] ?? null);
            $this->after('transport_job', $jobId, $was, 'crew', $card);
            $card['strip'] = $svc->stripFor((int) $card['tour_id']);
            return $this->json(['status' => 200, 'message' => 'Crew updated.', 'data' => $card]);
        } catch (CrewWarning $e) {
            // tf-crew-master-r1: the clear warning before the allocation; the desk sends the same again with acknowledge
            return $this->json(['status' => 409, 'message' => $e->getMessage(), 'data' => ['warnings' => $e->warnings]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/transport_ops/crew/(:num)/replace {date, last_day, supplier_acc, person_name, person_bata, note}
    // A driver or guide replaced mid-tour (schema 24): the Crew replacement privilege, checked here
    // as well as on the screen. The vehicle on the card is not touched.
    public function crewReplace($crewId)
    {
        try {
            if (!$this->holds(Desks::CREW_CHANGE_URL)) {
                return $this->refuse('Replacing a driver or guide mid-tour needs the Crew replacement privilege (Management, or a group granted it under Settings → Group Privileges).', 'crew replacement');   // tf-pilot-r2: recorded
            }
            $b = $this->body();
            $svc  = $this->svc();
            $jobId = (int) ($this->db()->query('SELECT job_id FROM tf_transport_crew WHERE id = ?', [(int) $crewId])->getRowArray()['job_id'] ?? 0);
            $was  = $jobId ? $this->before('transport_job', $jobId) : null;
            $card = $svc->replaceCrew((int) $crewId, $b);
            $this->after('transport_job', $jobId, $was, 'crew replaced', $card);
            $card['strip'] = $svc->stripFor((int) $card['tour_id']);
            return $this->json(['status' => 200, 'message' => 'Replaced. Both people stay on the card with their days; issue each their payment voucher for the days worked. The vehicle is unchanged.', 'data' => $card]);
        } catch (CrewWarning $e) {
            return $this->json(['status' => 409, 'message' => $e->getMessage(), 'data' => ['warnings' => $e->warnings]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/transport_ops/crew/(:num)/dates {date_from, date_to, note} — the days a person works
    public function crewDates($crewId)
    {
        try {
            if (!$this->canAllocate() && !$this->holds(Desks::CREW_CHANGE_URL)) {
                return $this->refuse('The days a person works are part of the allocation: it needs the Driver Allocation (or Crew replacement) privilege.', 'allocation (crew dates)');
            }
            $b = $this->body();
            $svc  = $this->svc();
            $jobId = (int) ($this->db()->query('SELECT job_id FROM tf_transport_crew WHERE id = ?', [(int) $crewId])->getRowArray()['job_id'] ?? 0);
            $was  = $jobId ? $this->before('transport_job', $jobId) : null;
            $card = $svc->crewDates((int) $crewId, $b['date_from'] ?? null, $b['date_to'] ?? null, $b['note'] ?? null);
            $this->after('transport_job', $jobId, $was, 'crew dates', $card);
            $card['strip'] = $svc->stripFor((int) $card['tour_id']);
            $line = current(array_filter($card['crew'] ?? [], static fn ($c) => (int) $c['id'] === (int) $crewId)) ?: null;
            $stale = $line && !empty($line['voucher']) && !empty($line['voucher']['stale']);
            return $this->json(['status' => 200, 'message' => 'Days saved.' . ($stale ? ' Voucher ' . $line['voucher']['voucher_no'] . ' was issued for other days — cancel it and issue a fresh one.' : ''), 'data' => $card]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/transport_ops/crew/(:num)/voucher {note} — a payment voucher for the days actually worked
    public function crewVoucher($crewId)
    {
        try {
            $b = $this->body();
            $svc  = $this->svc();
            $jobId = (int) ($this->db()->query('SELECT job_id FROM tf_transport_crew WHERE id = ?', [(int) $crewId])->getRowArray()['job_id'] ?? 0);
            $card = $svc->issueCrewVoucher((int) $crewId, $b['note'] ?? null);
            $card['strip'] = $svc->stripFor((int) $card['tour_id']);
            $line = current(array_filter($card['crew'] ?? [], static fn ($c) => (int) $c['id'] === (int) $crewId)) ?: null;
            $v = $line['voucher'] ?? null;
            return $this->json(['status' => 200, 'message' => $v ? 'Voucher ' . $v['voucher_no'] . ' issued: ' . $v['days'] . ' day(s) × ' . number_format($v['rate'], 2) . ' = ' . $v['currency'] . ' ' . number_format($v['amount'], 2) . '. Print it from the line.' : 'Voucher issued.', 'data' => $card]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/transport_ops/voucher/(:num)/cancel {why}
    public function crewVoucherCancel($voucherId)
    {
        try {
            $b = $this->body();
            $svc  = $this->svc();
            $card = $svc->cancelCrewVoucher((int) $voucherId, $b['why'] ?? ($b['note'] ?? null));
            $card['strip'] = $svc->stripFor((int) $card['tour_id']);
            return $this->json(['status' => 200, 'message' => 'Voucher cancelled; it stays on the card marked so. Issue a fresh one when the days are right.', 'data' => $card]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET tour/transport_ops/voucher/(:num) — the payment voucher as a page to print
    public function crewVoucherPage($voucherId)
    {
        try {
            $d = $this->svc()->crewVoucher((int) $voucherId);
            $logo = null;
            try {
                $f = defined('FCPATH') ? FCPATH . 'assets/images/email/logo.png' : null;
                $logo = $f && is_file($f) ? base_url('assets/images/email/logo.png') : null;
            } catch (\Throwable $e) {
                $logo = null;
            }
            return $this->response->setStatusCode(200)->setBody(view('pages/tour/crew_voucher', $d + ['logo' => $logo, 'user' => $this->actorName()]));
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setBody(esc($e->getMessage()));
        }
    }

    // GET tour/transport_ops/card/(:num)/pack/(:segment)[?pdf=1] — one document of the driver's pack, generated
    // from the record: the itinerary copy (the programme sheet), the hotel list, the allowance sheet, the log
    // sheet. A page to print, or the same as a PDF when the library is on the server (else the print view opens).
    public function packDocument($jobId, $code)
    {
        try {
            $svc = $this->svc();
            $d   = $svc->packDocument((int) $jobId, (string) $code);
            $pdf = (bool) $this->request->getGet('pdf');
            if ($d['kind'] === 'route') {
                return $this->routeDocument($d, $pdf);   // tf-route-map-r1
            }
            if ($d['kind'] === 'itinerary') {
                $tpl  = 'pages/tour/programme';
                $data = ['details' => $d['details'], 'user' => $this->actorName(), 'pack_title' => $d['title'], 'vehicle_card' => $d['job']];
            } else {
                $tpl  = 'pages/tour/driver_pack';
                $data = $d + ['user' => $this->actorName()];
            }
            if (!$pdf) {
                return view($tpl, $data);
            }
            if (!class_exists('\TCPDF')) {
                // the PDF library is not installed on this server: the print view opens and the browser's own "Save as PDF" makes the file
                return view($tpl, $data + ['auto_print' => true, 'no_pdf_lib' => true]);
            }
            $html = view($tpl, $data + ['for_pdf' => true]);
            $doc  = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $doc->SetCreator('Avenir Holidays');
            $doc->SetAuthor('Avenir Holidays');
            $doc->SetTitle($d['title'] . ' ' . ($d['job']['tour_no'] ?? ''));
            $doc->setPrintHeader(false);
            $doc->setPrintFooter(false);
            $doc->SetMargins(12, 12, 12);
            $doc->SetAutoPageBreak(true, 14);
            $doc->AddPage();
            $doc->writeHTML($html, true, false, true, false, '');
            $name = preg_replace('/[^A-Za-z0-9\-]+/', '-', $d['title']) . '-' . preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($d['job']['tour_no'] ?? 'tour')) . '.pdf';
            return $this->response->setHeader('Content-Type', 'application/pdf')->setBody($doc->Output($name, 'S'))->setHeader('Content-Disposition', 'inline; filename="' . $name . '"');
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setBody(esc($e->getMessage()));
        }
    }

    /**
     * tf-route-map-r1: the driver's route map — GET card/(:num)/pack/ROUTE
     *   (no query)  the sheet as a page: the map, the day-by-day table, the totals, with print and the downloads
     *   ?map=1      the map alone as a PNG (the sheet shows it; the PDF embeds it)
     *   ?img=1      the whole card as one PNG, 1080 px wide, to send on WhatsApp
     *   ?pdf=1      the sheet as a PDF (TCPDF), or the print view when the library is not on the server
     * Read only: the picture is drawn from the card's saved legs; nothing is written but the PDF's temporary map file.
     */
    private function routeDocument(array $d, bool $pdf)
    {
        $r    = $d['route'];
        $map  = new \App\Libraries\TourFlow\RouteMap($this->db());
        $slug = preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($d['job']['tour_no'] ?? 'tour'));
        if ($this->request->getGet('img')) {
            return $this->response->setHeader('Content-Type', 'image/png')->setHeader('Content-Disposition', 'attachment; filename="Route-map-' . $slug . '.png"')->setHeader('Cache-Control', 'no-store')->setBody($map->png($r, 'card'));
        }
        if ($this->request->getGet('map')) {
            return $this->response->setHeader('Content-Type', 'image/png')->setHeader('Cache-Control', 'no-store')->setBody($map->png($r, 'sheet'));
        }
        $base = base_url('tour/transport_ops/card/' . (int) $d['job']['id'] . '/pack/' . rawurlencode((string) $d['code']));
        $data = $d + ['user' => $this->actorName(), 'map_url' => $base . '?map=1', 'img_url' => $base . '?img=1', 'pdf_url' => $base . '?pdf=1'];
        if (!$pdf) {
            return view('pages/tour/driver_route', $data);
        }
        if (!class_exists('\TCPDF') && is_file(ROOTPATH . 'vendor/tecnickcom/tcpdf/tcpdf.php')) {
            require_once ROOTPATH . 'vendor/tecnickcom/tcpdf/tcpdf.php';   // the library is in vendor/ but not in composer's class map on this server
        }
        if (!class_exists('\TCPDF')) {
            return view('pages/tour/driver_route', $data + ['auto_print' => true, 'no_pdf_lib' => true]);
        }
        $dir = WRITEPATH . 'tf_tmp/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $dir . 'route_' . (int) $d['job']['id'] . '_' . getmypid() . '.png';
        file_put_contents($file, $map->png($r, 'map'));
        try {
            $html = view('pages/tour/driver_route', $data + ['for_pdf' => true, 'map_file' => $file]);
            $doc  = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $doc->SetCreator('Avenir Holidays');
            $doc->SetAuthor('Avenir Holidays');
            $doc->SetTitle('Route map ' . ($d['job']['tour_no'] ?? ''));
            $doc->setPrintHeader(false);
            $doc->setPrintFooter(false);
            $doc->SetMargins(10, 10, 10);
            $doc->SetAutoPageBreak(true, 12);
            $doc->SetFont('dejavusans', '', 10);
            $doc->AddPage();
            $doc->writeHTML($html, true, false, true, false, '');
            $out = $doc->Output('Route-map-' . $slug . '.pdf', 'S');
        } finally {
            @unlink($file);
        }
        return $this->response->setHeader('Content-Type', 'application/pdf')->setHeader('Content-Disposition', 'inline; filename="Route-map-' . $slug . '.pdf"')->setBody($out);
    }

    // POST tour/transport_ops/advance/(:num)/(approve|adjust|pay|cancel) — the driver's advance (schema 26). Approve, adjust and
    // pay need the Driver advance privilege; the desk may cancel one it requested while it is only requested.
    public function advance($advanceId, $action)
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();
            $id  = (int) $advanceId;
            $may = $this->holds(Desks::DRIVER_ADVANCE_URL);
            switch ((string) $action) {
                case 'approve':
                    if (!$may) {
                        return $this->json(['status' => 403, 'message' => 'Approving an advance needs the Driver advance privilege (Management, or a group granted it under Settings → Group Privileges).'], 403);
                    }
                    $card = $svc->approveAdvance($id, $b['note'] ?? null);
                    $msg  = 'Approved. Record the payment once it is made.';
                    break;
                case 'adjust':
                    if (!$may) {
                        return $this->json(['status' => 403, 'message' => 'Adjusting an advance needs the Driver advance privilege.'], 403);
                    }
                    $card = $svc->adjustAdvance($id, $b['amount'] ?? null, $b['why'] ?? ($b['note'] ?? null));
                    $msg  = 'Amount adjusted; the reason stays on the advance.';
                    break;
                case 'pay':
                    if (!$may) {
                        return $this->json(['status' => 403, 'message' => 'Recording a payment needs the Driver advance privilege.'], 403);
                    }
                    $card = $svc->payAdvance($id, $b);
                    $msg  = 'Paid — recorded with who, when, how and the reference. It comes off the settlement.';
                    break;
                case 'cancel':
                    $row = $this->db()->query('SELECT status FROM tf_driver_advance WHERE id = ?', [$id])->getRowArray();
                    if (!$may && (string) ($row['status'] ?? '') !== 'requested') {
                        return $this->json(['status' => 403, 'message' => 'Cancelling an approved advance needs the Driver advance privilege.'], 403);
                    }
                    $card = $svc->cancelAdvance($id, $b['why'] ?? ($b['note'] ?? null));
                    $msg  = 'Cancelled; it stays on the card marked so.';
                    break;
                default:
                    return $this->json(['status' => 404, 'message' => 'Unknown action.'], 404);
            }
            $card['strip'] = $svc->stripFor((int) $card['tour_id']);
            return $this->json(['status' => 200, 'message' => $msg, 'data' => $card]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/transport_ops/adjustment/(:num)/remove {why} — an adjustment taken off the settlement (the privilege)
    public function adjustmentRemove($adjId)
    {
        try {
            if (!$this->holds(Desks::DRIVER_ADVANCE_URL)) {
                return $this->json(['status' => 403, 'message' => 'A settlement adjustment needs the Driver advance privilege.'], 403);
            }
            $b    = $this->body();
            $card = $this->svc()->removeAdjustment((int) $adjId, $b['why'] ?? ($b['note'] ?? null));
            $card['strip'] = $this->svc()->stripFor((int) $card['tour_id']);
            return $this->json(['status' => 200, 'message' => 'Adjustment taken off; the balance follows.', 'data' => $card]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/transport_ops/doc/(:num) {state, note}
    public function doc($docId)
    {
        try {
            $b = $this->body();
            $jobId = (int) ($this->db()->query('SELECT job_id FROM tf_transport_doc WHERE id = ?', [(int) $docId])->getRowArray()['job_id'] ?? 0);
            $was  = $jobId ? $this->before('transport_job', $jobId) : null;
            $card = $this->svc()->docState((int) $docId, (string) ($b['state'] ?? ''), $b['note'] ?? null);
            $this->after('transport_job', $jobId, $was, 'document', $card);
            return $this->json(['status' => 200, 'message' => 'Document updated.', 'data' => $card]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/transport_ops/doc_type {code, name, phase, active} — the driver's pack list
    public function docType()
    {
        try {
            $b = $this->body();
            $list = $this->svc()->saveDocType((string) ($b['code'] ?? ''), (string) ($b['name'] ?? ''), (string) ($b['phase'] ?? 'before'), !empty($b['active']));
            return $this->json(['status' => 200, 'message' => 'Document list saved. New cards start with this list; cards already open keep theirs.', 'data' => $list]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // =====================================================================
    //  The Board
    // =====================================================================

    // GET tour/board
    public function board()
    {
        return $this->page('pages/tour/board', self::BOARD);
    }

    // GET tour/board/data?which=live|arriving|problems|completed|cancelled|all&q=&handler=&from=&to=
    public function boardData()
    {
        try {
            $svc = $this->svc();
            $take = new IntakeService($this->db(), $this->user());
            return $this->json(['status' => 200, 'data' => [
                'tours'    => $svc->board($this->filters('which', 'live')),
                'inquiries' => $take->counts(),
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 500);
        }
    }

    // GET tour/board/tour/(:num) — the tour, its cards, and its whole history (the trail and every edit)
    public function tour($tourId)
    {
        try {
            $t = $this->svc()->get((int) $tourId);
            if (!$t) {
                return $this->json(['status' => 404, 'message' => 'Tour not found.'], 404);
            }
            try {
                $t['details'] = $this->svc()->details((int) $tourId);
            } catch (\Throwable $e) {
                $t['details'] = null;
            }
            try {
                $rows = (new Activity($this->db(), $this->user(), $this->userId(), $this->actorName()))->timeline((int) $t['intake_id'], (int) $tourId, 300);
                if (!$this->holds(TourIntakeController::PRICING_URL)) {
                    $rows = array_values(array_filter($rows, static fn ($r) => ($r['entity'] ?? '') !== 'pricing'));
                }
                $t['history'] = $rows;
            } catch (\Throwable $e) {
                $t['history'] = [];
            }
            return $this->json(['status' => 200, 'data' => $t]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET tour/board/tour/(:num)/record — the complete record of a confirmed tour, from the inquiry as it arrived
    // to where the tour stands now (tf-record-r1): a page of its own, printable, read from the one record and never
    // copied. Costs show only to a group holding the Pricing privilege; the page is reached under the Board's URL.
    public function record($tourId)
    {
        try {
            $db   = $this->db();
            $take = (new IntakeService($db, $this->user(), new HotelRateService($db, $this->user())))->withActor($this->userId(), $this->actorName());
            $sees = $this->holds(TourIntakeController::PRICING_URL);
            $r    = (new TourRecord($db, $this->user(), $this->svc(), $take, new QuoteService($db, $this->user(), $take), $this->activity()))->assemble((int) $tourId, $sees);
            if (!$r) {
                return $this->response->setStatusCode(404)->setBody('Tour not found.');
            }
            $iid = (int) $r['tour']['intake_id'];
            // the bar links the inquiry and the desks only for a sign-in that may open them (a link that answers Forbidden is no link)
            $canIntake = $this->holds(TourIntakeController::URL);
            return view('pages/tour/tour_record', [
                'r'            => $r,
                'urls'         => [
                    'board'     => base_url(self::BOARD),
                    'intake'    => $canIntake ? base_url(TourIntakeController::URL) : null,
                    'hotels'    => $this->holds(self::HOTEL) ? base_url(self::HOTEL) : null,
                    'transport' => $this->holds(self::TRANSPORT) ? base_url(self::TRANSPORT) : null,
                    'quote'     => $canIntake ? base_url(TourIntakeController::URL . '/intake/' . $iid . '/quote') : null,
                ],
                'sees_pricing' => $sees,
                'user'         => $this->actorName(),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setBody(esc($e->getMessage()));
        }
    }

    // POST tour/board/reauth {password, purpose} — the signed-in person's password, checked and nothing else (the
    // first of the three steps before a confirmed tour is un-confirmed, amended, cancelled or reopened). Management
    // only, as those actions are.
    public function reauth()
    {
        try {
            if (!$this->holds(Desks::MANAGE_URL)) {
                return $this->json(['status' => 403, 'ok' => false, 'message' => 'Only Management changes a confirmed tour (the Tour Desk Manage privilege, tour/manage).'], 403);
            }
            $b = $this->body();
            $r = Reauth::verify($this->db(), $this->userId(), isset($b['password']) ? (string) $b['password'] : '', mb_substr(trim((string) ($b['purpose'] ?? 'Tour Board')), 0, 80), $this->activity());
            return $this->json($r['ok'] ? ['status' => 200, 'ok' => true, 'message' => $r['message']] : ['status' => 403, 'ok' => false, 'message' => $r['message']], $r['ok'] ? 200 : 403);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/board/tour/(:num)/(:segment) {why, password}  unconfirm | amend | cancel | reopen
    //
    // Every change to a confirmed tour is Management's, and each one takes the same three things, checked here on the
    // server in this order: the Tour Desk Manage privilege, a reason, and the signed-in person's own password (checked
    // as the sign-in checks it, never stored, never logged; five wrong ones in fifteen minutes lock the check). A request
    // that fails any of the three changes nothing — not the tour, the inquiry, the cards or the desks; only a wrong
    // password leaves its own line on the Activity log. When all three hold, the action runs and one Activity line
    // records who did it, when, and why.
    private const ACTIONS = [
        'unconfirm' => ['verb' => 'un-confirms', 'done' => 'un-confirmed', 'purpose' => 'Un-confirm tour', 'action' => 'unconfirmed', 'to' => 'working'],
        'amend'     => ['verb' => 'amends', 'done' => 'opened for amendment', 'purpose' => 'Amend tour', 'action' => 'opened_for_amendment', 'to' => 'amending'],
        'cancel'    => ['verb' => 'cancels', 'done' => 'cancelled', 'purpose' => 'Cancel tour', 'action' => 'cancelled', 'to' => 'cancelled'],
        'reopen'    => ['verb' => 'reopens', 'done' => 'reopened', 'purpose' => 'Reopen tour', 'action' => 'reopened', 'to' => 'confirmed'],
    ];

    public function tourAction($tourId, $action)
    {
        try {
            $action = (string) $action;
            if (!isset(self::ACTIONS[$action])) {
                return $this->json(['status' => 404, 'message' => 'Unknown action.'], 404);
            }
            $w   = self::ACTIONS[$action];
            $b   = $this->body();
            $id  = (int) $tourId;
            $t   = $this->db()->query('SELECT tour_no, intake_id, stage FROM tf_tour WHERE id = ?', [$id])->getRowArray();
            $no  = $t['tour_no'] ?? ('#' . $id);
            if (!$this->holds(Desks::MANAGE_URL)) {
                return $this->json(['status' => 403, 'message' => 'Only Management ' . $w['verb'] . ' a confirmed tour (the Tour Desk Manage privilege, tour/manage). Nothing was changed.'], 403);
            }
            $why = trim((string) ($b['why'] ?? ''));
            if ($why === '') {
                return $this->json(['status' => 400, 'message' => 'Say why the tour is ' . $w['done'] . '; the reason is required and stays on the trail. Nothing was changed.'], 400);
            }
            $auth = Reauth::verify($this->db(), $this->userId(), isset($b['password']) ? (string) $b['password'] : '', $w['purpose'] . ' ' . $no, $this->activity());
            if (!$auth['ok']) {
                return $this->json(['status' => 403, 'message' => $auth['message']], 403);
            }
            $svc = $this->svc();
            switch ($action) {
                case 'unconfirm':
                    $data = $svc->unconfirm($id, $why);
                    $msg  = 'Un-confirmed by ' . $this->actorName() . '. The cards are removed and the inquiry is open again.';
                    break;
                case 'amend':
                    $data = $svc->unlock($id, $why);
                    $msg  = 'The inquiry is open for correction. Confirm it again when it is right; cards that have not moved are rebuilt, the rest are marked for their desk.';
                    break;
                case 'cancel':
                    // the reason, who and when go on the tour, every card's trail and the Activity log
                    $data = $svc->cancel($id, $why);
                    $msg  = 'Tour cancelled by ' . $this->actorName() . '. Every card is marked cancelled for the hotel and transport desks, and the trail is kept.';
                    break;
                default:
                    $data = $svc->reopen($id, $why);
                    $msg  = 'Tour reopened by ' . $this->actorName() . '; the cards are back at their first step.';
            }
            try {
                $this->activity()->record('tour', $id, $t ? (int) $t['intake_id'] : null, $id, $w['action'],
                    'Tour ' . $no . ' ' . $w['done'] . ' from the Board — ' . $why . ' — by ' . $this->actorName() . ' (Management, password checked)',
                    $t ? [['what' => 'stage', 'from' => $t['stage'], 'to' => $w['to']]] : null);
            } catch (\Throwable $e) {
                // the action stands; the tour's own trail line was written by the service
            }
            return $this->json(['status' => 200, 'message' => $msg, 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // ---- tf-supmail-r1: the activity and restaurant reservation requests, on the Activities desk ----

    // GET tour/hotel_bookings/tour/(:num)/supplier_requests — what is prepared for this tour's activity and meal lines
    public function supplierRequests($tourId)
    {
        try {
            $db = $this->db();
            if (!\App\Libraries\TourFlow\SupplierMailer::ready($db)) {
                return $this->json(['status' => 200, 'data' => ['installed' => false, 'parties' => [], 'messages' => []]]);
            }
            $sm = new \App\Libraries\TourFlow\SupplierMailer($db, $this->user(), $this->actorName(), $this->userId());
            $sm->prepare((int) $tourId, false);   // reading the desk prepares, never sends
            $parties = $sm->parties((int) $tourId);
            $sender  = \App\Libraries\TourFlow\CommsMail::senderFor($db, $this->userId(), $this->user());
            return $this->json(['status' => 200, 'data' => [
                'installed'   => true,
                // the screen gets what it shows; the row the letter was written from stays on the server
                'parties'     => array_map(static fn ($p) => array_diff_key($p, array_flip(['line', 'meal'])), $parties),
                // what is missing: the contacts the masters have not got, and the words the letters could not fill
                'missing'     => array_values(array_unique(array_filter(array_merge(
                    array_map(static fn ($p) => $p['missing'], $parties),
                    array_reduce($parties, function (array $c, array $p) use ($sm, $tourId) {
                        foreach ((array) ($sm->letter((int) $tourId, $p)['missing'] ?? []) as $w) {
                            $c[] = 'The letter to ' . $p['name'] . ' asks for ' . $w . ', and the record has none.';
                        }
                        return $c;
                    }, [])
                )))),
                // each request with the reason it cannot go yet, so the desk sees the hold rather than a dead button
                'messages'    => array_map(static function (array $m) use ($sm) {
                    $m['hold'] = (string) $m['state'] === 'prepared' ? $sm->holes((int) $m['id'], $m) : null;
                    return $m;
                }, $sm->messages((int) $tourId)),
                'unsent'      => $sm->unsent((int) $tourId),
                'can_send'    => \App\Libraries\TourFlow\SupplierMailer::canSend($sender),
                'auto_send'   => \App\Libraries\TourFlow\AutoSend::on($db),
                'sender_note' => $sender['note'] ?? null,
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/hotel_bookings/supplier_request/(:num)/send — send one prepared request; never twice
    public function supplierRequestSend($messageId)
    {
        try {
            $b = $this->body();
            $sender = \App\Libraries\TourFlow\CommsMail::senderFor($this->db(), $this->userId(), $this->user());
            $sender['user_id'] = $this->userId();
            $sm = new \App\Libraries\TourFlow\SupplierMailer($this->db(), $this->user(), $this->actorName(), $this->userId());
            $r  = $sm->send((int) $messageId, $sender, $b['to'] ?? null, $b['cc'] ?? null);
            return $this->json(['status' => !empty($r['ok']) ? 200 : 400] + $r);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/hotel_bookings/supplier_request/(:num)/edit {subject, body} — this one letter's own words
    public function supplierRequestEdit($messageId)
    {
        try {
            $b  = $this->body();
            $sm = new \App\Libraries\TourFlow\SupplierMailer($this->db(), $this->user(), $this->actorName(), $this->userId());
            $m  = $sm->edit((int) $messageId, $b['subject'] ?? null, $b['body'] ?? null);
            return $this->json(['status' => 200,
                'message' => 'Saved for this letter only. The template on Master Files is unchanged, and this wording is what will go.',
                'data' => ['message' => $m, 'hold' => $sm->holes((int) $messageId, $m)]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET tour/hotel_bookings/supplier_request/(:num)/preview — exactly what would go; sends nothing
    public function supplierRequestPreview($messageId)
    {
        try {
            $db = $this->db();
            $sm = new \App\Libraries\TourFlow\SupplierMailer($db, $this->user(), $this->actorName(), $this->userId());
            $m  = $db->query('SELECT * FROM tf_service_message WHERE id = ?', [(int) $messageId])->getRowArray();
            if (!$m) {
                return $this->json(['status' => 404, 'message' => 'That request is not on the record.']);
            }
            $sender = \App\Libraries\TourFlow\CommsMail::senderFor($db, $this->userId(), $this->user());
            $hold   = (string) $m['state'] === 'prepared' ? $sm->holes((int) $messageId, $m) : null;
            $stops  = [];
            if ($hold !== null) {
                $stops[] = $hold;
            }
            if (\App\Libraries\TourFlow\SupplierMailer::canSend($sender) === false) {
                $stops[] = ($sender['note'] ?? 'No mailbox is set up to send from.') . ' Nothing can be sent until it is.';
            }
            if (trim((string) ($m['to_addr'] ?? '')) === '') {
                $stops[] = 'There is no e-mail address for ' . $m['party_name'] . '. Send it by hand or print it, and record that here.';
            }
            if ((string) $m['state'] === 'sent') {
                $stops[] = 'This request was already sent on ' . substr((string) $m['at'], 0, 16) . '. It is not sent twice.';
            }
            return $this->json(['status' => 200, 'data' => [
                'from'      => trim(($sender['name'] ?? 'Avenir Holidays') . ' <' . ($sender['email'] ?? '') . '>'),
                'to'        => $m['to_addr'],
                'cc'        => $m['cc_addr'],
                'always_cc' => \App\Libraries\TourFlow\MailServer::alwaysCc(),
                'subject'   => $m['subject'],
                'message'   => $m['body'],
                'signature' => \App\Libraries\TourFlow\CommsMail::signature($db, $this->userId(), $this->user()),
                'edited'    => !empty($m['edited_at']) ? ['at' => $m['edited_at'], 'by' => $m['edited_by']] : null,
                'state'     => $m['state'],
                'stops'     => $stops,
                'can_send'  => $stops === [],
                'sent'      => false,
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/hotel_bookings/supplier_request/(:num)/record — it went by hand, or an answer came back
    public function supplierRequestRecord($messageId)
    {
        try {
            $b  = $this->body();
            $sm = new \App\Libraries\TourFlow\SupplierMailer($this->db(), $this->user(), $this->actorName(), $this->userId());
            $r  = $sm->record((int) $messageId, (string) ($b['channel'] ?? 'other'), trim((string) ($b['note'] ?? '')), (string) ($b['kind'] ?? 'request'));
            return $this->json(['status' => 200] + $r);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
