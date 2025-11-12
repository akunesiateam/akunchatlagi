<?php

namespace Modules\MaskingNumber\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MaskingNumber\Services\MaskingNumberService;

class MaskingNumberController extends Controller
{
    
    protected $maskingNumberService;

    public function __construct(MaskingNumberService $maskingNumberService)
    {
        $this->maskingNumberService = $maskingNumberService;
    }
    
    /**
     * Display tenant cache management dashboard
     */
    public function index()
    {
        $tenantId = tenant_id();
        $available = $this->maskingNumber->getTenantMaskingNumber($tenantId);

        return view('MaskingNumber::tenant.index', compact('maskingNumber'));
    }
    
    /**
     * Get embedded signup configuration for the tenant
     */
    public function config()
    {
        return [
            'name' => 'MaskingNumber',
            
            'settings' => [
                'enabled' => false,
            ]
        ];
        
    }
    
    /**
     * Check if embedded signup is available for the current tenant
     */
    public function availability()
    {
        try {
            $available = $this->maskingNumberService->isMaskingNumberAvailable();

            return response()->json([
                'available' => $available,
                'message' => $available ? 'Maskin Number is available' : 'Masking Number is not available',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'available' => false,
                'message' => 'Error checking availability',
            ], 500);
        }
    }
    
}
