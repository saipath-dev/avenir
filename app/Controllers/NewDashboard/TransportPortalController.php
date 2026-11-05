<?php

namespace App\Controllers\NewDashboard;

use App\Controllers\BaseController;
use App\Controllers\SmsController;
use App\Models\ConsigneeModel;
use App\Models\DriverAllocationModel;
use App\Models\DriverAdvanceRequestModel;
use App\Models\TourActivityModel;
use App\Models\DriverMeterReadingModel;
use App\Models\DriverPortalExpensesModel;
use App\Models\TourFinalizeModel;
use App\Models\DriverExpensesModel;
use App\Models\LogSheetMileModel;
use App\Models\AccountPostingModel;
use App\Models\VehicleModel;
use App\Models\UsageNoteModel;

class TransportPortalController extends BaseController
{
    public function index()
    {
        return view('pages/dashboard2/transport/transport_portal');
    }

    public function ongoingPanel()
    {
        return view('pages/dashboard2/transport/transport_ongoing');
    }

    // Admin/back-office uploads a meter reading photo on behalf of a driver who sent it
    // through another channel (WhatsApp etc). Writes to the same driver_meter_reading
    // table the driver's own portal uses, so it shows up in Driver Log Sheet Entries as normal.
    public function adminSaveMeterReading()
    {
        try {
            $tour_id      = $this->request->getPost('tour_id');
            $driver_acc   = $this->request->getPost('driver_acc');
            $reading_date = $this->request->getPost('reading_date');
            $type         = $this->request->getPost('type'); // 'start' or 'end'
            $km           = $this->request->getPost('km');
            $notes        = $this->request->getPost('notes');

            if (!$tour_id || !$driver_acc || !$reading_date || !$type || $km === null || $km === '') {
                return $this->response->setJSON(['status' => 400, 'message' => 'Required fields missing']);
            }

            $allocation = (new DriverAllocationModel())
                ->where('tour_id', $tour_id)
                ->where('driver', $driver_acc)
                ->first();
            if (!$allocation) {
                return $this->response->setJSON(['status' => 403, 'message' => 'Selected driver is not allocated to this tour']);
            }

            $readingModel = new DriverMeterReadingModel();
            $existing = $readingModel
                ->where('tour_id', $tour_id)
                ->where('driver_acc', $driver_acc)
                ->where('reading_date', $reading_date)
                ->where('type', $type)
                ->first();

            if ($existing) {
                $typeLabel = $type === 'start' ? 'Start' : 'End';
                return $this->response->setJSON(['status' => 400, 'message' => $typeLabel . ' reading for this date has already been submitted and cannot be resubmitted']);
            }

            $photoName = null;
            $photo = $this->request->getFile('photo');
            if ($photo && $photo->isValid() && !$photo->hasMoved()) {
                $uploadPath = FCPATH . 'uploads/meter_readings/' . $tour_id . '/';
                if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
                $photoName = $photo->getRandomName();
                $photo->move($uploadPath, $photoName);
            }

            $saveData = [
                'tour_id'      => $tour_id,
                'driver_acc'   => $driver_acc,
                'reading_date' => $reading_date,
                'type'         => $type,
                'km'           => $km,
                'notes'        => $notes,
                'created_by'   => session()->get('username'),
            ];
            if ($photoName) $saveData['photo'] = $photoName;

            $readingModel->insert($saveData);

            return $this->response->setJSON(['status' => 200, 'message' => 'Reading saved successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // Admin/back-office attaches a photo to an EXISTING meter reading that has none yet (e.g. the
    // driver phoned in the km without a photo). Add-only — never overwrites a photo the driver (or
    // a prior admin upload) already attached, so this can't be used to swap out real evidence.
    public function addMeterReadingPhoto()
    {
        try {
            $readingId = (int) $this->request->getPost('reading_id');
            if ($readingId <= 0) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Reading is required']);
            }

            $photo = $this->request->getFile('photo');
            if (!$photo || !$photo->isValid() || $photo->hasMoved()) {
                return $this->response->setJSON(['status' => 400, 'message' => 'A valid photo file is required']);
            }
            if (strpos((string) $photo->getMimeType(), 'image/') !== 0) {
                return $this->response->setJSON(['status' => 400, 'message' => 'File must be an image']);
            }

            $readingModel = new DriverMeterReadingModel();
            $reading = $readingModel->find($readingId);
            if (!$reading) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Reading not found']);
            }
            if (!empty($reading['photo'])) {
                return $this->response->setJSON(['status' => 400, 'message' => 'This reading already has a photo']);
            }

            $uploadPath = FCPATH . 'uploads/meter_readings/' . $reading['tour_id'] . '/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            $photoName = $photo->getRandomName();
            if (!$photo->move($uploadPath, $photoName)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Failed to save photo']);
            }

            $readingModel->update($readingId, ['photo' => $photoName]);

            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Photo added successfully',
                'photo_url' => base_url('uploads/meter_readings/' . $reading['tour_id'] . '/' . $photoName),
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function adminSaveExpense()
    {
        try {
            $tour_id       = trim((string) $this->request->getPost('tour_id'));
            $driver_acc    = trim((string) $this->request->getPost('driver_acc'));
            $expense_date  = trim((string) $this->request->getPost('expense_date'));
            $category      = trim((string) $this->request->getPost('category'));
            $supplier      = trim((string) $this->request->getPost('supplier'));
            $invoice_no    = trim((string) $this->request->getPost('invoice_no'));
            $description   = trim((string) $this->request->getPost('description'));
            $dollar_rate   = trim((string) $this->request->getPost('dollar_rate'));
            $dollar_amount = trim((string) $this->request->getPost('dollar_amount'));
            $lkr_amount    = trim((string) $this->request->getPost('lkr_amount'));

            if (
                $tour_id === '' || $driver_acc === '' || $expense_date === '' || $category === '' ||
                $supplier === '' || $invoice_no === ''
            ) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Please fill all required fields']);
            }

            $parsedDate = \DateTime::createFromFormat('Y-m-d', $expense_date);
            if (!$parsedDate || $parsedDate->format('Y-m-d') !== $expense_date) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid expense date']);
            }

            if (
                ($dollar_rate !== '' && !is_numeric($dollar_rate)) ||
                ($dollar_amount !== '' && !is_numeric($dollar_amount)) ||
                ($lkr_amount !== '' && !is_numeric($lkr_amount))
            ) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Amounts must be numeric']);
            }

            $dollarRateValue = $dollar_rate === '' ? 0.00 : round((float) $dollar_rate, 2);
            $dollarAmountValue = $dollar_amount === '' ? 0.00 : round((float) $dollar_amount, 2);
            $lkrAmountValue = $lkr_amount === '' ? 0.00 : round((float) $lkr_amount, 2);

            if ($dollarRateValue < 0 || $dollarAmountValue < 0 || $lkrAmountValue < 0) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Amounts cannot be negative']);
            }

            if ($dollarRateValue === 0.00 && $dollarAmountValue === 0.00 && $lkrAmountValue === 0.00) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Please enter either a Dollar amount/rate or a direct LKR amount']);
            }

            // Direct LKR entry takes priority when both dollar rate AND amount are also given,
            // fall back to the computed dollar total the same way the driver portal's own
            // save_expense()/update_expense() already resolve this — see DriverPortalController.
            $computedLkrAmount = ($dollarRateValue > 0 && $dollarAmountValue > 0)
                ? round($dollarRateValue * $dollarAmountValue, 2)
                : $lkrAmountValue;

            $allocation = (new DriverAllocationModel())
                ->where('tour_id', $tour_id)
                ->where('driver', $driver_acc)
                ->first();
            if (!$allocation) {
                return $this->response->setJSON(['status' => 403, 'message' => 'Selected driver is not allocated to this tour']);
            }

            $expenseCategories = [
                '6-9-23-10640' => 'Entrance Fee Expenses',
                '6-9-23-10641' => 'Other Expenses',
            ];
            if (!isset($expenseCategories[$category])) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid expense category']);
            }

            $accountType = $category === '6-9-23-10640' ? 1 : 2;
            $account = (new AccountPostingModel())
                ->select('L_Code, PName')
                ->where('B_Code', 4)
                ->where('T_Code', 8)
                ->where('H_Code', 109)
                ->where('type', $accountType)
                ->where('L_Code', $supplier)
                ->first();
            if (!$account) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid expense account']);
            }
            $supplierName = $account['PName'] ?? $supplier;

            $consignee = (new ConsigneeModel())->select('tour_cancel')->where('tour_id', $tour_id)->first();
            if (!$consignee) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Tour not found']);
            }
            if ((int) ($consignee['tour_cancel'] ?? 0) === 1) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot add expense. The tour has been cancelled.']);
            }

            $invoiceImage = $this->request->getFile('invoice_image');
            if ($invoiceImage && $invoiceImage->getError() !== UPLOAD_ERR_NO_FILE) {
                if (!$invoiceImage->isValid() || $invoiceImage->hasMoved()) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'Failed to upload invoice image']);
                }
                if (strpos((string) $invoiceImage->getMimeType(), 'image/') !== 0) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'Invoice image must be a valid image file']);
                }
                $uploadPath = FCPATH . 'uploads/driver_portal_expenses/' . $tour_id . '/';
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
            }

            $description = $description !== '' ? $description : $expenseCategories[$category];
            $timestamp = date('Y-m-d H:i:s');

            // Once this driver has already finalized the tour, TourFinalize() has done its one-time
            // copy from driver_portal_expenses into driver_expenses and will never run again for
            // them — a driver_portal_expenses row added after that point would sit invisible,
            // never entering the approval/payment flow. So a post-finalize expense goes straight
            // into driver_expenses instead, exactly like TourFinalize()'s own copy does.
            $isFinalized = (new TourFinalizeModel())
                ->where('tour_id', $tour_id)
                ->where('driver_acc', $driver_acc)
                ->countAllResults() > 0;

            if ($isFinalized) {
                $expenseModel = new DriverExpensesModel();
                $saveData = [
                    'tour_id' => $tour_id,
                    'agent' => $driver_acc,
                    'agent_name' => $allocation['driver_name'] ?? $driver_acc,
                    'category' => $category,
                    'supplier' => $supplier,
                    'supplier_name' => $supplierName,
                    'invoice_no' => $invoice_no,
                    'expense_date' => $expense_date,
                    'dollar_rate' => $dollarRateValue,
                    'dollar_amount' => $dollarAmountValue,
                    'lkr_amount' => $computedLkrAmount,
                    'description' => $description,
                    'status' => 'pending',
                    'request_send' => 0,
                    'created_by' => session()->get('username'),
                    'created_at' => $timestamp,
                ];
            } else {
                $expenseModel = new DriverPortalExpensesModel();
                $saveData = [
                    'tour_id' => $tour_id,
                    'agent' => $driver_acc,
                    'agent_name' => $allocation['driver_name'] ?? $driver_acc,
                    'category' => $category,
                    'supplier' => $supplier,
                    'supplier_name' => $supplierName,
                    'invoice_no' => $invoice_no,
                    'expense_date' => $expense_date,
                    'dollar_rate' => $dollarRateValue,
                    'dollar_amount' => $dollarAmountValue,
                    'lkr_amount' => $computedLkrAmount,
                    'description' => $description,
                    'status' => 'pending',
                    'request_send' => 0,
                    'created_by' => session()->get('username'),
                    'created_at' => $timestamp,
                ];
            }

            $expenseId = $expenseModel->insert($saveData, true);
            if (!$expenseId) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Failed to save expense']);
            }

            // Both tables share the same image folder/naming convention, keyed by each row's own
            // id within its own table — driver_expenses ids and driver_portal_expenses ids are
            // independent sequences, so this can never collide between the two.
            if ($invoiceImage && $invoiceImage->getError() !== UPLOAD_ERR_NO_FILE) {
                $extension = strtolower((string) ($invoiceImage->getExtension() ?: $invoiceImage->guessExtension() ?: 'jpg'));
                $imageName = $expenseId . '.' . $extension;
                $uploadPath = FCPATH . 'uploads/driver_portal_expenses/' . $tour_id . '/';

                if (!$invoiceImage->move($uploadPath, $imageName, true)) {
                    $expenseModel->delete($expenseId);
                    return $this->response->setJSON(['status' => 400, 'message' => 'Failed to save invoice image']);
                }
            }

            return $this->response->setJSON(['status' => 200, 'message' => 'Expense saved successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // Admin/back-office edit of a driver's expense from the unified Expenses table on
    // driver_tour_report.php. expense_id is always the driver_portal_expenses id (the row of
    // record that exists from the moment the expense is created). If TourFinalize() has already
    // copied this expense into driver_expenses, that copy is located by the same (date, category,
    // supplier, lkr_amount) match key driverReport() itself uses to join the two tables, and
    // updated too — so both stay in sync. Editing is blocked once the driver_expenses copy has
    // moved past pending (status posted/approved — see getExpensesByTour()'s normalized_status),
    // matching driverReport()'s is_posted/status_label the view uses to hide the edit button.
    public function adminUpdateExpense()
    {
        try {
            $expense_id    = (int) $this->request->getPost('expense_id');
            $tour_id       = trim((string) $this->request->getPost('tour_id'));
            $driver_acc    = trim((string) $this->request->getPost('driver_acc'));
            $expense_date  = trim((string) $this->request->getPost('expense_date'));
            $category      = trim((string) $this->request->getPost('category'));
            $supplier      = trim((string) $this->request->getPost('supplier'));
            $invoice_no    = trim((string) $this->request->getPost('invoice_no'));
            $description   = trim((string) $this->request->getPost('description'));
            $dollar_rate   = trim((string) $this->request->getPost('dollar_rate'));
            $dollar_amount = trim((string) $this->request->getPost('dollar_amount'));
            $lkr_amount    = trim((string) $this->request->getPost('lkr_amount'));

            if (
                $expense_id <= 0 || $tour_id === '' || $driver_acc === '' || $expense_date === '' ||
                $category === '' || $supplier === '' || $invoice_no === ''
            ) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Please fill all required fields']);
            }

            $parsedDate = \DateTime::createFromFormat('Y-m-d', $expense_date);
            if (!$parsedDate || $parsedDate->format('Y-m-d') !== $expense_date) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid expense date']);
            }

            if (
                ($dollar_rate !== '' && !is_numeric($dollar_rate)) ||
                ($dollar_amount !== '' && !is_numeric($dollar_amount)) ||
                ($lkr_amount !== '' && !is_numeric($lkr_amount))
            ) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Amounts must be numeric']);
            }

            $dollarRateValue = $dollar_rate === '' ? 0.00 : round((float) $dollar_rate, 2);
            $dollarAmountValue = $dollar_amount === '' ? 0.00 : round((float) $dollar_amount, 2);
            $lkrAmountValue = $lkr_amount === '' ? 0.00 : round((float) $lkr_amount, 2);

            if ($dollarRateValue < 0 || $dollarAmountValue < 0 || $lkrAmountValue < 0) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Amounts cannot be negative']);
            }

            if ($dollarRateValue === 0.00 && $dollarAmountValue === 0.00 && $lkrAmountValue === 0.00) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Please enter either a Dollar amount/rate or a direct LKR amount']);
            }

            $computedLkrAmount = ($dollarRateValue > 0 && $dollarAmountValue > 0)
                ? round($dollarRateValue * $dollarAmountValue, 2)
                : $lkrAmountValue;

            $allocation = (new DriverAllocationModel())
                ->where('tour_id', $tour_id)
                ->where('driver', $driver_acc)
                ->first();
            if (!$allocation) {
                return $this->response->setJSON(['status' => 403, 'message' => 'Selected driver is not allocated to this tour']);
            }

            $expenseCategories = [
                '6-9-23-10640' => 'Entrance Fee Expenses',
                '6-9-23-10641' => 'Other Expenses',
            ];
            if (!isset($expenseCategories[$category])) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid expense category']);
            }

            $accountType = $category === '6-9-23-10640' ? 1 : 2;
            $account = (new AccountPostingModel())
                ->select('L_Code, PName')
                ->where('B_Code', 4)
                ->where('T_Code', 8)
                ->where('H_Code', 109)
                ->where('type', $accountType)
                ->where('L_Code', $supplier)
                ->first();
            if (!$account) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid expense account']);
            }
            $supplierName = $account['PName'] ?? $supplier;

            $expenseModel = new DriverPortalExpensesModel();
            $existingExpense = $expenseModel
                ->where('id', $expense_id)
                ->where('tour_id', $tour_id)
                ->where('agent', $driver_acc)
                ->first();
            if (!$existingExpense) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Expense not found']);
            }

            // Find the driver_expenses copy (if TourFinalize() has already made one) BEFORE
            // updating, using the row's current values — same match key driverReport() uses.
            $legacyExpenseModel = new DriverExpensesModel();
            $legacyExpense = $legacyExpenseModel
                ->where('tour_id', $tour_id)
                ->where('agent', $driver_acc)
                ->where('expense_date', $existingExpense['expense_date'] ?? '')
                ->where('category', $existingExpense['category'] ?? '')
                ->where('supplier_name', $existingExpense['supplier_name'] ?? ($existingExpense['supplier'] ?? ''))
                ->where('lkr_amount', $existingExpense['lkr_amount'] ?? 0)
                ->first();

            if ($legacyExpense && in_array($legacyExpense['status'] ?? '', ['posted', 'approved'], true)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'This expense cannot be edited — it has already been posted/approved']);
            }

            $consignee = (new ConsigneeModel())->select('tour_cancel')->where('tour_id', $tour_id)->first();
            if (!$consignee) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Tour not found']);
            }
            if ((int) ($consignee['tour_cancel'] ?? 0) === 1) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot edit expense. The tour has been cancelled.']);
            }

            $invoiceImage = $this->request->getFile('invoice_image');
            if ($invoiceImage && $invoiceImage->getError() !== UPLOAD_ERR_NO_FILE) {
                if (!$invoiceImage->isValid() || $invoiceImage->hasMoved()) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'Failed to upload invoice image']);
                }
                if (strpos((string) $invoiceImage->getMimeType(), 'image/') !== 0) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'Invoice image must be a valid image file']);
                }
            }

            $updateData = [
                'category' => $category,
                'supplier' => $supplier,
                'supplier_name' => $supplierName,
                'invoice_no' => $invoice_no,
                'expense_date' => $expense_date,
                'dollar_rate' => $dollarRateValue,
                'dollar_amount' => $dollarAmountValue,
                'lkr_amount' => $computedLkrAmount,
                'description' => $description !== '' ? $description : $expenseCategories[$category],
                'updated_by' => session()->get('username'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if (!$expenseModel->update($expense_id, $updateData)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Failed to update expense']);
            }

            // Keep the driver_expenses copy in sync with the same field set (excluding audit
            // fields like created_by/status, which belong to that table's own workflow).
            if ($legacyExpense) {
                $legacyExpenseModel->update($legacyExpense['id'], [
                    'category' => $category,
                    'supplier' => $supplier,
                    'supplier_name' => $supplierName,
                    'invoice_no' => $invoice_no,
                    'expense_date' => $expense_date,
                    'dollar_rate' => $dollarRateValue,
                    'dollar_amount' => $dollarAmountValue,
                    'lkr_amount' => $computedLkrAmount,
                    'description' => $updateData['description'],
                ]);
            }

            if ($invoiceImage && $invoiceImage->getError() !== UPLOAD_ERR_NO_FILE) {
                $uploadPath = FCPATH . 'uploads/driver_portal_expenses/' . $tour_id . '/';
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                $extension = strtolower((string) ($invoiceImage->getExtension() ?: $invoiceImage->guessExtension() ?: 'jpg'));
                $imageName = $expense_id . '.' . $extension;

                if (!$invoiceImage->move($uploadPath, $imageName, true)) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'Failed to update invoice image']);
                }

                // Same folder/id-keyed naming convention as adminSaveExpense() — clear any older
                // extension for this id so a changed file type doesn't leave a stale duplicate.
                $pattern = FCPATH . 'uploads/driver_portal_expenses/' . $tour_id . '/' . $expense_id . '.*';
                foreach (glob($pattern) ?: [] as $match) {
                    if (basename($match) !== $imageName && is_file($match)) {
                        @unlink($match);
                    }
                }
            }

            return $this->response->setJSON(['status' => 200, 'message' => 'Expense updated successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // Admin/back-office delete of a driver's expense from the unified Expenses table — mirrors
    // adminUpdateExpense()'s dual-table sync: deletes the driver_portal_expenses row and, if
    // TourFinalize() already copied it, the matching driver_expenses row too. Blocked once that
    // copy has moved past pending (posted/approved), same rule as adminUpdateExpense().
    public function adminDeleteExpense()
    {
        try {
            $expense_id = (int) $this->request->getPost('expense_id');
            $tour_id    = trim((string) $this->request->getPost('tour_id'));
            $driver_acc = trim((string) $this->request->getPost('driver_acc'));

            if ($expense_id <= 0 || $tour_id === '' || $driver_acc === '') {
                return $this->response->setJSON(['status' => 400, 'message' => 'Expense, tour and driver are required']);
            }

            $allocation = (new DriverAllocationModel())
                ->where('tour_id', $tour_id)
                ->where('driver', $driver_acc)
                ->first();
            if (!$allocation) {
                return $this->response->setJSON(['status' => 403, 'message' => 'Selected driver is not allocated to this tour']);
            }

            $expenseModel = new DriverPortalExpensesModel();
            $existingExpense = $expenseModel
                ->where('id', $expense_id)
                ->where('tour_id', $tour_id)
                ->where('agent', $driver_acc)
                ->first();
            if (!$existingExpense) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Expense not found']);
            }

            $legacyExpenseModel = new DriverExpensesModel();
            $legacyExpense = $legacyExpenseModel
                ->where('tour_id', $tour_id)
                ->where('agent', $driver_acc)
                ->where('expense_date', $existingExpense['expense_date'] ?? '')
                ->where('category', $existingExpense['category'] ?? '')
                ->where('supplier_name', $existingExpense['supplier_name'] ?? ($existingExpense['supplier'] ?? ''))
                ->where('lkr_amount', $existingExpense['lkr_amount'] ?? 0)
                ->first();

            if ($legacyExpense && in_array($legacyExpense['status'] ?? '', ['posted', 'approved'], true)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'This expense cannot be deleted — it has already been posted/approved']);
            }

            if (!$expenseModel->delete($expense_id)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Failed to delete expense']);
            }

            if ($legacyExpense) {
                $legacyExpenseModel->delete($legacyExpense['id']);
            }

            $pattern = FCPATH . 'uploads/driver_portal_expenses/' . $tour_id . '/' . $expense_id . '.*';
            foreach (glob($pattern) ?: [] as $match) {
                if (is_file($match)) {
                    @unlink($match);
                }
            }

            return $this->response->setJSON(['status' => 200, 'message' => 'Expense deleted successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // Deletes a single driver-submitted meter reading row (one side of a start/end pair).
    // Frees that (tour_id, driver_acc, reading_date, type) slot so it can be resubmitted.
    public function deleteMeterReading()
    {
        try {
            $id = $this->request->getPost('id');
            $tour_id = $this->request->getPost('tour_id');

            if (!$id || !$tour_id) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Required fields missing']);
            }

            $readingModel = new DriverMeterReadingModel();
            $reading = $readingModel->where('id', $id)->where('tour_id', $tour_id)->first();
            if (!$reading) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Reading not found']);
            }

            $readingModel->delete($id);

            return $this->response->setJSON(['status' => 200, 'message' => 'Reading deleted successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // Admin/back-office finalizes a tour's log sheet on behalf of the driver — same effect as the
    // driver's own TourFinalize() in DriverPortalController (locks expenses, records actual mileage/
    // bata/vehicle-rate transport cost, moves pending expenses into driver_expenses, inserts a
    // log_sheet_mile row). Blocked if the driver already finalized first (isTourExpenseLocked). Unlike
    // the driver portal, this admin path does NOT require full meter readings for every tour day.
    public function adminFinalizeTour()
    {
        try {
            $tour_id = trim((string) $this->request->getPost('tour_id'));
            $driver_acc = trim((string) $this->request->getPost('driver_acc'));

            if ($tour_id === '' || $driver_acc === '') {
                return $this->response->setJSON(['status' => 400, 'message' => 'Tour and driver are required']);
            }

            $allocation = (new DriverAllocationModel())
                ->where('tour_id', $tour_id)
                ->where('driver', $driver_acc)
                ->first();
            if (!$allocation) {
                return $this->response->setJSON(['status' => 403, 'message' => 'Selected driver is not allocated to this tour']);
            }

            if ($this->isTourExpenseLocked($tour_id, $driver_acc)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'This tour has already been finalized']);
            }

            $consigneeModel = new ConsigneeModel();
            $consignee = $consigneeModel
                ->select('manual_id, consignee, start_date, end_date, transport_agent, transport_agent_name, vehicle_rate, bata_day_rate, bata_days')
                ->where('tour_id', $tour_id)
                ->first();

            if (!$consignee) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Tour not found']);
            }

            $pendingExpenses = (new DriverPortalExpensesModel())
                ->where('tour_id', $tour_id)
                ->where('agent', $driver_acc)
                ->where('status', 'pending')
                ->where('request_send', 0)
                ->orderBy('id', 'ASC')
                ->findAll();

            $expenseIds = array_values(array_filter(array_map(static fn($expense) => (int) ($expense['id'] ?? 0), $pendingExpenses)));
            $expenseCount = count($expenseIds);
            $distanceTotal = $this->getTourDistanceTotal($tour_id, $driver_acc);
            $totalDollarAmount = round(array_sum(array_map(static fn($expense) => (float) ($expense['dollar_amount'] ?? 0), $pendingExpenses)), 2);
            $totalLkrAmount = round(array_sum(array_map(static fn($expense) => (float) ($expense['lkr_amount'] ?? 0), $pendingExpenses)), 2);

            $actualMileageValue = $distanceTotal;
            $actualBataValue = round((float) ($consignee['bata_day_rate'] ?? 0), 2);
            $noOfDateValue = (int) ($consignee['bata_days'] ?? 0);
            $vehicleRateValue = round((float) ($consignee['vehicle_rate'] ?? 0), 2);

            // consignee.transport_agent/transport_agent_name are tour-wide "primary agent"
            // display fields — first driver to finalize wins, per the guarded update below.
            // They must NOT be used to tag which driver a log_sheet_mile row belongs to (see
            // $driver_acc used directly for that further down): on a multi-driver tour, once the
            // first driver's finalize has already set consignee.transport_agent, a later driver's
            // fallback would silently inherit the FIRST driver's code instead of their own.
            $effectiveTransportAgent = trim((string) ($consignee['transport_agent'] ?? ''));
            if ($effectiveTransportAgent === '' || $effectiveTransportAgent === '6-9-11-10691') {
                $effectiveTransportAgent = $driver_acc;
            }
            $effectiveTransportAgentName = trim((string) ($consignee['transport_agent_name'] ?? ''));
            if ($effectiveTransportAgentName === '' || $effectiveTransportAgent === $driver_acc) {
                $effectiveTransportAgentName = (string) ($allocation['driver_name'] ?? $effectiveTransportAgentName);
            }

            $updatedTransportCost = round(($actualMileageValue * $vehicleRateValue) + ($actualBataValue * $noOfDateValue), 2);

            $tourDateIds = [];
            foreach ($pendingExpenses as $expense) {
                $tourDateId = (int) ($expense['tour_date_id'] ?? 0);
                if ($tourDateId > 0) {
                    $tourDateIds[$tourDateId] = $tourDateId;
                }
            }

            $timestamp = date('Y-m-d H:i:s');
            $tourFinalizeModel = new TourFinalizeModel();
            $driverExpensesModel = new DriverExpensesModel();
            $expenseModel = new DriverPortalExpensesModel();
            $logsheetmileModel = new LogSheetMileModel();
            $db = db_connect();
            $db->transStart();

            $finalizeId = $tourFinalizeModel->insert([
                'tour_id' => $tour_id,
                'manual_id' => $consignee['manual_id'] ?? null,
                'consignee_name' => $consignee['consignee'] ?? null,
                'driver_acc' => $driver_acc,
                'driver_name' => $allocation['driver_name'] ?? $driver_acc,
                'tour_date_ids' => !empty($tourDateIds) ? json_encode(array_values($tourDateIds)) : null,
                'expense_ids' => !empty($expenseIds) ? json_encode($expenseIds) : null,
                'expense_count' => $expenseCount,
                'distance_total' => $distanceTotal,
                'total_dollar_amount' => $totalDollarAmount,
                'total_lkr_amount' => $totalLkrAmount,
                'status' => 'pending',
                'finalized_by' => session()->get('username'),
                'finalized_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], true);

            if (!$finalizeId) {
                throw new \RuntimeException('Failed to create tour finalize record.');
            }

            // $logsheetInsertId = $logsheetmileModel->insert([
            //     'tour_id' => $tour_id,
            //     'transport_agent' => $driver_acc,
            //     'log_sheet_mileage' => $actualMileageValue,
            //     'estimate_mileage' => $distanceTotal,
            //     'vehicle_rate' => $vehicleRateValue,
            //     'per_day_rate' => $actualBataValue,
            //     'no_of_dates' => $noOfDateValue,
            //     'login' => session()->get('username'),
            //     'date' => $timestamp,
            // ], true);

            // if (!$logsheetInsertId) {
            //     throw new \RuntimeException('Failed to create logsheet mileage record.');
            // }

            $tourTotals = $this->getTourTransportTotals($tour_id, $logsheetmileModel);

            $consigneeUpdateData = [
                'log_sheet_mileage' => $tourTotals['mileage'],
                'total_mileage' => $tourTotals['mileage'],
                'bata_day_rate' => $actualBataValue,
                'bata_days' => $noOfDateValue,
                'vehicle_rate' => $vehicleRateValue,
                'total_transport_cost' => $tourTotals['cost'],
            ];

            if (trim((string) ($consignee['transport_agent'] ?? '')) === '' || (string) ($consignee['transport_agent'] ?? '') === '6-9-11-10691') {
                $consigneeUpdateData['transport_agent'] = $effectiveTransportAgent;
            }

            if (trim((string) ($consignee['transport_agent_name'] ?? '')) === '') {
                $consigneeUpdateData['transport_agent_name'] = $effectiveTransportAgentName;
            }

            $consigneeUpdateSuccess = $consigneeModel
                ->where('tour_id', $tour_id)
                ->set($consigneeUpdateData)
                ->update();

            if (!$consigneeUpdateSuccess) {
                throw new \RuntimeException('Failed to update tour transport details.');
            }

            if (!empty($pendingExpenses)) {
                $driverExpenseRows = [];

                foreach ($pendingExpenses as $expense) {
                    $driverExpenseRows[] = [
                        'tour_id' => $tour_id,
                        'agent' => $expense['agent'] ?? $driver_acc,
                        'agent_name' => $expense['agent_name'] ?? ($allocation['driver_name'] ?? $driver_acc),
                        'category' => $expense['category'] ?? null,
                        'supplier' => $expense['supplier'] ?? null,
                        'supplier_name' => $expense['supplier_name'] ?? null,
                        'invoice_no' => $expense['invoice_no'] ?? null,
                        'expense_date' => $expense['expense_date'] ?? null,
                        'dollar_rate' => round((float) ($expense['dollar_rate'] ?? 0), 2),
                        'dollar_amount' => round((float) ($expense['dollar_amount'] ?? 0), 2),
                        'lkr_amount' => round((float) ($expense['lkr_amount'] ?? 0), 2),
                        'description' => $expense['description'] ?? null,
                        'status' => 'pending',
                        'created_by' => $expense['created_by'] ?? session()->get('username'),
                        'created_at' => $expense['created_at'] ?? $timestamp,
                        'request_send' => 0,
                        'request_send_by' => session()->get('username'),
                        'request_send_at' => $timestamp,
                    ];
                }

                if (!$driverExpensesModel->insertBatch($driverExpenseRows)) {
                    throw new \RuntimeException('Failed to insert driver expenses.');
                }
            }

            $expenseUpdateSuccess = true;
            if (!empty($expenseIds)) {
                $expenseUpdateSuccess = $expenseModel
                    ->whereIn('id', $expenseIds)
                    ->set([
                        'request_send' => 1,
                        'status' => 'finalized',
                        'updated_by' => session()->get('username'),
                        'updated_at' => $timestamp,
                    ])
                    ->update();
            }

            if (!$expenseUpdateSuccess) {
                throw new \RuntimeException('Failed to update finalized expenses.');
            }

            $db->transComplete();
            if (!$db->transStatus()) {
                throw new \RuntimeException('Failed to finalize tour expenses.');
            }

            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Tour finalized successfully',
                'finalize_id' => $finalizeId,
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    private function isTourExpenseLocked(string $tour_id, string $driver_acc): bool
    {
        return (new TourFinalizeModel())
            ->where('tour_id', $tour_id)
            ->where('driver_acc', $driver_acc)
            ->countAllResults() > 0;
    }

    // A tour can be finalized by more than one driver (e.g. one driver covers days 1-3, another
    // covers day 4). consignee holds one total_mileage/total_transport_cost per tour, so instead
    // of the latest finalize overwriting the previous driver's contribution, sum the latest
    // log_sheet_mile row per transport_agent for this tour — matching getLogSheetMileageByTour()
    // below, which reads "current" mileage per driver the same way for the completed-tours panel.
    private function getTourTransportTotals(string $tour_id, LogSheetMileModel $logsheetmileModel): array
    {
        $rows = $logsheetmileModel
            ->select('id, transport_agent, log_sheet_mileage, vehicle_rate, per_day_rate, no_of_dates')
            ->where('tour_id', $tour_id)
            ->orderBy('id', 'DESC')
            ->findAll();

        $latestByAgent = [];
        foreach ($rows as $row) {
            $agent = (string) ($row['transport_agent'] ?? '');
            if ($agent === '' || isset($latestByAgent[$agent])) {
                continue;
            }
            $latestByAgent[$agent] = $row;
        }

        $mileageTotal = 0.0;
        $costTotal = 0.0;
        foreach ($latestByAgent as $row) {
            $mileage = (float) ($row['log_sheet_mileage'] ?? 0);
            $rate = (float) ($row['vehicle_rate'] ?? 0);
            $bata = (float) ($row['per_day_rate'] ?? 0);
            $days = (int) ($row['no_of_dates'] ?? 0);

            $mileageTotal += $mileage;
            $costTotal += ($mileage * $rate) + ($bata * $days);
        }

        return [
            'mileage' => round($mileageTotal, 2),
            'cost' => round($costTotal, 2),
        ];
    }

    private function getTourDistanceTotal(string $tour_id, string $driver_acc): float
    {
        $readings = (new DriverMeterReadingModel())
            ->select('reading_date, type, km')
            ->where('tour_id', $tour_id)
            ->where('driver_acc', $driver_acc)
            ->orderBy('reading_date', 'ASC')
            ->findAll();

        $readingsByDate = [];
        foreach ($readings as $reading) {
            $readingDate = (string) ($reading['reading_date'] ?? '');
            $readingType = (string) ($reading['type'] ?? '');
            if ($readingDate === '' || $readingType === '') {
                continue;
            }

            $readingsByDate[$readingDate][$readingType] = (float) ($reading['km'] ?? 0);
        }

        $distanceTotal = 0.0;
        foreach ($readingsByDate as $dayReadings) {
            if (!isset($dayReadings['start'], $dayReadings['end'])) {
                continue;
            }

            $distanceTotal += max(0, $dayReadings['end'] - $dayReadings['start']);
        }

        return round($distanceTotal, 2);
    }

    // Expense category accounts (supplier list) for the admin upload form's supplier dropdown
    public function getExpenseAccounts()
    {
        $category = $this->request->getGet('category');
        $accountType = $category === '6-9-23-10640' ? 1 : 2;

        $accounts = (new AccountPostingModel())
            ->select('L_Code, PName')
            ->where('B_Code', 4)
            ->where('T_Code', 8)
            ->where('H_Code', 109)
            ->where('type', $accountType)
            ->orderBy('PName', 'ASC')
            ->findAll();

        return $this->response->setJSON(['status' => 200, 'data' => $accounts]);
    }

    public function upcomingTours()
    {
        $consigneeModel = new ConsigneeModel();

        $today = date('Y-m-d');
        $in30Days = date('Y-m-d', strtotime('+30 days'));

        $startDateFrom = $this->request->getGet('start_date_from') ?: $today;
        $startDateTo = $this->request->getGet('start_date_to') ?: $in30Days;

        $tours = $consigneeModel
            ->select('tour_id, manual_id, consignee, passenger_count, start_date, end_date, is_template, template_id, template_name, transport_agent, arrival_flight, arrival_flight_time, departure_flight, departure_flight_time')
            ->where('start_date >=', $startDateFrom)
            ->where('start_date <=', $startDateTo)
            ->where('tour_cancel', 0)
            ->where('is_template', 0)
            ->where('verify', 1)
            ->orderBy('start_date', 'ASC')
            ->findAll();

        $tours = $this->enrichTours($tours, withTickets: true);

        return $this->response->setJSON([
            'status' => 200,
            'data' => $tours,
            'ticket_stock' => $this->getTicketStockRates(),
        ]);
    }

    public function ongoingTours()
    {
        $consigneeModel = new ConsigneeModel();

        $today = date('Y-m-d');

        $tours = $consigneeModel
            ->select('tour_id, manual_id, consignee, passenger_count, start_date, end_date, is_template, template_id, template_name, transport_agent')
            ->where('start_date <=', $today)
            ->where('end_date >=', $today)
            ->where('tour_cancel', 0)
            ->where('is_template', 0)
            ->where('verify', 1)
            ->orderBy('start_date', 'ASC')
            ->findAll();

        $tours = $this->enrichTours($tours, withTickets: false, withMileageAndExpenses: true, withFinalize: true);
        $tours = $this->attachMeterReadingReminderStatus($tours, $today);

        return $this->response->setJSON(['status' => 200, 'data' => $tours]);
    }

    // Manual trigger — staff click "Send Reminders" in the Ongoing Tours panel. Recomputes
    // ongoing tours + reminder status fresh (doesn't trust client-cached data) and sends SMS,
    // returning a summary so the button can show what happened.
    public function sendReminders()
    {
        $consigneeModel = new ConsigneeModel();
        $today = date('Y-m-d');

        $tours = $consigneeModel
            ->select('tour_id, manual_id, consignee, passenger_count, start_date, end_date, is_template, template_id, template_name, transport_agent')
            ->where('start_date <=', $today)
            ->where('end_date >=', $today)
            ->where('tour_cancel', 0)
            ->where('is_template', 0)
            ->where('verify', 1)
            ->orderBy('start_date', 'ASC')
            ->findAll();

        $tours = $this->enrichTours($tours, withTickets: false, withMileageAndExpenses: true, withFinalize: true);
        $tours = $this->attachMeterReadingReminderStatus($tours, $today);
        $result = $this->sendMeterReadingReminders($tours, $today);

        if ($result['too_early']) {
            return $this->response->setJSON([
                'status' => 400,
                'message' => 'Reminders can only be sent from 12:00 PM Colombo time.',
            ]);
        }

        return $this->response->setJSON([
            'status' => 200,
            'sent_count' => count($result['sent']),
            'skipped_count' => count($result['skipped']),
            'sent' => $result['sent'],
            'skipped' => $result['skipped'],
        ]);
    }

    // Manual trigger — staff click the SMS icon next to one driver in the Ongoing Tours table.
    // Same eligibility rule and message content as the bulk sweep (missing today's start reading,
    // 12PM Colombo cutoff, deduped via sms_log), just scoped to a single tour+driver.
    public function sendReminderToDriver()
    {
        $tourId = trim((string) $this->request->getPost('tour_id'));
        $driverAcc = trim((string) $this->request->getPost('driver_acc'));

        if ($tourId === '' || $driverAcc === '') {
            return $this->response->setJSON(['status' => 400, 'message' => 'Tour and driver are required']);
        }

        date_default_timezone_set('Asia/Colombo');
        if ((int) date('H') < 12) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Reminders can only be sent from 12:00 PM Colombo time.']);
        }

        $today = date('Y-m-d');
        $consigneeModel = new ConsigneeModel();

        $tour = $consigneeModel
            ->select('tour_id, manual_id, consignee, passenger_count, start_date, end_date, is_template, template_id, template_name, transport_agent')
            ->where('tour_id', $tourId)
            ->where('tour_cancel', 0)
            ->first();

        if (!$tour) {
            return $this->response->setJSON(['status' => 404, 'message' => 'Tour not found']);
        }

        $tours = $this->enrichTours([$tour], withTickets: false, withMileageAndExpenses: true, withFinalize: true);
        $tours = $this->attachMeterReadingReminderStatus($tours, $today);
        $tour = $tours[0];

        $driver = null;
        foreach ($tour['drivers'] ?? [] as $candidate) {
            if ((string) ($candidate['driver'] ?? '') === $driverAcc) {
                $driver = $candidate;
                break;
            }
        }

        if (!$driver) {
            return $this->response->setJSON(['status' => 404, 'message' => 'Driver is not allocated to this tour']);
        }

        $smsController = new SmsController();
        $result = $this->attemptSendReminderToDriver($tour, $driver, $today, $smsController);

        if ($result === null) {
            return $this->response->setJSON(['status' => 400, 'message' => "Today's start reading has already been submitted for this driver."]);
        }

        if (!$result['ok']) {
            return $this->response->setJSON(['status' => 400, 'message' => $result['info']['reason'] ?? 'Failed to send reminder']);
        }

        return $this->response->setJSON(['status' => 200, 'message' => 'Reminder sent to ' . ($result['info']['driver_name'] ?? $driverAcc)]);
    }

    // For each driver on each ongoing tour, checks whether today's start reading is missing
    // and whether any earlier day of the tour (start_date..yesterday) is still incomplete
    // (missing start or end). Attaches this per-driver so the frontend can highlight rows
    // without re-deriving the same logic from daily_mileage.
    private function attachMeterReadingReminderStatus(array $tours, string $today): array
    {
        foreach ($tours as &$tour) {
            $mileageByDriverDate = [];
            foreach ($tour['daily_mileage'] ?? [] as $row) {
                $mileageByDriverDate[$row['driver_acc']][$row['date']] = $row;
            }

            $driverStatuses = [];
            foreach ($tour['drivers'] ?? [] as $driver) {
                $driverAcc = (string) ($driver['driver'] ?? '');
                if ($driverAcc === '') {
                    continue;
                }

                $readingsByDate = $mileageByDriverDate[$driverAcc] ?? [];
                $todayRow = $readingsByDate[$today] ?? null;
                $missingTodayStart = $todayRow === null || $todayRow['start_km'] === null;

                $missingPastDays = [];
                $cursor = strtotime($tour['start_date']);
                $yesterday = strtotime('-1 day', strtotime($today));
                while ($cursor !== false && $cursor <= $yesterday) {
                    $date = date('Y-m-d', $cursor);
                    $row = $readingsByDate[$date] ?? null;
                    $hasStart = $row && $row['start_km'] !== null;
                    $hasEnd = $row && $row['end_km'] !== null;
                    if (!$hasStart || !$hasEnd) {
                        $missingPastDays[] = date('d M', $cursor);
                    }
                    $cursor = strtotime('+1 day', $cursor);
                }

                $driverStatuses[$driverAcc] = [
                    'missing_today_start' => $missingTodayStart,
                    'missing_past_days' => $missingPastDays,
                ];
            }

            $tour['meter_reading_status_by_driver'] = $driverStatuses;
        }
        unset($tour);

        return $tours;
    }

    // Sends a one-time-per-day SMS reminder (Asia/Colombo, from 12:00 PM) to any driver on an
    // ongoing tour who hasn't submitted today's start meter reading. Triggered by staff loading/
    // refreshing the Ongoing Tours table (no cron available) — dedupes against sms_log so repeated
    // page loads in the same day don't re-send.
    private function sendMeterReadingReminders(array $tours, string $today): array
    {
        date_default_timezone_set('Asia/Colombo');

        if ((int) date('H') < 12) {
            return ['sent' => [], 'skipped' => [], 'too_early' => true];
        }

        $smsController = new SmsController();
        $sent = [];
        $skipped = [];

        foreach ($tours as $tour) {
            foreach ($tour['drivers'] ?? [] as $driver) {
                $result = $this->attemptSendReminderToDriver($tour, $driver, $today, $smsController);

                if ($result === null) {
                    continue;
                }

                if ($result['ok']) {
                    $sent[] = $result['info'];
                } else {
                    $skipped[] = $result['info'];
                }
            }
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'too_early' => false];
    }

    // Shared by the bulk "Send Reminders" sweep and the per-driver SMS icon: checks whether this
    // driver is actually missing today's start reading, dedupes against sms_log, builds the right
    // message variant, and sends. Returns null if this driver isn't eligible at all (not missing
    // today's start), otherwise ['ok' => bool, 'info' => [...]] describing what happened.
    private function attemptSendReminderToDriver(array $tour, array $driver, string $today, SmsController $smsController): ?array
    {
        $driverAcc = (string) ($driver['driver'] ?? '');
        $status = $tour['meter_reading_status_by_driver'][$driverAcc] ?? null;
        $driverName = $driver['driver_name'] ?? $driverAcc;

        if (!$status || !$status['missing_today_start']) {
            return null;
        }

        $mobile = trim((string) ($driver['driver_mobile'] ?? ''));
        if ($mobile === '') {
            return ['ok' => false, 'info' => ['tour_id' => $tour['tour_id'], 'driver_name' => $driverName, 'reason' => 'No mobile number on file']];
        }

        $db = db_connect();
        $alreadySent = $db->table('sms_log')
            ->where('type', 'Meter Reading Reminder')
            ->where('tour_id', $tour['tour_id'])
            ->where('to', $this->normalizeDriverMobile($mobile))
            ->where('time >=', $today . ' 00:00:00')
            ->countAllResults() > 0;

        if ($alreadySent) {
            return ['ok' => false, 'info' => ['tour_id' => $tour['tour_id'], 'driver_name' => $driverName, 'reason' => 'Already sent today']];
        }

        $manualId = $tour['manual_id'] ?? $tour['tour_id'];
        $missingPastDays = $status['missing_past_days'] ?? [];

        if (empty($missingPastDays)) {
            $message = "Reminder : Please submit todays start meter reading for Tour {$manualId} , and continue updateing meter readings and Expenses, kindly reminder from Avenir Holidays . we apprciate your effort . for further assitance Contact 0775834441";
        } else {
            $datesList = implode(', ', $missingPastDays);
            $message = "Reminder : Please submit meter reading for follwing days {$datesList} for Tour {$manualId} . Delay in Updaing Meter Readings and expenses could Cause delay in settlemnt payment kindly reminder from Avenir Holidays . we apprciate your effort . for further assitance Contact 0775834441";
        }

        $success = $smsController->sendSms($this->normalizeDriverMobile($mobile), $message, 'Meter Reading Reminder', $tour['tour_id'], null, null);

        if ($success) {
            return ['ok' => true, 'info' => ['tour_id' => $tour['tour_id'], 'manual_id' => $manualId, 'driver_name' => $driverName]];
        }

        return ['ok' => false, 'info' => ['tour_id' => $tour['tour_id'], 'driver_name' => $driverName, 'reason' => 'SMS send failed']];
    }

    // Matches the normalization used elsewhere (DriverAllocationController): strip spaces, a
    // leading 0 or +94, keep the last 9 digits — the same format SmsController::sendSms() expects
    // (it prepends a single '0' itself).
    private function normalizeDriverMobile(string $mobile): string
    {
        $mobile = str_replace(' ', '', $mobile);
        $mobile = preg_replace('/^(0|\+94)/', '', $mobile);
        return substr($mobile, -9);
    }

    public function completedTours()
    {
        $consigneeModel = new ConsigneeModel();

        $today = date('Y-m-d');
        $in2WeeksAgo = date('Y-m-d', strtotime('-2 weeks'));

        $startDateFrom = $this->request->getGet('start_date_from') ?: $in2WeeksAgo;
        $startDateTo = $this->request->getGet('start_date_to') ?: $today;

        $tours = $consigneeModel
            ->select('tour_id, manual_id, consignee, passenger_count, start_date, end_date, is_template, template_id, template_name, transport_agent, transport_settled')
            ->where('start_date >=', $startDateFrom)
            ->where('start_date <=', $startDateTo)
            ->where('end_date <', $today)
            ->where('tour_cancel', 0)
            ->where('is_template', 0)
            ->where('verify', 1)
            ->orderBy('start_date', 'DESC')
            ->findAll();

        $tours = $this->enrichTours($tours, withTickets: false, withMileageAndExpenses: true, withFinalize: true, withBackOffice: true);

        return $this->response->setJSON(['status' => 200, 'data' => $tours]);
    }

    // Count of tours (within the same Start Date filter range as completedTours()) whose driver
    // settlement is still pending — end_date at least 1 full day in the past (tour is definitively
    // over) and transport_settled is not yet 1.
    public function pendingSettlementCount()
    {
        $today = date('Y-m-d');
        $in2WeeksAgo = date('Y-m-d', strtotime('-2 weeks'));
        $cutoffDate = date('Y-m-d', strtotime('-1 day'));

        $startDateFrom = $this->request->getGet('start_date_from') ?: $in2WeeksAgo;
        $startDateTo = $this->request->getGet('start_date_to') ?: $today;

        $count = (new ConsigneeModel())
            ->where('start_date >=', $startDateFrom)
            ->where('start_date <=', $startDateTo)
            ->where('end_date <=', $cutoffDate)
            ->where('tour_cancel', 0)
            ->where('is_template', 0)
            ->where('verify', 1)
            ->groupStart()
                ->where('transport_settled !=', 1)
                ->orWhere('transport_settled', null)
            ->groupEnd()
            ->countAllResults();

        return $this->response->setJSON(['status' => 200, 'count' => $count]);
    }

    private function enrichTours(array $tours, bool $withTickets, bool $withMileageAndExpenses = false, bool $withFinalize = false, bool $withBackOffice = false): array
    {
        if (empty($tours)) {
            return [];
        }

        $tourIds = array_column($tours, 'tour_id');

        $driversByTour = $this->getDriversByTour($tourIds);
        $driverAccs = array_unique(array_column(array_merge(...array_values($driversByTour ?: [[]])), 'driver'));
        $vehiclesByDriver = $this->getVehiclesByDriver($driverAccs);
        foreach ($driversByTour as &$driverRows) {
            foreach ($driverRows as &$driverRow) {
                $driverRow['vehicles'] = $vehiclesByDriver[$driverRow['driver']] ?? [];
            }
            unset($driverRow);
        }
        unset($driverRows);
        $advancesByTour = $this->getAdvancesByTour($tourIds);
        $ticketsByTour = $withTickets ? $this->getTicketsByTour($tourIds) : [];
        $templateNamesById = $this->getTemplateNamesById($tours);
        $mileageByTour = $withMileageAndExpenses ? $this->getMileageByTour($tourIds) : [];
        $expensesByTour = $withMileageAndExpenses ? $this->getExpensesByTour($tourIds) : [];
        $finalizeByTour = $withFinalize ? $this->getFinalizeByTour($tourIds) : [];
        $backOfficeByTour = $withBackOffice ? $this->getBackOfficeExpensesByTour($tourIds) : [];
        $logSheetByTour = $withBackOffice ? $this->getLogSheetMileageByTour($tourIds) : [];

        foreach ($tours as &$tour) {
            $tourId = $tour['tour_id'];

            $tour['drivers'] = $driversByTour[$tourId] ?? [];
            $tour['driver_advances'] = $advancesByTour[$tourId] ?? [];

            if ($withTickets) {
                $tour['entrance_fees'] = $ticketsByTour[$tourId] ?? [];
                $tour['entrance_fees_total'] = array_sum(array_map(
                    fn($activity) => $activity['lkr_fee'] ?? $activity['fee'] ?? 0,
                    $tour['entrance_fees']
                ));
            }

            if ($withMileageAndExpenses) {
                $tour['daily_mileage'] = $mileageByTour[$tourId] ?? [];
                $tour['total_distance'] = round(array_sum(array_column($tour['daily_mileage'], 'distance')), 2);

                $tour['expenses'] = $expensesByTour[$tourId] ?? [];
                $tour['expense_count'] = count($tour['expenses']);
                $tour['total_lkr'] = round(array_sum(array_column($tour['expenses'], 'lkr_amount')), 2);
            }

            if ($withFinalize) {
                $finalizeRecords = $finalizeByTour[$tourId] ?? [];
                $tour['finalize_records'] = $finalizeRecords;
                $tour['is_finalized'] = count($finalizeRecords) > 0;
                $tour['final_distance'] = round(array_sum(array_column($finalizeRecords, 'distance_total')), 2);
                $tour['final_lkr'] = round(array_sum(array_column($finalizeRecords, 'total_lkr_amount')), 2);
            }

            if ($withBackOffice) {
                $backOfficeRecords = $backOfficeByTour[$tourId] ?? [];
                $tour['back_office_expenses'] = $backOfficeRecords;
                $tour['back_office_count'] = count($backOfficeRecords);
                $tour['back_office_total_lkr'] = round(array_sum(array_column($backOfficeRecords, 'lkr_amount')), 2);
                $tour['back_office_pending_count'] = count(array_filter($backOfficeRecords, fn($e) => $e['normalized_status'] === 'pending'));
                $tour['back_office_approved_count'] = count(array_filter($backOfficeRecords, fn($e) => $e['normalized_status'] === 'approved'));

                $logSheetRecords = $logSheetByTour[$tourId] ?? [];
                $tour['log_sheet_mileage_records'] = $logSheetRecords;
                $tour['back_office_total_mileage'] = round(array_sum(array_column($logSheetRecords, 'log_sheet_mileage')), 2);
                $tour['back_office_total_estimate_mileage'] = round(array_sum(array_column($logSheetRecords, 'estimate_mileage')), 2);
            }

            if (!empty($tour['template_id']) && isset($templateNamesById[$tour['template_id']])) {
                $tour['template_name'] = $templateNamesById[$tour['template_id']];
            }
        }
        unset($tour);

        return $tours;
    }

    private function getFinalizeByTour(array $tourIds): array
    {
        if (empty($tourIds)) {
            return [];
        }

        $finalizeModel = new TourFinalizeModel();

        $rows = $finalizeModel
            ->select('tour_id, driver_acc, driver_name, expense_count, distance_total, total_dollar_amount, total_lkr_amount, status, finalized_by, finalized_at')
            ->whereIn('tour_id', $tourIds)
            ->orderBy('finalized_at', 'ASC')
            ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['tour_id']][] = $row;
        }

        return $grouped;
    }

    private function getMileageByTour(array $tourIds): array
    {
        if (empty($tourIds)) {
            return [];
        }

        $readingModel = new DriverMeterReadingModel();

        $rows = $readingModel
            ->select('driver_meter_reading.id, driver_meter_reading.tour_id, driver_meter_reading.driver_acc, driver_meter_reading.reading_date, driver_meter_reading.type, driver_meter_reading.km, account_posting.PName as driver_name')
            ->join('account_posting', 'account_posting.L_Code = driver_meter_reading.driver_acc', 'left')
            ->whereIn('driver_meter_reading.tour_id', $tourIds)
            ->orderBy('driver_meter_reading.reading_date', 'ASC')
            ->findAll();

        // Group by tour, then by driver+date so start/end pairs match per driver per day
        $byTourDriverDate = [];
        foreach ($rows as $row) {
            $byTourDriverDate[$row['tour_id']][$row['driver_acc']]['name'] = $row['driver_name'] ?? $row['driver_acc'];
            $byTourDriverDate[$row['tour_id']][$row['driver_acc']]['dates'][$row['reading_date']][$row['type']] = $row;
        }

        $grouped = [];
        foreach ($byTourDriverDate as $tourId => $byDriver) {
            foreach ($byDriver as $driverAcc => $driverData) {
                foreach ($driverData['dates'] as $date => $readings) {
                    $startKm = isset($readings['start']) ? (float) $readings['start']['km'] : null;
                    $endKm = isset($readings['end']) ? (float) $readings['end']['km'] : null;
                    $distance = ($startKm !== null && $endKm !== null) ? max(0, $endKm - $startKm) : 0;

                    $grouped[$tourId][] = [
                        'driver_acc' => $driverAcc,
                        'driver_name' => $driverData['name'],
                        'date' => $date,
                        'start_km' => $startKm,
                        'end_km' => $endKm,
                        'start_id' => $readings['start']['id'] ?? null,
                        'end_id' => $readings['end']['id'] ?? null,
                        'distance' => $distance,
                    ];
                }
            }
        }

        return $grouped;
    }

    // Matches App\Controllers\Driver\DriverPortalController::getExpenseCategories()
    private const EXPENSE_CATEGORY_LABELS = [
        '6-9-23-10640' => 'Entrance Fee',
        '6-9-23-10641' => 'Other',
    ];

    private function getExpensesByTour(array $tourIds): array
    {
        if (empty($tourIds)) {
            return [];
        }

        $expenseModel = new DriverPortalExpensesModel();

        $rows = $expenseModel
            ->select('tour_id, agent_name, category, supplier_name, invoice_no, expense_date, lkr_amount, status')
            ->whereIn('tour_id', $tourIds)
            ->orderBy('expense_date', 'ASC')
            ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $row['category_label'] = self::EXPENSE_CATEGORY_LABELS[$row['category']] ?? $row['category'];
            $grouped[$row['tour_id']][] = $row;
        }

        return $grouped;
    }

    private function getBackOfficeExpensesByTour(array $tourIds): array
    {
        if (empty($tourIds)) {
            return [];
        }

        $expenseModel = new DriverExpensesModel();

        $rows = $expenseModel
            ->select('tour_id, agent, agent_name, category, supplier_name, invoice_no, expense_date, dollar_rate, dollar_amount, lkr_amount, status, approved_by, approved_at, rejected_by, rejected_at, rejection_reason, created_by, created_at')
            ->whereIn('tour_id', $tourIds)
            ->whereIn('status', ['', 'pending', 'posted', 'approved'])
            ->orderBy('expense_date', 'ASC')
            ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $row['category_label'] = self::EXPENSE_CATEGORY_LABELS[$row['category']] ?? $row['category'];
            // Live data uses '' (not yet posted) and 'posted' (posted to accounts) alongside the
            // documented pending/approved/rejected values — normalize both schemes to one status.
            $row['normalized_status'] = match ($row['status']) {
                '', 'pending' => 'pending',
                'posted', 'approved' => 'approved',
                'rejected' => 'rejected',
                default => $row['status'],
            };
            $grouped[$row['tour_id']][] = $row;
        }

        return $grouped;
    }

    // Mirrors DriverLogsheetController::get_logsheet_mileage() — latest log_sheet_mile row
    // per (tour_id, transport_agent) is the admin-confirmed final mileage figure.
    private function getLogSheetMileageByTour(array $tourIds): array
    {
        if (empty($tourIds)) {
            return [];
        }

        $logSheetModel = new LogSheetMileModel();

        $rows = $logSheetModel
            ->select('id, tour_id, transport_agent, log_sheet_mileage, previous_mileage, is_override, estimate_mileage, vehicle_rate, per_day_rate, no_of_dates, date')
            ->whereIn('tour_id', $tourIds)
            ->orderBy('id', 'DESC')
            ->findAll();

        // Keep only the latest row per (tour_id, transport_agent) — earlier rows for the same
        // pair are superseded, matching the ->orderBy('id','desc')->first() lookup used elsewhere.
        $latestByTourAgent = [];
        foreach ($rows as $row) {
            $key = $row['tour_id'] . '|' . $row['transport_agent'];
            if (!isset($latestByTourAgent[$key])) {
                $latestByTourAgent[$key] = $row;
            }
        }

        $grouped = [];
        foreach ($latestByTourAgent as $row) {
            $grouped[$row['tour_id']][] = $row;
        }

        return $grouped;
    }

    private function getDriversByTour(array $tourIds): array
    {
        $allocationModel = new DriverAllocationModel();

        $rows = $allocationModel
            ->select('driver_allocation.tour_id, driver_allocation.driver, driver_allocation.driver_name, driver_allocation.allow_date, supplier.Mobile as driver_mobile')
            ->join('supplier', 'supplier.Sup_Acc_No = driver_allocation.driver', 'left')
            ->whereIn('driver_allocation.tour_id', $tourIds)
            ->orderBy('driver_allocation.allow_date', 'ASC')
            ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['tour_id']][] = $row;
        }

        return $grouped;
    }

    // vehicle.owner stores the driver's account code (supplier.Sup_Acc_No) — a driver can own more than one vehicle
    private function getVehiclesByDriver(array $driverAccs): array
    {
        if (empty($driverAccs)) {
            return [];
        }

        $rows = (new VehicleModel())
            ->select('owner, type, v_num')
            ->whereIn('owner', $driverAccs)
            ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['owner']][] = $row;
        }

        return $grouped;
    }

    private function getAdvancesByTour(array $tourIds): array
    {
        if (empty($tourIds)) {
            return [];
        }

        $advanceModel = new DriverAdvanceRequestModel();

        $rows = $advanceModel
            ->select('driver_advance_request.id, driver_advance_request.tour_id, driver_advance_request.driver, driver_advance_request.driver_name,
                      driver_advance_request.amount AS requested_amount, driver_advance_request.username AS requested_by,
                      driver_advance_request.date AS request_date, driver_advance_request.flag AS request_status,
                      payment_request.requestAmount AS approved_amount,
                      payment_request.payment_request_user AS authenticated_by,
                      payment_request.payment_request_date AS authentication_date,
                      account_runing.Dr AS paid_amount, account_runing.Date AS payment_date')
            ->join('payment_request', 'payment_request.tour_id = driver_advance_request.tour_id AND payment_request.hotel_acc = driver_advance_request.driver', 'left')
            ->join('account_runing', 'account_runing.index_no = driver_advance_request.tour_id AND account_runing.L_Code = driver_advance_request.driver AND account_runing.Dr > 0', 'left')
            ->whereIn('driver_advance_request.tour_id', $tourIds)
            ->orderBy('driver_advance_request.date', 'DESC')
            ->findAll();

        $grouped = [];
        $seenIds = [];
        foreach ($rows as $row) {
            if (isset($seenIds[$row['id']])) {
                continue;
            }
            $seenIds[$row['id']] = true;

            if ($row['paid_amount'] && $row['payment_date']) {
                $status = 'paid';
            } elseif ($row['authenticated_by']) {
                $status = 'approved';
            } else {
                $status = 'requested';
            }

            $row['status'] = $status;
            $grouped[$row['tour_id']][] = $row;
        }

        return $grouped;
    }

    // entrance_fee.id 1 = SIGIRIYA, 34 = POLONNARUWA SS — the only required entrance tickets tracked on this panel
    private const REQUIRED_TICKET_ENTRANCE_FEE_IDS = [1, 34];

    // entrance_fee.id -> usage_note.item_code — usage_note is 100% dedicated to these two tickets
    private const ENTRANCE_FEE_TO_ITEM_CODE = [1 => '0001', 34 => '0002'];

    private function getTicketsByTour(array $tourIds): array
    {
        $activityModel = new TourActivityModel();

        $rows = $activityModel
            ->select('tour_activity.id as activity_id, tour_activity.tour_id, tour_activity.tour_date, tour_activity.fee, tour_activity.lkr_fee, tour_activity.payment_request, tour_activity.name as entrance_fee_id, entrance_fee.name as place_name, entrance_fee.entranceFee as unit_price')
            ->join('entrance_fee', 'entrance_fee.id = tour_activity.name', 'left')
            ->whereIn('tour_activity.tour_id', $tourIds)
            ->whereIn('tour_activity.name', self::REQUIRED_TICKET_ENTRANCE_FEE_IDS)
            ->orderBy('tour_activity.tour_date', 'ASC')
            ->findAll();

        $usageNoteByActivity = $this->getUsageNoteStatusByActivity(array_column($rows, 'activity_id'));

        $grouped = [];
        foreach ($rows as $row) {
            $itemCode = self::ENTRANCE_FEE_TO_ITEM_CODE[(int) $row['entrance_fee_id']] ?? null;
            $row['item_code'] = $itemCode;
            $row['usage_note'] = $usageNoteByActivity[$row['activity_id']] ?? null;
            $grouped[$row['tour_id']][] = $row;
        }

        return $grouped;
    }

    // Latest usage_note row per tour_activity (a tour_activity can end up with more than one
    // usage_note over time — e.g. a rejected one followed by a resubmission).
    private function getUsageNoteStatusByActivity(array $activityIds): array
    {
        $activityIds = array_values(array_unique(array_filter($activityIds)));
        if (empty($activityIds)) {
            return [];
        }

        $rows = (new UsageNoteModel())
            ->select('id, activity_id, usage_note_no, status')
            ->whereIn('activity_id', $activityIds)
            ->orderBy('id', 'ASC')
            ->findAll();

        $byActivity = [];
        foreach ($rows as $row) {
            // later rows overwrite earlier ones, so the latest usage_note per activity wins
            $byActivity[$row['activity_id']] = [
                'usage_note_no' => $row['usage_note_no'],
                'status' => $row['status'],
            ];
        }

        return $byActivity;
    }

    // Current stock balance + rate for the two ticket stock items (Stock_Card 0001/0002),
    // sourced from inventory_stock_view — the same view app/Controllers/Grn/InventoryStockController.php
    // uses for the main Inventory Stock page, so figures match exactly what stock staff see there.
    private function getTicketStockRates(): array
    {
        $itemCodes = array_values(self::ENTRANCE_FEE_TO_ITEM_CODE);

        $db = db_connect();
        $placeholders = implode(',', array_fill(0, count($itemCodes), '?'));

        $rows = $db->query(
            "SELECT Stock_Card, item_description, current_balance, avg_cost
             FROM inventory_stock_view
             WHERE Stock_Card IN ({$placeholders})",
            $itemCodes
        )->getResultArray();

        $result = [];
        foreach ($rows as $row) {
            $code = $row['Stock_Card'];
            $result[$code] = [
                'item_code' => $code,
                'description' => $row['item_description'],
                'available_qty' => (float) ($row['current_balance'] ?? 0),
                'current_rate' => (float) ($row['avg_cost'] ?? 0),
            ];
        }

        return $result;
    }

    private function getTemplateNamesById(array $tours): array
    {
        $templateIds = array_filter(array_column($tours, 'template_id'));

        if (empty($templateIds)) {
            return [];
        }

        $consigneeModel = new ConsigneeModel();

        $templates = $consigneeModel
            ->select('tour_id, consignee')
            ->whereIn('tour_id', array_unique($templateIds))
            ->findAll();

        $map = [];
        foreach ($templates as $template) {
            $map[$template['tour_id']] = $template['consignee'];
        }

        return $map;
    }
}
