<?php

declare(strict_types=1);

namespace AS2aaS\Tests\Models;

use AS2aaS\Models\Account;
use PHPUnit\Framework\TestCase;

class AccountTest extends TestCase
{
    public function testAccountCreation(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Account',
            'slug' => 'test-account',
            'plan_type' => 'startup',
            'status' => 'active',
            'tenants_count' => 3,
            'created_at' => '2024-01-01T12:00:00Z',
            'updated_at' => '2024-01-01T12:00:00Z',
        ];

        $account = new Account($data);

        $this->assertEquals('1', $account->getId());
        $this->assertEquals('Test Account', $account->getName());
        $this->assertEquals('test-account', $account->getSlug());
        $this->assertEquals('startup', $account->getPlan());
        $this->assertEquals('active', $account->getStatus());
        $this->assertEquals(3, $account->getTenantCount());
    }

    public function testAccountDefaults(): void
    {
        $account = new Account([
            'id' => 1,
            'name' => 'Test Account'
        ]);

        $this->assertEquals('free', $account->getPlan());
        $this->assertEquals('active', $account->getStatus());
        $this->assertEquals(0, $account->getTenantCount());
        $this->assertEquals('', $account->getSlug());
    }

    public function testAccountJsonSerialization(): void
    {
        $account = new Account([
            'id' => 1,
            'name' => 'Test Account',
            'plan_type' => 'enterprise'
        ]);

        $json = json_encode($account);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals('1', $decoded['id']);
        $this->assertEquals('Test Account', $decoded['name']);
        $this->assertEquals('enterprise', $decoded['plan_type']);
    }
}
