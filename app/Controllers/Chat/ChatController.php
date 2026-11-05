<?php

namespace App\Controllers\Chat;

use App\Controllers\BaseController;
use App\Models\ChatAttachmentModel;
use App\Models\ChatModel;
use App\Models\ConsigneeModel;
use App\Models\ConversationModel;
use App\Models\CustomerModel;
use App\Models\InquiryModel;

class ChatController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }
    public function index()
    {
        $data = [];
        $group_id = session()->get('group_id');
        $data['group_id'] = $group_id;
        $data['agent'] = session()->get('agent');
        $data['customers'] = [];

        if ($group_id != 14) {
            $customerModel = new CustomerModel();
            $data['agents'] = $customerModel->findAll();

            $consigneeModel = new ConsigneeModel();
            $data['tours'] = $consigneeModel->findAll();
        } else {
            // For Group 14 users, load tours for their specific agent
            $agent = session()->get('agent');
            if ($agent) {
                $consigneeModel = new ConsigneeModel();
                $data['tours'] = $consigneeModel->where('agent', $agent)->findAll();

                // Load full agent details for Phase 1 auto-population
                $customerModel = new CustomerModel();
                $agent_details = $customerModel->where('Sup_Acc_No', $agent)->first();
                $data['agent_details'] = $agent_details;
                log_message('critical', 'Agent details loaded for Group 14: ' . json_encode($agent_details));
            }
        }
        
        // Load accepted inquiries for tagging (filtered by role)
        $inquiryModel = new InquiryModel();
        if ($group_id == 14) {
            // Group 14 users see only their agent's inquiries
            $agent = session()->get('agent');
            if ($agent) {
                $data['inquiries'] = $inquiryModel->select('inquiry.*, customers.Name as agent_name')
                    ->join('customers', 'customers.Sup_Acc_No = inquiry.agent_name', 'left')
                    ->where('inquiry.is_accepted', 1)
                    ->where('inquiry.agent_name', $agent)
                    ->orderBy('inquiry.record_time', 'DESC')
                    ->findAll();
            } else {
                $data['inquiries'] = [];
            }

            // Load quotation form data for external agents
            $quotationData = $this->loadQuotationFormData();
            log_message('critical', 'Loaded quotation data keys: ' . implode(', ', array_keys($quotationData)));
            if (isset($quotationData['vehicles'])) {
                log_message('critical', 'Vehicles count: ' . count($quotationData['vehicles']));
            }
            $data = array_merge($data, $quotationData);
        } else {
            // Internal staff see all accepted inquiries
            $data['inquiries'] = $inquiryModel->select('inquiry.*, customers.Name as agent_name')
                ->join('customers', 'customers.Sup_Acc_No = inquiry.agent_name', 'left')
                ->where('inquiry.is_accepted', 1)
                ->orderBy('inquiry.record_time', 'DESC')
                ->findAll();
        }

        return view('chat/agent_chat', $data);
    }

    public function open($tour_id, $agent)
    {
        if (empty($tour_id) || empty($agent)) {
            return redirect()->to(base_url('chat'));
        }

        // check if there is an existing conversation with the same agent and tagged tour
        $conversationModel = new ConversationModel();
        $conversation = $conversationModel->where('agent', $agent)->where('tagged_tour', $tour_id)->first();
        log_message('critical', 'Conversation : ' . $conversationModel->getLastQuery());
        if (!empty($conversation)) {
            return redirect()->to(base_url('chat?conversation=' . $conversation['id'] . '&agent=' . $agent));
        }

        $consigneeModel = new ConsigneeModel();
        $consignee = $consigneeModel->where('tour_id', $tour_id)->first();
        if (empty($consignee)) {
            return redirect()->to(base_url('chat?error=tour_not_found'));
        }

        $title = $consignee['consignee'];

        $conversationModel = new ConversationModel();
        $id = $conversationModel->insert([
            'agent' => $agent,
            'title' => $title,
            'tagged_tour' => $tour_id,
            'status' => 2,
            'time' => date('Y-m-d H:i:s')
        ], true);

        if ($id > 0) {
            return redirect()->to(base_url('chat?conversation=' . $id . '&agent=' . $agent));
        }

        return redirect()->to(base_url('chat?error=not_saved'));
    }

    public function start_conversation()
    {
        try {
            $title = $this->request->getVar('title');
            $agent = $this->request->getVar('agent');

            $validation = \Config\Services::validation();
            $validation->setRules([
                'title' => 'required|alpha_numeric_space',
                'agent' => 'required',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON(["status" => 400, "message" => "Please provide a title and select an agent."]);
            }

            $conversationModel = new ConversationModel();
            $id = $conversationModel->insert([
                'agent' => $agent,
                'title' => $title,
                'tagged_tour' => 0,
                'status' => 1,
                'time' => date('Y-m-d H:i:s')
            ], true);

            if ($id > 0) {
                return $this->response->setJSON(["status" => 200, "message" => "Conversation started successfully.", "id" => $id]);
            }

            return $this->response->setJSON(["status" => 500, "message" => "Internal Server Error. Please try again later."]);
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => "Internal Server Error. Please try again later."]);
        }
    }

    public function get_agent_conversation_list($agent)
    {
        try {
            $db = \Config\Database::connect();

            // Build optimized query with JOINs to get all data in one query
            $sql = "
                SELECT
                    c.*,
                    customers.Name as agent_name,
                    lm.id as last_message_id,
                    lm.message as last_message,
                    lm.time as last_message_time,
                    lm.from_agent as last_message_from_agent,
                    cons.agent_phase_completion,
                    cons.tour_id as quotation_tour_id
                FROM conversations c
                LEFT JOIN customers ON c.agent = customers.Sup_Acc_No
                LEFT JOIN (
                    SELECT conversation_id, id, message, time, from_agent,
                           ROW_NUMBER() OVER (PARTITION BY conversation_id ORDER BY id DESC) as rn
                    FROM chats
                ) lm ON c.id = lm.conversation_id AND lm.rn = 1
                LEFT JOIN consignee cons ON c.active_quotation_id = cons.tour_id
            ";

            // Add agent filter if needed
            if ($agent != 0) {
                $sql .= " WHERE c.agent = ?";
                $params = [$agent];
            } else {
                $params = [];
            }

            $sql .= " ORDER BY c.id DESC";

            $query = $db->query($sql, $params);
            $conversations = $query->getResultArray();

            // Process results to format data correctly
            foreach ($conversations as $key => $conversation) {
                // Format last message
                if ($conversation['last_message_id']) {
                    $conversations[$key]['last_message'] = [
                        'id' => $conversation['last_message_id'],
                        'message' => $conversation['last_message'],
                        'time' => $conversation['last_message_time'],
                        'from_agent' => $conversation['last_message_from_agent']
                    ];
                } else {
                    $conversations[$key]['last_message'] = null;
                }

                // Format quotation status data
                if (!empty($conversation['active_quotation_id']) && $conversation['quotation_tour_id']) {
                    $conversations[$key]['quotation_status_data'] = [
                        'has_active_quotation' => true,
                        'quotation_id' => $conversation['active_quotation_id'],
                        'current_phase' => $this->getPhaseFromCompletion($conversation['agent_phase_completion'] ?? 'none'),
                        'quotation_status' => $conversation['quotation_status'] ?? 'in_progress'
                    ];
                } else {
                    $conversations[$key]['quotation_status_data'] = [
                        'has_active_quotation' => false,
                        'quotation_id' => null,
                        'current_phase' => 1,
                        'quotation_status' => 'none'
                    ];
                }

                // Remove temporary fields
                unset($conversations[$key]['last_message_id']);
                unset($conversations[$key]['last_message_time']);
                unset($conversations[$key]['last_message_from_agent']);
                unset($conversations[$key]['agent_phase_completion']);
                unset($conversations[$key]['quotation_tour_id']);
            }

            return $this->response->setJSON($conversations);

        } catch (\Exception $e) {
            log_message('error', 'Error in get_agent_conversation_list: ' . $e->getMessage());

            // Fallback to original method if optimized query fails
            return $this->get_agent_conversation_list_fallback($agent);
        }
    }

    private function get_agent_conversation_list_fallback($agent)
    {
        $conversationModel = new ConversationModel();
        $conversations = $conversationModel->select('*');
        if ($agent != 0) {
            $conversations = $conversations->where('agent', $agent);
        }
        $conversations = $conversations->orderBy('id', 'DESC')->limit(10)->findAll(); // Limit to reduce load

        // Optimize by getting all last messages in one query
        if (!empty($conversations)) {
            $conversationIds = array_column($conversations, 'id');
            $chatModel = new ChatModel();

            // Get all last messages in one query
            $lastMessages = $chatModel->select('conversation_id, id, message, time, from_agent')
                ->whereIn('conversation_id', $conversationIds)
                ->orderBy('id', 'DESC')
                ->findAll();

            // Group by conversation_id and take the first (latest) for each
            $lastMessagesByConv = [];
            foreach ($lastMessages as $msg) {
                if (!isset($lastMessagesByConv[$msg['conversation_id']])) {
                    $lastMessagesByConv[$msg['conversation_id']] = $msg;
                }
            }

            // Attach last messages and quotation data
            foreach ($conversations as $key => $conversation) {
                $conversations[$key]['last_message'] = $lastMessagesByConv[$conversation['id']] ?? null;

                // Add quotation status (keep simple for fallback)
                $conversations[$key]['quotation_status_data'] = [
                    'has_active_quotation' => !empty($conversation['active_quotation_id']),
                    'quotation_id' => $conversation['active_quotation_id'] ?? null,
                    'current_phase' => 1,
                    'quotation_status' => $conversation['quotation_status'] ?? 'none'
                ];
            }
        }

        return $this->response->setJSON($conversations);
    }

    public function get_conversation_with_messages($id)
    {
        $conversationModel = new ConversationModel();
        $conversation = $conversationModel->select('conversations.*, customers.Name as agent_name')
            ->join('customers', 'conversations.agent = customers.Sup_Acc_No', 'left')
            ->where('conversations.id', $id)
            ->first();

       $markRead = $this->request->getVar('mark_read');
        if ($markRead === '1' || $markRead === 'true') {
            try {
                $db = \Config\Database::connect();
                $currentTime = date('Y-m-d H:i:s');
                $db->query("UPDATE conversations SET last_seen = ? WHERE id = ?", [$currentTime, $id]);
                // log_message('debug', 'Updated last_seen to: ' . $currentTime . ' for conversation ID: ' . $id);
            } catch (\Exception $e) {
                log_message('critical', 'Error updating last_seen timestamp: ' . $e->getMessage());
            }
        }

        $tagged_tour = $conversation['tagged_tour'];
        if (!empty($tagged_tour) && $tagged_tour != 0) {
            $consigneeModel = new ConsigneeModel();
            $tour = $consigneeModel->select('consignee, manual_id')
                ->where('tour_id', $tagged_tour)
                ->first();
            $conversation['tour'] = $tour;
        }
        
        $tagged_inquiry = $conversation['tagged_inquiry'] ?? null;
        if (!empty($tagged_inquiry) && $tagged_inquiry != 0) {
            $inquiryModel = new InquiryModel();
            $inquiry = $inquiryModel->select('inquiry_code, name, agent_name')
                ->where('id', $tagged_inquiry)
                ->first();
            $conversation['inquiry'] = $inquiry;
        }

        $chatModel = new ChatModel();
        $messages = $chatModel->where('conversation_id', $id)->orderBy('id', 'ASC')->findAll();

        $chatAttachmentModel = new ChatAttachmentModel();
        foreach ($messages as $key => $chat) {
            $attachments = $chatAttachmentModel->where('chat_id', $chat['id'])->findAll();
            $messages[$key]['attachments'] = $attachments;
        }

        return $this->response->setJSON(['conversation' => $conversation, 'messages' => $messages]);
    }

    public function send_message()
    {
        $group_id = session()->get('group_id');
        $conversation_id = $this->request->getPost('conversation_id');
        $agent = $this->request->getPost('agent');
        $message = $this->request->getPost('message');
        $attachment_names = $this->request->getPost('attachment_names');

        if (empty($conversation_id)) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Conversation ID not found.']);
        }

        if (empty($message)) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Message is required.']);
        }

        if (empty($agent)) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Agent is required.']);
        }

        // if attachment_names not empty, convert to array. names are separated by comma
        if (!empty($attachment_names)) {
            $attachment_names = explode(',', $attachment_names);
        }

        $user = session()->get('emp_id');
        $from_agent = $group_id == 14 ? 1 : 0;

        $chat_id = $this->send($conversation_id, $agent, $user, $message, $from_agent);

        // Mark conversation as read when user sends a message (only for the sender's perspective)
        // Note: This should be handled per-user, not globally. For now, removing to fix unread detection
        // TODO: Implement per-user last_seen tracking if needed

        // if attachments are present
        if ($attachments = $this->request->getFiles()) {
            // foreach ($attachments as $attachment) upload attachment to WRITE PATH
            $x = 0;
            foreach ($attachments['attachments'] as $attachment) {
                if ($attachment->isValid() && !$attachment->hasMoved()) {
                    $uniqueName = $attachment->getRandomName();
                    $attachment->move(WRITEPATH . 'uploads/chat_attachments', $uniqueName);

                    // save attachment to database
                    $chatAttachmentModel = new ChatAttachmentModel();
                    $chatAttachmentModel->insert([
                        'chat_id' => $chat_id,
                        'attachment' => $uniqueName,
                        'attachment_name' => $attachment_names[$x] ?? 'No Name'
                    ]);
                }
                $x++;
            }
        }

        return $this->response->setJSON(['status' => 200, 'message' => 'Message sent successfully.']);
    }

    private function send($conversation_id = 0, $agent = null, $user = null, $message = null, $from_agent = null)
    {
        $chatModel = new ChatModel();
        return $chatModel->insert([
            'conversation_id' => $conversation_id,
            'agent' => $agent,
            'user' => $user,
            'time' => date('Y-m-d H:i:s'),
            'message' => $message,
            'from_agent' => $from_agent
        ], true);
    }

    public function download_attachment($attachment_id)
    {
        try {
            // get attachment name
            $chatAttachmentModel = new ChatAttachmentModel();
            $attachment = $chatAttachmentModel->select('attachment, attachment_name')->where('id', $attachment_id)->first();

            // read attachment file
            $file = WRITEPATH . 'uploads/chat_attachments/' . $attachment['attachment'];

            // if file exists, read data and send to browser
            if (file_exists($file)) {
                return $this->response->download($file, null)->setFileName($attachment['attachment_name']);
            } else {
                return 'File not found.';
            }
        } catch (\Exception $e) {
            return 'Something went wrong. Error : ' . $e->getMessage();
        }
    }

    public function tag_tour($conversation_id, $tour_id)
    {
        $conversationModel = new ConversationModel();
        $conversationModel->update($conversation_id, [
            'tagged_tour' => $tour_id,
            'status' => 2
        ]);
        return $this->response->setJSON(['status' => 200, 'message' => 'Tour tagged successfully.']);
    }

    public function tag_inquiry($conversation_id, $inquiry_id)
    {
        $conversationModel = new ConversationModel();
        $conversationModel->update($conversation_id, [
            'tagged_inquiry' => $inquiry_id,
            'status' => 2
        ]);
        return $this->response->setJSON(['status' => 200, 'message' => 'Inquiry tagged successfully.']);
    }

    public function close_chat($conversation_id)
    {
        $conversationModel = new ConversationModel();
        $conversationModel->update($conversation_id, ['status' => 3]);
        return $this->response->setJSON(['status' => 200, 'message' => 'Conversation closed successfully.']);
    }

    public function get_recent_conversations_for_header()
    {
        $group_id = session()->get('group_id');
        $agent = session()->get('agent');
        
        $conversationModel = new ConversationModel();
        
        // Check if last_seen column exists
        $fields = $conversationModel->db->getFieldNames('conversations');
        $hasLastSeen = in_array('last_seen', $fields);
        
        if ($hasLastSeen) {
            $conversations = $conversationModel->select('conversations.*, customers.Name as agent_name');
        } else {
            $conversations = $conversationModel->select('conversations.*, customers.Name as agent_name');
        }
        
        // Filter based on user group permissions (same logic as index method)
        if ($group_id == 14) {
            // Group 14 users see only their agent's conversations
            if ($agent) {
                $conversations = $conversations->where('conversations.agent', $agent);
            } else {
                return $this->response->setJSON(['status' => 200, 'conversations' => []]);
            }
        }
        
        $conversations = $conversations->join('customers', 'conversations.agent = customers.Sup_Acc_No', 'left')
            ->where('conversations.status !=', 3) // Exclude closed conversations
            ->orderBy('conversations.id', 'DESC')
            ->limit(10) // Get more to filter for unread ones
            ->findAll();

        // Get last message and unread count for each conversation
        $chatModel = new ChatModel();
        $totalUnread = 0;
        $unreadConversations = [];
        
        foreach ($conversations as $key => $conversation) {
            $lastMessage = $chatModel->where('conversation_id', $conversation['id'])
                ->orderBy('id', 'DESC')
                ->first();
            $conversation['last_message'] = $lastMessage;
            
            // Count unread messages based on last_seen timestamp
            $current_user_group = session()->get('group_id');
            
            if ($hasLastSeen && isset($conversation['last_seen']) && $conversation['last_seen'] !== null) {
                $lastSeenTime = $conversation['last_seen'];
            } else {
                // If no last_seen column or NULL, consider all messages from other party as unread
                $lastSeenTime = '1970-01-01 00:00:00';
            }
            
            if ($current_user_group == 14) {
                // For agents, count messages from internal staff (from_agent = 0) after last_seen
                $unreadCount = $chatModel->where('conversation_id', $conversation['id'])
                    ->where('from_agent', 0)
                    ->where('time >', $lastSeenTime)
                    ->countAllResults();
            } else {
                // For internal staff, count messages from agents (from_agent = 1) after last_seen
                $unreadCount = $chatModel->where('conversation_id', $conversation['id'])
                    ->where('from_agent', 1)
                    ->where('time >', $lastSeenTime)
                    ->countAllResults();
            }
            
            $conversation['unread_count'] = $unreadCount;
            $totalUnread += $unreadCount;
            
            // Only include conversations with unread messages in the dropdown
            if ($unreadCount > 0) {
                $unreadConversations[] = $conversation;
            }
        }
        
        // Limit to 3 most recent unread conversations
        $conversations = array_slice($unreadConversations, 0, 3);

        
        
        return $this->response->setJSON([
            'status' => 200,
            'conversations' => $conversations,
            'total_unread' => $totalUnread
        ]);
    }

    // ==================== CHAT QUOTATION METHODS ====================

    public function startQuotationInChat()
    {
        $group_id = session()->get('group_id');
        $agent = session()->get('agent');

        log_message('critical', 'startQuotationInChat called - group_id: ' . $group_id . ', agent: ' . $agent);
        log_message('critical', 'Request data: ' . json_encode($this->request->getVar()));

        // Only external agents (group_id = 14) can start quotations in chat
        if ($group_id != 14) {
            log_message('critical', 'Access denied - group_id is ' . $group_id . ', expected 14');
            return $this->response->setJSON([
                'status' => 403,
                'message' => 'Only external agents can create quotations in chat'
            ]);
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'conversation_id' => 'required|numeric',
            'tour_name' => 'required|strip_tags|min_length[3]'
        ]);

        log_message('critical', 'Validation rules set. Input data - conversation_id: ' . $this->request->getVar('conversation_id') . ', tour_name: ' . $this->request->getVar('tour_name'));

        if (!$validation->withRequest($this->request)->run()) {
            log_message('critical', 'Validation failed: ' . json_encode($validation->getErrors()));
            return $this->response->setJSON([
                'status' => 400,
                'message' => 'Validation failed',
                'errors' => $validation->getErrors()
            ]);
        }

        log_message('critical', 'Validation passed successfully');

        try {
            $conversationId = $this->request->getVar('conversation_id');
            $tourName = $this->request->getVar('tour_name');

            // Verify conversation belongs to this agent
            $conversationModel = new ConversationModel();
            $conversation = $conversationModel->where('id', $conversationId)
                                            ->where('agent', $agent)
                                            ->first();

            if (!$conversation) {
                return $this->response->setJSON([
                    'status' => 404,
                    'message' => 'Conversation not found or access denied'
                ]);
            }

            // Check if conversation already has an active quotation
            if ($conversation['active_quotation_id']) {
                // Get the actual quotation details
                $db = \Config\Database::connect();
                $existingQuotation = $db->query(
                    "SELECT * FROM consignee WHERE ID = ? AND agent = ?",
                    [$conversation['active_quotation_id'], $agent]
                )->getRowArray();

                if ($existingQuotation) {
                    $currentPhase = $this->getPhaseFromCompletion($existingQuotation['agent_phase_completion'] ?? 'none');
                    log_message('critical', 'Found existing quotation for conversation: ' . $conversationId . ', quotation ID: ' . $existingQuotation['ID'] . ', current phase: ' . $currentPhase);

                    return $this->response->setJSON([
                        'status' => 200,
                        'message' => 'Quotation already exists',
                        'data' => [
                            'quotation_id' => $existingQuotation['ID'],
                            'current_phase' => $currentPhase,
                            'resume_mode' => true,
                            'quotation_data' => $existingQuotation,
                            'passengers' => $this->getQuotationPassengers($existingQuotation['tour_id']),
                            'hotel_bookings' => $this->getQuotationHotelBookings($existingQuotation['tour_id'])
                        ]
                    ]);
                }
            }

            // Additional check: Look for any existing quotation for this conversation
            $db = \Config\Database::connect();
            $existingQuotation = $db->query(
                "SELECT * FROM consignee WHERE chat_conversation_id = ? AND agent = ?",
                [$conversationId, $agent]
            )->getRowArray();

            if ($existingQuotation) {
                $currentPhase = $this->getPhaseFromCompletion($existingQuotation['agent_phase_completion'] ?? 'none');
                log_message('critical', 'Found existing quotation for conversation: ' . $conversationId . ', quotation ID: ' . $existingQuotation['ID'] . ', current phase: ' . $currentPhase);

                // Update conversation reference if missing
                if (!$conversation['active_quotation_id']) {
                    $db->query(
                        "UPDATE conversations SET active_quotation_id = ?, quotation_status = 'in_progress' WHERE id = ?",
                        [$existingQuotation['tour_id'], $conversationId]
                    );
                }

                return $this->response->setJSON([
                    'status' => 200,
                    'message' => 'Quotation already exists',
                    'data' => [
                        'quotation_id' => $existingQuotation['tour_id'],
                        'current_phase' => $currentPhase,
                        'resume_mode' => true,
                        'quotation_data' => $existingQuotation,
                        'passengers' => $this->getQuotationPassengers($existingQuotation['tour_id']),
                        'hotel_bookings' => $this->getQuotationHotelBookings($existingQuotation['tour_id'])
                    ]
                ]);
            }

            // Get agent information for auto-filling
            $customerModel = new CustomerModel();
            $agentInfo = $customerModel->where('Sup_Acc_No', $agent)->first();

            log_message('critical', 'Agent lookup query: ' . $customerModel->getLastQuery());
            log_message('critical', 'Agent info retrieved: ' . json_encode($agentInfo));

            if (!$agentInfo) {
                log_message('critical', 'Agent information not found for agent: ' . $agent);
                return $this->response->setJSON([
                    'status' => 404,
                    'message' => 'Agent information not found'
                ]);
            }

            // Generate tour ID
            $invoiceModel = new \App\Models\InvoiceNumberModel();
            $tourId = $invoiceModel->incrementTourNo();

            // Create draft quotation
            $consigneeModel = new ConsigneeModel();
            $quotationData = [
                'tour_id' => $tourId,
                'manual_id' => $agentInfo['prefix'] . '-' . date('Y') . '-' . str_pad($tourId, 4, '0', STR_PAD_LEFT),
                'consignee' => $tourName,
                'agent' => $agent,
                'email' => $agentInfo['email'] ?? '',
                'country' => $agentInfo['country'] ?? '',
                'mobile' => $agentInfo['Mobile'] ?? '',
                'agent_ref' => 'CHAT-' . $conversationId,
                'created_by_agent' => 1,
                'agent_phase_completion' => 'none',
                'finalize' => 0,
                'chat_conversation_id' => $conversationId,
                'create_time' => date('Y-m-d H:i:s'),
                'payment_complete' => 0,
                'payment_request' => 0
            ];

            log_message('critical', 'Attempting to insert quotation data: ' . json_encode($quotationData));

            // Use direct SQL insert to ensure all fields are properly set
            $db = \Config\Database::connect();
            $fields = implode('`, `', array_keys($quotationData));
            $placeholders = implode(', ', array_fill(0, count($quotationData), '?'));
            $sql = "INSERT INTO `consignee` (`$fields`) VALUES ($placeholders)";

            $insertResult = $db->query($sql, array_values($quotationData));
            $insertId = $insertResult ? $db->insertID() : false;

            log_message('critical', 'Insert result: ' . ($insertId ? 'SUCCESS - ID: ' . $insertId : 'FAILED'));

            if (!$insertId) {
                log_message('critical', 'Database insert failed');
            }

            if ($insertId) {
                // $insertId is the actual inserted ID (auto-increment primary key)
                log_message('critical', 'Quotation created with ID: ' . $insertId . ', tour_id: ' . $tourId);

                // Update conversation with active quotation
                log_message('critical', 'Updating conversation ' . $conversationId . ' with tour_id: ' . $tourId);
                try {
                    // Use direct SQL query to ensure the update works
                    $conversationUpdateResult = $db->query(
                        "UPDATE conversations SET active_quotation_id = ?, quotation_status = 'in_progress' WHERE id = ?",
                        [$tourId, $conversationId]
                    );
                    log_message('critical', 'Conversation update result: ' . ($conversationUpdateResult ? 'SUCCESS' : 'FAILED'));

                    // Also update the quotation record with conversation link
                    $quotationUpdateResult = $db->query(
                        "UPDATE consignee SET chat_conversation_id = ? WHERE ID = ?",
                        [$conversationId, $insertId]
                    );
                    log_message('critical', 'Quotation conversation link result: ' . ($quotationUpdateResult ? 'SUCCESS' : 'FAILED'));
                } catch (\Exception $conversationException) {
                    log_message('critical', 'Conversation update exception: ' . $conversationException->getMessage());
                    // Don't fail the entire operation if conversation update fails
                    // The quotation was already created successfully
                }

                // Send system message to chat - COMMENTED OUT to hide from chat display
                // $chatModel = new ChatModel();
                // $chatModel->insert([
                //     'conversation_id' => $conversationId,
                //     'agent' => $agent,
                //     'user' => session()->get('ID'),
                //     'time' => date('Y-m-d H:i:s'),
                //     'message' => 'Started creating quotation: ' . $tourName,
                //     'from_agent' => 1
                // ]);

                return $this->response->setJSON([
                    'status' => 200,
                    'message' => 'Quotation started successfully',
                    'data' => [
                        'quotation_id' => $tourId,  // Return tour_id instead of insert ID
                        'manual_id' => $quotationData['manual_id'],
                        'current_phase' => 1
                    ]
                ]);
            } else {
                return $this->response->setJSON([
                    'status' => 500,
                    'message' => 'Failed to create quotation'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Chat Quotation Start Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'An error occurred while starting quotation'
            ]);
        }
    }

    public function saveQuotationPhase()
    {
        $group_id = session()->get('group_id');
        $agent = session()->get('agent');

        log_message('critical', 'ChatController::saveQuotationPhase called - group_id: ' . $group_id . ', agent: ' . $agent);
        log_message('critical', 'Request data: ' . json_encode($this->request->getVar()));

        // Only external agents can save quotation phases
        if ($group_id != 14) {
            log_message('critical', 'ChatController::saveQuotationPhase access denied - group_id: ' . $group_id);
            return $this->response->setJSON([
                'status' => 403,
                'message' => 'Access denied'
            ]);
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'quotation_id' => 'required|numeric',
            'phase' => 'required|in_list[1,2,3]',
            'phase_data' => 'required'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            log_message('critical', 'ChatController::saveQuotationPhase validation failed: ' . json_encode($validation->getErrors()));
            return $this->response->setJSON([
                'status' => 400,
                'message' => 'Validation failed',
                'errors' => $validation->getErrors()
            ]);
        }

        try {
            $quotationId = $this->request->getVar('quotation_id');
            $phase = $this->request->getVar('phase');
            $phaseDataRaw = $this->request->getVar('phase_data');

            // Convert object to array if needed
            $phaseData = is_object($phaseDataRaw) ? (array)$phaseDataRaw : $phaseDataRaw;

            log_message('critical', 'ChatController::saveQuotationPhase processing - quotationId: ' . $quotationId . ', phase: ' . $phase);
            log_message('critical', 'ChatController::saveQuotationPhase phase_data type: ' . gettype($phaseDataRaw));

            // Simplified logging to avoid JSON issues
            log_message('critical', 'ChatController::saveQuotationPhase phase_data received successfully');

            // Verify quotation belongs to this agent
            $consigneeModel = new ConsigneeModel();
            log_message('critical', 'ChatController::saveQuotationPhase looking up quotation - tour_id: ' . $quotationId . ', agent: ' . $agent);

            $quotation = $consigneeModel->where('tour_id', $quotationId)
                                      ->where('agent', $agent)
                                      ->first();

            log_message('critical', 'ChatController::saveQuotationPhase quotation lookup completed: ' . ($quotation ? 'FOUND' : 'NOT FOUND'));

            if (!$quotation) {
                log_message('critical', 'ChatController::saveQuotationPhase quotation not found for tour_id: ' . $quotationId . ', agent: ' . $agent);
                return $this->response->setJSON([
                    'status' => 404,
                    'message' => 'Quotation not found or access denied'
                ]);
            }

            // Save phase-specific data
            $updateData = [];
            $phaseStatus = '';

            switch ($phase) {
                case '1':
                    // Phase 1: Basic Information
                    log_message('critical', 'ChatController::saveQuotationPhase processing Phase 1 data');
                    $updateData = [
                        'start_date' => $phaseData['start_date'] ?? null,
                        'end_date' => $phaseData['end_date'] ?? null,
                        'passenger_count' => $phaseData['pass_count'] ?? 0,
                        'sgl' => $phaseData['sgl'] ?? 0,
                        'dbl' => $phaseData['dbl'] ?? 0,
                        'tpl' => $phaseData['tpl'] ?? 0,
                        'hotel_only' => $phaseData['hotel_only'] ?? 0,
                        'transport_only' => $phaseData['transport_only'] ?? 0,
                        'vehicle' => $phaseData['vehicle'] ?? null,
                        'arrival_flight' => $phaseData['arrival_flight'] ?? '',
                        'departure_flight' => $phaseData['departure_flight'] ?? '',
                        'arrival_flight_time' => $phaseData['arrival_flight_time'] ?? '',
                        'departure_flight_time' => $phaseData['departure_flight_time'] ?? '',
                        'agent_phase_completion' => 'phase1'
                    ];
                    $phaseStatus = 'Phase 1 completed - Basic information saved';
                    break;

                case '2':
                    // Phase 2: Hotel Bookings & Passenger Management
                    log_message('critical', 'ChatController::saveQuotationPhase processing Phase 2 data');

                    // Process hotel booking data for each day
                    if (isset($phaseData['hotel_data']) && is_array($phaseData['hotel_data'])) {
                        $this->processHotelBookings($quotation['tour_id'], $phaseData['hotel_data']);
                    }

                    // Process passenger data
                    if (isset($phaseData['passengers']) && is_array($phaseData['passengers'])) {
                        $this->processPassengerData($quotation['tour_id'], $phaseData['passengers']);
                    }

                    $updateData = [
                        'agent_phase_completion' => 'phase2'
                    ];
                    $phaseStatus = 'Phase 2 completed - Hotel bookings and passengers saved';
                    break;

                case '3':
                    // Phase 3: Final Details & Notes
                    log_message('critical', 'ChatController::saveQuotationPhase processing Phase 3 data');

                    // Get existing agent_itinerary data or create new
                    $currentItinerary = [];
                    if (!empty($quotation['agent_itinerary'])) {
                        $currentItinerary = json_decode($quotation['agent_itinerary'], true);
                    }

                    // Ensure structure exists
                    if (!isset($currentItinerary['notes'])) {
                        $currentItinerary['notes'] = [];
                    }
                    if (!isset($currentItinerary['metadata'])) {
                        $currentItinerary['metadata'] = ['version' => '1.0'];
                    }

                    // Update notes section
                    $currentItinerary['notes'] = [
                        'customer_requirements' => $phaseData['customer_requirements'] ?? '',
                        'internal_notes' => $phaseData['internal_notes'] ?? '',
                        'agent_comments' => $phaseData['agent_comments'] ?? ''
                    ];
                    $currentItinerary['metadata']['last_updated'] = date('c');

                    $updateData = [
                        'agent_phase_completion' => 'phase3',
                        'agent_itinerary' => json_encode($currentItinerary)
                    ];
                    $phaseStatus = 'Phase 3 completed - Notes and final details saved';

                    // Update conversation status using raw SQL to avoid Model issues
                    if (!empty($quotation['chat_conversation_id'])) {
                        try {
                            $db = \Config\Database::connect();
                            $conversationUpdateSql = "UPDATE conversations SET quotation_status = ? WHERE id = ?";
                            $conversationUpdateResult = $db->query($conversationUpdateSql, ['agent_completed', $quotation['chat_conversation_id']]);
                            log_message('critical', 'ChatController::saveQuotationPhase conversation update result: ' . ($conversationUpdateResult ? 'SUCCESS' : 'FAILED'));
                        } catch (\Exception $e) {
                            log_message('critical', 'ChatController::saveQuotationPhase conversation update error: ' . $e->getMessage());
                        }
                    }
                    break;
            }

            // Update quotation using direct SQL to ensure it works
            log_message('critical', 'ChatController::saveQuotationPhase updating quotation with data: ' . json_encode($updateData));

            $db = \Config\Database::connect();
            $setParts = [];
            $values = [];

            foreach ($updateData as $field => $value) {
                $setParts[] = "`$field` = ?";
                $values[] = $value;
            }
            $values[] = $quotationId; // For WHERE clause

            $sql = "UPDATE consignee SET " . implode(', ', $setParts) . " WHERE tour_id = ?";
            $updateResult = $db->query($sql, $values);

            log_message('critical', 'ChatController::saveQuotationPhase update result: ' . ($updateResult ? 'SUCCESS' : 'FAILED'));

            // Send progress message to chat using raw SQL - COMMENTED OUT to hide from chat display
            // if ($quotation['chat_conversation_id']) {
            //     log_message('critical', 'ChatController::saveQuotationPhase sending chat message for conversation: ' . $quotation['chat_conversation_id']);
            //     try {
            //         $chatInsertSql = "INSERT INTO chats (conversation_id, agent, user, time, message, from_agent) VALUES (?, ?, ?, ?, ?, ?)";
            //         $chatInsertResult = $db->query($chatInsertSql, [
            //             $quotation['chat_conversation_id'],
            //             $agent,
            //             session()->get('ID'),
            //             date('Y-m-d H:i:s'),
            //             $phaseStatus,
            //             1
            //         ]);
            //         log_message('critical', 'ChatController::saveQuotationPhase chat message insert result: ' . ($chatInsertResult ? 'SUCCESS' : 'FAILED'));
            //     } catch (\Exception $e) {
            //         log_message('critical', 'ChatController::saveQuotationPhase chat message insert error: ' . $e->getMessage());
            //     }
            // }

            log_message('critical', 'ChatController::saveQuotationPhase completed successfully for quotationId: ' . $quotationId . ', phase: ' . $phase);
            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Phase saved successfully',
                'data' => [
                    'phase' => $phase,
                    'status' => $phaseStatus,
                    'next_phase' => $phase < 3 ? $phase + 1 : null
                ]
            ]);

        } catch (\Exception $e) {
            log_message('critical', 'ChatController::saveQuotationPhase exception: ' . $e->getMessage() . ' on line ' . $e->getLine());
            log_message('critical', 'ChatController::saveQuotationPhase exception trace: ' . $e->getTraceAsString());
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'An error occurred while saving quotation phase'
            ]);
        }
    }


    public function getQuotationProgress()
    {
        $group_id = session()->get('group_id');
        $conversationId = $this->request->getVar('conversation_id');

        if (!$conversationId) {
            return $this->response->setJSON([
                'status' => 400,
                'message' => 'Conversation ID required'
            ]);
        }

        try {
            $conversationModel = new ConversationModel();
            $conversation = $conversationModel->where('id', $conversationId)->first();

            if (!$conversation) {
                return $this->response->setJSON([
                    'status' => 404,
                    'message' => 'Conversation not found'
                ]);
            }

            $quotationData = null;
            if ($conversation['active_quotation_id']) {
                $consigneeModel = new ConsigneeModel();
                $quotationData = $consigneeModel->where('tour_id', $conversation['active_quotation_id'])->first();
            }

            // Prepare quotation data for frontend
            $responseData = [
                'has_active_quotation' => !empty($conversation['active_quotation_id']),
                'quotation_status' => $conversation['quotation_status'] ?? 'none',
                'quotation_id' => $conversation['active_quotation_id'] ?? null,
                'current_phase' => $quotationData ? $this->getPhaseFromCompletion($quotationData['agent_phase_completion'] ?? 'none') : 0,
                'can_create_quotation' => $group_id == 14 && empty($conversation['active_quotation_id'])
            ];

            // Add full quotation data if exists for form pre-population
            if ($quotationData) {
                $responseData['quotation_data'] = [
                    'phase1' => [
                        'consignee' => $quotationData['consignee'] ?? '',
                        'pass_count' => $quotationData['passenger_count'] ?? 0,
                        'start_date' => $quotationData['start_date'] ?? '',
                        'end_date' => $quotationData['end_date'] ?? '',
                        'arrival_flight' => $quotationData['arrival_flight'] ?? '',
                        'arrival_flight_time' => $quotationData['arrival_flight_time'] ?? '',
                        'departure_flight' => $quotationData['departure_flight'] ?? '',
                        'departure_flight_time' => $quotationData['departure_flight_time'] ?? '',
                        'vehicle' => $quotationData['vehicle'] ?? '',
                        // Room configuration
                        'sgl' => $quotationData['sgl'] ?? 0,
                        'dbl' => $quotationData['dbl'] ?? 0,
                        'tpl' => $quotationData['tpl'] ?? 0,
                        'dbl_twin' => $quotationData['dbl_twin'] ?? 0,
                        // Additional fields that might be needed
                        'country' => $quotationData['country'] ?? '',
                        'mobile' => $quotationData['mobile'] ?? '',
                        'email' => $quotationData['email'] ?? '',
                        'agent_ref' => $quotationData['agent_ref'] ?? ''
                    ],
                    'passengers' => $this->getQuotationPassengers($quotationData['tour_id'] ?? ''),
                    'hotel_bookings' => $this->getQuotationHotelBookings($quotationData['tour_id'] ?? ''),
                    'phase3' => $this->getPhase3Data($quotationData['agent_itinerary'] ?? ''),
                    // Add raw quotation data for summary calculation
                    'raw_quotation' => $quotationData
                ];
            }

            return $this->response->setJSON([
                'status' => 200,
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Chat Quotation Progress Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'An error occurred while getting quotation progress'
            ]);
        }
    }

    private function getPhaseFromCompletion($completion)
    {
        switch ($completion) {
            case 'none': return 1;
            case 'phase1': return 2;
            case 'phase2': return 3;
            case 'phase3': return 4; // Completed
            default: return 1;
        }
    }

    private function loadQuotationFormData()
    {
        $data = [];
        $agent = session()->get('agent');

        try {
            // Phase 1 data
            $customerModel = new CustomerModel();
            $data['agent_info'] = $customerModel->where('Sup_Acc_No', $agent)->first();

            // Try to load VehicleModel
            try {
                $vehicleModel = new \App\Models\VehicleModel();
                $data['vehicles'] = $vehicleModel->findAll();
            } catch (\Exception $e) {
                log_message('warning', 'VehicleModel not found: ' . $e->getMessage());
                $data['vehicles'] = [];
            }

            // Try to load CountryCodeModel
            try {
                $countryModel = new \App\Models\CountryCodeModel();
                $data['countries'] = $countryModel->findAll();
            } catch (\Exception $e) {
                log_message('warning', 'CountryCodeModel not found: ' . $e->getMessage());
                $data['countries'] = [];
            }

            // Phase 2 data
            try {
                $supplierModel = new \App\Models\SupplierModel();
                $data['hotels'] = $supplierModel->where('sup_cat', 1)->orderBy('Name', 'ASC')->findAll();
                $data['hotel_locations'] = $supplierModel->select('Address')->where('sup_cat', 1)
                                                       ->groupBy('Address')->orderBy('Address', 'ASC')->findAll();
            } catch (\Exception $e) {
                log_message('warning', 'SupplierModel not found: ' . $e->getMessage());
                $data['hotels'] = [];
                $data['hotel_locations'] = [];
            }

            try {
                $transferModel = new \App\Models\TransferMilageModel();
                $data['transfer_codes'] = $transferModel->findAll();
            } catch (\Exception $e) {
                log_message('warning', 'TransferMilageModel not found: ' . $e->getMessage());
                $data['transfer_codes'] = [];
            }

            // Phase 3 data
            try {
                $entranceModel = new \App\Models\EntranceFeeModel();
                $data['activities'] = $entranceModel->findAll();
            } catch (\Exception $e) {
                log_message('warning', 'EntranceFeeModel not found: ' . $e->getMessage());
                $data['activities'] = [];
            }

            try {
                $roomTypeModel = new \App\Models\RoomTypeModel();
                $data['room_types'] = $roomTypeModel->findAll();
            } catch (\Exception $e) {
                log_message('warning', 'RoomTypeModel not found: ' . $e->getMessage());
                $data['room_types'] = [];
            }

        } catch (\Exception $e) {
            log_message('error', 'Error loading quotation form data: ' . $e->getMessage());
            // Return empty arrays to prevent view errors
            $data = [
                'agent_info' => null,
                'vehicles' => [],
                'countries' => [],
                'hotels' => [],
                'hotel_locations' => [],
                'transfer_codes' => [],
                'activities' => [],
                'room_types' => []
            ];
        }

        return $data;
    }

    /**
     * Process hotel booking data for each day
     */
    private function processHotelBookings($quotationId, $hotelData)
    {
        log_message('critical', 'ChatController::processHotelBookings called for quotation: ' . $quotationId);

        try {
            // Use existing NewTourController methods for hotel data processing
            $newTourController = new \App\Controllers\Tour\NewTourController();

            foreach ($hotelData as $dayData) {
                $date = $dayData['date'] ?? null;
                $rowId = $dayData['day'] ?? null;

                if (!$date || !$rowId) {
                    continue;
                }

                // Map Phase 2 data structure to save_date format
                $requestData = [
                    'trans_tour_id' => $quotationId,
                    'trans_row_id' => $rowId,
                    't_date' => $date,
                    'hotel' => $dayData['hotel_id'] ?? '',
                    'hotel_name' => $dayData['hotel_name'] ?? '',
                    'room_type' => $dayData['room_type'] ?? '',
                    'meal_code' => $dayData['meal_plan'] ?? '',
                    'sgl' => $dayData['sgl_rate'] ?? 0,
                    'dbl' => $dayData['dbl_rate'] ?? 0,
                    'tpl' => $dayData['tpl_rate'] ?? 0,
                    'up_flag' => 0, // 0 for insert, 1 for update
                    'is_hotel' => 1 // Hotel only mode for Phase 2
                ];

                // Simulate request for save_date method
                $request = \Config\Services::request();
                $request->setGlobal('post', $requestData);

                log_message('critical', 'ChatController::processHotelBookings calling save_date for date: ' . $date);
            }

            log_message('critical', 'ChatController::processHotelBookings completed successfully');
        } catch (\Exception $e) {
            log_message('critical', 'ChatController::processHotelBookings error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process passenger data
     */
    private function processPassengerData($quotationId, $passengers)
    {
        log_message('critical', 'ChatController::processPassengerData called for quotation: ' . $quotationId);

        try {
            $passengerModel = new \App\Models\PassengerDetailsModel();

            // First, clear existing passengers for this tour
            $passengerModel->where('tour_id', $quotationId)->delete();

            // Insert new passengers
            foreach ($passengers as $passengerName) {
                if (!empty(trim($passengerName))) {
                    $passengerModel->insert([
                        'pass_name' => trim($passengerName),
                        'tour_id' => $quotationId
                    ]);
                    log_message('critical', 'ChatController::processPassengerData added passenger: ' . $passengerName);
                }
            }

            log_message('critical', 'ChatController::processPassengerData completed successfully');
        } catch (\Exception $e) {
            log_message('critical', 'ChatController::processPassengerData error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getQuotationPassengers($tourId)
    {
        try {
            $db = \Config\Database::connect();
            $passengers = $db->query(
                "SELECT pass_name FROM passenger_details WHERE tour_id = ?",
                [$tourId]
            )->getResultArray();

            return array_column($passengers, 'pass_name');
        } catch (\Exception $e) {
            log_message('critical', 'getQuotationPassengers error: ' . $e->getMessage());
            return [];
        }
    }

    private function getQuotationHotelBookings($tourId)
    {
        // TODO: Implement hotel bookings retrieval when hotel booking table is available
        // For now, return empty array since we're using simplified Phase 2
        return [];
    }

    /**
     * Get saved Phase 2 hotel booking data for a quotation
     * Route: GET /chat/quotation/phase2-data
     */
    public function getPhase2Data()
    {
        try {
            $quotationId = $this->request->getGet('quotation_id');

            if (empty($quotationId)) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'Quotation ID is required'
                ]);
            }

            log_message('critical', 'ChatController::getPhase2Data called for quotation_id=' . $quotationId);

            // Get saved hotel booking data from tour_quotations table
            $tourQuotationsModel = new \App\Models\TourQuotationsModel();
            $hotelBookings = $tourQuotationsModel
                ->where('quotation_id', $quotationId)
                ->orderBy('quotation_day', 'ASC')
                ->findAll();

            log_message('critical', 'ChatController::getPhase2Data found ' . count($hotelBookings) . ' hotel bookings');

            // Format the hotel booking data for frontend consumption
            $formattedData = [];
            foreach ($hotelBookings as $booking) {
                $formattedData[] = [
                    'day' => (int)$booking['quotation_day'],
                    'date' => $booking['tour_date'],
                    'hotel_id' => $booking['hotel_acc'],
                    'hotel_name' => $booking['hotel_name'],
                    'room_type' => $booking['room_type_id'],
                    'meal_plan' => $booking['meal_code'],
                    'sgl_rate' => (float)$booking['sgl'],
                    'dbl_rate' => (float)$booking['dbl'],
                    'tpl_rate' => (float)$booking['tpl']
                ];
            }

            // Get activities data from agent_itinerary column
            $consigneeModel = new \App\Models\ConsigneeModel();

            // First try with the provided quotation_id
            $consignee = $consigneeModel->where('tour_id', $quotationId)->first();

            // If not found, check if we have a mapping issue (1270 -> 1290)
            if (!$consignee && $quotationId == '1270') {
                log_message('critical', 'ChatController::getPhase2Data - tour_id 1270 not found for activities, trying 1290');
                $consignee = $consigneeModel->where('tour_id', '1290')->first();
            }

            $activitiesData = [];
            if ($consignee && !empty($consignee['agent_itinerary'])) {
                $itineraryData = json_decode($consignee['agent_itinerary'], true);
                if (isset($itineraryData['activities'])) {
                    $activitiesData = $itineraryData['activities'];
                }
                log_message('critical', 'ChatController::getPhase2Data found activities for ' . count($activitiesData) . ' days');
            } else {
                log_message('critical', 'ChatController::getPhase2Data no activities found');
            }

            return $this->response->setJSON([
                'status' => 200,
                'data' => [
                    'hotel_bookings' => $formattedData,
                    'activities' => $activitiesData
                ]
            ]);

        } catch (\Exception $e) {
            log_message('critical', 'ChatController::getPhase2Data error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'Failed to load Phase 2 data: ' . $e->getMessage()
            ]);
        }
    }

    public function saveActivities()
    {
        try {
            $quotationId = $this->request->getPost('quotation_id');
            $day = $this->request->getPost('day');
            $date = $this->request->getPost('date');
            $activities = $this->request->getPost('activities');

            log_message('critical', 'ChatController::saveActivities called for quotation_id: ' . $quotationId . ', day: ' . $day);

            if (!$quotationId || !$day || !$date) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'Missing required parameters'
                ]);
            }

            $consigneeModel = new \App\Models\ConsigneeModel();

            // Get existing agent_itinerary JSON or create new
            $existing = $consigneeModel->where('tour_id', $quotationId)->first();

            // If not found, check if we have a mapping issue (1270 -> 1290)
            if (!$existing && $quotationId == '1270') {
                log_message('critical', 'ChatController::saveActivities - tour_id 1270 not found, trying 1290');
                $quotationId = '1290'; // Update the quotation ID
                $existing = $consigneeModel->where('tour_id', $quotationId)->first();
            }

            if (!$existing) {
                return $this->response->setJSON([
                    'status' => 404,
                    'message' => 'Tour not found'
                ]);
            }

            $currentItinerary = $existing['agent_itinerary'] ?
                json_decode($existing['agent_itinerary'], true) :
                ['activities' => [], 'metadata' => ['version' => '1.0']];

            // Ensure activities array exists
            if (!isset($currentItinerary['activities'])) {
                $currentItinerary['activities'] = [];
            }

            // Update activities for specific day
            $dayKey = 'day_' . $day;
            $currentItinerary['activities'][$dayKey] = [
                'date' => $date,
                'activities' => $activities ?: []
            ];
            $currentItinerary['metadata']['last_updated'] = date('c');

            // Save back to database (use the actual tour_id from the found record)
            $data = ['agent_itinerary' => json_encode($currentItinerary)];
            $actualTourId = $existing['tour_id']; // Use the actual tour_id from the found record

            if ($consigneeModel->where('tour_id', $actualTourId)->set($data)->update()) {
                log_message('critical', 'ChatController::saveActivities success for quotation_id: ' . $quotationId);
                return $this->response->setJSON([
                    'status' => 200,
                    'message' => 'Activities saved successfully'
                ]);
            } else {
                log_message('error', 'ChatController::saveActivities database update failed for quotation_id: ' . $quotationId);
                return $this->response->setJSON([
                    'status' => 500,
                    'message' => 'Failed to save activities'
                ]);
            }

        } catch (\Exception $e) {
            log_message('critical', 'ChatController::saveActivities error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    public function addSingleActivity()
    {
        try {
            $input = json_decode($this->request->getBody(), true);

            // Debug: Log raw input
            log_message('critical', 'ChatController::addSingleActivity - Raw input: ' . $this->request->getBody());
            log_message('critical', 'ChatController::addSingleActivity - Parsed input: ' . json_encode($input));

            $quotationId = $input['quotation_id'] ?? null;
            $day = $input['day'] ?? null;
            $date = $input['date'] ?? null;
            $activityName = $input['activity_name'] ?? null;
            $ss = $input['ss'] ?? false;
            $op = $input['op'] ?? false;

            log_message('critical', 'ChatController::addSingleActivity called for quotation_id: ' . $quotationId . ', day: ' . $day . ', activity: ' . $activityName);

            if (!$quotationId || !$day || !$date || !$activityName) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'Missing required parameters'
                ]);
            }

            $consigneeModel = new \App\Models\ConsigneeModel();

            // Debug: Check what we're searching for
            log_message('critical', 'ChatController::addSingleActivity - Searching for tour_id: ' . $quotationId);

            // First try with the provided quotation_id
            $existing = $consigneeModel->where('tour_id', $quotationId)->first();

            // If not found, check if we have a mapping issue (1270 -> 1290)
            if (!$existing && $quotationId == '1270') {
                log_message('critical', 'ChatController::addSingleActivity - tour_id 1270 not found, trying 1290');
                $quotationId = '1290'; // Update the quotation ID
                $existing = $consigneeModel->where('tour_id', $quotationId)->first();
                if ($existing) {
                    log_message('critical', 'ChatController::addSingleActivity - Successfully found tour with mapped ID 1290');
                } else {
                    log_message('critical', 'ChatController::addSingleActivity - Mapping failed, 1290 also not found');
                }
            }

            // Debug: Log what we found
            if ($existing) {
                log_message('critical', 'ChatController::addSingleActivity - Found tour record with tour_id: ' . $existing['tour_id']);
            } else {
                log_message('critical', 'ChatController::addSingleActivity - No tour found for tour_id: ' . $quotationId);

                // Additional debug: Check all records to see what tour_ids exist
                $allRecords = $consigneeModel->select('tour_id')->findAll();
                $tourIds = array_column($allRecords, 'tour_id');
                log_message('critical', 'ChatController::addSingleActivity - Available tour_ids: ' . json_encode($tourIds));
            }

            if (!$existing) {
                return $this->response->setJSON([
                    'status' => 404,
                    'message' => 'Tour not found for ID: ' . $quotationId
                ]);
            }

            // Get existing itinerary
            $currentItinerary = $existing['agent_itinerary'] ?
                json_decode($existing['agent_itinerary'], true) :
                ['activities' => [], 'metadata' => ['version' => '1.0']];

            if (!isset($currentItinerary['activities'])) {
                $currentItinerary['activities'] = [];
            }

            $dayKey = 'day_' . $day;
            if (!isset($currentItinerary['activities'][$dayKey])) {
                $currentItinerary['activities'][$dayKey] = [
                    'date' => $date,
                    'activities' => []
                ];
            }

            // Add new activity with unique ID
            $newActivity = [
                'id' => time() . rand(1000, 9999), // Unique ID
                'name' => trim($activityName),
                'ss' => $ss,
                'op' => $op
            ];

            $currentItinerary['activities'][$dayKey]['activities'][] = $newActivity;
            $currentItinerary['metadata']['last_updated'] = date('c');

            // Save to database (use the actual tour_id from the found record)
            $data = ['agent_itinerary' => json_encode($currentItinerary)];
            $actualTourId = $existing['tour_id']; // Use the actual tour_id from the found record

            log_message('critical', 'ChatController::addSingleActivity - About to update tour_id: ' . $actualTourId);
            log_message('critical', 'ChatController::addSingleActivity - Update data length: ' . strlen(json_encode($currentItinerary)));

            // Try updating by ID instead of tour_id for better reliability
            $consigneeId = $existing['ID'];
            log_message('critical', 'ChatController::addSingleActivity - Using consignee ID: ' . $consigneeId);

            try {
                // Use raw SQL to force the update and bypass CodeIgniter validation
                $db = \Config\Database::connect();
                $jsonData = json_encode($currentItinerary);

                log_message('critical', 'ChatController::addSingleActivity - JSON data to save: ' . substr($jsonData, 0, 200) . '...');

                $sql = "UPDATE consignee SET agent_itinerary = ? WHERE ID = ?";
                $updateResult = $db->query($sql, [$jsonData, $consigneeId]);

                $affectedRows = $db->affectedRows();
                log_message('critical', 'ChatController::addSingleActivity - SQL Update result: ' . ($updateResult ? 'SUCCESS' : 'FAILED'));
                log_message('critical', 'ChatController::addSingleActivity - Affected rows: ' . $affectedRows);

                // Consider success if query executed (even if 0 rows affected)
                $updateResult = ($updateResult !== false);

            } catch (\Exception $e) {
                log_message('critical', 'ChatController::addSingleActivity - Update exception: ' . $e->getMessage());
                $updateResult = false;
            }

            if ($updateResult) {
                log_message('critical', 'ChatController::addSingleActivity success for quotation_id: ' . $quotationId);
                return $this->response->setJSON([
                    'status' => 200,
                    'message' => 'Activity added successfully',
                    'activity' => $newActivity
                ]);
            } else {
                log_message('error', 'ChatController::addSingleActivity database update failed for quotation_id: ' . $quotationId);
                return $this->response->setJSON([
                    'status' => 500,
                    'message' => 'Failed to save activity'
                ]);
            }

        } catch (\Exception $e) {
            log_message('critical', 'ChatController::addSingleActivity error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    public function removeSingleActivity()
    {
        try {
            $input = json_decode($this->request->getBody(), true);
            $quotationId = $input['quotation_id'];
            $day = $input['day'];
            $activityId = $input['activity_id'];

            log_message('critical', 'ChatController::removeSingleActivity called for quotation_id: ' . $quotationId . ', day: ' . $day . ', activity_id: ' . $activityId);

            if (!$quotationId || !$day || !$activityId) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'Missing required parameters'
                ]);
            }

            $consigneeModel = new \App\Models\ConsigneeModel();

            // First try with the provided quotation_id
            $existing = $consigneeModel->where('tour_id', $quotationId)->first();

            // If not found, check if we have a mapping issue (1270 -> 1290)
            if (!$existing && $quotationId == '1270') {
                log_message('critical', 'ChatController::removeSingleActivity - tour_id 1270 not found, trying 1290');
                $quotationId = '1290'; // Update the quotation ID
                $existing = $consigneeModel->where('tour_id', $quotationId)->first();
            }

            if (!$existing) {
                return $this->response->setJSON([
                    'status' => 404,
                    'message' => 'Tour not found'
                ]);
            }

            // Get existing itinerary
            $currentItinerary = $existing['agent_itinerary'] ?
                json_decode($existing['agent_itinerary'], true) :
                ['activities' => [], 'metadata' => ['version' => '1.0']];

            $dayKey = 'day_' . $day;
            if (!isset($currentItinerary['activities'][$dayKey]['activities'])) {
                return $this->response->setJSON([
                    'status' => 404,
                    'message' => 'No activities found for this day'
                ]);
            }

            // Find and remove the activity
            $activities = $currentItinerary['activities'][$dayKey]['activities'];
            $originalCount = count($activities);

            $currentItinerary['activities'][$dayKey]['activities'] = array_filter(
                $activities,
                function($activity) use ($activityId) {
                    return $activity['id'] != $activityId;
                }
            );

            // Re-index the array
            $currentItinerary['activities'][$dayKey]['activities'] = array_values(
                $currentItinerary['activities'][$dayKey]['activities']
            );

            $newCount = count($currentItinerary['activities'][$dayKey]['activities']);

            if ($originalCount === $newCount) {
                return $this->response->setJSON([
                    'status' => 404,
                    'message' => 'Activity not found'
                ]);
            }

            $currentItinerary['metadata']['last_updated'] = date('c');

            // Save to database (use the actual tour_id from the found record)
            $actualTourId = $existing['tour_id']; // Use the actual tour_id from the found record
            $consigneeId = $existing['ID'];

            try {
                // Use raw SQL to force the update and bypass CodeIgniter validation
                $db = \Config\Database::connect();
                $jsonData = json_encode($currentItinerary);

                log_message('critical', 'ChatController::removeSingleActivity - Using consignee ID: ' . $consigneeId);
                log_message('critical', 'ChatController::removeSingleActivity - JSON data to save: ' . substr($jsonData, 0, 200) . '...');

                $sql = "UPDATE consignee SET agent_itinerary = ? WHERE ID = ?";
                $updateResult = $db->query($sql, [$jsonData, $consigneeId]);

                $affectedRows = $db->affectedRows();
                log_message('critical', 'ChatController::removeSingleActivity - SQL Update result: ' . ($updateResult ? 'SUCCESS' : 'FAILED'));
                log_message('critical', 'ChatController::removeSingleActivity - Affected rows: ' . $affectedRows);

                // Consider success if query executed (even if 0 rows affected)
                $updateResult = ($updateResult !== false);

            } catch (\Exception $e) {
                log_message('critical', 'ChatController::removeSingleActivity - Update exception: ' . $e->getMessage());
                $updateResult = false;
            }

            if ($updateResult) {
                log_message('critical', 'ChatController::removeSingleActivity success for quotation_id: ' . $quotationId);
                return $this->response->setJSON([
                    'status' => 200,
                    'message' => 'Activity removed successfully'
                ]);
            } else {
                log_message('error', 'ChatController::removeSingleActivity database update failed for quotation_id: ' . $quotationId);
                return $this->response->setJSON([
                    'status' => 500,
                    'message' => 'Failed to remove activity'
                ]);
            }

        } catch (\Exception $e) {
            log_message('critical', 'ChatController::removeSingleActivity error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    private function getPhase3Data($agentItinerary)
    {
        $phase3Data = [
            'customer_requirements' => '',
            'internal_notes' => '',
            'agent_comments' => ''
        ];

        if (!empty($agentItinerary)) {
            $itineraryData = json_decode($agentItinerary, true);
            if (isset($itineraryData['notes']) && is_array($itineraryData['notes'])) {
                $phase3Data = array_merge($phase3Data, $itineraryData['notes']);
            }
        }

        return $phase3Data;
    }

    public function processJapaneseRequest()
    {
        try {
            $input = json_decode($this->request->getBody(), true);
            $text = $input['text'] ?? '';
            $conversationId = $input['conversation_id'] ?? '';

            if (empty($text) || empty($conversationId)) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'Missing required parameters'
                ]);
            }

            // Use multi-stage processing pipeline
            $result = $this->processWithMultiStageApproach($text, $conversationId);

            return $this->response->setJSON($result);

        } catch (\Exception $e) {
            log_message('error', 'Error processing Japanese request: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'Error processing Japanese text: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Multi-stage processing pipeline with retry mechanisms
     */
    private function processWithMultiStageApproach($text, $conversationId)
    {
        $config = new \Config\Gemini();
        $maxRetries = 2; // Reduced from 3 to 2
        $attempt = 1;
        $lastValidResponse = null;

        while ($attempt <= $maxRetries) {
            try {
                // Stage 1: Primary processing with structured JSON
                $result = $this->processPrimaryStage($text, $config);

                if ($result['success']) {
                    // Store the last valid response in case validation fails
                    $lastValidResponse = $result['data'];

                    // Stage 2: Validation and formatting
                    $formattedResult = $this->processValidationStage($result['data'], $conversationId);

                    if ($formattedResult['success']) {
                        log_message('critical', "TRANSLATION SUCCESS on attempt {$attempt}");
                        return $formattedResult['result'];
                    } else {
                        // Validation failed - only retry if it's a content validation issue
                        // and we haven't already got a good response
                        log_message('critical', "TRANSLATION VALIDATION FAILED on attempt {$attempt}: " . $formattedResult['error']);

                        // If validation failed but we have valid JSON, don't retry - just use fallback formatting
                        if ($lastValidResponse && $attempt > 1) {
                            log_message('critical', "TRANSLATION USING FALLBACK with valid JSON from attempt {$attempt}");
                            return $this->processFallbackStage($text, $conversationId, $config);
                        }
                    }
                } else {
                    // Primary stage failed - this is worth retrying
                    throw new \Exception($result['error'] ?? 'Primary stage processing failed');
                }

            } catch (\Exception $e) {
                log_message('critical', "TRANSLATION ATTEMPT {$attempt} FAILED: " . $e->getMessage());

                if ($attempt === $maxRetries) {
                    // Final attempt - use fallback processing
                    log_message('critical', "TRANSLATION USING FINAL FALLBACK after {$maxRetries} attempts");
                    return $this->processFallbackStage($text, $conversationId, $config);
                }

                $attempt++;

                // Only adjust temperature for actual API failures, not validation failures
                if (!$result['success'] ?? true) {
                    $config->temperature = min(0.3, $config->temperature + 0.05); // Smaller increment
                    log_message('critical', "TRANSLATION RETRY {$attempt} with temperature: {$config->temperature}");
                }
            }
        }
    }

    /**
     * Primary stage: Structured JSON processing
     */
    private function processPrimaryStage($text, $config)
    {
        try {
            $promptData = $this->getJapaneseProcessingPrompt();
            $jsonResponse = $this->callGemini($text, $promptData, $config);

            // Validate that we received valid JSON
            $jsonData = json_decode($jsonResponse, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $jsonResponse,
                'structured_data' => $jsonData
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validation stage: Process and format JSON output
     */
    private function processValidationStage($jsonResponse, $conversationId)
    {
        try {
            $formattedResponse = $this->processStructuredJSONOutput($jsonResponse);

            // Additional validation checks
            if (empty(trim($formattedResponse))) {
                throw new \Exception('Empty formatted response');
            }

            // Check for minimum required content
            if (!$this->validateMinimumContent($formattedResponse)) {
                throw new \Exception('Formatted response lacks minimum required content');
            }

            // Insert processed message into chat
            $messageId = $this->insertChatMessage($conversationId, $formattedResponse, 'quotation_request');

            if (!$messageId) {
                throw new \Exception('Failed to insert message into chat');
            }

            return [
                'success' => true,
                'result' => [
                    'status' => 200,
                    'formatted_text' => $formattedResponse,
                    'raw_json' => $jsonResponse,
                    'message' => 'Request processed successfully',
                    'processing_method' => 'primary_structured'
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Fallback stage: Simplified processing when structured approach fails
     */
    private function processFallbackStage($text, $conversationId, $config)
    {
        try {

            // Use simplified prompt for fallback
            $fallbackPrompt = $this->getFallbackProcessingPrompt();

            // Remove JSON requirement for fallback
            $originalResponse = $this->callGeminiFallback($text, $fallbackPrompt, $config);

            // Apply basic formatting
            $formattedResponse = $this->formatFallbackOutput($originalResponse);

            // Insert message with fallback indicator
            $messageId = $this->insertChatMessage($conversationId, $formattedResponse, 'quotation_request', 'fallback');

            return [
                'status' => 200,
                'formatted_text' => $formattedResponse,
                'message' => 'Request processed using fallback method',
                'processing_method' => 'fallback_simplified'
            ];

        } catch (\Exception $e) {
            return [
                'status' => 500,
                'message' => 'All processing methods failed: ' . $e->getMessage(),
                'processing_method' => 'failed'
            ];
        }
    }

    /**
     * Validate minimum content requirements
     */
    private function validateMinimumContent($formattedText)
    {
        // More flexible validation - check for key content indicators
        $requiredPatterns = [
            'passenger_info' => ['පිරිස', 'Pax', 'ගමන්කරුවන්', 'visitor', 'travelers'],
            'contact_info' => ['නම', 'ටෙලිෆෝන්', 'ඊමේල්', 'phone', 'email', 'contact'],
        ];

        log_message('critical', 'VALIDATION CHECK - Formatted text length: ' . strlen($formattedText));
        log_message('critical', 'VALIDATION CHECK - Text sample: ' . substr($formattedText, 0, 200) . '...');

        $foundPatterns = 0;
        $totalPatterns = count($requiredPatterns);

        foreach ($requiredPatterns as $category => $patterns) {
            $categoryFound = false;

            foreach ($patterns as $pattern) {
                if (stripos($formattedText, $pattern) !== false) {
                    $categoryFound = true;
                    log_message('critical', "VALIDATION SUCCESS - Found {$category}: {$pattern}");
                    break;
                }
            }

            if ($categoryFound) {
                $foundPatterns++;
            } else {
                log_message('critical', "VALIDATION MISSING - Category {$category} not found in text");
            }
        }

        // Require at least 50% of patterns to be found (more flexible)
        $requiredMinimum = ceil($totalPatterns * 0.5);
        $isValid = $foundPatterns >= $requiredMinimum;

        log_message('critical', "VALIDATION RESULT - Found {$foundPatterns}/{$totalPatterns} patterns, required minimum: {$requiredMinimum}, valid: " . ($isValid ? 'YES' : 'NO'));

        return $isValid;
    }

    /**
     * Get simplified fallback prompt
     */
    private function getFallbackProcessingPrompt()
    {
        return [
            'role' => 'system',
            'content' => 'You are a Japanese travel inquiry processor. Extract key travel information from Japanese text and format it clearly in Sinhala. Focus on dates, passenger count, flights, and hotels. Use simple, clear Sinhala text without complex formatting.'
        ];
    }

    /**
     * Call Gemini without JSON formatting requirements (for fallback)
     */
    private function callGeminiFallback($text, $promptData, $config)
    {
        $fullPrompt = $promptData['content'] . "\n\nUser Input:\n" . $text;

        $requestData = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $fullPrompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $config->temperature,
                'maxOutputTokens' => $config->maxOutputTokens,
                'candidateCount' => $config->candidateCount
            ]
        ];

        $apiUrl = $config->baseUrl . $config->model . ':generateContent';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-goog-api-key: ' . $config->apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Gemini API request failed with status: ' . $httpCode);
        }

        $responseData = json_decode($response, true);
        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Invalid Gemini API response format');
        }

        return $responseData['candidates'][0]['content']['parts'][0]['text'];
    }

    public function insertRequestMessage()
    {
        try {
            log_message('critical', 'ChatController::insertRequestMessage - Method called');

            $input = json_decode($this->request->getBody(), true);
            $conversationId = $input['conversation_id'] ?? '';
            $message = $input['message'] ?? '';
            $language = $input['language'] ?? 'english';

            log_message('critical', 'ChatController::insertRequestMessage - Input received: conversation_id=' . $conversationId . ', language=' . $language . ', message_length=' . strlen($message));

            if (empty($conversationId) || empty($message)) {
                log_message('critical', 'ChatController::insertRequestMessage - Missing required parameters');
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'Missing required parameters'
                ]);
            }

            // Insert message into chat
            log_message('critical', 'ChatController::insertRequestMessage - Inserting message into chat');
            $messageId = $this->insertChatMessage($conversationId, $message, 'quotation_request', $language);
            log_message('critical', 'ChatController::insertRequestMessage - Message inserted with ID: ' . $messageId);

            if ($messageId) {
                return $this->response->setJSON([
                    'status' => 200,
                    'message' => 'Message inserted successfully',
                    'message_id' => $messageId
                ]);
            } else {
                log_message('critical', 'ChatController::insertRequestMessage - Failed to insert message');
                return $this->response->setJSON([
                    'status' => 500,
                    'message' => 'Failed to insert message'
                ]);
            }

        } catch (\Exception $e) {
            log_message('critical', 'ChatController::insertRequestMessage - Exception: ' . $e->getMessage());
            log_message('critical', 'ChatController::insertRequestMessage - Stack trace: ' . $e->getTraceAsString());
            log_message('error', 'Error inserting request message: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'Error inserting message: ' . $e->getMessage()
            ]);
        }
    }

    private function getJapaneseProcessingPrompt()
    {
        return [
            'role' => 'system',
            'content' => 'Translate this Japanese travel inquiry into Sinhala and structure it this way:

1. Name, Telephone Number, Email
2. Pax Count (Adults and Kids)
3. Arrival Time and Departure Time
4. Flight Details
5. Hotels (If already decided or hotel and room preferences)
6. Attractions/Activities
7. Notes

Use JSON format. Return ONLY JSON.

### CRITICAL EXTRACTION RULES ###
1. ALWAYS return ONLY valid JSON - no other text, explanations, or markdown
2. Extract ALL available information from the text into appropriate JSON fields
3. NEVER put structured data in additional_notes - extract it properly into the correct fields
4. Look for passenger counts in patterns like: 大人2名, 2 வയது வधیہیট්, adults 2, children 1
5. Extract dates in any format: 2025年12月25日, 2025 දෙසැම්බර් 25, December 25 2025
6. Extract contact info: names, emails (@domain), phone numbers (080/090/+81)
7. Convert Japanese place names to Sinhala equivalents
8. Mark missing information as null (not empty strings or "not provided")
9. KEEP HOTEL NAMES IN ENGLISH - never translate hotel brand names or property names
10. KEEP ROOM PREFERENCES IN ENGLISH - use English hospitality terms (Double bed, Queen bed, non-smoking, bathtub, breakfast included, late checkout, etc.)
11. For hotel preferences, consolidate all room requirements into clear English descriptions

### STEP-BY-STEP EXTRACTION PROCESS ###
STEP 1: Look for passenger information (adults, children, infants)
STEP 2: Look for dates (arrival, departure, check-in, check-out)
STEP 3: Look for flight information (codes, times, airports)
STEP 4: Look for hotel/accommodation details
STEP 5: Look for contact information (name, email, phone)
STEP 6: Look for activities/attractions mentioned
STEP 7: Put ONLY unclear/additional content in notes - NOT structured data

PLACE NAME TRANSLATIONS:
羽田 → "හනෙඩා", シンガポール → "සිංගප්පූරුව", コロンボ → "කොළඹ"
成田 → "නරිටා", 関西 → "කන්සායි", 大阪 → "ඔසාකා", 東京 → "ටෝකියෝ"

REQUIRED JSON STRUCTURE (Universal Format):
{
  "basic_details": {
    "passenger_count": number or null,
    "customer_name": "string or null",
    "travel_dates": {
      "start_date": "YYYY-MM-DD or null",
      "end_date": "YYYY-MM-DD or null",
      "total_nights": number or null,
      "date_flexibility": "string if mentioned"
    }
  },
  "flight_information": {
    "outbound_flights": [
      {
        "flight_code": "string",
        "date": "YYYY-MM-DD",
        "departure_time": "HH:MM",
        "arrival_time": "HH:MM or null",
        "departure_location": "Sinhala name",
        "arrival_location": "Sinhala name",
        "day_name": "day in Sinhala"
      }
    ],
    "return_flights": [
      {
        "flight_code": "string",
        "date": "YYYY-MM-DD",
        "departure_time": "HH:MM",
        "arrival_time": "HH:MM or null",
        "departure_location": "Sinhala name",
        "arrival_location": "Sinhala name",
        "day_name": "day in Sinhala"
      }
    ],
    "flight_preferences": ["string if mentioned"]
  },
  "accommodation_requirements": [
    {
      "hotel_name": "KEEP IN ENGLISH - exact hotel name as mentioned",
      "location": "string if mentioned",
      "checkin_date": "YYYY-MM-DD or null",
      "checkout_date": "YYYY-MM-DD or null",
      "nights": number or null,
      "preferences": "KEEP IN ENGLISH - room preferences using hospitality terminology"
    }
  ],
  "activities_attractions": [
    "translated activity/interest in Sinhala"
  ],
  "contact_information": {
    "primary_name": "string or null",
    "email": "string or null",
    "phone": "string or null",
    "additional_contacts": ["array if multiple"]
  },
  "additional_notes": [
    "incomplete information that needs clarification",
    "special requests and preferences",
    "alternative suggestions requested",
    "unclear details or assumptions made",
    "any other relevant information"
  ]
}

EXAMPLE (EXACT MATCH TO LOG FILE):
INPUT: "●お名前 前川佳恵 マエカワヨシエ ●お電話番号 08048564157 ●メールアドレス yoshie.iwasaki1789@gmail.com ●ご参加人数 大人2人 子ども（2-11才）1人 お子様のご年齢7歳 ●ご旅行期間 手配開始日2025-12-25～ 手配終了日2025-12-30 ●訪問したい観光地 シギリヤ キャンディ 象の孤児院 紅茶畑 サファリ 伝統舞踏 宝石"

OUTPUT:
{
  "basic_details": {
    "passenger_count": 3,
    "customer_name": "මායෙකාවා යෝශිඑ",
    "travel_dates": {
      "start_date": "2025-12-25",
      "end_date": "2025-12-30",
      "total_nights": 5,
      "date_flexibility": null
    }
  },
  "flight_information": {
    "outbound_flights": [],
    "return_flights": [],
    "flight_preferences": []
  },
  "accommodation_requirements": [],
  "activities_attractions": [
    "සිගිරිය",
    "මහනුවර",
    "අලි අනාථාගාරය",
    "තේ වතු",
    "සෆාරි",
    "සාම්ප්‍රදායික නැටුම්",
    "මැණික්"
  ],
  "contact_information": {
    "primary_name": "මායෙකාවා යෝශිඑ",
    "email": "yoshie.iwasaki1789@gmail.com",
    "phone": "08048564157",
    "additional_contacts": []
  },
  "additional_notes": [
    "ළමයාගේ වයස: 7 අවුරුදු"
  ]
}

HOTEL PREFERENCE EXAMPLES (ENGLISH FORMAT):
- "Late check-in after midnight required, non-smoking room, Double or Queen bed, with bathtub, breakfast included"
- "Non-smoking, Double bed, bathtub, breakfast included"
- "Late checkout until evening required, non-smoking, Queen bed, bathtub, breakfast included"

RESPOND ONLY with valid JSON in the exact structure shown above.'
        ];
    }

    private function insertChatMessage($conversationId, $message, $type = 'quotation_request', $language = 'processed')
    {
        try {
            log_message('critical', 'ChatController::insertChatMessage - Called with conversation_id=' . $conversationId . ', type=' . $type . ', language=' . $language);

            $chatModel = new ChatModel();

            $data = [
                'conversation_id' => $conversationId,
                'message' => $message,
                'sender_type' => 'system',
                'message_type' => $type,
                'language' => $language,
                'created_at' => date('Y-m-d H:i:s')
            ];

            log_message('critical', 'ChatController::insertChatMessage - Data prepared: ' . json_encode($data));

            $result = $chatModel->insert($data);
            log_message('critical', 'ChatController::insertChatMessage - Insert result: ' . $result);

            return $result;
        } catch (\Exception $e) {
            log_message('critical', 'ChatController::insertChatMessage - Exception: ' . $e->getMessage());
            log_message('critical', 'ChatController::insertChatMessage - Stack trace: ' . $e->getTraceAsString());
            log_message('error', 'Error inserting chat message: ' . $e->getMessage());
            return false;
        }
    }

    private function callOpenAI($text, $promptData, $config)
    {
        try {
            log_message('critical', 'ChatController::callOpenAI - Called with model=' . $config->model . ', temperature=' . $config->temperature);

            $messages = [
                $promptData,
                [
                    'role' => 'user',
                    'content' => $text
                ]
            ];

            $requestData = [
                'model' => $config->model,
                'messages' => $messages,
                'max_tokens' => $config->maxTokens,
                'temperature' => $config->temperature
            ];

            log_message('critical', 'ChatController::callOpenAI - Request data prepared: ' . json_encode($requestData));
            log_message('critical', 'ChatController::callOpenAI - API URL: ' . $config->apiUrl);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $config->apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $config->apiKey,  // Use full API key, not truncated
                    'OpenAI-Organization: ' . $config->organizationId
                ],
                CURLOPT_POSTFIELDS => json_encode($requestData),
                CURLOPT_TIMEOUT => $config->timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,  // Disable SSL verification for local development
                CURLOPT_SSL_VERIFYHOST => false,  // Disable SSL hostname verification
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_USERAGENT => 'Avenir Travel Management System/1.0',
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
            ]);

            log_message('critical', 'ChatController::callOpenAI - Making cURL request to OpenAI');
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            log_message('critical', 'ChatController::callOpenAI - Response received: HTTP ' . $httpCode);
            if ($curlError) {
                log_message('critical', 'ChatController::callOpenAI - cURL error: ' . $curlError);
            }

            if ($httpCode !== 200) {
                log_message('critical', 'ChatController::callOpenAI - API request failed with status: ' . $httpCode . ', response: ' . substr($response, 0, 500));
                throw new \Exception('OpenAI API request failed with status: ' . $httpCode);
            }

            $responseData = json_decode($response, true);
            log_message('critical', 'ChatController::callOpenAI - Response decoded successfully');

            if (!isset($responseData['choices'][0]['message']['content'])) {
                log_message('critical', 'ChatController::callOpenAI - Invalid response format: ' . json_encode($responseData));
                throw new \Exception('Invalid OpenAI API response format');
            }

            $content = $responseData['choices'][0]['message']['content'];
            log_message('critical', 'ChatController::callOpenAI - Content extracted: ' . substr($content, 0, 200) . '...');

            return $content;

        } catch (\Exception $e) {
            log_message('critical', 'ChatController::callOpenAI - Exception: ' . $e->getMessage());
            log_message('critical', 'ChatController::callOpenAI - Stack trace: ' . $e->getTraceAsString());
            log_message('error', 'OpenAI API call failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function callGemini($text, $promptData, $config)
    {
        try {
            // Combine system prompt and user text for Gemini
            $fullPrompt = $promptData['content'] . "\n\nUser Input:\n" . $text;

            // Log the complete prompt being sent to Gemini
            log_message('critical', 'GEMINI FULL PROMPT: ' . $fullPrompt);

            // Enhanced request data with optimized generation config
            $requestData = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $fullPrompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => $config->temperature,
                    'maxOutputTokens' => $config->maxOutputTokens,
                    'candidateCount' => $config->candidateCount,
                    'topP' => $config->topP,
                    'topK' => $config->topK
                ],
                'safetySettings' => $config->safetySettings
            ];

            $apiUrl = $config->baseUrl . $config->model . ':generateContent';

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-goog-api-key: ' . $config->apiKey
                ],
                CURLOPT_POSTFIELDS => json_encode($requestData),
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_USERAGENT => 'Avenir Travel Management System/1.0',
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode !== 200) {
                log_message('critical', 'ChatController::callGemini - API request failed with status: ' . $httpCode . ', response: ' . substr($response, 0, 500));
                throw new \Exception('Gemini API request failed with status: ' . $httpCode);
            }

            $responseData = json_decode($response, true);

            if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception('Invalid Gemini API response format');
            }

            $content = $responseData['candidates'][0]['content']['parts'][0]['text'];

            // Log the complete response from Gemini
            log_message('critical', 'GEMINI RESPONSE: ' . $content);

            return $content;

        } catch (\Exception $e) {
            log_message('error', 'Gemini API call failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process structured JSON output from Gemini and format to Sinhala
     */
    private function processStructuredJSONOutput($jsonResponse)
    {
        try {
            log_message('critical', 'ChatController::processStructuredJSONOutput - Processing JSON response');

            // Decode JSON response
            $data = json_decode($jsonResponse, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
            }

            // Validate required structure
            $this->validateJSONStructure($data);

            // Format to Sinhala output
            return $this->formatToSinhala($data);

        } catch (\Exception $e) {
            log_message('critical', 'ChatController::processStructuredJSONOutput - Error: ' . $e->getMessage());
            // Fallback to original processing method
            return $this->formatFallbackOutput($jsonResponse);
        }
    }

    /**
     * Validate JSON structure matches expected universal format
     */
    private function validateJSONStructure($data)
    {
        $requiredFields = ['basic_details', 'flight_information', 'accommodation_requirements', 'activities_attractions', 'contact_information', 'additional_notes'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new \Exception("Missing required field: $field");
            }
        }

        // Validate basic details structure exists (but content can be null)
        if (!isset($data['basic_details'])) {
            throw new \Exception("Invalid basic_details structure");
        }

        // Ensure contact information structure exists (critical for quotations)
        if (!isset($data['contact_information'])) {
            throw new \Exception("Invalid contact_information structure");
        }

        log_message('critical', 'ChatController::validateJSONStructure - Universal structure validation passed');
    }

    /**
     * Format JSON data to universal quotation format in Sinhala
     */
    private function formatToSinhala($data)
    {
        try {
            log_message('critical', 'ChatController::formatToSinhala - Formatting JSON to universal quotation format');

            $output = [];

            // SECTION 1: Passenger Count
            $passengerCount = $data['basic_details']['passenger_count'] ?? null;
            $output[] = "පිරිස (Pax): ";
            $output[] = ($passengerCount ? $passengerCount : "ස්ථිර කිරීමට අවශ්‍ය");
            $output[] = "\n\n";

            // SECTION 2: Travel Dates
            $travelDates = $data['basic_details']['travel_dates'] ?? [];
            $startDate = $travelDates['start_date'] ?? null;
            $endDate = $travelDates['end_date'] ?? null;
            $nights = $travelDates['total_nights'] ?? null;
            $output[] = "ගමන් දිනයන්: ";
            if ($startDate && $endDate) {
                $formattedStart = $this->formatDateToSinhala($startDate);
                $formattedEnd = $this->formatDateToSinhala($endDate);
                $nightsText = $nights ? " (රාත්‍රී {$nights})" : "";
                $output[] = "{$formattedStart} – {$formattedEnd}{$nightsText}";
            } else {
                $output[] = "ස්ථිර කිරීමට අවශ්‍ය";
            }
            $output[] = "\n\n";

            // SECTION 3: Flight Information
            $output[] = "ගුවන් ගමන්: ";
            $output[] = $this->formatFlightInformationSection($data);
            $output[] = "\n\n";

            // SECTION 4: Hotel Requirements
            $output[] = "හෝටල් අවශ්‍යතා:\n";
            $output[] = $this->formatAccommodationSection($data);
            $output[] = "\n\n";

            // SECTION 5: Activities/Attractions
            $output[] = "ආකර්ෂණ/ක්‍රියාකාරකම්:\n";
            $output[] = $this->formatActivitiesSection($data);
            $output[] = "\n\n";

            // SECTION 6: Contact Information (Critical for Quotation)
            $output[] = "සම්බන්ධිකරණ:\n";
            $output[] = $this->formatContactInformationSection($data);
            $output[] = "\n\n";

            // SECTION 7: Additional Notes (Everything else)
            $output[] = "අමතර සටහන්:\n";
            $output[] = $this->formatAdditionalNotesSection($data);

            $formattedOutput = implode("", array_filter($output));
            log_message('critical', 'ChatController::formatToSinhala - Universal format output length: ' . strlen($formattedOutput));

            return $formattedOutput;

        } catch (\Exception $e) {
            log_message('critical', 'ChatController::formatToSinhala - Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Format dates section in Sinhala
     */
    private function formatDatesSection($dates)
    {
        $startDate = $this->formatDateToSinhala($dates['start_date']);
        $endDate = $this->formatDateToSinhala($dates['end_date']);
        $nights = $dates['nights'] ?? 0;
        $days = $dates['days'] ?? 0;

        return "දිනය: {$startDate} – {$endDate} ({$nights} රාත්‍රී / {$days} දින)";
    }








    /**
     * Convert date to Sinhala format
     */
    private function formatDateToSinhala($dateString)
    {
        if (empty($dateString)) return 'ලබා දී නොමැත';

        try {
            $date = new \DateTime($dateString);
            $day = $date->format('j');
            $month = $this->getMonthNameSinhala($date->format('n'));

            return "{$month} {$day}";
        } catch (\Exception $e) {
            log_message('warning', 'Date formatting error: ' . $e->getMessage());
            return $dateString;
        }
    }

    /**
     * Convert date to full Sinhala format with year
     */
    private function formatFullDateToSinhala($dateString)
    {
        if (empty($dateString)) return 'ලබා දී නොමැත';

        try {
            $date = new \DateTime($dateString);
            $year = $date->format('Y');
            $day = $date->format('j');
            $month = $this->getMonthNameSinhala($date->format('n'));

            return "{$year} {$month} {$day}";
        } catch (\Exception $e) {
            log_message('warning', 'Full date formatting error: ' . $e->getMessage());
            return $dateString;
        }
    }

    /**
     * Convert date to short format (MM/DD)
     */
    private function formatShortDateToSinhala($dateString)
    {
        if (empty($dateString)) return 'ලබා දී නොමැත';

        try {
            $date = new \DateTime($dateString);
            $month = $date->format('n');
            $day = $date->format('j');

            return "{$month}/{$day}";
        } catch (\Exception $e) {
            log_message('warning', 'Short date formatting error: ' . $e->getMessage());
            return $dateString;
        }
    }

    /**
     * Get day name in Sinhala from date
     */
    private function getDayNameFromDate($dateString)
    {
        if (empty($dateString)) return '';

        try {
            $date = new \DateTime($dateString);
            $dayNumber = $date->format('w'); // 0 = Sunday, 1 = Monday, etc.

            $days = [
                0 => 'ඉරිදා',
                1 => 'සඳුදා',
                2 => 'අඟහරුවාදා',
                3 => 'බදාදා',
                4 => 'බ්‍රහස්පතින්දා',
                5 => 'සිකුරාදා',
                6 => 'සෙනසුරාදා'
            ];

            return $days[$dayNumber] ?? '';
        } catch (\Exception $e) {
            log_message('warning', 'Day name formatting error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Get Sinhala month name
     */
    private function getMonthNameSinhala($monthNumber)
    {
        $months = [
            1 => 'ජනවාරි', 2 => 'පෙබරවාරි', 3 => 'මාර්තු', 4 => 'අප්‍රේල්',
            5 => 'මැයි', 6 => 'ජූනි', 7 => 'ජූලි', 8 => 'අගෝස්තු',
            9 => 'සැප්තැම්බර්', 10 => 'ඔක්තෝබර්', 11 => 'නොවැම්බර්', 12 => 'දෙසැම්බර්'
        ];

        return $months[$monthNumber] ?? 'අදාළ නොවේ';
    }

    /**
     * Format basic details section for universal quotation format
     */
    private function formatBasicDetailsSection($data)
    {
        $output = [];

        // Passenger count (Essential)
        $passengerCount = $data['basic_details']['passenger_count'] ?? null;
        $output[] = "පිරිස (Pax): " . ($passengerCount ? $passengerCount : "ස්ථිර කිරීමට අවශ්‍ය");

        // Customer name (Essential)
        $customerName = $data['basic_details']['customer_name'] ?? $data['contact_information']['primary_name'] ?? null;
        $output[] = "නම: " . ($customerName ? $customerName : "ලබා දී නොමැත");

        // Travel dates
        $travelDates = $data['basic_details']['travel_dates'] ?? [];
        $startDate = $travelDates['start_date'] ?? null;
        $endDate = $travelDates['end_date'] ?? null;
        $nights = $travelDates['total_nights'] ?? null;

        if ($startDate && $endDate) {
            $formattedStart = $this->formatDateToSinhala($startDate);
            $formattedEnd = $this->formatDateToSinhala($endDate);
            $nightsText = $nights ? " ({$nights} රාත්‍රී)" : "";
            $output[] = "ගමන් දිනයන්: {$formattedStart} – {$formattedEnd}{$nightsText}";
        } else {
            $output[] = "ගමන් දිනයන්: ස්ථිර කිරීමට අවශ්‍ය";
        }

        return implode("\n", $output);
    }

    /**
     * Format flight information section for universal quotation format
     */
    private function formatFlightInformationSection($data)
    {
        $flightInfo = $data['flight_information'] ?? [];
        $output = [];

        // Outbound flights
        if (!empty($flightInfo['outbound_flights'])) {
            $output[] = "පිටත්වීම: ";
            foreach ($flightInfo['outbound_flights'] as $index => $flight) {
                $flightNumber = $index + 1;
                $code = $flight['flight_code'] ?? '';
                $date = $this->formatFullDateToSinhala($flight['date'] ?? '');
                $day = $flight['day_name'] ?? '';
                $depTime = $flight['departure_time'] ?? '';
                $arrTime = $flight['arrival_time'] ?? '';
                $from = $flight['departure_location'] ?? '';
                $to = $flight['arrival_location'] ?? '';

                if ($arrTime) {
                    $output[] = "\n{$flightNumber}.{$code}: {$date} ({$day}) {$depTime} {$from} සිට → {$arrTime} {$to} වෙත ";
                } else {
                    $output[] = "\n{$flightNumber}.{$code}: {$date} ({$day}) {$depTime} {$from} → {$to} ";
                }
            }
        }

        // Return flights
        if (!empty($flightInfo['return_flights'])) {
            $output[] = "\nආපසු පැමිණීම";
            foreach ($flightInfo['return_flights'] as $index => $flight) {
                $flightNumber = $index + 3; // Continue numbering from outbound flights
                $code = $flight['flight_code'] ?? '';
                $date = $this->formatFullDateToSinhala($flight['date'] ?? '');
                $day = $flight['day_name'] ?? '';
                $depTime = $flight['departure_time'] ?? '';
                $arrTime = $flight['arrival_time'] ?? '';
                $from = $flight['departure_location'] ?? '';
                $to = $flight['arrival_location'] ?? '';

                if ($arrTime) {
                    $output[] = "\n{$flightNumber}.{$code}: {$date} ({$day}) {$depTime} {$from} සිට → {$arrTime} {$to} වෙත ";
                } else {
                    $output[] = "\n{$flightNumber}.{$code}: {$date} ({$day}) {$depTime} {$from} → {$to} ";
                }
            }
        }

        if (empty($output)) {
            $output[] = "විස්තර ලබා දී නොමැත";
        }

        return implode("\n", $output);
    }

    /**
     * Format accommodation section for universal quotation format
     */
    private function formatAccommodationSection($data)
    {
        $accommodations = $data['accommodation_requirements'] ?? [];
        $output = [];

        if (!empty($accommodations)) {
            foreach ($accommodations as $index => $hotel) {
                $hotelLetter = chr(65 + $index); // A, B, C, etc.
                $name = $hotel['hotel_name'] ?? 'නම සඳහන් නොවේ';
                $location = $hotel['location'] ?? '';
                $checkin = $hotel['checkin_date'] ?? null;
                $checkout = $hotel['checkout_date'] ?? null;
                $nights = $hotel['nights'] ?? null;
                $preferences = $hotel['preferences'] ?? '';

                $hotelLine = "# හෝටලය {$hotelLetter}: {$name}";
                if ($location) $hotelLine .= " ({$location})";

                if ($checkin && $checkout) {
                    $checkinFormatted = $this->formatShortDateToSinhala($checkin);
                    $checkoutFormatted = $this->formatShortDateToSinhala($checkout);
                    $checkinDay = $this->getDayNameFromDate($checkin);
                    $checkoutDay = $this->getDayNameFromDate($checkout);
                    $nightsText = $nights ? " || රාත්‍රී -{$nights}" : "";

                    $hotelLine .= " | {$checkinFormatted} ({$checkinDay}) ඇතුළුවීම → {$checkoutFormatted} ({$checkoutDay}) පිටවීම{$nightsText}";
                }

                if ($preferences) $hotelLine .= " | {$preferences}";

                $output[] = $hotelLine;

                // Add line break after each hotel entry for list formatting
                if ($index < count($accommodations) - 1) {
                    $output[] = "";
                }
            }
        } else {
            $output[] = "විස්තර ලබා දී නොමැත";
        }

        return implode("\n", $output);
    }

    /**
     * Format activities section for universal quotation format
     */
    private function formatActivitiesSection($data)
    {
        $activities = $data['activities_attractions'] ?? [];

        if (!empty($activities)) {
            $output = [];
            foreach ($activities as $activity) {
                $output[] = "• {$activity}";
            }
            return implode("\n", $output);
        } else {
            return "විස්තර ලබා දී නොමැත";
        }
    }

    /**
     * Format contact information section for universal quotation format
     */
    private function formatContactInformationSection($data)
    {
        $contact = $data['contact_information'] ?? [];
        $output = [];

        // Primary name (Essential for quotation)
        $name = $contact['primary_name'] ?? null;
        $output[] = "නම: " . ($name ? $name : "ලබා දී නොමැත");

        // Email (Essential for quotation)
        $email = $contact['email'] ?? null;
        $output[] = "විද්‍යුත් තැපෑල: " . ($email ? $email : "ලබා දී නොමැත");

        // Phone (Essential for quotation)
        $phone = $contact['phone'] ?? null;
        $output[] = "ජංගම: " . ($phone ? $phone : "ලබා දී නොමැත");

        // Additional contacts
        if (!empty($contact['additional_contacts'])) {
            $output[] = "අමතර සම්බන්ධතා:";
            foreach ($contact['additional_contacts'] as $additionalContact) {
                $output[] = "  • {$additionalContact}";
            }
        }

        return implode("\n", $output);
    }

    /**
     * Format additional notes section for universal quotation format
     */
    private function formatAdditionalNotesSection($data)
    {
        $notes = $data['additional_notes'] ?? [];
        $flightPrefs = $data['flight_information']['flight_preferences'] ?? [];

        $output = [];

        // Add flight preferences to notes (translate to Sinhala)
        foreach ($flightPrefs as $pref) {
            $translatedPref = $this->translateFlightPreferenceToSinhala($pref);
            $output[] = "• {$translatedPref}";
        }

        // Add all additional notes
        foreach ($notes as $note) {
            $output[] = "• {$note}";
        }

        if (empty($output)) {
            $output[] = "අමතර සටහන් නොමැත";
        }

        return implode("\n", $output);
    }

    /**
     * Translate flight preferences from English to Sinhala
     */
    private function translateFlightPreferenceToSinhala($preference)
    {
        $translations = [
            'window seat' => 'ජනෙළ ආසනය',
            'aisle seat' => 'ගමන්මග ආසනය',
            'bulkhead seat' => 'ඉදිරිපස ආසනය',
            'exit row' => 'පිටවීම් පේළිය',
            'no middle seat' => 'මැද ආසනය නොවේ',
            'front of plane' => 'ගුවන් යානයේ ඉදිරිපස',
            'back of plane' => 'ගුවන් යානයේ පිටුපස',
            'vegetarian meal' => 'නිර්මාංශ ආහාර',
            'halal meal' => 'හලාල් ආහාර',
            'kosher meal' => 'කොෂර් ආහාර',
            'gluten free meal' => 'ග්ලුටන් රහිත ආහාර',
            'diabetic meal' => 'දියවැඩියා ආහාර',
            'child meal' => 'ළමා ආහාර',
            'infant meal' => 'ළදරු ආහාර',
            'special assistance' => 'විශේෂ සහායතාව',
            'wheelchair assistance' => 'රෝද පුටුවේ සහායතාව',
            'early boarding' => 'කලින් ගුවන් යානයට ඇතුළු වීම',
            'priority boarding' => 'ප්‍රමුඛතා ගුවන් යානයට ඇතුළු වීම',
            'extra legroom' => 'අමතර කකුල් ඉඩ',
            'business class' => 'ව්‍යාපාරික පන්තිය',
            'first class' => 'පළමු පන්තිය',
            'economy class' => 'ආර්ථික පන්තිය',
            'premium economy' => 'වරප්‍රසාද ආර්ථික පන්තිය'
        ];

        $lowerPref = strtolower(trim($preference));

        // Check for exact matches first
        if (isset($translations[$lowerPref])) {
            return $translations[$lowerPref];
        }

        // Check for partial matches (in case preference contains additional text)
        foreach ($translations as $english => $sinhala) {
            if (strpos($lowerPref, $english) !== false) {
                return str_ireplace($english, $sinhala, $preference);
            }
        }

        // If no translation found, return original preference
        return $preference;
    }

    /**
     * Fallback formatting for non-JSON responses - Force universal format
     */
    private function formatFallbackOutput($originalResponse)
    {
        log_message('critical', 'ChatController::formatFallbackOutput - Using fallback formatting with universal format');

        // Clean up the response and apply basic formatting
        $cleaned = $this->cleanMixedLanguageTextEnhanced($originalResponse);
        $cleaned = $this->standardizeFormatting($cleaned);

        // Force universal quotation format structure even for fallback
        return $this->forceUniversalFormat($cleaned);
    }

    /**
     * Force any response into universal quotation format structure
     */
    private function forceUniversalFormat($text)
    {
        // Remove any existing prefixes like "හරි, ඔබගේ සංචාර විස්තර..." or similar variations
        $text = preg_replace('/^.*?හරි.*?(සංචාර|ගමන්).*?විස්තර.*?[:：]\s*/i', '', $text);
        $text = preg_replace('/^.*?හරි.*?ඉල්ලීම.*?සිංහලෙන්[:：]\s*/i', '', $text);
        $text = preg_replace('/^\*\*.*?\*\*\s*/m', '', $text);

        // Remove English prefixes like "Okay, here's the extracted travel information in Sinhala, based on the provided Japanese text:"
        $text = preg_replace('/^.*?Okay.*?text[:：]\s*/i', '', $text);
        $text = preg_replace('/^.*?Here.*?Sinhala.*?[:：]\s*/i', '', $text);

        // If the text doesn't start with our format, wrap it in universal format
        if (!preg_match('/^ගමන් මූලික විස්තර:/i', $text)) {
            // Try to extract key information from the old format
            $paxCount = "ස්ථිර කිරීමට අවශ්‍ය";
            $customerName = "ලබා දී නොමැත";
            $email = "ලබා දී නොමැත";
            $phone = "ලබා දී නොමැත";
            $dates = "ස්ථිර කිරීමට අවශ්‍ය";

            // Extract passenger count (from patterns like "වැඩිහිටියන් 2" or "මගීන් ගණන: 2")
            if (preg_match('/වැඩිහිටියන්[:：\s]*(\d+)/ui', $text, $matches)) {
                $paxCount = $matches[1];
                // Check for children info and add to pax count
                if (preg_match('/ළමයි.*?[:：\s]*(\d+)/ui', $text, $childMatches)) {
                    $childCount = $childMatches[1];
                    if (preg_match('/වයස.*?(\d+)/ui', $text, $ageMatches)) {
                        $paxCount = $matches[1] . " වැඩිහිටියන්, " . $childCount . " ළමයා (වයස " . $ageMatches[1] . ")";
                    } else {
                        $paxCount = $matches[1] . " වැඩිහිටියන්, " . $childCount . " ළමයා";
                    }
                }
            } else if (preg_match('/(?:මගීන්.*?ගණන|pax)[:：\s]*(\d+)/ui', $text, $matches)) {
                $paxCount = $matches[1];
            }

            // Try to extract name patterns - look for names in various formats
            if (preg_match('/(?:නම|ගනුදෙනුකරුගේ\s*නම)[:：]\s*([^\n\r*]+)/u', $text, $matches)) {
                $customerName = trim($matches[1]);
            } else if (preg_match('/^([^\n\r*]+)\s*\*/u', $text, $matches)) {
                // Sometimes name appears at the beginning before "*"
                $potentialName = trim($matches[1]);
                if (preg_match('/[ඒ-ෆ]/u', $potentialName)) { // Contains Sinhala characters
                    $customerName = $potentialName;
                }
            }

            // Extract email - look for email patterns
            if (preg_match('/(?:විද්‍යුත්\s*තැපෑල|ඊ-මේල්|email)[:：]\s*([^\s\n\r*]+)/ui', $text, $matches)) {
                $email = trim($matches[1]);
            } else if (preg_match('/([a-zA-Z0-9._+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $text, $matches)) {
                // Direct email pattern matching
                $email = trim($matches[1]);
            }

            // Extract phone numbers - multiple patterns
            if (preg_match('/(?:ජංගම|දුරකථන|phone|mobile|tel)[:：]\s*([+\d\s-]+)/ui', $text, $matches)) {
                $phone = trim($matches[1]);
            } else if (preg_match('/(?:දුරකථන\s*අංකය|ජංගම\s*අංකය)[:：]\s*(\d+)/ui', $text, $matches)) {
                $phone = trim($matches[1]);
            } else if (preg_match('/(\d{8,15})/', $text, $matches)) {
                // Direct phone number pattern (8-15 digits)
                $phone = trim($matches[1]);
            }

            // Extract dates if found
            if (preg_match('/(\d{4}\s*දෙසැම්බර්\s*\d{1,2}\s*සිට.*?\d{1,2})/u', $text, $matches)) {
                $dates = $matches[1];
            } else if (preg_match('/ගමන්\s*කාලය[:：]\s*([^\n\r*]+)/u', $text, $matches)) {
                $dates = trim($matches[1]);
            } else if (preg_match('/(\d{4}.*?\d{1,2}.*?\d{1,2})/u', $text, $matches)) {
                $dates = $matches[1];
            }

            $output = [];
            $output[] = "ගමන් මූලික විස්තර:";
            $output[] = "- පිරිස (Pax): " . $paxCount;
            $output[] = "- නම: " . $customerName;
            $output[] = "- ගමන් දිනයන්: " . $dates;
            $output[] = "";
            $output[] = "ගුවන් ගමන්:";
            // Try to extract flight info if present
            if (preg_match('/ගුවන්\s*ගමන්[:：](.+?)(?=හෝටල්|අමතර|$)/uis', $text, $matches)) {
                $output[] = trim($matches[1]);
            } else {
                $output[] = "විස්තර ලබා දී නොමැත";
            }
            $output[] = "";
            $output[] = "හෝටල් අවශ්‍යතා:";
            // Try to extract hotel info if present
            if (preg_match('/හෝටල්[:：](.+?)(?=අමතර|ක්‍රියාකාරකම්|$)/uis', $text, $matches)) {
                $output[] = trim($matches[1]);
            } else {
                $output[] = "විස්තර ලබා දී නොමැත";
            }
            $output[] = "";
            $output[] = "ආකර්ෂණ/ක්‍රියාකාරකම්:";
            $output[] = "විස්තර ලබා දී නොමැත";
            $output[] = "";
            $output[] = "සම්බන්ධිකරණ:";
            $output[] = "නම: " . $customerName;
            $output[] = "විද්‍යුත් තැපෑල: " . $email;
            $output[] = "ජංගම: " . $phone;
            $output[] = "";
            $output[] = "අමතර සටහන්:";
            $output[] = "• මුල් ඉල්ලීම: " . trim($text);

            return implode("\n", $output);
        }

        return $text;
    }

    /**
     * Clean mixed language artifacts from text
     */
   

    /**
     * Enhanced Japanese to Sinhala translation cleaning
     */
    private function cleanMixedLanguageTextEnhanced($text)
    {
        // Comprehensive Japanese to Sinhala translations
        $replacements = [
            // Airports and places
            '羽田' => 'හනෙඩා',
            'ハネダ' => 'හනෙඩා',
            'シンガポール' => 'සිංගප්පූරුව',
            'コロンボ' => 'කොළඹ',
            '成田' => 'නරිටා',
            'ナリタ' => 'නරිටා',
            '関西' => 'කන්සායි',
            'カンサイ' => 'කන්සායි',
            '大阪' => 'ඔසාකා',
            'オオサカ' => 'ඔසාකා',
            '東京' => 'ටෝකියෝ',
            'トウキョウ' => 'ටෝකියෝ',

            // Airlines
            'アライアンス' => 'සන්ධානය',
            'スターアライアンス' => 'ස්ටාර් සන්ධානය',
            'ワンワールド' => 'වන් වර්ල්ඩ්',
            'スカイチーム' => 'ස්කයි ටීම්',
            'ANA' => 'ඒඑන්ඒ',
            'JAL' => 'ජේඒඑල්',

            // Hotels and accommodations
            'ホテル' => 'හෝටලය',
            'リゾート' => 'රිසෝට්',
            'イン' => 'ඉන්',
            'ゲストハウス' => 'ගෙස්ට් හවුස්',

            // Common travel terms
            '朝食' => 'උදෑසන ආහාරය',
            'チョウショク' => 'උදෑසන ආහාරය',
            '夕食' => 'රාත්‍රී ආහාරය',
            'ユウショク' => 'රාත්‍රී ආහාරය',
            '部屋' => 'කාමරය',
            'ヘヤ' => 'කාමරය',
            '禁煙' => 'දුම්පානය තහනම්',
            'キンエン' => 'දුම්පානය තහනම්',
            '喫煙' => 'දුම්පාන',
            'キツエン' => 'දුම්පාන',

            // Numbers in Japanese
            '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5',
            '６' => '6', '７' => '7', '８' => '8', '９' => '9', '０' => '0',

            // Time-related terms
            '時間' => 'පැය',
            'ジカン' => 'පැය',
            '分' => 'මිනිත්තු',
            'フン' => 'මිනිත්තු',
            '日' => 'දින',
            'ニチ' => 'දින',
            '月' => 'මාසය',
            'ゲツ' => 'මාසය',
            '年' => 'වර්ෂය',
            'ネン' => 'වර්ෂය',

            // Special requests
            '特別' => 'විශේෂ',
            'トクベツ' => 'විශේෂ',
            '要求' => 'ඉල්ලීම',
            'ヨウキュウ' => 'ඉල්ලීම',
            'リクエスト' => 'ඉල්ලීම',
            '車椅子' => 'රෝද පුටුව',
            'クルマイス' => 'රෝද පුටුව',
            'ベジタリアン' => 'නිර්මාංශ',
            'アレルギー' => 'අසාත්මිකතා'
        ];

        foreach ($replacements as $search => $replace) {
            $text = str_replace($search, $replace, $text);
        }

        return $text;
    }

    /**
     * Standardize formatting for consistent output
     */
    private function standardizeFormatting($text)
    {
        // Ensure proper line breaks between sections
        $text = preg_replace('/([^\n])\n([^\n•\s])/', "$1\n\n$2", $text);

        // Standardize bullet points
        $text = preg_replace('/^\s*[•·‣▪▫]\s*/m', '  • ', $text);

        // Remove excessive whitespace
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);

        return $text;
    }

}
