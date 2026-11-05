<?php

namespace App\Controllers\Mail;

use App\Controllers\BaseController;
use App\Models\UserModel;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class SendController extends BaseController
{

    public function send($to, $subject, $body, $cc = null, $attachments = [], $tour_quotation_id = null, $tour_id = null)
    {
        require ROOTPATH . 'vendor/phpmailer/phpmailer/src/Exception.php';
        require ROOTPATH . 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require ROOTPATH . 'vendor/phpmailer/phpmailer/src/SMTP.php';

        $userId = session()->get('user_id');

        // Load the user's email and email password
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        // If user email and password is not set, return error
        if (empty($user['email']) || empty($user['email_password'])) {
            return 404;
        }

        $host = $user['host'];
        $email = $user['email'];
        $emailPassword = $user['email_password'];

        if (empty($host) || empty($email) || empty($emailPassword)) {
            return 'Email configuration is not set.';
        }

        try {
            $mail = new PHPMailer(true); // Create a new PHPMailer instance

            // Server settings
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $email;
            $mail->Password = $emailPassword;
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom($email, 'Avenir Holidays');
            $mail->addAddress($to);

            // Add CCs if provided
            if ($cc) {
                foreach ($cc as $ccx) {
                    $mail->addCC($ccx);
                }
            }

            // Attachments
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    // Each $attachment can be a file path, or an associative array with 'path' and 'name'
                    if (is_array($attachment) && isset($attachment['path'])) {
                        $filePath = $attachment['path'];
                        $fileName = isset($attachment['name']) ? $attachment['name'] : basename($filePath);
                    } else {
                        $filePath = $attachment; // If the $attachment is just a file path
                        $fileName = basename($filePath); // Use the file name as the attachment name
                    }

                    // Add the attachment
                    $mail->addAttachment($filePath, $fileName);
                }
            }

            // Email content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            // Send the email
            if ($mail->send()) {// Log the message ID to email_sents.log

                
                $logMessage = date('Y-m-d H:i:s') . " | TTTo: $to | Subject: $subject";
                file_put_contents(WRITEPATH . 'logs\email_sents.log', $logMessage, FILE_APPEND);
                return 200;
            } else {
                //return error message
                return 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
            }
        } catch (Exception $e) {
            // Capture error details
            return 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        }
    }
}
