<?php

namespace App\Controllers\NewDashboard;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\ConsigneeModel;
use App\Models\VoucherPaymentModel;
use App\Models\VoucherInvoiceModel;

class VoucherAuthenticationController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    public function auth()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Set dashboard2 flag
        $data['tile_dashboard'] = true;

        $tour_id = urldecode($this->request->getGet('tour_id'));
        $tour = $this->request->getGet('tour_id');
        $vouchernumber = $this->request->getGet('voucher_number');

        $consigneemodel = new ConsigneeModel();

        $data['tour_details'] = $consigneemodel->where('tour_id', $tour)->first();

        $voucherpaymentmodel  = new VoucherPaymentModel();
        $data['voucher_request'] =  $voucherpaymentmodel->load_vouchers($tour_id, $vouchernumber);

        // Make tour_id available to view for conditional display
        $data['tour_id'] = $tour_id;

        return view('pages/dashboard2/voucher_authentication', $data);
    }

    public function getVoucherDetails()
    {
        $v_num = strip_tags($this->request->getGet('v_num'));

        if (!$v_num) {
            echo json_encode(['error' => 'Voucher number not provided']);
            return;
        }

        $voucherinvmodel = new VoucherInvoiceModel();

        // Assuming your model method returns data
        $data = $voucherinvmodel->select('*')->where('V_tkn', $v_num)->findAll();

        // Check if data is found
        if (empty($data)) {
            echo json_encode(['error' => 'No data found for the given voucher number']);
            return;
        }

        // Convert data to JSON and output it
        echo json_encode($data);
    }

    public function update()
    {
        // Load the model
        $voucherpaymentmodel = new VoucherPaymentModel();

        // Attempt to update records where Aflag = '1'
        try {
            // Perform the update
            $affectedRows = $voucherpaymentmodel->where('Aflag', '1')
                ->set([
                    'is_confirm' => 1,
                    'confirm_user' => session()->get('username'),
                    'confirm_date' => date('Y-m-d H:i:s') // Correctly using PHP's date() function
                ])
                ->update();

            // Check if any rows were updated
            if ($affectedRows > 0) {
                // If successful, return a success message
                return json_encode(['status' => 200, 'message' => 'Debit note saved successfully']);
            } else {
                // If no rows were updated, return a different message
                return json_encode(['status' => 404, 'message' => 'No records found to update']);
            }
        } catch (\Exception $e) {
            // Catch any exceptions and return an error message
            return json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function update_tour()
    {
        // Load the model
        $tour_id = strip_tags($this->request->getVar('tour_id'));
        $voucherpaymentmodel = new VoucherPaymentModel();

        // Attempt to update records where Aflag = '1'
        try {
            if ($tour_id == 'other') {
                // Perform the update
                $affectedRows = $voucherpaymentmodel->where('Aflag', '1')
                    ->where('is_expenses!=', 1)
                    ->set([
                        'is_confirm' => 1,
                        'confirm_user' => session()->get('username'),
                        'confirm_date' => date('Y-m-d H:i:s') // Correctly set the date and time
                    ])
                    ->update();
            } else {
                // Perform the update
                $affectedRows = $voucherpaymentmodel->where('Aflag', '1')
                    ->where('ref_po', $tour_id)
                    ->set([
                        'is_confirm' => 1,
                        'confirm_user' => session()->get('username'),
                        'confirm_date' => date('Y-m-d H:i:s') // Correctly set the date and time
                    ])
                    ->update();
            }

            // Check if any rows were updated
            if ($affectedRows > 0) {
                // If successful, return a success message
                return json_encode(['status' => 200, 'message' => 'Debit note saved successfully']);
            } else {
                // If no rows were updated, return a different message
                return json_encode(['status' => 404, 'message' => 'No records found to update']);
            }
        } catch (\Exception $e) {
            // Catch any exceptions and return an error message
            return json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
