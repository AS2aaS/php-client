<?php

declare(strict_types=1);

namespace AS2aaS\Testing;

use AS2aaS\Models\Message;
use AS2aaS\Models\Partner;

/**
 * Mock Messages module
 */
class MockMessages
{
    private MockData $mockData;
    private ?string $tenantId;

    public function __construct(MockData $mockData, ?string $tenantId = null)
    {
        $this->mockData = $mockData;
        $this->tenantId = $tenantId;
    }

    public function send($partner, string $content, string $subject = '', array $options = []): Message
    {
        $partnerId = $partner instanceof Partner ? $partner->getId() : $partner;
        $messageId = $this->mockData->getNextId('messages', 'msg');

        $messageData = [
            'id' => $messageId,
            'message_id' => 'MOCK-' . strtoupper(uniqid()) . '@as2aas.com',
            'partner_id' => $partnerId,
            'status' => 'delivered', // Mock as delivered immediately
            'direction' => 'outbound',
            'subject' => $subject,
            'content_type' => $options['contentType'] ?? 'application/json',
            'bytes' => strlen($content),
            'mdn_mode' => 'async',
            'created_at' => date('c'),
            'sent_at' => date('c'),
            'delivered_at' => date('c'),
            'metadata' => $options['metadata'] ?? []
        ];

        $message = new Message($messageData);
        $this->mockData->messages[$messageId] = $message;

        return $message;
    }

    public function sendFile($partner, string $filePath, string $subject = '', array $options = []): Message
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        return $this->send($partner, $content, $subject, $options);
    }

    public function sendBatch(array $messages): array
    {
        $successful = [];
        $failed = [];

        foreach ($messages as $messageData) {
            try {
                $message = $this->send(
                    $messageData['partner'],
                    $messageData['content'],
                    $messageData['subject'] ?? '',
                    $messageData['options'] ?? []
                );
                $successful[] = $message;
            } catch (\Exception $e) {
                $failed[] = [
                    'error' => $e->getMessage(),
                    'data' => $messageData
                ];
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
            'total' => count($messages)
        ];
    }

    public function get(string $messageId): Message
    {
        if (!isset($this->mockData->messages[$messageId])) {
            throw new \Exception("Message with ID '{$messageId}' not found");
        }

        return $this->mockData->messages[$messageId];
    }

    public function list(array $options = []): array
    {
        $messages = array_values($this->mockData->messages);

        // Apply filters
        if (isset($options['status'])) {
            $messages = array_filter($messages, fn($m) => $m->getStatus() === $options['status']);
        }

        if (isset($options['direction'])) {
            $messages = array_filter($messages, fn($m) => $m->getDirection() === $options['direction']);
        }

        $limit = $options['limit'] ?? 20;
        $offset = $options['offset'] ?? 0;

        return [
            'data' => array_slice($messages, $offset, $limit),
            'total' => count($messages),
            'hasMore' => ($offset + $limit) < count($messages)
        ];
    }

    public function getPayload(string $messageId, array $options = []): string
    {
        $message = $this->get($messageId);
        $mockPayload = "Mock payload for message {$messageId}";

        if (isset($options['saveToFile'])) {
            file_put_contents($options['saveToFile'], $mockPayload);
        }

        return $mockPayload;
    }

    public function waitForDelivery(string $messageId, int $timeout = 300000): Message
    {
        $message = $this->get($messageId);
        
        // Mock immediate delivery
        $message->setAttribute('status', 'delivered');
        $message->setAttribute('delivered_at', date('c'));
        
        return $message;
    }

    public function validate(string $content, array $options = []): array
    {
        // Mock validation based on content
        $isEDI = str_starts_with(trim($content), 'ISA');
        
        return [
            'valid' => $isEDI,
            'format' => $isEDI ? 'EDI X12' : 'Unknown',
            'issues' => $isEDI ? [] : ['Unknown format'],
            'details' => ['mock_validation' => true]
        ];
    }

    public function sendTest($partner, array $options = []): Message
    {
        return $this->send($partner, 'Test message content', 'Test Message', [
            'contentType' => 'text/plain',
            'metadata' => ['test' => true]
        ]);
    }
}
