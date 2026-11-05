<?php

namespace App\Libraries;

use Config\OpenAI;

class HotelRateExtractor
{
    private OpenAI $config;

    public function __construct()
    {
        $this->config = new OpenAI();

        if (empty($this->config->apiKey)) {
            throw new \Exception('OpenAI API key not found.');
        }
    }

    /**
     * Extract hotel rate rows from a contract PDF or image file.
     *
     * @param string $filePath Absolute path to the uploaded file
     * @param string $mimeType e.g. application/pdf, image/jpeg, image/png
     * @param string $fileName Original file name (used for PDF file_data payload)
     * @return array List of extracted rate rows
     */
    public function extract(string $filePath, string $mimeType, string $fileName): array
    {
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw new \Exception('Unable to read uploaded file.');
        }

        $base64 = base64_encode($fileContent);

        if ($mimeType === 'application/pdf') {
            $contentPart = [
                'type' => 'file',
                'file' => [
                    'filename' => $fileName,
                    'file_data' => 'data:application/pdf;base64,' . $base64,
                ],
            ];
        } else {
            $contentPart = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:' . $mimeType . ';base64,' . $base64,
                ],
            ];
        }

        $messages = [
            [
                'role' => 'system',
                'content' => 'You read hotel/travel-agent rate contracts (PDF or scanned image) and extract every '
                    . 'rate row from it into structured data. Before extracting anything, read the ENTIRE document '
                    . 'start to finish — the main rate grid, and every other section, including any "Room '
                    . 'Supplements" and "Meal Supplements" tables which usually appear after the main grid. Do not '
                    . 'stop at the first table you see.'
                    . "\n\n"
                    . 'A row is one room type combined with one validity date range (season). Room prices are '
                    . 'broken down by occupancy (Single/Double/Triple) and meal plan (RO=Room Only, BB=Bed & '
                    . 'Breakfast, HB=Half Board, FB=Full Board). If the document lists multiple seasons for the '
                    . 'same room type, output one row per season.'
                    . "\n\n"
                    . 'Filling meal plans the document does not price directly: hotels usually only publish one '
                    . 'meal plan (often Half Board) in the main rate grid, then separately state per-person '
                    . 'adjustments in a "Meal Supplements PP" table that let you derive the other meal plans. Two '
                    . 'adjustments are common there and mean the following, regardless of the exact wording used:'
                    . "\n"
                    . '  - A "Bed & Breakfast reduction" (or similarly named BB adjustment) of amount X means '
                    . 'BB = HB minus X per person: sbb = shb - X, dbb = dhb - (X*2), tbb = thb - (X*3).'
                    . "\n"
                    . '  - A "Lunch" line of amount Y means Full Board = Half Board + one extra meal (lunch) per '
                    . 'person per day: sfb = shb + Y, dfb = dhb + (Y*2), tfb = thb + (Y*3).'
                    . "\n"
                    . 'Apply whichever of these are explicitly stated, for every occupancy and every season, instead '
                    . 'of leaving that meal-plan column at 0. Only leave a meal-plan column at 0 if the document '
                    . 'gives no price for it and no adjustment that lets you derive it. "Extra bed" and "All '
                    . 'Inclusive" supplements are NOT meal-plan adjustments — ignore them, there is nowhere to put '
                    . 'them in this table.'
                    . "\n\n"
                    . 'Room Supplements (e.g. "Premium: 25", "Junior Suite: 150" in a "Room Supplements" table) are '
                    . 'a flat per-room, per-night amount added on top of a base room type\'s rate. These tables are '
                    . 'frequently laid out as two side-by-side columns (label column, then a separate value column) '
                    . 'that can get jumbled when read out of order — read label-value pairs carefully in their '
                    . 'original visual row order, left to right, top to bottom; never assume the Nth label pairs '
                    . 'with the Nth value without checking their position on the page.'
                    . "\n\n"
                    . 'For the base room type itself (the one named in the main rate grid, e.g. "Superior"), ALWAYS '
                    . 'output its own row(s) for every season, using the prices/derivations above — do this even '
                    . 'though it also has room supplements. Then, for EACH room supplement listed, output further '
                    . 'ADDITIONAL rows — one per season, same seasons as the base room type — named "<base room '
                    . 'type> - <supplement name>" (e.g. "Superior - Premium"), where every occupancy/meal-plan price '
                    . 'equals the base room type\'s price for that same season PLUS that supplement\'s amount. '
                    . 'Example: if Superior Half Board Single for a season is 130 and Premium is +25, output a '
                    . '"Superior - Premium" row for that season with shb = 155 (and apply the same +25 to every '
                    . 'other filled column: sbb, sfb, dhb, dbb, dfb, thb, tbb, tfb for that row). So a room type with '
                    . '3 seasons and 2 listed supplements produces 3 base rows PLUS 6 supplement rows (3 seasons x 2 '
                    . 'supplements) = 9 rows total.'
                    . "\n\n"
                    . 'Dates must be normalized to YYYY-MM-DD. Never invent a number that is not stated in or '
                    . 'directly derivable (via an explicitly stated adjustment) from the document.',
            ],
            [
                'role' => 'user',
                'content' => [
                    $contentPart,
                    [
                        'type' => 'text',
                        'text' => 'Read this entire contract, including any room/meal supplement tables, then '
                            . 'extract every hotel rate row (base room types x season, plus derived meal-plan '
                            . 'columns and room-supplement rows as instructed).',
                    ],
                ],
            ],
        ];

        $requestData = [
            'model' => $this->config->visionModel,
            'messages' => $messages,
            'temperature' => 0,
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'hotel_rate_rows',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'rows' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'room_type' => ['type' => 'string'],
                                        'sdate' => ['type' => 'string'],
                                        'edate' => ['type' => 'string'],
                                        'sro' => ['type' => 'number'],
                                        'sbb' => ['type' => 'number'],
                                        'shb' => ['type' => 'number'],
                                        'sfb' => ['type' => 'number'],
                                        'dro' => ['type' => 'number'],
                                        'dbb' => ['type' => 'number'],
                                        'dhb' => ['type' => 'number'],
                                        'dfb' => ['type' => 'number'],
                                        'tro' => ['type' => 'number'],
                                        'tbb' => ['type' => 'number'],
                                        'thb' => ['type' => 'number'],
                                        'tfb' => ['type' => 'number'],
                                    ],
                                    'required' => [
                                        'room_type', 'sdate', 'edate',
                                        'sro', 'sbb', 'shb', 'sfb',
                                        'dro', 'dbb', 'dhb', 'dfb',
                                        'tro', 'tbb', 'thb', 'tfb',
                                    ],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                        'required' => ['rows'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->config->apiKey,
        ];

        if (!empty($this->config->organizationId)) {
            $headers[] = 'OpenAI-Organization: ' . $this->config->organizationId;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->config->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        // Certificate verification was switched off here until 15 Sep 2026, which meant
        // the OpenAI key was sent over a connection nobody checked. It is on now; see
        // caBundle() for where the root certificates come from.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $ca = self::caBundle();
        if ($ca !== null) {
            curl_setopt($ch, CURLOPT_CAINFO, $ca);
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('Curl error: ' . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('OpenAI API request failed with HTTP code: ' . $httpCode . '. Response: ' . $response);
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }

        if (!isset($responseData['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid API response format.');
        }

        $content = json_decode($responseData['choices'][0]['message']['content'], true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($content['rows']) || !is_array($content['rows'])) {
            throw new \Exception('AI did not return a valid rate table.');
        }

        return $content['rows'];
    }

    /**
     * Root certificates for outbound HTTPS.
     *
     * This PHP install has neither curl.cainfo nor openssl.cafile set and ships no
     * bundle of its own, so curl cannot verify anyone's certificate on its own. The
     * Mozilla root list is kept at writable/cacert.pem for that reason. If php.ini is
     * ever pointed at a bundle, that one wins and this file stops being consulted.
     *
     * Returns null when the platform already has a usable bundle configured.
     */
    public static function caBundle(): ?string
    {
        foreach ([(string) ini_get('curl.cainfo'), (string) ini_get('openssl.cafile')] as $configured) {
            if ($configured !== '' && is_file($configured)) {
                return null; // php.ini is set up correctly; let curl use it
            }
        }
        $bundled = WRITEPATH . 'cacert.pem';
        return is_file($bundled) ? $bundled : null;
    }
}
