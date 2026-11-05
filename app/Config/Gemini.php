<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Gemini extends BaseConfig
{
    // Read from the environment, never from source. Set GEMINI_API_KEY in the
    // project's .env file if a Gemini feature is switched on.
    //
    // A key was hard-coded on this line until 15 Sep 2026, which means it was
    // readable by anyone with access to the source or a backup of it. Replacing it
    // here does NOT revoke it — it still has to be rotated in the Google Cloud
    // console. Nothing in the application was found to consume this config.
    public $apiKey = '';
    public $model = 'gemini-2.0-flash';
    public $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    // Optimized settings for translation tasks
    public $temperature = 0.1;           // Low temperature for consistent translation output
    public $maxOutputTokens = 2048;      // Adequate tokens for structured responses
    public $candidateCount = 1;          // Single response to avoid variations
    public $topP = 0.95;                 // Focused sampling for better consistency
    public $topK = 40;                   // Balanced creativity vs consistency

    // Safety settings for content filtering
    public $safetySettings = [
        [
            'category' => 'HARM_CATEGORY_HARASSMENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_HATE_SPEECH',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ]
    ];

    public function __construct()
    {
        parent::__construct();
        // Same pattern as Config\OpenAI: the key comes from .env, not from source.
        $this->apiKey = env('GEMINI_API_KEY', '');
    }
}