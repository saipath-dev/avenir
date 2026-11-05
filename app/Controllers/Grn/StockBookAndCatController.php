<?php

namespace App\Controllers\Grn;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\Grn\StockBookModel;
use App\Models\Grn\StockCardModel;
use App\Models\Grn\StockCatModel;

class StockBookAndCatController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    /**
     * Stock Book/Cat Model = [Id, Name, Code]
     */
    // route : base_url/inventory/main_category
    public function index_main()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $data['stockBooks'] = json_decode($this->getStockBooks(), true);

        return view('pages/grn/main_category', $data);
    }

    public function create_main_category()
    {
        $Name = $this->request->getPost('Name');

        $id = $this->createStockBook($Name);
        if ($id == 0) return json_encode(array('status' => 409, 'message' => 'A category with the same name already exists!'));
        return json_encode(array('status' => 201, 'message' => 'Main Category created successfully!', 'id' => $id));
    }

    // route : base_url/inventory/sub_category
    public function index_sub()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $data['stockCats'] = json_decode($this->getStockCats(), true);

        return view('pages/grn/sub_category', $data);
    }

    public function create_sub_category()
    {
        $Name = $this->request->getPost('Name');

        $id = $this->createStockCat($Name);
        if ($id == 0) return json_encode(array('status' => 409, 'message' => 'A category with the same name already exists!'));
        return json_encode(array('status' => 201, 'message' => 'Sub Category created successfully!', 'id' => $id));
    }

    // create new stock book of not exists in same name and code
    public function createStockBook($Name)
    {
        $stockBookModel = new StockBookModel();
        $stockBook = $this->isStockBookExist($Name);
        if (!empty($stockBook)) return 0; // conflict
        $id = $stockBookModel->insert([
            'Name' => $Name
        ], true);

        return $id;
    }

    // check if stock book exists
    public function isStockBookExist($Name)
    {
        $stockBookModel = new StockBookModel();
        return $stockBookModel->where('Name', $Name)->first();
    }

    // get all stock books
    public function getStockBooks()
    {
        $stockBookModel = new StockBookModel();
        $stockBooks = $stockBookModel->findAll();
        return json_encode($stockBooks);
    }

    // create new stock cat of not exists in same name and code
    public function createStockCat($Name)
    {
        $stockCatModel = new StockCatModel();
        $stockCat = $this->isStockCatExist($Name);
        if (!empty($stockCat)) return 0; // conflict
        $id = $stockCatModel->insert([
            'Name' => $Name
        ], true);

        return $id;
    }

    // check if stock cat exists
    public function isStockCatExist($Name)
    {
        $stockCatModel = new StockCatModel();
        return $stockCatModel->where('Name', $Name)->first();
    }

    // get all stock cats
    public function getStockCats()
    {
        $stockCatModel = new StockCatModel();
        $stockCats = $stockCatModel->findAll();
        return json_encode($stockCats);
    }

    // route : base_url/inventory/main_category/get/:id
    public function get_main($id)
    {
        $stockBookModel = new StockBookModel();
        $stockBook = $stockBookModel->find($id);
        return json_encode($stockBook);
    }

    public function get_sub($id){
        $stockCatModel = new StockCatModel();
        $stockCat = $stockCatModel->find($id);
        return json_encode($stockCat);
    }

    public function update_main($id){
        $Name = $this->request->getPost('Name');

        $stockBookModel = new StockBookModel();
        $stockBook = $stockBookModel->find($id);
        if($stockBook['Name'] == $Name) return json_encode(array('status' => 200, 'message' => 'No changes made!'));

        $stockBook = $this->isStockBookExist($Name);
        if (!empty($stockBook)) return json_encode(array('status' => 409, 'message' => 'A category with the same name already exists!'));

        $stockBookModel->update($id, ['Name' => $Name]);
        return json_encode(array('status' => 200, 'message' => 'Main Category updated successfully!'));
    }

    public function update_sub($id){
        $Name = $this->request->getPost('Name');

        $stockCatModel = new StockCatModel();
        $stockCat = $stockCatModel->find($id);
        if($stockCat['Name'] == $Name) return json_encode(array('status' => 200, 'message' => 'No changes made!'));

        $stockCat = $this->isStockCatExist($Name);
        if (!empty($stockCat)) return json_encode(array('status' => 409, 'message' => 'A category with the same name already exists!'));

        $stockCatModel->update($id, ['Name' => $Name]);
        return json_encode(array('status' => 200, 'message' => 'Sub Category updated successfully!'));
    }

    public function delete_main($id){
        // delete cat if not items in it
        $stockCardModel = new StockCardModel();
        $stockCard = $stockCardModel->where('Stock_Book', $id)->first();

        if($stockCard) return json_encode(array('status' => 409, 'message' => 'Cannot delete main category with items in it!'));

        $stockBookModel = new StockBookModel();
        $stockBookModel->delete($id);

        return json_encode(array('status' => 200, 'message' => 'Main Category deleted successfully!'));
    }

    public function delete_sub($id){
        // delete cat if not items in it
        $stockCardModel = new StockCardModel();
        $stockCard = $stockCardModel->where('Stock_Cat', $id)->first();

        if($stockCard) return json_encode(array('status' => 409, 'message' => 'Cannot delete sub category with items in it!'));

        $stockCatModel = new StockCatModel();
        $stockCatModel->delete($id);

        return json_encode(array('status' => 200, 'message' => 'Sub Category deleted successfully!'));
    }
}
