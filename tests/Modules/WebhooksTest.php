<?php

declare(strict_types=1);

namespace AS2aaS\Tests\Modules;

use AS2aaS\Http\HttpClient;
use AS2aaS\Modules\Webhooks;
use AS2aaS\Models\Webhook;
use PHPUnit\Framework\TestCase;
use Mockery;

class WebhooksTest extends TestCase
{
    private Webhooks $webhooks;
    private $mockHttpClient;

    protected function setUp(): void
    {
        $this->mockHttpClient = Mockery::mock(HttpClient::class);
        $this->webhooks = new Webhooks($this->mockHttpClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testVerifySignature(): void
    {
        $payload = '{"test": "data"}';
        $secret = 'test_secret';
        $validSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        $invalidSignature = 'sha256=invalid_signature';

        $this->assertTrue($this->webhooks->verifySignature($payload, $validSignature, $secret));
        $this->assertFalse($this->webhooks->verifySignature($payload, $invalidSignature, $secret));
    }

    public function testHandleEvent(): void
    {
        $eventData = [
            'type' => 'message.delivered',
            'data' => [
                'id' => 'msg_123',
                'status' => 'delivered'
            ]
        ];

        $handlerCalled = false;
        $receivedData = null;

        $handlers = [
            'message.delivered' => function($data) use (&$handlerCalled, &$receivedData) {
                $handlerCalled = true;
                $receivedData = $data;
            }
        ];

        $this->webhooks->handleEvent($eventData, $handlers);

        $this->assertTrue($handlerCalled);
        $this->assertEquals(['id' => 'msg_123', 'status' => 'delivered'], $receivedData);
    }

    public function testHandleEventWithCatchAll(): void
    {
        $eventData = [
            'type' => 'unknown.event',
            'data' => ['test' => 'data']
        ];

        $catchAllCalled = false;

        $handlers = [
            'message.delivered' => function($data) {
                // Should not be called
            },
            '*' => function($data) use (&$catchAllCalled) {
                $catchAllCalled = true;
            }
        ];

        $this->webhooks->handleEvent($eventData, $handlers);

        $this->assertTrue($catchAllCalled);
    }

    public function testCreateWebhook(): void
    {
        $webhookData = [
            'url' => 'https://example.com/webhook',
            'events' => ['message.delivered'],
            'description' => 'Test webhook'
        ];

        $expectedResponse = [
            'id' => 'whe_123',
            'url' => 'https://example.com/webhook',
            'events' => ['message.delivered'],
            'active' => true
        ];

        $this->mockHttpClient
            ->shouldReceive('requestWithOptions')
            ->once()
            ->with('POST', 'webhook-endpoints', Mockery::type('array'))
            ->andReturn($expectedResponse);

        $webhook = $this->webhooks->create($webhookData);

        $this->assertInstanceOf(Webhook::class, $webhook);
        $this->assertEquals('whe_123', $webhook->getId());
    }
}
