<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Activity;
use App\Libraries\TourFlow\Staff;
use App\Models\AccountNumbersModel;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\CountryModel;
use App\Models\CustomerModel;
use App\Models\TourQuotationsModel;

class CustomerController extends BaseController
{
	public function __construct()
	{
		date_default_timezone_set('Asia/Colombo');
	}

	// route : base_url/master_files/customer
	public function index()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		// Load the supplier data
		$CustomerModel = new CustomerModel();
		$data['load_data'] = $CustomerModel->where('sup_cat', 2)->findAll();

		$countryModel = new CountryModel();
		$data['countries'] = $countryModel->findAll();

		$id = $this->request->getGet('id');
		if (!empty($id)) {
			$data['customer'] = $CustomerModel->where('ID', $id)->first();
		}

		return view('pages/master_files/new_customer', $data);
	}

	// create new customer - route : base_url/master_files/customer/create
	public function create()
	{
		try {
			// validation
			$validation =  \Config\Services::validation();
			$validation->setRules([
				'supplier_name' => 'required|strip_tags',
				'address' => 'required|strip_tags',
				'email' => 'required|strip_tags'
			]);

			if (!$validation->withRequest($this->request)->run()) {
				return $this->response->setJSON(["status" => 400, "message" => "Validation Failed! Please check the input fields.", "errors" => $validation->getErrors()]);
			}

			$accountNumbersModel = new AccountNumbersModel();
			$nextPRCode = $accountNumbersModel->incrementPRCode();

			$data = array(
				
				'Name' => $this->request->getVar('supplier_name'),
				'long_name' => $this->request->getVar('supplier_long_name'),
				'Address' => $this->request->getVar('address'),
				
				'TP' => $this->request->getVar('tel_home'),
				'Mobile' => $this->request->getVar('tel_mobile'),

				'Sup_Acc_No' => '1-2-5-' . $nextPRCode,
				'email' =>  $this->request->getVar('email'),
				'country' =>  $this->request->getVar('country'),
				't_code_1' =>  $this->request->getVar('t_code_1'),
				't_code_2' =>  $this->request->getVar('t_code_2'),
				'margin' => $this->request->getVar('margin')
			);

			$customerModel = new CustomerModel();
			$customer_id = $customerModel->insert($data);
			if ($customer_id) {
				$accountPostingMode = new AccountPostingModel();
				$accountPostingMode->insert([
					'P_Code' => $nextPRCode,
					'PName' => $this->request->getVar('supplier_name'),
					'B_Code' => 1,
					'T_Code' => 2,
					'H_Code' => 5,
					'L_Code' => '1-2-5-' . $nextPRCode
				]);
				return $this->response->setJSON(["status" => 201, "message" => "Customer Added successfully!", "customer_id" => $customer_id]);
			}
			throw new \Exception("Customer creation failed!");
		} catch (\Exception $e) {
			return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
		}
	}

	// all customers - route : base_url/master_files/customer/all
	public function all()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		$customerModel = new CustomerModel();
		$data['customers'] = $customerModel->select('customers.*, country.nicename')->join('country', 'customers.country = country.iso', 'left')->findAll();

		// The agent mark beside every record, and how many quotations / inquiries each
		// customer has been the agent on — read only, a help in deciding who to mark.
		$data['quoted'] = $this->quotedCounts();
		$agents = 0;
		foreach ($data['customers'] as &$c) {
			$c['agent'] = (int) ($c['agent'] ?? 0) === 1 ? 1 : 0;
			$c['quoted'] = $data['quoted'][(string) ($c['Sup_Acc_No'] ?? '')] ?? 0;
			$agents += $c['agent'];
		}
		unset($c);
		$data['agents_marked'] = $agents;

		return view('pages/master_files/all_customers', $data);
	}

	/**
	 * Quotations and inquiries per booking code: the legacy quotation header (consignee.agent)
	 * and the Tour Desk inquiry (tf_intake.agent_acc). Best effort — a missing table is a 0.
	 *
	 * @return array<string, int>
	 */
	private function quotedCounts(): array
	{
		$db  = \Config\Database::connect();
		$out = [];
		foreach ([
			"SELECT agent AS acc, COUNT(*) AS n FROM consignee WHERE agent IS NOT NULL AND agent <> '' GROUP BY agent",
			"SELECT agent_acc AS acc, COUNT(*) AS n FROM tf_intake WHERE agent_acc IS NOT NULL AND agent_acc <> '' AND archived_at IS NULL GROUP BY agent_acc",
		] as $sql) {
			try {
				foreach ($db->query($sql)->getResultArray() as $r) {
					$out[(string) $r['acc']] = ($out[(string) $r['acc']] ?? 0) + (int) $r['n'];
				}
			} catch (\Throwable $e) {
				// no such table on this install: nothing to count
			}
		}
		return $out;
	}

	/** Who is signed in, for the history line. */
	private function actor(): array
	{
		$db   = \Config\Database::connect();
		$user = (string) session()->get('username');
		$id   = (int) session()->get('user_id');
		$id   = $id > 0 ? $id : null;
		return [$db, $user, $id, Staff::displayName($db, $id, $user)];
	}

	/**
	 * Mark or unmark one customer as a booking agent — route : base_url/master_files/customer/agent
	 * POST id (customers.ID), agent (1 = agent, 0 = not). Sits under the Consignee screen's own
	 * privilege, like every other edit on it. The change is written to the Tour Desk history
	 * (tf_activity, entity "customer") with who and when. Nothing else on the record moves.
	 */
	public function agent()
	{
		try {
			$id   = (int) $this->request->getPost('id');
			$flag = $this->request->getPost('agent');
			if ($id <= 0 || !in_array((string) $flag, ['0', '1'], true)) {
				return $this->response->setJSON(["status" => 400, "message" => "Send the customer id and agent = 1 (agent) or 0 (not an agent)."]);
			}
			$flag = (int) $flag;
			[$db, $user, $userId, $name] = $this->actor();
			$row = $db->query('SELECT ID, Sup_Acc_No, Name, agent FROM customers WHERE ID = ?', [$id])->getRowArray();
			if (!$row) {
				return $this->response->setJSON(["status" => 404, "message" => "Customer not found!"]);
			}
			$was = (int) ($row['agent'] ?? 0) === 1 ? 1 : 0;
			if ($was !== $flag) {
				$db->query('UPDATE customers SET agent = ? WHERE ID = ?', [$flag, $id]);
				$this->logMark($db, $user, $userId, $name, $row, $flag);
			}
			$label = trim((string) $row['Name']) . ' (' . $row['Sup_Acc_No'] . ')';
			return $this->response->setJSON([
				"status"  => 200,
				"agent"   => $flag,
				"changed" => $was !== $flag,
				"message" => $flag ? "{$label} is now marked as an agent." : "{$label} is no longer marked as an agent.",
				"agents_marked" => $this->agentsMarked($db),
			]);
		} catch (\Exception $e) {
			return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
		}
	}

	/**
	 * Mark, in one go, every customer that has already been the agent on a quotation or an
	 * inquiry and is not marked yet — route : base_url/master_files/customer/agent_quoted (POST).
	 * A head start, not a rule: it marks only what the quotation records already say, and
	 * every mark can be undone one by one. Answers how many it marked, and which.
	 */
	public function agentQuoted()
	{
		try {
			[$db, $user, $userId, $name] = $this->actor();
			$quoted = $this->quotedCounts();
			$done   = [];
			if ($quoted !== []) {
				$rows = $db->query('SELECT ID, Sup_Acc_No, Name, agent FROM customers WHERE Sup_Acc_No IS NOT NULL AND (agent IS NULL OR agent <> 1)')->getResultArray();
				foreach ($rows as $row) {
					if (!isset($quoted[(string) $row['Sup_Acc_No']])) {
						continue;
					}
					$db->query('UPDATE customers SET agent = 1 WHERE ID = ?', [(int) $row['ID']]);
					$this->logMark($db, $user, $userId, $name, $row, 1, 'on ' . $quoted[(string) $row['Sup_Acc_No']] . ' quotation(s)');
					$done[] = ['id' => (int) $row['ID'], 'acc' => (string) $row['Sup_Acc_No'], 'name' => trim((string) $row['Name'])];
				}
			}
			return $this->response->setJSON([
				"status"  => 200,
				"marked"  => $done,
				"message" => $done ? count($done) . " customer(s) marked as agents from the quotation records." : "Every customer that appears on a quotation is already marked.",
				"agents_marked" => $this->agentsMarked($db),
			]);
		} catch (\Exception $e) {
			return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
		}
	}

	private function agentsMarked($db): int
	{
		try {
			return (int) ($db->query('SELECT COUNT(*) AS n FROM customers WHERE agent = 1')->getRowArray()['n'] ?? 0);
		} catch (\Throwable $e) {
			return 0;
		}
	}

	/** One history line per mark: who, when, which customer, from what to what. Never stops the mark. */
	private function logMark($db, string $user, ?int $userId, string $name, array $row, int $flag, string $why = ''): void
	{
		try {
			$label = trim((string) $row['Name']) . ' (' . $row['Sup_Acc_No'] . ')';
			(new Activity($db, $user, $userId, $name))->record(
				'customer', (int) $row['ID'], null, null, $flag ? 'agent_marked' : 'agent_unmarked',
				($flag ? 'Marked as an agent: ' : 'No longer an agent: ') . $label . ($why !== '' ? ' — ' . $why : ''),
				[['what' => 'Agent', 'from' => ((int) ($row['agent'] ?? 0) === 1 ? 'yes' : 'no'), 'to' => ($flag ? 'yes' : 'no')]]
			);
		} catch (\Throwable $e) {
			// no history table on this install: the mark itself is what matters
		}
	}

	// update - route : base_url/master_files/customer/update/{id}
	public function update($id = null, $acc_no = null)
	{
		try {
			// validation
			$validation =  \Config\Services::validation();
			$validation->setRules([
				'supplier_name' => 'required|strip_tags',
				'address' => 'required|strip_tags',
				'email' => 'required|strip_tags'
			]);

			if (!$validation->withRequest($this->request)->run()) {
				return $this->response->setJSON(["status" => 400, "message" => "Validation Failed! Please check the input fields.", "errors" => $validation->getErrors()]);
			}

			$data = array(

				'Name' => $this->request->getVar('supplier_name'),
				'long_name' => $this->request->getVar('supplier_long_name'),
				'Address' => $this->request->getVar('address'),

				'TP' => $this->request->getVar('tel_home'),
				'Mobile' => $this->request->getVar('tel_mobile'),

				'email' =>  $this->request->getVar('email'),
				'country' =>  $this->request->getVar('country'),
				't_code_1' =>  $this->request->getVar('t_code_1'),
				't_code_2' =>  $this->request->getVar('t_code_2'),
				'margin' => $this->request->getVar('margin')
			);

			$customerModel = new CustomerModel();
			$accountPostingMode = new AccountPostingModel();
			if ($customerModel->update($id, $data)) {
				$accountPostingMode->where('L_Code', $acc_no)->set('PName', $this->request->getVar('supplier_name'))->update();
				// log_message('critical', $accountPostingMode->getLastQuery());
				return $this->response->setJSON(["status" => 200, "message" => "Customer updated successfully!", "customer_id" => $id]);
			}
			throw new \Exception("Customer update failed!");
		} catch (\Exception $e) {
			return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
		}
	}

	// delete - route : base_url/master_files/customer/delete
	public function delete()
	{
		try {
			$accountRunningModel = new AccountRunningModel();
			$accHasRecords = $accountRunningModel->where('L_Code', $this->request->getVar('Acc_No'))->countAllResults();

			$tourquotationModel = new TourQuotationsModel();
			$tourquotatiom = $tourquotationModel->where('L_Code', $this->request->getVar('Acc_No'))->countAllResults();

			if ($accHasRecords > 0 || $tourquotatiom > 0) {
				return $this->response->setJSON(["status" => 400, "message" => "Cannot delete this customer. This customer has transactions!"]);
			}
			$customerModel = new CustomerModel();
			if ($customerModel->where('Sup_Acc_No', $this->request->getVar('Acc_No'))->delete()) {
				$accountPostingModel = new AccountPostingModel();
				$accountPostingModel->where('L_Code', $this->request->getVar('Acc_No'))->delete();
				log_message('critical', $accountPostingModel->getLastQuery());
				return $this->response->setJSON(["status" => 200, "message" => "Customer deleted successfully!"]);
			}
		} catch (\Exception $e) {
			return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
		}
	}

	// get customer details by id - route : base_url/master_files/customer/get
	public function getCustomerDetailsById()
	{
		$customerModel = new CustomerModel();
		$customer = $customerModel->where('ID', $this->request->getGet('id'))->first();
		if (!$customer) {
			return $this->response->setJSON(["status" => 404, "message" => "Customer not found!"]);
		}
		return $this->response->setJSON(["status" => 200, "customer" => $customer]);
	}
}
