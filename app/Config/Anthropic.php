<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Anthropic (Claude) API settings.
 *
 * The key is read from the environment, never from source: set ANTHROPIC_API_KEY
 * in the project's .env file. Same pattern as Config\OpenAI.
 */
class Anthropic extends BaseConfig
{
    public string $apiKey = '';

    public string $apiUrl = 'https://api.anthropic.com/v1/messages';

    /** Sent on every request; the API requires it. */
    public string $version = '2023-06-01';

    /**
     * Default model for reading inquiry emails. Balanced for this job: the emails
     * are messy business English from Japanese agents and accuracy matters far more
     * than the fraction of a cent between models. Overridable per install from the
     * tf_meta setting 'extract_model'.
     */
    public string $model = 'claude-sonnet-5';

    /**
     * Enough for a long multi-day itinerary with a rooming list and requests.
     *
     * 8192 was not: a real eleven-day group with ten names and eight questions ran past it
     * and the answer came back cut off mid-sentence. Nothing is charged for headroom that
     * goes unused, and a read that stops short is now refused rather than saved in part.
     */
    public int $maxTokens = 16384;

    public int $timeout = 120;

    /**
     * Sampling temperature. Left unset on purpose: the current Claude models reject the
     * parameter outright (400 "temperature is deprecated for this model"), and reading an
     * email never depended on it — every value the model returns is checked back against
     * the email text before it is kept. Give it a number only if you move to a model that
     * still accepts one; it can also be set from .env as anthropic.temperature.
     */
    public $temperature = null;

    public function __construct()
    {
        parent::__construct();
        $this->apiKey = env('ANTHROPIC_API_KEY', '');
    }
}
