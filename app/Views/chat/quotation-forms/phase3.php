<!-- Phase 3: Final Details & Review Form -->
<div class="quotation-phase-form" id="phase3-form">
    <div class="phase-header">
        <h6><i class="fas fa-check-circle"></i> Phase 3: Final Details & Review</h6>
        <small class="text-muted">Review information and add final touches</small>
    </div>

    <div class="form-container">
        <!-- Quotation Summary -->
        <!-- <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-file-invoice"></i> Quotation Summary</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Tour Name:</strong> <span id="summary-tour-name">-</span></p>
                        <p><strong>Duration:</strong> <span id="summary-duration">-</span></p>
                        <p><strong>Passengers:</strong> <span id="summary-passengers">-</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Tour Type:</strong> <span id="summary-tour-type">-</span></p>
                        <p><strong>Room Config:</strong> <span id="summary-rooms">-</span></p>
                        <p><strong>Total Days:</strong> <span id="summary-days">-</span></p>
                    </div>
                </div>
            </div>
        </div> -->


        <!-- Special Instructions -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Special Instructions & Notes</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="customer_requirements" class="form-label">Customer Requirements</label>
                        <textarea class="form-control" id="customer_requirements" name="customer_requirements" rows="3"
                                  placeholder="Any special requirements or preferences from the customer..."></textarea>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="internal_notes" class="form-label">Internal Notes</label>
                        <textarea class="form-control" id="internal_notes" name="internal_notes" rows="3"
                                  placeholder="Internal notes for staff (not visible to customer)..."></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <label for="agent_comments" class="form-label">Agent Comments</label>
                        <textarea class="form-control" id="agent_comments" name="agent_comments" rows="3"
                                  placeholder="Additional comments or clarifications from your side..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between mt-3">
            <button type="button" class="btn btn-secondary btn-sm" id="prev-phase3-btn">
                <i class="fas fa-arrow-left"></i> Previous Phase
            </button>
            <div>
                <button type="button" class="btn btn-info btn-sm" id="save-phase3-btn">
                    <i class="fas fa-save"></i> Save Progress
                </button>
                <button type="button" class="btn btn-success btn-sm" id="complete-quotation-btn">
                    <i class="fas fa-check"></i> Submit for Review
                </button>
            </div>
        </div>
    </div>
</div>


<style>
.quotation-phase-form .card-header.bg-success {
    background: linear-gradient(135deg, #78b848 0%, #78b848 100%) !important;
}

.quotation-phase-form .card-header.bg-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%) !important;
    color: #212529 !important;
}

.quotation-phase-form .card-header.bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%) !important;
}

.review-check:checked + label {
    color: #78b848;
    font-weight: 500;
}

.form-check-label small {
    font-size: 0.75rem;
    margin-top: 2px;
}

#summary-tour-name {
    color: #007bff;
    font-weight: 600;
}

.card-body p {
    margin-bottom: 0.5rem;
}

.card-body p strong {
    color: #495057;
    font-weight: 600;
}

.btn-success {
    background: linear-gradient(135deg, #78b848 0%, #78b848 100%);
    border: none;
}

.btn-success:hover {
    background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
}
</style>