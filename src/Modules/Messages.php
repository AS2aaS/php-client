<?php

declare(strict_types=1);

namespace AS2aaS\Modules;

use AS2aaS\Exceptions\AS2Error;
use AS2aaS\Exceptions\AS2ValidationError;
use AS2aaS\Http\HttpClient;
use AS2aaS\Models\Message;
use AS2aaS\Models\Partner;

/**
 * Messages module - Send and receive AS2 messages
 */
class Messages extends BaseModule
{
    /**
     * Send AS2 message
     */
    public function send($partner, string $content, string $subject = '', array $options = []): Message
    {
        $partnerId = $partner instanceof Partner ? $partner->getId() : $partner;

        $data = [
            'partner_id' => $partnerId,
            'subject' => $subject,
            'payload' => [
                'content' => base64_encode($content)
            ]
        ];

        // Add optional parameters
        if (isset($options['contentType'])) {
            $data['content_type'] = $options['contentType'];
        } else {
            // Auto-detect content type
            $data['content_type'] = $this->detectContentType($content, $subject);
        }

        if (isset($options['priority'])) {
            $data['priority'] = $options['priority'];
        }

        if (isset($options['compress'])) {
            $data['compress'] = $options['compress'];
        }

        if (isset($options['encrypt'])) {
            $data['encrypt'] = $options['encrypt'];
        }

        if (isset($options['sign'])) {
            $data['sign'] = $options['sign'];
        }

        if (isset($options['scheduledAt'])) {
            $data['scheduledAt'] = $options['scheduledAt']->format('c');
        }

        if (isset($options['metadata'])) {
            $data['metadata'] = $options['metadata'];
        }

        // Add idempotency key for message sending (UUID format)
        $options = [
            'json' => $data,
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
        
        $response = $this->httpClient->requestWithOptions('POST', 'messages', $options);
        return new Message($response);
    }

    /**
     * Send file directly from filesystem
     */
    public function sendFile($partner, string $filePath, string $subject = '', array $options = []): Message
    {
        if (!file_exists($filePath)) {
            throw new AS2ValidationError("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new AS2Error("Unable to read file: {$filePath}");
        }

        // Auto-detect content type from filename if not provided
        if (!isset($options['contentType'])) {
            $options['contentType'] = $this->detectContentTypeFromFilename($filePath);
        }

        return $this->send($partner, $content, $subject, $options);
    }

    /**
     * Send multiple messages efficiently
     */
    public function sendBatch(array $messages): array
    {
        $batchData = [];

        foreach ($messages as $message) {
            $partnerId = $message['partner'] instanceof Partner 
                ? $message['partner']->getId() 
                : $message['partner'];

            $messageData = [
                'partnerId' => $partnerId,
                'content' => base64_encode($message['content']),
                'subject' => $message['subject'] ?? '',
            ];

            if (isset($message['options'])) {
                $messageData = array_merge($messageData, $message['options']);
            }

            $batchData[] = $messageData;
        }

        $response = $this->httpClient->post('messages/batch', ['messages' => $batchData]);

        return [
            'successful' => array_map(fn($data) => new Message($data), $response['successful'] ?? []),
            'failed' => $response['failed'] ?? [],
            'total' => count($batchData),
        ];
    }

    /**
     * Get message details and status
     */
    public function get(string $messageId): Message
    {
        $response = $this->httpClient->get("messages/{$messageId}");
        return new Message($response);
    }

    /**
     * List messages with filtering and pagination
     */
    public function list(array $options = []): array
    {
        $query = [];

        if (isset($options['partner'])) {
            $partnerId = $options['partner'] instanceof Partner 
                ? $options['partner']->getId() 
                : $options['partner'];
            $query['partnerId'] = $partnerId;
        }

        if (isset($options['status'])) {
            $query['status'] = $options['status'];
        }

        if (isset($options['direction'])) {
            $query['direction'] = $options['direction'];
        }

        if (isset($options['dateRange'])) {
            if (isset($options['dateRange']['from'])) {
                $query['dateFrom'] = $options['dateRange']['from']->format('c');
            }
            if (isset($options['dateRange']['to'])) {
                $query['dateTo'] = $options['dateRange']['to']->format('c');
            }
        }

        $query['limit'] = $options['limit'] ?? 20;
        $query['offset'] = $options['offset'] ?? 0;

        $response = $this->httpClient->get('messages', $query);

        return [
            'data' => array_map(fn($data) => new Message($data), $response['data'] ?? []),
            'total' => $response['total'] ?? 0,
            'hasMore' => $response['hasMore'] ?? false,
        ];
    }

    /**
     * Download message payload content
     */
    public function getPayload(string $messageId, array $options = []): string
    {
        $query = [];
        if (isset($options['encoding'])) {
            $query['encoding'] = $options['encoding'];
        }

        $content = $this->httpClient->download("messages/{$messageId}/payload", $query);

        // Save to file if requested
        if (isset($options['saveToFile'])) {
            file_put_contents($options['saveToFile'], $content);
        }

        return $content;
    }

    /**
     * Wait for message delivery confirmation
     */
    public function waitForDelivery(string $messageId, int $timeout = 300000): Message
    {
        $startTime = time();
        $timeoutSeconds = $timeout / 1000;

        while (time() - $startTime < $timeoutSeconds) {
            $message = $this->get($messageId);

            if ($message->isDelivered()) {
                return $message;
            }

            if ($message->isFailed()) {
                throw new AS2Error("Message delivery failed: " . $message->getErrorMessage());
            }

            // Wait 5 seconds before next check
            sleep(5);
        }

        throw new AS2Error("Message delivery timeout after {$timeout}ms");
    }

    /**
     * Validate message content before sending
     */
    public function validate(string $content, array $options = []): array
    {
        $data = [
            'content' => base64_encode($content),
        ];

        if (isset($options['contentType'])) {
            $data['contentType'] = $options['contentType'];
        } else {
            $data['contentType'] = $this->detectContentType($content);
        }

        if (isset($options['validationType'])) {
            $data['validationType'] = $options['validationType'];
        }

        $response = $this->httpClient->post('messages/validate', $data);

        return [
            'valid' => $response['valid'] ?? false,
            'format' => $response['format'] ?? null,
            'issues' => $response['issues'] ?? [],
            'details' => $response['details'] ?? [],
        ];
    }

    /**
     * Send test message to verify partner configuration
     */
    public function sendTest($partner, array $options = []): Message
    {
        $partnerId = $partner instanceof Partner ? $partner->getId() : $partner;

        $data = [
            'partnerId' => $partnerId,
            'messageType' => $options['messageType'] ?? 'test',
        ];

        if (isset($options['customContent'])) {
            $data['customContent'] = base64_encode($options['customContent']);
        }

        if (isset($options['encrypt'])) {
            $data['encrypt'] = $options['encrypt'];
        }

        if (isset($options['sign'])) {
            $data['sign'] = $options['sign'];
        }

        if (isset($options['requestMdn'])) {
            $data['requestMdn'] = $options['requestMdn'];
        }

        $response = $this->httpClient->post('messages/test', $data);
        return new Message($response);
    }

    /**
     * Auto-detect content type from content
     */
    private function detectContentType(string $content, string $filename = ''): string
    {
        // Try filename first
        if (!empty($filename)) {
            $type = $this->detectContentTypeFromFilename($filename);
            if ($type !== 'application/octet-stream') {
                return $type;
            }
        }

        // Analyze content
        if (str_starts_with(trim($content), 'ISA')) {
            return 'application/edi-x12';
        }

        if (str_starts_with(trim($content), 'UNA') || str_starts_with(trim($content), 'UNB')) {
            return 'application/edifact';
        }

        if (str_starts_with(trim($content), '<?xml') || str_starts_with(trim($content), '<')) {
            return 'application/xml';
        }

        if ($this->isJson($content)) {
            return 'application/json';
        }

        return 'application/octet-stream';
    }

    /**
     * Detect content type from filename
     */
    private function detectContentTypeFromFilename(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'edi', 'x12' => 'application/edi-x12',
            'xml' => 'application/xml',
            'json' => 'application/json',
            'pdf' => 'application/pdf',
            'txt', 'text' => 'text/plain',
            'csv' => 'text/csv',
            'zip' => 'application/zip',
            default => 'application/octet-stream',
        };
    }

    /**
     * Check if content is valid JSON
     */
    private function isJson(string $content): bool
    {
        json_decode($content);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
