<?php

namespace Modules\FBLead\Http\Controllers;

use App\Http\Controllers\Controller;

class FBLeadController extends Controller
{
    /**
     * Display the module welcome screen
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('FBLead::index');
    }
}
