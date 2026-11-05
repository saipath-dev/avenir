<?php

namespace App\Controllers\Driver;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;


class DriverLoginController extends BaseController
{
	public function __construct()
	{
		date_default_timezone_set('Asia/Colombo');
	}


	public function Mileage()
	{
		$data = array();

		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		$user_id = session()->get('user_id');

		$data['user_id'] = $user_id;

		return view('pages/settings/user_account', $data);
	}


}
