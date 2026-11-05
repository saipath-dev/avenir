<?php

namespace App\Controllers;

use App\Libraries\MenuLoader;


class NavigationController extends BaseController
{

    public function index()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        return view('pages/navigation', $data);
    }
}
