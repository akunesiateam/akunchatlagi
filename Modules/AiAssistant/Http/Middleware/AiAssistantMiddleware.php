<?php

namespace Modules\AiAssistant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AiAssistantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (app('module.hooks')->requiresEnvatoValidation('AiAssistant')) {
            $moduleManager = app('module.manager');
            $moduleManager->deactivate('AiAssistant');

            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        return $next($request);
    }
}
