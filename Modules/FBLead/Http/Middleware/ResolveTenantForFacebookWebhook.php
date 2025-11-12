<?php

namespace Modules\FBLead\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantForFacebookWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantSubdomain = $request->route('tenant');

        // Also check query parameter as fallback
        if (empty($tenantSubdomain)) {
            $tenantSubdomain = $request->query('subdomain');
        }

        if (empty($tenantSubdomain)) {
            Log::warning('Facebook webhook: No tenant subdomain provided');

            return response('Bad Request: Missing tenant', 400);
        }

        // Find tenant by subdomain
        $tenant = Tenant::where('subdomain', $tenantSubdomain)->first();

        if (! $tenant) {
            Log::warning('Facebook webhook: Tenant not found', ['subdomain' => $tenantSubdomain]);

            return response('Bad Request: Invalid tenant', 400);
        }

        // Set tenant context
        $tenant->makeCurrent();

        // Add tenant info to request for controller access
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_id', $tenant->id);
        $request->attributes->set('tenant_subdomain', $tenantSubdomain);

        return $next($request);
    }
}
