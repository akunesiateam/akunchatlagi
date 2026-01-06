<?php

namespace Modules\SignAdmin\Http\Controllers;

use App\Http\Controllers\Controller;

class SignAdminController extends Controller
{
    public function index()
    {
        return view('SignAdmin::index');
    }
}