<?php

namespace Modules\FBLead\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FBLeadMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (app('module.hooks')->requiresEnvatoValidation('FBLead')) {
            app('module.manager')->deactivate('FBLead');

            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        return $next($request);
    }
}
