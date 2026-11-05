<!-- Phase 2: Hotel Bookings & Passenger Management -->
<div class="quotation-phase-form" id="phase2-form">
    <div class="phase-header">
        <h6><i class="fas fa-hotel"></i> Phase 2: Hotel Bookings & Passenger Management</h6>
        <small class="text-muted">Select hotels for each day and add passenger details</small>
    </div>

    <div class="form-container">
        <!-- Tour Days Timeline -->
        <div class="card mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0">Daily Hotel Selection</h6>
            </div>
            <div class="card-body">
                <div id="hotel-timeline-container">
                    <!-- Dynamic content will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Passenger Management -->
        <div class="card mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0">Passenger Management</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="passenger_name" class="form-label">Passenger Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="passenger_name" name="passenger_name"
                               placeholder="Enter passenger full name">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-success btn-sm" id="add-passenger-btn">
                            <i class="fas fa-plus"></i> Add Passenger
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="passenger-counter mb-2">
                            <small class="text-muted">
                                Passengers added: <span id="passenger-count">0</span> of <span id="total-passengers">0</span>
                            </small>
                        </div>
                        <div class="passenger-list-container" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm table-bordered" id="passenger-table">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="75%">Passenger Name</th>
                                        <th width="20%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="passenger-list">
                                    <!-- Dynamic passenger list -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hotel Day Template (Hidden) -->
        <template id="hotel-day-template">
            <div class="hotel-day-item mb-4" data-date="" data-day="">
                <div class="day-header bg-light p-2 rounded">
                    <h6 class="mb-0">
                        Day <span class="day-number"></span> - <span class="day-date"></span>
                        <small class="text-muted">(<span class="day-name"></span>)</small>
                        <span class="float-end hotel-status-badge">
                            <span class="badge bg-warning">Pending</span>
                        </span>
                    </h6>
                </div>

                <div class="day-content p-3 border">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Hotel</label>
                            <select class="form-select form-select-sm hotel-select" data-date="">
                                <option value="">Select Hotel</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Room Type</label>
                            <select class="form-select form-select-sm room-type-select" data-date="" disabled>
                                <option value="">Select Room Type</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label class="form-label">Meal Plan</label>
                            <select class="form-select form-select-sm meal-plan-select" data-date="" disabled>
                                <option value="">Select Meal Plan</option>
                                <option value="fb">Full Board (FB)</option>
                                <option value="hb">Half Board (HB)</option>
                                <option value="bb">Bed & Breakfast (BB)</option>
                                <option value="ro">Room Only (RO)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Activities Section -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label class="form-label">Daily Activities</label>
                            <div class="activities-container" data-day="">
                                <div class="activity-input-row mb-2">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <input type="text" class="form-control form-control-sm activity-name"
                                                   placeholder="Enter activity name">
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input activity-ss" type="checkbox" id="ss-">
                                                <label class="form-check-label" for="ss-">SS</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input activity-op" type="checkbox" id="op-">
                                                <label class="form-check-label" for="op-">OP</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-primary btn-sm add-activity-btn">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="activities-list">
                                    <!-- Dynamic activities will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-12">
                            <div class="text-end">
                                <button type="button" class="btn btn-success btn-sm save-day-btn" data-date="" disabled>
                                    <i class="fas fa-save"></i> Save Day
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between mt-3">
            <button type="button" class="btn btn-secondary btn-sm" id="back-to-phase1-btn">
                <i class="fas fa-arrow-left"></i> Back to Phase 1
            </button>
            <div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="save-phase2-draft-btn">
                    <i class="fas fa-save"></i> Save Draft
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="complete-phase2-btn">
                    <i class="fas fa-check"></i> Complete Phase 2
                </button>
            </div>
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

.hotel-day-item {
    border: 1px solid #e3e6f0;
    border-radius: 6px;
    overflow: hidden;
}

.day-header {
    border-bottom: 1px solid #e3e6f0;
}

.hotel-status-badge .badge {
    font-size: 0.75rem;
}

.passenger-counter {
    font-weight: 500;
}

#passenger-table {
    font-size: 0.875rem;
}

#passenger-table th {
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

.hotel-select:focus,
.room-type-select:focus,
.meal-plan-select:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.save-day-btn:disabled {
    opacity: 0.6;
}

.save-day-btn:not(:disabled) {
    background-color: #78b848;
    border-color: #78b848;
}

.save-day-btn:not(:disabled):hover {
    background-color: #218838;
    border-color: #1e7e34;
}

.activities-container {
    border: 1px solid #e3e6f0;
    border-radius: 6px;
    padding: 15px;
    background-color: #f8f9fa;
}

.activity-input-row {
    padding: 10px;
    background-color: white;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}

.activities-list {
    max-height: 200px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 8px 12px;
    margin-bottom: 5px;
    background-color: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 0.875rem;
}

.activity-item .activity-name {
    flex-grow: 1;
    margin-right: 10px;
}

.activity-item .activity-badges {
    margin-right: 10px;
}

.activity-item .activity-badges .badge {
    font-size: 0.75rem;
    margin-right: 5px;
}

.activity-item .remove-activity-btn {
    padding: 2px 6px;
    font-size: 0.75rem;
}
</style>