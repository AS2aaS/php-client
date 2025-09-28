<?php

declare(strict_types=1);

namespace AS2aaS\Tests\Testing;

use AS2aaS\Client;
use AS2aaS\Testing\MockClient;
use AS2aaS\Models\Partner;
use AS2aaS\Models\Message;
use AS2aaS\Models\Tenant;
use PHPUnit\Framework\TestCase;

class MockClientTest extends TestCase
{
    private MockClient $mockClient;

    protected function setUp(): void
    {
        $this->mockClient = Client::createMock();
    }

    public function testMockClientCreation(): void
    {
        $this->assertInstanceOf(MockClient::class, $this->mockClient);
        $this->assertNotNull($this->mockClient->getMockData());
    }

    public function testMockClientHasDefaultData(): void
    {
        // Should have seeded default data
        $partners = $this->mockClient->partners()->list();
        $this->assertGreaterThan(0, count($partners));

        $tenants = $this->mockClient->accounts()->listTenants();
        $this->assertGreaterThan(0, count($tenants));
    }

    public function testMockPartnerOperations(): void
    {
        // Create partner
        $partner = $this->mockClient->partners()->create([
            'name' => 'Test Partner',
            'as2_id' => 'TEST-PARTNER',
            'url' => 'https://test.example.com/as2'
        ]);

        $this->assertInstanceOf(Partner::class, $partner);
        $this->assertEquals('Test Partner', $partner->getName());
        $this->assertEquals('TEST-PARTNER', $partner->getAs2Id());

        // Get by AS2 ID
        $foundPartner = $this->mockClient->partners()->getByAs2Id('TEST-PARTNER');
        $this->assertEquals($partner->getId(), $foundPartner->getId());

        // List partners
        $partners = $this->mockClient->partners()->list();
        $this->assertGreaterThan(2, count($partners)); // Default + created

        // Test partner
        $testResult = $this->mockClient->partners()->test($partner);
        $this->assertTrue($testResult['success']);
    }

    public function testMockMessageOperations(): void
    {
        // Get a partner
        $partners = $this->mockClient->partners()->list();
        $partner = $partners[0];

        // Send message
        $message = $this->mockClient->messages()->send(
            $partner,
            'Test EDI content',
            'Test Message'
        );

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('delivered', $message->getStatus());
        $this->assertEquals('Test Message', $message->getSubject());
        $this->assertTrue($message->isDelivered());

        // Get message
        $retrievedMessage = $this->mockClient->messages()->get($message->getId());
        $this->assertEquals($message->getId(), $retrievedMessage->getId());

        // List messages
        $messageList = $this->mockClient->messages()->list();
        $this->assertGreaterThan(0, count($messageList['data']));
    }

    public function testMockTenantSwitching(): void
    {
        // Get available tenants
        $tenants = $this->mockClient->accounts()->listTenants();
        $this->assertGreaterThan(0, count($tenants));

        $tenant = $tenants[0];

        // Switch tenant
        $switchedTenant = $this->mockClient->tenants()->switch($tenant->getId());
        $this->assertEquals($tenant->getId(), $switchedTenant->getId());

        // Verify context switched
        $this->assertEquals($tenant->getId(), $this->mockClient->getCurrentTenant());
    }

    public function testMockMasterPartnerInheritance(): void
    {
        // Create master partner
        $masterPartner = $this->mockClient->accounts()->masterPartners()->create([
            'name' => 'Test Master Partner',
            'as2_id' => 'TEST-MASTER',
            'url' => 'https://test-master.example.com/as2'
        ]);

        $this->assertEquals('master', $masterPartner->getType());

        // Inherit to tenant
        $inheritResult = $this->mockClient->accounts()->masterPartners()->inherit(
            $masterPartner->getId(),
            [
                'tenant_ids' => ['1'],
                'override_settings' => ['url' => 'https://custom.example.com/as2']
            ]
        );

        $this->assertTrue($inheritResult['success']);
        $this->assertEquals(1, $inheritResult['inherited_count']);

        // Check inheritance status
        $status = $this->mockClient->accounts()->masterPartners()->getInheritanceStatus($masterPartner->getId());
        $this->assertEquals(1, $status['inheritance_stats']['total_inherited']);
    }

    public function testMockWebhookOperations(): void
    {
        // Create webhook
        $webhook = $this->mockClient->webhooks()->create([
            'url' => 'https://test.example.com/webhook',
            'events' => ['message.delivered'],
            'description' => 'Test webhook'
        ]);

        $this->assertEquals('https://test.example.com/webhook', $webhook->getUrl());
        $this->assertEquals(['message.delivered'], $webhook->getEvents());

        // Verify signature
        $payload = '{"test": "data"}';
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $webhook->getSecret());
        $isValid = $this->mockClient->webhooks()->verifySignature($payload, $signature, $webhook->getSecret());
        $this->assertTrue($isValid);
    }

    public function testMockDataManipulation(): void
    {
        $mockData = $this->mockClient->getMockData();

        // Add custom partner directly to mock data
        $customPartner = new Partner([
            'id' => 'custom_001',
            'name' => 'Custom Partner',
            'as2_id' => 'CUSTOM',
            'url' => 'https://custom.example.com/as2'
        ]);

        $mockData->partners['custom_001'] = $customPartner;

        // Verify it appears in lists
        $partners = $this->mockClient->partners()->list();
        $found = false;
        foreach ($partners as $partner) {
            if ($partner->getId() === 'custom_001') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }
}
