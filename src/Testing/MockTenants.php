<?php

declare(strict_types=1);

namespace AS2aaS\Testing;

use AS2aaS\Models\Tenant;

/**
 * Mock Tenants module
 */
class MockTenants
{
    private MockData $mockData;
    private $mockClient;

    public function __construct(MockData $mockData, $mockClient)
    {
        $this->mockData = $mockData;
        $this->mockClient = $mockClient;
    }

    public function switch(string $tenantId): Tenant
    {
        $tenant = $this->get($tenantId);
        $this->mockClient->setTenant($tenantId);
        
        return $tenant;
    }

    public function getCurrent(): Tenant
    {
        $currentTenantId = $this->mockClient->getCurrentTenant();
        
        if (!$currentTenantId) {
            // Return first tenant as default
            return reset($this->mockData->tenants);
        }

        return $this->get($currentTenantId);
    }

    public function get(string $tenantId): Tenant
    {
        if (!isset($this->mockData->tenants[$tenantId])) {
            throw new \Exception("Tenant with ID '{$tenantId}' not found");
        }

        return $this->mockData->tenants[$tenantId];
    }

    public function update(string $tenantId, array $data): Tenant
    {
        $tenant = $this->get($tenantId);
        $tenant->fill($data);
        $tenant->setAttribute('updated_at', date('c'));

        return $tenant;
    }

    public function delete(string $tenantId): bool
    {
        if (!isset($this->mockData->tenants[$tenantId])) {
            throw new \Exception("Tenant with ID '{$tenantId}' not found");
        }

        unset($this->mockData->tenants[$tenantId]);
        return true;
    }
}
