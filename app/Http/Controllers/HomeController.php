<?php

namespace App\Http\Controllers;

use App\Models\Theme;

class HomeController extends Controller
{
    public function landingPage()
    {
        $systemSettings = get_setting('system.is_enable_landing_page');
        if (! $systemSettings) {
            if (tenant_check()) {
                return redirect()->to(tenant_route('tenant.dashboard'));
            } else {
                return redirect()->route('admin.dashboard');
            }
        }

        $data = Theme::where('active', 1)->first();

        return view('welcome', compact('data'));
    }
}
