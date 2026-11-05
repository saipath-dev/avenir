<?php

namespace App\Controllers\Mail;

use App\Controllers\BaseController;
use App\Models\ConsigneeModel;
use App\Models\EmailModel;

class InboxController6 extends BaseController
{
    public function filterEmailsByThread($threadId)
    {
        // Fetch emails using IMAP with secure credentials from environment variables
        $hostname = getenv('EMAIL_HOSTNAME');
        $username = getenv('EMAIL_USERNAME');
        $password = getenv('EMAIL_PASSWORD');

        // Open IMAP connection
        $inbox = @imap_open($hostname, $username, $password);

        if (!$inbox) {
            // Log the error and return a meaningful response
            log_message('error', 'Cannot connect to mail server: ' . imap_last_error());
            return ['error' => 'Unable to connect to mail server.'];
        }

        // Search for all emails
        $emails = imap_search($inbox, 'ALL');
        $filteredEmails = [];

        if ($emails) {
            rsort($emails); // Sort emails from newest to oldest

            foreach ($emails as $email_number) {
                $overview = imap_fetch_overview($inbox, $email_number, 0)[0];
                $header = imap_fetchheader($inbox, $email_number);
                $parsed_header = imap_rfc822_parse_headers($header);

                // Get message_id, in_reply_to, and references
                $messageId = $parsed_header->message_id ?? '';
                $inReplyTo = $parsed_header->in_reply_to ?? '';
                $references = $parsed_header->references ?? '';

                // Filter by threadId (can be message_id, in_reply_to, or references)
                if ($messageId === $threadId || strpos($references, $threadId) !== false || $inReplyTo === $threadId) {

                    // Fetch email body as HTML
                    $body = $this->getBody($inbox, $email_number);

                    // Fetch attachments if needed
                    $structure = imap_fetchstructure($inbox, $email_number);
                    $attachments = $this->getAttachments($structure, $email_number, $inbox);

                    $filteredEmails[] = [
                        'msgno' => $overview->msgno,
                        'uid' => imap_uid($inbox, $email_number),
                        'subject' => $overview->subject,
                        'from' => $overview->from,
                        'to' => $overview->to,
                        'date' => $overview->date,
                        'seen' => $overview->seen,
                        'message_id' => $messageId,
                        'in_reply_to' => $inReplyTo,
                        'references' => $references,
                        'body' => $body,  // Include the email body as HTML
                        'attachments' => $attachments, // Include attachments if fetched
                    ];
                }
            }
        }

        // Close the IMAP connection
        imap_close($inbox);

        // Return the filtered emails
        return $filteredEmails;
    }

    private function getBody($imap, $msg_number)
    {
        $body = $this->get_part($imap, $msg_number, "TEXT/HTML");
        if ($body === false || trim($body) === "") {
            $body = $this->get_part($imap, $msg_number, "TEXT/PLAIN");
        }

        return $body ?? '';
    }

    private function get_part($imap, $msg_number, $mime_type, $structure = false, $part_number = false)
    {
        if (!$structure) {
            $structure = imap_fetchstructure($imap, $msg_number);
        }
        if ($structure) {
            if ($this->get_mime_type($structure) == $mime_type) {
                if (!$part_number) {
                    $part_number = "1";
                }
                $text = imap_fetchbody($imap, $msg_number, $part_number);
                switch ($structure->encoding) {
                    case 3:
                        return imap_base64($text);
                    case 4:
                        return imap_qprint($text);
                    default:
                        return $text;
                }
            }

            // If multipart, iterate through parts
            if ($structure->type == 1 && isset($structure->parts)) {
                foreach ($structure->parts as $index => $sub_structure) {
                    $prefix = $part_number ? $part_number . '.' : '';
                    $data = $this->get_part($imap, $msg_number, $mime_type, $sub_structure, $prefix . ($index + 1));
                    if ($data) {
                        return $data;
                    }
                }
            }
        }
        return false;
    }

    private function get_mime_type($structure)
    {
        $primary_mime_types = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];

        if (isset($structure->subtype) && isset($structure->type)) {
            return strtoupper($primary_mime_types[$structure->type]) . "/" . strtoupper($structure->subtype);
        }
        return "TEXT/PLAIN";
    }

    private function getAttachments($structure, $email_number, $imap, $part_number = '')
    {
        $attachments = [];

        if (isset($structure->parts) && count($structure->parts)) {
            foreach ($structure->parts as $index => $sub_structure) {
                $current_part_number = $part_number ? $part_number . '.' . ($index + 1) : ($index + 1);

                // Determine if the part is an attachment
                $is_attachment = false;
                $filename = '';
                $name = '';

                if ($sub_structure->ifdparameters) {
                    foreach ($sub_structure->dparameters as $object) {
                        if (strtolower($object->attribute) == 'filename') {
                            $filename = $object->value;
                            $is_attachment = true;
                        }
                    }
                }

                if ($sub_structure->ifparameters) {
                    foreach ($sub_structure->parameters as $object) {
                        if (strtolower($object->attribute) == 'name') {
                            $name = $object->value;
                            $is_attachment = true;
                        }
                    }
                }

                $attachment_name = $filename ?: $name;

                if ($is_attachment) {
                    $attachments[] = [
                        'filename' => $attachment_name,
                        'mime_type' => $this->get_mime_type($sub_structure),
                        'encoding' => $sub_structure->encoding,
                        'part_number' => $current_part_number
                    ];
                }

                // Recursively get attachments in subparts
                if (isset($sub_structure->parts) && count($sub_structure->parts)) {
                    $attachments = array_merge($attachments, $this->getAttachments($sub_structure, $email_number, $imap, $current_part_number));
                }
            }
        }

        return $attachments;
    }

    public function downloadAttachment($uid, $partNumber)
    {
        // Fetch email credentials from environment variables
        $hostname = getenv('EMAIL_HOSTNAME');
        $username = getenv('EMAIL_USERNAME');
        $password = getenv('EMAIL_PASSWORD');

        // Open IMAP connection
        $inbox = imap_open($hostname, $username, $password);
        if (!$inbox) {
            // Log the error and redirect with an error message
            log_message('error', 'Cannot connect to mail server: ' . imap_last_error());
            return redirect()->back()->with('error', 'Unable to connect to mail server.');
        }

        // Fetch the email number using UID
        $emailNumber = imap_msgno($inbox, $uid);
        if (!$emailNumber) {
            imap_close($inbox);
            return redirect()->back()->with('error', 'Email not found.');
        }

        // Fetch the email structure
        $structure = imap_fetchstructure($inbox, $emailNumber);
        if (!$structure || !isset($structure->parts[$partNumber - 1])) {
            imap_close($inbox);
            return redirect()->back()->with('error', 'Attachment not found.');
        }

        // Retrieve the specific part
        $attachment = $structure->parts[$partNumber - 1];
        $attachmentData = imap_fetchbody($inbox, $emailNumber, $partNumber);

        // Decode the attachment based on encoding
        switch ($attachment->encoding) {
            case 3: // BASE64
                $attachmentData = base64_decode($attachmentData);
                break;
            case 4: // QUOTED-PRINTABLE
                $attachmentData = quoted_printable_decode($attachmentData);
                break;
            default:
                // Handle other encodings if necessary
                break;
        }

        // Determine the filename
        $filename = 'attachment';
        if (isset($attachment->dparameters)) {
            foreach ($attachment->dparameters as $param) {
                if (strtolower($param->attribute) === 'filename') {
                    $filename = $param->value;
                    break;
                }
            }
        }

        // Set headers to initiate file download
        $response = service('response');
        $response->setHeader('Content-Type', $attachment->type . '/' . $attachment->subtype);
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setHeader('Content-Length', strlen($attachmentData));

        // Return the attachment data as a response
        $response->setBody($attachmentData);
        imap_close($inbox);
        return $response;
    }

    public function fetchUnreadEmails()
    {
        // Fetch emails using IMAP with secure credentials from environment variables
        $hostname = getenv('EMAIL_HOSTNAME');
        $username = getenv('EMAIL_USERNAME');
        $password = getenv('EMAIL_PASSWORD');

        // Open IMAP connection
        $inbox = @imap_open($hostname, $username, $password);

        if (!$inbox) {
            // Log the error and throw an exception
            log_message('error', 'Cannot connect to mail server: ' . imap_last_error());
            throw new Exception('Unable to connect to mail server.');
        }

        // Search for all unread emails
        $emails = imap_search($inbox, 'UNSEEN');
        $unreadEmails = [];

        if ($emails) {
            $emails_overview = imap_fetch_overview($inbox, implode(',', $emails), 0);

            $emailModel = new EmailModel();
            $existingEmails = $emailModel->findAll();
            $mailIdToTourId = array_column($existingEmails, 'tour_id', 'mail_id');
            $mailIdToTourQuotationId = array_column($existingEmails, 'tour_quotation_id', 'mail_id'); // Map mail_id to tour_quotation_id
            $consigneeModel = new ConsigneeModel();

            foreach ($emails_overview as $overview) {
                $messageIds = array_filter([
                    $overview->message_id ?? null,
                    $overview->references ?? null,
                    $overview->in_reply_to ?? null,
                ]);

                $foundTourId = null;
                $foundTourQuotationId = null; // Initialize foundTourQuotationId
                foreach ($messageIds as $messageId) {
                    if (isset($mailIdToTourId[$messageId])) {
                        $foundTourId = $mailIdToTourId[$messageId];
                        $foundTourQuotationId = $mailIdToTourQuotationId[$messageId]; // Get the tour_quotation_id
                        break;
                    }
                }

                if ($foundTourId !== null) {
                    $dates = $consigneeModel->getDatesByTourId($foundTourId);
                    $unreadEmails[] = [
                        'msgno' => $overview->msgno,
                        'uid' => imap_uid($inbox, $overview->msgno),
                        'subject' => $overview->subject,
                        'from' => $overview->from,
                        'to' => $overview->to,
                        'date' => $overview->date,
                        'seen' => $overview->seen,
                        'message_id' => $overview->message_id,
                        'in_reply_to' => $overview->in_reply_to,
                        'references' => $overview->references,
                        'tour_id' => $foundTourId,
                        'tour_quotation_id' => $foundTourQuotationId,
                        'start_date' => $dates['start_date'] ?? null,
                        'end_date' => $dates['end_date'] ?? null,
                    ];
                }
            }
        }

        // Close the IMAP connection
        imap_close($inbox);

        // Return unread emails
        return $unreadEmails;
    }

    public function getUnreadEmails()
    {
        try {
            // Call fetchUnreadEmails to get the emails
            $unreadEmails = $this->fetchUnreadEmails();

            // Check if there are unread emails
            if (!empty($unreadEmails)) {
                return $this->response->setJSON(['status' => 200, 'data' => $unreadEmails]);
            } else {
                return $this->response->setJSON(['status' => 204, 'message' => 'No unread emails.']);
            }
        } catch (Exception $e) {
            // Handle any unexpected exceptions
            return $this->response->setJSON(['status' => 500, 'error' => $e->getMessage()]);
        }
    }

}
