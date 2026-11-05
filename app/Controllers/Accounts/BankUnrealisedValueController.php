<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\AccountRunning2024Model;

class BankUnrealisedValueController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // Load the index view with bank selection
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $accountposting = new AccountPostingModel();
        $data['load_bank'] =  $accountposting->select('PName,L_Code')
            ->where('B_Code', '1')
            ->where('T_Code', '6')
            ->findAll();

        return view('pages/accounts/bank_unrealised_value_change', $data);
    }

    /******************** Bank Unrealised Value Change ***********************/
    public function process_unrealised_value_change()
    {
        // tf-legacy-retire-r1 (12 Oct 2026): retired. Refused before anything else runs: the security token first (403), then
        // the retirement (409); every attempt is audited. The original code below is kept for the record and is never reached.
        [$code, $message] = \App\Libraries\BankRec\LegacyGuard::refuseRetiredTool('bank_unrealised_value', ['bank' => mb_substr(strip_tags((string) $this->request->getPost('bank')), 0, 40)]);
        return $this->response->setStatusCode($code)->setJSON(['status' => $code, 'message' => $message, 'retired' => true, 'csrf' => csrf_hash()]);

        $bank = strip_tags($this->request->getVar('bank'));
        $targetDate = '2025-03-31';

        if (empty($bank)) {
            return $this->response->setJSON(["status" => 400, "message" => "Bank account is required!"]);
        }

        $accRunningModel = new AccountRunningModel();
        $accRunning2024Model = new AccountRunning2024Model();
        try {
            // Get all IDs where L_Code = bank and bank_rec = 0
            $unreconciledRecords = $accRunning2024Model
                ->select('ID')
                ->where('L_Code', $bank)
                ->where('bank_rec', 0)
                ->findAll();

            if (empty($unreconciledRecords)) {
                return $this->response->setJSON(["status" => 404, "message" => "No unreconciled records found for this bank!"]);
            }

            // Extract only IDs
            $ids = array_column($unreconciledRecords, 'ID');

            if (empty($ids)) {
                return $this->response->setJSON(["status" => 404, "message" => "No IDs found!"]);
            }

            $allRecords = [];
            $successCount = 0;
            $errors = [];

            // Loop through each ID and get all records with that ID (including different L_Codes)
            foreach ($ids as $id) {
                $records = $accRunning2024Model->where('ID', $id)->findAll();

                if (!empty($records) && count($records) == 2) {
                    // Get the bank record and the other record
                    $bankRecord = null;
                    $supplierRecord = null;

                    foreach ($records as $record) {
                        if ($record['L_Code'] == $bank) {
                            $bankRecord = $record;
                        } else {
                            $supplierRecord = $record;
                        }
                    }

                    if ($bankRecord && $supplierRecord) {
                        // Insert all records from $records into another database
                        $db2 = \Config\Database::connect('default'); // or specify another database connection

                        foreach ($records as $rec) {
                            // Insert to another database table
                            $db2->table('account_runing')->insert($rec); // Change table name as needed
                        }

                        // Process bank record - subtract amounts at target date
                        $bankLCode = $bankRecord['L_Code'];
                        $bankDr = $bankRecord['Dr'];
                        $bankCr = $bankRecord['Cr'];

                        // Get existing record at target date with bank L_Code
                        $existingBankRecord = $accRunningModel
                            ->where('Date', $targetDate)
                            ->where('L_Code', $bankLCode)
                            ->like('Discription', 'Brought Forward Balance 2025-03-31', 'after')
                            ->first();

                            log_message('critical', "Processing ID $id: Bank L_Code $bankLCode, Dr $bankDr, Cr $bankCr. Existing record at target date: " . json_encode($existingBankRecord));

                        if ($existingBankRecord) {
                            // Subtract amounts from existing record
                            $newDr = $existingBankRecord['Dr'] - $bankDr;
                            $newCr = $existingBankRecord['Cr'] - $bankCr;
                            // $accRunningModel->where('Date', $targetDate)
                            //     ->where('L_Code', $existingBankRecord['L_Code'])
                            //     ->like('Discription', 'Brought Forward Balance 2025-03-31', 'after')
                            //     ->update([
                            //         'Dr' => $newDr,
                            //         'Cr' => $newCr
                            //     ]);

                            $accRunningModel->set(['Dr' => $newDr, 'Cr' => $newCr])
                                ->where('Date', $targetDate)
                                ->where('L_Code', $existingBankRecord['L_Code'])
                                ->like('Discription', 'Brought Forward Balance 2025-03-31', 'after')
                                ->update();
                                
                            log_message('critical', " bank update query: " . $accRunningModel->getLastQuery());
                        } 

                        // Process supplier record - subtract amounts at target date
                        $supLCode = $supplierRecord['L_Code'];
                        $supDr = $supplierRecord['Dr'];
                        $supCr = $supplierRecord['Cr'];

                        // Get existing record at target date with supplier L_Code
                        $existingSupRecord = $accRunningModel
                            ->where('Date', $targetDate)
                            ->where('L_Code', $supLCode)
                            ->like('Discription', 'Brought Forward Balance 2025-03-31', 'after')
                            ->first();

                        log_message('critical', "Processing ID $id: Supplier L_Code $supLCode, Dr $supDr, Cr $supCr. Existing record at target date: " . json_encode($existingSupRecord));

                        if ($existingSupRecord) {
                            // Subtract amounts from existing record
                            $newDr = $existingSupRecord['Dr'] - $supDr;
                            $newCr = $existingSupRecord['Cr'] - $supCr;
                            // $accRunningModel->where('Date', $targetDate)
                            //     ->where('L_Code', $existingSupRecord['L_Code'])
                            //     ->like('Discription', 'Brought Forward Balance 2025-03-31', 'after')
                            //     ->update([
                            //         'Dr' => $newDr,
                            //         'Cr' => $newCr
                            //     ]);
                            $accRunningModel->set(['Dr' => $newDr, 'Cr' => $newCr])
                                ->where('Date', $targetDate)
                                ->where('L_Code', $existingSupRecord['L_Code'])
                                ->like('Discription', 'Brought Forward Balance 2025-03-31', 'after')
                                ->update();

                            log_message('critical', " supplier update query: " . $accRunningModel->getLastQuery());
                        } 

                        $allRecords[] = [
                            'ID' => $id,
                            'bank_L_Code' => $bankLCode,
                            'bank_Dr' => $bankDr,
                            'bank_Cr' => $bankCr,
                            'sup_L_Code' => $supLCode,
                            'sup_Dr' => $supDr,
                            'sup_Cr' => $supCr,
                            'processed' => true
                        ];
                        $successCount++;
                    }
                } else {
                    $errors[] = ['id' => $id, 'error' => 'Expected 2 records, found ' . count($records)];
                }
            }

            return $this->response->setJSON([
                "status" => 200,
                "message" => "Found " . count($allRecords) . " total records from " . count($ids) . " unique IDs!",
                "records" => $allRecords,
                "total_records" => count($allRecords),
                "unique_ids" => count($ids),
                "errors" => $errors
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => "An error occurred: " . $e->getMessage()]);
        }
    }
}
