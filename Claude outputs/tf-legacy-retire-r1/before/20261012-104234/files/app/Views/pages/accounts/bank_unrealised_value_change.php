<?= view('layouts/head') ?>

<body>

  <!-- Begin page -->
  <div id="layout-wrapper">

    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>

    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    <div class="main-content">

      <div class="page-content">
        <div class="container-fluid">

          <div class="row">
            <div class="col">

              <div class="h-100">
                <div class="row">
                  <div class="col-md-10 d-flex">
                    <h4 class="p-title">Bank Unrealised Value Change</h4>
                  </div>
                </div>

                <div class="row clearfix mt-3">
                  <div class="col-md-12">
                    <div class="card p-4">
                      <div class="body">
                        <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                          <div class="row">

                            <div class="col-md-6">
                              <div class="mb-3">
                                <label class="form-label" for="bank">Bank Account</label>
                                <select class="form-control txt_nav" id="bank" name="bank" required>
                                  <option value="">Select Bank</option>
                                  <?php
                                  if (!empty($load_bank)) {
                                    foreach ($load_bank as $value) {
                                      echo '<option value="' . $value['L_Code'] . '">' . $value['PName'] . '</option>';
                                    }
                                  }
                                  ?>
                                </select>
                              </div>
                            </div>

                            <div class="col-md-12">
                              <button type="submit" class="btn btn-success btn-sm" id="process_btn">
                                <i class="fas fa-sync"></i> Process Unrealised Value Change
                              </button>
                            </div>

                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Results Section -->
                <div class="row clearfix mt-3" id="results_section" style="display:none;">
                  <div class="col-md-12">
                    <div class="card p-4">
                      <div class="card-header">
                        <h5>Processing Results</h5>
                      </div>
                      <div class="card-body">
                        <div id="result_message" class="alert"></div>
                        <div id="result_details"></div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

        </div>
      </div>

      <?= view('layouts/brand') ?>
    </div>
    <!-- end main content-->

  </div>
  <!-- END layout-wrapper -->

  <?= view('layouts/inpage_footer') ?>
  <?= view('layouts/theme_settings') ?>
  <?= view('layouts/scripts') ?>

  <script>
    $(document).ready(function() {
      $('#form_submit').on('submit', function(e) {
        e.preventDefault();

        var bank = $('#bank').val();

        if (!bank) {
          Swal.fire({
            icon: 'warning',
            title: 'Warning',
            text: 'Please select a bank account'
          });
          return;
        }

        // Show loading
        $('#process_btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        $.ajax({
          url: '<?= base_url('accounts/bank_unrealised_value/process') ?>',
          type: 'POST',
          data: {
            bank: bank
          },
          dataType: 'json',
          success: function(response) {
            $('#process_btn').prop('disabled', false).html('<i class="fas fa-sync"></i> Process Unrealised Value Change');

            if (response.status === 200) {
              // Show success message
              $('#result_message').removeClass('alert-danger').addClass('alert-success').text(response.message);

              // Build results table
              var html = '<div class="table-responsive mt-3">';
              html += '<table class="table table-bordered table-hover">';
              html += '<thead class="table-light">';
              html += '<tr>';
              html += '<th>ID</th>';
              html += '<th>Bank L_Code</th>';
              html += '<th>Bank Dr</th>';
              html += '<th>Bank Cr</th>';
              html += '<th>Supplier L_Code</th>';
              html += '<th>Supplier Dr</th>';
              html += '<th>Supplier Cr</th>';
              html += '<th>Status</th>';
              html += '</tr>';
              html += '</thead>';
              html += '<tbody>';

              if (response.records && response.records.length > 0) {
                response.records.forEach(function(record) {
                  html += '<tr>';
                  html += '<td>' + record.ID + '</td>';
                  html += '<td>' + record.bank_L_Code + '</td>';
                  html += '<td>' + record.bank_Dr + '</td>';
                  html += '<td>' + record.bank_Cr + '</td>';
                  html += '<td>' + record.sup_L_Code + '</td>';
                  html += '<td>' + record.sup_Dr + '</td>';
                  html += '<td>' + record.sup_Cr + '</td>';
                  html += '<td><span class="badge bg-success">Processed</span></td>';
                  html += '</tr>';
                });
              }

              html += '</tbody>';
              html += '</table>';
              html += '</div>';

              // Show errors if any
              if (response.errors && response.errors.length > 0) {
                html += '<div class="alert alert-warning mt-3">';
                html += '<strong>Errors:</strong><ul>';
                response.errors.forEach(function(error) {
                  html += '<li>ID ' + error.id + ': ' + error.error + '</li>';
                });
                html += '</ul></div>';
              }

              $('#result_details').html(html);
              $('#results_section').show();

              Swal.fire({
                icon: 'success',
                title: 'Success',
                text: response.message
              });

            } else {
              $('#result_message').removeClass('alert-success').addClass('alert-danger').text(response.message);
              $('#result_details').html('');
              $('#results_section').show();

              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.message
              });
            }
          },
          error: function(xhr, status, error) {
            $('#process_btn').prop('disabled', false).html('<i class="fas fa-sync"></i> Process Unrealised Value Change');

            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'An error occurred while processing the request'
            });
          }
        });
      });
    });
  </script>

</body>

</html>
