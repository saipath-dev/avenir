<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChatbotLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false
            ],
            'query' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'response' => [
                'type' => 'LONGTEXT',
                'null' => true
            ],
            'source' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'default' => 'gemini'
            ],
            'relevance_score' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'default' => 0.00
            ],
            'processing_time' => [
                'type' => 'DECIMAL',
                'constraint' => '8,3',
                'null' => true,
                'default' => 0.000
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'session_id' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('created_at');
        $this->forge->addKey('source');
        
        $this->forge->createTable('chatbot_logs');
    }

    public function down()
    {
        $this->forge->dropTable('chatbot_logs');
    }
}