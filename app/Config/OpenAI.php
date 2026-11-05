<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class OpenAI extends BaseConfig
{
    /**
     * OpenAI API Configuration
     */
    
    // Your OpenAI API Key
    public string $apiKey;
    
    // OpenAI Organization ID
    public string $organizationId = 'org-xTBZemzqqGEimXataddSHhJ3';
    
    // OpenAI API endpoint
    public string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    
    // Model to use
    public string $model = 'gpt-3.5-turbo';

    // Vision/file-capable model used for document extraction (hotel rate PDFs/images)
    public string $visionModel = 'gpt-4o';
    
    // Request timeout in seconds
    public int $timeout = 30;
    
    // Maximum tokens for response
    public int $maxTokens = 1000;
    
    // Temperature for response creativity (0.0 to 2.0)
    public float $temperature = 0.3;
    
    // System context for the chatbot
    public string $systemContext = 'You are a helpful assistant for the Avenir Travel Management System. You help travel agents and staff understand how to use the system. ONLY provide step-by-step instructions. Do NOT include explanations, introductions, conclusions, or additional information. Focus ONLY on practical "how-to" steps.';
    
    // Response format instructions
    public string $responseFormat = 'IMPORTANT: Your response must ONLY contain numbered step-by-step instructions. Do NOT include any introductory text, explanations, or concluding remarks. Start directly with "1." and list only the required actions. Use format "Go to [Menu] → [Submenu]" for navigation. Each step should be a clear, actionable instruction.';
    
    // Rate limiting (requests per minute)
    public int $rateLimit = 60;
    
    // Cache responses for common queries (in minutes)
    public int $cacheTime = 60;

    public function __construct()
    {
        parent::__construct();
        $this->apiKey = env('OPENAI_API_KEY', '');
    }
}