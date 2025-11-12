<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EcommerceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (app('module.hooks')->requiresEnvatoValidation('Ecommerce')) {
            app('module.manager')->deactivate('Ecommerce');

            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        return $next($request);
    }
}
