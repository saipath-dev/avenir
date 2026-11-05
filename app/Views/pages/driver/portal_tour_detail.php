<?= view('layouts/head') ?>

<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                <div class="row mb-3">
                    <div class="col">
                        <a href="<?= base_url('driver_portal') ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <!-- Tour Header -->
                <div class="card shadow-sm border-0 rounded mb-4" style="background: linear-gradient(135deg,#667eea,#764ba2); color:white;">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <small>Manual ID</small>
                                <h5 class="fw-bold"><?= esc($tour['manual_id']) ?></h5>
                            </div>
                            <div class="col-md-3">
                                <small>Guest</small>
                                <h5 class="fw-bold"><?= esc($tour['consignee']) ?></h5>
                            </div>
                            <div class="col-md-2">
                                <small>Start Date</small>
                                <h5 class="fw-bold"><?= date('d M Y', strtotime($tour['start_date'])) ?></h5>
                            </div>
                            <div class="col-md-2">
                                <small>End Date</small>
                                <h5 class="fw-bold"><?= date('d M Y', strtotime($tour['end_date'])) ?></h5>
                            </div>
                            <div class="col-md-2">
                                <small>Vehicle</small>
                                <h5 class="fw-bold"><?= esc($tour['vehicle_name'] ?? '-') ?></h5>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Readings Table -->
                <div class="card shadow-lg border-0 rounded">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-speedometer2"></i> Daily Meter Readings</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Start KM</th>
                                        <th>End KM</th>
                                        <th>Distance</th>
                                        <th>Notes</th>
                                        <th>Photo</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dates as $date): ?>
                                        <?php $r = $readings[$date] ?? null; ?>
                                        <tr>
                                            <td><strong><?= date('d M Y', strtotime($date)) ?></strong></td>
                                            <td><?= $r ? number_format($r['start_km'], 1) : '<span class="text-muted">-</span>' ?></td>
                                            <td><?= $r ? number_format($r['end_km'], 1) : '<span class="text-muted">-</span>' ?></td>
                                            <td>
                                                <?php if ($r && $r['end_km'] && $r['start_km']): ?>
                                                    <span class="badge bg-info"><?= number_format($r['end_km'] - $r['start_km'], 1) ?> km</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $r ? esc($r['notes']) : '<span class="text-muted">-</span>' ?></td>
                                            <td>
                                                <?php if ($r && $r['photo']): ?>
                                                    <a href="<?= base_url('uploads/meter_readings/' . $tour['tour_id'] . '/' . $r['photo']) ?>" target="_blank">
                                                        <img src="<?= base_url('uploads/meter_readings/' . $tour['tour_id'] . '/' . $r['photo']) ?>" style="max-width:60px; max-height:60px; border-radius:4px;">
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-warning btn-sm add-reading-btn"
                                                    data-date="<?= $date ?>"
                                                    data-tour="<?= $tour['tour_id'] ?>"
                                                    data-start="<?= $r['start_km'] ?? '' ?>"
                                                    data-end="<?= $r['end_km'] ?? '' ?>"
                                                    data-notes="<?= esc($r['notes'] ?? '') ?>">
                                                    <i class="bi bi-pencil"></i> <?= $r ? 'Edit' : 'Add' ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <?= view('layouts/brand') ?>
    </div>
</div>

<!-- Add Reading Modal -->
<div class="modal fade" id="addReadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-speedometer2"></i> Meter Reading — <span id="modal_date_label"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="mr_tour_id" value="<?= $tour['tour_id'] ?>">
                <input type="hidden" id="mr_date">

                <div class="mb-3">
                    <label class="form-label">Start KM <font color="#FF0000"><strong>*</strong></font></label>
                    <input type="number" step="0.1" class="form-control" id="mr_start_km" placeholder="e.g. 12345.0" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">End KM <font color="#FF0000"><strong>*</strong></font></label>
                    <input type="number" step="0.1" class="form-control" id="mr_end_km" placeholder="e.g. 12567.0" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Odometer Photo</label>
                    <input type="file" class="form-control" id="mr_photo" accept="image/*">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" id="mr_notes" rows="2" placeholder="Optional remarks"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="save_reading_btn">
                    <i class="bi bi-check-lg"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/scripts') ?>

<script>
    $(document).on('click', '.add-reading-btn', function () {
        const date    = $(this).data('date');
        const tour    = $(this).data('tour');
        const start   = $(this).data('start');
        const end     = $(this).data('end');
        const notes   = $(this).data('notes');

        $('#mr_date').val(date);
        $('#mr_tour_id').val(tour);
        $('#mr_start_km').val(start || '');
        $('#mr_end_km').val(end || '');
        $('#mr_notes').val(notes || '');
        $('#mr_photo').val('');
        $('#modal_date_label').text(date);

        $('#addReadingModal').modal('show');
    });

    $('#save_reading_btn').click(function () {
        const start = $('#mr_start_km').val();
        const end   = $('#mr_end_km').val();

        if (!start || !end) {
            Swal.fire({ icon: 'error', title: 'Missing Fields', text: 'Start KM and End KM are required.' });
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).text('Saving...');

        const formData = new FormData();
        formData.append('tour_id',      $('#mr_tour_id').val());
        formData.append('reading_date', $('#mr_date').val());
        formData.append('start_km',     start);
        formData.append('end_km',       end);
        formData.append('notes',        $('#mr_notes').val());

        const photo = $('#mr_photo')[0].files[0];
        if (photo) formData.append('photo', photo);

        $.ajax({
            url: '<?= base_url('driver_portal/save_meter_reading') ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                if (res.status === 200) {
                    Swal.fire({ icon: 'success', title: 'Saved!', text: res.message, timer: 1500, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                    btn.prop('disabled', false).text('Save');
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong!' });
                btn.prop('disabled', false).text('Save');
            }
        });
    });
</script>
</body>
</html>
