<?php

namespace Modules\ThemeBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ThemeBuilderMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (app('module.hooks')->requiresEnvatoValidation('ThemeBuilder')) {
            app('module.manager')->deactivate('ThemeBuilder');

            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
