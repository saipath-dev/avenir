<?php

namespace App\Libraries;

use Config\Anthropic;

/**
 * tf-itinfmt-r1 (26 Oct 2026, Shantha): the formatter used OpenAI (gpt-4o) until that key stopped being accepted
 * (401 since 11 Oct 2026). It now uses Claude through the same settings and key as the Tour Inquiry and Hotel
 * Rates readers (Config\Anthropic: ANTHROPIC_API_KEY in .env). The key is read there only; never logged or shown.
 * The prompt, the JSON shape, the cache and the fallback (null → the raw per-line rendering) are unchanged.
 */
class ItineraryFormatter
{
    private $config;
    private $apiKey;
    private $cache;

    public function __construct()
    {
        $this->config = function_exists('config') ? (config('Anthropic') ?? new Anthropic()) : new Anthropic();
        $this->apiKey = (string) ($this->config->apiKey ?? '');
        $this->cache = \Config\Services::cache();

        if (trim($this->apiKey) === '') {
            throw new \Exception('Anthropic API key not found (ANTHROPIC_API_KEY in .env).');
        }
    }

    /**
     * Clean up the per-day itinerary narrative (dedupe description/area, drop noise)
     * while leaving every other section of the print view untouched.
     * Returns null on any failure so the caller can fall back to the raw per-line rendering.
     *
     * Return shape: ['tour_date' => ['day narrative line', 'day narrative line', ...], ...]
     */
    public function format(string $tourId, array $printData): ?array
    {
        try {
            $sourcePayload = $this->buildSourcePayload($printData);

            if (empty($sourcePayload['itinerary_days'])) {
                return null;
            }

            $cacheKey = 'itinerary_fmt_v3_' . $tourId . '_' . md5(json_encode($sourcePayload));
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }

            $formatted = $this->callClaudeAPI($sourcePayload);

            $this->cache->save($cacheKey, $formatted, 60 * 60 * 24 * 30);

            return $formatted;
        } catch (\Exception $e) {
            log_message('error', 'ItineraryFormatter: ' . $e->getMessage());
            return null;
        }
    }

    private function buildSourcePayload(array $printData): array
    {
        $itineraryDays = [];
        foreach ($printData['itinerary'] ?? [] as $tourDate => $group) {
            $lines = [];
            foreach ($group['details'] ?? [] as $row) {
                if (!empty($row['description'])) {
                    $lines[] = [
                        'description' => str_replace('@', 'at', $row['description']),
                        'area' => $row['area'] ?? '',
                    ];
                }
            }
            if (!empty($lines)) {
                $itineraryDays[] = ['date' => $tourDate, 'lines' => $lines];
            }
        }

        return [
            'itinerary_days' => $itineraryDays,
        ];
    }

    private function callClaudeAPI(array $sourcePayload): array
    {
        $systemPrompt = <<<PROMPT
You clean up per-day tour itinerary notes for a printed booking sheet. Use ONLY the description/area text provided for each day. Never invent details.

Rules for each day's "lines" array:
- Each input line has a description and an area. When area duplicates or is contained within description, output the phrase once, not twice.
- Drop empty/filler lines (e.g. a bare "Attn Guide" reminder can stay if it has real content, but never output an empty string).
- Keep each resulting bullet short and clear - one sentence per bullet.
- Do not reorder days. Do not merge two different days together.
- Do not add hotel names, confirmation status, or any text not derivable from the input lines for that day.

Respond with ONLY a JSON object (no markdown, no commentary) mapping each input "date" value exactly as given to an array of cleaned bullet strings, e.g.:
{"2026-08-08": ["Breakfast at Hotel", "Transfer to Dambulla Cave Temple"], "2026-08-09": ["..."]}
PROMPT;

        $requestData = [
            'model'      => (string) (($this->config->model ?? '') ?: 'claude-sonnet-5'),
            'max_tokens' => max(8192, (int) ($this->config->maxTokens ?? 0)),   // the model thinks before it answers; 1,500 (the OpenAI limit) was all used up by that
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => json_encode($sourcePayload['itinerary_days'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)],
            ],
        ];

        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: ' . ((string) ($this->config->version ?? '') ?: '2023-06-01'),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, (string) ($this->config->apiUrl ?: 'https://api.anthropic.com/v1/messages'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int) ($this->config->timeout ?: 120));
        // certificates checked, as everything else outbound (the OpenAI version switched verification off)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $ca = HotelRateExtractor::caBundle();
        if ($ca !== null) {
            curl_setopt($ch, CURLOPT_CAINFO, $ca);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('Curl error: ' . $error);
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            // the provider's error type only — never the key, never the whole body
            $j = json_decode((string) $response, true);
            $why = is_array($j) && isset($j['error']['type']) ? $j['error']['type'] . (isset($j['error']['message']) ? ': ' . mb_substr((string) $j['error']['message'], 0, 160) : '') : 'no detail';
            throw new \Exception('Claude answered HTTP ' . $httpCode . ' (' . $why . ')');
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }

        $text = '';
        foreach ((array) ($responseData['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= (string) ($block['text'] ?? '');
            }
        }
        if (trim($text) === '') {
            throw new \Exception('Invalid API response format (stop_reason: ' . (string) ($responseData['stop_reason'] ?? '?') . ', blocks: ' . implode(',', array_map(static fn ($b) => (string) ($b['type'] ?? '?'), (array) ($responseData['content'] ?? []))) . ')');
        }
        // the model is told "JSON only"; a stray ```json fence is tolerated, nothing else is
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text)));

        $dayMap = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($dayMap)) {
            throw new \Exception('Could not parse itinerary JSON from model response');
        }

        return $dayMap;
    }
}
