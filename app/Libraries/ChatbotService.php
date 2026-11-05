<?php

namespace App\Libraries;

use App\Models\ChatbotResponseModel;

class ChatbotService
{
    private $responseModel;

    public function __construct()
    {
        $this->responseModel = new ChatbotResponseModel();
    }

    /**
     * Create the chatbot_logs table if it doesn't exist
     */
    public function createTableIfNotExists(): bool
    {
        $db = \Config\Database::connect();
        
        if (!$db->tableExists('chatbot_logs')) {
            $forge = \Config\Database::forge();
            
            $forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true
                ],
                'user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false
                ],
                'query' => [
                    'type' => 'TEXT',
                    'null' => false
                ],
                'response' => [
                    'type' => 'LONGTEXT',
                    'null' => true
                ],
                'source' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                    'default' => 'gemini'
                ],
                'relevance_score' => [
                    'type' => 'DECIMAL',
                    'constraint' => '5,2',
                    'null' => true,
                    'default' => 0.00
                ],
                'processing_time' => [
                    'type' => 'DECIMAL',
                    'constraint' => '8,3',
                    'null' => true,
                    'default' => 0.000
                ],
                'ip_address' => [
                    'type' => 'VARCHAR',
                    'constraint' => 45,
                    'null' => true
                ],
                'user_agent' => [
                    'type' => 'TEXT',
                    'null' => true
                ],
                'session_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => true
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ]
            ]);
            
            $forge->addKey('id', true);
            $forge->addKey('user_id');
            $forge->addKey('created_at');
            $forge->addKey('source');
            
            return $forge->createTable('chatbot_logs');
        }
        
        return true;
    }

    /**
     * Save user query and response for analytics
     */
    public function saveResponse(string $query, array $response, int $userId = null, float $processingTime = null): bool
    {
        log_message('critical', 'ChatbotService: saveResponse called');
        
        try {
            // Ensure table exists
            log_message('critical', 'ChatbotService: Ensuring table exists');
            $this->createTableIfNotExists();
            
            $data = [
                'user_id' => $userId ?? session()->get('user_id') ?? 0,
                'query' => $query,
                'response' => json_encode($response),
                'source' => $response['source'] ?? 'openai',
                'processing_time' => $processingTime ?? 0.0,
                'ip_address' => service('request')->getIPAddress(),
                'user_agent' => service('request')->getUserAgent()->getAgentString(),
                'session_id' => session()->session_id ?? null
            ];
            
            log_message('critical', 'ChatbotService: Inserting data to database');
            $result = $this->responseModel->insert($data);
            log_message('critical', 'ChatbotService: Database insert result - ' . ($result ? 'SUCCESS' : 'FAILED'));
            
            return $result !== false;
        } catch (\Exception $e) {
            log_message('critical', 'ChatbotService: saveResponse error - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log user query for analytics
     */
    public function logQuery(array $logData): bool
    {
        $this->createTableIfNotExists();
        
        $data = [
            'user_id' => $logData['user_id'] ?? 0,
            'query' => $logData['query'],
            'source' => 'query_only',
            'ip_address' => $logData['ip_address'] ?? null,
            'user_agent' => $logData['user_agent'] ?? null,
            'session_id' => session()->session_id ?? null
        ];
        
        return $this->responseModel->insert($data) !== false;
    }

    /**
     * Get user's chat history
     */
    public function getUserHistory(int $userId, int $limit = 10): array
    {
        return $this->responseModel->where('user_id', $userId)
                   ->where('response IS NOT NULL')
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Get popular queries for analytics
     */
    public function getPopularQueries(int $limit = 20): array
    {
        $db = \Config\Database::connect();
        
        $query = $db->query("
            SELECT 
                query,
                COUNT(*) as frequency,
                AVG(processing_time) as avg_processing_time,
                MAX(created_at) as last_asked
            FROM chatbot_logs 
            WHERE response IS NOT NULL
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY query 
            ORDER BY frequency DESC, last_asked DESC
            LIMIT ?
        ", [$limit]);
        
        return $query->getResultArray();
    }

    /**
     * Get query statistics
     */
    public function getQueryStats(): array
    {
        $db = \Config\Database::connect();
        
        // Total queries
        $totalQueries = $this->responseModel->where('created_at >=', date('Y-m-d', strtotime('-30 days')))
                           ->countAllResults();
        
        // Successful responses
        $successfulQueries = $this->responseModel->where('created_at >=', date('Y-m-d', strtotime('-30 days')))
                                ->where('response IS NOT NULL')
                                ->countAllResults();
        
        // Average processing time
        $avgProcessingTime = $db->query("
            SELECT AVG(processing_time) as avg_time 
            FROM chatbot_logs 
            WHERE processing_time IS NOT NULL 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->getRow()->avg_time ?? 0;
        
        // Daily usage
        $dailyUsage = $db->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as queries
            FROM chatbot_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ")->getResultArray();
        
        // Top sources
        $topSources = $db->query("
            SELECT 
                source,
                COUNT(*) as count
            FROM chatbot_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY source
            ORDER BY count DESC
        ")->getResultArray();
        
        return [
            'total_queries_30_days' => $totalQueries,
            'successful_queries_30_days' => $successfulQueries,
            'success_rate' => $totalQueries > 0 ? round(($successfulQueries / $totalQueries) * 100, 2) : 0,
            'avg_processing_time' => round($avgProcessingTime, 3),
            'daily_usage_7_days' => $dailyUsage,
            'top_sources' => $topSources
        ];
    }

    /**
     * Get recent queries for admin review
     */
    public function getRecentQueries(int $limit = 50): array
    {
        return $this->responseModel->select('id, user_id, query, source, processing_time, created_at')
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Get queries that failed or had issues
     */
    public function getFailedQueries(int $limit = 20): array
    {
        return $this->responseModel->where('response IS NULL')
                   ->orWhere('source', 'error')
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Clean old logs (keep only last 90 days)
     */
    public function cleanOldLogs(): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime('-90 days'));
        
        return $this->responseModel->where('created_at <', $cutoffDate)->delete();
    }

    /**
     * Get query trends over time
     */
    public function getQueryTrends(int $days = 30): array
    {
        $db = \Config\Database::connect();
        
        $query = $db->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_queries,
                COUNT(CASE WHEN response IS NOT NULL THEN 1 END) as successful_queries,
                AVG(processing_time) as avg_processing_time
            FROM chatbot_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$days]);
        
        return $query->getResultArray();
    }

    /**
     * Get most active users
     */
    public function getMostActiveUsers(int $limit = 10): array
    {
        $db = \Config\Database::connect();
        
        $query = $db->query("
            SELECT 
                cl.user_id,
                u.Full_Name as user_name,
                COUNT(*) as query_count,
                MAX(cl.created_at) as last_query
            FROM chatbot_logs cl
            LEFT JOIN users u ON cl.user_id = u.User_ID
            WHERE cl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY cl.user_id
            ORDER BY query_count DESC
            LIMIT ?
        ", [$limit]);
        
        return $query->getResultArray();
    }

    /**
     * Export query data for analysis
     */
    public function exportQueryData(string $startDate, string $endDate): array
    {
        return $this->responseModel->where('created_at >=', $startDate)
                   ->where('created_at <=', $endDate)
                   ->orderBy('created_at', 'ASC')
                   ->findAll();
    }
}