<?php

declare(strict_types=1);

namespace AS2aaS\Modules;

use AS2aaS\Exceptions\AS2ValidationError;
use AS2aaS\Models\Webhook;

/**
 * Webhooks module - Event handling and webhook management
 */
class Webhooks extends BaseModule
{
    /**
     * Create webhook endpoint
     */
    public function create(array $webhookData): Webhook
    {
        // Validate required fields
        if (!isset($webhookData['url'])) {
            throw new AS2ValidationError('Webhook URL is required');
        }

        if (!isset($webhookData['events']) || empty($webhookData['events'])) {
            throw new AS2ValidationError('At least one event type is required');
        }

        // Auto-generate secret if not provided
        if (!isset($webhookData['secret'])) {
            $webhookData['secret'] = bin2hex(random_bytes(32));
        }

        // Add idempotency key for webhook creation
        $options = [
            'json' => $webhookData,
            'headers' => [
                'Idempotency-Key' => sprintf(
                    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                )
            ]
        ];
        
        $response = $this->httpClient->requestWithOptions('POST', 'webhook-endpoints', $options);
        return new Webhook($response);
    }

    /**
     * List webhook endpoints
     */
    public function list(): array
    {
        $response = $this->httpClient->get('webhook-endpoints');
        return array_map(fn($data) => new Webhook($data), $response['data'] ?? []);
    }

    /**
     * Get webhook endpoint
     */
    public function get(string $webhookId): Webhook
    {
        $response = $this->httpClient->get("webhook-endpoints/{$webhookId}");
        return new Webhook($response);
    }

    /**
     * Update webhook endpoint
     */
    public function update(string $webhookId, array $data): Webhook
    {
        $response = $this->httpClient->patch("webhook-endpoints/{$webhookId}", $data);
        return new Webhook($response);
    }

    /**
     * Delete webhook endpoint
     */
    public function delete(string $webhookId): bool
    {
        $this->httpClient->delete("webhook-endpoints/{$webhookId}");
        return true;
    }

    /**
     * Test webhook endpoint
     */
    public function test(string $webhookId): array
    {
        $response = $this->httpClient->post("webhook-endpoints/{$webhookId}/test");
        return $response;
    }

    /**
     * Get webhook statistics
     */
    public function getStats(): array
    {
        $response = $this->httpClient->get('webhook-endpoints-stats');
        return $response;
    }

    /**
     * Verify webhook signature for security
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process webhook event with typed handlers
     */
    public function handleEvent(array $eventData, array $handlers): void
    {
        $eventType = $eventData['type'] ?? 'unknown';

        if (isset($handlers[$eventType]) && is_callable($handlers[$eventType])) {
            $handlers[$eventType]($eventData['data'] ?? []);
        } elseif (isset($handlers['*']) && is_callable($handlers['*'])) {
            // Catch-all handler
            $handlers['*']($eventData);
        }
    }
}
