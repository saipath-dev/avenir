<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Help Assistant' ?> - Avenir Travel</title>
    
    <!-- Include your existing CSS framework -->
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.min.css') ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .chatbot-container {
            max-width: 1000px;
            margin: 20px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .chatbot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .chatbot-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .chatbot-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        
        .chat-area {
            height: 500px;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        
        .message.user {
            justify-content: flex-end;
        }
        
        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
        }
        
        .message.user .message-content {
            background: #007bff;
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message.assistant .message-content {
            background: white;
            color: #333;
            border: 1px solid #e9ecef;
            border-bottom-left-radius: 4px;
        }
        
        .message-steps {
            margin-top: 10px;
            padding-left: 0;
            list-style: none;
        }
        
        .message-steps li {
            padding: 5px 0;
            border-left: 3px solid #007bff;
            padding-left: 12px;
            margin-bottom: 5px;
            background: rgba(0, 123, 255, 0.05);
        }
        
        .quick-actions {
            margin-top: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .quick-action-btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            text-decoration: none;
            color: #495057;
            font-size: 12px;
            transition: all 0.2s;
        }
        
        .quick-action-btn:hover {
            background: #007bff;
            color: white;
            text-decoration: none;
        }
        
        .quick-action-btn i {
            margin-right: 5px;
            font-size: 10px;
        }
        
        .input-area {
            padding: 20px;
            background: white;
        }
        
        .input-group {
            display: flex;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        
        .chat-input:focus {
            border-color: #007bff;
        }
        
        .send-btn {
            width: 50px;
            height: 50px;
            border: none;
            background: #007bff;
            color: white;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .send-btn:hover:not(:disabled) {
            background: #0056b3;
        }
        
        .send-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .suggestions-panel {
            background: white;
            border-top: 1px solid #dee2e6;
            padding: 20px;
        }
        
        .suggestions-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #495057;
        }
        
        .suggestion-categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .suggestion-category {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }
        
        .category-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #007bff;
        }
        
        .suggestion-item {
            display: block;
            padding: 8px 0;
            color: #6c757d;
            text-decoration: none;
            font-size: 14px;
            border-bottom: 1px solid transparent;
            transition: all 0.2s;
        }
        
        .suggestion-item:hover {
            color: #007bff;
            text-decoration: none;
            border-bottom-color: #007bff;
        }
        
        .typing-indicator {
            display: none;
            margin-bottom: 15px;
        }
        
        .typing-dots {
            display: inline-block;
            position: relative;
            width: 40px;
            height: 20px;
            background: white;
            border-radius: 10px;
            padding: 5px 10px;
            border: 1px solid #e9ecef;
        }
        
        .typing-dots span {
            display: inline-block;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #007bff;
            margin: 0 1px;
            animation: typing 1.4s infinite;
        }
        
        .typing-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
            }
            30% {
                transform: translateY(-10px);
            }
        }
        
        .welcome-message {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .welcome-message i {
            font-size: 48px;
            color: #007bff;
            margin-bottom: 15px;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .chatbot-container {
                margin: 10px;
                border-radius: 0;
            }
            
            .suggestion-categories {
                grid-template-columns: 1fr;
            }
            
            .message-content {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <!-- Include your header/navigation here -->
    <?= view('layouts/header') ?>
    
    <div class="container-fluid">
        <div class="chatbot-container">
            <!-- Header -->
            <div class="chatbot-header">
                <h1><i class="fas fa-robot"></i> Help Assistant</h1>
                <p>Ask me anything about using the Avenir Travel Management System</p>
            </div>
            
            <!-- Chat Area -->
            <div class="chat-area" id="chatArea">
                <div class="welcome-message">
                    <i class="fas fa-comments"></i>
                    <h3>Welcome to your Help Assistant!</h3>
                    <p>I'm here to help you with any questions about using the system.<br>
                    Try asking something like "How do I create a tour quotation?" or choose from the suggestions below.</p>
                </div>
                
                <!-- Typing indicator -->
                <div class="typing-indicator" id="typingIndicator">
                    <div class="message assistant">
                        <div class="typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Input Area -->
            <div class="input-area">
                <div class="input-group">
                    <input type="text" 
                           class="chat-input" 
                           id="chatInput" 
                           placeholder="Ask me how to do something in the system..."
                           autocomplete="off">
                    <button class="send-btn" id="sendBtn" type="button">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
            
            <!-- Suggestions Panel -->
            <div class="suggestions-panel">
                <h3 class="suggestions-title">Popular Questions</h3>
                <div class="suggestion-categories" id="suggestionsContainer">
                    <?php if (isset($suggested_questions) && !empty($suggested_questions)): ?>
                        <?php foreach ($suggested_questions as $category => $questions): ?>
                            <div class="suggestion-category">
                                <div class="category-title"><?= esc($category) ?></div>
                                <?php foreach (array_slice($questions, 0, 4) as $question): ?>
                                    <a href="#" class="suggestion-item" data-question="<?= esc($question) ?>">
                                        <?= esc($question) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include your existing JS libraries -->
    <script src="<?= base_url('assets/bundles/libscripts.bundle.js') ?>"></script>
    <script src="<?= base_url('assets/bundles/vendorscripts.bundle.js') ?>"></script>
    
    <script>
        class ChatbotInterface {
            constructor() {
                this.chatArea = document.getElementById('chatArea');
                this.chatInput = document.getElementById('chatInput');
                this.sendBtn = document.getElementById('sendBtn');
                this.typingIndicator = document.getElementById('typingIndicator');
                
                this.initializeEventListeners();
                this.removeWelcomeMessage();
            }
            
            initializeEventListeners() {
                // Send button click
                this.sendBtn.addEventListener('click', () => this.sendMessage());
                
                // Enter key press
                this.chatInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                });
                
                // Suggestion clicks
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('suggestion-item')) {
                        e.preventDefault();
                        const question = e.target.getAttribute('data-question');
                        this.chatInput.value = question;
                        this.sendMessage();
                    }
                });
                
                // Quick action clicks
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('quick-action-btn')) {
                        // Let the link work normally, just track the click
                        console.log('Quick action clicked:', e.target.href);
                    }
                });
            }
            
            removeWelcomeMessage() {
                // Remove welcome message when user starts typing
                this.chatInput.addEventListener('input', () => {
                    const welcomeMsg = this.chatArea.querySelector('.welcome-message');
                    if (welcomeMsg && this.chatInput.value.length > 0) {
                        welcomeMsg.style.display = 'none';
                    }
                });
            }
            
            async sendMessage() {
                const message = this.chatInput.value.trim();
                if (!message) return;
                
                // Disable input
                this.setInputState(false);
                
                // Add user message
                this.addMessage(message, 'user');
                
                // Clear input
                this.chatInput.value = '';
                
                // Show typing indicator
                this.showTypingIndicator();
                
                try {
                    // Send to backend
                    const response = await this.sendToBackend(message);
                    
                    // Hide typing indicator
                    this.hideTypingIndicator();
                    
                    if (response.success) {
                        this.addMessage(response.response.text, 'assistant', response.response);
                    } else {
                        this.addErrorMessage(response.error || 'Sorry, I encountered an error. Please try again.');
                    }
                } catch (error) {
                    this.hideTypingIndicator();
                    this.addErrorMessage('Connection error. Please check your internet connection and try again.');
                    console.error('Chat error:', error);
                }
                
                // Re-enable input
                this.setInputState(true);
                this.chatInput.focus();
            }
            
            async sendToBackend(message) {
                const formData = new FormData();
                formData.append('query', message);
                
                const response = await fetch('<?= base_url('ai/chatbot/query') ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return await response.json();
            }
            
            addMessage(text, sender, responseData = null) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${sender}`;
                
                let content = `<div class="message-content">${this.formatMessage(text)}`;
                
                // Add steps if available
                if (responseData && responseData.steps && responseData.steps.length > 0) {
                    content += '<ol class="message-steps">';
                    responseData.steps.forEach(step => {
                        content += `<li>${this.escapeHtml(step.text)}</li>`;
                    });
                    content += '</ol>';
                }
                
                content += '</div>';
                
                // Add quick actions if available
                if (responseData && responseData.quick_actions && responseData.quick_actions.length > 0) {
                    content += '<div class="quick-actions">';
                    responseData.quick_actions.forEach(action => {
                        content += `<a href="${action.url}" class="quick-action-btn" target="_blank">
                            <i class="${action.icon || 'fas fa-link'}"></i>
                            ${this.escapeHtml(action.text)}
                        </a>`;
                    });
                    content += '</div>';
                }
                
                messageDiv.innerHTML = content;
                this.chatArea.appendChild(messageDiv);
                this.scrollToBottom();
            }
            
            addErrorMessage(errorText) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = errorText;
                this.chatArea.appendChild(errorDiv);
                this.scrollToBottom();
            }
            
            formatMessage(text) {
                // Convert line breaks to <br>
                text = this.escapeHtml(text);
                text = text.replace(/\n/g, '<br>');
                
                // Convert numbered lists
                text = text.replace(/(\d+)\.\s/g, '<strong>$1.</strong> ');
                
                // Highlight navigation instructions
                text = text.replace(/Go to ([^→]+)→([^<\n]+)/g, 'Go to <strong>$1 → $2</strong>');
                
                return text;
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            showTypingIndicator() {
                this.typingIndicator.style.display = 'block';
                this.scrollToBottom();
            }
            
            hideTypingIndicator() {
                this.typingIndicator.style.display = 'none';
            }
            
            setInputState(enabled) {
                this.chatInput.disabled = !enabled;
                this.sendBtn.disabled = !enabled;
            }
            
            scrollToBottom() {
                setTimeout(() => {
                    this.chatArea.scrollTop = this.chatArea.scrollHeight;
                }, 100);
            }
        }
        
        // Initialize chatbot when page loads
        document.addEventListener('DOMContentLoaded', () => {
            new ChatbotInterface();
        });
    </script>
</body>
</html>