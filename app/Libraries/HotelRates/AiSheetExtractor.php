<?php

namespace App\Libraries\HotelRates;

/**
 * Reads a rate sheet (PDF / image) with Claude and returns Sheet-shaped arrays — one per hotel found in the
 * document — ready for RateImportService::preview().
 *
 * tf-rate-reader-r1 (3 Oct 2026): the reader used OpenAI (Config\OpenAI, gpt-4o) until the OpenAI key on the
 * server stopped being accepted (401 for every file). It now uses Claude through the same settings and the same
 * key as the Tour Inquiry email reader (Config\Anthropic: ANTHROPIC_API_KEY in .env, model, API version). The key
 * is only ever read from there; it is never written, logged or shown.
 *
 * The document goes to Claude whole: a PDF as a document block, a JPG / PNG as an image block. The answer is held
 * to the shape by forced tool use (one tool, record_rate_sheets, whose input is the "sheets" list the instructions
 * below describe). A document with no rates or prices at all — a hotel factsheet, a brochure, a letter — comes back
 * as no sheet, never as an empty plan.
 *
 * Nothing is written: the person always sees the preview and the validator before a plan is saved.
 */
class AiSheetExtractor
{
    public const TOOL = 'record_rate_sheets';

    /** What the person asks of the reader, after the document itself. */
    public const ASK = 'Read the whole document, then record it with the record_rate_sheets tool in the JSON shape described (the "sheets" list). '
        . 'Never invent a number that is not printed or directly derivable. '
        . 'If the document has no room rates or prices at all (a hotel factsheet, a brochure, a letter about something else), record an empty "sheets" list.';

    private $config;

    /** @var callable|null fn(string $url, array $headers, string $json, int $timeout): array{0:int,1:string} — tests only */
    private $transport;

    /** Sheets the reader returned that carried no figure at all (a factsheet read as a plan), left out of the answer. */
    public int $dropped = 0;

    public function __construct($config = null, ?callable $transport = null)
    {
        $this->config = $config ?? (function_exists('config') ? config('Anthropic') : null) ?? new \Config\Anthropic();
        $this->transport = $transport;
        if (!self::configured($this->config)) {
            throw new \RuntimeException('No Anthropic API key is configured (ANTHROPIC_API_KEY in .env), so the sheet cannot be read automatically.');
        }
    }

    /** Whether the server has the key the reader needs (the Tour Inquiry email reader's key). */
    public static function configured($config = null): bool
    {
        try {
            $config = $config ?? (function_exists('config') ? config('Anthropic') : null) ?? (class_exists(\Config\Anthropic::class) ? new \Config\Anthropic() : null);
        } catch (\Throwable $e) {
            return false;
        }
        return $config !== null && trim((string) ($config->apiKey ?? '')) !== '';
    }

    public function model(): string
    {
        return (string) (($this->config->model ?? '') ?: 'claude-sonnet-5');
    }

    /** @return array<int, array> sheets */
    public function extract(string $filePath, string $mimeType, string $fileName, array $hints = []): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException('Unable to read the uploaded file.');
        }
        $body = $this->requestBody(base64_encode($content), $mimeType, $hints);
        $response = $this->send($body);

        // a reading that ran out of room comes back looking almost right, with the last lists cut off: refused whole
        if (($response['stop_reason'] ?? '') === 'max_tokens') {
            throw new \RuntimeException('The answer came back cut off: this document needs more room than one reading allows, so none of it was kept.');
        }
        $input = null;
        foreach ((array) ($response['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'tool_use' && ($block['name'] ?? '') === self::TOOL && is_array($block['input'] ?? null)) {
                $input = $block['input'];
                break;
            }
        }
        if ($input === null) {
            throw new \RuntimeException('The reading service did not return the structured answer' . (($response['stop_reason'] ?? '') !== '' ? ' (stopped: ' . $response['stop_reason'] . ')' : '') . '.');
        }
        $sheets = $input['sheets'] ?? (isset($input['contract']) ? [$input] : []);
        if (is_string($sheets)) {
            $sheets = json_decode($sheets, true);
        }
        if (!is_array($sheets)) {
            throw new \RuntimeException('The AI did not return valid JSON.');
        }
        $out = [];
        $this->dropped = 0;
        foreach ($sheets as $s) {
            if (is_string($s)) {
                $s = json_decode($s, true);
            }
            if (!is_array($s)) {
                continue;
            }
            $s['contract'] = is_array($s['contract'] ?? null) ? $s['contract'] : [];
            $s['contract']['source_type'] = 'ai';
            $s['contract']['source_file'] = $fileName;
            $n = Sheet::normalise($s);
            if (!self::hasFigures($n)) {
                $this->dropped++;
                continue;
            }
            $out[] = $n;
        }
        return $out;
    }

    /**
     * The request body, without sending it — extract() sends exactly this, so what the tests inspect is what goes on
     * the wire. temperature only when Config\Anthropic names one (the current models refuse it).
     */
    public function requestBody(string $base64, string $mimeType, array $hints = []): array
    {
        $part = $mimeType === 'application/pdf'
            ? ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $base64]]
            : ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mimeType, 'data' => $base64]];
        $hintText = '';
        if (!empty($hints['hotel_name'])) {
            $hintText .= "The document is expected to be for the hotel \"{$hints['hotel_name']}\"; if it covers several hotels, return one sheet per hotel. ";
        }
        if (!empty($hints['markets'])) {
            $hintText .= 'Market codes to use: ' . implode(', ', array_map(static fn ($k, $v) => "{$k} = {$v}", array_keys($hints['markets']), $hints['markets'])) . '. ';
        }
        $body = [
            'model'       => $this->model(),
            'max_tokens'  => max(16384, (int) ($this->config->maxTokens ?? 0)),
            'system'      => self::systemPrompt(),
            'messages'    => [['role' => 'user', 'content' => [$part, ['type' => 'text', 'text' => $hintText . self::ASK]]]],
            'tools'       => [[
                'name'         => self::TOOL,
                'description'  => 'Record every hotel rate plan found in the document, in the "sheets" shape the instructions describe. An empty list when the document has no rates.',
                'input_schema' => ['type' => 'object', 'properties' => ['sheets' => ['type' => 'array', 'items' => ['type' => 'object']]], 'required' => ['sheets']],
            ]],
            'tool_choice' => ['type' => 'tool', 'name' => self::TOOL],
        ];
        $t = $this->config->temperature ?? null;
        if ($t !== null && $t !== '') {
            $body['temperature'] = (float) $t;
        }
        return $body;
    }

    /** @return array the decoded response body */
    private function send(array $body): array
    {
        $headers = ['Content-Type: application/json', 'x-api-key: ' . $this->config->apiKey, 'anthropic-version: ' . (($this->config->version ?? '') ?: '2023-06-01')];
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $url = (string) (($this->config->apiUrl ?? '') ?: 'https://api.anthropic.com/v1/messages');
        $timeout = 240;   // a long rate sheet takes Claude a minute or two to transcribe; the controller allows the request 300 s
        if ($this->transport !== null) {
            [$code, $raw] = ($this->transport)($url, $headers, $json, $timeout);
        } else {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $json, CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            // the same root certificates as the Tour Inquiry email reader (HotelRateExtractor::caBundle)
            $ca = class_exists(\App\Libraries\HotelRateExtractor::class) ? \App\Libraries\HotelRateExtractor::caBundle() : null;
            if ($ca !== null) {
                curl_setopt($ch, CURLOPT_CAINFO, $ca);
            }
            $raw = curl_exec($ch);
            if (curl_errno($ch)) {
                $err = curl_error($ch);
                curl_close($ch);
                throw new \RuntimeException('Could not reach Claude: ' . $err);
            }
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }
        $j = json_decode((string) $raw, true);
        if ((int) $code !== 200) {
            $detail = is_array($j) && isset($j['error']['message']) ? ' — ' . $j['error']['message'] : '';
            throw new \RuntimeException('Claude answered ' . (int) $code . $detail);
        }
        if (!is_array($j)) {
            throw new \RuntimeException('Claude returned something that could not be read.');
        }
        return $j;
    }

    /**
     * Whether a read sheet carries anything to import: a room rate, a supplement, a meal price or difference, a child
     * rate, a guide or FOC rule, an offer, an extra, or the terms of an addendum. A factsheet read as a "plan" (the
     * hotel, its rooms, nothing priced) has none of these, and is not offered as a plan.
     */
    public static function hasFigures(array $s): bool
    {
        foreach ((array) ($s['seasons'] ?? []) as $p) {
            foreach ((array) ($p['rates'] ?? []) as $r) {
                foreach (['sgl', 'dbl', 'tpl', 'quad', 'unit', 'child_with_bed', 'child_no_bed'] as $k) {
                    if (isset($r[$k]) && is_numeric($r[$k])) {
                        return true;
                    }
                }
            }
        }
        // the terms of an addendum (cancellation bands, payment terms, stay restrictions) are content too
        foreach (['cancellation_rules', 'payment_rules', 'restrictions'] as $list) {
            if (!empty($s[$list])) {
                return true;
            }
        }
        foreach (['room_supplements', 'meal_deltas', 'meal_prices', 'supplements', 'child_rates', 'guide_policies', 'foc_rules', 'offers', 'extras'] as $list) {
            foreach ((array) ($s[$list] ?? []) as $row) {
                foreach ((array) $row as $k => $v) {
                    // an amount, an adult / child figure, a percentage, a free-of-charge count — not an age, a limit or an id
                    if (is_numeric($v) && preg_match('/amount|adult|child|pct|every_n|free_n|free_nights/', (string) $k) && !in_array($k, ['max_children', 'room_type_id'], true)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /** The reason as staff should read it — never the service's raw answer, never a key. */
    public static function plainError(string $raw, string $fileName): string
    {
        $f = '"' . $fileName . '"';
        if (preg_match('/answered 401|authentication|x-api-key|api key/i', $raw)) {
            return "The AI reader could not read {$f}: the reading service did not accept this server's key (ANTHROPIC_API_KEY in .env). Ask whoever looks after the Anthropic account to check it. Nothing was saved.";
        }
        if (preg_match('/answered (429|529|503)|overloaded|rate.?limit/i', $raw)) {
            return "The AI reader is busy just now and could not read {$f}. Try again in a minute. Nothing was saved.";
        }
        if (preg_match('/Could not reach|timed out|timeout/i', $raw)) {
            return "The AI reader could not be reached, or took too long, for {$f}. Try again; if it keeps happening, try a smaller file (the rate pages only). Nothing was saved.";
        }
        if (stripos($raw, 'cut off') !== false) {
            return "{$f} is too long to read in one go. Split the PDF (for example one season or one hotel per file) and read each part. Nothing was saved.";
        }
        if (preg_match('/answered 400|answered 413/i', $raw)) {
            return "The AI reader could not open {$f}. If it is password-protected or a very large scan, save the rate pages as a new PDF, JPG or PNG and read that. Nothing was saved.";
        }
        return "The AI reader could not read {$f}: " . mb_substr(preg_replace('/\s+/', ' ', $raw), 0, 200) . '. Nothing was saved.';
    }

    public static function systemPrompt(): string
    {
        return <<<'TXT'
You read Sri Lankan hotel rate sheets / travel-agent contracts (PDF or scanned image) for a destination management company and transcribe EVERYTHING into JSON. Read the entire document first: rate grids, supplement tables, child policy, guide policy, FOC policy, cancellation, payment, terms.

Return exactly this JSON (omit lists you have nothing for; never omit "hotel", "contract", "seasons"):
{"sheets":[{
 "hotel":{"name":"exact hotel name"},
 "contract":{"name":"short plan name incl. season","plan_type":"contract|special_offer|tactical|early_bird|addendum","rate_code":"code printed on the sheet or null","tier":"all|fit|group","tier_min_rooms":null,"tier_max_rooms":null,"markets":"ALL or comma list of market codes (JP,CN,FE,IN,SA,ME,EU,UK,NL,ES,EE,RU,AU,US); prefix with - to exclude","valid_from":"YYYY-MM-DD","valid_to":"YYYY-MM-DD","book_from":null,"book_to":"YYYY-MM-DD or null (book before)","currency":"USD","billing_currency":"same|LKR","pricing_mode":"per_room|per_person|per_unit","default_meal_plan":null,"taxes_included":1,"tax_pct":null,"tax_note":null,"contract_date":null,"contract_type":null,"hotel_reference":null,"credit_facility":"unknown|yes|no","min_stay":null,"max_rooms_per_booking":null,"release_days":null,"tentative_hold_hours":null,"extra_bed_adult":null,"max_children_free":null,"child_policy_text":"as written","guide_policy_text":"as written","foc_policy_text":"as written","cancellation_text":"as written","payment_text":"as written","terms_text":"other conditions, short","voucher_note":"anything that must appear on the voucher","notes":"anything unclear for the staff to check"},
 "room_types":[{"name":"","priced_as":"base|supplement","base_room_type":"name of the base room when priced as base + supplement","room_count":null,"size_sqm":null,"bed_config":null,"max_adults":null,"max_children":null,"unit_pax_max":null}],
 "seasons":[{"name":"","kind":"normal|peak|festive|low","min_stay":null,"max_rooms":null,"dates":[{"from":"YYYY-MM-DD","to":"YYYY-MM-DD"}],"rates":[{"room_type":"","meal_plan":"RO|BB|HB|FB|AI","sgl":null,"dbl":null,"tpl":null,"quad":null,"unit":null,"child_with_bed":null,"child_no_bed":null}]}],
 "room_supplements":[{"room_type":"","season":null,"amount":0,"per":"room|person"}],
 "meal_deltas":[{"from":"HB","to":"BB","adult":-5,"child":-2.5}],
 "meal_prices":[{"meal":"breakfast|lunch|dinner","adult":0,"child":null}],
 "supplements":[{"type":"gala|peak|festival|extra_adult|early_checkin|late_checkout|other","name":"","date_from":null,"date_to":null,"season":null,"meal_plans":"BB or HB,FB,AI or null for all","room_type":null,"per":"person|room","amount":null,"amount_sgl":null,"amount_dbl":null,"amount_tpl":null,"child_amount":null,"child_pct":null,"pct_of_rate":null,"compulsory":1}],
 "child_rates":[{"age_from":0,"age_to":5.99,"bed":"any|with_bed|no_bed","meal_plan":null,"amount":0,"pct":null,"pct_basis":"adult_rate|meal_rate|meal_supplement|room_rate","max_children":null,"note":null}],
 "guide_policies":[{"role":"guide|driver|tour_leader","meal_plan":null,"amount":null,"currency":"USD","pct_of":"sgl|null","pct":null,"per":"room|person","free_from_pax":null,"min_rooms":null,"accommodation":"hotel_room|guide_quarters|nearby|unknown","note":null}],
 "foc_rules":[{"basis":"pax|rooms","every_n":15,"free_n":1,"discount_pct":100,"note":null}],
 "offers":[{"kind":"early_booking|long_stay|honeymoon|group_reduction|free_night|other","name":"","book_from":null,"book_to":null,"stay_from":null,"stay_to":null,"min_nights":null,"min_pax":null,"min_rooms":null,"value_pct":null,"value_amount":null,"free_nights":null,"per":"room|person|booking|night","combinable":0,"excludes_peak":0,"conditions":null}],
 "cancellation_rules":[{"date_from":null,"date_to":null,"tier":"all|fit|group","min_rooms":null,"max_rooms":null,"min_pax":null,"max_pax":null,"days_from":30,"days_to":15,"charge_pct":50,"charge_nights":null,"note":null}],
 "payment_rules":[{"applies":"all|credit|non_credit|fit|group|peak","kind":"prepay|advance|interest|surcharge|credit_period|other","days_before":null,"pct":null,"date_from":null,"date_to":null,"note":null}],
 "restrictions":[{"kind":"min_stay|closed_to_arrival|closed_to_departure|meal_plan_required|stop_sale","date_from":"","date_to":"","value":"2 or HB,FB,AI","note":null}],
 "extras":[{"category":"excursion|transfer|mice|wellness|other","name":"","per":"person|room|booking|group","adult":null,"child":null}]
}]}

Rules:
- One "sheet" per hotel. Multi-hotel documents (Aitken Spence, Radisson, Amaya, MOI/DBI) → several sheets.
- A season may have SEVERAL date ranges when the sheet prices them together ("01 Nov–20 Dec & 01–30 Apr", "24–29 Dec and 2–7 Jan"). Put them all in "dates" of that one season.
- Rates are per room per night unless the sheet says per person; whole villa / bungalow / tent prices go in "unit" with pricing_mode per_unit. A FAMILY or 4-person column goes in "quad".
- A room priced as "base + USD X supplement" (Higher room category supplements, Room supplements, Add-on upgrades, Suite supplements) is NOT a rate row: list it in room_types with priced_as "supplement" and base_room_type, and add a room_supplements entry. Only the base room gets rate rows.
- Meal plans printed on the grid are rate rows (RO room only, BB, HB, FB, AI). Meal plans given as "BB reduction", "FB supplement", "AI package on HB", "Half board add-on" are meal_deltas relative to the basis they mention: reductions are NEGATIVE, supplements positive, per person per night; copy the child amount if printed. Breakfast/lunch/dinner prices per person go in meal_prices.
- "-", "X", "N/A" or blank cells mean not offered: leave null. Never write 0 for a rate.
- Gala / Christmas / New Year dinners: one supplements entry per basis group when the amounts differ (BB guests vs HB/FB/AI guests), type "gala", per person, with the exact date, child amount or child %. Peak, August, Perahera, festival surcharges: type peak/festival, per room or per person as printed; if the surcharge differs by occupancy (SGL/DBL/TPL) use amount_sgl/amount_dbl/amount_tpl.
- Extra adult / third person / 4th pax supplements: type "extra_adult" with the meal plan(s) they apply to. Child supplements with/without extra bed by age band go in child_rates (amount 0 = free of charge; "50% of meal rates" → pct 50, pct_basis meal_rate).
- Guide / driver / tour leader room rates, FOC thresholds ("above 15 pax free"), "every 15th paying pax the 16th is free", "every 7th room the 8th at 50%" → guide_policies / foc_rules.
- Cancellation: one row per band, with the stay period and FIT/group or room/pax limits when the sheet distinguishes them. days_from is the larger number of days before arrival, days_to the smaller (30→15). Free cancellation = charge_pct 0. "1 night charge" → charge_nights 1.
- "Bookings only accepted on HB/FB on 24 & 31 Dec" → restrictions meal_plan_required; "no check-out on 31 Dec" → closed_to_departure; "minimum 2 nights during the festive period" → min_stay.
- Keep exact dates with the correct year as printed. If the sheet prints an obviously stale year (last season's dates), keep what is printed and mention it in contract.notes.
- Booking windows ("book before 31 Oct", "valid for bookings made until") → book_to. Rate codes (HA/W26_27/AME/01), market codes and "must be mentioned on the voucher" → rate_code / voucher_note.
- "All other supplements as per the original contract" → plan_type addendum and say so in notes.
TXT;
    }
}
