<?= view('chat/layouts/header') ?>
<?= view('chat/layouts/navbar') ?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,100..900;1,100..900&display=swap');

    :root {
        --primary-color: #580ab8;
        --primary-hover-color: #6a0ddb;
        --secondary-color: #6c757d;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        line-height: 1.5;
        color: #333;
        /* background-image: linear-gradient(180deg, rgba(245, 245, 255, 0.8) 0%, rgba(250, 250, 255, 0.8) 100%); */
        background-color: #fff;
    }

    .btn {
        font-size: 13px;
        font-weight: 500;
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: #fff;
    }

    .text-primary {
        color: var(--primary-color) !important;
    }

    .btn-primary:hover {
        background-color: var(--primary-hover-color);
        border-color: var(--primary-hover-color);
    }

    .btn-primary:active {
        background-color: var(--primary-hover-color) !important;
        border-color: var(--primary-hover-color) !important;
    }

    .btn-primary:focus-visible {
        background-color: var(--primary-hover-color) !important;
        border-color: var(--primary-hover-color) !important;
    }

    .btn-primary:disabled {
        background-color: var(--primary-hover-color) !important;
        border-color: var(--primary-hover-color) !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #f4ecf7 !important;
        color: #3c3c3c;
    }

    .form-control {
        font-size: 14px;
        font-weight: 500;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.05rem var(--primary-color);
    }

    .b-link {
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .b-link:hover {
        color: var(--primary-color) !important;
        text-decoration: none;
    }

    /* Pink Purple Blue Gradient Text */
    .g-txt {
        background: linear-gradient(90deg, #ff6a00, #ee0979, #9d00ff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .card {
        border: 1px solid #eeeeee;
        border-radius: 10px;
        background-color: #f4f4f8;
    }

    h6,
    h5,
    h4,
    h3,
    h2,
    h1,
    span,
    p {
        font-weight: 350;
    }

    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    ::-webkit-scrollbar-button {
        width: 0px;
        height: 0px;
    }

    ::-webkit-scrollbar-thumb {
        background: #999999;
        border: 0px none #ffffff;
        border-radius: 50px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #999999;
    }

    ::-webkit-scrollbar-thumb:active {
        background: #999999;
    }

    ::-webkit-scrollbar-track {
        background: #ffffff;
        border: 0px none #ffffff;
        border-radius: 60px;
    }

    ::-webkit-scrollbar-track:hover {
        background: #ffffff;
    }

    ::-webkit-scrollbar-track:active {
        background: #ffffff;
    }

    ::-webkit-scrollbar-corner {
        background: transparent;
    }

    /* ===================================== */

    .conversation-card {
        background-color: #fff;
        cursor: pointer;
        transition: all 0.3s;
    }

    .conversation-card:hover {
        background-color: #f2f2f4;
        border: 1px solid #e8e8e2;
    }

    #conversation_ul {
        overflow-y: auto;
        height: calc(100vh - 480px);
    }

    .chat {
        width: 70%;
        border-radius: 10px;
        background-color: #fff;
        border: 1px solid #e8e8e2;
    }
</style>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="alert alert-light bg-body-tertiary" style="height: calc(100vh - 120px);">
                <div class="d-flex flex-column">
                    <h5 class="fw-bold text-black mb-3">Conversations</h5>
                    <?php
                    if ($group_id != 14) {
                        foreach ($customers as $customer) {
                            $name = $customer['Name'];
                            $first_letter = strtoupper(substr($name, 0, 1));
                    ?>
                            <div class="card conversation-card mb-1" data-agent="<?= $customer['Sup_Acc_No'] ?>" data-from-agent="0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center w-100">
                                        <div class="d-flex justify-content-center align-items-center bg-white border text-dark rounded-circle me-3" style="width: 50px; height: 40px;">
                                            <span class="fw-bold">
                                                <?= strtoupper($first_letter) ?>
                                            </span>
                                        </div>
                                        <div class="d-flex flex-column w-100">
                                            <div class="d-flex flex-column w-100">
                                                <div>
                                                    <span class="fw-bold">
                                                        <?= $name ?>
                                                    </span>
                                                </div>
                                                <small class="text-muted">
                                                    <?php
                                                    if (isset($customer['last_message_time'])) {
                                                        echo date('Y M d, h:i A', strtotime($customer['last_message_time']));
                                                    } else {
                                                        echo 'No messages yet.';
                                                    }
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                        <!-- <div class="d-flex justify-content-between">
                                            <span class="badge text-bg-primary">0</span>
                                        </div> -->
                                    </div>
                                </div>
                            </div>
                        <?php
                        }
                    } else { ?>
                        <div class="card conversation-card mb-1" data-agent="<?= $agent ?>" data-from-agent="1">
                            <div class="card-body">
                                <div class="d-flex align-items-center w-100">
                                    <div class="d-flex justify-content-center align-items-center bg-white border text-dark rounded-circle me-3" style="width: 50px; height: 40px;">
                                        <span class="fw-bold">
                                            A
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column w-100">
                                        <div class="d-flex flex-column w-100">
                                            <div>
                                                <span class="fw-bold">
                                                    Avenir Holidays
                                                </span>
                                            </div>
                                            <small class="text-muted">
                                                <?php
                                                if (isset($last_message_time)) {
                                                    echo date('Y M d, h:i A', strtotime($last_message_time));
                                                } else {
                                                    echo 'No messages yet.';
                                                }
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                    <!-- <div class="d-flex justify-content-between">
                                        <span class="badge text-bg-primary">0</span>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="alert alert-light bg-body-tertiary" style="height: calc(100vh - 120px);">
                <div class="d-flex flex-column h-100">
                    <div class="d-flex flex-column">
                        <input type="hidden" id="selected_agent">
                        <h5 class="fw-bold text-black" id="selected_agent_name">
                            Start a conversation
                        </h5>
                        <hr style="margin-top: 5px;">
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
                                <button class="btn btn-primary px-4 me-3 send" id="send">Send</button>
                                <button class="btn btn-light me-3" id="attach">
                                    <i class="fas fa-paperclip me-2"></i> Attach Files
                                </button>
                                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#attachmentModal">
                                    <i class="fas fa-eye me-2"></i> View Attached Files
                                </button>
                            </div>
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

<?= view('chat/layouts/footer') ?>
<script>
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
        let options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return date.toLocaleDateString('en-US', options);
    }

    function get_attachment_div(attachments) {
        let attachment_html = '';
        attachments.forEach(attachment => {
            attachment_html += `
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-paperclip me-2"></i>
                        <span>${attachment.attachment_name}</span>
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

    function populate_chats(chats, agent_name, agent, group_id) {
        $('#conversation_ul').html('');
        chats.forEach(chat => {

            let chat_html = '';
            let attachment_html = '';

            if (chat.attachments?.length > 0) {
                attachment_html = get_attachment_div(chat.attachments);
            }

            if (chat.message[0] != '<') {
                chat.message = `<p>${chat.message}</p>`;
            }

            if (group_id == 14) {
                if (chat.from_agent == 1) {
                    chat_html = `
                                <div class="d-flex justify-content-end mb-3">
                                    <div class="card chat">
                                        <div class="card-header">
                                            <div class="d-flex justify-content-between">
                                                <small>${agent_name}</small>
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
                } else {
                    chat_html = `
                                <div class="d-flex justify-content-start mb-3">
                                    <div class="card chat">
                                        <div class="card-header">
                                            <div class="d-flex justify-content-between">
                                                <small>Avenir Holidays</small>
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
                }
            } else {
                if (chat.from_agent == 1) {
                    chat_html = `
                                <div class="d-flex justify-content-start mb-3">
                                    <div class="card chat">
                                        <div class="card-header">
                                            <div class="d-flex justify-content-between">
                                                <small class="me-3">${agent_name}</small>
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
                } else {
                    chat_html = `
                                <div class="d-flex justify-content-end mb-3">
                                    <div class="card chat">
                                        <div class="card-header">
                                            <div class="d-flex justify-content-between">
                                                <small>Avenir Holidays</small>
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
                }
            }
            $('#conversation_ul').append(chat_html);
        });
    }

    function get_conversation(agent) {

        if (agent == '') {
            return;
        }

        var group_id = '<?= $group_id ?>';

        $.ajax({
            url: '<?= base_url('chat/get_conversation') ?>',
            type: 'GET',
            data: {
                agent: agent
            },
            dataType: 'json',
            success: function(response) {
                if (response.agent) {

                    $('#selected_agent').val(agent);

                    var agent_name = group_id != 14 ? response.agent.Name : 'Avenir Holidays';
                    $('#selected_agent_name').text(agent_name);

                    if (response.chats.length > 0) {
                        populate_chats(response.chats, agent_name, agent, group_id);
                    } else {
                        $('#conversation_ul').html(`
                            <div class="d-flex justify-content-center pt-5 w-100">
                                <span class="text-muted"> Type a message to start a conversation. </span>
                            </div>
                        `);
                    }
                }
            }
        });
    }

    //.conversation-card click
    $('.conversation-card').click(function() {
        let agent = $(this).data('agent');
        $('#selected_agent').val(agent);
        get_conversation(agent);
    });

    // .send click
    $('.send').click(function() {
        let agent = $('#selected_agent').val();
        let message = $('#message').val();

        if (message == '') {
            return;
        }

        let formData = new FormData();
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
                    $('#selected_files').html('No attachments.');
                    attachments = [];
                    attachment_names = [];
                    get_conversation(agent);
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

    // get conversation on page load in every 5 seconds
    $(document).ready(function() {
        let _agent = '<?= $agent ?>';
        let _group_id = '<?= $group_id ?>';

        if (_group_id != 14) {
            _agent = $('#selected_agent').val();
        }

        get_conversation(_agent);
        setInterval(function() {
            _agent = '<?= $agent ?>';
            group_id = '<?= $group_id ?>';

            if (_group_id != 14) {
                _agent = $('#selected_agent').val();
            }

            get_conversation(_agent);
        }, 5000);
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
</script>

</body>

</html>