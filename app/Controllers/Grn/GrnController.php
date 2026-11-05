<?php

namespace App\Controllers\Grn;

use App\Controllers\BaseController;
use App\Controllers\Vision\Document_AI;
use App\Libraries\MenuLoader;
use App\Models\Grn\StockCardModel;
use App\Models\SupplierModel;

class GrnController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/document
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        return view('pages/grn/new_grn', $data);
    }

    // validate supplier - route : base_url/grn/new/validate_supplier
    public function validate_supplier()
    {
        $supplier_name = $this->request->getGet('supplier_name');

        $supplierModel = new SupplierModel();
        // check if supplier exists with the given name and sup_cat = 4
        $supplier = $supplierModel->where('Name', $supplier_name)->where('sup_cat', 4)->first();
        if (!empty($supplier)) {
            return json_encode(array('status' => 200, 'message' => 'Existing Supplier!', 'supplier' => $supplier));
        }
        return json_encode(array('status' => 404, 'message' => 'Supplier not found!'));
    }

    // scan_document - route : base_url/grn/new/scan_document
    public function scan_document()
    {
        // Get file from post
        $file = $this->request->getFile('doc');

        // Get the MIME type of the file
        $mimeType = $file->getMimeType();
        $extension = $file->getExtension();

        // Define the allowed extensions
        $allowedExtensions = ['pdf', 'gif', 'tiff', 'jpg', 'jpeg', 'png', 'bmp', 'webp'];

        // Check if the file's extension is in the list of allowed extensions
        if (!in_array($extension, $allowedExtensions)) {
            // If it's not, return an error message
            return json_encode(array('status' => 400, 'message' => 'Invalid file type. Only pdf, gif, tiff, jpg, jpeg, png, bmp, webp files are allowed.'));
        }

        $file_name = $file->getRandomName();

        // Save the file to a directory
        $file_path = WRITEPATH . 'uploads/' . $file_name;
        $file->move(WRITEPATH . 'uploads', $file_name);

        // Instantiate the Document_AI class
        // $documentAI = new Document_AI();

        $GrnUtilityController = new GrnUtilityController();
        $result_HardCoded = $GrnUtilityController->hardcoded_data();
        return json_encode($result_HardCoded);

        // Call the processDocument function with the path of the saved file
        // $result = $documentAI->processDocument($file_path, $mimeType);
        // return json_encode($result);
    }


    public function check_product_code()
    {
        $productCode = $this->request->getPost('product_code');

        if (!$productCode) {
            return $this->response->setJSON([
                'status' => 400,
                'message' => 'Product code is required'
            ]);
        }

        $stockCardModel = new StockCardModel();

        $product = $stockCardModel->where('Stock_Card', $productCode)->first();

        if ($product) {
            return $this->response->setJSON([
                'status' => 200,
                'exists' => true,
                'message' => 'Product code already exists in the database'
            ]);
        }

        return $this->response->setJSON([
            'status' => 200,
            'exists' => false,
            'message' => 'Product code is available'
        ]);
    }

    // submit_table_data - route : base_url/grn/new/submit_table_data
    public function submit_table_data()
    {
        // Get JSON data from request
        $jsonData = $this->request->getJSON(true);

        // Check if tableData exists in the JSON
        if (!isset($jsonData['tableData']) || !is_array($jsonData['tableData'])) {
            return $this->response->setJSON([
                'status' => 400,
                'message' => 'Invalid data format!'
            ]);
        }

        // Initialize StockCardModel
        $stockCardModel = new StockCardModel();

        // Loop through the table data and insert each row into the database
        foreach ($jsonData['tableData'] as $rowData) {
            // Prepare data for insertion
            $data = [
                'Stock_Card' => $rowData['product_code'],
                'Descrip' => $rowData['description'],
                'units' => $rowData['quantity'],
                'foc' => $rowData['foc_quantity'] ?? 0,
                'wPrice' => $rowData['unit_price'],
                'vat' => $rowData['vat'],
                'dis' => $rowData['discount'],
                'tp' => $rowData['amount'],
                // Additional fields can be added here as per your table structure
            ];

            // Insert data into the database
            $stockCardModel->insert($data);
        }

        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Data submitted successfully!'
        ]);
    }
}
