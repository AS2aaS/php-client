<?php

declare(strict_types=1);

namespace AS2aaS\Tests\Models;

use AS2aaS\Models\Webhook;
use PHPUnit\Framework\TestCase;

class WebhookTest extends TestCase
{
    public function testWebhookCreation(): void
    {
        $data = [
            'id' => 'whe_123',
            'url' => 'https://example.com/webhook',
            'events' => ['message.sent', 'message.delivered'],
            'secret' => 'webhook_secret_123',
            'description' => 'Test webhook',
            'active' => true,
            'created_at' => '2024-01-01T12:00:00Z',
        ];

        $webhook = new Webhook($data);

        $this->assertEquals('whe_123', $webhook->getId());
        $this->assertEquals('https://example.com/webhook', $webhook->getUrl());
        $this->assertEquals(['message.sent', 'message.delivered'], $webhook->getEvents());
        $this->assertEquals('webhook_secret_123', $webhook->getSecret());
        $this->assertEquals('Test webhook', $webhook->getDescription());
        $this->assertTrue($webhook->isActive());
    }

    public function testWebhookDefaults(): void
    {
        $webhook = new Webhook([
            'id' => 'whe_123',
            'url' => 'https://example.com/webhook'
        ]);

        $this->assertEquals([], $webhook->getEvents());
        $this->assertEquals('', $webhook->getSecret());
        $this->assertNull($webhook->getDescription());
        $this->assertTrue($webhook->isActive());
    }
}
