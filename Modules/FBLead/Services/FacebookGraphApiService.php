<?php

namespace Modules\FBLead\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookGraphApiService
{
    const API_VERSION = 'v24.0';

    const BASE_URL = 'https://graph.facebook.com';

    protected $appId;

    protected $appSecret;

    public function __construct(?string $appId = null, ?string $appSecret = null)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

    /**
     * Exchange short-lived token for long-lived token
     */
    public function exchangeToken(string $shortLivedToken): ?array
    {
        if (! $this->appId || ! $this->appSecret) {
            throw new \InvalidArgumentException('App ID and App Secret are required');
        }

        try {
            $response = Http::timeout(30)->get(self::BASE_URL.'/'.self::API_VERSION.'/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->appId,
                'client_secret' => $this->appSecret,
                'fb_exchange_token' => $shortLivedToken,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Facebook token exchange failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Facebook token exchange error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Fetch user pages using access token
     */
    public function fetchPages(string $accessToken): ?array
    {
        try {
            $response = Http::timeout(30)->get(self::BASE_URL.'/'.self::API_VERSION.'/me/accounts', [
                'access_token' => $accessToken,
                'fields' => 'id,name,access_token,category,tasks,about,phone,website,location,link',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['data'] ?? [];
            }

            Log::error('Facebook pages fetch failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Facebook pages fetch error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Subscribe app to page for lead generation
     */
    public function subscribePageToApp(string $pageId, string $pageAccessToken): bool
    {
        try {
            $response = Http::timeout(30)->post(self::BASE_URL.'/'.self::API_VERSION.'/'.$pageId.'/subscribed_apps', [
                'access_token' => $pageAccessToken,
                'subscribed_fields' => 'leadgen',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['success'] ?? false;
            }

            Log::error('Facebook page subscription failed', [
                'page_id' => $pageId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Facebook page subscription error', [
                'page_id' => $pageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Unsubscribe app from page
     */
    public function unsubscribePageFromApp(string $pageId, string $pageAccessToken): bool
    {
        try {
            $response = Http::timeout(30)->delete(self::BASE_URL.'/'.self::API_VERSION.'/'.$pageId.'/subscribed_apps', [
                'access_token' => $pageAccessToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['success'] ?? false;
            }

            Log::error('Facebook page unsubscription failed', [
                'page_id' => $pageId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Facebook page unsubscription error', [
                'page_id' => $pageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Validate access token
     */
    public function validateAccessToken(string $accessToken): ?array
    {
        try {
            $response = Http::timeout(30)->get(self::BASE_URL.'/'.self::API_VERSION.'/me', [
                'access_token' => $accessToken,
                'fields' => 'id,name,email',
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Facebook token validation error', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get lead generation forms for a page
     */
    public function getLeadgenForms(string $pageId, string $pageAccessToken): ?array
    {
        try {
            $response = Http::timeout(30)->get(self::BASE_URL.'/'.self::API_VERSION.'/'.$pageId.'/leadgen_forms', [
                'access_token' => $pageAccessToken,
                'fields' => 'id,name,status,created_time,questions',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['data'] ?? [];
            }

            Log::error('Facebook leadgen forms fetch failed', [
                'page_id' => $pageId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Facebook leadgen forms fetch error', [
                'page_id' => $pageId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Make a generic API request to Facebook Graph API
     */
    public function makeApiRequest(string $endpoint, array $params = [], string $method = 'GET'): ?array
    {
        try {
            $url = self::BASE_URL.'/'.self::API_VERSION.$endpoint;

            $response = match (strtoupper($method)) {
                'GET' => Http::timeout(30)->get($url, $params),
                'POST' => Http::timeout(30)->post($url, $params),
                'DELETE' => Http::timeout(30)->delete($url, $params),
                default => Http::timeout(30)->get($url, $params),
            };

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Facebook API request failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'params' => $this->sanitizeParamsForLogging($params),
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Facebook API request error', [
                'method' => $method,
                'endpoint' => $endpoint,
                'params' => $this->sanitizeParamsForLogging($params),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get lead generation forms for multiple pages
     */
    public function getLeadgenFormsForPages(array $pageIds, string $accessToken): array
    {
        $allForms = [];

        foreach ($pageIds as $pageId) {
            $forms = $this->getLeadgenForms($pageId, $accessToken);
            if ($forms) {
                $allForms[$pageId] = $forms;
            }
        }

        return $allForms;
    }

    /**
     * Test if app has required permissions for lead retrieval
     */
    public function testLeadPermissions(string $accessToken): array
    {
        try {
            $response = $this->makeApiRequest('/me/permissions', [
                'access_token' => $accessToken,
            ]);

            if (! $response || ! isset($response['data'])) {
                return ['success' => false, 'message' => 'Failed to fetch permissions'];
            }

            $requiredPermissions = ['leads_retrieval', 'pages_manage_metadata', 'pages_read_engagement'];
            $grantedPermissions = [];
            $missingPermissions = [];

            foreach ($response['data'] as $permission) {
                if (in_array($permission['permission'], $requiredPermissions)) {
                    if ($permission['status'] === 'granted') {
                        $grantedPermissions[] = $permission['permission'];
                    } else {
                        $missingPermissions[] = $permission['permission'];
                    }
                }
            }

            $allRequired = array_diff($requiredPermissions, $grantedPermissions);

            return [
                'success' => empty($allRequired),
                'granted' => $grantedPermissions,
                'missing' => array_merge($missingPermissions, $allRequired),
                'message' => empty($allRequired)
                    ? 'All required permissions granted'
                    : 'Missing permissions: '.implode(', ', $allRequired),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Permission check failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get page insights for lead forms
     */
    public function getPageInsights(string $pageId, string $pageAccessToken, array $metrics = []): ?array
    {
        $defaultMetrics = ['page_leads', 'page_leads_by_source'];
        $metricsToFetch = ! empty($metrics) ? $metrics : $defaultMetrics;

        try {
            return $this->makeApiRequest("/{$pageId}/insights", [
                'access_token' => $pageAccessToken,
                'metric' => implode(',', $metricsToFetch),
                'period' => 'day',
                'since' => now()->subDays(30)->format('Y-m-d'),
                'until' => now()->format('Y-m-d'),
            ]);
        } catch (\Exception $e) {
            Log::error('Facebook page insights fetch error', [
                'page_id' => $pageId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Batch request for multiple API calls
     */
    public function batchRequest(array $requests, string $accessToken): ?array
    {
        try {
            $batchData = [];
            foreach ($requests as $key => $request) {
                $batchData[] = [
                    'method' => $request['method'] ?? 'GET',
                    'relative_url' => $request['url'],
                ];
            }

            return $this->makeApiRequest('/', [
                'access_token' => $accessToken,
                'batch' => json_encode($batchData),
            ], 'POST');

        } catch (\Exception $e) {
            Log::error('Facebook batch request error', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Sanitize params for logging (remove sensitive data)
     */
    private function sanitizeParamsForLogging(array $params): array
    {
        $sanitized = $params;

        // Remove or mask sensitive fields
        if (isset($sanitized['access_token'])) {
            $sanitized['access_token'] = substr($sanitized['access_token'], 0, 10).'...';
        }
        if (isset($sanitized['client_secret'])) {
            $sanitized['client_secret'] = '***';
        }

        return $sanitized;
    }
}
