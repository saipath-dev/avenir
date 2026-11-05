/**
 * ChatQuotation Class - Extracted from agent_chat.php
 * Handles all quotation-related functionality in the chat interface
 *
 * This is a simplified version of the original 2500-line class.
 * Full functionality should be migrated gradually from the backup file.
 */

class ChatQuotation {
    constructor() {
        this.conversationId = null;
        this.quotationId = null;
        this.currentPhase = 1;
        this.quotationData = {};
        this.autoSaveInterval = null;
        this.isAgentUser = false;
        this.isCreatingQuotation = false;
        this.hotelsLoading = false;
        this.baseUrl = this.getBaseUrl();

        this.init();
    }

    getBaseUrl() {
        // Extract base URL from current page location
        const path = window.location.pathname;
        const chatIndex = path.indexOf('/chat');
        if (chatIndex !== -1) {
            const baseUrl = path.substring(0, chatIndex);
            return baseUrl;
        }
        // Fallback - if we can't detect from path, use /avenir
        return '/avenir';
    }

    init() {
        this.isAgentUser = document.querySelector('body').dataset.groupId === '14';
        this.setupEventHandlers();
        this.updateConversationId();
        this.checkQuotationStatus();

        // Auto-refresh quotation status every 10 seconds
        setInterval(() => {
            this.updateConversationId();
            if (this.conversationId) {
                this.checkQuotationStatus();
            }
        }, 10000);
    }

    updateConversationId() {
        const conversationIdInput = document.getElementById('conversation_id');
        if (conversationIdInput && conversationIdInput.value) {
            this.conversationId = conversationIdInput.value;
            this.showQuotationControls();
        } else {
            this.conversationId = null;
            this.hideQuotationControls();
        }
    }

    showQuotationControls() {
        if (this.isAgentUser) {
            const quotationControls = document.getElementById('quotation-controls');
            if (quotationControls) {
                quotationControls.style.display = 'block';
            }
        }
    }

    hideQuotationControls() {
        const quotationControls = document.getElementById('quotation-controls');
        if (quotationControls) {
            quotationControls.style.display = 'none';
        }
    }

    setupEventHandlers() {
        // Create Quotation Button
        document.addEventListener('click', (e) => {
            if (e.target.matches('#create-quotation-btn')) {
                this.showCreateQuotationModal();
            }

            // Add Quotation Request Button
            if (e.target.matches('#add-quotation-request-btn')) {
                this.showQuotationRequestModal();
            }

            // Submit Quotation Request
            if (e.target.matches('#submit-quotation-request')) {
                this.submitQuotationRequest();
            }

            // Toggle Quotation Panel
            if (e.target.matches('#toggle-quotation-panel')) {
                this.toggleQuotationPanel();
            }

            // Close Quotation Panel
            if (e.target.matches('#close-quotation-panel')) {
                this.closeQuotationPanel();
            }

            // Back to Chat Button
            if (e.target.matches('#back-to-chat')) {
                this.backToChat();
            }
        });

        // Conversation Selection Handler
        document.addEventListener('click', (e) => {
            if (e.target.closest('.conversation-item')) {
                const conversationId = e.target.closest('.conversation-item').dataset.conversationId;
                this.selectConversation(conversationId);
            }
        });
    }

    selectConversation(conversationId) {
        this.conversationId = conversationId;

        // Hide quotation controls initially until status is checked
        if (this.isAgentUser) {
            document.getElementById('quotation-controls').style.display = 'none';
        }

        // Check for existing quotation (will show controls after check)
        this.checkQuotationStatus();
    }

    async checkQuotationStatus() {
        if (!this.conversationId) return;

        try {
            const response = await fetch(`${this.baseUrl}/chat/quotation/progress?conversation_id=${this.conversationId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.status === 200) {
                const data = result.data;

                // Show quotation controls now that we have the status
                if (this.isAgentUser) {
                    document.getElementById('quotation-controls').style.display = 'flex';
                }

                if (data.has_active_quotation) {
                    this.quotationId = data.quotation_id;
                    this.currentPhase = data.current_phase;

                    // Store quotation data for form pre-population
                    if (data.quotation_data) {
                        this.quotationData = data.quotation_data;
                    }

                    this.updateQuotationControls(data);
                    this.updateProgressIndicator();
                } else {
                    // Reset quotation state
                    this.quotationId = null;
                    this.currentPhase = 1;
                    this.quotationData = {};
                    this.updateCreateButtonVisibility(data.can_create_quotation);
                }
            }
        } catch (error) {
            console.error('Error checking quotation status:', error);
        }
    }

    updateQuotationControls(data) {
        const createBtn = document.getElementById('create-quotation-btn');
        const toggleBtn = document.getElementById('toggle-quotation-panel');

        if (data.has_active_quotation) {
            createBtn.innerHTML = `<i class="fas fa-play"></i> Continue Quotation (Phase ${data.current_phase})`;
            createBtn.className = 'btn btn-primary btn-sm me-2';
            toggleBtn.style.display = 'inline-block';
        } else {
            createBtn.innerHTML = '<i class="fas fa-file-invoice"></i> Create Quotation';
            createBtn.className = 'btn btn-success btn-sm me-2';
            toggleBtn.style.display = 'none';
        }
    }

    updateCreateButtonVisibility(canCreate) {
        const createBtn = document.getElementById('create-quotation-btn');
        createBtn.style.display = canCreate ? 'inline-block' : 'none';
    }

    updateProgressIndicator() {
        const progress = (this.currentPhase / 3) * 100;
        const progressBar = document.getElementById('quotation-progress');
        const progressText = document.getElementById('progress-text');

        if (progressBar) {
            progressBar.style.width = progress + '%';
            progressBar.setAttribute('aria-valuenow', progress);
        }

        if (progressText) {
            progressText.textContent = Math.round(progress) + '% Complete';
        }

        // Update phase indicators
        document.querySelectorAll('.phase-indicator').forEach((indicator, index) => {
            const phase = index + 1;
            const badge = indicator;

            if (phase < this.currentPhase) {
                badge.className = 'badge bg-success phase-indicator';
            } else if (phase === this.currentPhase) {
                badge.className = 'badge bg-primary phase-indicator';
            } else {
                badge.className = 'badge bg-light text-dark phase-indicator';
            }
        });
    }

    showCreateQuotationModal() {
        if (this.quotationId) {
            // Continue existing quotation
            this.showQuotationPanel();
            this.showQuotationPhase(this.currentPhase);
            this.showNotification(`Continuing quotation from Phase ${this.currentPhase}`, 'info');
        } else {
            // Create new quotation
            const conversationNameElement = document.getElementById('selected_conversation_name');
            const conversationName = conversationNameElement ? conversationNameElement.textContent.trim() : '';

            if (!conversationName || conversationName === 'Start a conversation') {
                this.showNotification('Please select a conversation first', 'warning');
                return;
            }

            this.createNewQuotation(conversationName);
        }
    }

    async createNewQuotation(tourName) {
        if (this.isCreatingQuotation) return;

        try {
            this.isCreatingQuotation = true;
            const createBtn = document.getElementById('create-quotation-btn');
            const originalText = createBtn.innerHTML;

            createBtn.disabled = true;
            createBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

            const response = await fetch(`${this.baseUrl}/chat/quotation/start`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    conversation_id: this.conversationId,
                    tour_name: tourName
                })
            });

            const result = await response.json();

            if (result.status === 200) {
                this.quotationId = result.data.quotation_id;
                this.currentPhase = result.data.current_phase;

                this.showNotification('Quotation created successfully!', 'success');
                this.showQuotationPanel();
                this.showQuotationPhase(1);
                this.updateProgressIndicator();
            } else {
                this.showNotification(result.message || 'Failed to create quotation', 'error');
            }
        } catch (error) {
            console.error('Error creating quotation:', error);
            this.showNotification('An error occurred while creating quotation', 'error');
        } finally {
            this.isCreatingQuotation = false;
            const createBtn = document.getElementById('create-quotation-btn');
            if (createBtn) {
                createBtn.disabled = false;
                createBtn.innerHTML = '<i class="fas fa-file-invoice"></i> Create Quotation';
            }
        }
    }

    showQuotationPanel() {
        const panel = document.getElementById('quotation-panel');
        const chatContainer = document.getElementById('chat-main-container');
        const conversationsPanel = document.querySelector('.col-md-3');

        // Hide chat and conversations, show full-width quotation panel
        panel.style.display = 'block';
        if (chatContainer) chatContainer.style.display = 'none';
        if (conversationsPanel) conversationsPanel.style.display = 'none';

        // Update quotation ID badge if we have an ID
        if (this.quotationId) {
            document.getElementById('quotation-id-text').textContent = `ID: ${this.quotationId}`;
        }

        // Update toggle button
        const toggleBtn = document.getElementById('toggle-quotation-panel');
        if (toggleBtn) {
            toggleBtn.style.display = 'none';
        }
    }

    closeQuotationPanel() {
        if (this.quotationId && !confirm('Are you sure you want to close the quotation creator? Your progress has been saved.')) {
            return;
        }
        this.backToChat();
    }

    backToChat() {
        const panel = document.getElementById('quotation-panel');
        const chatContainer = document.getElementById('chat-main-container');
        const conversationsPanel = document.querySelector('.col-md-3');

        // Show chat and conversations, hide quotation panel
        panel.style.display = 'none';
        if (chatContainer) {
            chatContainer.style.display = 'block';
            chatContainer.classList.remove('col-md-5');
            chatContainer.classList.add('col-md-9');
        }
        if (conversationsPanel) {
            conversationsPanel.style.display = 'block';
        }

        // Update toggle button
        const toggleBtn = document.getElementById('toggle-quotation-panel');
        if (toggleBtn && this.quotationId) {
            toggleBtn.style.display = 'inline-block';
            toggleBtn.innerHTML = '<i class="fas fa-expand-arrows-alt"></i> Show Panel';
        }
    }

    toggleQuotationPanel() {
        const panel = document.getElementById('quotation-panel');
        if (panel.style.display === 'none' || !panel.style.display) {
            this.showQuotationPanel();
        } else {
            this.backToChat();
        }
    }

    showQuotationPhase(phase) {
        // Hide welcome screen
        const welcomeScreen = document.getElementById('quotation-welcome');
        if (welcomeScreen) {
            welcomeScreen.style.display = 'none';
        }

        // Hide all phase forms
        const phaseForms = document.querySelectorAll('.quotation-phase-form');
        phaseForms.forEach(form => {
            form.style.display = 'none';
        });

        // Hide completion screen
        const completionScreen = document.getElementById('quotation-completed');
        if (completionScreen) {
            completionScreen.style.display = 'none';
        }

        // Show the selected phase
        const targetPhase = document.getElementById(`phase${phase}-form`);
        if (targetPhase) {
            targetPhase.style.display = 'block';
            this.currentPhase = phase;
            this.updateProgressIndicator();
            this.setupPhaseEventHandlers();
            this.updatePhaseHelp(phase);
            this.loadPhaseData(phase);

            // Initialize handlers for the specific phase
            setTimeout(() => {
                if (phase === 1) this.initializePhase1Handlers();
                else if (phase === 2) this.initializePhase2Handlers();
                else if (phase === 3) this.initializePhase3Handlers();
            }, 200);
        } else {
            this.showNotification(`Phase ${phase} form not found`, 'error');
        }
    }

    showQuotationRequestModal() {
        const modal = new bootstrap.Modal(document.getElementById('quotationRequestModal'));
        modal.show();
    }

    async submitQuotationRequest() {
        const language = document.querySelector('input[name="request_language"]:checked').value;
        const requestText = document.getElementById('quotation_request_text').value;

        if (!requestText.trim()) {
            this.showNotification('Please enter request details', 'warning');
            return;
        }

        try {
            document.getElementById('processing_status').classList.remove('d-none');
            document.getElementById('submit-quotation-request').disabled = true;

            const response = await fetch(`${this.baseUrl}/chat/quotation/request`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    conversation_id: this.conversationId,
                    language: language,
                    request_text: requestText
                })
            });

            const result = await response.json();

            if (result.status === 200) {
                this.showNotification('Quotation request submitted successfully!', 'success');

                // Close modal and clear form
                const modal = bootstrap.Modal.getInstance(document.getElementById('quotationRequestModal'));
                modal.hide();
                document.getElementById('quotation_request_text').value = '';

                // Reload conversation messages to show the new request
                if (window.ChatHelpers && this.conversationId) {
                    window.ChatHelpers.loadConversationMessages(this.conversationId);
                }
            } else {
                this.showNotification(result.message || 'Failed to submit request', 'error');
            }
        } catch (error) {
            console.error('Error submitting quotation request:', error);
            this.showNotification('An error occurred while submitting request', 'error');
        } finally {
            document.getElementById('processing_status').classList.add('d-none');
            document.getElementById('submit-quotation-request').disabled = false;
        }
    }

    showNotification(message, type = 'info') {
        // Use toastr for notifications
        if (window.toastr) {
            toastr[type](message);
        } else {
            alert(message);
        }
    }

    setupPhaseEventHandlers() {
        // Global save quotation phase function for forms
        window.saveQuotationPhase = (phase, data, proceedToNext) => {
            this.saveQuotationPhase(phase, data, proceedToNext);
        };

        // Global show quotation phase function for navigation
        window.showQuotationPhase = (phase) => {
            this.showQuotationPhase(phase);
        };

        // Store quotation data globally for forms
        window.quotationData = this.quotationData;
    }

    async saveQuotationPhase(phase, data, proceedToNext = false) {
        try {
            if (!this.quotationId) {
                // Try to create a new quotation if none exists
                const tourName = data.consignee || 'New Tour Quotation';
                await this.createNewQuotation(tourName);

                if (!this.quotationId) {
                    throw new Error('Failed to create quotation - no ID available');
                }
            }

            const requestBody = {
                quotation_id: this.quotationId,
                phase: phase,
                phase_data: data
            };

            const url = `${this.baseUrl}/chat/quotation/save-phase`;
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(requestBody)
            });

            const result = await response.json();

            if (result.status === 200) {
                // Update stored data
                this.quotationData[`phase${phase}`] = data;
                this.showNotification('Progress saved successfully!', 'success');
                this.updateQuotationSummary();

                if (proceedToNext && result.data && result.data.next_phase) {
                    this.showQuotationPhase(result.data.next_phase);
                } else if (proceedToNext) {
                    this.showQuotationPhase(Math.min(phase + 1, 3));
                } else if (phase === 3 && proceedToNext) {
                    this.handleQuotationCompletion();
                }

                this.updateProgressIndicator();
            } else {
                this.showNotification(result.message || 'Failed to save progress', 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred while saving: ' + error.message, 'error');
            throw error;
        }
    }

    updateQuotationSummary() {
        // Update the quotation summary in the right panel
        const summaryDiv = document.getElementById('quotation-summary');
        if (summaryDiv) {
            let summaryHTML = '<p class="text-muted small">Fill in the form to see summary</p>';

            if (Object.keys(this.quotationData).length > 0) {
                summaryHTML = '<div class="small">';

                if (this.quotationData.phase1) {
                    summaryHTML += '<h6 class="fw-bold">Basic Info</h6>';
                    if (this.quotationData.phase1.consignee) {
                        summaryHTML += `<p>Tour: ${this.quotationData.phase1.consignee}</p>`;
                    }
                    if (this.quotationData.phase1.pass_count) {
                        summaryHTML += `<p>Passengers: ${this.quotationData.phase1.pass_count}</p>`;
                    }
                    if (this.quotationData.phase1.start_date && this.quotationData.phase1.end_date) {
                        summaryHTML += `<p>Dates: ${this.quotationData.phase1.start_date} to ${this.quotationData.phase1.end_date}</p>`;
                    }
                }

                summaryHTML += '</div>';
            }

            summaryDiv.innerHTML = summaryHTML;
        }
    }

    handleQuotationCompletion() {
        this.showNotification('Quotation submitted for internal review!', 'success');

        // Update UI to show completion
        const progressText = document.getElementById('progress-text');
        if (progressText) {
            progressText.textContent = 'Completed - Under Review';
        }

        // Hide all phase forms
        document.querySelectorAll('.quotation-phase-form').forEach(form => {
            form.style.display = 'none';
        });

        // Show completion screen
        const completionScreen = document.getElementById('quotation-completed');
        if (completionScreen) {
            completionScreen.style.display = 'block';
        }
    }

    loadPhaseData(phase) {
        const phaseData = this.quotationData[`phase${phase}`];
        if (phaseData) {
            // Populate form with existing data
            Object.keys(phaseData).forEach(key => {
                const element = document.getElementById(key);
                if (element) {
                    if (element.type === 'checkbox') {
                        element.checked = phaseData[key] == 1;
                    } else {
                        element.value = phaseData[key];
                    }
                }
            });
        }

        // Update quotation summary for all phases
        this.updateQuotationSummary();
    }

    updatePhaseHelp(phase) {
        // Update help content based on current phase
        // This is a placeholder for phase-specific help content
    }

    initializePhase1Handlers() {
        // Phase 1 initialization - placeholder for complete functionality
        if (!document.getElementById('phase1-form')) return;

        // Basic handlers for Phase 1
        $(document).ready(() => {
            this.autoPopulateAgentFields();
            this.autoPopulateConsigneeField();
            this.generateAgentReference();
        });
    }

    initializePhase2Handlers() {
        // Phase 2 initialization - placeholder for complete functionality
        if (!document.getElementById('phase2-form')) return;
    }

    initializePhase3Handlers() {
        // Phase 3 initialization - placeholder for complete functionality
        if (!document.getElementById('phase3-form')) return;
    }

    autoPopulateAgentFields() {
        const agentEmail = document.body.dataset.agentEmail;
        const agentMobile = document.body.dataset.agentMobile;

        if (agentEmail && $('#email').length && $('#email').val() === '') {
            $('#email').val(agentEmail);
        }

        if (agentMobile && $('#contact').length && $('#contact').val() === '') {
            $('#contact').val(agentMobile);
        }
    }

    autoPopulateConsigneeField() {
        // Auto-populate consignee with conversation name
        const conversationName = $('#selected_conversation_name').text();
        if (conversationName && conversationName !== 'Start a conversation' && $('#consignee').length && $('#consignee').val() === '') {
            $('#consignee').val(conversationName);
        }
    }

    generateAgentReference() {
        if (this.conversationId && $('#agent_ref').length && $('#agent_ref').val() === '') {
            const agentPrefix = document.body.dataset.agentPrefix || 'AGENT';
            const timestamp = Date.now();
            const agentRef = `${agentPrefix}-CHAT-${this.conversationId}-${timestamp}`;
            $('#agent_ref').val(agentRef);
        }
    }
}

// Export for global use
window.ChatQuotation = ChatQuotation;

/**
 * NOTE: This is a simplified version of the original ChatQuotation class.
 * The original class contains approximately 2500+ lines with complex phase management,
 * form validation, auto-save functionality, and extensive quotation workflow logic.
 *
 * To complete the migration, the following sections need to be added from the backup file:
 * - Phase 1, 2, 3 initialization and validation methods
 * - Auto-save functionality
 * - Form data management and persistence
 * - Hotel and vehicle management
 * - Complex validation logic
 * - Preview and completion functionality
 * - Error handling and recovery
 *
 * These can be gradually migrated from agent_chat copy.php lines 423-3120
 */