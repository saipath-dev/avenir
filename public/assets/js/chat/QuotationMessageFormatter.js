/**
 * Quotation Message Formatter - Extracted from agent_chat.php
 * Handles the formatting of quotation messages in the chat interface
 */

class QuotationMessageFormatter {
    constructor() {
        this.init();
    }

    init() {
        // Apply formatting to existing messages
        this.formatExistingMessages();

        // Set up observer for dynamically added messages
        this.setupMessageObserver();
    }

    formatExistingMessages() {
        const messageElements = document.querySelectorAll('#conversation_ul .chat-message, #conversation_ul p, #conversation_ul div');
        messageElements.forEach(element => {
            this.formatMessageElement(element);
        });
    }

    setupMessageObserver() {
        const conversationContainer = document.getElementById('conversation_ul');
        if (!conversationContainer) return;

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        // Check if this node or its children contain quotation format
                        this.formatMessageElement(node);
                        node.querySelectorAll('*').forEach(child => {
                            this.formatMessageElement(child);
                        });
                    }
                });
            });
        });

        observer.observe(conversationContainer, {
            childList: true,
            subtree: true
        });
    }

    formatMessageElement(element) {
        if (!element || !element.textContent) return;

        const text = element.textContent.trim();

        // Check if this is a quotation format message
        if (this.isQuotationFormat(text)) {
            const formattedHTML = this.formatQuotationText(text);

            // Replace the element's content with formatted HTML
            if (element.innerHTML !== formattedHTML) {
                element.innerHTML = formattedHTML;
                element.classList.add('quotation-message');
            }
        }
    }

    isQuotationFormat(text) {
        // Check if text contains the universal quotation format markers
        return text.includes('ගමන් මූලික විස්තර:') ||
               text.includes('ගුවන් ගමන්:') ||
               text.includes('හෝටල් අවශ්‍යතා:') ||
               text.includes('සම්බන්ධිකරණ:');
    }

    formatQuotationText(text) {
        // Split the text into sections and format each one
        let formattedHTML = text;

        // Define section patterns and their formatting
        const sections = [
            {
                pattern: /(ගමන් මූලික විස්තර:)/g,
                replacement: '<div class="quotation-section"><span class="section-title">$1</span><div class="section-content">'
            },
            {
                pattern: /(ගුවන් ගමන්:)/g,
                replacement: '</div></div><div class="quotation-section"><span class="section-title">$1</span><div class="section-content">'
            },
            {
                pattern: /(හෝටල් අවශ්‍යතා:)/g,
                replacement: '</div></div><div class="quotation-section"><span class="section-title">$1</span><div class="section-content">'
            },
            {
                pattern: /(ආකර්ෂණ\/ක්‍රියාකාරකම්:)/g,
                replacement: '</div></div><div class="quotation-section"><span class="section-title">$1</span><div class="section-content">'
            },
            {
                pattern: /(සම්බන්ධිකරණ:)/g,
                replacement: '</div></div><div class="quotation-section contact-info"><span class="section-title">$1</span><div class="section-content">'
            },
            {
                pattern: /(අමතර සටහන්:)/g,
                replacement: '</div></div><div class="quotation-section additional-notes"><span class="section-title">$1</span><div class="section-content">'
            }
        ];

        // Apply section formatting
        sections.forEach(section => {
            formattedHTML = formattedHTML.replace(section.pattern, section.replacement);
        });

        // Format list items with proper structure
        formattedHTML = formattedHTML.replace(/- (පිරිස \(Pax\): .+?)(?=\s-|\sගුවන්|$)/g, '<div class="pax-highlight">$1</div><br>');
        formattedHTML = formattedHTML.replace(/- (.+?)(?=\s-|\sගුවන්|$)/g, '• $1<br>');

        // Format nested content with better structure
        formattedHTML = formattedHTML.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        formattedHTML = formattedHTML.replace(/\* (.+?)(?=\s\*|\sගුවන්|\sහෝටල්|\sආකර්ෂණ|\sසම්බන්ධිකරණ|\sඅමතර|$)/g, '• $1<br>');

        // Clean up extra line breaks and ensure proper closing
        formattedHTML = formattedHTML.replace(/\n+/g, '<br>');
        formattedHTML = formattedHTML.replace(/<br>\s*<br>/g, '<br>');

        // Ensure all sections are properly closed
        if (!formattedHTML.includes('</div></div>')) {
            formattedHTML += '</div></div>';
        }

        // Handle phone numbers and email highlighting
        formattedHTML = formattedHTML.replace(/(විද්‍යුත් තැපෑල: )([^\s<]+@[^\s<]+)/g, '$1<strong>$2</strong>');
        formattedHTML = formattedHTML.replace(/(ජංගම: )(\d{8,15})/g, '$1<strong>$2</strong>');
        formattedHTML = formattedHTML.replace(/(නම: )([^<\n]+?)(<br>|$)/g, '$1<strong>$2</strong>$3');

        return formattedHTML;
    }
}

// Initialize the formatter when the page loads
document.addEventListener('DOMContentLoaded', function() {
    new QuotationMessageFormatter();
});

// Also initialize when messages are loaded dynamically
$(document).ready(function() {
    // Re-run formatter when conversations are loaded
    $(document).on('conversationLoaded', function() {
        setTimeout(() => {
            new QuotationMessageFormatter();
        }, 500);
    });
});

// Export for global use
window.QuotationMessageFormatter = QuotationMessageFormatter;