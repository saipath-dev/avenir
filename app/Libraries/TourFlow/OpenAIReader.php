<?php

namespace App\Libraries\TourFlow;

use App\Libraries\HotelRateExtractor;

/**
 * Reads an email with OpenAI, using strict json_schema response format.
 *
 * Kept as the alternative backend so the choice of service stays a setting. The
 * rate-sheet reader (HotelRateExtractor) still uses OpenAI directly and is
 * untouched by any of this.
 */
final class OpenAIReader implements Reader
{
    private $config;
    private ?string $modelOverride;

    public function __construct($config = null, ?string $model = null)
    {
        $this->config        = $config ?? config('OpenAI');
        $this->modelOverride = $model;
    }

    public function name(): string
    {
        return 'openai';
    }

    public function label(): string
    {
        return 'OpenAI';
    }

    public function available(): bool
    {
        return trim((string) ($this->config->apiKey ?? '')) !== '';
    }

    public function model(): string
    {
        $m = trim((string) ($this->modelOverride ?? ''));
        return $m !== '' ? $m : (string) ($this->config->visionModel ?: 'gpt-4o');
    }

    public function read(string $system, string $user, array $schema): array
    {
        if (!$this->available()) {
            throw new \RuntimeException('No OpenAI key is configured, so the email cannot be read automatically. Set OPENAI_API_KEY in .env, or fill the inquiry in by hand.');
        }

        $body = [
            'model'           => $this->model(),
            'temperature'     => 0,
            'messages'        => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => 'tour_inquiry', 'strict' => true, 'schema' => $schema]],
        ];

        $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $this->config->apiKey];
        if (!empty($this->config->organizationId)) {
            $headers[] = 'OpenAI-Organization: ' . $this->config->organizationId;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => (string) $this->config->apiUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $ca = HotelRateExtractor::caBundle();
        if ($ca !== null) {
            curl_setopt($ch, CURLOPT_CAINFO, $ca);
        }

        $raw = curl_exec($ch);
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Could not reach the reading service: ' . $err);
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $j = json_decode((string) $raw, true);
        if ($code !== 200) {
            $detail = is_array($j) && isset($j['error']['message']) ? ' — ' . $j['error']['message'] : '';
            throw new \RuntimeException("The reading service answered {$code}{$detail}");
        }
        $content = $j['choices'][0]['message']['content'] ?? null;
        if (!is_string($content)) {
            throw new \RuntimeException('The reading service returned nothing to read.');
        }
        $parsed = json_decode($content, true);
        if (!is_array($parsed)) {
            throw new \RuntimeException('The reading service did not return usable JSON.');
        }
        return $parsed;
    }
}
