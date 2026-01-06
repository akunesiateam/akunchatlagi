<?php

namespace Modules\EmbeddedSignup\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EmbeddedSignupMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (app('module.hooks')->requiresEnvatoValidation('EmbeddedSignup')) {
            app('module.manager')->deactivate('EmbeddedSignup');

            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        return $next($request);
    }
}
