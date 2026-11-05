<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SetupChatbot extends BaseCommand
{
    protected $group = 'AI';
    protected $name = 'chatbot:setup';
    protected $description = 'Setup the AI Chatbot system (create database table)';

    public function run(array $params)
    {
        CLI::write('Setting up AI Chatbot...', 'green');
        
        try {
            // Run migration
            $migrate = \Config\Services::migrations();
            $migrate->setNamespace('App');
            
            if ($migrate->latest()) {
                CLI::write('✓ Database table created successfully', 'green');
            } else {
                CLI::write('✓ Database table already exists', 'yellow');
            }
            
            // Check knowledge base files
            $knowledgeBasePath = ROOTPATH . 'Documentation/AI';
            $jsonFiles = glob($knowledgeBasePath . '/*.json');
            
            CLI::write('✓ Found ' . count($jsonFiles) . ' knowledge base files', 'green');
            
            foreach ($jsonFiles as $file) {
                CLI::write('  - ' . basename($file), 'cyan');
            }
            
            CLI::newLine();
            CLI::write('Setup complete! Next steps:', 'green');
            CLI::write('1. Add your Gemini API key to app/Config/Gemini.php', 'yellow');
            CLI::write('2. Access the chatbot at: /ai/chatbot', 'yellow');
            CLI::newLine();
            
        } catch (\Exception $e) {
            CLI::write('Error during setup: ' . $e->getMessage(), 'red');
            return EXIT_ERROR;
        }
        
        return EXIT_SUCCESS;
    }
}