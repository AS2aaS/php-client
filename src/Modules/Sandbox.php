<?php

declare(strict_types=1);

namespace AS2aaS\Modules;

use AS2aaS\Models\Message;

/**
 * Sandbox module - Testing and development utilities
 */
class Sandbox extends BaseModule
{
    public function getInfo(): array
    {
        return $this->httpClient->get('sandbox/info');
    }

    public function getMessages(): array
    {
        $response = $this->httpClient->get('sandbox/messages');
        return array_map(fn($data) => new Message($data), $response['data'] ?? []);
    }

    public function simulateIncoming(array $messageData): Message
    {
        $data = [
            'partnerId' => $messageData['partnerId'],
            'content' => base64_encode($messageData['content']),
            'contentType' => $messageData['contentType'],
        ];

        if (isset($messageData['subject'])) {
            $data['subject'] = $messageData['subject'];
        }

        if (isset($messageData['fromAs2Id'])) {
            $data['fromAs2Id'] = $messageData['fromAs2Id'];
        }

        $response = $this->httpClient->post('sandbox/simulate-incoming', $data);
        return new Message($response);
    }

    public function getSample(string $type): string
    {
        return $this->httpClient->get("sandbox/samples/{$type}")['content'] ?? '';
    }

    public function clear(): void
    {
        $this->httpClient->post('sandbox/clear');
    }
}
