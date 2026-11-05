<?php

namespace App\Controllers\Driver;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\DriverAllocationModel;
use App\Models\ConsigneeModel;
use App\Models\DriverDailyMileageAmendmentModel;
use App\Models\DriverExpensesModel;
use App\Models\DriverMeterReadingModel;
use App\Models\DriverPortalExpensesModel;
use App\Models\LogSheetMileModel;
use App\Models\SupplierModel;
use App\Models\TourFinalizeModel;
use App\Models\TourQuotationsModel;
use App\Models\TourSubLocationsModel;
use App\Models\VoucherPaymentModel;

class DriverPortalController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    public function index()
    {
        $data = [];
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $driver_acc = session()->get('acc_no');
        $today = date('Y-m-d');

        $allocationModel = new DriverAllocationModel();
        $allocations = $allocationModel->where('driver', $driver_acc)->findAll();

        $driverName = '';
        foreach ($allocations as $alloc) {
            if (!empty($alloc['driver_name'])) {
                $driverName = $alloc['driver_name'];
                break;
            }
        }
        if ($driverName === '' && $driver_acc) {
            $supplier = (new SupplierModel())->where('Sup_Acc_No', $driver_acc)->first();
            $driverName = $supplier['Name'] ?? '';
        }
        $data['driver_name'] = trim($driverName);

        $upcoming = [];
        $data['expense_categories'] = $this->getExpenseCategories();
        $data['expense_accounts_by_category'] = $this->getExpenseAccountsByCategory();
        $expenseCategories = $data['expense_categories'];

        if (!empty($allocations)) {
            $consigneeModel = new ConsigneeModel();
            $readingModel   = new DriverMeterReadingModel();
            $expenseModel   = new DriverPortalExpensesModel();
            $tourQuotationModel = new TourQuotationsModel();
            $logSheetMileModel = new LogSheetMileModel();

            foreach ($allocations as $alloc) {
                $tour = $consigneeModel->where('tour_id', $alloc['tour_id'])->first();
                if (!$tour) continue;

                // Never show cancelled tours
                if ((int) ($tour['tour_cancel'] ?? 0) === 1) continue;

                // Once this driver has finalized their part of the tour, drop it from the
                // dashboard — nothing left for them to do. Checked per-driver, not tour-wide, so
                // a second driver on the same multi-driver tour still sees it until they finalize too.
                if ($this->isTourExpenseLocked($alloc['tour_id'], (string) ($alloc['driver'] ?? $driver_acc))) continue;

                // Visible only while the tour is current: started on/before today, and today is
                // no more than 2 days past the tour's end_date.
                if ($tour['start_date'] > $today) continue;
                $visibleUntil = date('Y-m-d', strtotime($tour['end_date'] . ' +2 days'));
                if ($today > $visibleUntil) continue;

                // Build date range
                $dates = [];
                $start = new \DateTime($tour['start_date']);
                $end   = new \DateTime($tour['end_date']);
                $end->modify('+1 day');
                foreach (new \DatePeriod($start, new \DateInterval('P1D'), $end) as $dt) {
                    $dates[] = $dt->format('Y-m-d');
                }

                // Load readings indexed by date+type
                $raw = $readingModel->where('tour_id', $alloc['tour_id'])->where('driver_acc', $driver_acc)->findAll();
                $readings = [];
                foreach ($raw as $r) {
                    $readings[$r['reading_date']][$r['type']] = $r;
                }

                // Tours can be split across multiple drivers (e.g. one driver covers days 1-3,
                // another covers the last day only). Once another driver has fully covered a day
                // (both start and end), that day isn't this driver's to touch — hide it from their
                // dashboard entirely instead of showing an empty, still-actionable day card.
                $otherDriverReadings = $readingModel
                    ->select('reading_date, type')
                    ->where('tour_id', $alloc['tour_id'])
                    ->where('driver_acc !=', $driver_acc)
                    ->findAll();
                $otherReadingsByDate = [];
                foreach ($otherDriverReadings as $r) {
                    $otherReadingsByDate[$r['reading_date']][$r['type']] = true;
                }
                $datesTakenByOtherDrivers = [];
                foreach ($otherReadingsByDate as $date => $dayReadings) {
                    if (!empty($dayReadings['start']) && !empty($dayReadings['end'])) {
                        $datesTakenByOtherDrivers[$date] = true;
                    }
                }

                $completedReadingDateCount = 0;
                foreach ($readings as $dayReadings) {
                    if (!empty($dayReadings['start']) && !empty($dayReadings['end'])) {
                        $completedReadingDateCount++;
                    }
                }

                $dateIds = [];
                $tourDateRows = $tourQuotationModel
                    ->select('id, tour_date')
                    ->where('quotation_id', $alloc['tour_id'])
                    ->orderBy('id', 'ASC')
                    ->findAll();

                foreach ($tourDateRows as $tourDateRow) {
                    $tourDate = trim((string) ($tourDateRow['tour_date'] ?? ''));
                    if ($tourDate !== '' && !isset($dateIds[$tourDate])) {
                        $dateIds[$tourDate] = (int) $tourDateRow['id'];
                    }
                }

                // Tours can be split across multiple drivers, and driver_portal_expenses has no
                // per-driver visibility filter otherwise — without scoping by agent, one driver's
                // dashboard would show (and count) the other driver's already-finalized expenses.
                $expenseCounts = [];
                $expenseItems = [];
                $expenseRows = $expenseModel
                    ->select('id, expense_date, category, supplier, supplier_name')
                    ->where('tour_id', $alloc['tour_id'])
                    ->where('agent', (string) ($alloc['driver'] ?? $driver_acc))
                    ->orderBy('expense_date', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->findAll();

                foreach ($expenseRows as $expenseRow) {
                    $expenseDate = trim((string) ($expenseRow['expense_date'] ?? ''));
                    if ($expenseDate === '') {
                        continue;
                    }

                    $expenseCounts[$expenseDate] = ($expenseCounts[$expenseDate] ?? 0) + 1;

                    $expenseId = (int) ($expenseRow['id'] ?? 0);
                    $expenseItems[$expenseDate][] = [
                        'category_label' => $expenseCategories[$expenseRow['category'] ?? ''] ?? ($expenseRow['category'] ?? ''),
                        'supplier_name' => $expenseRow['supplier_name'] ?? ($expenseRow['supplier'] ?? ''),
                        'invoice_image_src' => $expenseId > 0 ? $this->getExpenseImageUrl($alloc['tour_id'], $expenseId) : '',
                    ];
                }

                // Tours can be split across multiple drivers (e.g. one driver covers days 1-3,
                // another covers the last day only), and driver_allocation carries no per-driver
                // day range. So this only requires readings to be complete for days THIS driver
                // has already started a reading for, not every calendar day of the whole tour —
                // same scoping as DriverPortalController::getDaysMissingMeterReadings() used by
                // TourFinalize() itself. A driver with zero readings still can't finalize (blocked
                // separately by $tour['readings_complete'] below combined with completedReadingDateCount).
                $missingReadingDays = [];
                foreach ($readings as $date => $dayReadings) {
                    $hasStart = !empty($dayReadings['start']);
                    $hasEnd = !empty($dayReadings['end']);
                    if (!$hasStart || !$hasEnd) {
                        $missingReadingDays[] = date('d M', strtotime($date));
                    }
                }
                sort($missingReadingDays);

                $dates = array_values(array_filter($dates, function ($date) use ($datesTakenByOtherDrivers, $readings) {
                    return !isset($datesTakenByOtherDrivers[$date]) || isset($readings[$date]);
                }));

                $tour['dates']    = $dates;
                $tour['date_ids'] = $dateIds;
                $tour['expense_counts'] = $expenseCounts;
                $tour['expense_items'] = $expenseItems;
                $tour['readings'] = $readings;
                $tour['allocated_driver'] = $alloc['driver'];
                $tour['allocated_driver_name'] = $alloc['driver_name'] ?? '';
                $tour['distance_total'] = $this->getTourDistanceTotal($alloc['tour_id'], (string) ($alloc['driver'] ?? ''));
                $tour['completed_reading_dates_count'] = $completedReadingDateCount;
                $tour['missing_reading_days'] = $missingReadingDays;
                $tour['readings_complete'] = empty($missingReadingDays) && $completedReadingDateCount > 0;
                $tour['pending_expense_count'] = $this->getPendingExpenseCount($alloc['tour_id'], (string) ($alloc['driver'] ?? ''));
                $tour['expense_locked'] = $this->isTourExpenseLocked($alloc['tour_id'], (string) ($alloc['driver'] ?? ''));
                $upcoming[] = $tour;
            }
        }

        usort($upcoming, fn($a, $b) => strcmp($b['start_date'], $a['start_date']));

        $data['upcoming'] = $upcoming;
        return view('pages/driver/portal_dashboard', $data);
    }

    public function save_meter_reading()
    {
        try {
            $driver_acc   = session()->get('acc_no');
            if (!$driver_acc || !session()->get('logged_in')) {
                return $this->response->setJSON(['status' => 401, 'message' => 'Please login to continue']);
            }

            $tour_id      = $this->request->getPost('tour_id');
            $reading_date = $this->request->getPost('reading_date');
            $type         = $this->request->getPost('type'); // 'start' or 'end'
            $km           = $this->request->getPost('km');
            $notes        = $this->request->getPost('notes');

            if (!$tour_id || !$reading_date || !$type || $km === null || $km === '') {
                return $this->response->setJSON(['status' => 400, 'message' => 'Required fields missing']);
            }

            if (!$this->getDriverAllocation($tour_id, $driver_acc)) {
                return $this->response->setJSON(['status' => 403, 'message' => 'Access denied']);
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

    public function save_expense()
    {
        try {
            $driver_acc = session()->get('acc_no');
            if (!$driver_acc || !session()->get('logged_in')) {
                return $this->response->setJSON(['status' => 401, 'message' => 'Please login to continue']);
            }

            $tour_id       = trim((string) $this->request->getPost('tour_id'));
            $tour_date_id  = trim((string) $this->request->getPost('tour_date_id'));
            $expense_date  = trim((string) $this->request->getPost('expense_date'));
            $category      = trim((string) $this->request->getPost('category'));
            $supplier      = trim((string) $this->request->getPost('supplier'));
            $invoice_no    = trim((string) $this->request->getPost('invoice_no'));
            $description   = trim((string) $this->request->getPost('description'));
            $dollar_rate   = trim((string) $this->request->getPost('dollar_rate'));
            $dollar_amount = trim((string) $this->request->getPost('dollar_amount'));
            $lkr_amount    = trim((string) $this->request->getPost('lkr_amount'));

            if (
                $tour_id === '' || $expense_date === '' || $category === '' || $supplier === ''
            ) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Please fill all required fields']);
            }

            if (!$this->isValidDate($expense_date)) {
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

            $computedLkr = ($dollarRateValue > 0 && $dollarAmountValue > 0)
                ? round($dollarRateValue * $dollarAmountValue, 2)
                : $lkrAmountValue;

            $allocation = $this->getDriverAllocation($tour_id, $driver_acc);
            if (!$allocation) {
                return $this->response->setJSON(['status' => 403, 'message' => 'Access denied']);
            }

            $expenseDescriptions = $this->getExpenseDescriptions();
            if (!isset($expenseDescriptions[$category])) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid expense category']);
            }

            $supplierName = null;
            $allowedAccounts = $this->getExpenseAccountsByCategory()[$category] ?? [];
            foreach ($allowedAccounts as $account) {
                if (($account['L_Code'] ?? '') === $supplier) {
                    $supplierName = $account['PName'] ?? $supplier;
                    break;
                }
            }

            if ($supplierName === null) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid expense account']);
            }

            $consigneeModel = new ConsigneeModel();
            $tour = $consigneeModel->select('tour_cancel')->where('tour_id', $tour_id)->first();
            if (!$tour) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Tour not found']);
            }

            if ((int) ($tour['tour_cancel'] ?? 0) === 1) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot add expense. The tour has been cancelled.']);
            }

            $resolvedTourDateId = $this->resolveTourDateId($tour_id, $expense_date, $tour_date_id);
            if ($resolvedTourDateId === null) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Selected tour date reference is invalid']);
            }

            $expenseModel = new DriverPortalExpensesModel();
            $isLocked = $this->isTourExpenseLocked($tour_id, (string) ($allocation['driver'] ?? $driver_acc));

            if ($isLocked) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot add expense. A request has already been sent for approval.']);
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

            $saveData = [
                'tour_id' => $tour_id,
                'tour_date_id' => $resolvedTourDateId,
                'agent' => $allocation['driver'],
                'agent_name' => $allocation['driver_name'] ?? session()->get('username'),
                'category' => $category,
                'supplier' => $supplier,
                'supplier_name' => $supplierName,
                'invoice_no' => $invoice_no,
                'expense_date' => $expense_date,
                'dollar_rate' => $dollarRateValue,
                'dollar_amount' => $dollarAmountValue,
                'lkr_amount' => $computedLkr,
                'description' => $description !== '' ? $description : $expenseDescriptions[$category],
                'status' => 'pending',
                'request_send' => 0,
                'created_by' => session()->get('username'),
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $expenseId = $expenseModel->insert($saveData, true);
            if (!$expenseId) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Failed to save expense']);
            }

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

    public function update_expense()
    {
        try {
            $driver_acc = session()->get('acc_no');
            if (!$driver_acc || !session()->get('logged_in')) {
                return $this->response->setJSON(['status' => 401, 'message' => 'Please login to continue']);
            }

            $expense_id     = (int) $this->request->getPost('expense_id');
            $tour_id        = trim((string) $this->request->getPost('tour_id'));
            $tour_date_id   = trim((string) $this->request->getPost('tour_date_id'));
            $expense_date   = trim((string) $this->request->getPost('expense_date'));
            $category       = trim((string) $this->request->getPost('category'));
            $supplier       = trim((string) $this->request->getPost('supplier'));
            $invoice_no     = trim((string) $this->request->getPost('invoice_no'));
            $description    = trim((string) $this->request->getPost('description'));
            $dollar_rate    = trim((string) $this->request->getPost('dollar_rate'));
            $dollar_amount  = trim((string) $this->request->getPost('dollar_amount'));
            $lkr_amount     = trim((string) $this->request->getPost('lkr_amount'));

            if (
                $expense_id <= 0 || $tour_id === '' || $expense_date === '' || $category === '' ||
                $supplier === ''
            ) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Please fill all required fields']);
            }

            if (!$this->isValidDate($expense_date)) {
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

            $computedLkr = ($dollarRateValue > 0 && $dollarAmountValue > 0)
                ? round($dollarRateValue * $dollarAmountValue, 2)
                : $lkrAmountValue;

            $allocation = $this->getDriverAllocation($tour_id, $driver_acc);
            if (!$allocation) {
                return $this->response->setJSON(['status' => 403, 'message' => 'Access denied']);
            }

            $expenseModel = new DriverPortalExpensesModel();
            $existingExpense = $expenseModel
                ->where('id', $expense_id)
                ->where('tour_id', $tour_id)
                ->where('agent', $allocation['driver'])
                ->first();

            if (!$existingExpense) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Expense not found']);
            }

            if (!$this->canEditExpense($existingExpense)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'This expense cannot be edited']);
            }

            $isLocked = $this->isTourExpenseLocked($tour_id, (string) ($allocation['driver'] ?? $driver_acc));

            if ($isLocked) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot edit expense. A request has already been sent for approval.']);
            }

            $expenseDescriptions = $this->getExpenseDescriptions();
            if (!isset($expenseDescriptions[$category])) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid expense category']);
            }

            $supplierName = $this->resolveExpenseSupplierName($category, $supplier);
            if ($supplierName === null) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid expense account']);
            }

            $consigneeModel = new ConsigneeModel();
            $tour = $consigneeModel->select('tour_cancel')->where('tour_id', $tour_id)->first();
            if (!$tour) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Tour not found']);
            }

            if ((int) ($tour['tour_cancel'] ?? 0) === 1) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot edit expense. The tour has been cancelled.']);
            }

            $resolvedTourDateId = $this->resolveTourDateId($tour_id, $expense_date, $tour_date_id);
            if ($resolvedTourDateId === null) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Selected tour date reference is invalid']);
            }

            $invoiceImage = $this->request->getFile('invoice_image');
            if ($invoiceImage && $invoiceImage->getError() !== UPLOAD_ERR_NO_FILE) {
                $imageValidationMessage = $this->validateExpenseImageFile($invoiceImage);
                if ($imageValidationMessage !== null) {
                    return $this->response->setJSON(['status' => 400, 'message' => $imageValidationMessage]);
                }
            }

            $updateData = [
                'tour_date_id' => $resolvedTourDateId,
                'category' => $category,
                'supplier' => $supplier,
                'supplier_name' => $supplierName,
                'invoice_no' => $invoice_no,
                'expense_date' => $expense_date,
                'dollar_rate' => $dollarRateValue,
                'dollar_amount' => $dollarAmountValue,
                'lkr_amount' => $computedLkr,
                'description' => $description !== '' ? $description : $expenseDescriptions[$category],
                'updated_by' => session()->get('username'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if (!$expenseModel->update($expense_id, $updateData)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Failed to update expense']);
            }

            if ($invoiceImage && $invoiceImage->getError() !== UPLOAD_ERR_NO_FILE) {
                $uploadPath = $this->ensureExpenseUploadPath($tour_id);
                $extension = strtolower((string) ($invoiceImage->getExtension() ?: $invoiceImage->guessExtension() ?: 'jpg'));
                $imageName = $expense_id . '.' . $extension;

                if (!$invoiceImage->move($uploadPath, $imageName, true)) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'Failed to update invoice image']);
                }

                $this->deleteExpenseImages($tour_id, $expense_id, $imageName);
            }

            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Expense updated successfully',
                'previous_expense_date' => $existingExpense['expense_date'] ?? '',
                'current_expense_date' => $expense_date,
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function get_expenses()
    {
        try {
            $driver_acc = session()->get('acc_no');
            if (!$driver_acc || !session()->get('logged_in')) {
                return $this->response->setJSON(['status' => 401, 'message' => 'Please login to continue']);
            }

            $tour_id = trim((string) $this->request->getPost('tour_id'));
            $expense_date = trim((string) $this->request->getPost('expense_date'));

            if ($tour_id === '' || $expense_date === '') {
                return $this->response->setJSON(['status' => 400, 'message' => 'Tour and expense date are required']);
            }

            if (!$this->isValidDate($expense_date)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid expense date']);
            }

            if (!$this->getDriverAllocation($tour_id, $driver_acc)) {
                return $this->response->setJSON(['status' => 403, 'message' => 'Access denied']);
            }

            $expenseCategories = $this->getExpenseCategories();
            $expenses = (new DriverPortalExpensesModel())
                ->where('tour_id', $tour_id)
                ->where('expense_date', $expense_date)
                ->orderBy('id', 'ASC')
                ->findAll();

            $formattedExpenses = [];
            foreach ($expenses as $expense) {
                $expenseId = (int) ($expense['id'] ?? 0);
                $formattedExpenses[] = [
                    'id' => $expenseId,
                    'tour_id' => $expense['tour_id'] ?? $tour_id,
                    'tour_date_id' => $expense['tour_date_id'] ?? null,
                    'expense_date' => $expense['expense_date'] ?? $expense_date,
                    'category' => $expense['category'] ?? '',
                    'category_label' => $expenseCategories[$expense['category'] ?? ''] ?? ($expense['category'] ?? ''),
                    'supplier' => $expense['supplier'] ?? '',
                    'supplier_name' => $expense['supplier_name'] ?? ($expense['supplier'] ?? ''),
                    'invoice_no' => $expense['invoice_no'] ?? '',
                    'description' => $expense['description'] ?? '',
                    'dollar_rate' => round((float) ($expense['dollar_rate'] ?? 0), 2),
                    'dollar_amount' => round((float) ($expense['dollar_amount'] ?? 0), 2),
                    'lkr_amount' => round((float) ($expense['lkr_amount'] ?? 0), 2),
                    'invoice_image_src' => $expenseId > 0 ? $this->getExpenseImageUrl($tour_id, $expenseId) : '',
                    'status' => $expense['status'] ?? '',
                    'request_send' => (int) ($expense['request_send'] ?? 0),
                    'can_edit' => $this->canEditExpense($expense),
                    'source' => 'saved',
                ];
            }

            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Expenses loaded successfully',
                'data' => $formattedExpenses,
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function delete_expense()
    {
        try {
            $driver_acc = session()->get('acc_no');
            if (!$driver_acc || !session()->get('logged_in')) {
                return $this->response->setJSON(['status' => 401, 'message' => 'Please login to continue']);
            }

            $expense_id = (int) $this->request->getPost('expense_id');
            $tour_id = trim((string) $this->request->getPost('tour_id'));

            if ($expense_id <= 0 || $tour_id === '') {
                return $this->response->setJSON(['status' => 400, 'message' => 'Expense and tour are required']);
            }

            $allocation = $this->getDriverAllocation($tour_id, $driver_acc);
            if (!$allocation) {
                return $this->response->setJSON(['status' => 403, 'message' => 'Access denied']);
            }

            $expenseModel = new DriverPortalExpensesModel();
            $existingExpense = $expenseModel
                ->where('id', $expense_id)
                ->where('tour_id', $tour_id)
                ->where('agent', $allocation['driver'])
                ->first();

            if (!$existingExpense) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Expense not found']);
            }

            if (!$this->canEditExpense($existingExpense)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'This expense cannot be deleted']);
            }

            $isLocked = $this->isTourExpenseLocked($tour_id, (string) ($allocation['driver'] ?? $driver_acc));
            if ($isLocked) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot delete expense. A request has already been sent for approval.']);
            }

            if (!$expenseModel->delete($expense_id)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Failed to delete expense']);
            }

            $this->deleteExpenseImages($tour_id, $expense_id);

            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Expense deleted successfully',
                'expense_date' => $existingExpense['expense_date'] ?? '',
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function TourFinalize()
    {
        try {
            $driver_acc = session()->get('acc_no');
            if (!$driver_acc || !session()->get('logged_in')) {
                return $this->response->setJSON(['status' => 401, 'message' => 'Please login to continue']);
            }

            $tour_id = trim((string) $this->request->getPost('tour_id'));
            if ($tour_id === '') {
                return $this->response->setJSON(['status' => 400, 'message' => 'Tour is required']);
            }

            $actualMileageInput = trim((string) $this->request->getPost('actual_mileage'));
            $actualBataInput = trim((string) $this->request->getPost('actual_bata'));
            $noOfDateInput = trim((string) $this->request->getPost('no_of_date'));
            $vehicleRateInput = trim((string) $this->request->getPost('vehicle_rate'));

            $allocation = $this->getDriverAllocation($tour_id, $driver_acc);
            if (!$allocation) {
                return $this->response->setJSON(['status' => 403, 'message' => 'Access denied']);
            }

            $driverCode = (string) ($allocation['driver'] ?? $driver_acc);
            if ($this->isTourExpenseLocked($tour_id, $driverCode)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Expenses for this tour have already been finalized.']);
            }

            $consigneeModel = new ConsigneeModel();
            $consignee = $consigneeModel
                ->select('manual_id, consignee, start_date, end_date, transport_agent, transport_agent_name, vehicle_rate, bata_day_rate, bata_days')
                ->where('tour_id', $tour_id)
                ->first();

            if (!$consignee) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Tour not found']);
            }

            $missingDays = $this->getDaysMissingMeterReadings($tour_id, $driverCode);
            if (!empty($missingDays)) {
                return $this->response->setJSON([
                    'status' => 400,
                    'message' => 'Cannot finalize — meter readings are missing for: ' . implode(', ', $missingDays),
                ]);
            }

            $hasSubmittedAnyReading = (new DriverMeterReadingModel())
                ->where('tour_id', $tour_id)
                ->where('driver_acc', $driverCode)
                ->countAllResults() > 0;
            if (!$hasSubmittedAnyReading) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Cannot finalize — no meter readings have been submitted yet']);
            }

            $pendingExpenses = $this->getPendingExpenseRows($tour_id, $driverCode);

            $expenseIds = array_values(array_filter(array_map(static fn($expense) => (int) ($expense['id'] ?? 0), $pendingExpenses)));
            $expenseCount = count($expenseIds);
            $distanceTotal = $this->getTourDistanceTotal($tour_id, $driverCode);
            $totalDollarAmount = round(array_sum(array_map(static fn($expense) => (float) ($expense['dollar_amount'] ?? 0), $pendingExpenses)), 2);
            $totalLkrAmount = round(array_sum(array_map(static fn($expense) => (float) ($expense['lkr_amount'] ?? 0), $pendingExpenses)), 2);

            foreach ([
                'Actual Mileage' => $actualMileageInput,
                'Actual Bata' => $actualBataInput,
                'No of Date' => $noOfDateInput,
                'Vehicle Rate' => $vehicleRateInput,
            ] as $fieldLabel => $fieldValue) {
                if ($fieldValue !== '' && !is_numeric($fieldValue)) {
                    return $this->response->setJSON(['status' => 400, 'message' => $fieldLabel . ' must be numeric']);
                }
            }

            $actualMileageValue = $actualMileageInput === '' ? $distanceTotal : round((float) $actualMileageInput, 2);
            $actualBataValue = $actualBataInput === '' ? round((float) ($consignee['bata_day_rate'] ?? 0), 2) : round((float) $actualBataInput, 2);
            $noOfDateValue = $noOfDateInput === '' ? (int) ($consignee['bata_days'] ?? 0) : (int) round((float) $noOfDateInput);
            $vehicleRateValue = $vehicleRateInput === '' ? round((float) ($consignee['vehicle_rate'] ?? 0), 2) : round((float) $vehicleRateInput, 2);
            // consignee.transport_agent/transport_agent_name are tour-wide "primary agent"
            // display fields — first driver to finalize wins, per the guarded update below.
            // They must NOT be used to tag which driver a log_sheet_mile row belongs to (see
            // $driverCode used directly for that further down): on a multi-driver tour, once the
            // first driver's finalize has already set consignee.transport_agent, a later driver's
            // fallback would silently inherit the FIRST driver's code instead of their own.
            $effectiveTransportAgent = trim((string) ($consignee['transport_agent'] ?? ''));
            if ($effectiveTransportAgent === '' || $effectiveTransportAgent === '6-9-11-10691') {
                $effectiveTransportAgent = $driverCode;
            }
            $effectiveTransportAgentName = trim((string) ($consignee['transport_agent_name'] ?? ''));
            if ($effectiveTransportAgentName === '' || $effectiveTransportAgent === $driverCode) {
                $effectiveTransportAgentName = (string) ($allocation['driver_name'] ?? $effectiveTransportAgentName);
            }

            if ($actualMileageValue < 0 || $actualBataValue < 0 || $noOfDateValue < 0 || $vehicleRateValue < 0) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Finalize values cannot be negative']);
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
                'driver_acc' => $driverCode,
                'driver_name' => $allocation['driver_name'] ?? session()->get('username'),
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

            $logsheetInsertId = $logsheetmileModel->insert([
                'tour_id' => $tour_id,
                'transport_agent' => $driverCode,
                'log_sheet_mileage' => $actualMileageValue,
                'estimate_mileage' => $distanceTotal,
                'vehicle_rate' => $vehicleRateValue,
                'per_day_rate' => $actualBataValue,
                'no_of_dates' => $noOfDateValue,
                'login' => session()->get('username'),
                'date' => $timestamp,
            ], true);

            if (!$logsheetInsertId) {
                throw new \RuntimeException('Failed to create logsheet mileage record.');
            }

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
                        'agent' => $expense['agent'] ?? $driverCode,
                        'agent_name' => $expense['agent_name'] ?? ($allocation['driver_name'] ?? session()->get('username')),
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
                'expense_count' => $expenseCount,
                'distance_total' => $distanceTotal,
                'total_dollar_amount' => $totalDollarAmount,
                'total_lkr_amount' => $totalLkrAmount,
                'actual_mileage' => $actualMileageValue,
                'actual_bata' => $actualBataValue,
                'no_of_date' => $noOfDateValue,
                'vehicle_rate' => $vehicleRateValue,
                'total_transport_cost' => $updatedTransportCost,
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Printable Driver Settlement Invoice for a finalized tour.
     * Route: driver_portal/settlement_invoice/(:num)
     */
    public function settlementInvoice($finalizeId = null)
    {
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $finalizeId = (int) $finalizeId;
        $data['invoice'] = null;
        $data['error'] = null;

        $finalizeModel = new TourFinalizeModel();

        if ($finalizeId > 0) {
            $finalize = $finalizeModel->find($finalizeId);
        } else {
            // Fall back to looking up the latest settlement for a given tour (e.g. from the tile dashboard)
            $tourIdParam = trim((string) $this->request->getGet('tour_id'));
            if ($tourIdParam === '') {
                $data['error'] = 'No settlement record specified.';
                return view('pages/driver/settlement_invoice', $data);
            }

            $finalize = $finalizeModel->where('tour_id', $tourIdParam)->orderBy('id', 'DESC')->first();
        }

        if (!$finalize) {
            $data['error'] = 'Settlement record not found. This tour may not have been finalized by the driver yet.';
            return view('pages/driver/settlement_invoice', $data);
        }

        $finalizeId = (int) $finalize['id'];

        // Only the owning driver or a super admin may view this settlement invoice — deny by default
      

        $tour = (new ConsigneeModel())->where('tour_id', $finalize['tour_id'])->first();

        $supplier = (new SupplierModel())
            ->where('Sup_Acc_No', $finalize['driver_acc'])
            ->first();

        $expenseIds = [];
        if (!empty($finalize['expense_ids'])) {
            $decoded = json_decode((string) $finalize['expense_ids'], true);
            if (is_array($decoded)) {
                $expenseIds = array_map('intval', $decoded);
            }
        }

        $expenseCategories = $this->getExpenseCategories();
        $entranceFees = [];
        $otherExpenses = [];
        $expenseRows = !empty($expenseIds)
            ? (new DriverExpensesModel())->whereIn('id', $expenseIds)->orderBy('expense_date', 'ASC')->findAll()
            : [];

        foreach ($expenseRows as $row) {
            $line = [
                'description' => $row['supplier_name'] ?: ($expenseCategories[$row['category'] ?? ''] ?? 'Other'),
                'amount' => round((float) ($row['lkr_amount'] ?? 0), 2),
            ];
            // '6-9-23-10640' is the Entrance Fee category code
            if (($row['category'] ?? '') === '6-9-23-10640') {
                $entranceFees[] = $line;
            } else {
                $otherExpenses[] = $line;
            }
        }

        $totalEntranceFees = round(array_sum(array_column($entranceFees, 'amount')), 2);
        $totalOtherExpenses = round(array_sum(array_column($otherExpenses, 'amount')), 2);
        $totalReimbursable = round($totalEntranceFees + $totalOtherExpenses, 2);

        // Live settlement figures — same aggregation as DriverPaymentSettlementController::get_balance_payment_details()
        $db = db_connect();
        $driverAcc = (string) $finalize['driver_acc'];
        $tourIdForLedger = $finalize['tour_id'];

        // Driver earnings = vehicle rate * mileage + bata (from logsheet mileage if present, else consignee)
        $logsheetDetails = (new LogSheetMileModel())
            ->select('log_sheet_mileage, vehicle_rate, per_day_rate, no_of_dates')
            ->where('tour_id', $tourIdForLedger)
            ->where('transport_agent', $driverAcc)
            ->orderBy('id', 'DESC')
            ->first();

        if ($logsheetDetails) {
            $totalDriverEarnings = round(
                ((float) $logsheetDetails['vehicle_rate'] * (float) $logsheetDetails['log_sheet_mileage'])
                + ((float) $logsheetDetails['per_day_rate'] * (float) $logsheetDetails['no_of_dates']),
                2
            );
        } else {
            $totalDriverEarnings = round((float) ($tour['total_transport_cost'] ?? 0), 2);
        }

        // Net advances already issued to this driver for this tour, still outstanding
        $advanceIssued = (float) ((new \App\Models\VoucherPaymentModel())
            ->selectSum('Amount', 'advance_dr')
            ->where('ref_po', $tourIdForLedger)
            ->where('Acc_No', $driverAcc)
            ->where('Aflag', 1)
            ->where('advances_pay', 1)
            ->first()['advance_dr'] ?? 0);

        $advanceRecovered = (float) ($db->table('account_runing')
            ->selectSum('Cr', 'advance_cr')
            ->where('index_no', $tourIdForLedger)
            ->where('L_Code', $driverAcc)
            ->where('advances_pay', 1111)
            ->get()
            ->getRowArray()['advance_cr'] ?? 0);

        $totalDeductions = round(max(0, $advanceIssued - $advanceRecovered), 2);

        // Amount already settled/paid out via voucher for this tour + driver
        $alreadySettled = (float) ($db->table('account_runing')
            ->selectSum('Cr', 'total_pay_cr')
            ->where('index_no', $tourIdForLedger)
            ->where('payee', $finalize['driver_name'] ?? '')
            ->where('Cr >', 0.00)
            ->groupStart()
                ->like('Discription', 'VOUCHER PAYMENT%')
                ->orLike('Discription', 'JOURNAL ENTRY%')
            ->groupEnd()
            ->where('advances_pay', 11)
            ->get()
            ->getRowArray()['total_pay_cr'] ?? 0);

        $netAmountPayable = round($totalDriverEarnings + $totalReimbursable - $totalDeductions - $alreadySettled, 2);

        $tourDateIds = [];
        if (!empty($finalize['tour_date_ids'])) {
            $decoded = json_decode((string) $finalize['tour_date_ids'], true);
            if (is_array($decoded)) {
                $tourDateIds = array_map('intval', $decoded);
            }
        }

        // Per-day trip rows: office-booked route (tour_activity) + driver's own odometer readings
        $tripRows = [];
        if (!empty($tour['start_date']) && !empty($tour['end_date'])) {
            $routeByDate = [];
            $activities = db_connect()->table('tour_activity')
                ->select('tour_date, code')
                ->where('tour_id', $finalize['tour_id'])
                ->where('code !=', 'LUNCH')
                ->orderBy('tour_row', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($activities as $activity) {
                $activityDate = substr((string) ($activity['tour_date'] ?? ''), 0, 10);
                $activityCode = trim((string) ($activity['code'] ?? ''));
                if ($activityDate === '' || $activityCode === '') {
                    continue;
                }
                $routeByDate[$activityDate][] = $activityCode;
            }

            $readingsByDate = [];
            foreach ((new DriverMeterReadingModel())
                ->where('tour_id', $finalize['tour_id'])
                ->where('driver_acc', $finalize['driver_acc'])
                ->findAll() as $reading) {
                $readingsByDate[$reading['reading_date']][$reading['type']] = $reading;
            }

            $tripStart = new \DateTime((string) $tour['start_date']);
            $tripEnd = new \DateTime((string) $tour['end_date']);
            $tripEnd->modify('+1 day');
            foreach (new \DatePeriod($tripStart, new \DateInterval('P1D'), $tripEnd) as $dt) {
                $rowDate = $dt->format('Y-m-d');
                $startReading = $readingsByDate[$rowDate]['start'] ?? null;
                $endReading = $readingsByDate[$rowDate]['end'] ?? null;
                $startKm = $startReading ? (float) $startReading['km'] : null;
                $endKm = $endReading ? (float) $endReading['km'] : null;

                $tripRows[] = [
                    'date' => $rowDate,
                    'route' => !empty($routeByDate[$rowDate]) ? implode(', ', array_unique($routeByDate[$rowDate])) : '',
                    'odometer_start' => $startKm,
                    'odometer_end' => $endKm,
                    'mileage' => ($startKm !== null && $endKm !== null) ? max(0, $endKm - $startKm) : null,
                ];
            }
        }

        $tourRoute = [];
        foreach ($tripRows as $row) {
            if ($row['route'] !== '') {
                $tourRoute[] = $row['route'];
            }
        }
        $tourRoute = implode(', ', array_unique(array_filter(array_map('trim', explode(',', implode(',', $tourRoute))))));

        $data['invoice'] = [
            'finalize_id' => $finalizeId,
            'invoice_no' => 'DH-' . str_pad((string) $finalizeId, 6, '0', STR_PAD_LEFT),
            'invoice_date' => date('Y-m-d', strtotime((string) ($finalize['finalized_at'] ?? 'now'))),
            'tour_id' => $finalize['tour_id'],
            'manual_id' => $finalize['manual_id'] ?? '',
            'consignee' => $finalize['consignee_name'] ?? ($tour['consignee'] ?? ''),
            'tour_route' => $tourRoute,
            'start_date' => $tour['start_date'] ?? '',
            'end_date' => $tour['end_date'] ?? '',
            'driver_name' => $finalize['driver_name'] ?? '',
            'driver_acc' => $finalize['driver_acc'] ?? '',
            'driver_nic' => $supplier['NIC'] ?? '',
            'driver_contact' => $supplier['Mobile'] ?? '',
            'bank_name' => $supplier['bank_name'] ?? '',
            'branch_name' => $supplier['branch_name'] ?? '',
            'bank_account_no' => $supplier['sup_bank_acc'] ?? '',
            'bank_account_holder' => $supplier['acc_holder'] ?? '',
            'vehicle_name' => $tour['vehicle_name'] ?? '',
            'distance_total' => round((float) ($finalize['distance_total'] ?? 0), 2),
            'day_count' => !empty($tripRows) ? count($tripRows) : (int) ($tour['bata_days'] ?? 0),
            'trip_rows' => $tripRows,
            'total_dollar_amount' => round((float) ($finalize['total_dollar_amount'] ?? 0), 2),
            'total_lkr_amount' => round((float) ($finalize['total_lkr_amount'] ?? 0), 2),
            'entrance_fees' => $entranceFees,
            'total_entrance_fees' => $totalEntranceFees,
            'other_expenses' => $otherExpenses,
            'total_other_expenses' => $totalOtherExpenses,
            'total_reimbursable' => $totalReimbursable,
            'total_driver_earnings' => $totalDriverEarnings,
            'total_deductions' => $totalDeductions,
            'already_settled' => $alreadySettled,
            'net_amount_payable' => $netAmountPayable,
            'status' => $finalize['status'] ?? 'pending',
            'finalized_by' => $finalize['finalized_by'] ?? '',
            'finalized_at' => $finalize['finalized_at'] ?? '',
        ];

        return view('pages/driver/settlement_invoice', $data);
    }

    /**
     * Admin-facing report: all data a driver entered for a tour —
     * per-day meter readings, expenses, totals, bata, dates, driver name.
     * Route: dashboard2/transport/driver_report?tour_id=XXX
     */
    public function driverReport()
    {
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $tour_id = trim((string) $this->request->getGet('tour_id'));
        $data['tour_id'] = $tour_id;
        $data['report'] = null;
        $data['error'] = null;
        $data['allocations'] = [];

        if ($tour_id === '') {
            // No tour searched yet — render empty search page
            return view('pages/report/driver_tour_report', $data);
        }

        $consigneeModel = new ConsigneeModel();
        $tour = $consigneeModel->where('tour_id', $tour_id)->first();
        if (!$tour) {
            $data['error'] = 'No tour found for the given Tour ID.';
            return view('pages/report/driver_tour_report', $data);
        }

        // Drivers for this tour (from allocation; fall back to consignee transport agent).
        // A tour can have more than one driver allocated (e.g. one covers days 1-3, another
        // covers day 4), so fetch all of them for the driver-switch dropdown and pick the one
        // requested via ?driver=, defaulting to the first allocation row.
        $requestedDriver = trim((string) $this->request->getGet('driver'));
        $allocations = (new DriverAllocationModel())->where('tour_id', $tour_id)->orderBy('allow_date', 'ASC')->findAll();
        $allocation = null;
        if ($requestedDriver !== '') {
            foreach ($allocations as $allocRow) {
                if ((string) $allocRow['driver'] === $requestedDriver) {
                    $allocation = $allocRow;
                    break;
                }
            }
        }
        if ($allocation === null) {
            $allocation = $allocations[0] ?? null;
        }
        $driverCode = (string) ($allocation['driver'] ?? ($tour['transport_agent'] ?? ''));
        $driverName = (string) ($allocation['driver_name'] ?? ($tour['transport_agent_name'] ?? ''));
        $data['allocations'] = $allocations;

        $expenseCategories = $this->getExpenseCategories();

        // Build the tour date range
        $dates = [];
        try {
            $start = new \DateTime($tour['start_date']);
            $end   = new \DateTime($tour['end_date']);
            $end->modify('+1 day');
            foreach (new \DatePeriod($start, new \DateInterval('P1D'), $end) as $dt) {
                $dates[] = $dt->format('Y-m-d');
            }
        } catch (\Exception $e) {
            $dates = [];
        }

        // Activities/places visited each day (office-booked itinerary), keyed by date.
        $activitiesByDate = [];
        $activityRows = db_connect()->table('tour_activity')
            ->select('tour_date, code')
            ->where('tour_id', $tour_id)
            ->where('code !=', 'LUNCH')
            ->orderBy('tour_row', 'ASC')
            ->get()
            ->getResultArray();
        foreach ($activityRows as $activity) {
            $aDate = substr((string) ($activity['tour_date'] ?? ''), 0, 10);
            $aCode = trim((string) ($activity['code'] ?? ''));
            if ($aDate === '' || $aCode === '') {
                continue;
            }
            $activitiesByDate[$aDate][] = $aCode;
        }

        // Planned per-day route + distance from the tour's sub-location legs (the same
        // office-planned waypoint breakdown shown on the quotation timeline's mileage editor —
        // finer-grained than tour_quotations' one row per day), keyed by date. Ground truth to
        // catch a driver padding actual km with personal-errand mileage. A day can have several
        // legs (e.g. airport transfer + hotel transfer + sightseeing same day), so legs are
        // accumulated per date, not overwritten.
        //
        // Tracked three ways per day:
        //   planned_km      - estimate_km + extra_km for every leg (matches quotation-editor totals)
        //   planned_km_base - estimate_km only, no extras, for context
        //   predictable_km  - estimate_km for every leg, PLUS extra_km only for legs whose extra_km
        //                     is exactly 40 — that figure is the office's marker for a genuine
        //                     activity/sightseeing detour. Any other extra_km value (0, 10, 15,
        //                     etc.) is a plain transfer leg and its extra_km is excluded.
        $plannedByDate = [];
        $plannedBaseByDate = [];
        $predictableByDate = [];
        $routeByDate = [];
        $subLocationRows = (new TourSubLocationsModel())
            ->select('date_id, from, to, estimate_km, extra_km')
            ->where('quotation_id', $tour_id)
            ->orderBy('id', 'ASC')
            ->findAll();
        foreach ($subLocationRows as $sl) {
            $slDate = substr((string) ($sl['date_id'] ?? ''), 0, 10);
            if ($slDate === '') {
                continue;
            }
            $legEstimateKm = $sl['estimate_km'] !== null ? (float) $sl['estimate_km'] : 0.0;
            $legExtraKm = $sl['extra_km'] !== null ? (float) $sl['extra_km'] : 0.0;
            $legPlanned = $legEstimateKm + $legExtraKm;
            $isActivityLeg = $sl['extra_km'] !== null && (float) $sl['extra_km'] === 40.0;
            $legPredictable = $legEstimateKm + ($isActivityLeg ? $legExtraKm : 0.0);

            $plannedByDate[$slDate] = ($plannedByDate[$slDate] ?? 0.0) + $legPlanned;
            $plannedBaseByDate[$slDate] = ($plannedBaseByDate[$slDate] ?? 0.0) + $legEstimateKm;
            $predictableByDate[$slDate] = ($predictableByDate[$slDate] ?? 0.0) + $legPredictable;

            $routeByDate[$slDate][] = [
                'from_location'   => $sl['from'] ?? '',
                'to_location'     => $sl['to'] ?? '',
                'gdistance'       => $sl['estimate_km'] !== null ? (float) $sl['estimate_km'] : null,
                'extra_kilo'      => $sl['extra_km'] !== null ? (float) $sl['extra_km'] : null,
                'leg_planned'     => $legPlanned,
                'is_activity_leg' => $isActivityLeg,
            ];
        }

        // "Similar Mileage": median actual driven distance (from driver_meter_reading) on OTHER
        // tours' days that have the exact same SET of waypoint legs as this day — not just one
        // matching leg. A day with 3 legs where only 1 leg text matches another tour's day is not
        // "similar" (that tour's other 2 legs could go anywhere), so the comparison is keyed by
        // the day's full leg set (order-independent), computed once per date here.
        $similarRouteByDate = [];
        foreach ($routeByDate as $slDate => $legs) {
            $similarRouteByDate[$slDate] = $this->getSimilarRouteAverageDistance($legs, $tour_id);
        }

        // Back-office mileage amendments (with optional note) for this driver on this tour,
        // keyed by date, so the report can pre-fill the Amended Mileage column with whatever was
        // last saved.
        $mileageAmendmentsByDate = [];
        $mileageAmendmentRows = (new DriverDailyMileageAmendmentModel())
            ->where('tour_id', $tour_id)
            ->where('driver_acc', $driverCode)
            ->findAll();
        foreach ($mileageAmendmentRows as $amendment) {
            $mileageAmendmentsByDate[substr((string) $amendment['reading_date'], 0, 10)] = $amendment;
        }

        // Meter readings indexed by date+type (start/end)
        $rawReadings = (new DriverMeterReadingModel())
            ->where('tour_id', $tour_id)
            ->where('driver_acc', $driverCode)
            ->orderBy('reading_date', 'ASC')
            ->findAll();

        $readings = [];
        foreach ($rawReadings as $r) {
            $readings[$r['reading_date']][$r['type']] = $r;
        }

        // Expenses entered by the driver for this tour, grouped by expense date
        $expenseRows = (new DriverPortalExpensesModel())
            ->where('tour_id', $tour_id)
            ->orderBy('expense_date', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        // driver_portal_expenses has no approval audit trail of its own — once a tour is finalized,
        // its expenses are snapshotted into the legacy driver_expenses table (see the finalize()
        // action below), and accounts staff approve THAT copy (DriverExpensesController sets
        // approved_by there). There's no shared id between the two tables, so match by the same
        // (date, category, supplier, lkr_amount) tuple the finalize snapshot copies verbatim.
        $legacyExpenseRows = (new DriverExpensesModel())
            ->where('tour_id', $tour_id)
            ->where('agent', $driverCode)
            ->findAll();
        $approvedByLookup = [];
        $finalizedByLookup = [];
        $legacyExpenseLookup = [];
        foreach ($legacyExpenseRows as $legacyEx) {
            $key = implode('|', [
                (string) ($legacyEx['expense_date'] ?? ''),
                (string) ($legacyEx['category'] ?? ''),
                (string) ($legacyEx['supplier_name'] ?? ($legacyEx['supplier'] ?? '')),
                number_format((float) ($legacyEx['lkr_amount'] ?? 0), 2, '.', ''),
            ]);
            if (!empty($legacyEx['approved_by'])) {
                $approvedByLookup[$key] = $legacyEx['approved_by'];
            }
            if (!empty($legacyEx['request_send_by'])) {
                $finalizedByLookup[$key] = $legacyEx['request_send_by'];
            }
            // Checked By = who sent this expense for approval (driver_expenses.request_send_by),
            // i.e. the back-office staff member who reviewed the driver's raw expense and posted
            // the request onward — not driver_portal_expenses.created_by (the driver themself).
            $legacyExpenseLookup[$key] = [
                'id'           => (int) ($legacyEx['id'] ?? 0),
                'status'       => (string) ($legacyEx['status'] ?? ''),
                'request_send' => (int) ($legacyEx['request_send'] ?? 0),
                'checked_by'   => (string) ($legacyEx['request_send_by'] ?? ''),
            ];
        }

        // created_by is sometimes the driver's own NIC (when the driver submitted the expense via
        // the portal) rather than a staff username — resolve those against supplier.NIC to show the
        // driver's name instead of a raw NIC number.
        $createdByNics = array_unique(array_filter(array_map(
            static fn($ex) => (string) ($ex['created_by'] ?? ''),
            $expenseRows
        )));
        $createdByNameLookup = [];
        if (!empty($createdByNics)) {
            $supplierRows = (new SupplierModel())
                ->select('NIC, Name')
                ->whereIn('NIC', $createdByNics)
                ->findAll();
            foreach ($supplierRows as $supplierRow) {
                if (!empty($supplierRow['NIC']) && !empty($supplierRow['Name'])) {
                    $createdByNameLookup[$supplierRow['NIC']] = $supplierRow['Name'];
                }
            }
        }

        $expenses = [];
        $expensesByDate = [];
        $totalDollar = 0.0;
        $totalLkr = 0.0;
        $matchedLegacyKeys = [];
        foreach ($expenseRows as $ex) {
            $expenseId = (int) ($ex['id'] ?? 0);
            $totalDollar += (float) ($ex['dollar_amount'] ?? 0);
            $totalLkr    += (float) ($ex['lkr_amount'] ?? 0);
            $expenseDate = $ex['expense_date'] ?? '';
            $matchKey = implode('|', [
                (string) $expenseDate,
                (string) ($ex['category'] ?? ''),
                (string) ($ex['supplier_name'] ?? ($ex['supplier'] ?? '')),
                number_format((float) ($ex['lkr_amount'] ?? 0), 2, '.', ''),
            ]);
            $legacyMatch = $legacyExpenseLookup[$matchKey] ?? null;
            if ($legacyMatch !== null) {
                $matchedLegacyKeys[$matchKey] = true;
            }
            // Posted once the driver_expenses copy exists and has moved past pending (accountant
            // approved/posted it to accounts) — matches this codebase's 'posted'/'approved' status
            // values (see getExpensesByTour()'s normalized_status). No legacy match yet, or still
            // pending there, means this is still editable.
            $isPosted = $legacyMatch !== null && in_array($legacyMatch['status'], ['posted', 'approved'], true);
            // Has a driver_expenses copy sitting unsent — what "Send All Requests" (send_all_requests())
            // acts on. No legacy row yet (not finalized) means nothing to send.
            $hasUnsentRequest = $legacyMatch !== null && !$isPosted && $legacyMatch['request_send'] === 0;

            $formattedExpense = [
                'id'             => $expenseId,
                'category'       => $ex['category'] ?? '',
                'category_label' => $expenseCategories[$ex['category'] ?? ''] ?? ($ex['category'] ?? ''),
                'supplier'       => $ex['supplier'] ?? '',
                'supplier_name'  => $ex['supplier_name'] ?? ($ex['supplier'] ?? ''),
                'invoice_no'     => $ex['invoice_no'] ?? '',
                'description'    => $ex['description'] ?? '',
                'dollar_rate'    => round((float) ($ex['dollar_rate'] ?? 0), 2),
                'dollar_amount'  => round((float) ($ex['dollar_amount'] ?? 0), 2),
                'lkr_amount'     => round((float) ($ex['lkr_amount'] ?? 0), 2),
                'status'         => $ex['status'] ?? '',
                'invoice_image_src' => $expenseId > 0 ? $this->getExpenseImageUrl($tour_id, $expenseId) : '',
                'created_by'     => $createdByNameLookup[$ex['created_by'] ?? ''] ?? ($ex['created_by'] ?? ''),
                'checked_by'     => $legacyMatch['checked_by'] ?? '',
                'approved_by'    => $approvedByLookup[$matchKey] ?? '',
                'finalized_by'   => $finalizedByLookup[$matchKey] ?? '',
                'legacy_expense_id' => $legacyMatch['id'] ?? null,
                'is_posted'      => $isPosted,
                'has_unsent_request' => $hasUnsentRequest,
                'status_label'   => $isPosted ? 'Posted' : 'Pending',
                'is_orphan'      => false,
            ];
            $expenses[] = $formattedExpense;
            if ($expenseDate !== '') {
                $expensesByDate[$expenseDate][] = $formattedExpense;
            }
        }

        // driver_expenses rows with no driver_portal_expenses counterpart — entered directly at
        // the accounts level (or imported) rather than through the driver portal, so there's no
        // source row to drive the loop above from. Still show them, read-only (no Edit/Delete —
        // there's no driver_portal_expenses row for adminUpdateExpense/adminDeleteExpense to sync).
        foreach ($legacyExpenseRows as $legacyEx) {
            $key = implode('|', [
                (string) ($legacyEx['expense_date'] ?? ''),
                (string) ($legacyEx['category'] ?? ''),
                (string) ($legacyEx['supplier_name'] ?? ($legacyEx['supplier'] ?? '')),
                number_format((float) ($legacyEx['lkr_amount'] ?? 0), 2, '.', ''),
            ]);
            if (isset($matchedLegacyKeys[$key])) {
                continue;
            }

            $legacyStatus = (string) ($legacyEx['status'] ?? '');
            $isPosted = in_array($legacyStatus, ['posted', 'approved'], true);
            $legacyDate = $legacyEx['expense_date'] ?? '';
            $legacyLkr = round((float) ($legacyEx['lkr_amount'] ?? 0), 2);
            $totalDollar += (float) ($legacyEx['dollar_amount'] ?? 0);
            $totalLkr    += $legacyLkr;

            $formattedExpense = [
                'id'             => null,
                'expense_date'   => $legacyDate,
                'category'       => $legacyEx['category'] ?? '',
                'category_label' => $expenseCategories[$legacyEx['category'] ?? ''] ?? ($legacyEx['category'] ?? ''),
                'supplier'       => $legacyEx['supplier'] ?? '',
                'supplier_name'  => $legacyEx['supplier_name'] ?? ($legacyEx['supplier'] ?? ''),
                'invoice_no'     => $legacyEx['invoice_no'] ?? '',
                'description'    => $legacyEx['description'] ?? '',
                'dollar_rate'    => round((float) ($legacyEx['dollar_rate'] ?? 0), 2),
                'dollar_amount'  => round((float) ($legacyEx['dollar_amount'] ?? 0), 2),
                'lkr_amount'     => $legacyLkr,
                'status'         => $legacyStatus,
                'invoice_image_src' => '',
                // No driver_portal_expenses row exists for this orphan, so driver_expenses'
                // own created_by (the staff member who entered it directly) is the closest
                // "Created By" available.
                'created_by'     => (string) ($legacyEx['created_by'] ?? ''),
                'checked_by'     => (string) ($legacyEx['request_send_by'] ?? ''),
                'approved_by'    => (string) ($legacyEx['approved_by'] ?? ''),
                'finalized_by'   => (string) ($legacyEx['request_send_by'] ?? ''),
                'legacy_expense_id' => (int) ($legacyEx['id'] ?? 0),
                'is_posted'      => $isPosted,
                'has_unsent_request' => !$isPosted && (int) ($legacyEx['request_send'] ?? 0) === 0,
                'status_label'   => $isPosted ? 'Posted' : 'Pending',
                'is_orphan'      => true,
            ];
            $expenses[] = $formattedExpense;
            if ($legacyDate !== '') {
                $expensesByDate[$legacyDate][] = $formattedExpense;
            }
        }

        // Per-day rows: start km, end km, distance, planned distance, and that day's expenses.
        // A day is flagged when actual distance exceeds the planned gdistance by more than 15km
        // — days with no planned distance on file are never flagged, nothing to compare against.
        $dailyRows = [];
        $totalDistance = 0.0;
        $flaggedDayCount = 0;
        $totalPredictableKmMin = 0.0;
        $totalPredictableKmMax = 0.0;
        $totalQuotationKmWithExtras = 0.0;
        $firstTourDate = $dates[0] ?? null;
        $lastTourDate = !empty($dates) ? $dates[count($dates) - 1] : null;
        foreach ($dates as $date) {
            $rs = $readings[$date]['start'] ?? null;
            $re = $readings[$date]['end'] ?? null;

            // A tour can have more than one driver allocated (each covering different days), so a
            // day with no meter reading AND no amendment for the currently selected driver belongs
            // to a different driver — skip it entirely rather than showing an empty row.
            $mileageAmendmentForDate = $mileageAmendmentsByDate[$date] ?? null;
            if ($rs === null && $re === null && $mileageAmendmentForDate === null) {
                continue;
            }

            $startKm = $rs ? (float) $rs['km'] : null;
            $endKm   = $re ? (float) $re['km'] : null;
            $distance = ($startKm !== null && $endKm !== null) ? max(0, $endKm - $startKm) : null;
            if ($distance !== null) {
                $totalDistance += $distance;
            }

            $plannedKm = $plannedByDate[$date] ?? null;
            if ($plannedKm !== null) {
                $totalQuotationKmWithExtras += $plannedKm;
            }
            $plannedKmBase = $plannedBaseByDate[$date] ?? null;
            $predictableKm = $predictableByDate[$date] ?? null;
            // Predictable Mileage Range: lower bound is the plain base estimate (no extras),
            // upper bound adds extra_km only for legs whose extra_km is exactly 40 (the office's
            // marker for a genuine activity/sightseeing detour). Garage Mileage (flat 30km each,
            // the driver's trip from the garage/home to the tour's actual pickup point, and back
            // again at the end) widens both bounds on day 1 AND the tour's last day — a day-1-only
            // tour gets both additions on that single day. Expected additions to the estimate, not
            // something the driver's odometer would ever capture, so they belong on the predictable
            // side, not the actual-distance side. Only applied when a plan already exists for that
            // day — no plan means nothing to widen.
            $garageKm = 0.0;
            if ($date === $firstTourDate) {
                $garageKm += self::GARAGE_MILEAGE_KM;
            }
            if ($date === $lastTourDate) {
                $garageKm += self::GARAGE_MILEAGE_KM;
            }
            $predictableKmMin = $plannedKmBase !== null ? $plannedKmBase + $garageKm : null;
            $predictableKmMax = $predictableKm !== null ? $predictableKm + $garageKm : null;
            // Flagged when the driver's actual mileage exceeds the top of the Predictable Mileage
            // Range — under-reporting (below the range) is not flagged, only inflated mileage is.
            $isFlagged = $predictableKmMax !== null && $distance !== null && $distance > $predictableKmMax;
            if ($isFlagged) {
                $flaggedDayCount++;
            }
            if ($predictableKmMin !== null) {
                $totalPredictableKmMin += $predictableKmMin;
            }
            if ($predictableKmMax !== null) {
                $totalPredictableKmMax += $predictableKmMax;
            }

            $dayRoute = $routeByDate[$date] ?? [];
            $similarRoute = $similarRouteByDate[$date] ?? ['average_km' => null, 'match_count' => 0, 'matches' => []];
            $mileageAmendment = $mileageAmendmentForDate;

            $dailyRows[] = [
                'date'       => $date,
                'start_km'   => $startKm,
                'end_km'     => $endKm,
                'distance'   => $distance,
                'garage_km'  => $garageKm > 0 ? $garageKm : null,
                'planned_km' => $plannedKm,
                'planned_km_base' => $plannedKmBase,
                'predictable_km' => $predictableKm,
                'predictable_km_min' => $predictableKmMin,
                'predictable_km_max' => $predictableKmMax,
                'similar_route_km' => $similarRoute['average_km'],
                'similar_route_match_count' => $similarRoute['match_count'],
                'similar_route_matches' => $similarRoute['matches'] ?? [],
                'amended_km' => $mileageAmendment['amended_km'] ?? null,
                'amended_note' => $mileageAmendment['note'] ?? null,
                'amended_by' => $mileageAmendment['amended_by'] ?? null,
                'amended_at' => $mileageAmendment['amended_at'] ?? null,
                'is_flagged' => $isFlagged,
                'route'      => $dayRoute,
                'activities' => $activitiesByDate[$date] ?? [],
                'start_photo' => $rs['photo'] ?? null,
                'end_photo'   => $re['photo'] ?? null,
                'start_reading_id' => $rs['id'] ?? null,
                'end_reading_id'   => $re['id'] ?? null,
                'start_km_override' => !empty($rs['is_override']) ? [
                    'previous_km' => $rs['previous_km'] ?? null,
                    'overridden_by' => $rs['overridden_by'] ?? null,
                    'overridden_at' => $rs['overridden_at'] ?? null,
                ] : null,
                'end_km_override' => !empty($re['is_override']) ? [
                    'previous_km' => $re['previous_km'] ?? null,
                    'overridden_by' => $re['overridden_by'] ?? null,
                    'overridden_at' => $re['overridden_at'] ?? null,
                ] : null,
                'expenses'   => $expensesByDate[$date] ?? [],
            ];
        }

        // Finalize record (if the tour has been finalized by the driver)
        $finalize = (new TourFinalizeModel())
            ->where('tour_id', $tour_id)
            ->where('driver_acc', $driverCode)
            ->orderBy('id', 'DESC')
            ->first();

        $bataDayRate = round((float) ($tour['bata_day_rate'] ?? 0), 2);
        $bataDays    = (int) ($tour['bata_days'] ?? 0);

        // Latest log_sheet_mile row for this driver — used to show whether the tour's log sheet
        // mileage was overridden by the back office (pending_expenses_dashboard's Edit Mileage
        // modal, or log_sheet.php), and what it replaced.
        $latestLogSheetMile = (new LogSheetMileModel())
            ->where('tour_id', $tour_id)
            ->where('transport_agent', $driverCode)
            ->orderBy('id', 'DESC')
            ->first();

        // Driver Log Sheet section below mirrors TourAccountsController::approved_expenses_report()'s
        // Driver Payment Summary EXACTLY (same source tables/fields/formula), scoped to this one
        // driver, so the two reports never disagree about what a driver is owed.
        $paymentAmendedMileage = ($latestLogSheetMile && $latestLogSheetMile['log_sheet_mileage'] > 0)
            ? (float) $latestLogSheetMile['log_sheet_mileage']
            : 0.0;
        $paymentVehicleRate = (float) ($latestLogSheetMile['vehicle_rate'] ?? 0);
        $paymentBataRate    = (float) ($latestLogSheetMile['per_day_rate'] ?? 0);
        $paymentBataDays    = (int) ($latestLogSheetMile['no_of_dates'] ?? 0);
        $paymentLogSheetAmount = ($latestLogSheetMile && $latestLogSheetMile['log_sheet_mileage'] > 0)
            ? ($paymentVehicleRate * $paymentAmendedMileage) + ($paymentBataRate * $paymentBataDays)
            : 0.0;
        $paymentHasLogSheet = ($latestLogSheetMile && $latestLogSheetMile['log_sheet_mileage'] > 0);

        $paymentAdvance = (new VoucherPaymentModel())
            ->select('SUM(Amount) as advance_amount')
            ->where('ref_po', $tour_id)
            ->where('Acc_No', $driverCode)
            ->where('Aflag', 1)
            ->where('advances_pay', 1)
            ->first();
        $paymentAdvanceAmount = (float) ($paymentAdvance['advance_amount'] ?? 0);

        $paymentExpenses = (new DriverPortalExpensesModel())
            ->select('SUM(lkr_amount) as total_lkr')
            ->where('tour_id', $tour_id)
            ->where('agent', $driverCode)
            ->where('status', 'finalized')
            ->first();
        $paymentExpensesAmount = (float) ($paymentExpenses['total_lkr'] ?? 0);

        $paymentPayableAmount = $paymentLogSheetAmount - ($paymentAdvanceAmount - $paymentExpensesAmount);

        $data['report'] = [
            'tour_id'        => $tour_id,
            'manual_id'      => $tour['manual_id'] ?? '',
            'consignee'      => $tour['consignee'] ?? '',
            'driver_acc'     => $driverCode,
            'driver_name'    => $driverName,
            'start_date'     => substr((string) ($tour['start_date'] ?? ''), 0, 10),
            'end_date'       => substr((string) ($tour['end_date'] ?? ''), 0, 10),
            'total_days'     => count($dailyRows),
            'vehicle_rate'   => round((float) ($tour['vehicle_rate'] ?? 0), 2),
            'bata_day_rate'  => $bataDayRate,
            'bata_days'      => $bataDays,
            'total_bata'     => round($bataDayRate * $bataDays, 2),
            'total_distance' => round($totalDistance, 2),
            'flagged_day_count' => $flaggedDayCount,
            'total_predictable_km_min' => round($totalPredictableKmMin, 2),
            'total_predictable_km_max' => round($totalPredictableKmMax, 2),
            'total_quotation_km_with_extras' => round($totalQuotationKmWithExtras, 2),
            'daily_rows'     => $dailyRows,
            'expenses'       => $expenses,
            'total_dollar'   => round($totalDollar, 2),
            'total_lkr'      => round($totalLkr, 2),
            'expense_count'  => count($expenses),
            'is_finalized'   => !empty($finalize),
            'finalized_at'   => $finalize['finalized_at'] ?? null,
            'finalized_by'   => $finalize['finalized_by'] ?? null,
            'total_transport_cost' => round((float) ($tour['total_transport_cost'] ?? 0), 2),
            'log_sheet_mileage_is_override' => !empty($latestLogSheetMile['is_override']),
            'log_sheet_mileage_current'     => $latestLogSheetMile['log_sheet_mileage'] ?? null,
            'log_sheet_mileage_previous'    => $latestLogSheetMile['previous_mileage'] ?? null,
            'log_sheet_mileage_overridden_by' => $latestLogSheetMile['overridden_by'] ?? null,
            'payment_amended_mileage' => round($paymentAmendedMileage, 2),
            'payment_vehicle_rate'    => round($paymentVehicleRate, 2),
            'payment_bata_rate'       => round($paymentBataRate, 2),
            'payment_bata_days'       => $paymentBataDays,
            'payment_log_sheet_amount' => round($paymentLogSheetAmount, 2),
            'payment_advance_amount' => round($paymentAdvanceAmount, 2),
            'payment_expenses_amount' => round($paymentExpensesAmount, 2),
            'payment_payable_amount' => round($paymentPayableAmount, 2),
            'payment_has_log_sheet'  => $paymentHasLogSheet,
        ];

        return view('pages/report/driver_tour_report', $data);
    }

    // Admin overrides a single day's driver-submitted odometer reading from the Driver Log
    // Report's Mileage Detail modal. Edits the existing driver_meter_reading row in place (unlike
    // TransportPortalController::adminSaveMeterReading(), which only inserts a missing reading and
    // refuses to touch one that already exists) — the prior value is preserved in previous_km so
    // the override is never silently lost.
    public function overrideMeterReading()
    {
        try {
            $id = $this->request->getPost('id');
            $km = $this->request->getPost('km');

            if (!$id || $km === null || $km === '') {
                return $this->response->setJSON(['status' => 400, 'message' => 'Required fields missing']);
            }

            if (!is_numeric($km)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'KM must be a number']);
            }

            $readingModel = new DriverMeterReadingModel();
            $reading = $readingModel->find($id);
            if (!$reading) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Reading not found']);
            }

            $updated = $readingModel->update($id, [
                'km' => round((float) $km, 2),
                'previous_km' => $reading['km'],
                'is_override' => 1,
                'overridden_by' => session()->get('username'),
                'overridden_at' => date('Y-m-d H:i:s'),
            ]);

            if (!$updated) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Failed to update reading']);
            }

            return $this->response->setJSON(['status' => 200, 'message' => 'Reading updated successfully']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // Back-office amends a single day's mileage on the Driver Log Report (driverReport()), with an
    // optional note explaining why. Upserts into driver_daily_mileage_amendment (one row per
    // tour+driver+date), then recalculates the tour-wide total — summing, for every day of the
    // tour, that day's amended_km if an amendment exists, else the driver's actual driven distance
    // — and inserts a NEW log_sheet_mile row with that total, following the same insert-only /
    // is_override / previous_mileage pattern DriverLogsheetController::add_logsheet_mileage()
    // already uses (log_sheet_mile has no per-day rows, only one row per tour-wide override event).
    public function saveMileageAmendment()
    {
        try {
            $tour_id = trim((string) $this->request->getPost('tour_id'));
            $driver_acc = trim((string) $this->request->getPost('driver_acc'));
            $reading_date = trim((string) $this->request->getPost('reading_date'));
            $amended_km = $this->request->getPost('amended_km');
            $note = trim((string) $this->request->getPost('note'));

            if ($tour_id === '' || $driver_acc === '' || $reading_date === '' || $amended_km === null || $amended_km === '') {
                return $this->response->setJSON(['status' => 400, 'message' => 'Required fields missing']);
            }

            if (!is_numeric($amended_km)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Amended mileage must be a number']);
            }

            if (!$this->isValidDate($reading_date)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Invalid date']);
            }

            $allocation = $this->getDriverAllocation($tour_id, $driver_acc);
            if (!$allocation) {
                return $this->response->setJSON(['status' => 403, 'message' => 'Selected driver is not allocated to this tour']);
            }

            $now = date('Y-m-d H:i:s');
            $username = session()->get('username');

            $amendmentModel = new DriverDailyMileageAmendmentModel();
            $existingAmendment = $amendmentModel
                ->where('tour_id', $tour_id)
                ->where('driver_acc', $driver_acc)
                ->where('reading_date', $reading_date)
                ->first();

            $amendmentData = [
                'tour_id' => $tour_id,
                'driver_acc' => $driver_acc,
                'reading_date' => $reading_date,
                'amended_km' => round((float) $amended_km, 2),
                'note' => $note !== '' ? $note : null,
                'amended_by' => $username,
                'amended_at' => $now,
                'updated_at' => $now,
            ];

            if ($existingAmendment) {
                $amendmentModel->update($existingAmendment['id'], $amendmentData);
            } else {
                $amendmentModel->insert($amendmentData);
            }

            // Recalculate the tour-wide total: every day in the tour's date range contributes its
            // amended_km if amended, else the driver's actual driven distance for that day (same
            // per-day pairing getTourDistanceTotal() uses) — plus the flat Garage Mileage that
            // already applies to day 1 and the tour's last day.
            $tour = (new ConsigneeModel())->where('tour_id', $tour_id)->first();
            if (!$tour) {
                return $this->response->setJSON(['status' => 404, 'message' => 'Tour not found']);
            }

            $newTotalMileage = $this->getAmendedTourDistanceTotal($tour_id, $driver_acc, $tour['start_date'] ?? '', $tour['end_date'] ?? '');

            // Unlike add_logsheet_mileage() (a distinct, deliberate override action), a mileage
            // amendment here is a correction to the SAME running total — so this updates the
            // existing latest log_sheet_mile row in place instead of inserting a new row per save.
            // previous_mileage still records what it changed from, so the correction is visible,
            // but repeatedly amending different days of the same tour doesn't pile up one row per
            // click.
            $logSheetMileModel = new LogSheetMileModel();
            $previousLogSheet = $logSheetMileModel
                ->where('tour_id', $tour_id)
                ->where('transport_agent', $driver_acc)
                ->orderBy('id', 'DESC')
                ->first();

            $logSheetData = [
                'tour_id' => $tour_id,
                'transport_agent' => $driver_acc,
                'log_sheet_mileage' => $newTotalMileage,
                'previous_mileage' => $previousLogSheet['log_sheet_mileage'] ?? null,
                'estimate_mileage' => $previousLogSheet['estimate_mileage'] ?? 0,
                'vehicle_rate' => $previousLogSheet['vehicle_rate'] ?? 0,
                'per_day_rate' => $previousLogSheet['per_day_rate'] ?? 0,
                'no_of_dates' => $previousLogSheet['no_of_dates'] ?? 0,
                'is_override' => 1,
                'overridden_by' => $username,
                'login' => $username,
                'date' => $now,
            ];

            if ($previousLogSheet) {
                $logSheetMileModel->update($previousLogSheet['id'], $logSheetData);
            } else {
                $logSheetMileModel->insert($logSheetData);
            }

            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Mileage amendment saved successfully',
                'new_total_mileage' => $newTotalMileage,
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // Sum of, for every calendar day of the tour, that day's amended_km (if the back office has
    // amended it) or else the driver's actual driven distance from driver_meter_reading — plus
    // Garage Mileage on day 1 and the tour's last day, matching driverReport()'s own rule.
    private function getAmendedTourDistanceTotal(string $tour_id, string $driver_acc, string $tourStartDate, string $tourEndDate): float
    {
        $dates = [];
        try {
            $start = new \DateTime($tourStartDate);
            $end = new \DateTime($tourEndDate);
            $end->modify('+1 day');
            foreach (new \DatePeriod($start, new \DateInterval('P1D'), $end) as $dt) {
                $dates[] = $dt->format('Y-m-d');
            }
        } catch (\Exception $e) {
            return 0.0;
        }

        if (empty($dates)) {
            return 0.0;
        }

        $amendmentsByDate = [];
        $amendments = (new DriverDailyMileageAmendmentModel())
            ->where('tour_id', $tour_id)
            ->where('driver_acc', $driver_acc)
            ->findAll();
        foreach ($amendments as $amendment) {
            $amendmentsByDate[substr((string) $amendment['reading_date'], 0, 10)] = (float) $amendment['amended_km'];
        }

        $readingsByDate = [];
        $readings = (new DriverMeterReadingModel())
            ->select('reading_date, type, km')
            ->where('tour_id', $tour_id)
            ->where('driver_acc', $driver_acc)
            ->findAll();
        foreach ($readings as $reading) {
            $readingDate = substr((string) ($reading['reading_date'] ?? ''), 0, 10);
            $readingType = (string) ($reading['type'] ?? '');
            if ($readingDate === '' || $readingType === '') {
                continue;
            }
            $readingsByDate[$readingDate][$readingType] = (float) ($reading['km'] ?? 0);
        }

        $total = 0.0;
        foreach ($dates as $date) {
            if (isset($amendmentsByDate[$date])) {
                $total += $amendmentsByDate[$date];
            } elseif (isset($readingsByDate[$date]['start'], $readingsByDate[$date]['end'])) {
                $total += max(0, $readingsByDate[$date]['end'] - $readingsByDate[$date]['start']);
            }
        }

        return round($total, 2);
    }

    // "Similar Mileage" for a day: median actual driven distance (from driver_meter_reading) on
    // OTHER tours' days whose tour_sub_locations legs form the exact same SET of From->To pairs as
    // this day (same legs, same count, order doesn't matter) — not just one matching leg. A day
    // with 3 legs where only 1 leg text matches another tour's day is NOT "similar", since that
    // other day's remaining legs could go anywhere and its total distance isn't comparable.
    // Excludes the current tour. Only days with a complete start+end reading count; a route nobody
    // else has ever driven returns average_km = null (not 0), so callers can distinguish "no data"
    // from "drove 0km". Median rather than mean: a single mistyped odometer reading (e.g.
    // 1,234,567 instead of a real 5-digit value) would otherwise drag a mean into nonsense.
    private function getSimilarRouteAverageDistance(array $dayLegs, string $excludeTourId): array
    {
        $signature = $this->routeLegSetSignature($dayLegs);
        if ($signature === null) {
            return ['average_km' => null, 'match_count' => 0];
        }

        // Any one leg from this day narrows the candidate set to (tour, date) pairs that touch at
        // least one of the same locations — cheap pre-filter before the exact full-set comparison,
        // since scanning every row in tour_sub_locations would otherwise be wasteful. Both steps —
        // building each candidate day's signature and matching driver_meter_reading — now run as
        // one query (a signature per GROUP BY, compared server-side) instead of one PHP round trip
        // per candidate day, which was the real cost on routes with 100+ candidates.
        $legPairs = [];
        foreach ($dayLegs as $leg) {
            $legPairs[] = [(string) ($leg['from_location'] ?? ''), (string) ($leg['to_location'] ?? '')];
        }

        $db = db_connect();
        $touchPlaceholders = implode(' OR ', array_fill(0, count($legPairs), '(`from` = ? AND `to` = ?)'));
        $touchBindings = [];
        foreach ($legPairs as [$from, $to]) {
            $touchBindings[] = $from;
            $touchBindings[] = $to;
        }

        $bindings = array_merge($touchBindings, [$excludeTourId, $signature]);

        $rows = $db->query(
            "SELECT matched.quotation_id, matched.date_id, dmr.driver_acc,
                c.manual_id,
                da.driver_name,
                MAX(CASE WHEN dmr.type = 'end' THEN dmr.km END)
                    - MAX(CASE WHEN dmr.type = 'start' THEN dmr.km END) AS day_distance
            FROM (
                SELECT quotation_id, date_id,
                    GROUP_CONCAT(CONCAT(`from`, '=>', `to`) ORDER BY `from`, `to` SEPARATOR '||') AS sig
                FROM tour_sub_locations
                WHERE (quotation_id, date_id) IN (
                    SELECT DISTINCT quotation_id, date_id
                    FROM tour_sub_locations
                    WHERE ($touchPlaceholders)
                      AND quotation_id != ?
                )
                GROUP BY quotation_id, date_id
                HAVING sig = ?
            ) matched
            INNER JOIN driver_meter_reading dmr
                ON dmr.tour_id = matched.quotation_id
                AND dmr.reading_date = matched.date_id
            LEFT JOIN consignee c
                ON c.tour_id = matched.quotation_id
            LEFT JOIN driver_allocation da
                ON da.tour_id = matched.quotation_id
                AND da.driver = dmr.driver_acc
            GROUP BY matched.quotation_id, matched.date_id, dmr.driver_acc, c.manual_id, da.driver_name
            HAVING day_distance IS NOT NULL",
            $bindings
        )->getResultArray();

        $matches = [];
        foreach ($rows as $row) {
            $km = (float) $row['day_distance'];
            if ($km < 0) {
                continue;
            }
            $matches[] = [
                'tour_id'     => $row['quotation_id'],
                'manual_id'   => $row['manual_id'] ?? $row['quotation_id'],
                'driver_name' => $row['driver_name'] ?? $row['driver_acc'],
                'date'        => substr((string) $row['date_id'], 0, 10),
                'distance_km' => $km,
            ];
        }

        if (empty($matches)) {
            return ['average_km' => null, 'match_count' => 0, 'matches' => []];
        }

        $distances = array_map(static fn($m) => $m['distance_km'], $matches);
        sort($distances);
        $count = count($distances);
        $mid = intdiv($count, 2);
        $median = ($count % 2 === 0)
            ? ($distances[$mid - 1] + $distances[$mid]) / 2
            : $distances[$mid];

        usort($matches, static fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);

        return [
            'average_km' => round($median, 2),
            'match_count' => $count,
            'matches' => $matches,
        ];
    }

    // Normalizes a day's legs into an order-independent signature ("from|to" pairs, sorted) so two
    // days with the same waypoint set in a different order compare equal. Returns null for an
    // empty/invalid leg list (nothing to match against).
    private function routeLegSetSignature(array $legs): ?string
    {
        $pairs = [];
        foreach ($legs as $leg) {
            $from = trim((string) ($leg['from_location'] ?? ''));
            $to = trim((string) ($leg['to_location'] ?? ''));
            if ($from === '' || $to === '') {
                continue;
            }
            $pairs[] = $from . '=>' . $to;
        }

        if (empty($pairs)) {
            return null;
        }

        sort($pairs);
        return implode('||', $pairs);
    }

    private function getDriverAllocation(string $tour_id, string $driver_acc): ?array
    {
        return (new DriverAllocationModel())
            ->where('tour_id', $tour_id)
            ->where('driver', $driver_acc)
            ->first();
    }

    private function getExpenseCategories(): array
    {
        return [
            '6-9-23-10640' => 'Entrance Fee',
            '6-9-23-10641' => 'Other',
        ];
    }

    private function getExpenseDescriptions(): array
    {
        return [
            '6-9-23-10640' => 'Entrance Fee Expenses',
            '6-9-23-10641' => 'Other Expenses',
        ];
    }

    private function getExpenseAccountsByCategory(): array
    {
        $loadAccounts = static function (int $type): array {
            return (new AccountPostingModel())
                ->select('L_Code, PName')
                ->where('B_Code', 4)
                ->where('T_Code', 8)
                ->where('H_Code', 109)
                ->where('type', $type)
                ->orderBy('PName', 'ASC')
                ->findAll();
        };

        return [
            '6-9-23-10640' => $loadAccounts(1),
            '6-9-23-10641' => $loadAccounts(2),
        ];
    }

    private function resolveExpenseSupplierName(string $category, string $supplier): ?string
    {
        $allowedAccounts = $this->getExpenseAccountsByCategory()[$category] ?? [];
        foreach ($allowedAccounts as $account) {
            if (($account['L_Code'] ?? '') === $supplier) {
                return $account['PName'] ?? $supplier;
            }
        }

        return null;
    }

    private function getPendingExpenseRows(string $tour_id, string $driver_acc): array
    {
        return (new DriverPortalExpensesModel())
            ->where('tour_id', $tour_id)
            ->where('agent', $driver_acc)
            ->where('status', 'pending')
            ->where('request_send', 0)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    private function getPendingExpenseCount(string $tour_id, string $driver_acc): int
    {
        return (new DriverPortalExpensesModel())
            ->where('tour_id', $tour_id)
            ->where('agent', $driver_acc)
            ->where('status', 'pending')
            ->where('request_send', 0)
            ->countAllResults();
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
    // log_sheet_mile row per transport_agent for this tour — matching how getLogSheetMileageByTour
    // in TransportPortalController already reads "current" mileage per driver.
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

    // Garage Mileage: a flat allowance for the driver's trip from the garage/home to the tour's
    // actual pickup point, applied only to the Predictable Mileage Range on day 1 (see
    // driverReport()) — not to the driver's actual odometer distance total, since the garage trip
    // isn't something the driver's readings would ever capture. Always 30km, no per-tour override.
    private const GARAGE_MILEAGE_KM = 30.0;

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

    // Tours can be split across multiple drivers (e.g. driver A covers days 1-3, driver B covers
    // day 4 only), and driver_allocation carries no per-driver day range. So instead of requiring
    // every calendar day of the whole tour, this only requires that each day THIS driver has
    // started a reading for is also completed (both 'start' and 'end' present). A driver who has
    // submitted nothing yet cannot finalize either — see the empty-readings check in the caller.
    private function getDaysMissingMeterReadings(string $tour_id, string $driver_acc): array
    {
        $readings = (new DriverMeterReadingModel())
            ->select('reading_date, type')
            ->where('tour_id', $tour_id)
            ->where('driver_acc', $driver_acc)
            ->findAll();

        $readingsByDate = [];
        foreach ($readings as $reading) {
            $readingDate = (string) ($reading['reading_date'] ?? '');
            $readingType = (string) ($reading['type'] ?? '');
            if ($readingDate === '' || $readingType === '') {
                continue;
            }
            $readingsByDate[$readingDate][$readingType] = true;
        }

        $missingDays = [];
        foreach ($readingsByDate as $date => $dayReadings) {
            $hasStart = $dayReadings['start'] ?? false;
            $hasEnd = $dayReadings['end'] ?? false;
            if (!$hasStart || !$hasEnd) {
                $timestamp = strtotime($date);
                $missingDays[] = $timestamp !== false ? date('d M', $timestamp) : $date;
            }
        }

        sort($missingDays);

        return $missingDays;
    }

    private function isValidDate(string $date): bool
    {
        $parsed = \DateTime::createFromFormat('Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date;
    }

    private function resolveTourDateId(string $tour_id, string $expense_date, string $postedTourDateId = ''): ?int
    {
        $tourQuotationModel = new TourQuotationsModel();

        if ($postedTourDateId !== '' && ctype_digit($postedTourDateId)) {
            $matchedRow = $tourQuotationModel
                ->select('id')
                ->where('id', (int) $postedTourDateId)
                ->where('quotation_id', $tour_id)
                ->where('tour_date', $expense_date)
                ->first();

            if (!empty($matchedRow['id'])) {
                return (int) $matchedRow['id'];
            }
        }

        $fallbackRow = $tourQuotationModel
            ->select('id')
            ->where('quotation_id', $tour_id)
            ->where('tour_date', $expense_date)
            ->orderBy('id', 'ASC')
            ->first();

        return !empty($fallbackRow['id']) ? (int) $fallbackRow['id'] : null;
    }

    private function getExpenseImageUrl(string $tour_id, int $expenseId): string
    {
        $pattern = FCPATH . 'uploads/driver_portal_expenses/' . $tour_id . '/' . $expenseId . '.*';
        $matches = glob($pattern);

        if (empty($matches)) {
            return '';
        }

        return base_url('uploads/driver_portal_expenses/' . $tour_id . '/' . basename($matches[0]));
    }

    private function canEditExpense(array $expense): bool
    {
        return (($expense['status'] ?? '') === 'pending') && (int) ($expense['request_send'] ?? 0) === 0;
    }

    private function validateExpenseImageFile($invoiceImage): ?string
    {
        if (!$invoiceImage->isValid() || $invoiceImage->hasMoved()) {
            return 'Failed to upload invoice image';
        }

        if (strpos((string) $invoiceImage->getMimeType(), 'image/') !== 0) {
            return 'Invoice image must be a valid image file';
        }

        return null;
    }

    private function ensureExpenseUploadPath(string $tour_id): string
    {
        $uploadPath = FCPATH . 'uploads/driver_portal_expenses/' . $tour_id . '/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        return $uploadPath;
    }

    private function deleteExpenseImages(string $tour_id, int $expenseId, string $keepFileName = ''): void
    {
        $pattern = FCPATH . 'uploads/driver_portal_expenses/' . $tour_id . '/' . $expenseId . '.*';
        $matches = glob($pattern);

        foreach ($matches ?: [] as $match) {
            if ($keepFileName !== '' && basename($match) === $keepFileName) {
                continue;
            }

            if (is_file($match)) {
                @unlink($match);
            }
        }
    }
}
