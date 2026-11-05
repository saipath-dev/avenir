<?php

namespace App\Controllers\Mail;

use App\Controllers\BaseController;
use Config\Services;

class SendController2 extends BaseController
{
    public function send($to, $subject, $body, $cc = null, $attachments = [])
    {
        // Load email service
        $email = Services::email();

        // Set email details from config
        $email->setFrom(config('Email')->fromEmail, config('Email')->fromName);
        $email->setTo($to);

        if (!empty($cc)) {
            $email->setCC($cc);
        }

        $email->setSubject($subject);
        $email->setMessage($body);

        // Handle file attachments if any
        if ($attachments) {
            if (isset($attachments['attachments']) && is_array($attachments['attachments'])) {
                foreach ($attachments['attachments'] as $attachment) {
                    if ($attachment->isValid() && !$attachment->hasMoved()) {
                        $email->attach($attachment->getTempName(), 'attachment', $attachment->getClientName());
                    }
                }
            }
        }

        // Send the email and handle success/failure
        if ($email->send()) {
            return true;
        } else {
            // Capture detailed error info for debugging
            $error = $email->printDebugger(['headers']);
            return $error;
        }
    }
}
