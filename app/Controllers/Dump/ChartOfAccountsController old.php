<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\AccountNumbersModel;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\BasementAccountsModel;
use App\Models\HeadingAccountsModel;
use App\Models\TitleAccountsModel;

class ChartOfAccountsController extends BaseController
{
	public function __construct()
	{
		date_default_timezone_set('Asia/Colombo');
	}

	// route : base_url/accounts
	public function index()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		// Load sidebar accounts data
		$data = array_merge($data, json_decode($this->getChartOfAccounts(), true));

		// if BCode is set && path is set && codes is set
		if ($this->request->getGet('BCode') != null && $this->request->getGet('path') != null && $this->request->getGet('codes') != null) {
			// get BCode from url
			$BCode = $this->request->getGet('BCode');
			$TitleAccountsModel = new TitleAccountsModel();
			$data['page_data'] = $TitleAccountsModel->where('B_Code', $BCode)->findAll();

			$data['level'] = "base_account";
			$data['path'] = $this->request->getGet('path');
			$data['codes'] = $this->request->getGet('codes');
		} else {
			$data['level'] = "";
		}

		return view('pages/accounts/manage_accounts', $data);
	}

	// route : base_url/accounts/title_accounts
	public function title_accounts()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		// Load sidebar accounts data
		$data = array_merge($data, json_decode($this->getChartOfAccounts(), true));

		// if BCode is set && TCode is set && path is set && codes is set
		if ($this->request->getGet('BCode') != null && $this->request->getGet('TCode') != null && $this->request->getGet('path') != null && $this->request->getGet('codes') != null) {
			// get BCode from url
			$BCode = $this->request->getGet('BCode');
			$TCode = $this->request->getGet('TCode');
			$HeadingAccountsModel = new HeadingAccountsModel();
			$data['page_data'] = $HeadingAccountsModel->where('B_Code', $BCode)->where('T_Code', $TCode)->findAll();

			$data['level'] = "title_account";
			$data['path'] = $this->request->getGet('path');
			$data['codes'] = $this->request->getGet('codes');
		} else {
			$data['level'] = "";
		}

		return view('pages/accounts/manage_accounts', $data);
	}

	// route : base_url/accounts/heading_accounts
	public function heading_accounts()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		// Load sidebar accounts data
		$data = array_merge($data, json_decode($this->getChartOfAccounts(), true));

		// if BCode is set && TCode is set && HCode is set && path is set && codes is set
		if ($this->request->getGet('BCode') != null && $this->request->getGet('TCode') != null && $this->request->getGet('HCode') != null && $this->request->getGet('path') != null && $this->request->getGet('codes') != null) {
			// get BCode from url
			$BCode = $this->request->getGet('BCode');
			$TCode = $this->request->getGet('TCode');
			$HCode = $this->request->getGet('HCode');
			$PostingAccountModel = new AccountPostingModel();
			$data['page_data'] = $PostingAccountModel->where('B_Code', $BCode)->where('T_Code', $TCode)->where('H_Code', $HCode)->findAll();

			$data['level'] = "heading_account";
			$data['path'] = $this->request->getGet('path');
			$data['codes'] = $this->request->getGet('codes');
		} else {
			$data['level'] = "";
		}

		return view('pages/accounts/manage_accounts', $data);
	}

	// route : base_url/accounts/get_accounts_list
	public function getChartOfAccounts()
	{
		$data = array();
		$BasementAccountsModel = new BasementAccountsModel();
		$data['base_accounts'] = $BasementAccountsModel->findAll();
		$TitleAccountsModel = new TitleAccountsModel();
		$data['title_accounts'] = $TitleAccountsModel->findAll();
		$HeadingAccountsModel = new HeadingAccountsModel();
		$data['heading_accounts'] = $HeadingAccountsModel->findAll();
		$PostingAccountModel = new AccountPostingModel();
		$data['posting_accounts'] = $PostingAccountModel->findAll();
		return json_encode($data);
	}

	// route : base_url/accounts/create/title_account
	public function createTitleAccount()
	{
		// get post data
		$BCode = $this->request->getPost('B_Code');
		$TName = $this->request->getPost('TName');

		// validation
		// TName is required and not contain //
		$validation = \Config\Services::validation();
		$validation->setRules([
			'B_Code' => 'required',
			'TName' => 'required|regex_match[/^[^\/]*$/]'
		]);

		// if validation fails
		if ($validation->withRequest($this->request)->run() == false) {
			return json_encode(array('status' => 400, 'message' => 'Validation failed'));
		}

		$AccountNumbersModel = new AccountNumbersModel();
		$TitleAccountsModel = new TitleAccountsModel();
		$data = array(
			'B_Code' => $BCode,
			'TName' => $TName,
			'T_Code' => $AccountNumbersModel->incrementTRCode()
		);
		$is_exist = $TitleAccountsModel->where('B_Code', $BCode)->where('TName', $TName)->countAllResults();
		if ($is_exist == 0) {
			$TitleAccountsModel->insert($data);
			return json_encode(array('status' => 201, 'message' => 'Title Account created successfully'));
		}
		return json_encode(array('status' => 409, 'message' => 'Title Account already exists'));
	}

	// route : base_url/accounts/create/heading_account
	public function createHeadingAccount()
	{
		// get post data
		$BCode = $this->request->getPost('B_Code');
		$TCode = $this->request->getPost('T_Code');
		$HName = $this->request->getPost('HName');

		// validation
		$validation = \Config\Services::validation();
		$validation->setRules([
			'B_Code' => 'required',
			'T_Code' => 'required',
			'HName' => 'required|regex_match[/^[^\/]*$/]',
		]);

		// if validation fails
		if ($validation->withRequest($this->request)->run() == false) {
			return json_encode(array('status' => 400, 'message' => 'Validation failed'));
		}

		$AccountNumbersModel = new AccountNumbersModel();
		$HeadingAccountsModel = new HeadingAccountsModel();
		$data = array(
			'B_Code' => $BCode,
			'T_Code' => $TCode,
			'HName' => $HName,
			'H_Code' => $AccountNumbersModel->incrementHRCode()
		);
		$is_exist = $HeadingAccountsModel->where('B_Code', $BCode)->where('T_Code', $TCode)->where('HName', $HName)->countAllResults();
		if ($is_exist == 0) {
			$HeadingAccountsModel->insert($data);
			return json_encode(array('status' => 201, 'message' => 'Heading Account created successfully'));
		}
		return json_encode(array('status' => 409, 'message' => 'Heading Account already exists'));
	}

	// route : base_url/accounts/create/posting_account
	public function createPostingAccount()
	{
		// get post data
		$BCode = $this->request->getPost('B_Code');
		$TCode = $this->request->getPost('T_Code');
		$HCode = $this->request->getPost('H_Code');
		$PName = $this->request->getPost('PName');
		$type = $this->request->getPost('type');

		// validation
		$validation = \Config\Services::validation();
		$validation->setRules([
			'B_Code' => 'required',
			'T_Code' => 'required',
			'H_Code' => 'required',
			'PName' => 'required|regex_match[/^[^\/]*$/]'
		]);

		// if validation fails
		if ($validation->withRequest($this->request)->run() == false) {
			return json_encode(array('status' => 400, 'message' => 'Validation failed'));
		}

		if(empty($type)){
			$type = 0;
		}

		$AccountNumbersModel = new AccountNumbersModel();
		$AccountPostingModel = new AccountPostingModel();
		$PCode = $AccountNumbersModel->incrementPRCode();
		$data = array(
			'B_Code' => $BCode,
			'T_Code' => $TCode,
			'H_Code' => $HCode,
			'PName' => $PName,
			'P_Code' => $PCode,
			'L_Code' => $BCode . '-' . $TCode . '-' . $HCode . '-' . $PCode,
			'type' => $type
		);
		$is_exist = $AccountPostingModel->where('B_Code', $BCode)->where('T_Code', $TCode)->where('H_Code', $HCode)->where('PName', $PName)->countAllResults();
		if ($is_exist == 0) {
			$AccountPostingModel->insert($data);
			return json_encode(array('status' => 201, 'message' => 'Posting Account created successfully'));
		}
		return json_encode(array('status' => 409, 'message' => 'Posting Account already exists'));
	}

	// route : base_url/accounts/update/title_account
	public function updateTitleAccount()
	{
		// get post data
		$Id = $this->request->getPost('Id');
		$TName = $this->request->getPost('TName');

		// validation
		$validation = \Config\Services::validation();
		$validation->setRules([
			'Id' => 'required',
			'TName' => 'required|regex_match[/^[^\/]*$/]'
		]);

		// if validation fails
		if ($validation->withRequest($this->request)->run() == false) {
			return json_encode(array('status' => 400, 'message' => 'Validation failed'));
		}

		$TitleAccountsModel = new TitleAccountsModel();
		$data = array(
			'TName' => $TName
		);
		// check if the title account exists
		$is_exist = $TitleAccountsModel->where('TName', $TName)->countAllResults();
		if ($is_exist == 0) {
			$TitleAccountsModel->update($Id, $data);
			return json_encode(array('status' => 200, 'message' => 'Title Account updated successfully'));
		}
		return json_encode(array('status' => 409, 'message' => 'Title Account already exists'));
	}

	// route : base_url/accounts/update/heading_account
	public function updateHeadingAccount()
	{
		// get post data
		$Id = $this->request->getPost('Id');
		$HName = $this->request->getPost('HName');

		// validation
		$validation = \Config\Services::validation();
		$validation->setRules([
			'Id' => 'required',
			'HName' => 'required|regex_match[/^[^\/]*$/]'
		]);

		// if validation fails
		if ($validation->withRequest($this->request)->run() == false) {
			return json_encode(array('status' => 400, 'message' => 'Validation failed'));
		}

		$HeadingAccountsModel = new HeadingAccountsModel();
		$data = array(
			'HName' => $HName
		);
		// check if the heading account exists
		$is_exist = $HeadingAccountsModel->where('HName', $HName)->countAllResults();
		if ($is_exist == 0) {
			$HeadingAccountsModel->update($Id, $data);
			return json_encode(array('status' => 200, 'message' => 'Heading Account updated successfully'));
		}
		return json_encode(array('status' => 409, 'message' => 'Heading Account already exists'));
	}

	// route : base_url/accounts/update/posting_account
	public function updatePostingAccount()
	{
		// get post data
		$Id = $this->request->getPost('Id');
		$PName = $this->request->getPost('PName');

		// validation
		$validation = \Config\Services::validation();
		$validation->setRules([
			'Id' => 'required',
			'PName' => 'required|regex_match[/^[^\/]*$/]'
		]);

		// if validation fails
		if ($validation->withRequest($this->request)->run() == false) {
			return json_encode(array('status' => 400, 'message' => 'Validation failed'));
		}

		$AccountPostingModel = new AccountPostingModel();
		$data = array(
			'PName' => $PName
		);
		// check if the posting account exists
		$is_exist = $AccountPostingModel->where('PName', $PName)->countAllResults();
		if ($is_exist == 0) {
			$AccountPostingModel->update($Id, $data);
			return json_encode(array('status' => 200, 'message' => 'Posting Account updated successfully'));
		}
		return json_encode(array('status' => 409, 'message' => 'Posting Account already exists'));
	}

	// route : base_url/accounts/delete/title_account
	public function deleteTitleAccount()
	{
		$TCode = $this->request->getPost('TCode');
		$TitleAccountsModel = new TitleAccountsModel();
		// check if there any heading accounts with this T_Code
		$HeadingAccountsModel = new HeadingAccountsModel();
		$heading_accounts = $HeadingAccountsModel->where('T_Code', $TCode)->countAllResults();
		if ($heading_accounts == 0) {
			$TitleAccountsModel->where('T_Code', $TCode)->delete();
			return json_encode(array('status' => 200, 'message' => 'Title Account deleted successfully'));
		}
		return json_encode(array('status' => 409, 'message' => 'Title Account cannot be deleted'));
	}

	// route : base_url/accounts/delete/heading_account
	public function deleteHeadingAccount()
	{
		$HCode = $this->request->getPost('HCode');
		$HeadingAccountsModel = new HeadingAccountsModel();
		// check if there any posting accounts with this H_Code
		$AccountPostingModel = new AccountPostingModel();
		$posting_accounts = $AccountPostingModel->where('H_Code', $HCode)->countAllResults();
		if ($posting_accounts == 0) {
			$HeadingAccountsModel->where('H_Code', $HCode)->delete();
			return json_encode(array('status' => 200, 'message' => 'Heading Account deleted successfully'));
		}
		return json_encode(array('status' => 409, 'message' => 'Heading Account cannot be deleted'));
	}

	// route : base_url/accounts/delete/posting_account
	public function deletePostingAccount()
	{
		$PCode = $this->request->getPost('PCode');
		$AccountPostingModel = new AccountPostingModel();
		// get the posting account
		$account = $AccountPostingModel->where('P_Code', $PCode)->first();
		$LCode = $account['L_Code'];
		// check if account_running table has any records with this L_Code
		$AccountRunningModel = new AccountRunningModel();
		$account_running = $AccountRunningModel->where('L_Code', $LCode)->countAllResults();
		if ($account_running == 0) {
			$AccountPostingModel->where('P_Code', $PCode)->delete();
			return json_encode(array('status' => 200, 'message' => 'Posting Account deleted successfully'));
		}
		return json_encode(array('status' => 409, 'message' => 'Posting Account cannot be deleted'));
	}
}
