<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\AccountRunningModel;
use App\Models\TrackingLogModel;
use App\Models\VehiclePricesModel;

class UtilityDataController extends BaseController
{
	// protected $API;

	public function __construct()
	{
		// $this->API = new ApiRequestHelper();
		date_default_timezone_set('Asia/Colombo');
	}

	// route : base_url/data/sync_notifications
	public function sync_auction()
	{
		// $model = new VehiclePricesModel();
		// $auction = $model->where('vehi_reg_flag !=', 1)->findAll();

		// return json_encode($auction);
	}

	public function sync_trans()
	{
		// $model = new VehiclePricesModel();
		// $transport = $model->where('transport_flag !=', 1)->findAll();

		// return json_encode($transport);
	}

	public function sync_price()
	{
		// $model = new VehiclePricesModel();
		// $priced = $model->where('price_flag !=', 1)->findAll();

		// return json_encode($priced);
	}

	public function sync_export()
	{
		// $model = new VehiclePricesModel();
		// $export = $model->where('transport_flag', 1)->where('vehi_reg_flag', 1)->where('price_flag', 1)->findAll();

		// return json_encode($export);
	}

	public function sync_documents()
	{
		// $model = new VehiclePricesModel();
		// $document = $model->where('document_flag !=', 1)->findAll();

		// return json_encode($document);
	}

	// route : base_url/data/dashboard_firstrow_counts
	public function dashboard_firstrow_counts()
	{
		// try {
		// 	$data = array();

		// 	// get user's uploads count
		// 	$document_upload_model = new VehiclePricesModel();
		// 	$Buy_tax = $document_upload_model->select('SUM(Buying_price_tax) AS Buy_tax')->findAll();

		// 	// $document_upload_model = new DashboardModel();
		// 	$Trans_tax = $document_upload_model->select('SUM(Transport_tax) AS Trans_tax')->findAll();

		// 	// $document_upload_model = new DashboardModel();
		// 	$Auc_tax = $document_upload_model->select('SUM(Auction_fee_tax) AS Auc_tax')->findAll();

		// 	// $document_upload_model = new DashboardModel();
		// 	$Repair_tax = $document_upload_model->select('SUM(Repairs_tax) AS Repair_tax')->findAll();

		// 	$Recyc_tax = $document_upload_model->select('SUM(Recycle) AS Recyc_tax')->findAll();

		// 	$Road_tax = $document_upload_model->select('SUM(Road_tax) AS Road_tax')->findAll();

		// 	$chartview = new VehiclePricesModel();
		// 	$fullvehical = $chartview->select('count(ID) as fullvehical')->findAll();

		// 	$preg_count = $chartview->select('count(ID) as preg_count')->where('vehi_reg_flag', 0)->findAll();

		// 	$ptrans_count = $chartview->select('count(ID) as ptrans_count')->where('transport_flag', 0)->findAll();

		// 	$data['Buy_tax'] = $Buy_tax;
		// 	$data['Trans_tax'] = $Trans_tax;
		// 	$data['Auc_tax'] = $Auc_tax;
		// 	$data['Repair_tax'] = $Repair_tax;
		// 	$data['Recyc_tax'] = $Recyc_tax;
		// 	$data['Road_tax'] = $Road_tax;
		// 	$data['fullvehical'] = $fullvehical;
		// 	$data['preg_count'] = $preg_count;
		// 	$data['ptrans_count'] = $ptrans_count;

		// 	return json_encode(['status' => 'success', 'data' => $data]);
		// } catch (\Exception $e) {
		// 	return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
		// }
	}

	public function WriteVehicleLog($chassis_no, $action, $comment, $time = NULL, $auction = NULL)
	{
		// $trackingLogModel = new TrackingLogModel();
		// return $trackingLogModel->insert([
		// 	'chassis_no' => $chassis_no,
		// 	'action' => $action,
		// 	'description' => $comment,
		// 	'auction' => $auction,
		// 	'log_time' => isset($time) ? $time : date('Y-m-d H:i:s'),
		// 	'user' => session()->get('emp_id')
		// ]);
		/**
		 @ Actions
		 * 1 - Auction/Pricing Added
		 * 2 - Vehicle Registered
		 * 3 - Transport Added
		 * 4 - Document Uploaded
		 * 5 - Vehicle Exported
		 * 6 - Prices Updated
		 * 7 - Registration Data Updated
		 */
	}

	public function manualRun1()
	{
		// // get all records from vehicle prices
		// $model = new VehiclePricesModel();
		// $vehicles = $model->findAll();

		// // loop through each record, and and write log
		// $x = 0;
		// foreach ($vehicles as $vehicle) {
		// 	$this->WriteVehicleLog($vehicle['Chassis_no'], 1, 'Price Added', $vehicle['add_date']);
		// 	$x++;
		// }

		// echo $x . ' records added';
	}

	public function db_backup_view()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		// read backup log
		$log_path = WRITEPATH . 'backups/backup.log';
		if (file_exists($log_path)) {
			// get logs with line breaks from bottom to top
			$data['backup_log'] = implode('<br>', array_reverse(explode(PHP_EOL, file_get_contents($log_path))));
		} else {
			$data['backup_log'] = 'No backup log found';
		}

		return view('pages/settings/backup_database', $data);
	}

	// route : base_url/data/backup_database
	public function backup_database()
	{
		try {
			date_default_timezone_set('Asia/Colombo');

			$db = \Config\Database::connect();
			$db->initialize();

			$db_name = $db->getDatabase();

			$backup_file = WRITEPATH . 'backups/' . $db_name . '-' . date('Y-m-d-H-i-s') . '.sql';

			if (!is_dir(WRITEPATH . 'backups')) mkdir(WRITEPATH . 'backups', 0777, true);

			// Auto-detect mysqldump — Windows (WAMP) or Linux (Bitnami/system)
			$mysqldump_path = '';
			if (PHP_OS_FAMILY === 'Windows') {
				$candidates = glob('F:\\wamp64\\bin\\mysql\\mysql*\\bin\\mysqldump.exe');
				if (!empty($candidates)) $mysqldump_path = $candidates[0];
			} else {
				foreach ([
					'/opt/bitnami/mysql/bin/mysqldump',
					'/opt/bitnami/mariadb/bin/mysqldump',
					'/usr/bin/mysqldump',
					'/usr/local/bin/mysqldump',
				] as $p) {
					if (is_file($p)) { $mysqldump_path = $p; break; }
				}
			}
			if (empty($mysqldump_path) || !is_file($mysqldump_path)) {
				log_message('error', 'mysqldump not found. Check WAMP path (Windows) or run `which mysqldump` (Linux).');
				echo 'Database backup failed. Please contact the system administrator.';
				return;
			}

			// Build command — credentials stay in memory only, never logged or echoed
			$command = "\"{$mysqldump_path}\" -h {$db->hostname} -u {$db->username} -p{$db->password} {$db_name} > \"{$backup_file}\"";

			$return_var = NULL;
			$output     = NULL;
			exec($command, $output, $return_var);

			// Wipe credentials from memory immediately after exec
			unset($command);

			if ($return_var === 0) {
				// Delete backups older than 7 days
				$files = glob(WRITEPATH . 'backups/*');
				$now   = time();
				foreach ($files as $file) {
					if (is_file($file) && ($now - filemtime($file) >= 60 * 60 * 24 * 7)) {
						if (pathinfo($file, PATHINFO_EXTENSION) !== 'log') {
							unlink($file);
						}
					}
				}
				$this->backup_log(date('Y-m-d H:i:s') . ' - Database backup created successfully.' . PHP_EOL);
			} else {
				// Log only the DB name — never the command or credentials
				log_message('error', 'Database backup failed for database: ' . $db_name);
				$this->backup_log(date('Y-m-d H:i:s') . ' - Database backup failed.' . PHP_EOL);
				$backup_file = NULL;
			}

			// do NOT close the shared DB connection - the DB session handler needs it at shutdown

			if (!empty($backup_file)) {
				@ob_end_clean();
				ignore_user_abort(true);
				set_time_limit(0);
				ini_set('memory_limit', '-1');

				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="' . basename($backup_file) . '"');
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($backup_file));
				flush();

				$file = fopen($backup_file, 'rb');
				while (!feof($file)) {
					print fread($file, 8192);
					flush();
				}
				fclose($file);
				exit;
			} else {
				echo 'Database backup failed. Please contact the system administrator.';
			}
		} catch (\Throwable $e) {
			log_message('error', 'backup_database exception: ' . $e->getMessage());
			echo 'Database backup failed. Please contact the system administrator.';
		}
	}

	private function backup_log($message)
	{
		$log_path = WRITEPATH . 'backups/backup.log';
		if (!file_exists($log_path)) file_put_contents($log_path, '');
		file_put_contents($log_path, $message, FILE_APPEND);
	}
}
