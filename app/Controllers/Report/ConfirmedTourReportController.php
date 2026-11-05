<?php

namespace App\Controllers\Report;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\TourQuotationsModel;
use App\Models\ConsigneeModel;

class ConfirmedTourReportController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/report/confirmed_tours

    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Get filter parameters
        $startdate = $this->request->getGet('startdate');
        $data['date1'] = $startdate;

        $enddate = $this->request->getGet('enddate');
        $data['date2'] = $enddate;

        $keyword = $this->request->getGet('keyword');
        $keyword = urldecode($keyword);
        $data['keyword'] = $keyword;

        $data['is_search'] = true;
        $data['resultlist'] = [];

        $TourQuotationsModel = new TourQuotationsModel();

        // Build query
        $result = $TourQuotationsModel->select('
            tour_quotations.quotation_id AS tour_id,
            MAX(consignee.manual_id) AS manual_id,
            MAX(consignee.consignee) AS tour_name,
            MIN(consignee.start_date) AS start_date,
            MAX(consignee.end_date) AS end_date,
            MAX(tour_quotations.confirm) AS confirm_status,
            MAX(tour_quotations.finalize) AS finalize_status
        ')
        ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')
        ->where('tour_quotations.confirm !=', 0)
        ->where('tour_quotations.confirm !=', 3);

        // Apply date filters
        if (!empty($startdate)) {
            $result->where('consignee.start_date >=', $startdate);
        }

        if (!empty($enddate)) {
            $result->where('consignee.end_date <=', $enddate);
        }

        // Apply keyword search
        if (!empty($keyword)) {
            $result->groupStart()
                ->like('tour_quotations.quotation_id', $keyword)
                ->orLike('consignee.manual_id', $keyword)
                ->orLike('consignee.consignee', $keyword)
                ->groupEnd();
        }

        $result->groupBy('tour_quotations.quotation_id')
            ->orderBy('consignee.start_date', 'DESC');

        $data['resultlist'] = $result->findAll();

        return view('pages/report/confirmed_tour_report', $data);
    }

    // route : base_url/report/confirmed_tours/export_excel
    public function exportExcel()
    {
        try {
            // Get filter parameters
            $startdate = $this->request->getGet('startdate');
            $enddate = $this->request->getGet('enddate');
            $keyword = $this->request->getGet('keyword');
            $keyword = urldecode($keyword);

            $TourQuotationsModel = new TourQuotationsModel();

            // Build query - same as index method
            $result = $TourQuotationsModel->select('
                MAX(consignee.manual_id) AS manual_id,
                MAX(consignee.consignee) AS tour_name,
                MIN(consignee.start_date) AS start_date,
                MAX(consignee.end_date) AS end_date
            ')
            ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')
            ->where('tour_quotations.confirm !=', 0)
            ->where('tour_quotations.confirm !=', 3);

            // Apply date filters
            if (!empty($startdate)) {
                $result->where('consignee.start_date >=', $startdate);
            }

            if (!empty($enddate)) {
                $result->where('consignee.end_date <=', $enddate);
            }

            // Apply keyword search
            if (!empty($keyword)) {
                $result->groupStart()
                    ->like('tour_quotations.quotation_id', $keyword)
                    ->orLike('consignee.manual_id', $keyword)
                    ->orLike('consignee.consignee', $keyword)
                    ->groupEnd();
            }

            $result->groupBy('tour_quotations.quotation_id')
                ->orderBy('consignee.start_date', 'DESC');

            $data = $result->findAll();

            // Prepare CSV data
            $filename = 'confirmed_tour_report_' . date('Y-m-d_His') . '.csv';

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            $output = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($output, [
                'Manual ID',
                'Tour Name',
                'Start Date',
                'End Date'
            ]);

            // CSV Data
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['manual_id'] ?? '-',
                    $row['tour_name'],
                    date('Y-m-d', strtotime($row['start_date'])),
                    date('Y-m-d', strtotime($row['end_date']))
                ]);
            }

            fclose($output);
            exit;
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
            exit;
        }
    }

    // route : base_url/report/confirmed_tours/export_pdf
    public function exportPdf()
    {
        try {
            // Get filter parameters
            $startdate = $this->request->getGet('startdate');
            $enddate = $this->request->getGet('enddate');
            $keyword = $this->request->getGet('keyword');
            $keyword = urldecode($keyword);

            $TourQuotationsModel = new TourQuotationsModel();

            // Build query - same as index method
            $result = $TourQuotationsModel->select('
                MAX(consignee.manual_id) AS manual_id,
                MAX(consignee.consignee) AS tour_name,
                MIN(consignee.start_date) AS start_date,
                MAX(consignee.end_date) AS end_date
            ')
            ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')
            ->where('tour_quotations.confirm !=', 0)
            ->where('tour_quotations.confirm !=', 3);

            // Apply date filters
            if (!empty($startdate)) {
                $result->where('consignee.start_date >=', $startdate);
            }

            if (!empty($enddate)) {
                $result->where('consignee.end_date <=', $enddate);
            }

            // Apply keyword search
            if (!empty($keyword)) {
                $result->groupStart()
                    ->like('tour_quotations.quotation_id', $keyword)
                    ->orLike('consignee.manual_id', $keyword)
                    ->orLike('consignee.consignee', $keyword)
                    ->groupEnd();
            }

            $result->groupBy('tour_quotations.quotation_id')
                ->orderBy('consignee.start_date', 'DESC');

            $data = $result->findAll();

            // Create PDF
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator('Avenir Holidays');
            $pdf->SetAuthor('Avenir Holidays');
            $pdf->SetTitle('Confirmed Tour Report');
            $pdf->SetSubject('Confirmed Tour Report');

            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set margins
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 15);

            // Add a page
            $pdf->AddPage();

            // Set font
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Confirmed Tour Report', 0, 1, 'C');
            $pdf->Ln(5);

            // Add filter info if applicable
            $pdf->SetFont('helvetica', '', 10);
            if (!empty($startdate) || !empty($enddate) || !empty($keyword)) {
                $filterText = 'Filters: ';
                $filters = [];
                if (!empty($keyword)) $filters[] = 'Keyword: ' . $keyword;
                if (!empty($startdate)) $filters[] = 'Start Date: ' . $startdate;
                if (!empty($enddate)) $filters[] = 'End Date: ' . $enddate;
                $filterText .= implode(' | ', $filters);
                $pdf->Cell(0, 8, $filterText, 0, 1, 'L');
                $pdf->Ln(3);
            }

            // Build HTML table
            $html = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr style="background-color: #343a40; color: #ffffff; font-weight: bold;">
                        <th style="width: 20%;">Manual ID</th>
                        <th style="width: 40%;">Tour Name</th>
                        <th style="width: 20%;">Start Date</th>
                        <th style="width: 20%;">End Date</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($data as $row) {
                $html .= '<tr>
                    <td>' . ($row['manual_id'] ?? '-') . '</td>
                    <td>' . htmlspecialchars($row['tour_name']) . '</td>
                    <td>' . date('Y-m-d', strtotime($row['start_date'])) . '</td>
                    <td>' . date('Y-m-d', strtotime($row['end_date'])) . '</td>
                </tr>';
            }

            $html .= '</tbody></table>';

            // Print HTML table
            $pdf->writeHTML($html, true, false, true, false, '');

            // Output PDF
            $filename = 'confirmed_tour_report_' . date('Y-m-d_His') . '.pdf';
            $pdf->Output($filename, 'D');
            exit;
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
            exit;
        }
    }
}
