<?php

declare(strict_types=1);

namespace AS2aaS\Testing;

use AS2aaS\Models\Account;
use AS2aaS\Models\Certificate;
use AS2aaS\Models\Message;
use AS2aaS\Models\Partner;
use AS2aaS\Models\Tenant;
use AS2aaS\Models\Webhook;

/**
 * Mock AS2aaS Client for testing
 * 
 * Provides a complete mock implementation that can be used in unit tests
 * without making actual API calls.
 */
class MockClient
{
    private MockData $mockData;
    private ?string $currentTenantId = null;

    public function __construct()
    {
        $this->mockData = new MockData();
        $this->seedDefaultData();
    }

    /**
     * Get mock data storage
     */
    public function getMockData(): MockData
    {
        return $this->mockData;
    }

    /**
     * Set tenant context (same as real client)
     */
    public function setTenant(?string $tenantId): self
    {
        $this->currentTenantId = $tenantId;
        return $this;
    }

    /**
     * Get current tenant
     */
    public function getCurrentTenant(): ?string
    {
        return $this->currentTenantId;
    }

    /**
     * Partners module mock
     */
    public function partners(): MockPartners
    {
        return new MockPartners($this->mockData, $this->currentTenantId);
    }

    /**
     * Messages module mock
     */
    public function messages(): MockMessages
    {
        return new MockMessages($this->mockData, $this->currentTenantId);
    }

    /**
     * Certificates module mock
     */
    public function certificates(): MockCertificates
    {
        return new MockCertificates($this->mockData, $this->currentTenantId);
    }

    /**
     * Accounts module mock
     */
    public function accounts(): MockAccounts
    {
        return new MockAccounts($this->mockData);
    }

    /**
     * Tenants module mock
     */
    public function tenants(): MockTenants
    {
        return new MockTenants($this->mockData, $this);
    }

    /**
     * Webhooks module mock
     */
    public function webhooks(): MockWebhooks
    {
        return new MockWebhooks($this->mockData);
    }

    /**
     * Utils module mock
     */
    public function utils(): MockUtils
    {
        return new MockUtils();
    }

    /**
     * Seed with default test data
     */
    private function seedDefaultData(): void
    {
        // Add default account
        $this->mockData->accounts['1'] = new Account([
            'id' => 1,
            'name' => 'Test Account',
            'plan_type' => 'startup',
            'status' => 'active',
            'tenants_count' => 2
        ]);

        // Add default tenants
        $this->mockData->tenants['1'] = new Tenant([
            'id' => 1,
            'account_id' => 1,
            'name' => 'Test Tenant 1',
            'slug' => 'test-tenant-1',
            'status' => 'active'
        ]);

        $this->mockData->tenants['2'] = new Tenant([
            'id' => 2,
            'account_id' => 1,
            'name' => 'Test Tenant 2',
            'slug' => 'test-tenant-2',
            'status' => 'active'
        ]);

        // Add default partners
        $this->mockData->partners['prt_001'] = new Partner([
            'id' => 'prt_001',
            'name' => 'McKesson Corporation',
            'as2_id' => 'MCKESSON',
            'url' => 'https://as2.mckesson.com/receive',
            'type' => 'inherited',
            'sign' => true,
            'encrypt' => true,
            'compress' => false,
            'mdn_mode' => 'async',
            'active' => true
        ]);

        $this->mockData->partners['prt_002'] = new Partner([
            'id' => 'prt_002',
            'name' => 'Cardinal Health',
            'as2_id' => 'CARDINAL',
            'url' => 'https://as2.cardinal.com/receive',
            'type' => 'tenant',
            'sign' => true,
            'encrypt' => true,
            'compress' => false,
            'mdn_mode' => 'sync',
            'active' => true
        ]);
    }
}
