<?php

declare(strict_types=1);

namespace AS2aaS\Testing;

use AS2aaS\Models\Account;
use AS2aaS\Models\Tenant;

/**
 * Mock Accounts module
 */
class MockAccounts
{
    private MockData $mockData;

    public function __construct(MockData $mockData)
    {
        $this->mockData = $mockData;
    }

    public function get(): Account
    {
        // Return first account or create default
        if (empty($this->mockData->accounts)) {
            $this->mockData->accounts['1'] = new Account([
                'id' => 1,
                'name' => 'Mock Account',
                'plan_type' => 'startup',
                'status' => 'active'
            ]);
        }

        return reset($this->mockData->accounts);
    }

    public function listTenants(): array
    {
        return array_values($this->mockData->tenants);
    }

    public function createTenant(array $tenantData): Tenant
    {
        $tenantId = $this->mockData->getNextId('tenants');
        
        $tenantData['id'] = $tenantId;
        $tenantData['account_id'] = 1;
        $tenantData['status'] = 'active';
        $tenantData['created_at'] = date('c');

        if (!isset($tenantData['slug']) && isset($tenantData['name'])) {
            $tenantData['slug'] = strtolower(str_replace(' ', '-', $tenantData['name'])) . '-' . time();
        }

        $tenant = new Tenant($tenantData);
        $this->mockData->tenants[$tenantId] = $tenant;

        return $tenant;
    }

    public function masterPartners(): MockMasterPartners
    {
        return new MockMasterPartners($this->mockData);
    }
}
