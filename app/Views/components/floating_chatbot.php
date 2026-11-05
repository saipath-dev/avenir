
<!-- Modern Chat Interface -->
<div id="floating-chatbot" class="modern-chat-widget">
    <!-- Chat Toggle Button -->
    <div id="chat-toggle" class="chat-toggle-btn">
        <img src="<?= base_url('assets/icons/chat.png') ?>" alt="Chat Icon" style="width: 50px; height: 50px;">
        <span class="notification-badge" id="chat-badge" style="display: none;">1</span>
    </div>
    
    <!-- Chat Container -->
    <div id="chat-widget" class="chat-container">
        <!-- Header -->
        <div class="chat-header">
            <div class="header-content">
                <div class="assistant-avatar">
                    <img src="<?= base_url('assets/icons/chat.png') ?>" alt="Assistant Avatar" width="30" height="30">
                </div>
                <div class="header-text">
                    <div class="header-title">Help Assistant</div>
                    <div class="header-subtitle">Ask me anything about the system</div>
                </div>
            </div>
            <button id="chat-close" class="header-close">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Chat Messages Area -->
        <div class="chat-messages-area">
            <div id="chat-messages" class="messages-container">
                <!-- Welcome Message -->
                <div class="message-group">
                    <div class="assistant-message">
                        <div class="assistant-avatar">
                            <img id="welcome-avatar" src="<?= base_url('assets/icons/chat.png') ?>" alt="Assistant Avatar" width="30" height="30">
                        </div>
                        <div class="message-bubble assistant">
                            <div class="message-text">
                                Hi! I'm your Help Assistant. Ask me anything about using the system!
                            </div>
                            <div class="message-timestamp"><?= date('H:i') ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Typing Indicator -->
            <div id="chat-typing" class="typing-indicator" style="display: none;">
                <div class="typing-message">
                    <div class="assistant-avatar">
                        <img id="typing-avatar" src="<?= base_url('assets/icons/Typing.gif') ?>" alt="Assistant Avatar" width="30" height="30">
                    </div>
                    <div class="typing-bubble">
                        <div class="typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Input Area -->
        <div class="input-area">
            <div class="input-container">
                <input 
                    type="text" 
                    id="chat-input" 
                    class="text-input"
                    placeholder="Type your message..."
                    autocomplete="off"
                    maxlength="500"
                >
                <button id="chat-send" class="send-button" disabled>
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Chat Interface Design System */
:root {
    /* Color Palette - Neutrals */
    --background-primary: #FFFFFF;
    --background-secondary: #F8F9FA;
    --background-tertiary: #E5E5E7;
    --surface-elevated: #FFFFFF;
    --border-light: #E5E5E7;
    --border-medium: #D1D1D6;
    --shadow-light: rgba(0, 0, 0, 0.08);
    --shadow-medium: rgba(0, 0, 0, 0.12);
    
    /* Color Palette - Text */
    --text-primary: #1D1D1F;
    --text-secondary: #86868B;
    --text-tertiary: #AEAEB2;
    --text-placeholder: #8E8E93;
    
    /* Color Palette - Interactive */
    --icon-primary: #1D1D1F;
    --icon-secondary: #86868B;
    --icon-accent: #007AFF;
    --button-primary: #007AFF;
    --button-secondary: #F2F2F7;
    
    /* Typography */
    --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Floating Widget Container */
.modern-chat-widget {
    position: fixed;
    bottom: 16px;
    right: 16px;
    z-index: 9999;
    font-family: var(--font-family);
}

/* Toggle Button */
.chat-toggle-btn {
    width: 60px;
    height: 60px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 1px 3px var(--shadow-light);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    border: none;
    color: var(--background-primary);
}

.chat-toggle-btn:hover {
    background: #f4f4fcff;
    transform: scale(1.02);
    box-shadow: 0 2px 8px var(--shadow-medium);
}

.chat-toggle-btn:active {
    background: #eeeef5ff;
    transform: scale(0.98);
}

.notification-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: var(--button-primary);
    color: var(--background-primary);
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 11px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--background-primary);
}

/* Chat Container */
.chat-container {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 550px;
    height: 550px;
    background: var(--background-primary);
    border-radius: 16px;
    box-shadow: 0 8px 24px var(--shadow-medium);
    display: none;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--border-light);
}

.chat-container.show {
    display: flex;
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 768px) {
    .modern-chat-widget {
        bottom: 12px;
        right: 12px;
    }
    
    .chat-container {
        width: 480px;
        height: 480px;
        bottom: 70px;
    }
    
    .chat-toggle-btn {
        width: 52px;
        height: 52px;
    }
}

@media (max-width: 480px) {
    .chat-container {
        position: fixed;
        bottom: 0;
        right: 0;
        left: 0;
        top: 0;
        width: 100%;
        height: 100vh;
        border-radius: 0;
        z-index: 10000;
    }
}

/* Header */
.chat-header {
    background: var(--background-primary);
    border-bottom: 1px solid var(--border-light);
    padding: 16px;
    position: relative;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-right: 44px;
}

.assistant-avatar {
    background: transparent;
    color: var(--background-primary);
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.header-text {
    flex: 1;
}

.header-title {
    color: var(--text-primary);
    font-size: 16px;
    font-weight: 600;
    line-height: 1.3;
    margin-bottom: 2px;
}

.header-subtitle {
    color: var(--text-secondary);
    font-size: 13px;
    font-weight: 400;
    line-height: 1.3;
}

.header-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: transparent;
    color: var(--icon-secondary);
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease-out;
}

.header-close:hover {
    color: var(--icon-primary);
    background: var(--background-secondary);
}

/* Chat Messages Area */
.chat-messages-area {
    flex: 1;
    background: var(--background-primary);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.messages-container {
    flex: 1;
    padding: 0;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Scrollbar Styling */
.messages-container::-webkit-scrollbar {
    width: 3px;
}

.messages-container::-webkit-scrollbar-track {
    background: transparent;
}

.messages-container::-webkit-scrollbar-thumb {
    background: var(--text-tertiary);
    border-radius: 2px;
}

.messages-container::-webkit-scrollbar-thumb:hover {
    background: var(--text-secondary);
}

/* Message Layout */
.message-group {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin: 8px 16px;
}

/* Assistant Messages */
.assistant-message {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    align-self: flex-start;
    max-width: 100%;
}

.assistant-avatar {

 
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}

.assistant-avatar img {
    width: 30px;
    height: 30px;
    object-fit: contain;
    border-radius: 50%;
}

.message-bubble.assistant {
    background: var(--surface-elevated);
    color: var(--text-primary);
    border: 1px solid var(--border-light);
    border-radius: 4px 20px 20px 20px;
    padding: 16px 20px;
    box-shadow: 0 1px 3px var(--shadow-light);
    transition: box-shadow 0.2s ease-out;
    text-align: left;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.message-bubble.assistant:hover {
    box-shadow: 0 2px 8px var(--shadow-medium);
}

/* User Messages */
.user-message {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    align-self: flex-end;
    max-width: 85%;
    flex-direction: row-reverse;
}

.user-avatar {
    background: var(--icon-primary);
    color: var(--background-primary);
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    flex-shrink: 0;
}

.message-bubble.user {
    background: var(--background-secondary);
    color: var(--text-primary);
    border: none;
    border-radius: 20px 20px 4px 20px;
    padding: 16px 20px;
    text-align: left;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.message-text {
    font-size: 15px;
    line-height: 1.4;
    font-weight: 400;
    color: inherit;
    font-family: var(--font-family);
    white-space: pre-wrap;
    margin-bottom: 4px;
    text-align: left !important;
    width: 100%;
    display: block;
}

.message-timestamp {
    color: var(--text-secondary);
    font-size: 13px;
    line-height: 1.3;
    font-weight: 400;
    text-align: left !important;
    width: 100%;
    display: block;
    margin-top: 4px;
}

@keyframes messageAppear {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}


/* Typing Indicator */
.typing-indicator {
    margin: 8px 16px;
    max-width: 85%;
    align-self: flex-start;
}

.typing-message {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    align-self: flex-start;
}

.typing-bubble {
    background: var(--surface-elevated);
    border: 1px solid var(--border-light);
    border-radius: 4px 20px 20px 20px;
    padding: 16px 20px;
    box-shadow: 0 1px 3px var(--shadow-light);
}

.typing-dots {
    display: flex;
    gap: 4px;
    align-items: center;
    justify-content: flex-start;
}

.typing-dots span {
    width: 6px;
    height: 6px;
    background: var(--text-secondary);
    border-radius: 50%;
    animation: typingAnimation 1.5s ease-in-out infinite;
}

.typing-dots span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-dots span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typingAnimation {
    0%, 60%, 100% {
        transform: scale(1);
        opacity: 0.5;
    }
    30% {
        transform: scale(1.2);
        opacity: 1;
    }
}

/* Input Area */
.input-area {
    background: var(--background-primary);
    border-top: 1px solid var(--border-light);
    padding: 16px;
    flex-shrink: 0;
}

.input-container {
    background: var(--background-primary);
    border: 1px solid var(--border-light);
    border-radius: 24px;
    padding: 12px 20px;
    box-shadow: 0 1px 3px var(--shadow-light);
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.2s ease-out;
}

.input-container:focus-within {
    border-color: var(--button-primary);
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}

.text-input {
    flex: 1;
    background: transparent;
    border: none;
    font-size: 15px;
    color: var(--text-primary);
    outline: none;
    font-family: var(--font-family);
    line-height: 1.4;
}

.text-input::placeholder {
    color: var(--text-placeholder);
}

.send-button {
    background: var(--icon-primary);
    color: var(--background-primary);
    border: none;
    border-radius: 20%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease-out;
    flex-shrink: 0;
}

.send-button:hover:not(:disabled) {
    background: #2D2D2F;
    transform: scale(1.02);
}

.send-button:active {
    background: #0D0D0F;
    transform: scale(0.98);
}

.send-button:disabled {
    background: var(--text-tertiary);
    cursor: not-allowed;
    transform: none;
}


/* Animation Utilities */
@media (prefers-reduced-motion: reduce) {
    .chat-container,
    .chat-toggle-btn,
    .send-button,
    .message-card {
        animation: none !important;
        transition: none !important;
    }
}
</style>

<script>
class ModernChatWidget {
    constructor() {
        this.isOpen = false;
        this.conversationHistory = [];
        this.isTyping = false;
        
        this.initializeElements();
        this.bindEvents();
        this.loadConversationHistory();
    }
    
    initializeElements() {
        this.chatToggle = document.getElementById('chat-toggle');
        this.chatWidget = document.getElementById('chat-widget');
        this.chatInput = document.getElementById('chat-input');
        this.chatSend = document.getElementById('chat-send');
        this.chatMessages = document.getElementById('chat-messages');
        this.chatTyping = document.getElementById('chat-typing');
        this.chatClose = document.getElementById('chat-close');
        this.chatBadge = document.getElementById('chat-badge');
        this.welcomeAvatar = document.getElementById('welcome-avatar');
        this.typingAvatar = document.getElementById('typing-avatar');
        
        // Initialize avatar states
        this.initializeAvatars();
    }
    
    initializeAvatars() {
        this.avatarStates = {
            greeting: '<?= base_url('assets/icons/chat.png') ?>',
            typing: '<?= base_url('assets/icons/Typing.gif') ?>'
        };
        this.hasUserInteracted = false;
    }
    
    loadConversationHistory() {
        try {
            const saved = localStorage.getItem('modern-chat-history');
            if (saved) {
                this.conversationHistory = JSON.parse(saved);
                this.restoreMessages();
            }
        } catch (error) {
            console.warn('Failed to load chat history:', error);
            this.conversationHistory = [];
        }
    }
    
    saveConversationHistory() {
        try {
            // Keep only last 50 messages to prevent localStorage bloat
            const recentHistory = this.conversationHistory.slice(-50);
            localStorage.setItem('modern-chat-history', JSON.stringify(recentHistory));
        } catch (error) {
            console.warn('Failed to save chat history:', error);
        }
    }
    
    restoreMessages() {
        if (this.conversationHistory.length === 0) return;
        
        // Clear welcome message
        this.chatMessages.innerHTML = '';
        
        // Restore conversation messages
        this.conversationHistory.forEach(msg => {
            this.addMessageToDOM(msg.text, msg.sender, msg.timestamp, false);
        });
    }
    
    bindEvents() {
        // Toggle chat
        this.chatToggle.addEventListener('click', () => this.toggleChat());
        
        // Close chat
        this.chatClose.addEventListener('click', () => this.closeChat());
        
        // Send message
        this.chatSend.addEventListener('click', () => this.sendMessage());
        
        // Input events
        this.chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        this.chatInput.addEventListener('input', () => {
            this.chatSend.disabled = this.chatInput.value.trim().length === 0;
        });
    }
    
    toggleChat() {
        if (this.isOpen) {
            this.closeChat();
        } else {
            this.openChat();
        }
    }
    
    openChat() {
        this.chatWidget.classList.add('show');
        this.isOpen = true;
        this.isMinimized = false;
        this.chatBadge.style.display = 'none';
        this.chatInput.focus();
    }
    
    closeChat() {
        this.chatWidget.classList.remove('show');
        this.isOpen = false;
        this.isMinimized = false;
    }
    
    minimizeChat() {
        this.closeChat();
        this.isMinimized = true;
    }
    
    async sendMessage() {
        const message = this.chatInput.value.trim();
        if (!message || this.isTyping) return;
        
        // Add user message
        this.addMessage(message, 'user');
        this.chatInput.value = '';
        this.chatSend.disabled = true;
        
        // Show typing indicator
        this.showTyping();
        
        try {
            console.log('Calling API...');
            const response = await this.callChatbotAPI(message);
            console.log('API call completed');
            this.hideTyping();
            
            if (response && response.success) {
                // Structure and format the API response
                console.log('API Response:', response);
                try {
                    const formattedResponse = this.formatAPIResponse(response);
                    console.log('Formatted Response:', formattedResponse);
                    this.addMessage(formattedResponse, 'assistant');
                } catch (formatError) {
                    console.error('Error in formatAPIResponse:', formatError);
                    this.addMessage('Error formatting response: ' + formatError.message, 'assistant');
                }
            } else {
                console.log('API Error Response:', response);
                const errorMsg = this.formatErrorResponse(response);
                this.addMessage(errorMsg, 'assistant');
            }
        } catch (error) {
            console.error('Caught error in sendMessage:', error);
            this.hideTyping();
            const networkError = this.formatNetworkError(error);
            this.addMessage(networkError, 'assistant');
            console.error('Chat API Error:', error);
        }
        
        this.chatInput.focus();
    }
    
    async callChatbotAPI(message) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout
        
        try {
            const formData = new FormData();
            formData.append('query', message);
            
            const response = await fetch('<?= base_url('ai/chatbot/query') ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                throw new Error(`API Error: ${response.status} ${response.statusText}`);
            }
            
            const data = await response.json();
            return data;
            
        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                throw new Error('Request timeout - please try again');
            }
            throw error;
        }
    }
    
    addMessage(text, sender, customTimestamp = null) {
        console.log('Adding message:', {text, sender});
        const timestamp = customTimestamp || new Date().toISOString();
        
        // Add to DOM
        this.addMessageToDOM(text, sender, timestamp, true);
        
        // Store in history
        this.conversationHistory.push({ text, sender, timestamp });
        this.saveConversationHistory();
    }
    
    addMessageToDOM(text, sender, timestamp, shouldAnimate = true) {
        // Remove welcome message if exists
        const welcomeMsg = this.chatMessages.querySelector('.message-group');
        if (welcomeMsg && welcomeMsg.querySelector('.message-text')?.textContent?.includes('Hi! I\'m your Help Assistant')) {
            welcomeMsg.remove();
        }
        
        const messageGroup = document.createElement('div');
        messageGroup.className = 'message-group';
        
        const messageContainer = document.createElement('div');
        const timeStr = this.formatTimestamp(timestamp);
        const formattedText = sender === 'assistant' ? this.formatAssistantMessage(text) : this.escapeHtml(text);
        
        if (sender === 'assistant') {
            messageContainer.className = 'assistant-message';
            messageContainer.innerHTML = `
                <div class="assistant-avatar">
                    <img class="assistant-avatar-img" src="${this.avatarStates?.greeting || '<?= base_url('assets/icons/chat.png') ?>'}" alt="Assistant Avatar" width="30" height="30">
                </div>
                <div class="message-bubble assistant">
                    <div class="message-text">${formattedText}</div>
                    <div class="message-timestamp">${timeStr}</div>
                </div>
            `;
        } else {
            messageContainer.className = 'user-message';
            const userInitial = '<?= substr(session()->get('Full_Name') ?? 'U', 0, 1) ?>';
            messageContainer.innerHTML = `
               <img src="<?= base_url('assets/icons/user.png') ?>" alt="User Avatar" class="user-avatar" width="30" height="30">
                <div class="message-bubble user">
                    <div class="message-text">${formattedText}</div>
                    <div class="message-timestamp">${timeStr}</div>
                </div>
            `;
        }
        
        if (!shouldAnimate) {
            messageContainer.style.animation = 'none';
        }
        
        messageGroup.appendChild(messageContainer);
        this.chatMessages.appendChild(messageGroup);
        
        // Setup avatar control for new assistant messages
        if (sender === 'assistant') {
            const newAvatar = messageContainer.querySelector('.assistant-avatar-img');
            if (newAvatar && !this.hasUserInteracted) {
                // Avatar is already using the greeting GIF from the template
                // No additional setup needed
            }
        }
        
        if (shouldAnimate) {
            this.scrollToBottom();
        }
    }
    
    formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: false 
        });
    }
    
    formatAPIResponse(response) {
        console.log('formatAPIResponse called with:', response);
        if (!response || !response.response) {
            console.log('No response or response.response found');
            return 'I apologize, but I received an incomplete response. Please try asking your question again.';
        }
        
        const data = response.response;
        console.log('Data extracted:', data);
        
        // Handle different response formats
        if (typeof data === 'string') {
            console.log('Data is string, returning:', data);
            return data;
        }
        
        if (data.text) {
            console.log('Found data.text, returning:', data.text);
            return data.text;
        }
        
        if (data.message) {
            return data.message;
        }
        
        if (data.answer) {
            return data.answer;
        }
        
        // Handle structured responses
        if (data.sections && Array.isArray(data.sections)) {
            return this.formatStructuredResponse(data);
        }
        
        // Fallback for unknown formats
        return JSON.stringify(data, null, 2);
    }
    
    formatStructuredResponse(data) {
        let formatted = '';
        
        if (data.title) {
            formatted += `**${data.title}**\n\n`;
        }
        
        if (data.summary) {
            formatted += `${data.summary}\n\n`;
        }
        
        if (data.sections) {
            data.sections.forEach((section, index) => {
                if (section.heading) {
                    formatted += `**${index + 1}. ${section.heading}**\n`;
                }
                if (section.content) {
                    formatted += `${section.content}\n\n`;
                }
                if (section.steps && Array.isArray(section.steps)) {
                    section.steps.forEach((step, stepIndex) => {
                        formatted += `   ${stepIndex + 1}. ${step}\n`;
                    });
                    formatted += '\n';
                }
            });
        }
        
        if (data.tips && Array.isArray(data.tips)) {
            formatted += '**Tips:**\n';
            data.tips.forEach(tip => {
                formatted += `• ${tip}\n`;
            });
        }
        
        return formatted.trim();
    }
    
    formatErrorResponse(response) {
        if (!response) {
            return 'I\'m experiencing technical difficulties. Please try again in a moment.';
        }
        
        if (response.error) {
            return `I apologize, but I encountered an error: ${response.error}`;
        }
        
        if (response.message) {
            return response.message;
        }
        
        return 'Something went wrong while processing your request. Please try again.';
    }
    
    formatNetworkError(error) {
        if (error.message?.includes('timeout')) {
            return 'The request is taking longer than expected. Please try again with a shorter question.';
        }
        
        if (error.message?.includes('fetch')) {
            return 'Unable to connect to the help service. Please check your internet connection and try again.';
        }
        
        return 'I\'m currently unavailable. Please try again in a few minutes.';
    }
    
    formatAssistantMessage(text) {
        // Escape HTML first
        text = this.escapeHtml(text);
        
        // Convert line breaks
        text = text.replace(/\n/g, '<br>');
        
        // Format bold text (markdown-style)
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // Format numbered lists with spacing
        text = text.replace(/^(\d+)\. (.+)$/gm, '<div style="margin-bottom: 8px;"><strong>$1.</strong> $2</div>');
        
        // Format bullet points
        text = text.replace(/^• (.+)$/gm, '• $1');
        
        // Format navigation paths
        text = text.replace(/Go to ([^→]+)→([^<\n]+)/g, 'Go to <strong>$1 → $2</strong>');
        
        return text;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    showTyping() {
        this.isTyping = true;
        this.chatTyping.style.display = 'block';
        this.scrollToBottom();
    }
    
    hideTyping() {
        this.isTyping = false;
        this.chatTyping.style.display = 'none';
    }
    
    scrollToBottom() {
        setTimeout(() => {
            this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
        }, 100);
    }
    
    
    trackQuickAction(url) {
        // Track quick action usage for analytics
        console.log('Quick action used:', url);
    }
    
    showNotification() {
        if (!this.isOpen) {
            this.chatBadge.style.display = 'flex';
        }
    }
}

// Initialize Modern Chat widget when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.modernChatWidget === 'undefined') {
        window.modernChatWidget = new ModernChatWidget();
    }
});
</script>