/**
 * ChatHelpers - Complete extracted functionality from agent_chat.php
 * Handles all chat interface interactions and AJAX operations
 */

// Reusable AJAX helper function (global scope)
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
    // Initialize Select2
    $('.js-example-basic-single').select2({
        placeholder: " Select"
    });

    // Local variables for attachments
    var attachments = [];
    var attachment_names = [];

    // Initialize message sending as disabled
    $('#send').attr('disabled', true);
    $('#selected_files').html('No attachments.');

    // Initialize Summernote
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
        var group_id = document.body.dataset.groupId;
        var agent = document.body.dataset.agent;
        if (group_id != 14) {
            agent = $('#agent_select').val();
        }

        // Get base URL dynamically
        const path = window.location.pathname;
        const chatIndex = path.indexOf('/chat');
        const baseUrl = chatIndex !== -1 ? path.substring(0, chatIndex) : '/avenir';

        makeAjaxCall(`${baseUrl}/chat/get_agent_conversation_list/${agent}`, 'GET', null, {
            success: function(response) {
                populate_agent_conversations(response);
            }
        });
    }

    function get_attachment_div(attachments) {
        let attachment_html = '';
        const path = window.location.pathname;
        const chatIndex = path.indexOf('/chat');
        const baseUrl = chatIndex !== -1 ? path.substring(0, chatIndex) : '/avenir';

        attachments.forEach(attachment => {
            attachment_html += `
                <div class="d-flex justify-content-between align-items-center py-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-paperclip me-2"></i>
                        <small>${attachment.attachment_name}</small>
                    </div>
                    <div>
                        <a href="${baseUrl}/chat/download/attachment/${attachment.id}" target="_blank" class="text-decoration-none">
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

        var group_id = document.body.dataset.groupId;
        let agent_name = conversation.agent_name;

        messages.forEach(chat => {
            let chat_html = '';
            let attachment_html = '';

            if (chat.attachments?.length > 0) {
                attachment_html = get_attachment_div(chat.attachments);
            }

            if (chat.message[0] != '<') {
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
                            <div class="d-flex justify-content-between">
                                <small>${sender}</small>
                                <small>${get_formatted_time(chat.time)}</small>
                            </div>
                        </div>
                        <div class="card-body py-0 d-flex flex-column">
                            <p class="card-text">${chat.message}</p>
                            ${attachment_html}
                        </div>
                    </div>
                </div>
            `;

            $('#conversation_ul').append(chat_html);
        });

        if (messages?.length == 0) {
            $('#conversation_ul').html('<div class="d-flex justify-content-center text-muted" style="margin-top: 50px;">No messages yet.</div>');
        }
    }

    function get_conversation_with_messages(id, markRead = false) {
        const path = window.location.pathname;
        const chatIndex = path.indexOf('/chat');
        const baseUrl = chatIndex !== -1 ? path.substring(0, chatIndex) : '/avenir';

        var url = `${baseUrl}/chat/get_conversation_with_messages/${id}`;
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

                    // Trigger event for other components to know conversation was loaded
                    $(document).trigger('conversationLoaded');
                } else {
                    toastr.error('No conversation found.');
                }
            },
            error: function() {
                toastr.error('Internal Server Error. Please try again later.');
            }
        });
    }

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

    // Check for URL parameters and load conversation
    const urlParams = new URLSearchParams(window.location.search);
    const conversation_id = urlParams.get('conversation');
    const agent = urlParams.get('agent');

    var group_id = document.body.dataset.groupId;
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

    // Call get_agent_conversations every 5 seconds
    setInterval(function() {
        get_agent_conversations();
        var conversation_id = $('#conversation_id').val();
        if (conversation_id != '') {
            get_conversation_with_messages(conversation_id);
        }
    }, 5000);

    // Save new conversation button click event
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

        var group_id = document.body.dataset.groupId;
        if (group_id == 14) {
            agent = document.body.dataset.agent;
        }

        const path = window.location.pathname;
        const chatIndex = path.indexOf('/chat');
        const baseUrl = chatIndex !== -1 ? path.substring(0, chatIndex) : '/avenir';

        $.ajax({
            url: `${baseUrl}/chat/start_conversation`,
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

    // Conversation card click event
    $(document).on('click', '.conversation-card', function() {
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
                    let agent = $(this).data('agent');
                    let id = $(this).data('id');
                    let quotationStatus = $(this).data('quotation-status');
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
            let agent = $(this).data('agent');
            let id = $(this).data('id');
            let quotationStatus = $(this).data('quotation-status');
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

    // Agent select change event
    $('#agent_select').on('change', function() {
        get_agent_conversations();
    });

    // Send button click
    $('.send').click(function() {
        let conversation_id = $('#conversation_id').val();
        let agent = $('#selected_agent').val();
        let message = $('#message').val();

        if (message == '') {
            toastr.error('Please type a message.');
            return;
        }

        let formData = new FormData();
        formData.append('conversation_id', conversation_id);
        formData.append('agent', agent);
        formData.append('message', message);

        attachments.forEach(attachment => {
            formData.append('attachments[]', attachment);
        });

        // Get attachment names by splitting with comma
        let attachment_names_str = attachment_names.join(',');
        formData.append('attachment_names', attachment_names_str);

        const path = window.location.pathname;
        const chatIndex = path.indexOf('/chat');
        const baseUrl = chatIndex !== -1 ? path.substring(0, chatIndex) : '/avenir';

        $.ajax({
            url: `${baseUrl}/chat/send_message`,
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

    // Attach button click
    $('#attach').click(function() {
        $('#attachment_input').click();
    });

    // Attachment input change
    $('#attachment_input').change(function() {
        let files = $(this)[0].files;
        if (files.length > 0) {
            for (let i = 0; i < files.length; i++) {
                var size = get_file_size(files[i]);

                // If files less than 100MB then add to attachments
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

    // Remove attachment
    $(document).on('click', '.remove-attachment', function() {
        let index = $(this).data('index');
        attachments.splice(index, 1);
        attachment_names.splice(index, 1);
        populate_selected_attached();
    });

    // Logout click
    $('.logout').click(function() {
        const path = window.location.pathname;
        const chatIndex = path.indexOf('/chat');
        const baseUrl = chatIndex !== -1 ? path.substring(0, chatIndex) : '/avenir';

        $.ajax({
            url: `${baseUrl}/logout`,
            type: 'GET',
            success: function(response) {
                if (response.status == 200) {
                    window.location.href = baseUrl;
                }
            }
        });
    });

    // Message summernote change
    $('#message').on('summernote.change', function() {
        let message = $('#message').val();
        if (message != '') {
            $('#send').attr('disabled', false);
        } else {
            $('#send').attr('disabled', true);
        }
    });

    // New conversation modal button click
    $('.new_cnv_modal_btn').click(function() {
        $('#title').val('');
        var agent = $('#agent_select').val();
        $('#new_cnv_agent').val(agent).trigger('change');
    });

    // Tag tour button click
    $(document).on('click', '.tag_tour_btn', function() {
        const path = window.location.pathname;
        const chatIndex = path.indexOf('/chat');
        const baseUrl = chatIndex !== -1 ? path.substring(0, chatIndex) : '/avenir';
        handleTagging('tour', '#tour_select', `${baseUrl}/chat/tag_tour/`);
    });

    // Tag inquiry button click
    $(document).on('click', '.tag_inquiry_btn', function() {
        const path = window.location.pathname;
        const chatIndex = path.indexOf('/chat');
        const baseUrl = chatIndex !== -1 ? path.substring(0, chatIndex) : '/avenir';
        handleTagging('inquiry', '#inquiry_select', `${baseUrl}/chat/tag_inquiry/`);
    });

    // Close chat button
    $(document).on('click', '.close_chat_btn', function() {
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
                const path = window.location.pathname;
                const chatIndex = path.indexOf('/chat');
                const baseUrl = chatIndex !== -1 ? path.substring(0, chatIndex) : '/avenir';

                $.ajax({
                    url: `${baseUrl}/chat/close_chat/${conversation_id}`,
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

    // Export functions for global access
    window.ChatHelpers = {
        get_agent_conversations: get_agent_conversations,
        get_conversation_with_messages: get_conversation_with_messages,
        populate_agent_conversations: populate_agent_conversations,
        populate_conversation_messages: populate_conversation_messages,
        makeAjaxCall: makeAjaxCall,
        loadConversationMessages: get_conversation_with_messages
    };
});