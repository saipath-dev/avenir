<?php

namespace App\Controllers\AI;

use App\Controllers\BaseController;
use App\Libraries\OpenAIChatbot;
use App\Libraries\ChatbotService;

class ChatbotController extends BaseController
{
    private $openaiChatbot;
    private $chatbotService;

    public function __construct()
    {
        log_message('critical', 'ChatbotController: Starting constructor');
        
        try {
            $this->openaiChatbot = new OpenAIChatbot();
            log_message('critical', 'ChatbotController: OpenAIChatbot initialized successfully');
            
            $this->chatbotService = new ChatbotService();
            log_message('critical', 'ChatbotController: ChatbotService initialized successfully');
            
            log_message('critical', 'ChatbotController: Constructor completed successfully');
        } catch (\Exception $e) {
            log_message('critical', 'ChatbotController: Constructor error - ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Main chatbot interface
     */
    public function index()
    {
        // Check if user is logged in
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        $data = [
            'title' => 'Help Assistant',
            'suggested_questions' => $this->openaiChatbot->getSuggestedQuestions(),
            'system_status' => $this->openaiChatbot->getSystemStatus()
        ];

        return view('pages/ai/chatbot', $data);
    }

    /**
     * Process user query via AJAX
     */
    public function query()
    {
        log_message('critical', 'ChatbotController: Query method called');
        
        // Check if user is logged in
        if (!session()->get('logged_in')) {
            log_message('critical', 'ChatbotController: User not logged in');
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Please log in to use the help assistant.'
            ]);
        }

        try {
            $userQuery = $this->request->getPost('query');
            log_message('critical', 'ChatbotController: Received query - ' . substr($userQuery ?? 'NULL', 0, 100));
            
            if (empty($userQuery)) {
                log_message('critical', 'ChatbotController: Empty query received');
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Please enter a question.'
                ]);
            }

            // Clean and validate query
            $userQuery = strip_tags(trim($userQuery));
            log_message('critical', 'ChatbotController: Query cleaned - ' . substr($userQuery, 0, 100));
            
            if (strlen($userQuery) < 3) {
                log_message('critical', 'ChatbotController: Query too short');
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Please enter a more specific question (at least 3 characters).'
                ]);
            }

            // Log the query for analytics
            log_message('critical', 'ChatbotController: Logging user query');
            $this->logUserQuery($userQuery);

            // Process query through OpenAI
            log_message('critical', 'ChatbotController: Processing query through OpenAI');
            $result = $this->openaiChatbot->processQuery($userQuery);
            log_message('critical', 'ChatbotController: OpenAI processing completed - success: ' . ($result['success'] ? 'true' : 'false'));

            if ($result['success']) {
                // Save successful response for future reference
                $this->chatbotService->saveResponse($userQuery, $result['response']);
                
                return $this->response->setJSON([
                    'success' => true,
                    'response' => $result['response'],
                    'source' => $result['source'] ?? 'openai',
                    'relevant_docs' => $result['relevant_docs'] ?? 0
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => $result['error']
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Chatbot Query Error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'error' => 'I apologize, but I\'m having trouble right now. Please try again in a moment.'
            ]);
        }
    }

    /**
     * Get suggested questions based on category
     */
    public function suggestions($category = null)
    {
        $suggestions = $this->openaiChatbot->getSuggestedQuestions();
        
        if ($category && isset($suggestions[$category])) {
            return $this->response->setJSON([
                'success' => true,
                'suggestions' => $suggestions[$category]
            ]);
        }
        
        return $this->response->setJSON([
            'success' => true,
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Quick help for specific actions
     */
    public function quickHelp($action)
    {
        $quickHelps = [
            'create_quotation' => 'To create a tour quotation: Go to Tour → Quotation, fill in customer details and dates, add hotels and activities for each day, then finalize the quotation.',
            'process_payment' => 'To process a payment: Go to Accounts → Voucher Payments, select payment type (advance/settlement), enter amount and details, then submit for approval.',
            'assign_driver' => 'To assign a driver: Go to Dashboard → Tour Transport, find your tour, click "Assign Driver", select driver from list, then confirm allocation.',
            'send_hotel_email' => 'To send hotel reservations: Go to tour details, select dates needing confirmation, enter hotel email, compose message with tour details, then send.',
            'confirm_booking' => 'To confirm a booking: Go to Tour → Tour Details Search, find your tour, review all details, then click "Confirm Tour" to finalize.'
        ];

        if (isset($quickHelps[$action])) {
            return $this->response->setJSON([
                'success' => true,
                'help' => $quickHelps[$action]
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'error' => 'Help not available for this action.'
        ]);
    }

    /**
     * Get chat history for user
     */
    public function history()
    {
        if (!session()->get('logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Not authorized'
            ]);
        }

        $userId = session()->get('user_id');
        $history = $this->chatbotService->getUserHistory($userId, 10); // Last 10 queries

        return $this->response->setJSON([
            'success' => true,
            'history' => $history
        ]);
    }

    /**
     * Admin: View chatbot analytics
     */
    public function analytics()
    {
        // Check admin permissions
        if (!session()->get('logged_in') || session()->get('group_id') != 1) {
            return redirect()->to('/login');
        }

        $data = [
            'title' => 'Chatbot Analytics',
            'popular_queries' => $this->chatbotService->getPopularQueries(),
            'query_stats' => $this->chatbotService->getQueryStats(),
            'system_status' => $this->openaiChatbot->getSystemStatus(),
            'recent_queries' => $this->chatbotService->getRecentQueries(50)
        ];

        return view('pages/ai/analytics', $data);
    }

    /**
     * Admin: Manage responses
     */
    public function manageResponses()
    {
        if (!session()->get('logged_in') || session()->get('group_id') != 1) {
            return redirect()->to('/login');
        }

        if ($this->request->getMethod() === 'POST') {
            $action = $this->request->getPost('action');
            
            switch ($action) {
                case 'clear_cache':
                    cache()->clean();
                    session()->setFlashdata('success', 'Cache cleared successfully.');
                    break;
                    
                case 'update_config':
                    // Handle configuration updates
                    session()->setFlashdata('success', 'Configuration updated successfully.');
                    break;
            }
            
            return redirect()->to('/ai/chatbot/manage');
        }

        $data = [
            'title' => 'Manage Chatbot',
            'system_status' => $this->openaiChatbot->getSystemStatus(),
            'cache_info' => [
                'enabled' => cache() !== null,
                'size' => $this->getCacheSize()
            ]
        ];

        return view('pages/ai/manage', $data);
    }

    /**
     * Log user query for analytics
     */
    private function logUserQuery($query)
    {
        $logData = [
            'user_id' => session()->get('user_id'),
            'query' => $query,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString()
        ];

        $this->chatbotService->logQuery($logData);
    }

    /**
     * Get cache size for admin
     */
    private function getCacheSize()
    {
        // This is a simple estimate - actual implementation may vary
        $cacheDir = WRITEPATH . 'cache';
        $size = 0;
        
        if (is_dir($cacheDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($cacheDir)
            );
            
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }
        
        return $this->formatBytes($size);
    }

    /**
     * Format bytes for display
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }


    /**
     * Health check endpoint
     */
    public function health()
    {
        try {
            $status = $this->openaiChatbot->getSystemStatus();
            
            return $this->response->setJSON([
                'status' => 'healthy',
                'timestamp' => date('c'),
                'details' => $status
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'timestamp' => date('c'),
                'error' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}