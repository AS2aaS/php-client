<?php

declare(strict_types=1);

namespace AS2aaS\Modules;

use AS2aaS\Models\Tenant;
use AS2aaS\Models\Partner;

/**
 * Tenants module - Tenant management and context switching
 */
class Tenants extends BaseModule
{
    /**
     * Switch active tenant context
     * 
     * This is stateless - we just update the client's internal tenant context
     * and all subsequent tenant-scoped requests will use the X-Tenant-ID header
     */
    public function switch(string $tenantId): Tenant
    {
        // Set the tenant context internally (no API call needed for switching)
        $this->httpClient->setTenantId($tenantId);
        
        // Get the tenant information to return
        return $this->get($tenantId);
    }

    /**
     * Get current active tenant
     */
    public function getCurrent(): Tenant
    {
        $response = $this->httpClient->get('tenants/current');
        return new Tenant($response);
    }

    /**
     * Get tenant by ID
     */
    public function get(string $tenantId): Tenant
    {
        $response = $this->httpClient->get("tenants/{$tenantId}");
        return new Tenant($response['data'] ?? $response);
    }

    /**
     * Update tenant
     */
    public function update(string $tenantId, array $data): Tenant
    {
        $response = $this->httpClient->put("tenants/{$tenantId}", $data);
        return new Tenant($response);
    }

    /**
     * Delete tenant
     */
    public function delete(string $tenantId): bool
    {
        $this->httpClient->delete("tenants/{$tenantId}");
        return true;
    }

    /**
     * Inherit master partner to tenant with optional overrides
     */
    public function inheritMasterPartner(string $tenantId, string $masterPartnerId, array $overrides = []): Partner
    {
        $data = [
            'tenantId' => $tenantId,
        ];

        if (!empty($overrides)) {
            $data['overrideSettings'] = $overrides;
        }

        $response = $this->httpClient->post("accounts/partners/{$masterPartnerId}/inherit", $data);
        return new Partner($response);
    }
}
