<?php

namespace App\Controllers\Chat;

use App\Controllers\BaseController;
use App\Models\ChatAttachmentModel;
use App\Models\ChatModel;

class ChatController1 extends BaseController
{
    public function index()
    {
        $data = [];
        $group_id = session()->get('group_id');
        $data['group_id'] = $group_id;
        $data['agent'] = session()->get('agent');
        $data['customers'] = [];

        $db = \Config\Database::connect();

        // Agent User
        if ($group_id == 14) {

            // get agent's last message time
            $chatModel = new ChatModel();
            $last_message = $chatModel->select('time')->where('agent', $data['agent'])->orderBy('id', 'DESC')->first();
            if ($last_message) {
                $data['last_message_time'] = $last_message['time'];
            }
        } else {
            $customerModel = $db->table('customers');
            $data['customers'] = $customerModel->get()->getResultArray();

            // foreach customer, get last message time
            foreach ($data['customers'] as $key => $customer) {
                $chatModel = new ChatModel();
                $last_message = $chatModel->select('time')->where('agent', $customer['Sup_Acc_No'])->orderBy('id', 'DESC')->first();
                if ($last_message) {
                    $data['customers'][$key]['last_message_time'] = $last_message['time'];
                }
            }
        }

        return view('chat/chat', $data);
    }

    public function get_conversation()
    {
        $agent = $this->request->getGet('agent');

        // get agent name
        $db = \Config\Database::connect();
        $customerModel = $db->table('customers');
        $agent_data = $customerModel->select('Name')->where('Sup_Acc_No', $agent)->get()->getRowArray();

        $chats = $this->receive($agent);
        return $this->response->setJSON(['status' => 200, 'chats' => $chats, 'agent' => $agent_data]);
    }

    public function send_message()
    {
        $group_id = session()->get('group_id');
        $agent = $this->request->getPost('agent');
        $message = $this->request->getPost('message');
        $attachment_names = $this->request->getPost('attachment_names');

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

        $chat_id = $this->send($agent, $user, $message, $from_agent);

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

    private function send($agent = null, $user = null, $message = null, $from_agent = null)
    {
        $chatModel = new ChatModel();
        return $chatModel->insert([
            'agent' => $agent,
            'user' => $user,
            'time' => date('Y-m-d H:i:s'),
            'message' => $message,
            'from_agent' => $from_agent
        ], true);
    }

    private function receive($agent)
    {
        $chatModel = new ChatModel();
        $messages = $chatModel->select('*')->where('agent', $agent)->findAll();

        $chatAttachmentModel = new ChatAttachmentModel();
        foreach ($messages as $key => $message) {
            $attachments = $chatAttachmentModel->select('*')->where('chat_id', $message['id'])->findAll();
            $messages[$key]['attachments'] = $attachments;
        }

        return $messages;
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
}
