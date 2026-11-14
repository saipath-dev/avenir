<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Activity;
use App\Libraries\TourFlow\CostingGuard;
use App\Libraries\TourFlow\Desks;
use App\Libraries\TourFlow\EditRequests;
use App\Libraries\TourFlow\EmailExtractor;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\IntakeService;
use App\Libraries\TourFlow\MealConflict;
use App\Libraries\TourFlow\MealMaster;
use App\Libraries\TourFlow\NoAuthority;
use App\Libraries\TourFlow\PaxMatrix;
use App\Libraries\TourFlow\QuoteMail;
use App\Libraries\TourFlow\QuoteService;
use App\Libraries\TourFlow\Reauth;
use App\Libraries\TourFlow\HotelRateJump;
use App\Libraries\TourFlow\InquiryReadiness;
use App\Libraries\TourFlow\NotReadyToQuote;
use App\Libraries\TourFlow\SectionChecks;
use App\Libraries\TourFlow\SuperAdminOnly;
use App\Libraries\TourFlow\Staff;
use App\Libraries\TourFlow\TextChecks;
use App\Libraries\TourFlow\TourMailer;
use App\Libraries\TourFlow\TourService;

/**
 * Tour Inquiry — tour/intake
 *
 * Where a tour starts: paste what the agent sent, have it read, correct anything
 * that is wrong, and see straight away which of the hotels they asked for can
 * actually be priced. Nothing here writes to consignee, tour_quotations or any
 * hr2_ table, so nothing on an existing screen changes until an inquiry is
 * deliberately converted into a tour.
 *
 * One menu entry = one privilege (UserPermissionFilter): everyone whose group
 * holds "Tour Inquiry" may open this panel, read an email into it and correct it.
 */
class TourIntakeController extends BaseController
{
    public const URL          = 'tour/intake';
    public const PRICING_URL  = 'tour/pricing';
    public const MILEAGE_URL  = 'tour/intake/mileage';
    public const EXC_URL      = 'master_files/excursion_rates';
    public const MANAGE_URL   = Desks::MANAGE_URL;

    /**
     * The edits that are written to the history, by method: each POST to one of these is
     * bracketed by a snapshot of the inquiry before and after, and the difference is written
     * with the word on the right. Confirm is not here — the desks' own trail records it.
     * The markup is written under 'pricing', which only a Pricing holder is shown.
     */
    /** The crew master (schema 23): driver and guide categories, languages and bata — Admin's screen; the quotation only reads it. */
    public const CREW_URL = 'master_files/crew_bata';
    /** The tour photo library (schema 27): Admin's screen; the quotation shows its pictures and staff pick a day's picture here. */
    public const PHOTOS_URL = 'master_files/tour_photos';
    /** The two programme masters (schema 29): free visits (never costed) and paid activities & services (costed). */
    public const FREE_URL = 'master_files/free_visits';
    public const PAID_URL = 'master_files/paid_services';

    private const TRACKED = [
        'read' => 'reread', 'field' => 'edit', 'day' => 'itinerary', 'pax' => 'passengers', 'email' => 'email', 'vehicle' => 'vehicles',
        'intakeCrew' => 'crew', 'sector' => 'mileage', 'google' => 'mileage', 'actual' => 'mileage', 'extra' => 'mileage', 'accom' => 'crew rooms', 'agent' => 'agent', 'service' => 'services', 'recheck' => 'recheck',
        'request' => 'requests', 'status' => 'status', 'rooms' => 'rooms', 'group' => 'groups', 'paxGroup' => 'passengers', 'vehicleDay' => 'vehicles',
        'excursion' => 'excursions', 'markup' => 'markup', 'go' => 'quotation', 'quoteAction' => 'quotation', 'emailEdit' => 'email',
        'followUp' => 'follow-up', 'freeVisit' => 'services',
        // schema 40: a meal of the Meal Master put on a day, changed or removed
        'meal' => 'meals', 'mealNew' => 'meals', 'mealsFill' => 'meals',
        // schema 32: the dates rule's own actions, each written to the history like any other edit
        'departureDay' => 'itinerary', 'extraNight' => 'itinerary', 'departure' => 'itinerary', 'dates' => 'edit', 'nightsMissing' => 'itinerary',
    ];

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

    /** The name the history shows for the signed-in person, looked up once per request. */
    private function actorName(): string
    {
        return $this->actorName ??= Staff::displayName($this->db(), $this->userId(), $this->user());
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    private function svc(): IntakeService
    {
        return (new IntakeService($this->db(), $this->user(), new HotelRateService($this->db(), $this->user())))->withActor($this->userId(), $this->actorName());
    }

    /**
     * Every action arrives here first (CodeIgniter calls _remap when a controller has one).
     * A tracked POST is bracketed with a snapshot before and after, so that whatever the
     * action changed is written to the history — one place, for every edit, instead of a
     * line in each method that would sooner or later be forgotten.
     */
    public function _remap(string $method, ...$params)
    {
        if (!method_exists($this, $method) || $method[0] === '_') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $isPost = strtoupper((string) $this->request->getMethod()) === 'POST';
        if ($isPost && isset(self::TRACKED[$method]) && isset($params[0]) && ctype_digit((string) $params[0])) {
            return $this->tracked((int) $params[0], self::TRACKED[$method], fn () => $this->{$method}(...$params));
        }
        return $this->{$method}(...$params);
    }

    /** Snapshot, do the work, snapshot again, write the difference. The history never stops the work. */
    private function tracked(int $id, string $action, \Closure $fn)
    {
        $act    = null;
        $before = null;
        try {
            $act    = new Activity($this->db(), $this->user(), $this->userId(), $this->actorName());
            $before = $act->snapshotIntake($id);
        } catch (\Throwable $e) {
            $act = null;
        }
        $result = $fn();
        if ($act !== null && $before !== null) {
            try {
                $after   = $act->snapshotIntake($id);
                $changes = Activity::diff($before, $after);
                $entity  = $action === 'markup' ? 'pricing' : 'inquiry';
                if ($entity !== 'pricing') {
                    // the markup is Management's; it is never written where the desk reads
                    $changes = array_values(array_filter($changes, static fn ($c) => $c['what'] !== 'markup %'));
                }
                if ($changes) {
                    $tour = null;
                    try {
                        $tour = $this->db()->query('SELECT id FROM tf_tour WHERE intake_id = ?', [$id])->getRowArray();
                    } catch (\Throwable $e) {
                        // no tour table yet
                    }
                    $act->record($entity, $id, $id, $tour ? (int) $tour['id'] : null, $action, Activity::summarise($changes), $changes);
                }
            } catch (\Throwable $e) {
                // a history line that will not write is not a reason to lose the edit
            }
        }
        return $result;
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

    private function isSysAdmin(): bool
    {
        return (int) ($this->menuData()['user_role'] ?? 0) === 1;
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

    private function quotes(): QuoteService
    {
        return new QuoteService($this->db(), $this->user(), $this->svc());
    }

    /** The costing as this person may see it: cost and price for everyone on the desk, markup and profit only for a group holding Pricing. */
    private function costForViewer(array $cost): array
    {
        if ($this->holds(self::PRICING_URL)) {
            return $cost;
        }
        $m = $cost['markup'] ?? [];
        $cost['markup'] = ['pct' => null, 'source' => $m['source'] ?? 'none', 'set_by' => null, 'set_at' => null, 'sell_usd' => $m['sell_usd'] ?? null, 'profit_usd' => null, 'per_person_usd' => $m['per_person_usd'] ?? null, 'hidden' => true];
        $cost['warnings'] = array_values(array_filter((array) ($cost['warnings'] ?? []), static fn ($w) => empty($w['internal'])));

        // tf-margin-r1 (2 Nov 2026), Shantha's rule: the tour's own COST TOTALS are Management's too.
        // A total cost shown beside the client price is the markup, one subtraction away, so hiding the
        // percentage while showing the total hides nothing. The totals go; the supplier figure each line
        // carries stays, because the desks need it to do the work (see the note in the handover).
        foreach (['cost_usd', 'cost_lkr', 'per_person_cost_usd'] as $k) {
            if (array_key_exists($k, $cost)) {
                $cost[$k] = null;
            }
        }
        $cost['cost_hidden'] = true;
        // the section roll-up adds up to the tour cost exactly, so it is a total by another name
        if (!empty($cost['sections']) && is_array($cost['sections'])) {
            foreach ($cost['sections'] as $k => $v) {
                $cost['sections'][$k] = null;
            }
            $cost['sections_hidden'] = true;
        }
        // the three roll-ups are the tour cost in three pieces: they go with it
        if (!empty($cost['parts']) && is_array($cost['parts'])) {
            foreach ($cost['parts'] as $k => $p) {
                $cost['parts'][$k]['usd'] = null;
                $cost['parts'][$k]['sections'] = [];
                $cost['parts'][$k]['hidden'] = true;
            }
        }
        // the pax matrix (schema 31): the price by party size for the desk — no cost, no profit on any row
        if (array_key_exists('pax_matrix', $cost)) {
            $cost['pax_matrix'] = PaxMatrix::forStaff($cost['pax_matrix']);
            if (!empty($cost['pax_matrix']['levels']) && is_array($cost['pax_matrix']['levels'])) {
                foreach ($cost['pax_matrix']['levels'] as $i => $L) {
                    foreach (['cost_usd', 'cost_pp_usd'] as $k) {
                        if (array_key_exists($k, $L)) {
                            $cost['pax_matrix']['levels'][$i][$k] = null;
                        }
                    }
                    $cost['pax_matrix']['levels'][$i]['parts'] = [];
                    $cost['pax_matrix']['levels'][$i]['sections'] = [];
                }
                $cost['pax_matrix']['cost_hidden'] = true;
            }
            // the check line quotes the matrix total and the costing total: it does not go to this desk
            if (array_key_exists('check', (array) $cost['pax_matrix'])) {
                $cost['pax_matrix']['check'] = null;
                $cost['pax_matrix']['check_hidden'] = true;
            }
        }
        // a blocker that quotes the tour cost is said without the figures
        if (!empty($cost['blockers']) && is_array($cost['blockers'])) {
            foreach ($cost['blockers'] as $i => $b) {
                if (is_array($b) && isset($b['label']) && preg_match('/\bUSD\s[\d,]/', (string) $b['label'])
                    && in_array((string) ($b['code'] ?? ''), ['pax_matrix_check'], true)) {
                    $cost['blockers'][$i]['label'] = 'The price by party size and the detailed costing do not agree. Management checks this under Pricing before a quotation can go out.';
                }
            }
        }
        return $cost;
    }

    /**
     * tf-mkguard-r1 (2 Nov 2026) — the markup privacy guard.
     *
     * costForViewer() filters a costing, but the inquiry row itself is read with SELECT * and returned by
     * many endpoints, so tf_intake.markup_pct / markup_by / markup_at / markup_note travelled to anyone on
     * the desk in the JSON even though no screen drew them. Hiding a field is not withholding it.
     *
     * Every JSON answer from this controller now passes through here, and for a sign-in that does not hold
     * Pricing the internal figures are removed from the payload at any depth. The gate is the privilege,
     * checked on the server, not a flag the page sends.
     */
    private const INTERNAL_FIGURES = [
        'markup_pct', 'markup_by', 'markup_at', 'markup_note',      // the inquiry's own markup (tf_intake)
        'quote_markup_pct',                                          // the agent's default markup (customers)
        'markup_default_pct',                                        // the company default (tf_meta)
        'profit_usd', 'remaining_profit', 'markup_source',           // the profit, by any of its names
    ];

    private function scrubInternal($value)
    {
        if (!is_array($value)) {
            return $value;
        }
        foreach ($value as $k => $v) {
            if (is_string($k) && in_array($k, self::INTERNAL_FIGURES, true)) {
                $value[$k] = null;
                continue;
            }
            $value[$k] = $this->scrubInternal($v);
        }
        return $value;
    }

    private function json(array $payload, int $code = 200)
    {
        if (!$this->holds(self::PRICING_URL)) {
            $payload = $this->scrubInternal($payload);
        }
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    private function body(): array
    {
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : ($this->request->getPost() ?: []);
    }

    private function fail(\Throwable $e, int $status = 500)
    {
        return $this->json(['status' => $status, 'message' => $e->getMessage()]);
    }

    // GET tour/intake
    public function index()
    {
        // tf-guided-r1 (2 Nov 2026): the Tour Desk menu opens the Guided Inquiry panel. The classic panel
        // is unchanged and one click away at tour/intake?classic=1 — nothing here was removed.
        // tf-inquiry-r2 (14 Nov 2026, Shantha): Tour Desk → Tour Inquiry opens the redesigned inquiry page (this panel)
        // directly, for new and existing inquiries. The Guided panel is kept and reachable at tour/intake/guided, or
        // with ?guided=1 here; nothing was removed.
        if ((string) $this->request->getGet('guided') !== '') {
            $q = $this->request->getGet('id');
            return redirect()->to(base_url(self::URL . '/guided' . ($q !== null && $q !== '' ? '?id=' . (int) $q : '')));
        }

        $data = $this->menuData();
        $data['user']         = $this->user();
        $data['is_sysadmin']  = $this->isSysAdmin();
        $data['intake_url']   = self::URL;
        $data['panel_url']    = 'master_files/hotel_rate_v2';
        $data['work_url']     = 'master_files/hotel_work';
        $data['installed']    = false;
        $data['ai_ready']     = false;
        $data['reader_label'] = 'Claude (Anthropic)';
        $data['reader_key']   = 'ANTHROPIC_API_KEY';
        $data['reader_model'] = '';
        $data['counts']       = ['new' => 0, 'working' => 0, 'converted' => 0, 'declined' => 0];
        $data['fields']       = EmailExtractor::FIELDS;
        $data['open_id']      = (int) $this->request->getGet('id');
        // what this person may see and do on top of the desk's own work: the markup
        // (Management), typing kilometres over the history (Management), the excursion master
        $data['sees_pricing']   = $this->holds(self::PRICING_URL);
        $data['is_super']       = $this->isSysAdmin();   // tf-ratejump-r1: Edit hotel (the Hotel Rates master from a night) is the Super Admin's
        $data['can_mileage']    = $this->holds(self::MILEAGE_URL);
        $data['can_issue']      = $this->holds(\App\Libraries\TourFlow\StaffTaskMap::ISSUE_URL);   // tf-panel2-r1
        // tf-walkfix-r1: the desks and the Board are linked only for a sign-in that may open them — a link that
        // answers Forbidden is no link
        $data['can_hotel']      = $this->holds(TourDeskController::HOTEL);
        $data['can_transport']  = $this->holds(TourDeskController::TRANSPORT);
        $data['can_board']      = $this->holds(TourDeskController::BOARD);
        $data['pricing_url']    = self::PRICING_URL;
        $data['excursions_url'] = self::EXC_URL;
        $data['sees_excursions'] = $this->holds(self::EXC_URL);
        // the crew bata is maintained under Master Files → Crew Bata (Admin) and only read here;
        // a holder gets a link to it beside the driver and guide sections, nobody gets an editor
        $data['crew_url']       = self::CREW_URL;
        $data['sees_crew']      = $this->holds(self::CREW_URL);
        // the photo library is kept under Master Files → Tour Photos; a holder gets a link to it beside the day pictures
        $data['photos_url']     = self::PHOTOS_URL;
        $data['sees_photos']    = $this->holds(self::PHOTOS_URL);
        // the two programme masters (schema 29): the day programme picks from both; a holder gets a link to each
        $data['free_url']       = self::FREE_URL;
        $data['sees_free']      = $this->holds(self::FREE_URL);
        $data['paid_url']       = self::PAID_URL;
        $data['sees_paid']      = $this->holds(self::PAID_URL);
        // schema 39: the Place to Visit / Entrance Fee Master — where a place's rate is set; a holder gets a link from a held line
        $data['master_url']     = \App\Libraries\TourFlow\EntranceMaster::URL;
        $data['sees_master']    = $this->holds(\App\Libraries\TourFlow\EntranceMaster::URL);
        // tf-stage9: the Masters column links only to the masters this sign-in holds (the same privileges as the doors)
        $data['masters_links'] = [
            'customers' => $this->holds('master_files/customer'),
            'hotel'     => $this->holds('master_files/hotel_master'),
            'rates'     => $this->holds('master_files/hotel_rate_v2'),
            'crew'      => $this->holds('master_files/crew_people'),
            'place'     => $this->holds(\App\Libraries\TourFlow\EntranceMaster::URL),
            'meal'      => $this->holds('master_files/meal_master'),
            'paid'      => $this->holds(self::PAID_URL),
            'blocks'    => (int) ($this->menuData()['user_role'] ?? 0) === 1,
        ];
        // schema 40: the Meal Master — where a meal is added or its rate changed; a holder gets a link from the Meals card
        $data['meal_url']       = MealMaster::URL;
        $data['sees_meals']     = $this->holds(MealMaster::URL);
        // the ⋯ Manage menus: reassigning, archiving, restoring — Management and the administrator
        $data['can_manage']     = $this->holds(self::MANAGE_URL);
        // tf-stage4: the Recalculate right; the System Administrator and Pricing (Management) holders act on any inquiry, others on their own
        $data['can_recalc']     = $this->holds(CostingGuard::URL);
        $data['recalc_any']     = (int) ($this->menuData()['user_role'] ?? 0) === 1 || $this->holds(self::PRICING_URL);
        $data['user_id']        = $this->userId();
        $data['manage_url']     = self::MANAGE_URL;
        $data['handlers']       = [];
        $data['user_id']        = $this->userId();
        // schema 33: Edit Request Authority is this person's own switch, turned on by Management —
        // not a screen privilege, and not part of the System Administrator's "everything"
        $data['edit_authority'] = false;
        try {
            $db = $this->db();
            $version = Installer::installedVersion($db);
            $data['installed'] = $version !== null && $version >= Installer::SCHEMA_VERSION;
            $data['version']   = $version;
            if ($data['installed']) {
                $data['counts']   = $this->svc()->counts();
                $data['handlers'] = Staff::handlers($db);
                $data['edit_authority'] = EditRequests::holds($db, $this->userId());
            }
            $reader = EmailExtractor::make($data['installed'] ? $db : null)->reader();
            $data['ai_ready']     = $reader->available();
            $data['reader_label'] = $reader->label();
            $data['reader_model'] = $reader->model();
            $data['reader_key']   = $reader->name() === 'openai' ? 'OPENAI_API_KEY' : 'ANTHROPIC_API_KEY';
        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }
        return view('pages/tour/intake', $data);
    }

    // POST setup — System Administrator: create or upgrade the tf_ tables
    public function setup()
    {
        if (!$this->isSysAdmin()) {
            return $this->json(['status' => 403, 'message' => 'Only the System Administrator installs the tables.']);
        }
        try {
            $res = Installer::install($this->db(), APPPATH . 'Database/Sql');
            return $this->json(['status' => 200, 'message' => implode(' ', $res['notes']), 'data' => $res]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET list?status=open|new|working|quoted|confirmed|declined|cancelled|archived|all&q=&handler=&from=&to=
    // Every filter combines with every other: a colleague's quoted inquiries for one customer arriving in March is one call.
    public function list()
    {
        try {
            $svc = $this->svc();
            $g   = fn (string $k) => (string) ($this->request->getGet($k) ?? '');
            return $this->json(['status' => 200, 'data' => [
                'items'  => $svc->list(['status' => $g('status') ?: 'open', 'q' => $g('q'), 'handler' => $g('handler'), 'from' => $g('from'), 'to' => $g('to')]),
                'counts' => $svc->counts(),
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET handlers — everyone who can be a handling agent, for the filter and the reassign box
    public function handlers()
    {
        try {
            return $this->json(['status' => 200, 'data' => Staff::handlers($this->db())]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 200, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    // GET intake/(:num)/activity — the tour's history: every edit, every move on the desks, who and when
    public function activity($id)
    {
        try {
            $db   = $this->db();
            $tour = null;
            try {
                $tour = $db->query('SELECT id FROM tf_tour WHERE intake_id = ?', [(int) $id])->getRowArray();
            } catch (\Throwable $e) {
                // no tour table yet
            }
            $rows = (new Activity($db, $this->user(), $this->userId(), $this->actorName()))->timeline((int) $id, $tour ? (int) $tour['id'] : null, 300);
            if (!$this->holds(self::PRICING_URL)) {
                // the markup's history is Management's, like the markup
                $rows = array_values(array_filter($rows, static fn ($r) => ($r['entity'] ?? '') !== 'pricing'));
            }
            return $this->json(['status' => 200, 'data' => $rows]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST intake {source_text, source_subject, source_from, source_received_at, read:true}
    // Saves the pasted email, then reads it. The email is kept whether the reading works or not.
    /**
     * tf-intake-r1 (26 Oct 2026, Shantha): the reader's failure in words staff can act on — never the raw answer.
     * The technical detail is kept after it, for whoever looks after the key.
     */
    public static function plainReadError(string $raw): string
    {
        $r = trim($raw);
        // written to writable/logs as well: until now a failed read left no trace outside the inquiry itself
        if (function_exists('log_message')) {
            log_message('error', 'Tour Inquiry reader failed: {msg}', ['msg' => mb_substr(preg_replace('/\s+/', ' ', $r), 0, 300)]);
        }
        if (preg_match('/answered 401|invalid x-api-key|authentication/i', $r)) {
            $why = 'The reading service did not accept this server\'s key (ANTHROPIC_API_KEY in .env is missing, expired or wrong). Ask whoever looks after the key to renew it.';
        } elseif (preg_match('/answered (429|529|503)|overloaded|rate.?limit/i', $r)) {
            $why = 'The reading service is busy just now. Try again in a minute.';
        } elseif (preg_match('/Could not reach|timed out|timeout/i', $r)) {
            $why = 'The reading service could not be reached from this server, or took too long. Try again; if it keeps happening, tell IT.';
        } elseif (preg_match('/answered 4\d\d/i', $r)) {
            $why = 'The reading service refused this request.';
        } else {
            $why = 'The reader failed.';
        }
        return $why . ' (detail: ' . mb_substr(preg_replace('/\s+/', ' ', $r), 0, 160) . ')';
    }

    /**
     * tf-intake-r1: the same email pressed twice (a double click, a second button while the first is still
     * reading, a refresh) must not make a second inquiry. The same person, the same text, within 15 minutes:
     * the inquiry already saved is returned instead. Nothing is deleted or merged.
     */
    private function recentDuplicate(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }
        $db   = $this->db();
        $rows = $db->query(
            'SELECT id, inquiry_no, source_text, created_at FROM tf_intake WHERE created_by = ? AND created_at >= ? AND source_text IS NOT NULL ORDER BY id DESC LIMIT 5',
            [$this->user(), date('Y-m-d H:i:s', time() - 15 * 60)]
        )->getResultArray();
        foreach ($rows as $r) {
            if (trim((string) $r['source_text']) === $text) {
                return $r;
            }
        }
        return null;
    }

    public function create()
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();

            // Started from the by-hand form: the record and everything typed into it are
            // created together, so a number is used only when an inquiry really is saved.
            if (!empty($b['fields']) && is_array($b['fields'])) {
                $item = $svc->createManual($b['fields'], (int) ($b['make_nights'] ?? 0), (string) ($b['agent_acc'] ?? ''));
                $n    = count($item['days']);
                return $this->json([
                    'status'  => 200,
                    'message' => $item['inquiry_no'] . ' created' . ($n ? " with {$n} blank night(s) to fill in." : '. Add the nights on this screen.'),
                    'data'    => $item,
                ]);
            }

            // tf-intake-r1: the same email saved moments ago by the same person is opened, not saved again
            $dup = $this->recentDuplicate((string) ($b['source_text'] ?? ''));
            if ($dup) {
                $item = $svc->get((int) $dup['id']);
                return $this->json(['status' => 200, 'message' => 'This email was already saved as ' . $dup['inquiry_no'] . ' a moment ago — opened that one instead of saving it twice.', 'duplicate' => true, 'saved' => ['id' => (int) $dup['id'], 'inquiry_no' => $dup['inquiry_no'], 'read' => empty($item['extract_error'])], 'data' => $item]);
            }

            $id = $svc->create($b);
            $saved = ['id' => $id, 'inquiry_no' => null, 'read' => null];

            if (!empty($b['read'])) {
                try {
                    $ex = EmailExtractor::make($this->db())->extract((string) $b['source_text'], $b['source_received_at'] ?? null);
                    $item = $svc->applyExtraction($id, $ex);
                    $msg  = 'Saved as ' . $item['inquiry_no'] . '. Read ' . count($item['days']) . ' night(s), ' . count($item['passengers']) . ' name(s) and ' . count($item['requests']) . ' request(s).';
                    if (!empty($ex['notes'])) {
                        $msg .= ' ' . implode(' ', $ex['notes']);
                    }
                    $saved['inquiry_no'] = $item['inquiry_no']; $saved['read'] = true;
                    return $this->json(['status' => 200, 'message' => $msg, 'saved' => $saved, 'data' => $item]);
                } catch (\Throwable $e) {
                    $plain = self::plainReadError($e->getMessage());
                    $svc->recordExtractionFailure($id, $plain);
                    $item = $svc->get($id);
                    $saved['inquiry_no'] = $item['inquiry_no']; $saved['read'] = false;
                    return $this->json(['status' => 200, 'message' => 'Saved as ' . $item['inquiry_no'] . ', but the email could not be read automatically. ' . $plain . ' The email is kept — fill the fields in by hand, or try reading again later.', 'saved' => $saved, 'data' => $item]);
                }
            }
            $item = $svc->get($id);
            $saved['inquiry_no'] = $item['inquiry_no']; $saved['read'] = null;
            return $this->json(['status' => 200, 'message' => 'Saved as ' . $item['inquiry_no'] . ' without reading. The email is kept with the inquiry.', 'saved' => $saved, 'data' => $item]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    /**
     * tf-intake-r1: GET reader_check — does each AI key the system holds still work? One small call per
     * provider (the model list), and only the outcome comes back: HTTP code, the provider's error type and a
     * fingerprint (length + 8 hex of a hash) so a replaced key can be told from the old one. The key itself is
     * never returned, logged or shown; any key-like string in a provider message is masked.
     */
    public function readerCheck()
    {
        $mask = static fn (string $t): string => preg_replace('/\b(sk-[A-Za-z0-9_-]{4})[A-Za-z0-9_*-]{8,}/', '$1…', $t);
        $call = static function (string $url, array $headers): array {
            $ch = curl_init();
            curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2]);
            $ca = \App\Libraries\HotelRateExtractor::caBundle();
            if ($ca !== null) { curl_setopt($ch, CURLOPT_CAINFO, $ca); }
            $raw = curl_exec($ch);
            if (curl_errno($ch)) { $e = curl_error($ch); curl_close($ch); return ['http' => 0, 'error_type' => 'network', 'message' => 'Could not reach the service: ' . $e]; }
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            $j = json_decode((string) $raw, true);
            return ['http' => $code, 'error_type' => $j['error']['type'] ?? ($j['error']['code'] ?? null), 'message' => isset($j['error']['message']) ? mb_substr((string) $j['error']['message'], 0, 200) : ($code === 200 ? 'OK' : 'no detail'), 'models' => $code === 200 && isset($j['data']) ? count((array) $j['data']) : null];
        };
        $fp = static fn (string $k): array => ['configured' => $k !== '', 'length' => strlen($k), 'fingerprint' => $k === '' ? null : substr(hash('sha256', $k), 0, 8)];
        $out = [];
        try {
            $a = config('Anthropic');
            $k = (string) ($a->apiKey ?? '');
            $out['anthropic'] = ['label' => 'Anthropic (Tour Inquiry reader, Hotel Rates reader)', 'model' => (string) ($a->model ?? ''), 'env_key' => 'ANTHROPIC_API_KEY'] + $fp($k)
                + ($k === '' ? ['http' => null, 'error_type' => 'not_configured', 'message' => 'No key in .env'] : $call('https://api.anthropic.com/v1/models?limit=5', ['x-api-key: ' . $k, 'anthropic-version: ' . (($a->version ?? '') ?: '2023-06-01')]));
        } catch (\Throwable $e) { $out['anthropic'] = ['error_type' => 'exception', 'message' => $e->getMessage()]; }
        try {
            $o = new \Config\OpenAI();
            $k = (string) ($o->apiKey ?? '');
            $out['openai'] = ['label' => 'OpenAI (Itinerary Formatter)', 'model' => (string) ($o->model ?? ''), 'env_key' => 'OPENAI_API_KEY'] + $fp($k)
                + ($k === '' ? ['http' => null, 'error_type' => 'not_configured', 'message' => 'No key in .env'] : $call('https://api.openai.com/v1/models', ['Authorization: Bearer ' . $k]));
        } catch (\Throwable $e) { $out['openai'] = ['error_type' => 'exception', 'message' => $e->getMessage()]; }
        foreach ($out as &$r) { if (isset($r['message'])) { $r['message'] = $mask((string) $r['message']); } $r['ok'] = (($r['http'] ?? 0) === 200); }
        unset($r);
        return $this->json(['status' => 200, 'checked_at' => date('Y-m-d H:i:s'), 'data' => $out]);
    }

    // GET intake/(:num)
    public function show($id)
    {
        try {
            $svc = $this->svc();
            // tf-rate-refresh-r1: nights left without a rate are checked again when a plan was approved since
            $refreshed = $svc->refreshUnpricedNights((int) $id);
            // tf-stage4d: frozen nights compared with the panel now (a corrected contract shows at once; nothing moves)
            $svc->scanHotelDrift((int) $id);
            $item = $svc->get((int) $id);
            if (!$item) {
                return $this->json(['status' => 404, 'message' => 'Inquiry not found.'], 404);
            }
            if ($refreshed) {
                $item['rates_refreshed'] = $refreshed;
            }
            return $this->json(['status' => 200, 'data' => $item]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST intake/(:num)/read — read (or re-read) the stored email
    public function read($id)
    {
        try {
            $svc  = $this->svc();
            $item = $svc->get((int) $id);
            if (!$item) {
                return $this->json(['status' => 404, 'message' => 'Inquiry not found.'], 404);
            }
            // schema 33: switched off once an Edit Request is applied — refused before the email is read
            $blocked = EditRequests::readBlocked($this->db(), (int) $id);
            if ($blocked !== null) {
                return $this->json(['status' => 409, 'message' => $blocked, 'data' => $item]);
            }
            $ex   = EmailExtractor::make($this->db())->extract((string) $item['source_text'], $item['source_received_at'] ?? null);
            $item = $svc->applyExtraction((int) $id, $ex);
            $msg  = 'Read again. Anything you had corrected by hand was kept.';
            if (!empty($ex['notes'])) {
                $msg .= ' ' . implode(' ', $ex['notes']);
            }
            return $this->json(['status' => 200, 'message' => $msg, 'data' => $item]);
        } catch (\Throwable $e) {
            $plain = self::plainReadError($e->getMessage());   // tf-intake-r1
            $this->svc()->recordExtractionFailure((int) $id, $plain);
            return $this->json(['status' => 400, 'message' => 'The email could not be read. ' . $plain . ' Nothing you entered was changed.', 'data' => $this->svc()->get((int) $id)]);
        }
    }

    // POST check_text {source_text, source_received_at} — the review step's date check, before anything
    // reads the email: every date mention, which carry a year, and what is wrong. By rule, no model.
    public function checkText()
    {
        try {
            $b = $this->body();
            $r = TextChecks::scan((string) ($b['source_text'] ?? ''), $b['source_received_at'] ?? null);
            $dates = array_map(static fn (array $d) => ['text' => $d['text'], 'year' => $d['year'], 'iso' => $d['iso'], 'line' => $d['line']], $r['travel']);
            return $this->json(['status' => 200, 'data' => ['flags' => $r['flags'], 'dates' => $dates, 'year_stated' => $r['year_stated']]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/email_edit {source_text, note, read} — correct the working copy of the
    // email (the original stays as pasted), then read it again if asked
    public function emailEdit($id)
    {
        try {
            $b    = $this->body();
            $svc  = $this->svc();
            if (!empty($b['read'])) {
                // schema 33: reading again is switched off once an Edit Request is applied; nothing is saved
                $blocked = EditRequests::readBlocked($this->db(), (int) $id);
                if ($blocked !== null) {
                    return $this->json(['status' => 409, 'message' => $blocked . ' Nothing was saved.']);
                }
            }
            $item = $svc->editEmail((int) $id, (string) ($b['source_text'] ?? ''), isset($b['note']) ? (string) $b['note'] : null);
            if (empty($b['read'])) {
                return $this->json(['status' => 200, 'message' => 'Email text corrected. The original is kept as pasted; press Read the email again to read the corrected text.', 'data' => $item]);
            }
            try {
                $ex   = EmailExtractor::make($this->db())->extract((string) $item['source_text'], $item['source_received_at'] ?? null);
                $item = $svc->applyExtraction((int) $id, $ex);
                $msg  = 'Corrected and read again. Anything you had corrected by hand was kept.';
                if (!empty($ex['notes'])) {
                    $msg .= ' ' . implode(' ', $ex['notes']);
                }
                return $this->json(['status' => 200, 'message' => $msg, 'data' => $item]);
            } catch (\Throwable $e) {
                $svc->recordExtractionFailure((int) $id, $e->getMessage());
                return $this->json(['status' => 200, 'message' => 'The correction is saved but the email could not be read: ' . $e->getMessage(), 'data' => $svc->get((int) $id)]);
            }
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // =====================================================================
    //  Edit Requests (schema 33): below the original email — what to change, and to what
    // =====================================================================

    private function edits(): EditRequests
    {
        return new EditRequests($this->db(), $this->svc());
    }

    /** The requests on an inquiry, whether this person may make and confirm one, and where the inquiry stands. */
    private function editPayload(int $id, ?array $item = null): array
    {
        $db   = $this->db();
        $item = $item ?? $this->svc()->get($id);
        $stand = EditRequests::standing($item ?? []);
        return [
            'requests'  => $this->edits()->listFor($id),
            'authority' => EditRequests::holds($db, $this->userId()),
            'standing'  => $stand['state'],
            'why'       => $stand['why'],
            'summary'   => $item['edit_requests'] ?? null,
        ];
    }

    private function editFail(\Throwable $e)
    {
        if ($e instanceof NoAuthority) {
            return $this->json(['status' => 403, 'message' => $e->getMessage(), 'no_authority' => true]);
        }
        return $this->fail($e, 400);
    }

    // GET intake/(:num)/edit_requests
    public function editRequests($id)
    {
        try {
            if (!EditRequests::installed($this->db())) {
                return $this->json(['status' => 409, 'message' => 'Edit Requests need the schema 33 upgrade: the administrator opens Tour Inquiry once and installs it.']);
            }
            return $this->json(['status' => 200, 'data' => $this->editPayload((int) $id)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST intake/(:num)/edit_request {pasted, change_to, reason} — saved, and checked at once on an open inquiry
    public function editRequest($id)
    {
        try {
            $out = $this->edits()->submit((int) $id, $this->body());
            return $this->json(['status' => 200, 'message' => $out['message'], 'data' => $out + $this->editPayload((int) $id)]);
        } catch (\Throwable $e) {
            return $this->editFail($e);
        }
    }

    // POST intake/(:num)/edit_request/(:num)/(check|confirm|cancel) {answers} | {fingerprint} | {note}
    public function editRequestAction($id, $reqId, $action)
    {
        try {
            $b   = $this->body();
            $er  = $this->edits();
            if ($action === 'check') {
                $out = $er->check((int) $id, (int) $reqId, is_array($b['answers'] ?? null) ? $b['answers'] : []);
            } elseif ($action === 'confirm') {
                $out = $er->confirm((int) $id, (int) $reqId, isset($b['fingerprint']) ? (string) $b['fingerprint'] : null);
            } elseif ($action === 'cancel') {
                $out = $er->cancel((int) $id, (int) $reqId, isset($b['note']) ? (string) $b['note'] : null);
            } else {
                return $this->json(['status' => 404, 'message' => 'No such action on an Edit Request.']);
            }
            $item = $this->svc()->get((int) $id);
            $data = $out + $this->editPayload((int) $id, $item);
            if (!empty($out['applied'])) {
                $data['inquiry'] = $item;   // everything that followed, for the screen to draw again
            }
            return $this->json(['status' => 200, 'message' => $out['message'], 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->editFail($e);
        }
    }

    // POST intake/(:num)/field {key, value, note}
    public function field($id)
    {
        try {
            $b = $this->body();
            return $this->json(['status' => 200, 'message' => 'Saved.', 'data' => $this->svc()->saveField((int) $id, (string) ($b['key'] ?? ''), $b['value'] ?? null, $b['note'] ?? null)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/day/(:num) {area, hotel_name_raw, meal_plan, rooms, day_date, narrative}
    // POST intake/(:num)/day            — add a night by hand
    // POST intake/(:num)/day/(:num)     — correct one, or remove it
    public function day($id, $dayId = null)
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();
            if ($dayId === null) {
                return $this->json(['status' => 200, 'message' => 'Night added.', 'data' => $svc->addDay((int) $id, $b)]);
            }
            if (!empty($b['delete'])) {
                return $this->json(['status' => 200, 'message' => 'Night removed.', 'data' => $svc->deleteDay((int) $id, (int) $dayId)]);
            }
            return $this->json(['status' => 200, 'message' => 'Night updated.', 'data' => $svc->saveDay((int) $id, (int) $dayId, $b)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/day/(:num)/departure — a line on the departure day is the departure day (schema 32)
    public function departureDay($id, $dayId)
    {
        try {
            return $this->json(['status' => 200, 'message' => 'It is the departure day now: what was on that line is on the departure day, and the line is gone.', 'data' => $this->svc()->makeDepartureDay((int) $id, (int) $dayId)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/day/(:num)/extra_night — the party really stays that night: the departure moves a day later
    public function extraNight($id, $dayId)
    {
        try {
            return $this->json(['status' => 200, 'message' => 'Kept as a night: the departure date moved to the next day.', 'data' => $this->svc()->extraNight((int) $id, (int) $dayId)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/departure {text?, note?} — the departure day's own wording, and a note for the desks
    public function departure($id)
    {
        try {
            return $this->json(['status' => 200, 'message' => 'Departure day saved.', 'data' => $this->svc()->saveDeparture((int) $id, $this->body())]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/dates {action: keep|email} — when the email's dates and the inquiry's differ
    public function dates($id)
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();
            if (($b['action'] ?? '') === 'email') {
                return $this->json(['status' => 200, 'message' => 'The email\'s dates are on the inquiry now; the nights moved with them and every hotel was checked again.', 'data' => $svc->useEmailDates((int) $id)]);
            }
            return $this->json(['status' => 200, 'message' => 'These dates are confirmed. If either the inquiry\'s dates or the email\'s change, this is asked again.', 'data' => $svc->confirmDates((int) $id)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/nights_missing — a blank line for every night the dates give that has none
    public function nightsMissing($id)
    {
        try {
            return $this->json(['status' => 200, 'message' => 'The missing nights are on the itinerary, dated, for a hotel to be named on each.', 'data' => $this->svc()->addMissingNights((int) $id)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    /** tf-stage4: may this person recalculate (or keep) the saved figures of this inquiry? null when yes, else the reason. */
    private function recalcRefusal(int $id): ?string
    {
        $it = $this->db()->query('SELECT handler_id, handler_name FROM tf_intake WHERE id = ?', [$id])->getRowArray() ?: [];
        $may = CostingGuard::may($this->holds(CostingGuard::URL), (int) ($this->menuData()['user_role'] ?? 0) === 1 || $this->holds(self::PRICING_URL), $this->userId(), $it);
        return $may['ok'] ? null : $may['why'];
    }

    // POST intake/(:num)/recalculate {mode: take|keep, note} — tf-stage4: the deliberate, authorised, logged recalculation
    public function recalculate($id)
    {
        try {
            if (($why = $this->recalcRefusal((int) $id)) !== null) {
                return $this->json(['status' => 403, 'message' => $why], 403);
            }
            $b    = $this->body();
            $mode = ($b['mode'] ?? 'take') === 'keep' ? 'keep' : 'take';
            $r    = CostingGuard::run($this->svc(), $this->quotes(), (int) $id, $mode, isset($b['note']) ? (string) $b['note'] : null, $this->user(), $this->actorName());
            return $this->json(['status' => 200, 'message' => $r['message'], 'data' => $r['data'], 'log_id' => $r['log_id'], 'before' => $r['before'] ?? null, 'after' => $r['after'] ?? null]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET intake/(:num)/recalc_log — tf-stage4: the log of recalculations and reviews on this inquiry
    public function recalcLog($id)
    {
        return $this->json(['status' => 200, 'data' => CostingGuard::history($this->db(), (int) $id)]);
    }

    // POST intake/(:num)/day/(:num)/reprice — take the panel's current published rate onto a night on purpose
    public function reprice($id, $dayId)
    {
        try {
            // tf-stage4: one night's re-price is a recalculation too — the same right, the same log
            if (($why = $this->recalcRefusal((int) $id)) !== null) {
                return $this->json(['status' => 403, 'message' => $why], 403);
            }
            $was = $this->db()->query('SELECT day_no, hotel_name_raw, rate_currency, rate_sgl, rate_dbl, rate_tpl, rate_unit, rate_drift_json FROM tf_intake_day WHERE id = ? AND intake_id = ?', [(int) $dayId, (int) $id])->getRowArray();
            if ($was) {
                $dr = json_decode((string) ($was['rate_drift_json'] ?? ''), true) ?: [];
                $f  = static fn ($a) => implode(' / ', array_filter([$a['sgl'] ?? $a['rate_sgl'] ?? null, $a['dbl'] ?? $a['rate_dbl'] ?? null, $a['tpl'] ?? $a['rate_tpl'] ?? null, $a['unit'] ?? $a['rate_unit'] ?? null], static fn ($v) => $v !== null && $v !== '')) ?: 'no rate';
                CostingGuard::logManual($this->db(), (int) $id, 'Night ' . (int) $was['day_no'] . ' · ' . $was['hotel_name_raw'] . ' (Use the new rate)', $f($was), $dr ? $f($dr) : 'the panel\'s current rate', $this->user(), $this->actorName());
            }
            return $this->json(['status' => 200, 'message' => 'The night now carries the published rate as Hotel Rates gives it today.', 'data' => $this->svc()->repriceDay((int) $id, (int) $dayId)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/pax        — add a name
    // POST intake/(:num)/pax/(:num) — change or remove one
    public function pax($id, $paxId = null)
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();
            if ($paxId !== null && !empty($b['delete'])) {
                return $this->json(['status' => 200, 'message' => 'Name removed.', 'data' => $svc->deletePassenger((int) $id, (int) $paxId)]);
            }
            $data = $svc->savePassenger((int) $id, $paxId === null ? null : (int) $paxId, $b);
            return $this->json(['status' => 200, 'message' => $paxId ? 'Name updated.' : 'Name added.', 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/email — attach the agent's email to an inquiry started by hand
    public function email($id)
    {
        try {
            $b    = $this->body();
            $svc  = $this->svc();
            if (!empty($b['read'])) {
                // schema 33: reading is switched off once an Edit Request is applied; nothing is saved
                $blocked = EditRequests::readBlocked($this->db(), (int) $id);
                if ($blocked !== null) {
                    return $this->json(['status' => 409, 'message' => $blocked . ' Nothing was saved: press Attach it only to keep the email with the inquiry.']);
                }
            }
            $item = $svc->attachEmail((int) $id, $b);

            if (empty($b['read'])) {
                return $this->json(['status' => 200, 'message' => 'Email saved with this inquiry. Nothing you had entered was changed.', 'data' => $item]);
            }
            try {
                $ex   = EmailExtractor::make($this->db())->extract((string) $item['source_text'], $item['source_received_at'] ?? null);
                $item = $svc->applyExtraction((int) $id, $ex);
                $msg  = 'Read. Everything you had already entered was kept.';
                if (!empty($ex['notes'])) {
                    $msg .= ' ' . implode(' ', $ex['notes']);
                }
                return $this->json(['status' => 200, 'message' => $msg, 'data' => $item]);
            } catch (\Throwable $e) {
                // The email is already saved; a failed read must not lose it or stop the work.
                $svc->recordExtractionFailure((int) $id, $e->getMessage());
                return $this->json(['status' => 200, 'message' => 'The email is saved but could not be read: ' . $e->getMessage() . ' Carry on filling it in by hand.', 'data' => $svc->get((int) $id)]);
            }
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET masters — the lists the panel picks from: agents, areas and the hotels in them
    public function masters()
    {
        try {
            $svc   = $this->svc();
            $areas = $svc->areas();
            $byArea = [];
            foreach ($areas as $a) {
                $byArea[$a['id']] = $svc->hotelsIn((int) $a['id']);
            }
            // agents: the customers marked as agents (the whole ledger until one is marked);
            // customers: everyone, for the one that has to be picked although not marked yet
            $marked = $svc->agentsMarked();
            return $this->json(['status' => 200, 'data' => ['agents' => $svc->agents(), 'agents_marked' => $marked, 'customers' => $marked > 0 ? $svc->customers() : [], 'areas' => $areas, 'hotels' => $byArea]]);
        } catch (\Throwable $e) {
            // the panel still works with everything typed by hand, so a master that will
            // not read is an empty list and a note, never a broken screen
            return $this->json(['status' => 200, 'data' => ['agents' => [], 'areas' => [], 'hotels' => [], 'error' => $e->getMessage()]]);
        }
    }

    // GET programme_masters — what a day programme can be built from (schema 29): the free visits
    // (never costed) and the paid items (entrances and tickets from the priced list; activities and
    // excursions with their rate structure), each under its destination
    public function programmeMasters()
    {
        try {
            $m = $this->svc()->masters();
            // schema 39: a place of the Place to Visit / Entrance Fee Master is picked by its place (id 0), with the master's word for its rate
            $entrances = array_map(static fn ($e) => ['id' => $e['id'], 'name' => $e['name'], 'code' => $e['code'], 'destination' => $e['destination'], 'usd' => $e['usd'], 'rate_state' => $e['rate_state'], 'pay_mode' => $e['pay_mode']]
                + (!empty($e['master']) ? ['master' => true, 'place_id' => $e['place_id'], 'jeep' => (int) ($e['jeep'] ?? 0), 'rate_text' => $e['rate_text'] ?? ''] : []), $m->entrances());
            $activities = array_map(static fn ($a) => ['code' => $a['code'], 'name' => $a['name'], 'destination' => $a['location'], 'unit' => $a['unit'], 'rate' => $a['rate'], 'currency' => $a['currency'], 'includes_transport' => $a['includes_transport'], 'valid_from' => $a['valid_from'] ?? null, 'valid_to' => $a['valid_to'] ?? null], $m->activities());
            $free = array_map(static fn ($f) => ['id' => $f['id'], 'name' => $f['name'], 'destination' => $f['destination'], 'kind' => $f['kind'], 'kind_word' => $f['kind_word']], $m->freeVisits());
            return $this->json(['status' => 200, 'data' => [
                'available'    => $m->available(),
                'destinations' => $m->destinations(),
                'free'         => $free,
                'entrances'    => $entrances,
                'activities'   => $activities,
                'free_url'     => self::FREE_URL, 'sees_free' => $this->holds(self::FREE_URL),
                'paid_url'     => self::PAID_URL, 'sees_paid' => $this->holds(self::PAID_URL),
            ]]);
        } catch (\Throwable $e) {
            // the day programme still takes typed lines, so a master that will not read is an empty list and a note
            return $this->json(['status' => 200, 'data' => ['available' => false, 'destinations' => [], 'free' => [], 'entrances' => [], 'activities' => [], 'error' => $e->getMessage()]]);
        }
    }

    // GET crew — the driver and guide roles, with whatever day rate is set
    public function crew()
    {
        try {
            $svc = $this->svc();
            return $this->json(['status' => 200, 'data' => [
                'roles' => $svc->crewRoles(), 'fleet' => $svc->planner()->fleet(),
                'classes' => $svc->planner()->classes(), 'freelancers' => $svc->freelancers(),
            ]]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 200, 'data' => ['roles' => [], 'fleet' => [], 'classes' => [], 'error' => $e->getMessage()]]);
        }
    }

    // POST crew/rate — retired at schema 23. The bata is maintained under Master Files → Crew
    // Bata (Admin); the quotation screen reads it and never changes it. Kept as a refusal so
    // an old page or script gets a plain answer rather than a missing route.
    public function crewRate()
    {
        return $this->json(['status' => 403, 'message' => 'The bata is not changed from the quotation screen any more. Admin maintains it under Master Files → Crew Bata.'], 403);
    }

    // POST intake/(:num)/vehicle — override the vehicle, or hand it back to the planner
    public function vehicle($id)
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();
            if (!empty($b['auto'])) {
                return $this->json(['status' => 200, 'message' => 'Vehicle chosen from the passenger count again.', 'data' => $svc->autoVehicle((int) $id)]);
            }
            // the rate per kilometre on one line: a special rate for this job, or back to the master's —
            // a pricing decision, so the Pricing privilege (Management)
            if (!empty($b['line_id'])) {
                if (!$this->holds(self::PRICING_URL)) {
                    return $this->json(['status' => 403, 'message' => 'A special rate per kilometre is a pricing decision for Management (the Pricing privilege). The master rate stays.'], 403);
                }
                $special = array_key_exists('rate', $b) && $b['rate'] !== null && $b['rate'] !== '';
                $data = $svc->saveVehicleRate((int) $id, (int) $b['line_id'], $special ? $b['rate'] : null, $b['note'] ?? null);
                return $this->json(['status' => 200, 'message' => $special ? 'Special rate set for this inquiry only. The master rate stays shown beside it.' : 'Back on the master rate.', 'data' => $data]);
            }
            if (!empty($b['reprice'])) {
                if (!$this->holds(self::PRICING_URL)) {
                    return $this->json(['status' => 403, 'message' => 'Re-pricing from today\'s master rates is for Management (the Pricing privilege).'], 403);
                }
                return $this->json(['status' => 200, 'message' => 'Re-priced from today\'s master rates.', 'data' => $svc->repriceVehicle((int) $id)]);
            }
            return $this->json(['status' => 200, 'message' => 'Vehicle set.', 'data' => $svc->saveVehicle((int) $id, (array) ($b['rows'] ?? []))]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/crew — add, change or drop a driver or guide
    //   {code, days, supplier_acc, note}                — add the role, or change its days / person
    //   {change_from, code, days, supplier_acc, note}   — move a line to another type or language of the same kind
    //   {code, delete:1}                                — take the line off
    //   {auto:1}                                        — driver and guide from the agent's market again
    // The bata never travels in the body: it is the master's for the role (Master Files → Crew Bata).
    // POST intake/(:num)/transfers {arrival_by?, departure_by?, pickup_fee?, drop_fee?} — tf-transfer-r1
    public function transfers($id)
    {
        try {
            return $this->json(['status' => 200, 'message' => 'Airport transfers saved. The route and the costing follow.', 'data' => $this->svc()->saveTransfers((int) $id, $this->body())]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET transfer_hotels?q= — tf-transfer-r2: hotels of the master whose name holds what was typed
    public function transferHotels()
    {
        return $this->json(['status' => 200, 'data' => \App\Libraries\TourFlow\HotelTransfers::search($this->db(), (string) ($this->request->getGet('q') ?? ''))]);
    }

    public function intakeCrew($id)
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();
            if (!empty($b['auto'])) {
                return $this->json(['status' => 200, 'message' => 'Driver and guide chosen from the agent\'s market again.', 'data' => $svc->autoCrew((int) $id)]);
            }
            if (!empty($b['change_from'])) {
                $data = $svc->changeCrew((int) $id, (string) $b['change_from'], (string) ($b['code'] ?? ''), $b);
                return $this->json(['status' => 200, 'message' => 'Changed. The bata per day is the master\'s for the new type and language.', 'data' => $data]);
            }
            $msg = !empty($b['delete']) ? 'Line removed.'
                : (array_key_exists('supplier_acc', $b) && trim((string) $b['supplier_acc']) !== '' ? 'Engaged. The bata per day stays the master\'s for the role; their own agreed figure is kept for the payment side.' : 'Saved.');
            return $this->json(['status' => 200, 'message' => $msg, 'data' => $svc->saveCrew((int) $id, (string) ($b['code'] ?? ''), $b)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/sector          — add a leg by hand
    // POST intake/(:num)/sector/(:num)   — correct one, or remove it
    // POST intake/(:num)/sector {auto:1} — throw away every hand-typed leg and read the
    //                                      whole itinerary's driving from the history again
    public function sector($id, $sectorId = null)
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();
            if (!empty($b['auto'])) {
                return $this->json(['status' => 200, 'message' => 'Mileage read from the driving history again.', 'data' => $svc->autoSectors((int) $id)]);
            }
            if ($sectorId !== null && !empty($b['delete'])) {
                return $this->json(['status' => 200, 'message' => 'Leg taken off the mileage. The totals and the costing follow.', 'data' => $svc->deleteSector((int) $id, (int) $sectorId)]);
            }
            if ($sectorId !== null && !empty($b['restore'])) {
                return $this->json(['status' => 200, 'message' => 'Leg put back on the mileage.', 'data' => $svc->restoreSector((int) $id, (int) $sectorId)]);
            }
            // Mileage is editable with permission: typing a distance over what the history says
            // is the "Mileage override" privilege (Management by default). Anyone on the desk may
            // still add or move a leg and let the history price it.
            $typed = (array_key_exists('km', $b) && $b['km'] !== null && $b['km'] !== '') || (array_key_exists('extra_km', $b) && $b['extra_km'] !== null && $b['extra_km'] !== '');
            if ($typed && !$this->holds(self::MILEAGE_URL)) {
                return $this->json(['status' => 403, 'message' => 'Typing kilometres over the driving history needs the Mileage override privilege. Leave the distance blank and it is looked up, or ask Management to set it.']);
            }
            $data = $svc->saveSector((int) $id, $sectorId === null ? null : (int) $sectorId, $b);
            return $this->json(['status' => 200, 'message' => $sectorId ? 'Leg updated.' : 'Leg added.', 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/sector/(:num)/actual {km, note} — the actual mileage of a leg, typed by hand
    // Into the actual column beside Google's figure, never over it. Typing kilometres is the
    // Mileage override privilege, as for any figure typed over what the record says.
    public function actual($id, $sectorId)
    {
        try {
            $b = $this->body();
            if (!$this->holds(self::MILEAGE_URL)) {
                return $this->json(['status' => 403, 'message' => 'Typing an actual mileage needs the Mileage override privilege. Ask Management to set it, or enter it on the Transport desk after the tour.']);
            }
            $data = $this->svc()->saveActual((int) $id, (int) $sectorId, $b['km'] ?? null, $b['note'] ?? null);
            $typed = isset($b['km']) && $b['km'] !== '' && $b['km'] !== null;
            return $this->json(['status' => 200, 'message' => $typed ? 'Actual mileage kept beside Google\'s figure.' : 'Typed actual cleared; the record\'s figure is back.', 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/sector/(:num)/extra {km, note} — the extra running for site visits and
    // city tours on that leg. Its own column; the driven figures are never touched. Blank puts
    // the automatic figure back (the town's allowance, or what the drivers logged).
    public function extra($id, $sectorId)
    {
        try {
            $b = $this->body();
            if (!$this->holds(self::MILEAGE_URL)) {
                return $this->json(['status' => 403, 'message' => 'Changing the extra running needs the Mileage override privilege. Ask Management to set it.']);
            }
            $data  = $this->svc()->saveExtra((int) $id, (int) $sectorId, $b['km'] ?? null, $b['note'] ?? null);
            $typed = isset($b['km']) && $b['km'] !== '' && $b['km'] !== null;
            return $this->json(['status' => 200, 'message' => $typed ? 'Extra running saved. The charged kilometres follow.' : 'Back to the automatic figure for this leg.', 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/google              {refresh?} — ask Google about every driven leg
    // POST intake/(:num)/sector/(:num)/google {refresh?} — about one leg
    // Google's answer goes beside each leg; a distance typed by hand is never replaced.
    public function google($id, $sectorId = null)
    {
        try {
            $b    = $this->body();
            $data = $this->svc()->googleLegs((int) $id, $sectorId === null ? null : (int) $sectorId, !empty($b['refresh']));
            $mil  = $data['mileage'] ?? [];
            $msg  = 'Google asked. ' . (int) ($mil['quoted_google'] ?? 0) . ' leg(s) are quoted at its road distance'
                . ((int) ($mil['google_missing'] ?? 0) > 0 ? '; ' . (int) $mil['google_missing'] . ' it has no figure for — see the Google column.' : '.');
            return $this->json(['status' => 200, 'message' => $msg, 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/accom — say whether a driver or guide room is needed, and correct it
    public function accom($id)
    {
        try {
            $b = $this->body();
            $data = $this->svc()->saveAccom((int) $id, (string) ($b['kind'] ?? ''), $b);
            return $this->json(['status' => 200, 'message' => 'Accommodation updated.', 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/agent — take a registered agent onto this inquiry
    public function agent($id)
    {
        try {
            $b = $this->body();
            return $this->json(['status' => 200, 'message' => 'Agent taken from the ledger.', 'data' => $this->svc()->applyAgent((int) $id, (string) ($b['acc'] ?? ''))]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET services — the priced list, for the type-ahead when adding a day service
    public function feelist()
    {
        try {
            $look = $this->svc()->services();
            return $this->json(['status' => 200, 'data' => ['available' => $look->available(), 'items' => $look->all()]]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 200, 'data' => ['available' => false, 'items' => []]]);
        }
    }

    // POST intake/(:num)/service            — add one day service
    // POST intake/(:num)/service/(:num)     — change one
    public function service($id, $serviceId = null)
    {
        try {
            $body = $this->body();
            $svc  = $this->svc();
            if (($body['delete'] ?? '') && $serviceId) {
                return $this->json(['status' => 200, 'message' => 'Service removed.', 'data' => $svc->deleteService((int) $id, (int) $serviceId)]);
            }
            $data = $svc->saveService((int) $id, $serviceId === null ? null : (int) $serviceId, $body);
            // a line merged into one already on the day, or a quantity capped at the party: said, not hidden
            return $this->json(['status' => 200, 'message' => $svc->serviceNote ?: ($serviceId ? 'Service updated.' : 'Service added.'), 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET intake/(:num)/meals/options?day=N&type=lunch|dinner — schema 40: what the Meal Master offers for a day of the tour:
    // the meals that suit the day's route and where the party sleeps, every other meal that may be booked that day, the party
    public function mealOptions($id)
    {
        try {
            $svc = $this->svc();
            $it  = $svc->get((int) $id);
            if (!$it) {
                return $this->json(['status' => 404, 'message' => 'Inquiry not found.'], 404);
            }
            $o = $svc->meals()->options($it, (int) $this->request->getGet('day'), (string) $this->request->getGet('type'));
            // a new restaurant goes into the Meal Master: offered to a holder of the Meal Master only
            $o['can_add'] = $this->holds(MealMaster::URL);   // and "save the booking contact on the Meal Master"
            if ($o['can_add']) {
                $o['suppliers'] = $svc->meals()->suppliers();
            }
            return $this->json(['status' => 200, 'data' => $o]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/meal[/(:num)] {day_no, meal_type, meal_id, qty_adult, qty_child, meal_time, special_note, confirm_outside, outside_reason} —
    // schema 40: a meal of the Meal Master on a day (or a lunch / dinner line taken onto it, or changed); {delete:1} removes the line.
    // A meal the hotel's plan gives that night answers 409 with what can be done (switch the HB meal, or confirm it outside with a reason).
    public function meal($id, $serviceId = null)
    {
        try {
            $body = $this->body();
            $svc  = $this->svc();
            if (($body['delete'] ?? '') && $serviceId) {
                return $this->json(['status' => 200, 'message' => 'Meal removed.', 'data' => $svc->deleteService((int) $id, (int) $serviceId)]);
            }
            // "save on the Meal Master" is for a holder of the Meal Master only
            $body['save_master'] = !empty($body['save_master']) && $this->holds(MealMaster::URL) ? 1 : 0;
            $data = $svc->saveMeal((int) $id, $serviceId === null ? null : (int) $serviceId, $body);
            return $this->json(['status' => 200, 'message' => $svc->mealNote ?: 'Meal saved.', 'data' => $data]);
        } catch (MealConflict $e) {
            return $this->json(['status' => 409, 'message' => $e->getMessage(), 'conflict' => 'hotel_gives', 'can_switch' => $e->canSwitch]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/meal_new {day_no, meal_type, restaurant, location, adult_rate, child_rate, currency, tax_pct, tax_included, supplier_id,
    // code, cuisine, description, contact_name, contact_phone, address, route_from, route_to, valid_to, qty_adult, qty_child, service_id} —
    // schema 40: "+ Add a new restaurant" at a day's lunch place: into the Meal Master, and onto the day. A holder of the Meal Master only.
    public function mealNew($id)
    {
        try {
            if (!$this->holds(MealMaster::URL)) {
                return $this->json(['status' => 403, 'message' => 'A new restaurant goes into the Meal Master, which you do not hold. Pick one from the list, or ask a Meal Master holder to add it.'], 403);
            }
            $svc  = $this->svc();
            $data = $svc->quickMeal((int) $id, $this->body());
            return $this->json(['status' => 200, 'message' => $svc->mealNote ?: 'Restaurant added.', 'data' => $data]);
        } catch (MealConflict $e) {
            return $this->json(['status' => 409, 'message' => $e->getMessage(), 'conflict' => 'hotel_gives', 'can_switch' => $e->canSwitch]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/meals/fill — schema 40: "Pick lunch places": the system picks the lunch place of every day from the route now
    // (the route first, then the cheapest; a person's picks and removals stand)
    public function mealsFill($id)
    {
        try {
            $svc  = $this->svc();
            $data = $svc->fillLunches((int) $id);
            $n = count($svc->lunchNotes);
            return $this->json(['status' => 200, 'message' => $n ? implode(' ', $svc->lunchNotes) : 'Every day already has its lunch place, or nothing in the Meal Master suits the route.', 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/day/(:num)/hb_meal {hb_meal: lunch | dinner | ''} — schema 40: on a Half Board night, the meal the hotel gives besides breakfast
    public function hbMeal($id, $dayId)
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();
            $v   = trim((string) ($b['hb_meal'] ?? ''));
            $data = $svc->setHbMeal((int) $id, (int) $dayId, $v === '' ? null : $v);
            return $this->json(['status' => 200, 'message' => $svc->mealNote ?: 'Saved.', 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/free_visit {day_no, free_visit_id} — a free visit from the master on a day: on the itinerary, never costed
    public function freeVisit($id)
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();
            $data = $svc->addFreeVisit((int) $id, (int) ($b['day_no'] ?? 0), (int) ($b['free_visit_id'] ?? 0));
            return $this->json(['status' => 200, 'message' => $svc->serviceNote ?: 'Free visit added.', 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/recheck — ask the Hotel Rates panel about every named hotel again
    public function recheck($id)
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();
            $svc->assertOpen((int) $id);
            $svc->resolveAgent((int) $id);
            $svc->syncNights((int) $id);
            $dated = $svc->dateNights((int) $id);
            $svc->planCrew((int) $id);
            $svc->planVehicle((int) $id);
            // {take_new_rates: true} — every night the panel has moved away from takes the panel's figure (each one a line in the history)
            // tf-stage4: taking the new rates is a recalculation — the same right, the same log
            if (!empty($b['take_new_rates'])) {
                if (($why = $this->recalcRefusal((int) $id)) !== null) {
                    return $this->json(['status' => 403, 'message' => $why], 403);
                }
                $n = (int) ($this->db()->query('SELECT COUNT(*) n FROM tf_intake_day WHERE intake_id = ? AND rate_drift_json IS NOT NULL', [(int) $id])->getRowArray()['n'] ?? 0);
                CostingGuard::logManual($this->db(), (int) $id, 'Use all new rates: ' . $n . ' night(s)', 'the saved rates', 'the panel\'s current rates', $this->user(), $this->actorName());
            }
            $svc->checkHotels((int) $id, null, ['take_new' => !empty($b['take_new_rates'])]);
            $svc->checkServices((int) $id);
            $svc->planSectors((int) $id);
            $svc->planAccom((int) $id);
            return $this->json(['status' => 200, 'message' => 'Hotels checked against the panel again.' . ($dated ? " {$dated} night(s) were dated from the arrival date first." : ''), 'data' => $svc->get((int) $id)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/request/(:num) {status, answer}
    // POST intake/(:num)/request            — add one by hand
    // POST intake/(:num)/request/(:num)     — answer it, retitle it, or remove it
    public function request($id, $reqId = null)
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();
            if ($reqId === null) {
                return $this->json(['status' => 200, 'message' => 'Request added.', 'data' => $svc->saveRequest((int) $id, null, $b)]);
            }
            if (!empty($b['delete'])) {
                return $this->json(['status' => 200, 'message' => 'Request removed.', 'data' => $svc->deleteRequest((int) $id, (int) $reqId)]);
            }
            // The one modal does both jobs, so a save can carry a retitle and an answer
            // together; doing only one of them would quietly drop the other.
            if (array_key_exists('title', $b) && trim((string) $b['title']) !== '') {
                $svc->saveRequest((int) $id, (int) $reqId, $b);
            }
            return $this->json(['status' => 200, 'message' => 'Updated.', 'data' => $svc->answerRequest((int) $id, (int) $reqId, (string) ($b['status'] ?? 'open'), $b['answer'] ?? null)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/status {status, note}
    public function status($id)
    {
        try {
            $b = $this->body();
            return $this->json(['status' => 200, 'message' => 'Inquiry updated.', 'data' => $this->svc()->setStatus((int) $id, (string) ($b['status'] ?? ''), $b['note'] ?? null)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/follow_up {on, note} — when to chase the agent; blank clears it. Nothing is sent.
    public function followUp($id)
    {
        try {
            $b = $this->body();
            $it = $this->svc()->saveFollowUp((int) $id, $b['on'] ?? null, $b['note'] ?? null);
            return $this->json(['status' => 200, 'message' => !empty($it['follow_up_on']) ? 'Follow-up set for ' . $it['follow_up_on'] . '. It shows under "Follow-ups due" when the day comes.' : 'Follow-up cleared.', 'data' => $it]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/confirm — the agent said yes: the tour goes to the Hotel and Transport desks
    // POST intake/(:num)/reauth {password} — the signed-in person's password, checked and nothing else: the screen asks
    // for it before the final Confirm Tour warning, so a wrong password stops there. Never stored, never logged.
    public function reauth($id)
    {
        try {
            $b   = $this->body();
            $it  = $this->svc()->get((int) $id);
            $why = 'Confirm Tour ' . ($it['inquiry_no'] ?? ('#' . (int) $id));
            $r   = Reauth::verify($this->db(), $this->userId(), isset($b['password']) ? (string) $b['password'] : '', $why, new Activity($this->db(), $this->user(), $this->userId(), $this->actorName()));
            return $this->json($r['ok'] ? ['status' => 200, 'ok' => true, 'message' => $r['message']] : ['status' => 403, 'ok' => false, 'message' => $r['message']]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/confirm {password} — the agent said yes: the tour is numbered and released to the desks,
    // once the signed-in person's password has been checked again (the screen checked it once already, before the
    // final warning); who confirmed, and when, goes on the tour, the inquiry and the Activity log.
    public function confirm($id)
    {
        try {
            @set_time_limit(600);   // tf-tourmail-r1: the hotels' and restaurants' mails go in this request
            $b    = $this->body();
            $svc  = $this->svc();
            $it   = $svc->get((int) $id);
            $log  = new Activity($this->db(), $this->user(), $this->userId(), $this->actorName());
            $auth = Reauth::verify($this->db(), $this->userId(), isset($b['password']) ? (string) $b['password'] : '', 'Confirm Tour ' . ($it['inquiry_no'] ?? ('#' . (int) $id)), $log);
            if (!$auth['ok']) {
                return $this->json(['status' => 403, 'ok' => false, 'message' => $auth['message']]);
            }
            $tourSvc = new TourService($this->db(), $this->user(), $svc);
            // tf-resdesk-r2: one Confirm at a time per inquiry — a double click or a second screen pressing Confirm at the
            // same moment waits here and is then refused by confirm() ("Already confirmed"), so no second order and no
            // second hotel e-mail is made. The lock is released when this request ends (below, or by MySQL at disconnect).
            $lock = $this->db()->query('SELECT GET_LOCK(?, 30) AS l', ['tf_confirm_tour_' . (int) $id])->getRowArray();
            if ((int) ($lock['l'] ?? 0) !== 1) {
                return $this->json(['status' => 409, 'ok' => false, 'message' => 'This inquiry is being confirmed at this moment from another screen. Reload in a minute; nothing was changed here.']);
            }
            try {
                $tour = $tourSvc->confirm((int) $id);
            } catch (\Throwable $e) {
                $this->db()->query('SELECT RELEASE_LOCK(?)', ['tf_confirm_tour_' . (int) $id]);
                throw $e;
            }
            // schema 40: each restaurant of the Meal Master on the tour is booked at once — its booking voucher issued and emailed
            // from this person's mailbox (never in the way of the confirmation: what could not go is said, for the Activities desk)
            $booked = $this->bookRestaurants($tourSvc, (int) $tour['id']);
            // tf-tourmail-r1 (10 Oct 2026): each hotel's booking request goes at once too, as the list before Confirm showed
            // it and the person pressed OK; one that cannot go is kept as "not sent" with the reason (never in the way of
            // the confirmation). An older screen that sends no list sends no hotel mail.
            $hotelMails = !empty($b['mail']) && is_array($b['mail']) ? $this->mailHotels($tourSvc, (int) $tour['id'], (int) $id, $b['mail']) : [];
            $h = count(array_filter($tour['hotel_jobs'], static fn ($j) => empty($j['archived_at'])));
            $t = count(array_filter($tour['transport_jobs'], static fn ($j) => empty($j['archived_at'])));
            $a = count(array_filter($tour['service_jobs'] ?? [], static fn ($j) => empty($j['archived_at'])));
            try {
                $log->record('tour', (int) $tour['id'], (int) $id, (int) $tour['id'], 'confirmed_by', 'Tour ' . $tour['tour_no'] . ' confirmed by ' . $this->actorName() . ' after their password was checked; released to Hotel Bookings (' . $h . ' stay' . ($h === 1 ? '' : 's') . ', ' . $a . ' activity line' . ($a === 1 ? '' : 's') . ') and Transport (' . $t . ' vehicle' . ($t === 1 ? '' : 's') . ')',
                    [['what' => 'Confirm Tour', 'from' => (string) ($it['status'] ?? ''), 'to' => 'confirmed · ' . $tour['tour_no']]]);
            } catch (\Throwable $e) {
                // the confirmation stands; the tour's own trail line was written by the service
            }
            $this->db()->query('SELECT RELEASE_LOCK(?)', ['tf_confirm_tour_' . (int) $id]);
            return $this->json([
                'status'  => 200,
                'message' => "Confirmed as tour {$tour['tour_no']}. One job is on Hotel Bookings ({$h} hotel stay(s), {$a} activity or ticket line(s)) and one on Transport ({$t} vehicle(s)). The inquiry is now read-only." . self::bookedText($booked) . ($hotelMails ? ' ' . TourMailController::resultText($hotelMails) : ''),
                'data'    => $svc->get((int) $id),
                'meal_bookings' => $booked,
                'hotel_mails' => $hotelMails,
                'tour_id' => (int) $tour['id'],
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }
    /** Confirm Tour, tf-tourmail-r1: the hotels' booking requests, e-mailed as listed (TourMailer::sendHotels). */
    private function mailHotels(TourService $tourSvc, int $tourId, int $intakeId, array $choice): array
    {
        try {
            $db     = $this->db();
            $sender = Staff::contact($db, $this->userId(), $this->user());
            $it     = $this->svc()->get($intakeId) ?: ['id' => $intakeId, 'accom' => []];
            return (new TourMailer($db, $this->user(), $this->userId(), $this->actorName()))->sendHotels($tourSvc, $tourId, $it, $choice, $sender,
                static fn (int $jobId, array $who) => TourMailController::renderRequest($tourSvc, $db, $jobId, $who), true);
        } catch (\Throwable $e) {
            return [['job_id' => 0, 'hotel' => 'the hotels', 'status' => 'not_sent', 'to' => null, 'why' => $e->getMessage()]];
        }
    }

    /** Confirm Tour, schema 40: the restaurants' booking vouchers, issued and emailed (MealMaster::bookOnConfirm). */
    private function bookRestaurants(TourService $tourSvc, int $tourId): array
    {
        try {
            if (!MealMaster::installed($this->db())) {
                return [];
            }
            $who  = Staff::contact($this->db(), $this->userId(), $this->user());
            $user = $this->actorName();
            return (new MealMaster($this->db(), $this->user(), $this->userId(), $user))->bookOnConfirm($tourSvc, $tourId, $who,
                static fn (array $v, string $mode, ?string $message, array $sender) => MealMaster::voucherHtml($v, $mode, $message, $sender, $user), true);
        } catch (\Throwable $e) {
            return [['job_id' => 0, 'restaurant' => 'the restaurants', 'voucher_no' => null, 'status' => 'not_issued', 'to' => null, 'why' => $e->getMessage()]];
        }
    }

    /** What the restaurants' booking came to, in a sentence for the confirmation message. */
    private static function bookedText(array $booked): string
    {
        if (!$booked) {
            return '';
        }
        $sent = array_filter($booked, static fn ($b) => in_array($b['status'], ['sent', 'kept'], true));
        $not  = array_filter($booked, static fn ($b) => !in_array($b['status'], ['sent', 'kept'], true));
        $t = ' Restaurants: ' . count($sent) . ' booking voucher' . (count($sent) === 1 ? '' : 's') . ' emailed';
        if ($not) {
            $t .= '; ' . count($not) . ' not sent — ' . implode('; ', array_map(static fn ($b) => $b['restaurant'] . ($b['voucher_no'] ? ' (' . $b['voucher_no'] . ')' : '') . ': ' . $b['why'], $not)) . ' Finish ' . (count($not) === 1 ? 'it' : 'them') . ' on the Activities desk';
        }
        return $t . '.';
    }

    // =====================================================================
    //  The quotation engine: groups, rooms, vehicles by day, excursions
    // =====================================================================

    // POST intake/(:num)/rooms — the room mix for a party that does not split
    public function rooms($id)
    {
        try {
            return $this->json(['status' => 200, 'message' => 'Room mix saved.', 'data' => $this->svc()->saveRooms((int) $id, $this->body())]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/group          — add a passenger group
    // POST intake/(:num)/group/(:num)   — change one, or remove it {delete:1}
    public function group($id, $groupId = null)
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();
            if ($groupId !== null && !empty($b['delete'])) {
                return $this->json(['status' => 200, 'message' => 'Group removed; its names and vehicles are back with the whole party.', 'data' => $svc->deleteGroup((int) $id, (int) $groupId)]);
            }
            $data = $svc->saveGroup((int) $id, $groupId === null ? null : (int) $groupId, $b);
            return $this->json(['status' => 200, 'message' => $groupId ? 'Group updated.' : 'Group added.', 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/pax/(:num)/group {group_id} — put a name in a group, or take it out (blank)
    public function paxGroup($id, $paxId)
    {
        try {
            $b = $this->body();
            $g = ($b['group_id'] ?? '') === '' || $b['group_id'] === null ? null : (int) $b['group_id'];
            return $this->json(['status' => 200, 'message' => 'Passenger assigned.', 'data' => $this->svc()->assignPassenger((int) $id, (int) $paxId, $g)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/vehicle_day {vehicle_seq, day_no, retained, note} — keep a vehicle on for a stay day, or release it
    public function vehicleDay($id)
    {
        try {
            $b = $this->body();
            $retained = !empty($b['retained']) && (string) $b['retained'] !== '0';
            $data = $this->svc()->setVehicleDay((int) $id, (int) ($b['vehicle_seq'] ?? 0), (int) ($b['day_no'] ?? 0), $retained, $b['note'] ?? null);
            return $this->json(['status' => 200, 'message' => $retained ? 'Vehicle kept on for the day; the stay-day figure is charged again.' : 'Vehicle released for the day; nothing is charged for it.', 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET excursions — the excursion rate master, for the picker (active ones)
    public function excursions()
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->svc()->excursionRates(true)]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 200, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    // POST intake/(:num)/excursion          — put an excursion on a day
    // POST intake/(:num)/excursion/(:num)   — change one, or remove it {delete:1}
    public function excursion($id, $lineId = null)
    {
        try {
            $b   = $this->body();
            $svc = $this->svc();
            if ($lineId !== null && !empty($b['delete'])) {
                return $this->json(['status' => 200, 'message' => 'Excursion removed.', 'data' => $svc->deleteExcursion((int) $id, (int) $lineId)]);
            }
            $data = $svc->saveExcursion((int) $id, $lineId === null ? null : (int) $lineId, $b);
            return $this->json(['status' => 200, 'message' => $svc->serviceNote ?: ($lineId ? 'Excursion updated.' : 'Excursion added, at the master\'s rate.'), 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // =====================================================================
    //  Go for Quotation
    // =====================================================================

    // GET intake/(:num)/costing — the live costing, as this person may see it
    public function costing($id)
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->costForViewer($this->quotes()->costing((int) $id))]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET intake/(:num)/readiness — the inquiry's traffic light (red / yellow / green), what is still missing and each day's
    // activity status. Read-only: looking never changes a status.
    public function readiness($id)
    {
        try {
            $it = $this->svc()->get((int) $id);
            if (!$it) {
                return $this->json(['status' => 404, 'message' => 'Inquiry not found.'], 404);
            }
            $rd = new InquiryReadiness($this->db(), $this->user(), $this->userId(), $this->actorName());
            return $this->json(['status' => 200, 'data' => $rd->state($it, $this->quotes()->costing((int) $id))]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/day_check {day_no, status: checked | none | clear} — a person ticks a day's activities
    public function dayCheck($id)
    {
        try {
            $b  = $this->body();
            $it = $this->svc()->get((int) $id);
            if (!$it) {
                return $this->json(['status' => 404, 'message' => 'Inquiry not found.'], 404);
            }
            $rd = new InquiryReadiness($this->db(), $this->user(), $this->userId(), $this->actorName());
            return $this->json(['status' => 200, 'message' => 'Saved.', 'data' => $rd->setDay($it, $this->quotes()->costing((int) $id), (int) ($b['day_no'] ?? 0), (string) ($b['status'] ?? ''))]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET intake/(:num)/day/(:num)/rate_jump — Edit hotel on the Hotels tab: where in the Hotel Rates master this night's rate is.
    // SUPER ADMIN ONLY, checked here (the button is hidden for everybody else, but the address itself is refused too).
    // Nothing is changed: a rate is corrected in the Hotel Rates panel, through its own save, approval and history.
    public function rateJump($id, $dayId)
    {
        try {
            $it = $this->svc()->get((int) $id);
            if (!$it) {
                return $this->json(['status' => 404, 'message' => 'Inquiry not found.'], 404);
            }
            $role = (int) ($this->menuData()['user_role'] ?? 0);
            if ($role !== 1) {
                HotelRateJump::resolve($role, $it, (int) $dayId);   // throws: refused before anything is read
            }
            $line = null;
            try {
                $dayNo = 0;
                foreach ((array) ($it['days'] ?? []) as $d) {
                    if ((int) $d['id'] === (int) $dayId) {
                        $dayNo = (int) $d['day_no'];
                    }
                }
                foreach ((array) ($this->quotes()->costing((int) $id)['hotels'] ?? []) as $h) {
                    if ((int) $h['day_no'] === $dayNo) {
                        $line = $h;
                    }
                }
            } catch (\Throwable $e) {
                // the costing only supplies the reason a rate is missing; the jump works without it
            }
            $r = HotelRateJump::resolve($role, $it, (int) $dayId, $line);
            $r['url'] = base_url($r['path']);
            try {
                (new Activity($this->db(), $this->user(), $this->userId(), $this->actorName()))->record('inquiry', (int) $id, (int) $id, null, 'rate_jump',
                    'Opened the Hotel Rates master from night ' . $r['night'] . ' (' . $r['summary'] . ')' . ($r['found'] ? '' : ' — rate missing'));
            } catch (\Throwable $e) {
                // best effort
            }
            return $this->json(['status' => 200, 'data' => $r]);
        } catch (SuperAdminOnly $e) {
            return $this->json(['status' => 403, 'message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET intake/(:num)/checks — Before Tour Confirmation: the four sections (Hotels, Transfers, Entrance Fees, Activities)
    // read from the same costing the inquiry uses, each with its status and what is still missing. Totals are Pricing's.
    public function checks($id)
    {
        try {
            $it = $this->svc()->get((int) $id);
            if (!$it) {
                return $this->json(['status' => 404, 'message' => 'Inquiry not found.'], 404);
            }
            $chk = new SectionChecks($this->db(), $this->user(), $this->userId(), $this->actorName());
            return $this->json(['status' => 200, 'data' => $chk->state($it, $this->quotes()->costing((int) $id), $this->holds(self::PRICING_URL))]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/check {section, status} — a person marks a section Checked / Not required / Needs attention.
    // It records who and when in the history. It never confirms the tour: Confirm Tour is its own action.
    public function check($id)
    {
        try {
            $b  = $this->body();
            $it = $this->svc()->get((int) $id);
            if (!$it) {
                return $this->json(['status' => 404, 'message' => 'Inquiry not found.'], 404);
            }
            $chk  = new SectionChecks($this->db(), $this->user(), $this->userId(), $this->actorName());
            $data = $chk->set($it, $this->quotes()->costing((int) $id), (string) ($b['section'] ?? ''), (string) ($b['status'] ?? ''), $this->holds(self::PRICING_URL));
            return $this->json(['status' => 200, 'message' => 'Saved.', 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/markup {pct, note} — Management only: the markup for this one inquiry
    public function markup($id)
    {
        if (!$this->holds(self::PRICING_URL)) {
            return $this->json(['status' => 403, 'message' => 'The markup is set under Pricing, by Management.']);
        }
        try {
            $b = $this->body();
            $cost = $this->quotes()->setMarkup((int) $id, $b['pct'] ?? null, $b['note'] ?? null);
            return $this->json(['status' => 200, 'message' => ($b['pct'] ?? '') === '' ? 'Back on the default markup.' : 'Markup set for this inquiry.', 'data' => $cost]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/go — validate; on a pass, freeze a draft version
    public function go($id)
    {
        try {
            $r = $this->quotes()->goForQuotation((int) $id);
            $r['cost'] = $this->costForViewer($r['cost']);
            if ($r['quote'] !== null) {
                $r['quote']['cost'] = $this->costForViewer($r['quote']['cost']);
            }
            $r['status']  = 200;
            $r['message'] = $r['ok'] ? 'Quotation version ' . $r['quote']['version'] . ' is ready to preview.' : count($r['blockers']) . ' thing(s) have to be fixed before a quotation can be produced.';
            $r['data']    = $this->svc()->get((int) $id);
            return $this->json($r);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/quote/(:num)/(issue|discard)
    public function quoteAction($id, $qid, $action)
    {
        try {
            // tf-panel2-r1: issuing is its own permission (act:quote.issue), checked on the server before anything is read
            if ($action === 'issue' && !$this->holds(\App\Libraries\TourFlow\StaffTaskMap::ISSUE_URL)) {
                return $this->json(['status' => 403, 'message' => 'You may prepare quotations but not issue them. Ask the Super Admin to open "Issue quotations" for you on the Staff Access Control Panel.']);
            }
            $q = $this->quotes();
            $row = $q->quote((int) $qid);
            if (!$row || (int) $row['intake_id'] !== (int) $id) {
                return $this->json(['status' => 404, 'message' => 'That quotation is not on this inquiry.']);
            }
            if ($action === 'issue') {
                $b = $this->body();
                $q->issue((int) $qid, $b['note'] ?? null);
                return $this->json(['status' => 200, 'message' => 'Version ' . $row['version'] . ' issued. The inquiry is marked quoted.', 'data' => $this->svc()->get((int) $id)]);
            }
            if ($action === 'discard') {
                $q->discard((int) $qid);
                return $this->json(['status' => 200, 'message' => 'Draft discarded.', 'data' => $this->svc()->get((int) $id)]);
            }
            return $this->json(['status' => 400, 'message' => 'Unknown action.']);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET intake/(:num)/quote/(:num)?view=internal|client[&pdf=1] — a frozen version, on screen or as a PDF
    public function quoteView($id, $qid)
    {
        try {
            $q = $this->quotes()->quote((int) $qid);
            if (!$q && (int) $qid > 0) {
                // tf-pdf-r1: a draft is replaced each time Go for Quotation runs, so a page opened before that points at a
                // version that no longer exists — opened on the inquiry's latest version instead of a dead end
                $latest = $this->db()->query('SELECT id FROM tf_quote WHERE intake_id = ? ORDER BY version DESC, id DESC LIMIT 1', [(int) $id])->getRowArray();
                if ($latest) {
                    $qs = $this->request->getUri()->getQuery();
                    return redirect()->to(site_url(self::URL . '/intake/' . (int) $id . '/quote/' . (int) $latest['id']) . ($qs !== '' ? '?' . $qs : ''));
                }
            }
            if (!$q || (int) $q['intake_id'] !== (int) $id) {
                return $this->response->setStatusCode(404)->setBody('That quotation is not on this inquiry.');
            }
            return $this->renderQuote($q['cost'], $q, (string) $this->request->getGet('view'), (bool) $this->request->getGet('pdf'));
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setBody(esc($e->getMessage()));
        }
    }

    // GET intake/(:num)/preview?view=internal|client[&pdf=1] — the live costing, not frozen, marked as a preview
    public function preview($id)
    {
        try {
            $cost = $this->quotes()->costing((int) $id);
            return $this->renderQuote($cost, null, (string) $this->request->getGet('view'), (bool) $this->request->getGet('pdf'));
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setBody(esc($e->getMessage()));
        }
    }

    // =====================================================================
    //  The pictures on the client quotation (schema 27)
    // =====================================================================

    // GET intake/(:num)/photos — each day's picture and the tags its programme points at, and the library to choose from
    public function photos($id)
    {
        try {
            $svc = $this->svc();
            $it  = $svc->get((int) $id);
            if (!$it) {
                return $this->json(['status' => 404, 'message' => 'Inquiry not found.'], 404);
            }
            $lib = $svc->photos();
            if (!$lib->available()) {
                return $this->json(['status' => 200, 'data' => ['available' => false, 'days' => [], 'tags' => [], 'library' => [], 'all_tags' => []]]);
            }
            $library = array_map(static fn ($p) => ['id' => $p['id'], 'thumb_url' => $p['thumb_url'], 'url' => $p['url'], 'caption' => $p['caption'], 'preferred' => $p['preferred'], 'tag_codes' => $p['tag_codes'], 'tags' => $p['tags'], 'uses' => $p['uses'], 'width' => $p['width'], 'height' => $p['height']], $lib->photos());
            return $this->json(['status' => 200, 'data' => [
                'available' => true,
                'days'      => $it['photos']['days'] ?? [],
                'tags'      => $it['photos']['tags'] ?? [],
                'cover'     => $it['photos']['cover'] ?? [],
                'library'   => array_values($library),
                'all_tags'  => array_map(static fn ($t) => ['code' => $t['code'], 'name' => $t['name'], 'kind' => $t['kind'], 'photos' => $t['photos']], $lib->tags()),
                'sees_photos' => $this->holds(self::PHOTOS_URL),
                'photos_url'  => self::PHOTOS_URL,
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/photo/(:num) {photo_id | choice: 'none' | 'auto'} — a person's choice of picture for one day
    public function photo($id, $dayNo)
    {
        try {
            $b = $this->body();
            $choice = $b['photo_id'] ?? ($b['choice'] ?? 'auto');
            if (!in_array($choice, ['none', 'auto', '', null], true)) {
                $choice = (int) $choice;
            }
            $it = $this->svc()->setDayPhoto((int) $id, (int) $dayNo, $choice);
            $word = $choice === 'none' ? 'No picture on day ' . (int) $dayNo . '.' : ($choice === 'auto' || $choice === '' || $choice === null ? 'Day ' . (int) $dayNo . ' takes the library\'s own pick again.' : 'Picture set for day ' . (int) $dayNo . '.');
            return $this->json(['status' => 200, 'message' => $word, 'data' => $it]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    /** The pictures for the quotation of an inquiry, read live from the library; nothing when it is not installed. */
    private function photosFor(int $intakeId): array
    {
        try {
            $svc = $this->svc();
            if (!$svc->photos()->available()) {
                return ['days' => [], 'cover' => []];
            }
            $it = $svc->get($intakeId);
            $out = $it['photos'] ?? ['days' => [], 'cover' => []];
            $out['dir'] = $svc->photos()->dir();   // where the files are on this server, for the PDF
            return $out;
        } catch (\Throwable $e) {
            return ['days' => [], 'cover' => []];
        }
    }

    /**
     * One renderer for both views, on screen and on paper. The client page never carries a cost, a markup or a rate.
     * On a version, the client page and its PDF show the price-per-person rows up to the version's maximum number of
     * travellers (schema 34) and no further; a preview, and a version made before the maximum existed, show every row.
     * $toFile: the PDF written to that path (for the email) instead of answered to the browser.
     */
    private function renderQuote(array $cost, ?array $quote, string $view, bool $pdf, ?string $toFile = null)
    {
        $view = $view === 'client' ? 'client' : 'internal';
        $seesPricing = $this->holds(self::PRICING_URL);
        $maxPax = $quote !== null && isset($quote['max_pax']) && $quote['max_pax'] !== null ? (int) $quote['max_pax'] : null;
        $data = [
            'cost'         => $view === 'client' ? QuoteService::clientViewFor($cost, $maxPax) : ($seesPricing ? $cost : $this->costForViewer($cost)),
            'quote'        => $quote,
            'preview'      => $quote === null,
            'sees_pricing' => $seesPricing,
            'intake_url'   => self::URL,
            'intake_id'    => (int) $cost['intake_id'],
            'pricing'      => $this->quotes()->pricing(),
            'company'      => $this->company(),
            'logo'         => FCPATH . 'assets/images/Logo-new.png',
            // the day pictures (schema 27) — the client page only; a presentation layer over the same days
            'photos'       => $view === 'client' ? $this->photosFor((int) $cost['intake_id']) : ['days' => [], 'cover' => []],
            // schema 34: the staff controls on the client page — the maximum number of travellers, and the email
            'levels'       => $quote !== null && $view === 'client' ? QuoteService::paxLevels($cost) : [],
            'max_pax'      => $maxPax,
            'email'        => $quote !== null && $view === 'client' && !$pdf ? $this->emailDraftFor($quote) : null,
            'me_name'      => $this->actorName(),
        ];
        $tpl = $view === 'client' ? 'pages/tour/quote_client' : 'pages/tour/quote_internal';
        if (!$pdf) {
            return view($tpl, $data);
        }
        // tf-pdf-r1: TCPDF writes its working images (a PNG logo with transparency) to a cache folder. On UAT the PHP temp
        // folder is not writable by the web server, so the PDF stopped with "imagepng(...): Permission denied". The app's
        // own writable folder is used instead, defined before the library loads (it reads K_PATH_CACHE once).
        if (!defined('K_PATH_CACHE')) {
            foreach ([rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR, rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR, rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR, rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR, '/tmp/'] as $cacheDir) {
                if (!is_dir($cacheDir)) {
                    @mkdir($cacheDir, 0775, true);
                }
                if (is_dir($cacheDir) && is_writable($cacheDir)) {
                    define('K_PATH_CACHE', $cacheDir);
                    break;
                }
            }
        }
        if (!defined('K_PATH_CACHE') || !is_writable(K_PATH_CACHE)) {
            // nowhere to write the logo's working image: the PDF goes out without the logo rather than not at all
            $data['logo'] = null;
        }
        if (!class_exists('\TCPDF')) {
            // the library sits where composer puts it (vendor/tecnickcom/tcpdf) but composer's autoloader was
            // never regenerated for it on this server: load it from there by hand
            $lib = (defined('ROOTPATH') ? ROOTPATH : dirname(APPPATH) . DIRECTORY_SEPARATOR) . 'vendor' . DIRECTORY_SEPARATOR . 'tecnickcom' . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'tcpdf.php';
            if (is_file($lib)) {
                require_once $lib;
            }
        }
        if (!class_exists('\TCPDF')) {
            // the PDF library is not installed on this server (composer install puts it in vendor/tecnickcom):
            // the print view opens instead, and the browser's own "Save as PDF" makes the file
            $data['auto_print'] = true;
            $data['no_pdf_lib'] = true;
            return view($tpl, $data);
        }
        $data['for_pdf'] = true;
        $html = view($tpl, $data);
        $doc  = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $doc->SetCreator('Avenir Holidays');
        $doc->SetAuthor('Avenir Holidays');
        $doc->SetTitle(($view === 'client' ? 'Quotation ' : 'Costing ') . ($cost['inquiry_no'] ?? ''));
        $doc->setPrintHeader(false);
        $doc->setPrintFooter(false);
        $doc->SetMargins(14, 14, 14);
        $doc->SetAutoPageBreak(true, 16);
        $doc->AddPage();
        $doc->writeHTML($html, true, false, true, false, '');
        $name = ($view === 'client' ? 'Quotation-' : 'Costing-') . preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($cost['inquiry_no'] ?? 'draft')) . ($quote ? '-v' . $quote['version'] : '-preview') . '.pdf';
        if ($toFile !== null) {
            file_put_contents($toFile, $doc->Output($name, 'S'));
            return $name;
        }
        return $this->response->setHeader('Content-Type', 'application/pdf')->setBody($doc->Output($name, 'S'))->setHeader('Content-Disposition', 'inline; filename="' . $name . '"');
    }

    /** The email panel's starting state for a version (schema 34); never throws — the page shows without it. */
    private function emailDraftFor(array $quote): ?array
    {
        try {
            return $this->quotes()->emailDraft((int) $quote['id'], Staff::contact($this->db(), $this->userId(), $this->user()), $this->company());
        } catch (\Throwable $e) {
            return null;
        }
    }

    // POST intake/(:num)/quote/(:num)/max_pax {max_pax} — the maximum number of travellers the version is quoted
    // for: the client quotation and its PDF show the price-per-person rows up to it. One of the sizes on the table;
    // not changed once the version has been emailed (the agent has that range) — a new version then.
    public function quoteMaxPax($id, $qid)
    {
        try {
            $q = $this->quotes();
            $row = $q->quote((int) $qid);
            if (!$row || (int) $row['intake_id'] !== (int) $id) {
                return $this->json(['status' => 404, 'message' => 'That quotation is not on this inquiry.']);
            }
            $b    = $this->body();
            $was  = $row['max_pax'] ?? null;
            $row  = $q->setMaxPax((int) $qid, $b['max_pax'] ?? null);
            if ((int) $was !== (int) $row['max_pax']) {
                try {
                    (new Activity($this->db(), $this->user(), $this->userId(), $this->actorName()))->record('inquiry', (int) $id, (int) $id, null, 'quotation',
                        'Quotation v' . $row['version'] . ' · maximum number of travellers: ' . ($was !== null ? (int) $was : '—') . ' → ' . (int) $row['max_pax'],
                        [['what' => 'v' . $row['version'] . ' maximum number of travellers', 'from' => $was !== null ? (string) (int) $was : null, 'to' => (string) (int) $row['max_pax']]]);
                } catch (\Throwable $e) {
                    // the setting stands
                }
            }
            return $this->json(['status' => 200, 'message' => 'Version ' . $row['version'] . ' is quoted for up to ' . (int) $row['max_pax'] . ' traveller' . ((int) $row['max_pax'] === 1 ? '' : 's') . '; the quotation and its PDF show the price rows up to that size.', 'data' => ['max_pax' => (int) $row['max_pax'], 'version' => (int) $row['version']]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET intake/(:num)/quote/(:num)/email[?template=ID] — the email as it starts: To, CC, the templates offered, subject and message
    // from the chosen (or the default) one, the PDF's name, the sends so far
    public function quoteEmail($id, $qid)
    {
        try {
            $row = $this->quotes()->quote((int) $qid);
            if (!$row || (int) $row['intake_id'] !== (int) $id) {
                return $this->json(['status' => 404, 'message' => 'That quotation is not on this inquiry.']);
            }
            $tpl = (int) ($this->request->getGet('template') ?? 0);
            return $this->json(['status' => 200, 'data' => $this->quotes()->emailDraft((int) $qid, Staff::contact($this->db(), $this->userId(), $this->user()), $this->company(), $tpl > 0 ? $tpl : null)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    /** The fields as typed, checked: a valid To, a subject, the maximum set; the CC list split on commas and semicolons. */
    private function emailFields(array $b, array $row): array
    {
        $to  = trim((string) ($b['to'] ?? ''));
        $cc  = array_values(array_filter(array_map('trim', preg_split('/[;,\s]+/', (string) ($b['cc'] ?? '')) ?: []), static fn ($x) => $x !== ''));
        $bad = array_values(array_filter($cc, static fn ($x) => !filter_var($x, FILTER_VALIDATE_EMAIL)));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Enter the agent\'s email address in To.');
        }
        if ($bad) {
            throw new \InvalidArgumentException('Not an email address in CC: ' . implode(', ', $bad) . '.');
        }
        $subject = trim(str_replace(["\r", "\n"], ' ', (string) ($b['subject'] ?? '')));
        if ($subject === '') {
            throw new \InvalidArgumentException('The email needs a subject.');
        }
        $message = str_replace("\r", '', (string) ($b['message'] ?? ''));
        if (trim($message) === '') {
            throw new \InvalidArgumentException('The email needs a message.');
        }
        if (($row['max_pax'] ?? null) === null && QuoteService::paxLevels($row['cost']) !== []) {
            throw new \InvalidArgumentException('Choose the Maximum Number of Travellers first; the quotation goes out with the price rows up to it.');
        }
        return ['to' => $to, 'cc' => implode(', ', $cc), 'cc_list' => $cc, 'subject' => mb_substr($subject, 0, 255), 'message' => mb_substr($message, 0, 8000), 'pdf_name' => QuoteService::pdfName($row['cost']['inquiry_no'] ?? null, (int) $row['version'])];
    }

    // POST intake/(:num)/quote/(:num)/email/preview {to, cc, subject, message} — the email exactly as it would go, nothing sent
    public function quoteEmailPreview($id, $qid)
    {
        try {
            $row = $this->quotes()->quote((int) $qid);
            if (!$row || (int) $row['intake_id'] !== (int) $id) {
                return $this->json(['status' => 404, 'message' => 'That quotation is not on this inquiry.']);
            }
            $m   = $this->emailFields($this->body(), $row);
            $who = Staff::contact($this->db(), $this->userId(), $this->user());
            // tf-walkfix-r1: the words and the attachment must agree — a message that still names another
            // range than the one the PDF carries is pointed out on the preview, where Send now is
            $warning = null;
            if (isset($row['max_pax']) && $row['max_pax'] !== null && preg_match('/up to (\d+) travellers?/i', (string) $m['message'], $mm) && (int) $mm[1] !== (int) $row['max_pax']) {
                $warning = 'The message says "up to ' . (int) $mm[1] . ' travellers" but the quotation attached carries the price rows up to ' . (int) $row['max_pax'] . '. Pick the template again to fill the message in afresh, or correct the wording, before pressing Send now.';
            }
            return $this->json(['status' => 200, 'data' => [
                'from' => $who['email'], 'from_name' => $who['name'], 'can_send' => (bool) $who['can_send'], 'to' => $m['to'], 'cc' => $m['cc'], 'subject' => $m['subject'],
                'html' => QuoteMail::html($m['message']), 'attachment' => $m['pdf_name'], 'max_pax' => $row['max_pax'] ?? null, 'warning' => $warning,
                'pdf_url' => base_url(self::URL . '/intake/' . (int) $id . '/quote/' . (int) $qid) . '?view=client&pdf=1',
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST intake/(:num)/quote/(:num)/email/send {to, cc, subject, message} — the quotation PDF, with the price rows up to
    // the version's maximum, attached and sent from the signed-in person's own mailbox; the send (or the failure) is kept
    // on the version, a draft is issued by it, and the Activity log says who sent what to whom
    public function quoteEmailSend($id, $qid)
    {
        $tmp = null;
        try {
            $q   = $this->quotes();
            $row = $q->quote((int) $qid);
            if (!$row || (int) $row['intake_id'] !== (int) $id) {
                return $this->json(['status' => 404, 'message' => 'That quotation is not on this inquiry.']);
            }
            if ($row['status'] === 'superseded') {
                return $this->json(['status' => 400, 'message' => 'Version ' . $row['version'] . ' is history; send the current version.']);
            }
            // tf-panel2-r1: e-mailing a draft issues it, so it needs the issue permission too
            if ($row['status'] === 'draft' && !$this->holds(\App\Libraries\TourFlow\StaffTaskMap::ISSUE_URL)) {
                return $this->json(['status' => 403, 'message' => 'Sending this version issues it. You may prepare quotations but not issue them; ask the Super Admin to open "Issue quotations" for you.']);
            }
            // tf-comms-r3: a version the system drafted from a request e-mail goes nowhere until a person has reviewed it and pressed Issue
            if ($row['status'] === 'draft' && (int) ($row['review_required'] ?? 0) === 1) {
                return $this->json(['status' => 400, 'message' => 'Version ' . $row['version'] . ' was drafted by the system from the request e-mail and is marked "Draft – Staff Review Required". Check every line against the e-mail, correct what is needed, and press Issue; only then can it be e-mailed.']);
            }
            // tf-signal-r1: Send Quotation only on green — checked again here, on the server, immediately before anything is sent.
            // (A confirmed inquiry is the record of a sale already made; its quotation may be sent again.)
            $itNow = $this->svc()->get((int) $id);
            if ($itNow && in_array((string) ($itNow['status'] ?? ''), ['new', 'working', 'quoted'], true)) {
                try {
                    (new InquiryReadiness($this->db(), $this->user(), $this->userId(), $this->actorName()))->assertReady($itNow, $q->costing((int) $id));
                } catch (NotReadyToQuote $e) {
                    try {
                        (new Activity($this->db(), $this->user(), $this->userId(), $this->actorName()))->record('inquiry', (int) $id, (int) $id, null, 'quotation', 'Send Quotation v' . $row['version'] . ' refused: the inquiry is not green');
                    } catch (\Throwable $e2) {
                        // best effort
                    }
                    return $this->json(['status' => 409, 'blocked' => true, 'message' => $e->getMessage()]);
                }
            }
            $m   = $this->emailFields($this->body(), $row);
            $who = Staff::contact($this->db(), $this->userId(), $this->user());
            if (!$who['can_send'] && QuoteMail::$transport === null) {
                return $this->json(['status' => 400, 'message' => 'Your account has no mailbox set up to send from (email, password and host on your user profile). Download the PDF and send it from your mail program, or ask Management to set the mailbox up.']);
            }
            $dir = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'tf_mail';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $tmp  = $dir . DIRECTORY_SEPARATOR . 'q' . (int) $qid . '-' . bin2hex(random_bytes(6)) . '.pdf';
            $made = $this->renderQuote($row['cost'], $row, 'client', true, $tmp);
            if (!is_string($made) || !is_file($tmp) || filesize($tmp) < 100) {
                return $this->json(['status' => 400, 'message' => 'The quotation PDF could not be made (is the PDF library installed?), so nothing was sent.']);
            }
            $r = QuoteMail::send($m['to'], $m['subject'], QuoteMail::html($m['message']), $m['cc_list'], [['path' => $tmp, 'name' => $m['pdf_name']]]);
            $log = new Activity($this->db(), $this->user(), $this->userId(), $this->actorName());
            if (!$r['ok']) {
                try {
                    $failedRec = $q->recordEmail((int) $qid, $m, 'failed', $r['error'], $this->user(), $this->userId(), $this->actorName(), $who['email']);
                    (new \App\Libraries\TourFlow\Comms($this->db(), $this->user(), $this->userId(), $this->actorName()))->captureQuoteEmail((int) $failedRec['id']);   // tf-comms-r1
                    $log->record('inquiry', (int) $id, (int) $id, null, 'quotation', 'Quotation v' . $row['version'] . ' could not be emailed to ' . $m['to'] . ' — ' . $r['error']);
                } catch (\Throwable $e) {
                    // the failure is reported either way
                }
                return $this->json(['status' => 400, 'message' => $r['error']]);
            }
            $rec = $q->recordEmail((int) $qid, $m, 'sent', null, $this->user(), $this->userId(), $this->actorName(), $who['email']);
            try {   // tf-comms-r1: the quotation e-mail and its PDF join the Communications & History record
                (new \App\Libraries\TourFlow\Comms($this->db(), $this->user(), $this->userId(), $this->actorName()))->captureQuoteEmail((int) $rec['id'], $tmp);
            } catch (\Throwable $e) {
                log_message('error', 'Communications capture (quotation e-mail) failed: ' . $e->getMessage());
            }
            try {
                $log->record('inquiry', (int) $id, (int) $id, null, 'quotation', 'Quotation v' . $row['version'] . ' emailed to ' . $m['to'] . ($m['cc'] !== '' ? ' (cc ' . $m['cc'] . ')' : '') . ' by ' . $this->actorName() . ' — ' . $m['pdf_name'] . ', price rows up to ' . (int) ($row['max_pax'] ?? 0) . ' travellers' . ($row['status'] === 'draft' ? '; version issued' : ''),
                    [['what' => 'v' . $row['version'] . ' emailed', 'from' => null, 'to' => $m['to']]]);
            } catch (\Throwable $e) {
                // the send stands; it is on the version
            }
            return $this->json(['status' => 200, 'message' => 'Quotation v' . $row['version'] . ' sent to ' . $m['to'] . ' with ' . $m['pdf_name'] . ' attached' . ($row['status'] === 'draft' ? '. The version is issued; the inquiry is marked quoted.' : '.'), 'data' => ['email' => $this->emailDraftFor($rec['quote']), 'quote' => ['status' => $rec['quote']['status'], 'version' => (int) $rec['quote']['version']]]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        } finally {
            if ($tmp !== null && is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    /**
     * tf-comms-r2: the client quotation (with its itinerary) of one version as a PDF file, for the e-mail composed
     * on Communications & History. Returns the file name, or null when the PDF could not be made. The signed-in
     * person's view (pricing or not) is the same as on the quotation page.
     */
    public function quotePdfToFile(int $qid, string $toFile): ?string
    {
        $row = $this->quotes()->quote($qid);
        if (!$row) {
            return null;
        }
        $made = $this->renderQuote($row['cost'], $row, 'client', true, $toFile);
        return is_string($made) ? $made : null;
    }

    // tf-safari-r2: GET intake/(:num)/safari_slip/(:num) — the safari payment slip(s) of one safari line, printable
    public function safariSlip($id, $serviceId)
    {
        $data = ['company' => $this->company(), 's' => null, 'error' => null];
        try {
            $db = $this->db();
            $m = new \App\Libraries\TourFlow\EntranceMaster($db, $this->user(), $this->userId(), $this->actorName());
            $data['s'] = $m->slips((int) $id, (int) $serviceId);
        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }
        return view('pages/tour/safari_slip', $data);
    }

    // tf-child-r1: the Child Fare Review
    // GET  intake/(:num)/child_review                         the children, the items, the suggestions and the decisions
    // POST intake/(:num)/child_review {key, decision, reason_code, reason, per_night}   decide one item ('' clears it)
    // POST intake/(:num)/children {children:[{age, bed}]}     each child's age and bedding
    public function childReview($id)
    {
        try {
            $svc = $this->svc();
            $cf = new \App\Libraries\TourFlow\ChildFares($this->db(), $this->user(), $this->userId(), $this->actorName());
            if (strtolower($this->request->getMethod()) === 'post') {
                $svc->assertOpen((int) $id);
                $b = $this->body();
                $it = $svc->get((int) $id);
                $r = $cf->decide($it, (string) ($b['key'] ?? ''), (string) ($b['decision'] ?? ''), isset($b['reason_code']) ? (string) $b['reason_code'] : null, isset($b['reason']) ? (string) $b['reason'] : null, $b['per_night'] ?? null);
                $svc->checkServices((int) $id);
                $it = $svc->get((int) $id);
                return $this->json(['status' => 200, 'message' => $r['decision'] === '' ? 'Cleared — it needs checking again.' : ($r['decision'] === 'applicable' ? 'Applicable — the child fare is charged.' : 'Not applicable — Child: no charge.'), 'data' => ['review' => $cf->review($it), 'inquiry' => $it]]);
            }
            return $this->json(['status' => 200, 'data' => ['review' => $cf->review($svc->get((int) $id))]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    public function children($id)
    {
        try {
            $svc = $this->svc();
            $svc->assertOpen((int) $id);
            $b = $this->body();
            $cf = new \App\Libraries\TourFlow\ChildFares($this->db(), $this->user(), $this->userId(), $this->actorName());
            $cf->saveChildren((int) $id, is_array($b['children'] ?? null) ? $b['children'] : []);
            $svc->checkServices((int) $id);
            $it = $svc->get((int) $id);
            return $this->json(['status' => 200, 'message' => 'Children saved. Items whose charge depends on them need checking again.', 'data' => ['review' => $cf->review($it), 'inquiry' => $it]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    /** The company's own particulars for the letterhead, from the settings that already exist; nothing invented. */
    private function company(): array
    {
        $out = ['name' => 'Avenir Holidays', 'address' => '', 'phone' => '', 'email' => '', 'web' => ''];
        try {
            $db = $this->db();
            if ($db->query("SHOW TABLES LIKE 'tf_meta'")->getResultArray()) {
                foreach ($db->query("SELECT k, v FROM tf_meta WHERE k IN ('company_name','company_address','company_phone','company_email','company_web')")->getResultArray() as $r) {
                    $k = substr((string) $r['k'], 8);
                    if (trim((string) $r['v']) !== '') {
                        $out[$k] = (string) $r['v'];
                    }
                }
            }
        } catch (\Throwable $e) {
            // the letterhead falls back to the name alone
        }
        return $out;
    }
}
