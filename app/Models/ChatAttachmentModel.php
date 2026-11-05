<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatAttachmentModel extends Model
{
    protected $table = 'chat_attachments';
    protected $primaryKey = 'id';
    protected $allowedFields = ['chat_id', 'attachment', 'attachment_name'];
    protected $returnType = 'array';
}
