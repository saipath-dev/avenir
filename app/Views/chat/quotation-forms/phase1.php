<!-- Phase 1: Basic Information Form -->
<div class="quotation-phase-form" id="phase1-form">
    <div class="phase-header">
        <h6><i class="fas fa-info-circle"></i> Phase 1: Basic Information</h6>
        <small class="text-muted">Enter tour details and passenger information</small>
    </div>

    <div class="form-container">
        <!-- Tour Dates -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" required>
            </div>
            <div class="col-md-6">
                <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" required>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="card mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0">Customer Information</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="consignee" class="form-label">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="consignee" name="consignee" required
                               placeholder="Auto-filled from conversation name" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="agent_ref" class="form-label">Agent Reference <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="agent_ref" name="agent_ref" required
                               placeholder="Auto-generated reference">
                    </div>
                </div>
                <!-- Hidden fields for Email and Contact - still submitted but not visible -->
                <div style="display: none;">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control form-control-sm" id="email" name="email"
                                   value="tour@lionroyal.com">
                        </div>
                        <div class="col-md-6">
                            <label for="contact" class="form-label">Contact Number</label>
                            <input type="text" class="form-control form-control-sm" id="contact" name="contact"
                                   value="338388945">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Passenger Information -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="pass_count" class="form-label">Total Passengers <span class="text-danger">*</span></label>
                <input type="number" class="form-control form-control-sm" id="pass_count" name="pass_count" min="1" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Tour Type</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="hotel_only" name="hotel_only" value="1">
                        <label class="form-check-label" for="hotel_only">Hotel Only</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="transport_only" name="transport_only" value="1">
                        <label class="form-check-label" for="transport_only">Transport Only</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Configuration -->
        <div class="card mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0">Room Configuration</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label for="sgl" class="form-label">Single Rooms</label>
                        <input type="number" class="form-control form-control-sm" id="sgl" name="sgl" min="0" value="0">
                    </div>
                    <div class="col-md-4">
                        <label for="dbl" class="form-label">Double Rooms</label>
                        <input type="number" class="form-control form-control-sm" id="dbl" name="dbl" min="0" value="0">
                    </div>
                    <div class="col-md-4">
                        <label for="tpl" class="form-label">Triple Rooms</label>
                        <input type="number" class="form-control form-control-sm" id="tpl" name="tpl" min="0" value="0">
                    </div>
                </div>
                <div class="mt-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_twin" name="is_twin" value="1">
                        <label class="form-check-label" for="is_twin">
                            Prefer Twin Beds for Double Rooms
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicle Selection (Hidden if Hotel Only) -->
        <div class="card mb-3" id="vehicle-section">
            <div class="card-header py-2">
                <h6 class="mb-0">Vehicle & Transport</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <label for="vehicle" class="form-label">Select Vehicle</label>
                        <select class="form-select form-select-sm" id="vehicle" name="vehicle">
                            <option value="">Select Vehicle</option>
                            <?php if (isset($vehicles) && is_array($vehicles) && !empty($vehicles)): ?>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <?php if (is_array($vehicle) && isset($vehicle['id'])): ?>
                                        <option value="<?= $vehicle['id'] ?>"
                                                data-name="<?= htmlspecialchars($vehicle['type'] ?? 'Unknown Vehicle') ?>"
                                                data-rate="<?= $vehicle['km_rate'] ?? 0 ?>">
                                            <?= htmlspecialchars($vehicle['type'] ?? 'Unknown Vehicle') ?> - $<?= number_format($vehicle['km_rate'] ?? 0, 2) ?>/day
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No vehicles available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flight Information -->
        <div class="card mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0">Flight Information <small class="text-muted">(Optional)</small></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label for="arrival_flight" class="form-label">Arrival Flight Details</label>
                        <input type="text" class="form-control form-control-sm" id="arrival_flight" name="arrival_flight"
                               placeholder="e.g., UL 102">
                    </div>
                    <div class="col-md-6">
                        <label for="arrival_flight_time" class="form-label">Arrival Flight Time</label>
                        <input type="time" class="form-control form-control-sm" id="arrival_flight_time" name="arrival_flight_time">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="departure_flight" class="form-label">Departure Flight Details</label>
                        <input type="text" class="form-control form-control-sm" id="departure_flight" name="departure_flight"
                               placeholder="e.g., UL 103">
                    </div>
                    <div class="col-md-6">
                        <label for="departure_flight_time" class="form-label">Departure Flight Time</label>
                        <input type="time" class="form-control form-control-sm" id="departure_flight_time" name="departure_flight_time">
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between mt-3">
            <button type="button" class="btn btn-secondary btn-sm" id="save-draft-btn">
                <i class="fas fa-save"></i> Save Draft
            </button>
            <button type="button" class="btn btn-primary btn-sm" id="next-phase-btn">
                <i class="fas fa-arrow-right"></i> Next Phase
            </button>
        </div>
    </div>
</div>


<style>
.quotation-phase-form {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.phase-header {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.phase-header h6 {
    margin: 0;
    color: #495057;
}

.form-container .card {
    border: 1px solid #e3e6f0;
    box-shadow: none;
}

.form-container .card-header {
    background: #f1f3f4;
    border-bottom: 1px solid #e3e6f0;
}

.form-control-sm, .form-select-sm {
    font-size: 0.875rem;
}

.btn-sm {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
}
</style>