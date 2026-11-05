<?php

namespace App\Controllers\Mail;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\StaffMemberModel;

class SentController extends BaseController
{
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Load employee data
        $employeeModel = new StaffMemberModel();
        $data['employee'] = $employeeModel->where('empno', session()->get('emp_id'))->first();


        $data['employee_name'] = $data['employee']['fullname'];
        $data['emp_code'] = session()->get('emp_id');
        $data['sidebar_employee_prof_pic'] = $data['employee']['profile_pic'] ?? base_url('assets/images/default_profile.png');

        // Fetch emails using IMAP
        $hostname = '{mail.esrilanka.org:993/imap/ssl}INBOX';
        $username = 'pamu@esrilanka.org';
        $password = 'Pamu@77557'; // Handle passwords securely

        // Open IMAP connection
        $inbox = imap_open($hostname, $username, $password) or die('Cannot connect to mail server: ' . imap_last_error());

        // Search for all emails
        $emails = imap_search($inbox, 'ALL');

        $emailList = [];

        if ($emails) {
            rsort($emails); // Sort emails from newest to oldest

            foreach ($emails as $email_number) {
                $overview = imap_fetch_overview($inbox, $email_number, 0)[0];

                $emailList[] = [
                    'msgno' => $overview->msgno,
                    'uid' => imap_uid($inbox, $email_number),
                    'subject' => $overview->subject,
                    'from' => $overview->from,
                    'to' => $overview->to,
                    'date' => $overview->date,
                    'seen' => $overview->seen,
                ];
            }
        }

        // Close the IMAP connection
        imap_close($inbox);

        // Pass emails to the view
        $data['emails'] = $emailList;

        // Render the view with the loaded data
        return view('pages/mail/sent', $data);
    }

    public function getEmailBody($uid)
    {
        // Email credentials
        $hostname = getenv('EMAIL_HOSTNAME');
        $username = getenv('EMAIL_USERNAME');
        $password = getenv('EMAIL_PASSWORD');

        // Open IMAP connection
        $inbox = imap_open($hostname, $username, $password);

        // Fetch email number from UID
        $email_number = imap_msgno($inbox, $uid);

        if ($email_number) {
            $overview = imap_fetch_overview($inbox, $email_number, 0)[0];
            $body = $this->getBody($inbox, $email_number);

            // Fetch email structure
            $structure = imap_fetchstructure($inbox, $email_number);

            // Get attachments
            $attachments = $this->getAttachments($structure, $email_number, $inbox);

            // Close the IMAP connection
            imap_close($inbox);

            // Return the email details as JSON
            return $this->response->setJSON([
                'subject' => $overview->subject,
                'from' => $overview->from,
                'date' => $overview->date,
                'body' => $body,
                'attachments' => $attachments
            ]);
        } else {
            // Close the IMAP connection
            imap_close($inbox);

            // Return an error response
            return $this->response->setStatusCode(404)->setBody('Email not found');
        }
    }

    private function getBody($imap, $msg_number)
    {
        $body = $this->get_part($imap, $msg_number, "TEXT/HTML");
        if ($body == "") {
            $body = $this->get_part($imap, $msg_number, "TEXT/PLAIN");
        }
        return $body;
    }

    private function get_part($imap, $msg_number, $mime_type, $structure = false, $part_number = false)
    {
        if (!$structure) {
            $structure = imap_fetchstructure($imap, $msg_number);
        }
        if ($structure) {
            if ($mime_type == $this->get_mime_type($structure)) {
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

            if ($structure->type == 1) {
                foreach ($structure->parts as $index => $sub_structure) {
                    $prefix = "";
                    if ($part_number) {
                        $prefix = $part_number . ".";
                    }
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
        $primary_mime_type = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];

        if ($structure->subtype) {
            return $primary_mime_type[(int) $structure->type] . "/" . $structure->subtype;
        }
        return "TEXT/PLAIN";
    }

    private function getAttachments($structure, $email_number, $imap, $part_number = '')
    {
        $attachments = [];

        if (isset($structure->parts) && count($structure->parts)) {
            foreach ($structure->parts as $index => $sub_structure) {
                $current_part_number = $part_number ? $part_number . '.' . ($index + 1) : ($index + 1);

                // If the part is an attachment
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

    public function downloadAttachment($uid, $part_number)
    {
        // Email credentials
        $hostname = getenv('EMAIL_HOSTNAME');
        $username = getenv('EMAIL_USERNAME');
        $password = getenv('EMAIL_PASSWORD');

        // Open IMAP connection
        $inbox = imap_open($hostname, $username, $password);

        // Fetch email number from UID
        $email_number = imap_msgno($inbox, $uid);

        if ($email_number) {
            // Fetch email structure
            $structure = imap_fetchstructure($inbox, $email_number);

            // Fetch the attachment data
            $attachment = $this->getAttachmentData($inbox, $email_number, $structure, $part_number);

            if ($attachment) {
                // Close the IMAP connection
                imap_close($inbox);

                // Set headers for download
                return $this->response
                    ->setHeader('Content-Type', $attachment['mime_type'])
                    ->setHeader('Content-Disposition', 'attachment; filename="' . $attachment['filename'] . '"')
                    ->setBody($attachment['data']);
            } else {
                // Close the IMAP connection
                imap_close($inbox);

                // Attachment not found
                return $this->response->setStatusCode(404)->setBody('Attachment not found');
            }
        } else {
            // Close the IMAP connection
            imap_close($inbox);

            // Email not found
            return $this->response->setStatusCode(404)->setBody('Email not found');
        }
    }

    private function getAttachmentData($imap, $msg_number, $structure, $part_number)
    {
        // Fetch the attachment data
        $data = imap_fetchbody($imap, $msg_number, $part_number);

        // Find the part in the structure
        $part = $this->findPart($structure, explode('.', $part_number));

        if ($part) {
            // Decode the data
            switch ($part->encoding) {
                case 0:
                    $data = $data; // 7BIT
                    break;
                case 1:
                    $data = imap_8bit($data);
                    break;
                case 2:
                    $data = imap_binary($data);
                    break;
                case 3:
                    $data = imap_base64($data);
                    break;
                case 4:
                    $data = imap_qprint($data);
                    break;
                default:
                    $data = $data;
                    break;
            }

            // Get the filename
            $filename = $this->getFilenameFromPart($part);

            // Get the MIME type
            $mime_type = $this->get_mime_type($part);

            return [
                'filename' => $filename,
                'mime_type' => $mime_type,
                'data' => $data
            ];
        }

        return null;
    }

    private function findPart($structure, $part_numbers)
    {
        if (empty($part_numbers)) {
            return $structure;
        }

        $index = array_shift($part_numbers) - 1;

        if (isset($structure->parts[$index])) {
            return $this->findPart($structure->parts[$index], $part_numbers);
        }

        return null;
    }

    private function getFilenameFromPart($part)
    {
        $filename = '';

        if ($part->ifdparameters) {
            foreach ($part->dparameters as $object) {
                if (strtolower($object->attribute) == 'filename') {
                    $filename = $object->value;
                    break;
                }
            }
        }

        if (!$filename && $part->ifparameters) {
            foreach ($part->parameters as $object) {
                if (strtolower($object->attribute) == 'name') {
                    $filename = $object->value;
                    break;
                }
            }
        }

        return $filename ?: 'attachment.bin';
    }

    // Reply to an email
    public function reply($uid)
    {
        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Fetch the email information based on UID
        $hostname = getenv('EMAIL_HOSTNAME');
        $username = getenv('EMAIL_USERNAME');
        $password = getenv('EMAIL_PASSWORD');

        // Load employee data
        $employeeModel = new StaffMemberModel();
        $data['employee'] = $employeeModel->where('empno', session()->get('emp_id'))->first();

        $data['employee_name'] = $data['employee']['fullname'];
        $data['emp_code'] = session()->get('emp_id');

        // Open IMAP connection
        $inbox = imap_open($hostname, $username, $password);
        $email_number = imap_msgno($inbox, $uid);

        if ($email_number) {
            $overview = imap_fetch_overview($inbox, $email_number, 0)[0];
            $body = $this->getBody($inbox, $email_number);

            imap_close($inbox);

            // Format the reply message to resemble Gmail's style with proper line breaks and indentation
            $replyBody = "\n\n" .
                "--\nOn " . date('D, M d, Y \a\t h:i A', strtotime($overview->date)) . " " . $overview->from . " wrote:\n\n" .
                "> " . str_replace("\n", "\n> ", strip_tags($body));  // Add ">" before each line for quoting and preserve line breaks

            // Pre-fill the reply form with "to", "subject", and the formatted original message
            $data['to'] = $overview->from;
            $data['subject'] = 'Re: ' . $overview->subject;  // Prefix 'Re: ' to the subject for reply
            $data['body'] = $replyBody;

            return view('pages/mail/compose', $data);  // Render the compose view with pre-filled data
        } else {
            imap_close($inbox);
            return redirect()->back()->with('error', 'Email not found.');
        }
    }

    // Forward an email
    public function forward($uid)
    {
        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Fetch the email information based on UID
        $hostname = getenv('EMAIL_HOSTNAME');
        $username = getenv('EMAIL_USERNAME');
        $password = getenv('EMAIL_PASSWORD');

        // Open IMAP connection
        $inbox = imap_open($hostname, $username, $password);
        $email_number = imap_msgno($inbox, $uid);

        if ($email_number) {
            $overview = imap_fetch_overview($inbox, $email_number, 0)[0];
            $body = $this->getBody($inbox, $email_number);

            imap_close($inbox);

            // Pre-fill the forward form with "subject" and the original message
            $data['to'] = '';  // Empty 'to' field for forwarding
            $data['subject'] = 'Fwd: ' . $overview->subject;  // Prefix 'Fwd: ' to the subject for forwarding
            $data['body'] = "\n\n---------- Forwarded message ----------\n" .
                "From: " . $overview->from . "\n" .
                "Date: " . $overview->date . "\n" .
                "Subject: " . $overview->subject . "\n\n" .
                strip_tags($body);  // Original message

            return view('pages/mail/compose', $data);  // Render the compose view with pre-filled data
        } else {
            imap_close($inbox);
            return redirect()->back()->with('error', 'Email not found.');
        }
    }
}
