<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Gemini extends BaseConfig
{
    public $apiKey = 'AIzaSyC2lgbgcJbbhFot_VAqbe5OSDEoUnPLKHw';
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
}