<?php

namespace Modules\ThemeBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Http\Request;

class ThemeCustomizerController extends Controller
{
    /**
     * Display the module welcome screen
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Theme $theme) // automatic route model binding
    {
        // Pass the theme to the view
        return view('ThemeBuilder::customize', compact('theme'));
    }

    public function payload(Request $request, Theme $theme)
    {
        if ($request->isMethod('patch') || $request->isMethod('post')) {
            // Expect 'payload' as an array/object in the request
            $data = $request->validate([
                'payload' => 'required',
                'html' => 'required',
                'css' => 'required',
            ]);

            // Save payload (cast to array/json as necessary in the model)
            $theme->payload = $request->payload;
            $theme->theme_html = $request->html;
            $theme->theme_css = $request->css;
            $theme->save();

            return response()->json([
                'success' => true,
                'payload' => $theme->payload,
            ]);
        }

        // GET request: return current payload
        return response()->json([
            'payload' => $theme->payload ?? [],
            'html' => $theme->theme_html ?? '',
            'css' => $theme->theme_css ?? '',
        ]);
    }
}
