<?php

namespace App\Libraries;

use Config\OpenAI;

class OpenAIChatbot
{
    private $config;
    private $apiKey;
    private $knowledgeBase;
    private $cache;

    public function __construct()
    {
        log_message('critical', 'OpenAIChatbot: Starting constructor');
        
        try {
            $this->config = new OpenAI();
            log_message('critical', 'OpenAIChatbot: Config loaded successfully');
            
            $this->apiKey = $this->config->apiKey;
            log_message('critical', 'OpenAIChatbot: API key loaded - ' . (empty($this->apiKey) ? 'EMPTY' : 'EXISTS (length: ' . strlen($this->apiKey) . ')'));
            
            $this->knowledgeBase = new KnowledgeBase();
            log_message('critical', 'OpenAIChatbot: KnowledgeBase initialized');
            
            $this->cache = \Config\Services::cache();
            log_message('critical', 'OpenAIChatbot: Cache service loaded - ' . (is_null($this->cache) ? 'NULL' : 'LOADED'));
            
            if (empty($this->apiKey)) {
                log_message('critical', 'OpenAIChatbot: API key is empty - throwing exception');
                throw new \Exception('OpenAI API key not found.');
            }
            
            log_message('critical', 'OpenAIChatbot: Constructor completed successfully');
        } catch (\Exception $e) {
            log_message('critical', 'OpenAIChatbot: Constructor error - ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process user query and generate helpful response
     */
    public function processQuery(string $userQuery): array
    {
        log_message('critical', 'OpenAIChatbot: Processing query - ' . substr($userQuery, 0, 100));
        
        try {
            // Check cache first
            $cacheKey = 'chatbot_' . md5(strtolower($userQuery));
            log_message('critical', 'OpenAIChatbot: Checking cache with key - ' . $cacheKey);
            
            $cachedResponse = $this->cache->get($cacheKey);
            
            if ($cachedResponse !== null) {
                log_message('critical', 'OpenAIChatbot: Cache hit - returning cached response');
                return [
                    'success' => true,
                    'response' => $cachedResponse,
                    'source' => 'cache'
                ];
            }
            
            log_message('critical', 'OpenAIChatbot: Cache miss - proceeding with fresh processing');

            // Search knowledge base for relevant content
            log_message('critical', 'OpenAIChatbot: Searching knowledge base');
            $searchResults = $this->knowledgeBase->searchRelevantContent($userQuery);
            log_message('critical', 'OpenAIChatbot: Knowledge base search completed - found ' . count($searchResults) . ' results');
            
            // Prepare context from knowledge base
            log_message('critical', 'OpenAIChatbot: Building context for AI');
            $context = $this->knowledgeBase->buildContextForAI($searchResults, $userQuery);
            log_message('critical', 'OpenAIChatbot: Context built - length: ' . strlen($context));
            
            // Generate response using OpenAI
            log_message('critical', 'OpenAIChatbot: Calling OpenAI API');
            $response = $this->callOpenAIAPI($userQuery, $context);
            log_message('critical', 'OpenAIChatbot: OpenAI API response received - length: ' . strlen($response));
            
            // Process response with quick actions
            $processedResponse = $this->processResponse($response, $searchResults);
            
            // Cache the response
            $this->cache->save($cacheKey, $processedResponse, $this->config->cacheTime * 60);
            
            return [
                'success' => true,
                'response' => $processedResponse,
                'source' => 'openai',
                'relevant_docs' => count($searchResults),
                'search_results' => $searchResults
            ];
            
        } catch (\Exception $e) {
            log_message('error', 'OpenAI Chatbot Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'I apologize, but I\'m having trouble processing your request right now. Please try again in a moment.',
                'technical_error' => $e->getMessage()
            ];
        }
    }


    /**
     * Call OpenAI API
     */
    private function callOpenAIAPI(string $userQuery, string $context): string
    {
        log_message('critical', 'OpenAIChatbot: Preparing OpenAI API request');
        
        $messages = [
            [
                'role' => 'system',
                'content' => $this->config->systemContext . "\n\n" . $this->config->responseFormat . "\n\nContext from documentation:\n" . $context
            ],
            [
                'role' => 'user',
                'content' => $userQuery . "\n\nPlease provide a helpful, step-by-step answer based on the context above. If the context doesn't fully answer the question, provide general guidance for travel management systems. Always be specific about menu navigation and actions."
            ]
        ];
        
        $requestData = [
            'model' => $this->config->model,
            'messages' => $messages,
            'temperature' => $this->config->temperature,
            'max_tokens' => $this->config->maxTokens
        ];

        log_message('critical', 'OpenAIChatbot: Request data prepared - model: ' . $this->config->model . ', max_tokens: ' . $this->config->maxTokens);

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];
        
        log_message('critical', 'OpenAIChatbot: Authorization header set with key: ' . substr($this->apiKey, 0, 10) . '...');
        
        // Add organization header if provided
        if (!empty($this->config->organizationId)) {
            $headers[] = 'OpenAI-Organization: ' . $this->config->organizationId;
            log_message('critical', 'OpenAIChatbot: Added organization header: ' . $this->config->organizationId);
        }

        log_message('critical', 'OpenAIChatbot: Making cURL request to: ' . $this->config->apiUrl);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->config->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        log_message('critical', 'OpenAIChatbot: cURL completed - HTTP Code: ' . $httpCode);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            log_message('critical', 'OpenAIChatbot: cURL error - ' . $error);
            curl_close($ch);
            throw new \Exception("Curl error: " . $error);
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            log_message('critical', 'OpenAIChatbot: API request failed - HTTP ' . $httpCode . ' - Response: ' . substr($response, 0, 500));
            throw new \Exception("API request failed with HTTP code: " . $httpCode . ". Response: " . $response);
        }

        log_message('critical', 'OpenAIChatbot: API response received - length: ' . strlen($response));
        
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('critical', 'OpenAIChatbot: JSON decode error - ' . json_last_error_msg());
            throw new \Exception("JSON decode error: " . json_last_error_msg());
        }
        
        if (!isset($responseData['choices'][0]['message']['content'])) {
            log_message('critical', 'OpenAIChatbot: Invalid API response format - ' . print_r($responseData, true));
            throw new \Exception("Invalid API response format");
        }

        $content = $responseData['choices'][0]['message']['content'];
        log_message('critical', 'OpenAIChatbot: Successfully extracted content from response');
        log_message('critical', 'OpenAIChatbot: API REPLY CONTENT: ' . $content);
        return $content;
    }


    /**
     * Process the AI response with quick actions
     */
    private function processResponse(string $response, array $searchResults): array
    {
        // Clean up the response
        $response = trim($response);
        
        // Return only the text - no steps or quick actions
        return [
            'text' => $response,
            'steps' => [], // Empty - no step extraction
            'quick_actions' => [], // Empty - no quick action buttons
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Extract numbered steps from response
     */
    private function extractStepsFromResponse(string $response): array
    {
        $steps = [];
        $lines = explode("\n", $response);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^(\d+)\.\s*(.+)$/', $line, $matches)) {
                $steps[] = [
                    'number' => $matches[1],
                    'text' => $matches[2]
                ];
            }
        }
        
        return $steps;
    }


    /**
     * Get suggested questions from knowledge base
     */
    public function getSuggestedQuestions(): array
    {
        return $this->knowledgeBase->getSuggestedQuestions();
    }

    /**
     * Get system status for admin
     */
    public function getSystemStatus(): array
    {
        $knowledgeStats = $this->knowledgeBase->getStatistics();
        
        return [
            'api_configured' => !empty($this->apiKey),
            'knowledge_base_topics' => $knowledgeStats['total_topics'],
            'total_workflows' => $knowledgeStats['total_workflows'],
            'total_keywords' => $knowledgeStats['total_keywords'],
            'cache_enabled' => $this->cache !== null,
            'last_update' => date('Y-m-d H:i:s')
        ];
    }
}