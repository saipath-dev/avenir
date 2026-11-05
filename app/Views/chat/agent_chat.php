<?= view('chat/layouts/header') ?>
<?= view('chat/layouts/navbar') ?>

<div class="containers mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="alert alert-light" style="height: calc(100vh - 120px);">
                <div class="d-flex flex-column">
                    <h5 class="fw-bold text-black mb-3">Conversations</h5>
                    <?php if ($group_id != 14) { ?>
                        <div class="card border mb-3">
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Select Agent</label>
                                    <select name="agent_select" id="agent_select" class="js-example-basic-single form-control" style="width: 100%;">
                                        <option value="0">All Agents</option>
                                        <?php
                                        foreach ($agents as $agentx) {
                                            echo '<option value="' . $agentx['Sup_Acc_No'] . '">' . $agentx['Name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <!-- Nav tabs -->
                    <ul class="nav nav-pills mb-3" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="new-tab" data-bs-toggle="tab" data-bs-target="#new" type="button" role="tab" aria-controls="new" aria-selected="true">New</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="false">Pending</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="closed-tab" data-bs-toggle="tab" data-bs-target="#closed" type="button" role="tab" aria-controls="closed" aria-selected="false">Closed</button>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content">
                        <div class="tab-pane active" id="new" role="tabpanel" aria-labelledby="new-tab" tabindex="0">
                            <?php //if ($group_id != 14) { 
                            ?>
                            <div class="w-100 text-muted mb-3">
                                Start a New Conversation
                            </div>
                            <div class="w-100 mb-3">
                                <button
                                    class="btn btn-secondary w-100 new_cnv_modal_btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#newChatModal">
                                    <i class="fas fa-plus me-2"></i> New Conversation
                                </button>
                            </div>
                            <?php //} 
                            ?>
                            <div class="w-100 text-muted mb-3">
                                Started Conversations
                            </div>
                            <div class="d-flex flex-column" id="agent_new_conversations">
                            </div>
                        </div>
                        <div class="tab-pane" id="pending" role="tabpanel" aria-labelledby="pending-tab" tabindex="0">
                            <div class="w-100 text-muted mb-3">
                                Pending Conversations
                            </div>
                            <div class="d-flex flex-column" id="agent_pending_conversations">
                            </div>
                        </div>
                        <div class="tab-pane" id="closed" role="tabpanel" aria-labelledby="closed-tab" tabindex="0">
                            <div class="w-100 text-muted mb-3">
                                Closed Conversations
                            </div>
                            <div class="d-flex flex-column" id="agent_closed_conversations">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-9" id="chat-main-container" data-quotation-active="false">
            <div class="alert alert-light" style="height: calc(100vh - 120px);">
                <div class="d-flex flex-column h-100">
                    <div class="d-flex flex-column">
                        <div class="d-flex">
                            <div class="d-flex flex-column w-25">
                                <span class="fw-bold text-black" id="selected_conversation_name">
                                    Start a conversation
                                </span>
                                <small class="text-muted" id="selected_conversation_agent_name"></small>
                            </div>
                            <div class="d-flex justify-content-end w-100">
                                <!-- Quotation Controls for External Agents -->
                                <?php if ($group_id == 14) { ?>
                                    <div class="d-flex align-items-center me-3" id="quotation-controls">
                                        <button type="button" class="btn btn-success btn-sm me-2" id="create-quotation-btn">
                                            <i class="fas fa-file-invoice"></i> Create Quotation
                                        </button>
                                        <button type="button" class="btn btn-info btn-sm me-2" id="add-quotation-request-btn">
                                            <i class="fas fa-file-plus"></i> Add Quotation Request
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-sm" id="toggle-quotation-panel">
                                            <i class="fas fa-expand-arrows-alt"></i> Show Panel
                                        </button>
                                    </div>
                                <?php } ?>

                                <div class="d-flex flex-column d-none" id="tag_options_div">
                                    <?php if (!empty($tours)) { ?>
                                        <div class="d-flex mb-1">
                                            <div class="me-1">
                                                <select name="tour_select" id="tour_select" class="js-example-basic-single form-control" style="width: 150px;">
                                                    <option value="">Select Tour</option>
                                                    <?php
                                                    foreach ($tours as $tr) {
                                                        echo '<option value="' . $tr['tour_id'] . '">' . $tr['consignee'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <button class="btn btn-sm btn-warning tag_tour_btn" style="height: fit-content;">
                                                <i class="fas fa-check me-1"></i> Tag Tour
                                            </button>
                                        </div>
                                    <?php } ?>
                                    <!-- <?php if (!empty($inquiries)) { ?>
                                        <div class="d-flex">
                                            <div class="me-1">
                                                <select name="inquiry_select" id="inquiry_select" class="js-example-basic-single form-control" style="width: 150px;">
                                                    <option value="">Select Inquiry</option>
                                                    <?php
                                                    foreach ($inquiries as $inquiry) {
                                                        $display_text = $inquiry['inquiry_code'] . ' - ' . $inquiry['name'] . ' (' . $inquiry['agent_name'] . ')';
                                                        echo '<option value="' . $inquiry['id'] . '">' . $display_text . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <button class="btn btn-sm btn-info tag_inquiry_btn" style="height: fit-content;">
                                                <i class="fas fa-check me-1"></i> Tag Inquiry
                                            </button>
                                        </div> -->
                                    <?php } ?>
                                </div>
                                <?php if ($group_id == 14) {
                                ?>
                                    <div id="close_chat_div" class="d-none">
                                        <button class="btn btn-sm btn-success close_chat_btn">
                                            <i class="fas fa-check me-1"></i> Close Chat
                                        </button>
                                    </div>
                                <?php }
                                ?>
                            </div>
                        </div>
                        <hr style="margin-top: 5px;">
                        <input type="hidden" id="conversation_id">
                        <input type="hidden" id="selected_agent">
                        <div class="d-flex flex-column p-3 ps-0" id="conversation_ul">
                            <div class="d-flex justify-content-center pt-5 w-100">
                                <span class="text-muted">
                                    No conversation selected.
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex mt-auto">
                        <div class="d-flex flex-column w-100">
                            <input type="file" id="attachment_input" class="d-none" multiple>
                            <div class="mb-3">
                                <textarea name="message" id="message" placeholder="Type your message here..."></textarea>
                            </div>
                            <div class="d-flex justify-content-start">
                                <button class="btn btn-primary px-4 me-3 send" id="send">
                                    <i class="fas fa-paper-plane me-2"></i> Send
                                </button>
                                <button class="btn btn-light me-3" id="attach">
                                    <i class="fas fa-paperclip me-2"></i> Attach Files
                                </button>
                                <button type="button" class="btn btn-light view_attached_btn" data-bs-toggle="modal" data-bs-target="#attachmentModal">
                                    <i class="fas fa-eye me-2"></i> View Attached Files
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quotation Panel (Hidden by default, Full-width when active) -->
        <div class="col-md-12" id="quotation-panel" style="display: none;">
            <div class="alert alert-light" style="height: calc(100vh - 120px); overflow-y: auto;">
                <div class="d-flex flex-column h-100">
                    <!-- Quotation Panel Header -->
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary me-3" id="back-to-chat">
                                <i class="fas fa-arrow-left"></i> Back to Chat
                            </button>
                            <h4 class="mb-0">
                                <i class="fas fa-file-invoice text-primary"></i> Quotation Creator
                            </h4>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-info me-3" id="quotation-id-badge">
                                <i class="fas fa-hashtag"></i> <span id="quotation-id-text">New Quotation</span>
                            </span>
                            <button type="button" class="btn btn-sm btn-danger" id="close-quotation-panel">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </div>

                    <!-- Main Content Area with Two Columns -->
                    <div class="row flex-grow-1">
                        <!-- Left Column - Form Area -->
                        <div class="col-md-8">
                            <!-- Progress Indicator -->
                            <div class="progress-container mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted fw-bold">Progress</small>
                                    <small class="text-muted fw-bold" id="progress-text">0% Complete</small>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                         role="progressbar" id="quotation-progress"
                                         style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-3">
                                    <span class="badge bg-secondary phase-indicator px-3 py-2" data-phase="1">
                                        <i class="fas fa-info-circle"></i> Basic Info
                                    </span>
                                    <span class="badge bg-light text-dark phase-indicator px-3 py-2" data-phase="2">
                                        <i class="fas fa-calendar-alt"></i> Timeline
                                    </span>
                                    <span class="badge bg-light text-dark phase-indicator px-3 py-2" data-phase="3">
                                        <i class="fas fa-check-circle"></i> Review
                                    </span>
                                </div>
                            </div>

                            <!-- Quotation Form Container -->
                            <div id="quotation-form-container" class="bg-white rounded p-4 shadow-sm">
                                <!-- Welcome Screen (shown initially) -->
                                <div id="quotation-welcome" class="text-center text-muted py-5">
                                    <i class="fas fa-file-invoice fa-4x mb-3 opacity-25"></i>
                                    <h5>Ready to Create Your Quotation</h5>
                                    <p>This wizard will guide you through creating a comprehensive tour quotation.</p>
                                </div>

                                <!-- Include Quotation Phase Forms -->
                                <?= view('chat/quotation-forms/phase1') ?>
                                <?= view('chat/quotation-forms/phase2') ?>
                                <?= view('chat/quotation-forms/phase3') ?>
                            </div>
                        </div>

                        <!-- Right Column - Help & Summary -->
                        <div class="col-md-4">
                            <!-- Help Section -->
                           

                            <!-- Summary Section -->
                            <div class="card h-100">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-list-check"></i> Quotation Summary</h6>
                                </div>
                                <div class="card-body overflow-auto">
                                    <div id="quotation-summary">
                                        <p class="text-muted small">Fill in the form to see summary</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                     
                            <!-- Manual Save Indicator -->
    
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="attachmentModal" tabindex="-1" aria-labelledby="attachmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <h6 class="fw-bold">Attached Files</h6>
                <div class="mb-3 d-flex flex-wrap" id="selected_files">
                    No attachments.
                </div>
                <button
                    class="btn btn-dark"
                    data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="newChatModal" tabindex="-1" aria-labelledby="newChatModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <h6 class="fw-bold">New Conversation</h6>
                <?php if ($group_id != 14) { ?>
                    <div class="form-group my-3">
                        <label class="form-label">Select Agent</label>
                        <select name="new_cnv_agent" id="new_cnv_agent" class="js-example-basic-single form-control" style="width: 100%;">
                            <option value=""></option>
                            <?php
                            foreach ($agents as $agentx) {
                                echo '<option value="' . $agentx['Sup_Acc_No'] . '">' . $agentx['Name'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                <?php } ?>
                <div class="form-group mb-4">
                    <label for="title">Title</label>
                    <input type="text" class="form-control" id="title" placeholder="Enter conversation title">
                </div>
                <div class="d-flex justify-content-end">
                    <button
                        class="btn btn-light me-1"
                        data-bs-dismiss="modal">
                        Close
                    </button>
                    <button class="btn btn-primary save_new_conversation">
                        <i class="fas fa-plus me-2"></i> Start Conversation
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="quotationRequestModal" tabindex="-1" aria-labelledby="quotationRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quotationRequestModalLabel">
                    <i class="fas fa-file-plus text-primary"></i> Add Quotation Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label class="form-label fw-bold">Select Language:</label>
                    <div class="form-check-inline me-4">
                        <input class="form-check-input" type="radio" name="request_language" id="language_english" value="english" checked>
                        <label class="form-check-label" for="language_english">
                            <i class="fas fa-globe"></i> English
                        </label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="radio" name="request_language" id="language_japanese" value="japanese">
                        <label class="form-check-label" for="language_japanese">
                            <i class="fas fa-yen-sign"></i> Japanese (日本語)
                        </label>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label for="quotation_request_text" class="form-label fw-bold">Request Details:</label>
                    <textarea
                        id="quotation_request_text"
                        name="quotation_request_text"
                        rows="12"
                        class="form-control"
                        placeholder="Enter travel inquiry details...&#10;&#10;For Japanese: Include dates, passenger count, hotels, activities, special requests&#10;For English: Provide complete travel requirements"
                        style="resize: vertical;">
                    </textarea>
                    <div class="form-text">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            <strong>Japanese:</strong> Text will be automatically processed and formatted in Sinhala.
                            <strong>English:</strong> Text will appear as-is in the chat.
                        </small>
                    </div>
                </div>
                <div class="alert alert-info d-none" id="processing_status">
                    <i class="fas fa-spinner fa-spin"></i> Processing request...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="submit-quotation-request">
                    <i class="fas fa-paper-plane"></i> Submit Request
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.body.dataset.groupId = '<?= $group_id ?>';
    document.body.dataset.agent = '<?= $agent ?? '' ?>';

    <?php if ($group_id == 14 && isset($agent_details) && $agent_details): ?>
    // Pass agent details for auto-population in Phase 1
    document.body.dataset.agentName = '<?= htmlspecialchars($agent_details['Name'] ?? '') ?>';
    document.body.dataset.agentEmail = '<?= htmlspecialchars($agent_details['email'] ?? '') ?>';
    document.body.dataset.agentCountry = '<?= htmlspecialchars($agent_details['country'] ?? '') ?>';
    document.body.dataset.agentMobile = '<?= htmlspecialchars($agent_details['Mobile'] ?? '') ?>';
    document.body.dataset.agentPrefix = '<?= htmlspecialchars($agent_details['prefix'] ?? '') ?>';
    <?php endif; ?>
</script>

<?= view('chat/layouts/footer') ?>
<script>
/**
 * Chat Quotation Integration JavaScript
 * Moved inline from external file for simplified dependency management
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

            // Save Quotation Draft
            if (e.target.matches('#save-quotation-draft')) {
                this.saveCurrentPhase(false);
            }

            // Preview Quotation
            if (e.target.matches('#preview-quotation')) {
                this.previewQuotation();
            }
        });

        // Conversation Selection Handler
        document.addEventListener('click', (e) => {
            if (e.target.closest('.conversation-item')) {
                const conversationId = e.target.closest('.conversation-item').dataset.conversationId;
                this.selectConversation(conversationId);
            }
        });

        // Phase Navigation
        document.addEventListener('click', (e) => {
            if (e.target.matches('.phase-indicator')) {
                const phase = parseInt(e.target.dataset.phase);
                if (phase <= this.getMaxAccessiblePhase()) {
                    this.showQuotationPhase(phase);
                    this.updatePhaseHelp(phase);
                }
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

    setQuotationStatusFromCache(conversationId, quotationStatusData) {
        this.conversationId = conversationId;

        if (this.isAgentUser) {
            // Show quotation controls immediately with cached data
            document.getElementById('quotation-controls').style.display = 'flex';

            if (quotationStatusData.has_active_quotation) {
                this.quotationId = quotationStatusData.quotation_id;
                this.currentPhase = quotationStatusData.current_phase;

                // Update button immediately with cached data
                this.updateQuotationControls(quotationStatusData);
                this.updateProgressIndicator();
            } else {
                // Reset quotation state
                this.quotationId = null;
                this.currentPhase = 1;
                this.quotationData = {};
                this.updateCreateButtonVisibility(true); // Assume can create for group 14
            }
        }

        // Still call checkQuotationStatus for full data and form pre-population
        // but don't wait for it to show the button
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

                    // Auto-show panel if quotation exists - DISABLED
                    // Uncomment below to auto-show panel for in-progress quotations
                    // if (this.isAgentUser && data.quotation_status === 'in_progress') {
                    //     this.showQuotationPanel();
                    // }
                } else {
                    // Reset quotation state
                    this.quotationId = null;
                    this.currentPhase = 1;
                    this.quotationData = {};
                    this.updateCreateButtonVisibility(data.can_create_quotation);
                }
            }
        } catch (error) {
            // Error checking quotation status
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

    showCreateQuotationModal() {
        if (this.quotationId) {
            // Continue existing quotation
            this.showQuotationPanel();

            // Auto-navigate to next incomplete phase or current phase
            const nextPhase = this.determineNextPhase();
            this.showQuotationPhase(nextPhase);

            this.showNotification(`Continuing quotation from Phase ${nextPhase}`, 'info');
        } else {
            // Create new quotation using conversation name
            const conversationNameElement = document.getElementById('selected_conversation_name');
            const conversationName = conversationNameElement ? conversationNameElement.textContent.trim() : '';

            // Check if a conversation is selected
            if (!conversationName || conversationName === 'Start a conversation') {
                this.showNotification('Please select a conversation first', 'warning');
                return;
            }

            // Directly create quotation with conversation name as tour name
            this.createNewQuotation(conversationName);
        }
    }

    determineNextPhase() {
        // If we have quotation data, determine the next incomplete phase
        const hasPhase1Data = this.quotationData.phase1 && Object.keys(this.quotationData.phase1).length > 0;
        const hasPhase2Data = this.quotationData.phase2 && Object.keys(this.quotationData.phase2).length > 0;

        // If Phase 1 is incomplete, start there
        if (!hasPhase1Data) {
            return 1;
        }
        // If Phase 1 is complete but Phase 2 is incomplete, go to Phase 2
        else if (hasPhase1Data && !hasPhase2Data) {
            return 2;
        }
        // Otherwise, go to Phase 3 (review)
        else {
            return 3;
        }
    }

    async createNewQuotation(tourName) {
        // Prevent duplicate submissions
        if (this.isCreatingQuotation) {
            return;
        }

        try {
            this.isCreatingQuotation = true;
            const createBtn = document.getElementById('create-quotation-btn');
            const originalText = createBtn.innerHTML;

            // Disable button and show loading state
            createBtn.disabled = true;
            createBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

            this.updateConversationId(); // Make sure we have the latest conversation ID
            //console.log('[ChatQuotation] Updated conversationId:', this.conversationId);

            const requestBody = {
                conversation_id: this.conversationId,
                tour_name: tourName
            };
            //console.log('[ChatQuotation] Request body:', requestBody);

            const url = `${this.baseUrl}/chat/quotation/start`;
            //console.log('[ChatQuotation] Making request to:', url);

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(requestBody)
            });

            //console.log('[ChatQuotation] Response status:', response.status, response.statusText);
            const result = await response.json();
            //console.log('[ChatQuotation] Response result:', result);

            if (result.status === 200) {
                this.quotationId = result.data.quotation_id;
                this.currentPhase = result.data.current_phase;
                //console.log('[ChatQuotation] Quotation created successfully - ID:', this.quotationId, 'Phase:', this.currentPhase);

                this.showNotification('Quotation created successfully!', 'success');
                this.showQuotationPanel();
                this.showQuotationPhase(1);
                this.updateProgressIndicator();

                // Auto-save disabled - only save on Next Phase click
                // this.startAutoSave();
            } else {
                //console.error('[ChatQuotation] Failed to create quotation:', result);
                this.showNotification(result.message || 'Failed to create quotation', 'error');
            }
        } catch (error) {
            //console.error('[ChatQuotation] Exception creating quotation:', error);
            //console.error('[ChatQuotation] Error stack:', error.stack);
            this.showNotification('An error occurred while creating quotation', 'error');
        } finally {
            // Reset button state and flag
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
        // Ask for confirmation if there's unsaved data
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

            // Load existing data for this phase
            this.loadPhaseData(phase);

            // Initialize handlers for the specific phase only
            setTimeout(() => {
                if (phase === 1) this.initializePhase1Handlers();
                else if (phase === 2) this.initializePhase2Handlers();
                else if (phase === 3) this.initializePhase3Handlers();
            }, 200);
        } else {
            // Show debug info temporarily
            const debugInfo = document.getElementById('debug-info');
            if (debugInfo) {
                debugInfo.style.display = 'block';
                setTimeout(() => {
                    debugInfo.style.display = 'none';
                }, 5000);
            }

            this.showNotification(`Phase ${phase} form not found`, 'error');
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

    initializePhase1Handlers() {

        // Check if Phase 1 elements exist
        const phase1Form = document.getElementById('phase1-form');
        if (!phase1Form) return;

        $(document).ready(() => {
            // Debug vehicle field initialization
            $('#vehicle option').each(function(i, option) {
                if (i < 3) { // Log first 3 options
                }
            });

            // Auto-populate agent fields if agent data is available
            this.autoPopulateAgentFields();

            // Auto-populate consignee field with conversation name
            this.autoPopulateConsigneeField();

            // Auto-generate agent reference
            this.generateAgentReference();

            // Toggle vehicle section based on tour type
            $(document).off('change', '#hotel_only, #transport_only').on('change', '#hotel_only, #transport_only', function() {
                const hotelOnly = $('#hotel_only').is(':checked');
                const transportOnly = $('#transport_only').is(':checked');


                // Prevent both being selected
                if (hotelOnly && transportOnly) {
                    if ($(this).attr('id') === 'hotel_only') {
                        $('#transport_only').prop('checked', false);
                    } else {
                        $('#hotel_only').prop('checked', false);
                    }
                }

                // Show/hide vehicle section
                if ($('#hotel_only').is(':checked')) {
                    $('#vehicle-section').hide();
                    $('#vehicle').prop('required', false);
                    $('#vehicle').val(''); // Clear vehicle selection in hotel-only mode
                } else {
                    $('#vehicle-section').show();
                    $('#vehicle').prop('required', !$('#transport_only').is(':checked'));
                }


                this.validatePhase1Form();
            }.bind(this));

            // Vehicle selection tracking
            $(document).off('change', '#vehicle').on('change', '#vehicle', function() {
                const selectedValue = $(this).val();
                const selectedOption = $(this).find('option:selected');
                const selectedText = selectedOption.length > 0 ? selectedOption.text() : '';

                // Only proceed if we have a valid selection
                if (selectedValue && selectedText) {
                    //console.log('Vehicle selected:', selectedValue, selectedText);
                }
                this.validatePhase1Form();
            }.bind(this));

            // Auto-validate passenger count vs room allocation
            $(document).off('input change keyup', '#pass_count, #sgl, #dbl, #tpl').on('input change keyup', '#pass_count, #sgl, #dbl, #tpl', () => {
                this.validateRoomAllocation();
                this.validatePhase1Form();
            });

            // Date validation
            $(document).off('input change blur keyup', '#start_date, #end_date').on('input change blur keyup', '#start_date, #end_date', () => {
                const startDate = new Date($('#start_date').val());
                const endDate = new Date($('#end_date').val());

                if (startDate && endDate && startDate >= endDate) {
                    this.showNotification('End date must be after start date', 'error');
                    $('#end_date').val('');
                }
                this.validatePhase1Form();
            });

            // Button handlers
            $(document).off('click', '#save-draft-btn').on('click', '#save-draft-btn', () => {
                this.savePhase1Data(false);
            });

            // CRITICAL: Next phase button handler
            $(document).off('click', '#next-phase-btn').on('click', '#next-phase-btn', (e) => {

                // Prevent any default behavior
                e.preventDefault();
                e.stopPropagation();

                // Validate form before proceeding
                if ($('#next-phase-btn').prop('disabled') || !this.validatePhase1Form()) {
                    this.showNotification('Please fill all required fields correctly', 'warning');
                    return;
                }

                try {
                    this.savePhase1Data(true); // true = proceed to next phase after save
                } catch (error) {
                    //console.error('Error in savePhase1Data:', error);
                    this.showNotification('Error saving Phase 1 data', 'error');
                }
            });

            // DISABLED: Alternative handler was skipping save operation
            // $(document).off('click.phase1-navigation', '#next-phase-btn').on('click.phase1-navigation', '#next-phase-btn', (e) => {
            //     e.preventDefault();
            //     e.stopPropagation();

            //     if (window.chatQuotationInstance) {
            //         window.chatQuotationInstance.showQuotationPhase(2);
            //     } else {
            //         //console.error('Global chatQuotationInstance not available!');
            //     }
            // });

            // Test if button is actually clickable
            setTimeout(() => {
                const btn = $('#next-phase-btn');

                // Manual test click
                btn.on('click.test', () => {
                });
            }, 500);

            // Initialize form validation
            setTimeout(() => {
                this.validatePhase1Form();
            }, 300);
        });
    }

    autoPopulateAgentFields() {
        // Auto-populate agent email and contact from dataset
        //console.log('[ChatQuotation] Auto-populating agent fields...');
        const agentEmail = document.body.dataset.agentEmail;
        const agentMobile = document.body.dataset.agentMobile;

        //console.log('[ChatQuotation] Agent email:', agentEmail, 'Agent mobile:', agentMobile);

        try {
            if (agentEmail && $('#email').length && $('#email').val() === '') {
                $('#email').val(agentEmail);
                //console.log('[ChatQuotation] Email populated:', agentEmail);
            }

            if (agentMobile && $('#contact').length && $('#contact').val() === '') {
                $('#contact').val(agentMobile);
                //console.log('[ChatQuotation] Contact populated:', agentMobile);
            }
        } catch (error) {
            //console.error('[ChatQuotation] Error populating agent fields:', error);
        }
    }

    generateAgentReference() {
        // Auto-generate agent reference if not already set
        //console.log('[ChatQuotation] Generating agent reference...');

        try {
            if (this.conversationId && $('#agent_ref').length && $('#agent_ref').val() === '') {
                const agentPrefix = document.body.dataset.agentPrefix || 'AGENT';
                const timestamp = Date.now();
                const agentRef = `${agentPrefix}-CHAT-${this.conversationId}-${timestamp}`;
                $('#agent_ref').val(agentRef);
                //console.log('[ChatQuotation] Agent reference generated:', agentRef);
            }
        } catch (error) {
            //console.error('[ChatQuotation] Error generating agent reference:', error);
        }
    }

    autoPopulateConsigneeField() {
        // Auto-populate consignee field with conversation name
        try {
            const conversationNameElement = document.getElementById('selected_conversation_name');
            const consigneeField = document.getElementById('consignee');

            if (conversationNameElement && consigneeField) {
                const conversationName = conversationNameElement.textContent.trim();

                // Only populate if field is empty and conversation name is valid
                if (conversationName &&
                    conversationName !== 'Start a conversation' &&
                    consigneeField.value === '') {
                    consigneeField.value = conversationName;
                }
            }
        } catch (error) {
            console.error('Error populating consignee field:', error);
        }
    }

    validatePhase1Form() {
        // Check if all required fields are filled
        //console.log('[ChatQuotation] Validating Phase 1 form...');
        const requiredFields = ['start_date', 'end_date', 'pass_count', 'consignee', 'email', 'contact', 'agent_ref'];

        let allValid = true;
        const errors = [];

        // Basic field validation
        requiredFields.forEach(fieldId => {
            try {
                const field = $('#' + fieldId);
                if (field.length === 0) {
                    //console.warn('[ChatQuotation] Field not found:', fieldId);
                    return;
                }
                const rawValue = field.val();
                const value = rawValue === undefined || rawValue === null ? '' : String(rawValue);

                if (!value || value.trim() === '') {
                    allValid = false;
                    errors.push(`${fieldId.replace('_', ' ')} is required`);
                    field.addClass('is-invalid');
                    //console.log('[ChatQuotation] Validation failed for field:', fieldId, 'value:', value);
                } else {
                    field.removeClass('is-invalid');
                }
            } catch (error) {
                //console.error('[ChatQuotation] Error validating field:', fieldId, error);
                allValid = false;
            }
        });

        // Date validation
        const startDate = new Date($('#start_date').val());
        const endDate = new Date($('#end_date').val());

        if (startDate && endDate && startDate >= endDate) {
            allValid = false;
            errors.push('End date must be after start date');
            $('#end_date').addClass('is-invalid');
        }

        // Passenger count validation
        const passCount = parseInt($('#pass_count').val() || 0);
        if (passCount < 1) {
            allValid = false;
            errors.push('At least 1 passenger is required');
            $('#pass_count').addClass('is-invalid');
        }

        // Vehicle validation (only if not hotel-only)
        const hotelOnly = $('#hotel_only').is(':checked');
        if (!hotelOnly) {
            const vehicle = $('#vehicle').val();
            if (!vehicle) {
                allValid = false;
                errors.push('Vehicle selection is required for this tour type');
                $('#vehicle').addClass('is-invalid');
            } else {
                $('#vehicle').removeClass('is-invalid');
            }
        } else {
            // Remove validation styling for hotel-only tours
            $('#vehicle').removeClass('is-invalid');
        }

        // Update next button state
        const nextBtn = $('#next-phase-btn');
        if (nextBtn.length > 0) {
            nextBtn.prop('disabled', !allValid);

            if (!allValid) {
                nextBtn.removeClass('btn-primary').addClass('btn-secondary');
                nextBtn.attr('title', 'Please fill all required fields: ' + errors.join(', '));
            } else {
                nextBtn.removeClass('btn-secondary').addClass('btn-primary');
                nextBtn.removeAttr('title');
            }
        }

        return allValid;
    }

    validateRoomAllocation() {
        const passCount = parseInt($('#pass_count').val() || 0);
        const sgl = parseInt($('#sgl').val() || 0);
        const dbl = parseInt($('#dbl').val() || 0);
        const tpl = parseInt($('#tpl').val() || 0);

        const totalBeds = sgl + (dbl * 2) + (tpl * 3);

        if (passCount > 0 && totalBeds > 0) {
            const message = totalBeds < passCount
                ? 'Room allocation insufficient for passenger count'
                : (totalBeds > passCount + 2 ? 'Room allocation may be excessive' : null);

            if (message) {
                this.showNotification(message, totalBeds < passCount ? 'warning' : 'info');
            }
        }
    }

    async savePhase1Data(proceedToNext = false) {
        try {
            const formData = {
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                pass_count: $('#pass_count').val(),
                consignee: $('#consignee').val(),
                email: $('#email').val(),
                contact: $('#contact').val(),
                agent_ref: $('#agent_ref').val(),
                sgl: $('#sgl').val(),
                dbl: $('#dbl').val(),
                tpl: $('#tpl').val(),
                hotel_only: $('#hotel_only').is(':checked') ? 1 : 0,
                transport_only: $('#transport_only').is(':checked') ? 1 : 0,
                vehicle: $('#vehicle').val(),
                arrival_flight: $('#arrival_flight').val(),
                departure_flight: $('#departure_flight').val(),
                arrival_flight_time: $('#arrival_flight_time').val(),
                departure_flight_time: $('#departure_flight_time').val(),
                is_twin: $('#is_twin').is(':checked') ? 1 : 0
            };


            // If no quotation ID exists, create new quotation first
            if (!this.quotationId) {
                const tourName = formData.consignee || 'New Tour Quotation';
                await this.createNewQuotation(tourName);

                if (!this.quotationId) {
                    this.showNotification('Failed to create quotation', 'error');
                    return;
                }
            }

            // Save phase data
            //console.log('[ChatQuotation] Calling saveQuotationPhase with phase 1...');
            await this.saveQuotationPhase(1, formData, proceedToNext);

        } catch (error) {
            //console.error('[ChatQuotation] Error saving Phase 1 data:', error);
            //console.error('[ChatQuotation] Error stack:', error.stack);
            this.showNotification('Error saving Phase 1 data: ' + error.message, 'error');
        }
    }

    initializePhase2Handlers() {
        // Phase 2 handlers - Hotel Bookings & Passenger Management
        if (!document.getElementById('phase2-form')) return;

        $(document).ready(() => {
            // Generate hotel timeline based on Phase 1 data
            this.generateHotelTimeline();

            // Load hotels after timeline is generated (no callback needed here as we only populate when showing Phase 2)
            this.loadHotels();

            // Hotel selection change handler
            const self = this;
            $(document).off('change', '.hotel-select').on('change', '.hotel-select', function() {
                const hotelId = $(this).val();
                const date = $(this).data('date') || $(this).closest('.hotel-day-item').data('date');
                const roomTypeSelect = $(this).closest('.hotel-day-item').find('.room-type-select');

                //console.log('[ChatQuotation] Hotel selected:', {hotelId, date, hasRoomTypeSelect: roomTypeSelect.length > 0});
                //console.log('[ChatQuotation] Hotel select element data-date:', $(this).data('date'));
                //console.log('[ChatQuotation] Hotel day item data-date:', $(this).closest('.hotel-day-item').data('date'));

                // Clear dependent dropdowns
                roomTypeSelect.html('<option value="">Select Room Type</option>').prop('disabled', !hotelId);

                // Reset save button
                $(this).closest('.hotel-day-item').find('.save-day-btn').prop('disabled', true);

                if (hotelId && date) {
                    //console.log('[ChatQuotation] Calling loadRoomTypesForHotel');
                    self.loadRoomTypesForHotel(hotelId, roomTypeSelect, date);
                }
            });

            // Room type change handler
            $(document).off('change', '.room-type-select').on('change', '.room-type-select', function() {
                const roomType = $(this).val();
                const mealPlanSelect = $(this).closest('.hotel-day-item').find('.meal-plan-select');
                const dayItem = $(this).closest('.hotel-day-item');

                //console.log('[ChatQuotation] Room type selected:', {roomType, hasMealPlanSelect: mealPlanSelect.length > 0});

                // Enable meal plan if room type selected
                mealPlanSelect.prop('disabled', !roomType);
                dayItem.find('.save-day-btn').prop('disabled', true);

                if (roomType) {
                    //console.log('[ChatQuotation] Calling loadRoomRates for room type:', roomType);
                    self.loadRoomRates(dayItem);
                }
            });

            // Meal plan change handler
            $(document).off('change', '.meal-plan-select').on('change', '.meal-plan-select', function() {
                const mealPlan = $(this).val();
                const dayItem = $(this).closest('.hotel-day-item');
                const saveBtn = dayItem.find('.save-day-btn');

                //console.log('[ChatQuotation] Meal plan selected:', {mealPlan});

                if (mealPlan) {
                    //console.log('[ChatQuotation] Calling loadRoomRates for meal plan:', mealPlan);
                    self.loadRoomRates(dayItem);
                    // Check if all required fields are filled to enable save button
                    //console.log('[ChatQuotation] Checking day completion');
                    self.checkDayCompletion(dayItem);
                } else {
                    saveBtn.prop('disabled', true);
                }
            });

            // Save day button handler
            $(document).off('click', '.save-day-btn').on('click', '.save-day-btn', (event) => {
                const dayItem = $(event.target).closest('.hotel-day-item');
                //console.log('[ChatQuotation] Save day button clicked, dayItem found:', dayItem.length > 0);
                this.saveDayData(dayItem);
            });

            // Passenger management handlers
            $(document).off('click', '#add-passenger-btn').on('click', '#add-passenger-btn', function() {
                const passengerName = $('#passenger_name').val().trim();
                if (passengerName) {
                    this.addPassenger(passengerName);
                    $('#passenger_name').val('');
                } else {
                    this.showNotification('Please enter a passenger name', 'warning');
                }
            }.bind(this));

            // Remove passenger handler
            $(document).off('click', '.remove-passenger-btn').on('click', '.remove-passenger-btn', function() {
                const row = $(this).closest('tr');
                const passengerName = row.find('td:eq(1)').text();
                this.removePassenger(row, passengerName);
            }.bind(this));

            // Enter key handler for passenger input
            $(document).off('keypress', '#passenger_name').on('keypress', '#passenger_name', function(e) {
                if (e.which === 13) { // Enter key
                    $('#add-passenger-btn').click();
                }
            });

            // Activities event handlers - Direct database saving
            $(document).off('click', '.add-activity-btn').on('click', '.add-activity-btn', (event) => {
                const dayItem = $(event.target).closest('.hotel-day-item');
                const activitiesContainer = dayItem.find('.activities-container');
                const activityName = activitiesContainer.find('.activity-name').val().trim();
                const ss = activitiesContainer.find('.activity-ss').is(':checked');
                const op = activitiesContainer.find('.activity-op').is(':checked');
                const day = dayItem.data('day');
                const date = dayItem.data('date');

                if (activityName) {
                    // Show loading state
                    const addBtn = $(event.target);
                    const originalText = addBtn.html();
                    addBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');

                    // Save directly to database
                    this.addActivityDirectly(day, date, activityName, ss, op, addBtn, originalText);

                    // Clear form
                    activitiesContainer.find('.activity-name').val('');
                    activitiesContainer.find('.activity-ss, .activity-op').prop('checked', false);
                } else {
                    this.showNotification('Please enter an activity name', 'warning');
                }
            });

            $(document).off('click', '.remove-activity-btn').on('click', '.remove-activity-btn', (event) => {
                const button = $(event.target).closest('.remove-activity-btn');
                const day = button.data('day');
                const activityId = parseInt(button.data('activity-id'));
                this.removeActivityDirectly(day, activityId, button);
            });

            // Handle Enter key on activity input
            $(document).off('keypress', '.activity-name').on('keypress', '.activity-name', (event) => {
                if (event.which === 13) { // Enter key
                    $(event.target).closest('.activities-container').find('.add-activity-btn').click();
                }
            });

            // Navigation buttons
            $(document).off('click', '#back-to-phase1-btn').on('click', '#back-to-phase1-btn', () => {
                this.showQuotationPhase(1);
            });

            $(document).off('click', '#save-phase2-draft-btn').on('click', '#save-phase2-draft-btn', () => {
                this.savePhase2Data(false);
            });

            $(document).off('click', '#complete-phase2-btn').on('click', '#complete-phase2-btn', () => {
                this.savePhase2Data(true);
            });

            // Update passenger count from existing data
            this.updatePassengerCount();
        });
    }

    generateHotelTimeline() {
        // Try to get dates from quotation data first, then fall back to DOM fields
        let startDate = this.quotationData && this.quotationData.phase1 ? this.quotationData.phase1.start_date : null;
        let endDate = this.quotationData && this.quotationData.phase1 ? this.quotationData.phase1.end_date : null;

        // If not available from quotation data, try DOM fields
        if (!startDate) startDate = $('#start_date').val();
        if (!endDate) endDate = $('#end_date').val();

        if (!startDate || !endDate) {
            $('#hotel-timeline-container').html('<div class="alert alert-warning">Please complete Phase 1 with tour dates first.</div>');
            return;
        }

        const start = new Date(startDate);
        const end = new Date(endDate);
        const container = $('#hotel-timeline-container');
        const template = $('#hotel-day-template').html();

        container.empty();

        let dayNumber = 1;
        const currentDate = new Date(start);

        while (currentDate <= end) {
            const dateStr = currentDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
            const dayName = currentDate.toLocaleDateString('en-US', { weekday: 'long' });
            const isoDate = currentDate.toISOString().split('T')[0];

            // Clone template and populate data
            let dayHtml = template.replace(/data-date=""/g, `data-date="${isoDate}"`)
                                  .replace(/data-day=""/g, `data-day="${dayNumber}"`)
                                  .replace('<span class="day-number"></span>', dayNumber)
                                  .replace('<span class="day-date"></span>', dateStr)
                                  .replace('<span class="day-name"></span>', dayName);

            //console.log(`[ChatQuotation] Day ${dayNumber} - Date: ${isoDate}, HTML contains data-date: ${dayHtml.includes('data-date="' + isoDate + '"')}`);

            container.append(dayHtml);

            dayNumber++;
            currentDate.setDate(currentDate.getDate() + 1);
        }

        // Update total passengers display
        const totalPax = parseInt($('#total_pax').val()) || 0;
        $('#total-passengers').text(totalPax);
    }

    loadHotels(callback) {
        // Prevent multiple simultaneous hotel loading
        if (this.hotelsLoading) {
            //console.log('[ChatQuotation] Hotels already loading, skipping duplicate call');
            return;
        }

        this.hotelsLoading = true;
        //console.log('[ChatQuotation] loadHotels called');
        $.ajax({
            url: `${this.baseUrl}/tour/quotation/get_hotel_details`,
            type: 'GET',
            data: {
                destination_city: '',
                date: ''
            },
            dataType: 'json',
            success: function(data) {
                //console.log('[ChatQuotation] Hotel API response:', data);
                if (data.status == 200 && data.data && data.data.hotels) {
                    let hotelOptionsHtml = '<option value="">Select Hotel</option>';

                    $.each(data.data.hotels, function(index, hotel) {
                        if (hotel && hotel.Sup_Acc_No && hotel.Name) {
                            hotelOptionsHtml += `<option value="${hotel.Sup_Acc_No}">${hotel.Name}</option>`;
                        }
                    });

                    $('.hotel-select').html(hotelOptionsHtml);
                    //console.log('[ChatQuotation] Hotels loaded successfully:', data.data.hotels.length);

                    this.hotelsLoading = false;

                    // Execute callback after hotels are loaded
                    if (typeof callback === 'function') {
                        //console.log('[ChatQuotation] Executing callback after hotels loaded');
                        callback();
                    }
                } else {
                    //console.error('[ChatQuotation] Hotel data not in expected format:', data);
                    this.hotelsLoading = false;
                }
            }.bind(this),
            error: function(xhr, status, error) {
                //console.error('[ChatQuotation] Hotel loading failed:', status, error);
                //console.error('[ChatQuotation] Response:', xhr.responseText);
                this.hotelsLoading = false;
            }.bind(this)
        });
    }


    loadRoomTypesForHotel(hotelId, roomTypeSelect, date) {
        //console.log('[ChatQuotation] loadRoomTypesForHotel called with:', {hotelId, date});
        $.ajax({
            url: `${this.baseUrl}/tour/quotation/get_hotel_type`,
            type: 'GET',
            data: {
                hotelId: hotelId,
                date: date,
            },
            dataType: 'json',
            success: function(data) {
                //console.log('[ChatQuotation] Room types API response:', data);
                if (data.status == 200) {
                    roomTypeSelect.empty().append('<option value="">Select Room Type</option>');

                    if (data.data && data.data.hotels_type && Array.isArray(data.data.hotels_type)) {
                        $.each(data.data.hotels_type, function(index, roomType) {
                            //console.log('[ChatQuotation] Processing room type:', roomType);
                            if (roomType && roomType.id && roomType.rtype) {
                                roomTypeSelect.append(
                                    `<option value="${roomType.id}">${roomType.rtype}</option>`
                                );
                            }
                        });
                        //console.log('[ChatQuotation] Room types loaded:', data.data.hotels_type.length);
                    } else {
                        //console.warn('[ChatQuotation] No room types found in response');
                    }
                    roomTypeSelect.prop('disabled', false);
                    //console.log('[ChatQuotation] Room type dropdown enabled');
                } else {
                    //console.error('[ChatQuotation] Room type loading failed:', data.message);
                    this.showNotification(data.message || 'Error loading room types', 'error');
                }
            }.bind(this),
            error: function(xhr, status, error) {
                //console.error('[ChatQuotation] Room type AJAX error:', status, error);
                //console.error('[ChatQuotation] Response text:', xhr.responseText);
                this.showNotification('Error loading room types', 'error');
            }.bind(this)
        });
    }

    loadRoomRates(dayItem) {
        const hotelId = dayItem.find('.hotel-select').val();
        const roomType = dayItem.find('.room-type-select').val();
        const mealPlan = dayItem.find('.meal-plan-select').val();
        const date = dayItem.data('date');

        if (hotelId && roomType && mealPlan) {
            $.ajax({
                url: `${this.baseUrl}/tour/quotation/get_meal_prices`,
                type: 'GET',
                data: {
                    room_type: roomType,
                    h_code: hotelId,
                    meal_plan: mealPlan,
                    date: date
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status == 200) {

                        this.checkDayCompletion(dayItem);
                    } else {
                        this.showNotification(data.message || 'Error loading room rates', 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotification('Error loading room rates', 'error');
                }.bind(this)
            });
        }
    }

    checkDayCompletion(dayItem) {
        const hotel = dayItem.find('.hotel-select').val();
        const roomType = dayItem.find('.room-type-select').val();
        const mealPlan = dayItem.find('.meal-plan-select').val();
        const isComplete = hotel && roomType && mealPlan;

        dayItem.find('.save-day-btn').prop('disabled', !isComplete);

        // Update status badge
        const statusBadge = dayItem.find('.hotel-status-badge .badge');
        if (isComplete) {
            statusBadge.removeClass('bg-warning').addClass('bg-success').text('Ready');
        } else {
            statusBadge.removeClass('bg-success').addClass('bg-warning').text('Pending');
        }
    }

    saveDayData(dayItem) {
        const dayNumber = dayItem.data('day') || dayItem.attr('data-day');
        const date = dayItem.data('date') || dayItem.attr('data-date');

        // Debug info commented out

        // Validation checks
        if (!this.quotationId) {
            this.showNotification('No quotation ID available. Please create quotation first.', 'error');
            return;
        }

        if (!dayNumber || !date) {
            this.showNotification('Invalid day or date information.', 'error');
            return;
        }

        const hotel = dayItem.find('.hotel-select').val() || '';
        const hotelName = dayItem.find('.hotel-select option:selected').text() || '';
        const roomType = dayItem.find('.room-type-select').val() || '';
        const mealCode = dayItem.find('.meal-plan-select').val() || '';

        const saveBtn = dayItem.find('.save-day-btn');
        const originalText = saveBtn.html();

        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        // Check if this is a new record or update based on whether data exists for this day
        // Use '0' for new records (insert), '1' for existing records (update)
        const isUpdate = '0';

        // Prepare data for existing NewTourController::save_date endpoint
        const formData = new FormData();
        formData.append('trans_tour_id', this.quotationId);
        formData.append('trans_row_id', dayNumber);
        formData.append('t_date', date);
        formData.append('hotel', hotel);
        formData.append('hotel_code', hotel); // Using hotel ID as hotel code for simplicity
        formData.append('room_type', roomType);
        formData.append('room_type_id', roomType);
        formData.append('meal_code', mealCode);
        formData.append('sgl', '0');
        formData.append('dbl', '0');
        formData.append('tpl', '0');
        formData.append('up_flag', isUpdate); // Use 1 for updates, 0 for new records
        formData.append('is_hotel', '1'); // Hotel only flag

        // Make API call to existing endpoint
        fetch(`${this.baseUrl}/tour/quotation/save_date`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' || data.success) {
                // Store locally as well for UI state
                const dayData = {
                    day: dayNumber,
                    date: date,
                    hotel_id: hotel,
                    hotel_name: hotelName,
                    room_type: roomType,
                    meal_plan: mealCode,
                    sgl_rate: 0,
                    dbl_rate: 0,
                    tpl_rate: 0
                };

                if (!this.quotationData.phase2) {
                    this.quotationData.phase2 = { hotel_data: {} };
                }
                this.quotationData.phase2.hotel_data[dayNumber] = dayData;

                saveBtn.prop('disabled', false).html('<i class="fas fa-check"></i> Saved');

                // Update status badge
                const statusBadge = dayItem.find('.hotel-status-badge .badge');
                statusBadge.removeClass('bg-warning bg-success').addClass('bg-success').text('Saved');

                this.showNotification(`Day ${dayNumber} saved successfully!`, 'success');

                // Save activities for this day
                this.saveActivities(dayNumber, date)
                    .then(activitiesResponse => {
                        if (activitiesResponse.status === 200) {
                            console.log('Activities saved successfully');
                        } else {
                            console.warn('Activities save warning:', activitiesResponse.message);
                        }
                    })
                    .catch(activitiesError => {
                        console.error('Activities save error:', activitiesError);
                        // Don't fail the whole save operation for activities
                    });

                // Update the quotation summary to reflect the new booking
                this.updateQuotationSummary();

                // Reset button after 2 seconds
                setTimeout(() => {
                    saveBtn.html('<i class="fas fa-save"></i> Save Day');
                }, 2000);
            } else {
                throw new Error(data.message || 'Failed to save day data');
            }
        })
        .catch(error => {
            //console.error('Error saving day data:', error);
            saveBtn.prop('disabled', false).html(originalText);

            this.showNotification(`Failed to save Day ${dayNumber}: ${error.message}`, 'error');
        });
    }

    addPassenger(passengerName) {
        const passengerTable = $('#passenger-list');
        const currentCount = passengerTable.find('tr').length;
        const rowNumber = currentCount + 1;

        const passengerRow = `
            <tr>
                <td>${rowNumber}</td>
                <td>${passengerName}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-passenger-btn">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        passengerTable.append(passengerRow);
        this.updatePassengerCount();

        // Store passenger data
        if (!this.quotationData.phase2) {
            this.quotationData.phase2 = {};
        }
        if (!this.quotationData.phase2.passengers) {
            this.quotationData.phase2.passengers = [];
        }
        this.quotationData.phase2.passengers.push(passengerName);

        this.showNotification(`Passenger "${passengerName}" added successfully!`, 'success');
    }

    removePassenger(row, passengerName) {
        row.remove();

        // Renumber remaining rows
        $('#passenger-list tr').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });

        this.updatePassengerCount();

        // Remove from stored data
        if (this.quotationData.phase2 && this.quotationData.phase2.passengers) {
            const index = this.quotationData.phase2.passengers.indexOf(passengerName);
            if (index > -1) {
                this.quotationData.phase2.passengers.splice(index, 1);
            }
        }

        this.showNotification(`Passenger "${passengerName}" removed successfully!`, 'success');
    }

    updatePassengerCount() {
        const currentCount = $('#passenger-list tr').length;
        const totalPax = parseInt($('#total_pax').val()) || 0;

        $('#passenger-count').text(currentCount);
        $('#total-passengers').text(totalPax);
    }

    savePhase2Data(proceedToNext = false) {
        const hotelData = {};
        const passengers = [];

        // Collect hotel data from each day
        $('.hotel-day-item').each(function() {
            const dayItem = $(this);
            const dayNumber = dayItem.data('day');
            const date = dayItem.data('date');
            const hotel = dayItem.find('.hotel-select').val();
            const roomType = dayItem.find('.room-type-select').val();
            const mealPlan = dayItem.find('.meal-plan-select').val();

            if (hotel && roomType && mealPlan) {
                hotelData[dayNumber] = {
                    day: dayNumber,
                    date: date,
                    hotel_id: hotel,
                    hotel_name: dayItem.find('.hotel-select option:selected').text(),
                    room_type: roomType,
                    meal_plan: mealPlan
                };
            }
        });

        // Collect passenger data
        $('#passenger-list tr').each(function() {
            const passengerName = $(this).find('td:eq(1)').text().trim();
            if (passengerName) {
                passengers.push(passengerName);
            }
        });

        const phase2Data = {
            hotel_data: hotelData,
            passengers: passengers
        };

        // Save to server via ChatController
        this.saveQuotationPhase(2, phase2Data, proceedToNext);
    }

    initializePhase3Handlers() {
        // Phase 3 handlers - simplified version
        if (!document.getElementById('phase3-form')) return;

        $(document).ready(() => {

            // Previous phase button
            $(document).off('click', '#prev-phase3-btn').on('click', '#prev-phase3-btn', () => {
                this.showQuotationPhase(2);
            });

            // Save Phase 3 button
            $(document).off('click', '#save-phase3-btn').on('click', '#save-phase3-btn', () => {
                this.savePhase3Data();
            });

            // Complete quotation button
            $(document).off('click', '#complete-quotation-btn').on('click', '#complete-quotation-btn', () => {
                this.savePhase3Data(true); // Save before completing
                this.showNotification('Quotation completed successfully!', 'success');
            });
        });
    }

    async savePhase3Data(completing = false) {
        if (!this.quotationId) {
            this.showNotification('No quotation found to save', 'error');
            return;
        }

        const saveBtn = $('#save-phase3-btn');
        const originalText = saveBtn.html();

        // Show loading state
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        try {
            // Collect form data
            const phaseData = {
                customer_requirements: $('#customer_requirements').val() || '',
                internal_notes: $('#internal_notes').val() || '',
                agent_comments: $('#agent_comments').val() || ''
            };

            // Save to backend
            const response = await this.saveQuotationPhase(3, phaseData, false);

            if (response.status === 200) {
                this.showNotification('Phase 3 data saved successfully!', 'success');
                if (completing) {
                    // Update phase completion status
                    this.currentPhase = 4; // Mark as completed
                }
            } else {
                throw new Error(response.message || 'Failed to save Phase 3 data');
            }

        } catch (error) {
            this.showNotification(`Failed to save: ${error.message}`, 'error');
        } finally {
            // Reset button state
            saveBtn.prop('disabled', false).html(originalText);
        }
    }

    async saveQuotationPhase(phase, data, proceedToNext = false) {
        //console.log('[ChatQuotation] saveQuotationPhase called - phase:', phase, 'proceedToNext:', proceedToNext);
        //console.log('[ChatQuotation] quotationId:', this.quotationId);
        //console.log('[ChatQuotation] phase data:', data);

        try {
            if (!this.quotationId) {
                //console.error('[ChatQuotation] No quotationId available for saving phase');
                //console.log('[ChatQuotation] Attempting to create new quotation first...');

                // Try to create a new quotation if none exists
                const tourName = data.consignee || 'New Tour Quotation';
                await this.createNewQuotation(tourName);

                if (!this.quotationId) {
                    throw new Error('Failed to create quotation - no ID available');
                }

                //console.log('[ChatQuotation] New quotation created with ID:', this.quotationId);
            }

            const requestBody = {
                quotation_id: this.quotationId,
                phase: phase,
                phase_data: data
            };

            //console.log('[ChatQuotation] Request body:', requestBody);

            const url = `${this.baseUrl}/chat/quotation/save-phase`;
            //console.log('[ChatQuotation] Making request to:', url);

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(requestBody)
            });

            //console.log('[ChatQuotation] Response status:', response.status, response.statusText);
            const result = await response.json();
            //console.log('[ChatQuotation] Response result:', result);

            if (result.status === 200) {
                //console.log('[ChatQuotation] Phase saved successfully');
                // Update stored data
                this.quotationData[`phase${phase}`] = data;

                this.showNotification('Progress saved successfully!', 'success');
                this.updateQuotationSummary();

                if (proceedToNext && result.data && result.data.next_phase) {
                    //console.log('[ChatQuotation] Proceeding to next phase:', result.data.next_phase);
                    this.showQuotationPhase(result.data.next_phase);
                } else if (proceedToNext) {
                    //console.log('[ChatQuotation] Proceeding to default next phase: 2');
                    this.showQuotationPhase(2);
                } else if (phase === 3 && proceedToNext) {
                    //console.log('[ChatQuotation] Final submission for phase 3');
                    // Final submission
                    this.handleQuotationCompletion();
                }

                this.updateProgressIndicator();
            } else {
                //console.error('[ChatQuotation] Phase save failed:', result);
                this.showNotification(result.message || 'Failed to save progress', 'error');
            }
        } catch (error) {
            //console.error('[ChatQuotation] Error in saveQuotationPhase:', error);
            //console.error('[ChatQuotation] Error stack:', error.stack);
            this.showNotification('An error occurred while saving: ' + error.message, 'error');
            throw error; // Re-throw to ensure error propagation
        }
    }

    saveCurrentPhase(proceedToNext = false) {
        // Trigger save from current active form
        const activeForm = document.querySelector('.quotation-phase-form:not([style*="display: none"])');
        if (activeForm) {
            const saveButton = activeForm.querySelector('[id$="-btn"]:not([disabled])');
            if (saveButton) {
                saveButton.click();
            }
        }
    }

    handleQuotationCompletion() {
        this.showNotification('Quotation submitted for internal review!', 'success');

        // Update UI to show completion
        const progressText = document.getElementById('progress-text');
        progressText.textContent = 'Completed - Under Review';

        // Hide all phase forms
        document.querySelectorAll('.quotation-phase-form').forEach(form => {
            form.style.display = 'none';
        });

        // Show completion screen
        const completionScreen = document.getElementById('quotation-completed');
        if (completionScreen) {
            completionScreen.style.display = 'block';
        }

        // Auto-save disabled - no need to stop
        // this.stopAutoSave();
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

    getMaxAccessiblePhase() {
        // Agents can access phases they've completed + current + 1
        return Math.min(this.currentPhase + 1, 3);
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

        // Handle Phase 2 specific data (passengers and hotel bookings)
        if (phase === 2) {
            // Generate hotel timeline for Phase 2
            this.generateHotelTimeline();

            if (this.quotationData.passengers) {
                this.loadPassengerData();
            }

            // Load hotels first, then load saved hotel bookings data
            this.loadHotels(() => {
                // This callback executes after hotels are loaded
                this.loadHotelBookingsData();
            });
        }

        // Handle Phase 3 specific data (notes)
        if (phase === 3 && this.quotationData.phase3) {
            this.loadPhase3Data();
        }

        // Update quotation summary for all phases
        this.updateQuotationSummary();
    }

    loadPhase3Data() {
        if (!this.quotationData.phase3) return;

        const phase3Data = this.quotationData.phase3;

        // Populate the three textarea fields
        const customerReqField = document.getElementById('customer_requirements');
        const internalNotesField = document.getElementById('internal_notes');
        const agentCommentsField = document.getElementById('agent_comments');

        if (customerReqField && phase3Data.customer_requirements) {
            customerReqField.value = phase3Data.customer_requirements;
        }

        if (internalNotesField && phase3Data.internal_notes) {
            internalNotesField.value = phase3Data.internal_notes;
        }

        if (agentCommentsField && phase3Data.agent_comments) {
            agentCommentsField.value = phase3Data.agent_comments;
        }
    }

    loadPassengerData() {
        const passengerList = document.getElementById('passenger-list');
        const passengerCount = document.getElementById('passenger-count');
        const totalPassengers = document.getElementById('total-passengers');

        if (passengerList && this.quotationData.passengers) {
            // Clear existing passengers
            passengerList.innerHTML = '';

            // Add each passenger
            this.quotationData.passengers.forEach((passengerName, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${passengerName}</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm remove-passenger-btn" data-index="${index}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                passengerList.appendChild(row);
            });

            // Update counters
            if (passengerCount) passengerCount.textContent = this.quotationData.passengers.length;
            if (totalPassengers) {
                const totalFromQuotation = this.quotationData.phase1?.passenger_count || this.quotationData.passengers.length;
                totalPassengers.textContent = totalFromQuotation;
            }
        }
    }

    loadHotelBookingsData() {
        //console.log('[ChatQuotation] loadHotelBookingsData called');

        if (!this.quotationId) {
            //console.log('[ChatQuotation] No quotation ID available for loading hotel bookings');
            return;
        }

        // Fetch saved Phase 2 data from database
        $.ajax({
            url: `${this.baseUrl}/chat/quotation/phase2-data`,
            type: 'GET',
            data: {
                quotation_id: this.quotationId
            },
            dataType: 'json',
            success: (response) => {
                //console.log('[ChatQuotation] Phase 2 data response:', response);

                if (response.status === 200 && response.data) {
                    // Initialize phase2 data structure
                    if (!this.quotationData.phase2) {
                        this.quotationData.phase2 = {};
                    }

                    // Handle hotel bookings
                    if (response.data.hotel_bookings && response.data.hotel_bookings.length > 0) {
                        // Add a small delay to ensure DOM is fully updated after hotel loading
                        setTimeout(() => {
                            this.populateSavedHotelBookings(response.data.hotel_bookings);
                        }, 200);

                        this.quotationData.phase2.hotel_data = {};
                        response.data.hotel_bookings.forEach(booking => {
                            this.quotationData.phase2.hotel_data[booking.day] = booking;
                        });

                        //console.log('[ChatQuotation] Hotel bookings loaded successfully');
                    }

                    // Handle activities
                    if (response.data.activities && Object.keys(response.data.activities).length > 0) {
                        this.quotationData.phase2.activities = response.data.activities;

                        // Load activities for each day
                        Object.entries(response.data.activities).forEach(([dayKey, dayData]) => {
                            const dayNumber = dayKey.replace('day_', '');
                            if (dayData.activities && Array.isArray(dayData.activities)) {
                                this.loadActivitiesForDay(dayNumber, dayData.activities);
                            }
                        });

                        //console.log('[ChatQuotation] Activities loaded successfully');
                    }

                    // Update summary after loading all data
                    this.updateQuotationSummary();

                    //console.log('[ChatQuotation] Phase 2 data loaded and populated successfully');
                } else {
                    //console.log('[ChatQuotation] No saved Phase 2 data found');
                }
            },
            error: (xhr, status, error) => {
                //console.error('[ChatQuotation] Failed to load Phase 2 data:', error);
            }
        });
    }

    populateSavedHotelBookings(hotelBookings) {
        //console.log('[ChatQuotation] populateSavedHotelBookings called with:', hotelBookings);

        hotelBookings.forEach(booking => {
            const dayItem = $(`.hotel-day-item[data-day="${booking.day}"][data-date="${booking.date}"]`);

            if (dayItem.length > 0) {
                //console.log(`[ChatQuotation] Populating day ${booking.day} with booking data:`, booking);

                // Populate hotel dropdown
                if (booking.hotel_id) {
                    const hotelSelect = dayItem.find('.hotel-select');
                    const availableOptions = hotelSelect.find('option').length;
                    //console.log(`[ChatQuotation] Hotel dropdown has ${availableOptions} options for day ${booking.day}`);

                    hotelSelect.val(booking.hotel_id);

                    // Check if the hotel was actually selected (option exists)
                    if (hotelSelect.val() === booking.hotel_id) {
                        //console.log(`[ChatQuotation] Hotel ${booking.hotel_id} selected successfully for day ${booking.day}`);
                        // Trigger change event to load room types
                        hotelSelect.trigger('change');
                    } else {
                        //console.warn(`[ChatQuotation] Hotel ${booking.hotel_id} not found in dropdown for day ${booking.day}. Available options:`, hotelSelect.find('option').map((i, opt) => opt.value).get());
                    }

                    // Set room type and meal plan after a short delay to allow room types to load
                    setTimeout(() => {
                        if (booking.room_type) {
                            const roomTypeSelect = dayItem.find('.room-type-select');
                            roomTypeSelect.val(booking.room_type);
                            roomTypeSelect.trigger('change');
                        }

                        if (booking.meal_plan) {
                            const mealPlanSelect = dayItem.find('.meal-plan-select');
                            mealPlanSelect.val(booking.meal_plan);
                            mealPlanSelect.trigger('change');
                        }

                        // Update status badge to show as saved
                        const statusBadge = dayItem.find('.hotel-status-badge .badge');
                        statusBadge.removeClass('bg-warning').addClass('bg-success').text('Saved');

                    }, 500);
                }
            } else {
                //console.warn(`[ChatQuotation] Could not find day item for day ${booking.day}, date ${booking.date}`);
            }
        });

        // Update the quotation summary after hotel bookings are populated
        this.updateQuotationSummary();
    }

    updateQuotationSummary() {
        const summaryContainer = document.querySelector('#quotation-summary');

        if (!summaryContainer) {
            return;
        }

        if (!this.quotationData || !this.quotationData.phase1) {
            summaryContainer.innerHTML = 'Fill in the form to see summary';
            return;
        }

        const phase1 = this.quotationData.phase1;
        const rawData = this.quotationData.raw_quotation || {};

        let summaryHTML = '<div class="quotation-summary-details">';

        // Basic tour information
        if (phase1.consignee) {
            summaryHTML += `<div class="summary-item"><strong>Tour:</strong> ${phase1.consignee}</div>`;
        }

        if (phase1.pass_count) {
            summaryHTML += `<div class="summary-item"><strong>Total Passengers:</strong> ${phase1.pass_count}</div>`;
        }

        if (phase1.start_date && phase1.end_date) {
            summaryHTML += `<div class="summary-item"><strong>Dates:</strong> ${phase1.start_date} to ${phase1.end_date}</div>`;
        }

        // Flight information
        if (phase1.arrival_flight || phase1.arrival_flight_time) {
            const arrivalInfo = [];
            if (phase1.arrival_flight) arrivalInfo.push(phase1.arrival_flight);
            if (phase1.arrival_flight_time) arrivalInfo.push(`at ${phase1.arrival_flight_time}`);
            summaryHTML += `<div class="summary-item"><strong>Arrival Flight:</strong> ${arrivalInfo.join(' ')}</div>`;
        }

        if (phase1.departure_flight || phase1.departure_flight_time) {
            const departureInfo = [];
            if (phase1.departure_flight) departureInfo.push(phase1.departure_flight);
            if (phase1.departure_flight_time) departureInfo.push(`at ${phase1.departure_flight_time}`);
            summaryHTML += `<div class="summary-item"><strong>Departure Flight:</strong> ${departureInfo.join(' ')}</div>`;
        }

        // Room configuration
        const rooms = [];
        if (phase1.sgl > 0) rooms.push(`${phase1.sgl} Single`);
        if (phase1.dbl > 0) rooms.push(`${phase1.dbl} Double`);
        if (phase1.tpl > 0) rooms.push(`${phase1.tpl} Triple`);
        if (phase1.dbl_twin > 0) rooms.push(`${phase1.dbl_twin} Twin`);

        if (rooms.length > 0) {
            summaryHTML += `<div class="summary-item"><strong>Rooms:</strong> ${rooms.join(', ')}</div>`;
        }

        // Vehicle information
        if (phase1.vehicle) {
            summaryHTML += `<div class="summary-item"><strong>Vehicle:</strong> ${phase1.vehicle}</div>`;
        }

        // Cost information if available
        if (rawData.total_room_cost && parseFloat(rawData.total_room_cost) > 0) {
            summaryHTML += `<div class="summary-item"><strong>Room Cost:</strong> $${parseFloat(rawData.total_room_cost).toFixed(2)}</div>`;
        }

        if (rawData.total_transport_cost && parseFloat(rawData.total_transport_cost) > 0) {
            summaryHTML += `<div class="summary-item"><strong>Transport Cost:</strong> $${parseFloat(rawData.total_transport_cost).toFixed(2)}</div>`;
        }

        // Hotel bookings information (Phase 2)
        if (this.quotationData.phase2 && this.quotationData.phase2.hotel_data) {
            const hotelBookings = this.quotationData.phase2.hotel_data;
            const bookingCount = Object.keys(hotelBookings).length;

            if (bookingCount > 0) {
                summaryHTML += '<div class="summary-section mt-3">';
                summaryHTML += '<div class="summary-item"><strong>Hotel Bookings:</strong></div>';

                Object.values(hotelBookings).forEach(booking => {
                    if (booking.hotel_name) {
                        const mealPlanText = this.getMealPlanText(booking.meal_plan);
                        const roomTypeText = this.getRoomTypeText(booking.room_type);
                        summaryHTML += `<div class="summary-sub-item" style="margin-left: 20px; color: #666; font-size: 0.9em;">
                            <strong>Day ${booking.day}:</strong> ${booking.hotel_name.trim()}<br>
                            <span style="margin-left: 10px; font-size: 0.85em; color: #888;">
                                Room: ${roomTypeText} | Meals: ${mealPlanText}
                            </span>
                        </div>`;
                    }
                });
                summaryHTML += '</div>';
            }
        }

        // Activities information (Phase 2)
        if (this.quotationData.phase2 && this.quotationData.phase2.activities) {
            const activities = this.quotationData.phase2.activities;
            const activitiesCount = Object.keys(activities).length;

            if (activitiesCount > 0) {
                summaryHTML += '<div class="summary-section mt-3">';
                summaryHTML += '<div class="summary-item"><strong>Daily Activities:</strong></div>';

                Object.entries(activities).forEach(([dayKey, dayData]) => {
                    if (dayData.activities && dayData.activities.length > 0) {
                        const dayNumber = dayKey.replace('day_', '');
                        summaryHTML += `<div class="summary-sub-item" style="margin-left: 20px; color: #666; font-size: 0.9em;">
                            <strong>Day ${dayNumber}:</strong><br>`;

                        dayData.activities.forEach(activity => {
                            const badges = [];
                            if (activity.ss) badges.push('<span class="badge bg-info">SS</span>');
                            if (activity.op) badges.push('<span class="badge bg-warning">OP</span>');

                            summaryHTML += `<span style="margin-left: 20px; font-size: 0.85em; color: #888;">
                                • ${activity.name} ${badges.join(' ')}<br></span>`;
                        });
                        summaryHTML += '</div>';
                    }
                });
                summaryHTML += '</div>';
            }
        }

        summaryHTML += '</div>';

        summaryContainer.innerHTML = summaryHTML;
    }

    getMealPlanText(mealCode) {
        const mealPlans = {
            'fb': 'Full Board',
            'hb': 'Half Board',
            'bb': 'Bed & Breakfast',
            'ro': 'Room Only'
        };
        return mealPlans[mealCode] || mealCode || 'Not specified';
    }

    getRoomTypeText(roomTypeId) {
        // Common room type mappings - you can expand this based on your data
        const roomTypes = {
            '1': 'Standard Room',
            '2': 'Deluxe Room',
            '3': 'Suite',
            '4': 'Family Room',
            '5': 'Superior Room',
            '6': 'Economy Room',
            '7': 'Premium Room',
            '8': 'Executive Room',
            '133': 'Standard Double Room'
        };
        return roomTypes[String(roomTypeId)] || `Room Type ${roomTypeId}` || 'Not specified';
    }

    startAutoSave() {
        // AUTO-SAVE DISABLED - Only manual save on button click
        // this.autoSaveInterval = setInterval(() => {
        //     this.saveCurrentPhase(false);
        // }, 30000);
    }

    stopAutoSave() {
        // AUTO-SAVE DISABLED
        // if (this.autoSaveInterval) {
        //     clearInterval(this.autoSaveInterval);
        //     this.autoSaveInterval = null;
        // }
    }

    previewQuotation() {
        if (!this.quotationId) return;

        // Open quotation preview in new tab
        window.open(`/tour/quotation/preview/${this.quotationId}`, '_blank');
    }

    updatePhaseHelp(phase) {
        const helpContent = document.getElementById('phase-help-content');
        if (!helpContent) return;

        let helpHTML = '';
        switch (phase) {
            case 1:
                helpHTML = `
                    <p class="small mb-2"><strong>Phase 1: Basic Information</strong></p>
                    <ul class="small">
                        <li>Enter tour dates and passenger count</li>
                        <li>Configure room requirements</li>
                        <li>Select tour type and vehicle</li>
                        <li>Add flight information if available</li>
                    </ul>
                `;
                break;
            case 2:
                helpHTML = `
                    <p class="small mb-2"><strong>Phase 2: Daily Timeline</strong></p>
                    <ul class="small">
                        <li>Plan day-by-day itinerary</li>
                        <li>Select hotels for each night</li>
                        <li>Add activities and attractions</li>
                        <li>Configure meal plans</li>
                    </ul>
                `;
                break;
            case 3:
                helpHTML = `
                    <p class="small mb-2"><strong>Phase 3: Review & Submit</strong></p>
                    <ul class="small">
                        <li>Review all quotation details</li>
                        <li>Verify pricing and costs</li>
                        <li>Add special requirements</li>
                        <li>Submit for internal review</li>
                    </ul>
                `;
                break;
        }
        helpContent.innerHTML = helpHTML;
    }

    // Activities Management Methods
    addActivity(day, activityName, ss, op) {
        if (!activityName.trim()) {
            this.showNotification('Please enter an activity name', 'error');
            return;
        }

        if (!this.quotationData.phase2) {
            this.quotationData.phase2 = {};
        }
        if (!this.quotationData.phase2.activities) {
            this.quotationData.phase2.activities = {};
        }

        const dayKey = `day_${day}`;
        if (!this.quotationData.phase2.activities[dayKey]) {
            this.quotationData.phase2.activities[dayKey] = {
                date: $(`.hotel-day-item[data-day="${day}"]`).data('date'),
                activities: []
            };
        }

        const activityId = Date.now(); // Simple ID generation
        const activity = {
            id: activityId,
            name: activityName.trim(),
            ss: ss || false,
            op: op || false
        };

        this.quotationData.phase2.activities[dayKey].activities.push(activity);
        this.renderActivitiesForDay(day);
        this.updateQuotationSummary();
    }

    removeActivity(day, activityId) {
        const dayKey = `day_${day}`;
        if (this.quotationData.phase2?.activities?.[dayKey]?.activities) {
            this.quotationData.phase2.activities[dayKey].activities =
                this.quotationData.phase2.activities[dayKey].activities.filter(
                    activity => activity.id !== activityId
                );
            this.renderActivitiesForDay(day);
            this.updateQuotationSummary();
        }
    }

    removeActivityDirectly(day, activityId, button) {
        const originalText = button.html();
        const dayKey = `day_${day}`;

        // Find activity for feedback
        const activity = this.quotationData.phase2?.activities?.[dayKey]?.activities?.find(
            act => act.id === activityId
        );
        const activityName = activity ? activity.name : 'activity';

        // Show loading state
        button.prop('disabled', true)
              .html('<i class="fas fa-spinner fa-spin"></i>');

        // Remove from database first
        this.removeActivityFromDB(day, activityId)
            .then(response => {
                if (response.status === 200) {
                    // Remove from local data
                    this.removeActivity(day, activityId);

                    // Show success feedback
                    this.showNotification(`Activity "${activityName}" removed successfully!`, 'success');
                } else {
                    throw new Error(response.message || 'Failed to remove activity');
                }
            })
            .catch(error => {
                // Reset button on error
                button.prop('disabled', false).html(originalText);
                this.showNotification(`Failed to remove activity: ${error.message}`, 'error');
            });
    }

    removeActivityFromDB(day, activityId) {
        return fetch(`${this.baseUrl}/chat/quotation/remove-single-activity`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                quotation_id: this.quotationId,
                day: day,
                activity_id: activityId
            })
        })
        .then(response => response.json());
    }

    renderActivitiesForDay(day) {
        const dayItem = $(`.hotel-day-item[data-day="${day}"]`);
        const activitiesList = dayItem.find('.activities-list');

        activitiesList.empty();

        const dayKey = `day_${day}`;
        const dayActivities = this.quotationData.phase2?.activities?.[dayKey]?.activities || [];

        // Update activity counter
        const activityCounter = dayItem.find('.activity-counter');
        if (activityCounter.length === 0) {
            const activitiesContainer = dayItem.find('.activities-container');
            activitiesContainer.prepend(`
                <div class="activity-counter mb-2">
                    <small class="text-muted">
                        <i class="fas fa-list"></i> Activities: <span class="activity-count">0</span>
                    </small>
                </div>
            `);
        }
        dayItem.find('.activity-count').text(dayActivities.length);

        dayActivities.forEach(activity => {
            const badges = [];
            if (activity.ss) badges.push('<span class="badge bg-info">SS</span>');
            if (activity.op) badges.push('<span class="badge bg-warning">OP</span>');

            // Add saved status badge
            badges.push('<span class="badge bg-success"><i class="fas fa-check"></i> Saved</span>');

            const activityHtml = `
                <div class="activity-item" data-activity-id="${activity.id}">
                    <div class="activity-name">${activity.name}</div>
                    <div class="activity-badges">${badges.join(' ')}</div>
                    <button type="button" class="btn btn-danger btn-sm remove-activity-btn"
                            data-day="${day}" data-activity-id="${activity.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            activitiesList.append(activityHtml);
        });

        // Show empty state if no activities
        if (dayActivities.length === 0) {
            activitiesList.append(`
                <div class="text-center text-muted py-2">
                    <i class="fas fa-plus-circle"></i> No activities added yet
                </div>
            `);
        }
    }

    loadActivitiesForDay(day, activities) {
        if (!activities || !Array.isArray(activities)) return;

        const dayKey = `day_${day}`;
        if (!this.quotationData.phase2) {
            this.quotationData.phase2 = {};
        }
        if (!this.quotationData.phase2.activities) {
            this.quotationData.phase2.activities = {};
        }

        this.quotationData.phase2.activities[dayKey] = {
            date: $(`.hotel-day-item[data-day="${day}"]`).data('date'),
            activities: activities
        };

        this.renderActivitiesForDay(day);
    }

    getActivitiesData() {
        return this.quotationData.phase2?.activities || {};
    }

    saveActivities(day, date) {
        const dayKey = `day_${day}`;
        const activities = this.quotationData.phase2?.activities?.[dayKey]?.activities || [];

        return $.ajax({
            url: `${this.baseUrl}/chat/quotation/save-activities`,
            type: 'POST',
            data: {
                quotation_id: this.quotationId,
                day: day,
                date: date,
                activities: activities
            },
            dataType: 'json'
        });
    }

    addActivityDirectly(day, date, activityName, ss, op, button, originalText) {
        // First add to local data for immediate UI update
        this.addActivity(day, activityName, ss, op);

        // Then immediately save to database
        this.saveActivityToDB(day, date, activityName, ss, op)
            .then(response => {
                if (response.status === 200) {
                    // Update local activity with the server-generated ID
                    const dayKey = `day_${day}`;
                    const activities = this.quotationData.phase2.activities[dayKey].activities;
                    const lastActivity = activities[activities.length - 1];
                    lastActivity.id = response.activity.id; // Use server-generated ID

                    // Re-render to show updated activity with correct ID
                    this.renderActivitiesForDay(day);

                    // Show success feedback
                    button.removeClass('btn-primary').addClass('btn-success')
                          .html('<i class="fas fa-check"></i> Added');

                    this.showNotification(`Activity "${activityName}" added successfully!`, 'success');

                    // Update summary to reflect new activity
                    this.updateQuotationSummary();

                    // Reset button after 2 seconds
                    setTimeout(() => {
                        button.removeClass('btn-success').addClass('btn-primary')
                              .prop('disabled', false).html(originalText);
                    }, 2000);
                } else {
                    throw new Error(response.message || 'Failed to save activity');
                }
            })
            .catch(error => {
                // Revert local changes on error
                this.removeActivityFromLocal(day, activityName);

                button.prop('disabled', false).html(originalText);
                this.showNotification(`Failed to add activity: ${error.message}`, 'error');
            });
    }

    saveActivityToDB(day, date, activityName, ss, op) {
        const payload = {
            quotation_id: this.quotationId,
            day: day,
            date: date,
            activity_name: activityName,
            ss: ss,
            op: op
        };

        // Debug: Log what we're sending
        console.log('saveActivityToDB - Sending payload:', payload);

        return fetch(`${this.baseUrl}/chat/quotation/add-single-activity`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json());
    }

    removeActivityFromLocal(day, activityName) {
        // Helper method to remove activity from local data if save fails
        const dayKey = `day_${day}`;
        if (this.quotationData.phase2?.activities?.[dayKey]?.activities) {
            this.quotationData.phase2.activities[dayKey].activities =
                this.quotationData.phase2.activities[dayKey].activities.filter(
                    activity => activity.name !== activityName
                );
            this.renderActivitiesForDay(day);
            this.updateQuotationSummary();
        }
    }


    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);

        // Update last saved time if this is a save notification
        if (type === 'success' && message.includes('saved')) {
            const lastSavedDiv = document.getElementById('last-saved-time');
            if (lastSavedDiv) {
                const timeSpan = lastSavedDiv.querySelector('span');
                const now = new Date();
                timeSpan.textContent = now.toLocaleTimeString();
                lastSavedDiv.style.display = 'block';
            }
        }
    }

    // Quotation Request Methods
    showQuotationRequestModal() {
        if (!this.conversationId) {
            this.showNotification('Please select a conversation first', 'warning');
            return;
        }

        // Clear form
        document.getElementById('quotation_request_text').value = '';
        document.getElementById('language_english').checked = true;
        document.getElementById('processing_status').classList.add('d-none');

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('quotationRequestModal'));
        modal.show();
    }

    async submitQuotationRequest() {
        const text = document.getElementById('quotation_request_text').value.trim();
        const language = document.querySelector('input[name="request_language"]:checked').value;

        if (!text) {
            this.showNotification('Please enter request details', 'warning');
            return;
        }

        if (!this.conversationId) {
            this.showNotification('No conversation selected', 'error');
            return;
        }

        try {
            // Show processing status
            document.getElementById('processing_status').classList.remove('d-none');
            const submitBtn = document.getElementById('submit-quotation-request');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            if (language === 'english') {
                // Direct insertion for English
                await this.insertRequestToChat(text, 'english');
            } else {
                // Process Japanese text through OpenAI
                await this.processJapaneseRequest(text);
            }

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('quotationRequestModal'));
            modal.hide();

            this.showNotification('Request added to chat successfully!', 'success');

        } catch (error) {
            console.error('Error submitting quotation request:', error);
            this.showNotification('Error processing request: ' + error.message, 'error');
        } finally {
            // Reset UI
            document.getElementById('processing_status').classList.add('d-none');
            const submitBtn = document.getElementById('submit-quotation-request');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
        }
    }

    async processJapaneseRequest(text) {
        try {
            const response = await fetch(`${this.baseUrl}/chat/process-japanese-request`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    text: text,
                    conversation_id: this.conversationId
                })
            });

            const result = await response.json();

            if (result.status === 200) {
                // Message already inserted by backend
                // Refresh conversation to show new message
                this.refreshConversation();
                return result.formatted_text;
            } else {
                throw new Error(result.message || 'Failed to process Japanese text');
            }
        } catch (error) {
            console.error('Error processing Japanese request:', error);
            throw error;
        }
    }

    async insertRequestToChat(text, language) {
        try {
            const response = await fetch(`${this.baseUrl}/chat/insert-request-message`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    conversation_id: this.conversationId,
                    message: text,
                    language: language
                })
            });

            const result = await response.json();

            if (result.status === 200) {
                // Refresh conversation to show new message
                this.refreshConversation();
                return true;
            } else {
                throw new Error(result.message || 'Failed to insert message');
            }
        } catch (error) {
            console.error('Error inserting request to chat:', error);
            throw error;
        }
    }

    refreshConversation() {
        // Trigger conversation refresh to show new messages
        if (window.loadConversation && this.conversationId) {
            window.loadConversation(this.conversationId);
        }
    }
}

// Global notification function for forms
window.showNotification = function(message, type = 'info') {
    const chatQuotation = window.chatQuotationInstance;
    if (chatQuotation) {
        chatQuotation.showNotification(message, type);
    }
};


document.addEventListener('DOMContentLoaded', function() {
    window.chatQuotationInstance = new ChatQuotation();
});

// CSS Styles for responsive layout
const style = document.createElement('style');
style.textContent = `
    /* Quotation Panel Styles */
    #quotation-panel {
        transition: all 0.3s ease;
        background: #f8f9fc;
        padding: 20px;
    }

    #quotation-panel .alert {
        background: transparent !important;
        border: none !important;
        padding: 15px !important;
    }

    #chat-main-container {
        transition: all 0.3s ease;
    }

    #quotation-form-container {
        background: white;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        min-height: 400px;
        margin-bottom: 15px;
    }

    .quotation-phase-form {
        max-height: calc(100vh - 350px);
        overflow-y: auto;
    }

    .progress-container .phase-indicator {
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.9rem;
    }

    .progress-container .phase-indicator:hover {
        transform: scale(1.05);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .phase-indicator.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Full-width quotation panel specific styles */
    #quotation-panel .row {
        margin-top: 0;
    }

    #quotation-panel .col-md-4 {
        padding-left: 20px;
    }

    #quotation-panel .card {
        border: 1px solid #e3e6f0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        margin-bottom: 15px;
    }

    #quotation-panel .card-header {
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
    }

    #quotation-panel .card-body {
        padding: 1rem;
    }

    #quotation-id-badge {
        font-size: 0.9rem;
        padding: 0.4rem 0.8rem;
    }

    /* Back to chat button hover effect */
    #back-to-chat:hover {
        transform: translateX(-3px);
        transition: transform 0.2s ease;
    }

    /* Progress bar improvements */
    .progress {
        background-color: #e9ecef;
    }

    .progress-bar {
        transition: width 0.6s ease;
    }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
        #quotation-panel .col-md-8 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 20px;
        }

        #quotation-panel .col-md-4 {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }

    @media (max-width: 768px) {
        #quotation-panel {
            padding: 10px;
        }

        .phase-indicator {
            font-size: 0.75rem !important;
            padding: 0.4rem !important;
        }

        #quotation-form-container {
            padding: 15px;
        }
    }

    /* Form styling */
    .quotation-phase-form .form-control-sm,
    .quotation-phase-form .form-select-sm {
        font-size: 0.875rem;
    }

    .quotation-phase-form .card {
        border: 1px solid #e3e6f0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .quotation-phase-form .card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 1px solid #dee2e6;
        font-weight: 600;
    }

    /* Animation for phase transitions */
    #quotation-form-container > * {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Summary section styling */
    #quotation-summary {
    
        overflow-y: auto;
    }

    #phase-help-content ul {
        padding-left: 20px;
        margin-bottom: 0;
    }

    #phase-help-content ul li {
        margin-bottom: 5px;
    }

    /* Last saved time indicator */
    #last-saved-time {
        animation: pulse 0.5s ease-in-out;
    }

    @keyframes pulse {
        0% { opacity: 0.6; }
        50% { opacity: 1; }
        100% { opacity: 0.6; }
    }
`;
document.head.appendChild(style);
</script>

<script>
// Reusable AJAX helper function
function makeAjaxCall(url, type = 'GET', data = null, options = {}) {
    const ajaxOptions = {
        url: url,
        type: type,
        success: options.success || function() {},
        error: options.error || function() {
            toastr.error('Internal Server Error. Please try again later.');
        }
    };

    if (data) {
        if (options.isFormData) {
            ajaxOptions.data = data;
            ajaxOptions.dataType = 'json';
            ajaxOptions.cache = false;
            ajaxOptions.contentType = false;
            ajaxOptions.processData = false;
        } else {
            ajaxOptions.data = data;
        }
    }

    if (options.beforeSend) ajaxOptions.beforeSend = options.beforeSend;
    if (options.complete) ajaxOptions.complete = options.complete;

    return $.ajax(ajaxOptions);
}

$(document).ready(function() {
    $('.js-example-basic-single').select2({
        placeholder: " Select"
    });

    var attachments = [];
    var attachment_names = [];

    $('#send').attr('disabled', true);
    $('#selected_files').html('No attachments.');

    $('#message').summernote({
        height: 150,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link']],
            ['view', []]
        ],
    });

    function get_formatted_time(time) {
        // 2024 Jan 5, 09:32 AM
        let date = new Date(time);

        // Check if date is invalid or epoch time (1970)
        if (isNaN(date.getTime()) || date.getFullYear() === 1970) {
            return ''; // Return empty string for invalid timestamps
        }

        let options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return date.toLocaleDateString('en-US', options);
    }

    function populate_agent_conversations(conversations) {

        $('#agent_new_conversations').html('');
        $('#agent_pending_conversations').html('');
        $('#agent_closed_conversations').html('');

        var new_conversations_count = 0;
        var pending_conversations_count = 0;
        var closed_conversations_count = 0;

        conversations.forEach(conversation => {
            let title = conversation.title;
            let agent = conversation.agent;
            let id = conversation.id;
            let last_message = conversation.last_message;
            let time = last_message ? get_formatted_time(last_message.time) : 'No messages yet.';
            // Add quotation status data to the card
            const quotationData = conversation.quotation_status_data || {
                has_active_quotation: false,
                quotation_id: null,
                current_phase: 1,
                quotation_status: 'none'
            };

            html = `
                <div class="card conversation-card mb-1" data-agent="${agent}" data-id="${id}"
                     data-quotation-status='${JSON.stringify(quotationData)}'>
                    <div class="card-body">
                        <div class="d-flex align-items-center w-100">
                            <div class="d-flex justify-content-center align-items-center bg-white border text-dark rounded-circle me-3" style="width: 50px; height: 40px;">
                                <span class="fw-bold">
                                    ${title.charAt(0).toUpperCase()}
                                </span>
                            </div>
                            <div class="d-flex flex-column w-100">
                                <div class="d-flex flex-column w-100">
                                    <div>
                                        <span class="fw-bold">
                                            ${title}
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        ${time}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            if (conversation.status == 1) {
                new_conversations_count++;
                $('#agent_new_conversations').append(html);
            } else if (conversation.status == 2) {
                pending_conversations_count++;
                $('#agent_pending_conversations').append(html);
            } else if (conversation.status == 3) {
                closed_conversations_count++;
                $('#agent_closed_conversations').append(html);
            }
        });

        // Handle empty states
        const emptyStates = [
            { count: new_conversations_count, element: '#agent_new_conversations', message: 'No new conversations.' },
            { count: pending_conversations_count, element: '#agent_pending_conversations', message: 'No pending conversations.' },
            { count: closed_conversations_count, element: '#agent_closed_conversations', message: 'No closed conversations.' }
        ];

        emptyStates.forEach(state => {
            if (state.count == 0) {
                $(state.element).html(`<div class="text-danger">${state.message}</div>`);
            }
        });
    }

    function get_agent_conversations() {
        var group_id = '<?= $group_id ?>';
        var agent = '<?= $agent ?>';
        if (group_id != 14) {
            agent = $('#agent_select').val();
        }
        makeAjaxCall('<?= base_url('chat/get_agent_conversation_list') ?>/' + agent, 'GET', null, {
            success: function(response) {
                populate_agent_conversations(response);
            }
        });
    }

    function get_attachment_div(attachments) {
        let attachment_html = '';
        attachments.forEach(attachment => {
            attachment_html += `
                <div class="d-flex justify-content-between align-items-center py-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-paperclip me-2"></i>
                        <small>${attachment.attachment_name}</small>
                    </div>
                    <div>
                        <a href="<?= base_url('chat/download/attachment/') ?>${attachment.id}" target="_blank" class="text-decoration-none">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                </div>
            `;
        });
        return attachment_html;
    }

    function populate_conversation_messages(conversation, messages) {

        $('#conversation_ul').html('');

        var group_id = '<?= $group_id ?>';
        let agent_name = conversation.agent_name;

        messages.forEach(chat => {
            let chat_html = '';
            let attachment_html = '';

            if (chat.attachments?.length > 0) {
                attachment_html = get_attachment_div(chat.attachments);
            }

            if (chat.message[0] != '<') {
                // Convert \n line breaks to <br> tags for proper display
                chat.message = chat.message.replace(/\n/g, '<br>');
                chat.message = `<p>${chat.message}</p>`;
            }

            // Determine alignment and sender based on group_id and from_agent
            let alignment, sender;
            if (group_id == 14) {
                alignment = chat.from_agent == 1 ? 'end' : 'start';
                sender = chat.from_agent == 1 ? agent_name : 'Avenir Holidays';
            } else {
                alignment = chat.from_agent == 1 ? 'start' : 'end';
                sender = chat.from_agent == 1 ? agent_name : 'Avenir Holidays';
            }

            chat_html = `
                <div class="d-flex justify-content-${alignment} mb-3">
                    <div class="card chat border pb-2">
                        <div class="card-header">
                            <small>${sender}</small>
                        </div>
                        <div class="card-body py-0 d-flex flex-column">
                            <p class="card-text" style="white-space: pre-line">${chat.message}</p>
                            ${attachment_html}
                        </div>
                    </div>
                </div>
            `;

            $('#conversation_ul').append(chat_html);
        });

        if (messages?.length == 0) {
            $('#conversation_ul').html('<div class="d-flex justify-content-center text-muted" style="margin-top: 50px;">No messages yet.</div>');
        } else {
            // $('#conversation_ul').animate({
            //     scrollTop: $('#conversation_ul').prop("scrollHeight")
            // }, 500);
        }

        // Trigger event to format messages after they're loaded
        $(document).trigger('conversationLoaded');
    }

    function get_conversation_with_messages(id, markRead = false) {
        var url = '<?= base_url('chat/get_conversation_with_messages') ?>/' + id;
        if (markRead) {
            url += '?mark_read=1';
        }
        
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                if (response.conversation && response.messages) {

                    var conversation = response.conversation;

                    $('#conversation_id').val(conversation.id);
                    $('#selected_agent').val(conversation.agent);
                    $('#selected_conversation_name').html(conversation.title);
                    $('#selected_conversation_agent_name').html(conversation.agent_name);

                    var status = conversation.status;
                    if (status == 1) {
                        $('#close_chat_div').addClass('d-none');
                        $('#tag_options_div').removeClass('d-none');
                        $('#tour_select').val('').trigger('change');
                        $('#inquiry_select').val('').trigger('change');
                    } else if (status == 2) {
                        $('#close_chat_div').removeClass('d-none');
                        $('#tag_options_div').removeClass('d-none');

                        var tagged_tour = conversation.tagged_tour;
                        var tagged_inquiry = conversation.tagged_inquiry;
                        
                        $('#tour_select').val(tagged_tour).trigger('change');
                        $('#inquiry_select').val(tagged_inquiry).trigger('change');

                        var desc = `${conversation.agent_name}`;
                        if (conversation.tour) {
                            desc += ` | Tour: ${conversation.tour.consignee} | ${conversation.tour.manual_id || 0}`;
                        }
                        if (conversation.inquiry) {
                            desc += ` | Inquiry: ${conversation.inquiry.inquiry_code} - ${conversation.inquiry.name}`;
                        }
                        $('#selected_conversation_agent_name').html(desc);
                    } else {
                        $('#close_chat_div').addClass('d-none');
                        $('#tag_options_div').addClass('d-none');
                    }

                    populate_conversation_messages(response.conversation, response.messages);
                } else {
                    toastr.error('No conversation found.');
                }
            },
            error: function() {
                toastr.error('Internal Server Error. Please try again later.');
            }
        });
    }

    $(document).ready(function() {

        // if url param has conversation & agent params then get them
        const urlParams = new URLSearchParams(window.location.search);
        const conversation_id = urlParams.get('conversation');
        const agent = urlParams.get('agent');

        var group_id = '<?= $group_id ?>';
        if (group_id != 14) {

            if (conversation_id != null && agent != null) {
                $('#agent_select').val(agent).trigger('change');
                // Only mark as read if mark_read=1 is explicitly in URL
                var markRead = urlParams.get('mark_read') === '1';
                get_conversation_with_messages(conversation_id, markRead);
            } else {
                $('#agent_select').val('0').trigger('change');
            }

        } else {
            get_agent_conversations();
        }

        // call get_agent_conversations every 5 seconds
        setInterval(function() {
            get_agent_conversations();
            var conversation_id = $('#conversation_id').val();
            // Fix: Check for undefined, null, and empty string to prevent 404 errors
            if (conversation_id && conversation_id !== '' && conversation_id !== 'undefined') {
                get_conversation_with_messages(conversation_id);
            }
        }, 5000);
    });


    // .save_new_conversation button click event
    $('.save_new_conversation').on('click', function() {

        let title = $('#title').val();
        var agent = $('#new_cnv_agent').val();

        if (title.trim() == '') {
            toastr.error('Please provide a title for the conversation.');
            return;
        }

        if (agent == '') {
            toastr.error('Please select an agent.');
            return;
        }

        var group_id = '<?= $group_id ?>';
        if (group_id == 14) {
            agent = '<?= $agent ?>'
        }

        $.ajax({
            url: '<?= base_url('chat/start_conversation') ?>',
            type: 'POST',
            data: {
                title: title,
                agent: agent
            },
            success: function(response) {
                if (response.status == 200) {
                    toastr.success(response.message);
                    $('#newChatModal').modal('hide');
                    get_agent_conversations();
                    get_conversation_with_messages(response.id, true);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('Internal Server Error. Please try again later.');
            }
        });
    });

    // .conversation-card click event
    $(document).on('click', '.conversation-card', function() {
        // Get conversation data from card
        let agent = $(this).data('agent');
        let id = $(this).data('id');
        let quotationStatus = $(this).data('quotation-status');

        // Validate that we have a conversation ID
        if (!id || id === undefined || id === null) {
            toastr.error('Invalid conversation selected. Please try again.');
            return;
        }

        let message = $('#message').val();
        if (message != '') {
            Swal.fire({
                title: 'Are you sure?',
                text: "You will lose the current message if you switch the conversation.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, switch it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#message').summernote('code', '');
                    get_conversation_with_messages(id, true);
                    attachments = [];
                    attachment_names = [];
                    populate_selected_attached();

                    // Pass quotation status to ChatQuotation instance for immediate button update
                    if (window.chatQuotationInstance && quotationStatus) {
                        window.chatQuotationInstance.setQuotationStatusFromCache(id, quotationStatus);
                    }
                }
            });
        } else {
            get_conversation_with_messages(id, true);
            attachments = [];
            attachment_names = [];
            populate_selected_attached();

            // Pass quotation status to ChatQuotation instance for immediate button update
            if (window.chatQuotationInstance && quotationStatus) {
                window.chatQuotationInstance.setQuotationStatusFromCache(id, quotationStatus);
            }
        }
    });

    // .agent_select change event
    $('#agent_select').on('change', function() {
        get_agent_conversations();
    });

    // .send click
    $('.send').click(function() {
        let conversation_id = $('#conversation_id').val();
        let agent = $('#selected_agent').val();
        let message = $('#message').val();

        if (message == '') {
            toastr.error('Please type a message.');
        }

        let formData = new FormData();
        formData.append('conversation_id', conversation_id);
        formData.append('agent', agent);
        formData.append('message', message);


        attachments.forEach(attachment => {
            formData.append('attachments[]', attachment);
        });

        // get attachment names by splitting with comma
        let attachment_names_str = attachment_names.join(',');
        formData.append('attachment_names', attachment_names_str);

        $.ajax({
            url: '<?= base_url('chat/send_message') ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $('#send').attr('disabled', true);
                $('#send').html('Sending...');
            },
            success: function(response) {
                if (response.status == 200) {
                    $('#message').summernote('code', '');
                    $('#send').attr('disabled', true);
                    $('#attachment_input').val('');
                    attachments = [];
                    attachment_names = [];
                    populate_selected_attached();
                    get_conversation_with_messages(conversation_id);
                } else {
                    toastr.error(response.message);
                }
            },
            complete: function() {
                $('#send').attr('disabled', false);
                $('#send').html('Send');
            }
        });
    });

    // .attach click
    $('#attach').click(function() {
        $('#attachment_input').click();
    });

    function get_file_size(file) {
        let size = file.size;
        let s = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        let e = Math.floor(Math.log(size) / Math.log(1024));
        return (size / Math.pow(1024, e)).toFixed(2) + " " + s[e];
    }

    function populate_selected_attached() {
        $('#selected_files').html('');
        var i = 0;
        attachments.forEach(attachment => {
            $('#selected_files').append(`
                <div class="d-flex justify-content-between align-items-center border px-2 py-1 m-1 rounded-2 bg-body-tertiary">
                    <div class="d-flex align-items-center me-2">
                        <i class="fas fa-paperclip me-2"></i>
                        <small>${attachment.name}</small>
                    </div>
                    <a href="javascript:void(0)">
                        <i class="fas fa-times text-danger remove-attachment" data-index="${i}"></i>
                    </a>
                </div>
            `);
            i++;
        });

        if (attachments?.length == 0) {
            $('#selected_files').html('No attachments.');
            $('.view_attached_btn').html('<i class="fas fa-eye me-2"></i> View Attached Files');
        } else {
            $('.view_attached_btn').html(`<i class="fas fa-eye me-2"></i> View Attached Files (${attachments.length})`);
        }
    }

    // attachment_input change
    $('#attachment_input').change(function() {
        let files = $(this)[0].files;
        if (files.length > 0) {
            for (let i = 0; i < files.length; i++) {
                var size = get_file_size(files[i]);

                // if files less than 100MB then add to attachments
                if (size.split(' ')[1] == 'MB' && parseFloat(size.split(' ')[0]) > 100) {
                    toastr.error('File size should be less than 100MB.');
                    return;
                }

                attachments.push(files[i]);
                attachment_names.push(files[i].name);

                populate_selected_attached();
            }
        }
    });

    // remove-attachment
    $(document).on('click', '.remove-attachment', function() {
        let index = $(this).data('index');
        attachments.splice(index, 1);
        attachment_names.splice(index, 1);
        populate_selected_attached();
    });

    $('.logout').click(function() {
        $.ajax({
            url: '<?= base_url('logout') ?>',
            type: 'GET',
            success: function(response) {
                if (response.status == 200) {
                    window.location.href = '<?= base_url() ?>';
                }
            }
        });
    });

    // #message summer note change
    $('#message').on('summernote.change', function() {
        let message = $('#message').val();
        if (message != '') {
            $('#send').attr('disabled', false);
        } else {
            $('#send').attr('disabled', true);
        }
    });

    // .new_cnv_modal_btn click
    $('.new_cnv_modal_btn').click(function() {
        $('#title').val('');
        var agent = $('#agent_select').val();
        $('#new_cnv_agent').val(agent).trigger('change');
    });

    // Generic tag handler for both tour and inquiry
    function handleTagging(type, selectId, url) {
        let conversation_id = $('#conversation_id').val();
        let selectedId = $(selectId).val();

        if (selectedId == '' || selectedId == undefined) {
            toastr.error(`Please select ${type === 'tour' ? 'a tour' : 'an inquiry'}.`);
            return;
        }

        makeAjaxCall(url + conversation_id + '/' + selectedId, 'POST', null, {
            success: function(response) {
                if (response.status == 200) {
                    toastr.success(response.message);
                    get_conversation_with_messages(conversation_id);
                } else {
                    toastr.error(response.message);
                }
            }
        });
    }

    // .tag_tour_btn click
    $(document).on('click', '.tag_tour_btn', function() {
        handleTagging('tour', '#tour_select', '<?= base_url('chat/tag_tour') ?>/');
    });

    // .tag_inquiry_btn click
    $(document).on('click', '.tag_inquiry_btn', function() {
        handleTagging('inquiry', '#inquiry_select', '<?= base_url('chat/tag_inquiry') ?>/');
    });

    // .close_chat_btn
    $(document).on('click', '.close_chat_btn', function() {
        // swal confirm
        let conversation_id = $('#conversation_id').val();
        Swal.fire({
            title: 'Are you sure?',
            text: "You want to close this chat?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, close it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= base_url('chat/close_chat') ?>/' + conversation_id,
                    type: 'POST',
                    success: function(response) {
                        if (response.status == 200) {
                            toastr.success(response.message);
                            get_agent_conversations();
                            get_conversation_with_messages(conversation_id);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        toastr.error('Internal Server Error. Please try again later.');
                    }
                });
            }
        });
    });
});
</script>

<script>
/**
 * Quotation Message Formatter
 * Formats the universal quotation format messages with proper structure and styling
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
                    if (node.nodeType === Node.ELEMENT_NODE && !node.classList.contains('quotation-formatted')) {
                        // Check if this node or its children contain quotation format
                        this.formatMessageElement(node);
                        node.querySelectorAll('*:not(.quotation-formatted)').forEach(child => {
                            this.formatMessageElement(child);
                        });
                    }
                });
            });
        });

        observer.observe(conversationContainer, {
            childList: true,
            subtree: false  // Changed to false to prevent infinite recursion
        });
    }

    formatMessageElement(element) {
        if (!element || !element.textContent) return;

        // Skip if already formatted
        if (element.classList.contains('quotation-formatted')) return;

        const text = element.textContent.trim();

        // Check if this is a quotation format message
        if (this.isQuotationFormat(text)) {
            const formattedHTML = this.formatQuotationText(text);

            // Replace the element's content with formatted HTML
            if (element.innerHTML !== formattedHTML) {
                element.innerHTML = formattedHTML;
                // Remove quotation-message class to prevent grey styling
                element.classList.remove('quotation-message');
                element.classList.add('quotation-formatted'); // Add flag to prevent reprocessing
                // Ensure text color is black like regular messages
                element.style.color = '#000';
                // Apply line break preservation for proper formatting
                element.style.whiteSpace = 'pre-line';
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
        // Handle cases where \n characters may have been lost during JSON transmission
        // First, try to convert existing newlines
        let formattedText = text.replace(/\n/g, '<br>');

        // If no newlines were found, try to insert breaks based on content patterns
        if (!formattedText.includes('<br>')) {
            // Insert breaks before key sections (Sinhala patterns)
            formattedText = formattedText
                .replace(/(පිරිස \(Pax\):)/g, '<br><br>$1')
                .replace(/(ගමන් දිනයන්:)/g, '<br><br>$1')
                .replace(/(ගුවන් ගමන්:)/g, '<br><br>$1')
                .replace(/(හෝටල් අවශ්‍යතා:)/g, '<br><br>$1')
                .replace(/(ආකර්ෂණ\/ක්‍රියාකාරකම්:)/g, '<br><br>$1')
                .replace(/(සම්බන්ධිකරණ:)/g, '<br><br>$1')
                .replace(/(අමතර සටහන්:)/g, '<br><br>$1')
                .replace(/(නම:)/g, '<br>$1')
                .replace(/(විද්‍යුත් තැපෑල:)/g, '<br>$1')
                .replace(/(ජංගම:)/g, '<br>$1')
                .replace(/• /g, '<br>• ')
                // Flight section specific breaks
                .replace(/(ආපසු පැමිණීම)/g, '<br>$1')
                .replace(/(පිටත්වීම:)/g, '$1')  // Keep departure on same line as title
                // Hotel section line breaks for multiple hotels
                .replace(/(# හෝටලය [A-Z]:)/g, '<br>$1')
                // Clean up any extra breaks at the start
                .replace(/^(<br>)+/, '');
        }

        // Also add breaks for * and ** list items
        formattedText = formattedText
            .replace(/\*\*/g, '<br>**')
            .replace(/(?<!\*)\*(?!\*)/g, '<br>*');

        return `<div style="color: #000;">${formattedText}</div>`;
    }
}

// Single instance of QuotationMessageFormatter to prevent multiple observers
let quotationFormatter;

// Initialize the formatter when the page loads
document.addEventListener('DOMContentLoaded', function() {
    if (!quotationFormatter) {
        quotationFormatter = new QuotationMessageFormatter();
    }
});

// Also initialize when messages are loaded dynamically
$(document).ready(function() {
    // Re-run formatter when conversations are loaded
    $(document).on('conversationLoaded', function() {
        setTimeout(() => {
            if (quotationFormatter) {
                // Re-format existing messages instead of creating new instance
                quotationFormatter.formatExistingMessages();
            } else {
                quotationFormatter = new QuotationMessageFormatter();
            }
        }, 500);
    });
});
</script>

</body>

</html>