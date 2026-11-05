<?php

namespace App\Libraries\TourFlow;

use App\Libraries\HotelRateExtractor;

/**
 * Reads an email with Claude, through the Messages API.
 *
 * The answer is held to the schema by forced tool use: a single tool is declared
 * whose input_schema is the schema we want, and tool_choice requires it, so the
 * model can only reply by filling that shape in. This is the Anthropic equivalent
 * of OpenAI's strict json_schema response format.
 */
final class AnthropicReader implements Reader
{
    public const TOOL = 'record_inquiry';

    private $config;
    private ?string $modelOverride;

    public function __construct($config = null, ?string $model = null)
    {
        $this->config        = $config ?? config('Anthropic');
        $this->modelOverride = $model;
    }

    public function name(): string
    {
        return 'anthropic';
    }

    public function label(): string
    {
        return 'Claude (Anthropic)';
    }

    public function available(): bool
    {
        return trim((string) ($this->config->apiKey ?? '')) !== '';
    }

    public function model(): string
    {
        $m = trim((string) ($this->modelOverride ?? ''));
        return $m !== '' ? $m : (string) ($this->config->model ?: 'claude-sonnet-5');
    }

    public function read(string $system, string $user, array $schema): array
    {
        if (!$this->available()) {
            throw new \RuntimeException('No Anthropic API key is configured, so the email cannot be read automatically. Set ANTHROPIC_API_KEY in .env, or fill the inquiry in by hand.');
        }

        $response = $this->send($this->requestBody($system, $user, $schema));

        // A read that ran out of room comes back looking almost right: the early fields are
        // there and a list at the end is cut off mid-word. Saving that would quietly lose
        // part of an itinerary, which is the one thing this module must never do.
        if (($response['stop_reason'] ?? '') === 'max_tokens') {
            throw new \RuntimeException('The answer came back cut off because this email needs more room than the current limit allows, so none of it has been saved. Raise maxTokens in app/Config/Anthropic.php, or split the email and read it in parts.');
        }

        foreach ((array) ($response['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'tool_use' && ($block['name'] ?? '') === self::TOOL && is_array($block['input'] ?? null)) {
                return $block['input'];
            }
        }

        // Forced tool use should make this impossible; if it happens, say so plainly
        // rather than half-reading something and presenting it as the agent's words.
        $stop = (string) ($response['stop_reason'] ?? '');
        throw new \RuntimeException('The reading service did not return the structured answer' . ($stop !== '' ? " (stopped: {$stop})" : '') . '.');
    }

    /** @return array the decoded response body */
    private function send(array $body): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => (string) $this->config->apiUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->config->apiKey,
                'anthropic-version: ' . ($this->config->version ?: '2023-06-01'),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => (int) ($this->config->timeout ?: 120),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        // Same root certificates as everything else outbound; see HotelRateExtractor::caBundle().
        $ca = HotelRateExtractor::caBundle();
        if ($ca !== null) {
            curl_setopt($ch, CURLOPT_CAINFO, $ca);
        }

        $raw = curl_exec($ch);
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Could not reach Claude: ' . $err);
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $j = json_decode((string) $raw, true);
        if ($code !== 200) {
            $detail = is_array($j) && isset($j['error']['message']) ? ' — ' . $j['error']['message'] : '';
            throw new \RuntimeException("Claude answered {$code}{$detail}");
        }
        if (!is_array($j)) {
            throw new \RuntimeException('Claude returned something that could not be read.');
        }
        return $j;
    }

    /**
     * The request body, without sending it. read() builds its request through this same
     * method, so what the tests inspect is exactly what goes on the wire.
     *
     * temperature is deliberately absent unless Config\Anthropic names one. The current
     * Claude models reject the parameter outright (400 "temperature is deprecated for
     * this model"), and nothing here depended on it: the never-guess rule is enforced by
     * checking every quote back against the email in EmailExtractor, not by sampling.
     */
    public function requestBody(string $system, string $user, array $schema): array
    {
        $body = [
            'model'       => $this->model(),
            'max_tokens'  => (int) ($this->config->maxTokens ?: 8192),
            'system'      => $system,
            'messages'    => [['role' => 'user', 'content' => $user]],
            'tools'       => [[
                'name'         => self::TOOL,
                'description'  => 'Record what the agent\'s email actually states about the trip. Every value must be accompanied by a verbatim quote from the email, or left null.',
                'input_schema' => $schema,
            ]],
            'tool_choice' => ['type' => 'tool', 'name' => self::TOOL],
        ];

        $t = $this->config->temperature ?? null;
        if ($t !== null && $t !== '') {
            $body['temperature'] = (float) $t;
        }

        return $body;
    }
}
