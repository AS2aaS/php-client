<?php

declare(strict_types=1);

namespace AS2aaS\Tests\Models;

use AS2aaS\Models\Tenant;
use PHPUnit\Framework\TestCase;

class TenantTest extends TestCase
{
    public function testTenantCreation(): void
    {
        $data = [
            'id' => 1,
            'account_id' => 1,
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => 'active',
            'created_at' => '2024-01-01T12:00:00Z',
            'updated_at' => '2024-01-01T12:00:00Z',
        ];

        $tenant = new Tenant($data);

        $this->assertEquals('1', $tenant->getId());
        $this->assertEquals('Test Tenant', $tenant->getName());
        $this->assertEquals('test-tenant', $tenant->getSlug());
        $this->assertEquals(1, $tenant->getAccountId());
        $this->assertEquals('active', $tenant->getStatus());
        $this->assertTrue($tenant->isActive());
    }

    public function testTenantDefaults(): void
    {
        $tenant = new Tenant([
            'id' => 1,
            'name' => 'Test Tenant'
        ]);

        $this->assertEquals('', $tenant->getSlug());
        $this->assertEquals(0, $tenant->getAccountId());
        $this->assertEquals('active', $tenant->getStatus());
        $this->assertTrue($tenant->isActive());
        $this->assertEquals(0, $tenant->getMessageCount30d());
    }

    public function testInactiveTenant(): void
    {
        $tenant = new Tenant([
            'id' => 1,
            'name' => 'Inactive Tenant',
            'status' => 'inactive'
        ]);

        $this->assertEquals('inactive', $tenant->getStatus());
        $this->assertFalse($tenant->isActive());
    }
}
