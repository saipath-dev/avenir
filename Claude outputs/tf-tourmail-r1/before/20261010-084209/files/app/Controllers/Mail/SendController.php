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

        // tf-mailfix-r1 (10 Oct 2026): a company mailbox (@avenirholidays.com) sends through the company mail
        // server (Config\CompanyMail: smtp.lolipop.jp, 465, SSL) — mail.avenirholidays.com no longer exists;
        // any other mailbox through the host on its user account, as before
        $server = \App\Libraries\TourFlow\MailServer::forAddress((string) $email, (string) $host);
        $host = $server['host'];

        try {
            $mail = new PHPMailer(true); // Create a new PHPMailer instance

            // Server settings
            $mail->isSMTP();
            // tf-letter-r1: the text is UTF-8 (· — ’, Japanese names); PHPMailer would otherwise declare ISO-8859-1
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $email;
            $mail->Password = $emailPassword;
            $mail->SMTPSecure = $server['secure'];
            $mail->Port = $server['port'];
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
            // tf-letter-r1: the logo (and any picture of this site's own) goes inside the e-mail — a localhost address cannot be opened by the hotel
            $mail->Body = \App\Libraries\TourFlow\MailServer::embedLocalImages($mail, (string) $body);

            // Send the email
            if ($mail->send()) {// Log the message ID to email_sents.log

                
                $logMessage = date('Y-m-d H:i:s') . " | TTTo: $to | Subject: $subject";
                file_put_contents(WRITEPATH . 'logs\email_sents.log', $logMessage, FILE_APPEND);
                return 200;
            } else {
                //return error message
                return \App\Libraries\TourFlow\MailServer::readable('Message could not be sent. Mailer Error: ' . $mail->ErrorInfo, (string) $host, (string) $email);
            }
        } catch (Exception $e) {
            // Capture error details
            return \App\Libraries\TourFlow\MailServer::readable('Message could not be sent. Mailer Error: ' . $mail->ErrorInfo, (string) $host, (string) $email);
        }
    }
}
