<?php

namespace App\Controllers\Grn;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\InvoiceNumberModel;
use App\Models\Grn\StockModel;
use App\Models\UsageNoteModel;
use App\Models\StockTransferTempModel;
use App\Models\ItemReqTokenModel;
use App\Models\UserModel;
use App\Models\ConsigneeModel;
use App\Models\Grn\UsageNoteReturnModel;
use App\Models\Grn\StockOrderModel;
// use App\Models\InvoiceNumberModel as InvoiceNoModel;

class UsageNoteController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    public function index()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Get all usage notes with tour details
        $usageNoteModel = new UsageNoteModel();
        $builder = $usageNoteModel->builder();
        $builder->select('
            usage_note.usage_note_no,
            usage_note.date,
            usage_note.tour_id,
            usage_note.status,
            usage_note.created_by,
            consignee.consignee as tour_name,
            consignee.manual_id,
            consignee.passenger_count,
            user_acc_tbl.user as created_by_name
        ');
        $builder->join('consignee', 'consignee.tour_id = usage_note.tour_id', 'LEFT');
        $builder->join('user_acc_tbl', 'user_acc_tbl.acc_no = usage_note.created_by', 'LEFT');
        $builder->groupBy('usage_note.usage_note_no');
        $builder->orderBy('usage_note.created_at', 'DESC');
        $query = $builder->get();
        $data['usage_notes'] = $query->getResultArray();

        return view('pages/grn/usage_note_list', $data);
    }

    public function create()
    {
        $data = array();

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Get current user details
        $userModel = new UserModel();
        $user_id = session()->get('acc_no');
        $user = $userModel->where('acc_no', $user_id)->first();
        $data['created_by'] = $user ? $user['user'] : 'Unknown';

        return view('pages/grn/usage_note', $data);
    }

    public function search_item()
    {
        $stockCardModel = new \App\Models\Grn\StockCardModel();
        $term = $this->request->getVar('term');

        $likes = [
            'Descrip' => $term,
            'Stock_Card' => $term,
        ];

        $items = $stockCardModel->orLike($likes)->where('Stock_Book !=', 1)->findAll(10);

        $stockModel = new StockModel();
        $data = [];
        foreach ($items as $item) {
            // Get available quantity for this item
            $builder = $stockModel->builder();
            $builder->selectSum('Dr_Qty', 'total_dr');
            $builder->selectSum('Cr_Qty', 'total_cr');
            $builder->where('Stock_Card', $item['Stock_Card']);
            $builder->where('Status', 'P');
            $query = $builder->get();
            $result = $query->getRow();

            $quantity = ($result->total_dr ?? 0) - ($result->total_cr ?? 0);

            $data[] = [
                'label' => $item['Descrip'],
                'value' => $item['Descrip'],
                'stock_card' => $item['Stock_Card'],
                'available_qty' => $quantity
            ];
        }

        return $this->response->setJSON($data);
    }

    public function get_details()
    {
        $usageNoteNo = $this->request->getGet('usage_note_no');

        if (!$usageNoteNo) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Usage note number is required']);
        }

        $usageNoteModel = new UsageNoteModel();
        $builder = $usageNoteModel->builder();
        $builder->select('usage_note.*, stock_card.Descrip as item_description, tour_activity.tour_date as activity_date, entrance_fee.name as activity_name');
        $builder->join('stock_card', 'stock_card.Stock_Card = usage_note.item_code', 'left');
        $builder->join('tour_activity', 'tour_activity.id = usage_note.activity_id', 'left');
        $builder->join('entrance_fee', 'entrance_fee.id = tour_activity.name', 'left');
        $builder->where('usage_note.usage_note_no', $usageNoteNo);
        $builder->orderBy('usage_note.activity_id', 'ASC');
        $query = $builder->get();
        $items = $query->getResultArray();

        return $this->response->setJSON(['status' => 'success', 'data' => $items]);
    }

    public function get_tour_activities()
    {
        $tourActivityModel = new \App\Models\TourActivityModel();
        $tourId = $this->request->getGet('tour_id');

        $activities = $tourActivityModel
            ->select('tour_activity.id, tour_activity.tour_date, tour_activity.code, tour_activity.ref_no, tour_activity.fee, entrance_fee.name')
            ->join('entrance_fee', 'entrance_fee.id = tour_activity.name', 'left')
            ->where('tour_activity.tour_id', $tourId)
            ->where('tour_activity.fee >', 0) // Only activities with fees
            ->orderBy('tour_activity.tour_date', 'ASC')
            ->findAll();

        return $this->response->setJSON(['status' => 200, 'activities' => $activities]);
    }

    public function search_tours()
    {
        $keyword = $this->request->getGet('keyword');

        if (empty($keyword) || strlen($keyword) < 2) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Keyword must be at least 2 characters']);
        }

        $consigneeModel = new ConsigneeModel();

        // Optimized query with LIMIT and only necessary fields
        $results = $consigneeModel
            ->select('tour_id, consignee, manual_id, passenger_count, start_date, end_date')
            ->groupStart()
            ->like('manual_id', $keyword)
            ->orLike('consignee', $keyword)
            ->groupEnd()
            ->orderBy('ID', 'DESC')
            ->limit(10) // Limit to 10 results for performance
            ->findAll();

        if (empty($results)) {
            return $this->response->setJSON(['status' => 404, 'message' => 'No tours found']);
        }

        return $this->response->setJSON(['status' => 200, 'data' => $results]);
    }

    // public function add_temp_item()
    // {
    //     $stockTransferTempModel = new StockTransferTempModel();
    //     $itemReqTokenModel = new ItemReqTokenModel();
    //     $invoiceNumberModel = new InvoiceNumberModel();

    //     $item_code = $this->request->getPost('item_code');
    //     $item_name = $this->request->getPost('item_name');
    //     $quantity = $this->request->getPost('quantity');
    //     $batch_no = $this->request->getPost('batch_no');
    //     $store_id = $this->request->getPost('store_id');
    //     $user_id = session()->get('acc_no');
    //     $token_no = $this->request->getPost('token_no');

    //     if (!$token_no) {
    //         // Generate new token
    //         $invoice = $invoiceNumberModel->first();
    //         $token_no = $invoice['Item_Token'] + 1;
    //         $itemReqTokenModel->insert([
    //             'Acc_No' => $user_id,
    //             'Flag' => 0,
    //             'Token_No' => $token_no
    //         ]);
    //         $invoiceNumberModel->set('Item_Token', $token_no)->where('id', $invoice['id'])->update();
    //     }

    //     // Prevent duplicate
    //     $exists = $stockTransferTempModel->where([
    //         'Acc_No' => $user_id,
    //         'Flag' => 0,
    //         'Item' => $item_code,
    //         'Token_No' => $token_no
    //     ])->first();
    //     if ($exists) {
    //         return $this->response->setJSON(['status' => 'exists', 'token_no' => $token_no]);
    //     }

    //     $data = [
    //         'loading' => $store_id,
    //         'Acc_No' => $user_id,
    //         'Item' => $item_code,
    //         'Descrip' => $item_name,
    //         'type' => 'Usage Note',
    //         'Qty' => $quantity,
    //         'Token_No' => $token_no,
    //         'Date' => date('Y-m-d'),
    //         'Flag' => 0,
    //         'batch_no' => $batch_no
    //     ];
    //     $stockTransferTempModel->insert($data);
    //     return $this->response->setJSON(['status' => 'success', 'token_no' => $token_no]);
    // }

    // public function delete_temp_item()
    // {
    //     $stockTransferTempModel = new StockTransferTempModel();
    //     $id = $this->request->getPost('id');
    //     $deleted = $stockTransferTempModel->delete($id);
    //     return $this->response->setJSON(['status' => $deleted ? 'success' : 'error']);
    // }

    // public function get_temp_items()
    // {
    //     $stockTransferTempModel = new StockTransferTempModel();
    //     $token_no = $this->request->getGet('token_no');
    //     $user_id = session()->get('acc_no');
    //     $items = $stockTransferTempModel->where([
    //         'Token_No' => $token_no,
    //         'Acc_No' => $user_id,
    //         'Flag' => 0,
    //         'type' => 'Usage Note'
    //     ])->findAll();
    //     return $this->response->setJSON(['items' => $items]);
    // }

    public function finalize_usage_note()
    {
        try {

            $usageNoteModel = new UsageNoteModel();

            $stockModel = new StockModel();

            $invoiceNumberModel = new InvoiceNumberModel();

            // Get JSON data from request
            $data = $this->request->getJSON(true);
            $tour_id = $data['tour_id'] ?? '';
            $items = $data['items'] ?? [];
            $remarks = $data['remarks'] ?? '';
            $user_id = session()->get('acc_no');

            // Get new usage note number
            $invoiceNumber = $invoiceNumberModel->first();

            if (!$invoiceNumber) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Invoice number not found']);
            }

            $usageNoteNo = 'CN-' . ($invoiceNumber['Stock'] + 1);

            $allSuccess = true;
            foreach ($items as $index => $item) {

                $usageNoteData = [
                    'usage_note_no' => $usageNoteNo,
                    'date' => date('Y-m-d'),
                    'tour_id' => $tour_id,
                    'activity_id' => $item['activity_id'] ?? null,
                    'activity_ref_no' => $item['activity_ref_no'] ?? null,
                    'item_code' => $item['item_code'],
                    'quantity' => $item['quantity'],
                    'created_by' => $user_id,
                ];

                try {
                    $inserted = $usageNoteModel->insert($usageNoteData);

                    if (!$inserted) {
                        $errors = $usageNoteModel->errors();
                        $allSuccess = false;
                    }
                } catch (\Exception $e) {
                    log_message('critical', 'Exception inserting usage_note: ' . $e->getMessage());
                    $allSuccess = false;
                }

                // Stock insertion removed - will happen on approval
            }

            $invoiceNumberModel->incrementStockNo();

            if ($allSuccess) {
                // log_message('critical', 'Usage notes finalized successfully.');
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Usage note created successfully and is pending authorization.',
                    'usage_note_no' => $usageNoteNo,
                    'print_url' => base_url('inventory/usage_note/print/' . $usageNoteNo)
                ]);
            } else {
                // log_message('critical', 'Failed to finalize some usage notes.');
                return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to finalize some usage notes.']);
            }
        } catch (\Exception $e) {
            log_message('critical', 'EXCEPTION in finalize_usage_note: ' . $e->getMessage());
            log_message('critical', 'Stack trace: ' . $e->getTraceAsString());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function print($usage_note_no)
    {
        $usageNoteModel = new UsageNoteModel();
        $consigneeModel = new ConsigneeModel();

        // Get usage note details
        $usageNoteDetails = $usageNoteModel->where('usage_note_no', $usage_note_no)->findAll();

        if (empty($usageNoteDetails)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Usage note not found');
        }

        // Get status from first item (all items have same status for a given usage_note_no)
        $status = $usageNoteDetails[0]['status'] ?? 'pending';
        $approved_by = $usageNoteDetails[0]['approved_by'] ?? null;
        $approved_at = $usageNoteDetails[0]['approved_at'] ?? null;
        $rejection_reason = $usageNoteDetails[0]['rejection_reason'] ?? null;

        // Get tour details
        $tour_id = $usageNoteDetails[0]['tour_id'];
        $tour = $consigneeModel->where('tour_id', $tour_id)->first();

        $userModel = new UserModel();
        $user = $userModel->where('acc_no', $usageNoteDetails[0]['created_by'])->first();
        $created_by = $user['user'];

        // Group items by activity
        $groupedByActivity = [];
        foreach ($usageNoteDetails as $detail) {
            $activityKey = $detail['activity_id'] ?: 'no_activity';
            if (!isset($groupedByActivity[$activityKey])) {
                $groupedByActivity[$activityKey] = [
                    'activity_id' => $detail['activity_id'],
                    'activity_ref_no' => $detail['activity_ref_no'],
                    'items' => []
                ];
            }
            $groupedByActivity[$activityKey]['items'][] = $detail;
        }

        $data = [
            'usage_note_no' => $usage_note_no,
            'usage_note_details' => $usageNoteDetails,
            'grouped_items' => $groupedByActivity,
            'tour' => $tour,
            'date' => $usageNoteDetails[0]['date'],
            'created_by' => $created_by,
            'status' => $status,
            'approved_by' => $approved_by,
            'approved_at' => $approved_at,
            'rejection_reason' => $rejection_reason
        ];

        return view('pages/grn/usage_note_print', $data);
    }

    public function report()
    {
        $data = array();

        // Load menu data
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Get all stock items for dropdown using StockCardModel
        $stockCardModel = new \App\Models\Grn\StockCardModel();
        $data['stock_items'] = $stockCardModel
            ->select('Stock_Card, Descrip')
            ->where('Stock_Book !=', 1)
            ->orderBy('Descrip', 'ASC')
            ->findAll();

        // Get filter parameters
        $filters = [
            'startdate' => $this->request->getGet('startdate'),
            'enddate' => $this->request->getGet('enddate'),
            'tour_keyword' => $this->request->getGet('tour_keyword'),
            'item_keyword' => $this->request->getGet('item_keyword')
        ];

        // Store filter values for view
        $data['startdate'] = $filters['startdate'];
        $data['enddate'] = $filters['enddate'];
        $data['tour_keyword'] = $filters['tour_keyword'];
        $data['item_keyword'] = $filters['item_keyword'];
        $data['is_search'] = false;
        $data['resultlist'] = [];
        $data['summary'] = [
            'total_cns' => 0,
            'total_items' => 0,
            'total_quantity' => 0,
            'total_value' => 0
        ];

        // Only execute query if at least one filter is provided or form submitted
        if (!empty($filters['startdate']) || !empty($filters['enddate']) || !empty($filters['tour_keyword']) || !empty($filters['item_keyword']) || $this->request->getGet('submit')) {
            $data['is_search'] = true;

            // Use model method to get report data
            $usageNoteModel = new UsageNoteModel();
            $resultlist = $usageNoteModel->getReportData($filters);
            $data['resultlist'] = $resultlist;

            // Calculate summary statistics
            if (!empty($resultlist)) {
                $unique_cns = array_unique(array_column($resultlist, 'usage_note_no'));
                $unique_items = array_unique(array_column($resultlist, 'item_code'));
                $total_quantity = array_sum(array_column($resultlist, 'quantity'));
                $total_value = array_sum(array_column($resultlist, 'total_value'));

                $data['summary'] = [
                    'total_cns' => count($unique_cns),
                    'total_items' => count($unique_items),
                    'total_quantity' => $total_quantity,
                    'total_value' => $total_value
                ];
            }
        }

        return view('pages/grn/usage_note_report', $data);
    }

    public function report_export_excel()
    {
        // Get filter parameters
        $startdate = $this->request->getGet('startdate');
        $enddate = $this->request->getGet('enddate');
        $tour_keyword = $this->request->getGet('tour_keyword');
        $item_keyword = $this->request->getGet('item_keyword');

        $usageNoteModel = new UsageNoteModel();
        $builder = $usageNoteModel->builder();

        $builder->select('
            usage_note.usage_note_no,
            usage_note.date,
            usage_note.tour_id,
            consignee.manual_id,
            consignee.consignee,
            entrance_fee.name as activity_name,
            tour_activity.tour_date as activity_date,
            usage_note.item_code,
            stock_card.Descrip as item_description,
            usage_note.quantity,
            COALESCE(stock.Unit_Cost, stock.pPrice, 0) as unit_cost,
            (usage_note.quantity * COALESCE(stock.Unit_Cost, stock.pPrice, 0)) as total_value,
            user_acc_tbl.user as created_by_name
        ');

        $builder->join('consignee', 'consignee.tour_id = usage_note.tour_id', 'LEFT');
        $builder->join('stock_card', 'stock_card.Stock_Card = usage_note.item_code', 'LEFT');
        $builder->join('tour_activity', 'tour_activity.id = usage_note.activity_id', 'LEFT');
        $builder->join('entrance_fee', 'entrance_fee.id = tour_activity.name', 'LEFT');
        $builder->join('user_acc_tbl', 'user_acc_tbl.acc_no = usage_note.created_by', 'LEFT');

        // Subquery for stock cost - get latest cost with proper grouping
        $builder->join('(
            SELECT s1.Stock_Card,
                   s1.Unit_Cost,
                   s1.pPrice
            FROM stock s1
            INNER JOIN (
                SELECT Stock_Card, MAX(ID) as max_id
                FROM stock
                WHERE (Unit_Cost > 0 OR pPrice > 0)
                GROUP BY Stock_Card
            ) s2 ON s1.Stock_Card = s2.Stock_Card AND s1.ID = s2.max_id
        ) as stock', 'stock.Stock_Card = usage_note.item_code', 'LEFT');

        // Apply same filters
        if (!empty($startdate)) {
            $builder->where('usage_note.date >=', $startdate);
        }
        if (!empty($enddate)) {
            $builder->where('usage_note.date <=', $enddate);
        }
        if (!empty($tour_keyword)) {
            $builder->groupStart()
                ->like('consignee.tour_id', $tour_keyword)
                ->orLike('consignee.consignee', $tour_keyword)
                ->orLike('consignee.manual_id', $tour_keyword)
                ->groupEnd();
        }
        if (!empty($item_keyword)) {
            $builder->groupStart()
                ->like('stock_card.Stock_Card', $item_keyword)
                ->orLike('stock_card.Descrip', $item_keyword)
                ->groupEnd();
        }

        $builder->orderBy('usage_note.date', 'DESC');
        $builder->orderBy('usage_note.usage_note_no', 'DESC');

        $data = $builder->get()->getResultArray();

        // Prepare CSV
        $filename = 'usage_note_report_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // CSV Headers
        fputcsv($output, [

            'Date',
            'Tour ID',
            'Manual ID',
            'Tour Name',
            'Activity',
            'Activity Date',
            'Item Code',
            'Item Description',
            'Quantity',
            'Unit Cost',
            'Total Value',
            'Created By'
        ]);

        // CSV Data
        foreach ($data as $row) {
            fputcsv($output, [

                date('Y-m-d', strtotime($row['date'])),
                $row['tour_id'],
                $row['manual_id'] ?? 'N/A',
                $row['consignee'],
                $row['activity_name'] ?? 'N/A',
                $row['activity_date'] ? date('Y-m-d', strtotime($row['activity_date'])) : 'N/A',
                $row['item_code'],
                $row['item_description'],
                $row['quantity'],
                number_format($row['unit_cost'], 2),
                number_format($row['total_value'], 2),
                $row['created_by_name']
            ]);
        }

        fclose($output);
        exit;
    }

    public function report_export_pdf()
    {
        // Get filter parameters
        $startdate = $this->request->getGet('startdate');
        $enddate = $this->request->getGet('enddate');
        $tour_keyword = $this->request->getGet('tour_keyword');
        $item_keyword = $this->request->getGet('item_keyword');

        $usageNoteModel = new UsageNoteModel();
        $builder = $usageNoteModel->builder();

        $builder->select('
            usage_note.usage_note_no,
            usage_note.date,
            usage_note.tour_id,
            consignee.manual_id,
            consignee.consignee,
            entrance_fee.name as activity_name,
            tour_activity.tour_date as activity_date,
            usage_note.item_code,
            stock_card.Descrip as item_description,
            usage_note.quantity,
            COALESCE(stock.Unit_Cost, stock.pPrice, 0) as unit_cost,
            (usage_note.quantity * COALESCE(stock.Unit_Cost, stock.pPrice, 0)) as total_value,
            user_acc_tbl.user as created_by_name
        ');

        $builder->join('consignee', 'consignee.tour_id = usage_note.tour_id', 'LEFT');
        $builder->join('stock_card', 'stock_card.Stock_Card = usage_note.item_code', 'LEFT');
        $builder->join('tour_activity', 'tour_activity.id = usage_note.activity_id', 'LEFT');
        $builder->join('entrance_fee', 'entrance_fee.id = tour_activity.name', 'LEFT');
        $builder->join('user_acc_tbl', 'user_acc_tbl.acc_no = usage_note.created_by', 'LEFT');

        // Subquery for stock cost - get latest cost with proper grouping
        $builder->join('(
            SELECT s1.Stock_Card,
                   s1.Unit_Cost,
                   s1.pPrice
            FROM stock s1
            INNER JOIN (
                SELECT Stock_Card, MAX(ID) as max_id
                FROM stock
                WHERE (Unit_Cost > 0 OR pPrice > 0)
                GROUP BY Stock_Card
            ) s2 ON s1.Stock_Card = s2.Stock_Card AND s1.ID = s2.max_id
        ) as stock', 'stock.Stock_Card = usage_note.item_code', 'LEFT');

        // Apply same filters
        if (!empty($startdate)) {
            $builder->where('usage_note.date >=', $startdate);
        }
        if (!empty($enddate)) {
            $builder->where('usage_note.date <=', $enddate);
        }
        if (!empty($tour_keyword)) {
            $builder->groupStart()
                ->like('consignee.tour_id', $tour_keyword)
                ->orLike('consignee.consignee', $tour_keyword)
                ->orLike('consignee.manual_id', $tour_keyword)
                ->groupEnd();
        }
        if (!empty($item_keyword)) {
            $builder->groupStart()
                ->like('stock_card.Stock_Card', $item_keyword)
                ->orLike('stock_card.Descrip', $item_keyword)
                ->groupEnd();
        }

        $builder->orderBy('usage_note.date', 'DESC');
        $builder->orderBy('usage_note.usage_note_no', 'DESC');

        $resultlist = $builder->get()->getResultArray();

        // Calculate summary
        $unique_cns = array_unique(array_column($resultlist, 'usage_note_no'));
        $unique_items = array_unique(array_column($resultlist, 'item_code'));
        $total_quantity = array_sum(array_column($resultlist, 'quantity'));
        $total_value = array_sum(array_column($resultlist, 'total_value'));

        // Build HTML for PDF
        $html = '
        <style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            h2 { text-align: center; color: #204080; }
            h4 { text-align: center; margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            th { background-color: #f2f2f2; padding: 8px; border: 1px solid #ddd; text-align: left; font-weight: bold; }
            td { padding: 6px; border: 1px solid #ddd; }
            .text-right { text-align: right; }
            .summary-box { background: #f9f9f9; padding: 10px; margin: 10px 0; border: 1px solid #ddd; }
            .total-row { background-color: #e3f2fd; font-weight: bold; }
        </style>
        <h2>AVENIR HOLIDAYS (PVT) LTD</h2>
        <h4>Usage Note Report</h4>
        <p style="text-align: center;">Generated on: ' . date('Y-m-d H:i:s') . '</p>';

        if (!empty($startdate) || !empty($enddate)) {
            $html .= '<p style="text-align: center;">Period: ';
            $html .= !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : 'Start';
            $html .= ' to ';
            $html .= !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : 'End';
            $html .= '</p>';
        }

        // Summary statistics


        // Detailed table
        $html .= '
        <table>
            <thead>
                <tr>
                    <th>#</th>
           
                    <th>Date</th>
                    <th>Tour ID</th>
                    <th>Tour Name</th>
                    <th>Activity</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit Cost</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>';

        $serial = 1;
        foreach ($resultlist as $row) {
            $html .= '<tr>
                <td>' . $serial++ . '</td>

                <td>' . date('Y-m-d', strtotime($row['date'])) . '</td>
                <td>' . htmlspecialchars($row['tour_id']) . '</td>
                <td>' . htmlspecialchars($row['consignee']) . '</td>
                <td>' . htmlspecialchars($row['activity_name'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($row['item_code']) . '</td>
                <td>' . htmlspecialchars($row['item_description']) . '</td>
                <td class="text-right">' . number_format($row['quantity']) . '</td>
                <td class="text-right">' . number_format($row['unit_cost'], 2) . '</td>
                <td class="text-right">' . number_format($row['total_value'], 2) . '</td>
            </tr>';
        }

        $html .= '<tr class="total-row">
                <td colspan="8" class="text-right"><strong>GRAND TOTAL:</strong></td>
                <td class="text-right"><strong>' . number_format($total_quantity) . '</strong></td>
                <td></td>
                <td class="text-right"><strong>Rs. ' . number_format($total_value, 2) . '</strong></td>
            </tr>';

        $html .= '</tbody></table>';

        // Generate PDF
        $pdf = new \TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Avenir Holidays');
        $pdf->SetAuthor('Avenir Holidays');
        $pdf->SetTitle('Usage Note Report');
        $pdf->SetSubject('Usage Note Report');

        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);

        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = 'usage_note_report_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    public function authorization_panel()
    {
        $data = array();
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // User will manually assign permission - no permission check here
        // Just load the view

        return view('pages/grn/usage_note_authorization', $data);
    }

    public function get_pending_usage_notes()
    {
        try {
            $usageNoteModel = new UsageNoteModel();
            $consigneeModel = new ConsigneeModel();

            $builder = $usageNoteModel->builder();

            // Get status filter (all, pending, approved, rejected)
            $statusFilter = $this->request->getGet('status') ?? 'pending';

            $builder->select('
                usage_note.usage_note_no,
                usage_note.date,
                usage_note.tour_id,
                usage_note.status,
                usage_note.created_by,
                usage_note.approved_by,
                usage_note.approved_at,
                usage_note.rejection_reason,
                consignee.consignee as tour_name,
                consignee.manual_id,
                creator.user as created_by_name,
                approver.user as approved_by_name,
                COUNT(DISTINCT usage_note.item_code) as item_count,
                SUM(usage_note.quantity) as total_quantity
            ');

            $builder->join('consignee', 'consignee.tour_id = usage_note.tour_id', 'LEFT');
            // user_acc_tbl.acc_no is not unique (duplicate accounts exist), so join a
            // deduped one-row-per-acc_no subquery instead of the raw table — joining
            // the raw table fans out usage_note rows and doubles SUM(quantity)/item_count.
            $builder->join('(SELECT acc_no, MIN(id) as min_id FROM user_acc_tbl GROUP BY acc_no) as creator_ids', 'creator_ids.acc_no = usage_note.created_by', 'LEFT');
            $builder->join('user_acc_tbl as creator', 'creator.id = creator_ids.min_id', 'LEFT');
            $builder->join('(SELECT acc_no, MIN(id) as min_id FROM user_acc_tbl GROUP BY acc_no) as approver_ids', 'approver_ids.acc_no = usage_note.approved_by', 'LEFT');
            $builder->join('user_acc_tbl as approver', 'approver.id = approver_ids.min_id', 'LEFT');

            if ($statusFilter !== 'all') {
                $builder->where('usage_note.status', $statusFilter);
            }

            $builder->groupBy('usage_note.usage_note_no');
            $builder->orderBy('usage_note.created_at', 'DESC');

            $results = $builder->get()->getResultArray();

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $results
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching usage notes: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to fetch usage notes'
            ]);
        }
    }

    public function get_usage_note_details($usage_note_no)
    {
        try {
            $usageNoteModel = new UsageNoteModel();
            $consigneeModel = new ConsigneeModel();

            $builder = $usageNoteModel->builder();

            $builder->select('
                usage_note.*,
                stock_card.Descrip as item_description,
                tour_activity.tour_date as activity_date,
                entrance_fee.name as activity_name,
                consignee.consignee as tour_name,
                consignee.manual_id,
                consignee.passenger_count,
                creator.user as created_by_name,
                approver.user as approved_by_name,
                COALESCE(stock.Unit_Cost, stock.pPrice, 0) as unit_cost,
                (usage_note.quantity * COALESCE(stock.Unit_Cost, stock.pPrice, 0)) as total_value
            ');

            $builder->join('stock_card', 'stock_card.Stock_Card = usage_note.item_code', 'LEFT');
            $builder->join('tour_activity', 'tour_activity.id = usage_note.activity_id', 'LEFT');
            $builder->join('entrance_fee', 'entrance_fee.id = tour_activity.name', 'LEFT');
            $builder->join('consignee', 'consignee.tour_id = usage_note.tour_id', 'LEFT');
            // user_acc_tbl.acc_no is not unique (duplicate accounts exist), so join a
            // deduped one-row-per-acc_no subquery instead of the raw table — joining
            // the raw table fans out usage_note rows and duplicates every line item shown.
            $builder->join('(SELECT acc_no, MIN(id) as min_id FROM user_acc_tbl GROUP BY acc_no) as creator_ids', 'creator_ids.acc_no = usage_note.created_by', 'LEFT');
            $builder->join('user_acc_tbl as creator', 'creator.id = creator_ids.min_id', 'LEFT');
            $builder->join('(SELECT acc_no, MIN(id) as min_id FROM user_acc_tbl GROUP BY acc_no) as approver_ids', 'approver_ids.acc_no = usage_note.approved_by', 'LEFT');
            $builder->join('user_acc_tbl as approver', 'approver.id = approver_ids.min_id', 'LEFT');

            // Subquery for stock cost - get latest cost with proper grouping
            $builder->join('(
                SELECT s1.Stock_Card,
                       s1.Unit_Cost,
                       s1.pPrice
                FROM stock s1
                INNER JOIN (
                    SELECT Stock_Card, MAX(ID) as max_id
                    FROM stock
                    WHERE (Unit_Cost > 0 OR pPrice > 0)
                    GROUP BY Stock_Card
                ) s2 ON s1.Stock_Card = s2.Stock_Card AND s1.ID = s2.max_id
            ) as stock', 'stock.Stock_Card = usage_note.item_code', 'LEFT');

            $builder->where('usage_note.usage_note_no', $usage_note_no);

            $results = $builder->get()->getResultArray();

            if (empty($results)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Usage note not found'
                ]);
            }

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $results
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching usage note details: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to fetch details'
            ]);
        }
    }

    public function approve_usage_note()
    {
        try {
            $usageNoteModel = new UsageNoteModel();
            $stockModel = new StockModel();

            $data = $this->request->getJSON(true);
            $usage_note_no = $data['usage_note_no'] ?? '';
            $user_id = session()->get('acc_no');

            if (empty($usage_note_no)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Usage note number is required'
                ]);
            }

            // Get all items for this usage note
            $items = $usageNoteModel->where('usage_note_no', $usage_note_no)->findAll();

            if (empty($items)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Usage note not found'
                ]);
            }

            // Check if already approved/rejected
            if ($items[0]['status'] !== 'pending') {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'This usage note has already been ' . $items[0]['status']
                ]);
            }

            $db = $usageNoteModel->db;

            // Verify each item has enough stock before approving anything —
            // approval used to insert Cr_Qty unconditionally, which let stock
            // go negative. Aggregate requested quantity per item first in case
            // the same item appears on multiple lines of this usage note.
            $requestedByItem = [];
            foreach ($items as $item) {
                $requestedByItem[$item['item_code']] = ($requestedByItem[$item['item_code']] ?? 0) + $item['quantity'];
            }

            $insufficient = [];
            foreach ($requestedByItem as $itemCode => $requestedQty) {
                $balRow = $db->query(
                    'SELECT COALESCE(SUM(Dr_Qty), 0) - COALESCE(SUM(Cr_Qty), 0) AS balance FROM stock WHERE Stock_Card = ?',
                    [$itemCode]
                )->getRowArray();
                $available = $balRow['balance'] ?? 0;

                if ($requestedQty > $available) {
                    $insufficient[] = "{$itemCode} (requested {$requestedQty}, available {$available})";
                }
            }

            if (!empty($insufficient)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Insufficient stock for: ' . implode(', ', $insufficient)
                ]);
            }

            // Start database transaction
            $db->transStart();

            // Update usage_note records to approved
            $usageNoteModel->where('usage_note_no', $usage_note_no)
                ->set([
                    'status' => 'approved',
                    'approved_by' => $user_id,
                    'approved_at' => date('Y-m-d H:i:s')
                ])
                ->update();

            $invoiceNumberModel = new InvoiceNumberModel();
            $ref = $invoiceNumberModel->incrementStockNo();

            // i need to insert stock_orders table to record. only one record
            // stock order
            $data = array(
                'Date' => date('Y-m-d H:i:s'),
                'Type' => 'Issue',
                'Login' => session()->get('username'),
                'Ref' => $ref,
                'P_Order' => '',
                'Sup_Acc' => '',
                'Job' => '',
                'cashier' => session()->get('username'),
                'Stock_date' => date('Y-m-d H:i:s'),
                'GRN_no' => '',
                'GRN_Type' => '',
                'indx_no' => $items[0]['tour_id'],
            );

            $stockOrderModel = new StockOrderModel();
            $stockOrderModel->insert($data);

            // Insert stock transactions for each item
            foreach ($items as $item) {
                $stockData = [
                    'Stock_Card' => $item['item_code'],
                    'Cr_Qty' => $item['quantity'],  // Credit (consumption)
                    'Dr_Qty' => 0,
                    'Ref' => $ref,
                    'd_date' => $item['date'],
                    'Acc_No' => $item['created_by'],
                    'Descrip' => '', // Will be filled from stock_card via JOIN if needed
                    'Status' => 'P',  // Pending - means active/posted
                    'txt' => 'Usage Note - Tour: ' . $item['tour_id'],
                    'app_by' => $user_id,
                    'app_date' => date('Y-m-d H:i:s')
                ];

                $stockModel->insert($stockData);
            }

            // Complete transaction
            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Transaction failed. Please try again.'
                ]);
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Usage note approved successfully.',
                'print_url' => base_url('inventory/usage_note/print/' . $usage_note_no)
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error approving usage note: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function reject_usage_note()
    {
        try {
            $usageNoteModel = new UsageNoteModel();

            $data = $this->request->getJSON(true);
            $usage_note_no = $data['usage_note_no'] ?? '';
            $rejection_reason = $data['rejection_reason'] ?? '';
            $user_id = session()->get('acc_no');

            if (empty($usage_note_no)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Usage note number is required'
                ]);
            }

            if (empty($rejection_reason)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Rejection reason is required'
                ]);
            }

            // Get usage note to check status
            $items = $usageNoteModel->where('usage_note_no', $usage_note_no)->findAll();

            if (empty($items)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Usage note not found'
                ]);
            }

            // Check if already approved/rejected
            if ($items[0]['status'] !== 'pending') {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'This usage note has already been ' . $items[0]['status']
                ]);
            }

            // Update usage_note records to rejected
            $updated = $usageNoteModel->where('usage_note_no', $usage_note_no)
                ->set([
                    'status' => 'rejected',
                    'approved_by' => $user_id,
                    'approved_at' => date('Y-m-d H:i:s'),
                    'rejection_reason' => $rejection_reason
                ])
                ->update();

            if (!$updated) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to reject usage note'
                ]);
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Usage note rejected successfully. No stock changes were made.'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error rejecting usage note: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    // route : base_url/inventory/usage_note/process_tour_return
    public function process_tour_return()
    {
        try {
            // Get POST data
            $usageNoteId = $this->request->getPost('usage_note_id');
            $returnQuantity = $this->request->getPost('return_quantity');
            $returnDate = $this->request->getPost('return_date');
            $returnReason = $this->request->getPost('return_reason');
            $notes = $this->request->getPost('notes');
            $unitCost = $this->request->getPost('unit_cost');

            // Validation
            if (empty($usageNoteId) || empty($returnQuantity) || empty($returnDate) || empty($returnReason)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'All required fields must be provided'
                ]);
            }

            if ($returnQuantity <= 0) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Return quantity must be greater than zero'
                ]);
            }

            // Fetch original usage_note record
            $usageNoteModel = new UsageNoteModel();
            $usageNote = $usageNoteModel->find($usageNoteId);

            if (!$usageNote) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Usage note not found'
                ]);
            }

            // CRITICAL VALIDATION: Check if approved
            if ($usageNote['status'] !== 'approved') {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Can only return from approved usage notes'
                ]);
            }

            // Calculate total already returned
            $usageNoteReturnModel = new UsageNoteReturnModel();
            $builder = $usageNoteReturnModel->builder();
            $builder->selectSum('quantity', 'total_returned');
            $builder->where('usage_note_id', $usageNoteId);
            $builder->where('status', 'processed');
            $result = $builder->get()->getRowArray();
            $totalReturned = $result['total_returned'] ?? 0;

            // CRITICAL VALIDATION: Prevent over-return
            $returnable = $usageNote['quantity'] - $totalReturned;
            if ($returnQuantity > $returnable) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Cannot return more than issued quantity. Returnable: ' . $returnable
                ]);
            }

            // Validate return date
            if ($returnDate < $usageNote['date']) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Return date cannot be before original issue date (' . $usageNote['date'] . ')'
                ]);
            }

            // Generate return reference
            $invoiceNumberModel = new InvoiceNumberModel();
            $stockRef = $invoiceNumberModel->incrementStockNo();
            $returnRef = 'UNRTN-' . $stockRef;

            // Get username
            $username = session()->get('username') ?? 'system';

            // Calculate return value
            $returnValue = $returnQuantity * $unitCost;

            // Get usage_note_no
            $usageNoteNo = $usageNote['usage_note_no'];

            // Start database transaction
            $db = $usageNoteModel->db;
            $db->transStart();

            // 1. Insert into stock_return table
            $returnData = [
                'return_ref' => $returnRef,
                'stock_card' => $usageNote['item_code'],
                'return_date' => $returnDate,
                'quantity' => $returnQuantity,
                'unit_cost' => $unitCost,
                'total_value' => $returnValue,
                'reason' => $returnReason,
                'notes' => $notes,
                'usage_note_id' => $usageNoteId,
                'usage_note_no' => $usageNoteNo,
                'tour_id' => $usageNote['tour_id'],
                'original_quantity' => $usageNote['quantity'],
                'created_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'processed'
            ];

            $usageNoteReturnModel->insert($returnData);
            $returnId = $db->insertID();

            // 2. Create stock transaction (Dr_Qty to restore stock)
            $stockModel = new StockModel();
            $stockData = [
                'Stock_Card' => $usageNote['item_code'],
                'Dr_Qty' => $returnQuantity,  // DEBIT: Stock increase (return)
                'Cr_Qty' => 0,
                'Ref' => $returnRef,
                'Unit_Cost' => $unitCost,
                'Status' => 'P',  // Posted/Active
                'tym' => $returnDate,
                'd_date' => $returnDate,
                'V_Num' => $returnRef,
                'Descrip' => 'Return from Usage Note ' . $usageNoteNo . ' - Tour: ' . $usageNote['tour_id'],
                'txt' => 'Tour Return - ' . $returnReason,
                'foc' => 0,
                'pPrice' => $unitCost,
                'app_by' => session()->get('acc_no'),
                'app_date' => date('Y-m-d H:i:s')
            ];

            $stockModel->insert($stockData);
            $stockId = $db->insertID();

            // 3. Update usage_note_return with stock_ref
            $usageNoteReturnModel->update($returnId, ['stock_ref' => $stockId]);

            // Complete transaction
            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Transaction failed. Please try again.'
                ]);
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Return processed successfully. Reference: ' . $returnRef,
                'reference' => $returnRef,
                'quantity_returned' => $returnQuantity,
                'value' => $returnValue
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Tour return error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error processing tour return: ' . $e->getMessage()
            ]);
        }
    }
}
