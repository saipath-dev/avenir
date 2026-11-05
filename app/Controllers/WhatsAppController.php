<?php

namespace App\Controllers;

use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;
use Netflie\WhatsAppCloudApi\Response\ResponseException;

class WhatsAppController extends BaseController
{
    private $whatsapp;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');

        // Initialize WhatsApp Cloud API client (use env() helper for CodeIgniter 4)
        $phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');
        $accessToken = env('WHATSAPP_ACCESS_TOKEN');

        log_message('critical', '[WhatsApp] Constructor called - Phone ID: ' . $phoneNumberId . ', Token length: ' . strlen($accessToken));

        if (!empty($phoneNumberId) && !empty($accessToken)) {
            $this->whatsapp = new WhatsAppCloudApi([
                'from_phone_number_id' => $phoneNumberId,
                'access_token' => $accessToken,
                'graph_api_version' => 'v18.0',
                'http_client_config' => [
                    'verify' => false // Disable SSL verification for WAMP/Windows
                ]
            ]);
            log_message('critical', '[WhatsApp] API client initialized successfully');
        } else {
            log_message('critical', '[WhatsApp] API client NOT initialized - missing credentials');
        }
    }

    /**
     * Format phone number for WhatsApp (needs country code without +)
     * Input: 777123456 or 0777123456
     * Output: 94777123456
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Remove any spaces, dashes, or + signs
        $phoneNumber = preg_replace('/[\s\-\+]/', '', $phoneNumber);

        // Remove leading zero if present
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = substr($phoneNumber, 1);
        }

        // Add Sri Lanka country code if not present
        if (substr($phoneNumber, 0, 2) !== '94') {
            $phoneNumber = '94' . $phoneNumber;
        }

        return $phoneNumber;
    }

    /**
     * Send WhatsApp message with tour itinerary link
     *
     * @param string $to Phone number
     * @param string $message Message text
     * @param string $type Message type (e.g., "Driver Allocation")
     * @param int|null $tour_id Tour ID
     * @param string|null $token Security token
     * @param string|null $expires_at Token expiration datetime
     * @return bool Success status
     */
    public function sendWhatsApp($to, $message, $type, $tour_id = null, $token = null, $expires_at = null)
    {
        log_message('critical', '[WhatsApp] sendWhatsApp called - To: ' . $to . ', Type: ' . $type . ', Tour ID: ' . $tour_id);

        // Get credentials (use env() helper for CodeIgniter 4)
        $phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');
        $accessToken = env('WHATSAPP_ACCESS_TOKEN');

        if (empty($phoneNumberId) || empty($accessToken)) {
            log_message('critical', '[WhatsApp] FAILED - WhatsApp not configured');
            $this->logWhatsApp($to, $message, $type, 'FAILED', 'WhatsApp not configured', $token, $tour_id, $expires_at);
            return false;
        }

        // Format phone number for WhatsApp
        $formattedPhone = $this->formatPhoneNumber($to);
        log_message('critical', '[WhatsApp] Phone formatted - Original: ' . $to . ', Formatted: ' . $formattedPhone);

        // API endpoint
        $url = "https://graph.facebook.com/v18.0/{$phoneNumberId}/messages";

        // Message payload
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $formattedPhone,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message
            ]
        ];

        $jsonData = json_encode($data);

        log_message('critical', '[WhatsApp] Attempting to send message via cURL...');

        // Initialize cURL
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for WAMP
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        log_message('critical', '[WhatsApp] HTTP Code: ' . $httpCode);

        if ($curlError) {
            log_message('critical', '[WhatsApp] cURL Error - ' . $curlError);
            $this->logWhatsApp($to, $message, $type, 'FAILED', 'cURL Error: ' . $curlError, $token, $tour_id, $expires_at);
            return false;
        }

        $responseData = json_decode($response, true);
        log_message('critical', '[WhatsApp] Response: ' . $response);

        if ($httpCode == 200 && isset($responseData['messages'])) {
            $whatsappMessageId = $responseData['messages'][0]['id'];
            log_message('critical', '[WhatsApp] SUCCESS - Message ID: ' . $whatsappMessageId);

            $this->logWhatsApp(
                $to,
                $message,
                $type,
                'SUCCESS',
                $response,
                $token,
                $tour_id,
                $expires_at,
                $whatsappMessageId
            );

            return true;
        } else {
            log_message('critical', '[WhatsApp] FAILED - Error response from Meta');
            $this->logWhatsApp($to, $message, $type, 'FAILED', $response, $token, $tour_id, $expires_at);
            return false;
        }
    }

    /**
     * Log WhatsApp message attempt to database
     */
    private function logWhatsApp($to, $message, $type, $status, $response, $token, $tour_id, $expires_at, $whatsapp_message_id = null)
    {
        log_message('critical', '[WhatsApp] Logging to database - Status: ' . $status . ', Tour ID: ' . $tour_id);

        $db = \Config\Database::connect();
        $whatsappLogModel = $db->table('whatsapp_log');

        $logData = [
            'to' => $to,
            'message' => $message,
            'type' => $type,
            'status' => $status,
            'system' => 'BACKEND',
            'time' => date('Y-m-d H:i:s'),
            'response' => $response,
            'token' => $token,
            'tour_id' => $tour_id,
            'expires_at' => $expires_at,
            'whatsapp_message_id' => $whatsapp_message_id
        ];

        try {
            $whatsappLogModel->insert($logData);
            log_message('critical', '[WhatsApp] Database log successful');
        } catch (\Exception $e) {
            log_message('critical', '[WhatsApp] Database log FAILED - ' . $e->getMessage());
        }
    }

    /**
     * Generate secure token for itinerary links (shared with SMS)
     */
    public function generateToken($tour_id, $driver_acc_no)
    {
        return hash('sha256', $tour_id . $driver_acc_no . time() . bin2hex(random_bytes(8)));
    }

    /**
     * Validate token from URL (shared with SMS)
     */
    public function validateToken($token)
    {
        $db = \Config\Database::connect();

        // Check both SMS and WhatsApp logs for valid token
        $smsLog = $db->table('sms_log')
            ->where('token', $token)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->where('status', 'SUCCESS')
            ->get()
            ->getRowArray();

        if ($smsLog) {
            return $smsLog;
        }

        $whatsappLog = $db->table('whatsapp_log')
            ->where('token', $token)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->where('status', 'SUCCESS')
            ->get()
            ->getRowArray();

        return $whatsappLog ? $whatsappLog : false;
    }
}
