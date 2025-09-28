<?php

declare(strict_types=1);

namespace AS2aaS\Testing;

use AS2aaS\Models\Webhook;

/**
 * Mock Webhooks module
 */
class MockWebhooks
{
    private MockData $mockData;

    public function __construct(MockData $mockData)
    {
        $this->mockData = $mockData;
    }

    public function create(array $webhookData): Webhook
    {
        $webhookId = $this->mockData->getNextId('webhooks', 'whe');
        
        $webhookData['id'] = $webhookId;
        $webhookData['active'] = true;
        $webhookData['created_at'] = date('c');

        if (!isset($webhookData['secret'])) {
            $webhookData['secret'] = 'mock_secret_' . uniqid();
        }

        $webhook = new Webhook($webhookData);
        $this->mockData->webhooks[$webhookId] = $webhook;

        return $webhook;
    }

    public function list(): array
    {
        return array_values($this->mockData->webhooks);
    }

    public function get(string $webhookId): Webhook
    {
        if (!isset($this->mockData->webhooks[$webhookId])) {
            throw new \Exception("Webhook with ID '{$webhookId}' not found");
        }

        return $this->mockData->webhooks[$webhookId];
    }

    public function update(string $webhookId, array $data): Webhook
    {
        $webhook = $this->get($webhookId);
        $webhook->fill($data);
        $webhook->setAttribute('updated_at', date('c'));

        return $webhook;
    }

    public function delete(string $webhookId): bool
    {
        if (!isset($this->mockData->webhooks[$webhookId])) {
            throw new \Exception("Webhook with ID '{$webhookId}' not found");
        }

        unset($this->mockData->webhooks[$webhookId]);
        return true;
    }

    public function test(string $webhookId): array
    {
        return [
            'success' => true,
            'response_time' => 150,
            'status_code' => 200
        ];
    }

    public function getStats(): array
    {
        return [
            'total_webhooks' => count($this->mockData->webhooks),
            'active_webhooks' => count(array_filter($this->mockData->webhooks, fn($w) => $w->isActive())),
            'total_deliveries' => rand(100, 1000),
            'successful_deliveries' => rand(90, 99) / 100
        ];
    }

    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    public function handleEvent(array $eventData, array $handlers): void
    {
        $eventType = $eventData['type'] ?? 'unknown';

        if (isset($handlers[$eventType]) && is_callable($handlers[$eventType])) {
            $handlers[$eventType]($eventData['data'] ?? []);
        } elseif (isset($handlers['*']) && is_callable($handlers['*'])) {
            $handlers['*']($eventData);
        }
    }
}
