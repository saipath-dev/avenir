<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatModel extends Model
{
    protected $table = 'chats';
    protected $primaryKey = 'id';
    protected $allowedFields = ['conversation_id', 'agent', 'user', 'time', 'message', 'from_agent', 'seen'];
    protected $returnType = 'array';
}
