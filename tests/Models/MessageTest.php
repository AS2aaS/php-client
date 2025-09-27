<?php

declare(strict_types=1);

namespace AS2aaS\Tests\Models;

use AS2aaS\Models\Message;
use AS2aaS\Models\Partner;
use PHPUnit\Framework\TestCase;
use DateTime;

class MessageTest extends TestCase
{
    public function testMessageCreation(): void
    {
        $data = [
            'id' => 'msg_123',
            'message_id' => 'AS2-MSG-20240101-001',
            'partner_id' => 'prt_123',
            'status' => 'delivered',
            'direction' => 'outbound',
            'subject' => 'Test Message',
            'content_type' => 'application/edi-x12',
            'bytes' => 1024,
            'created_at' => '2024-01-01T12:00:00Z',
            'sent_at' => '2024-01-01T12:01:00Z',
            'delivered_at' => '2024-01-01T12:02:00Z',
        ];

        $message = new Message($data);

        $this->assertEquals('msg_123', $message->getId());
        $this->assertEquals('AS2-MSG-20240101-001', $message->getMessageId());
        $this->assertEquals('delivered', $message->getStatus());
        $this->assertEquals('outbound', $message->getDirection());
        $this->assertEquals('Test Message', $message->getSubject());
        $this->assertEquals('application/edi-x12', $message->getContentType());
        $this->assertEquals(1024, $message->getSize());
        $this->assertTrue($message->isDelivered());
        $this->assertFalse($message->isFailed());
        $this->assertFalse($message->isPending());
    }

    public function testMessagePartner(): void
    {
        $message = new Message([
            'id' => 'msg_123',
            'partner_id' => 'prt_123',
        ]);

        // Note: Partner data would come from a separate API call in real usage
        $this->assertEquals('prt_123', $message->getAttribute('partner_id'));
    }

    public function testMessageStatuses(): void
    {
        $queuedMessage = new Message(['status' => 'queued']);
        $this->assertTrue($queuedMessage->isPending());
        $this->assertFalse($queuedMessage->isDelivered());
        $this->assertFalse($queuedMessage->isFailed());

        $processingMessage = new Message(['status' => 'processing']);
        $this->assertTrue($processingMessage->isPending());

        $sentMessage = new Message(['status' => 'sent']);
        $this->assertTrue($sentMessage->isPending());

        $deliveredMessage = new Message(['status' => 'delivered']);
        $this->assertTrue($deliveredMessage->isDelivered());
        $this->assertFalse($deliveredMessage->isPending());

        $failedMessage = new Message(['status' => 'failed']);
        $this->assertTrue($failedMessage->isFailed());
        $this->assertFalse($failedMessage->isPending());
    }

    public function testStatusDescriptions(): void
    {
        $testCases = [
            'queued' => 'Message is queued for processing',
            'processing' => 'Message is being processed',
            'sent' => 'Message has been sent to partner',
            'delivered' => 'Message delivery confirmed by partner',
            'failed' => 'Message delivery failed',
            'received' => 'Message received from partner',
            'unknown' => 'Unknown status',
        ];

        foreach ($testCases as $status => $expectedDescription) {
            $message = new Message(['status' => $status]);
            $this->assertEquals($expectedDescription, $message->getStatusDescription());
        }
    }

    public function testMessageWithError(): void
    {
        $message = new Message([
            'id' => 'msg_123',
            'status' => 'failed',
            'error' => [
                'message' => 'Connection timeout',
                'code' => 'timeout',
            ],
        ]);

        $this->assertTrue($message->hasError());
        $this->assertEquals('Connection timeout', $message->getErrorMessage());
        $this->assertEquals(['message' => 'Connection timeout', 'code' => 'timeout'], $message->getError());
    }

    public function testMessageWithMdn(): void
    {
        $message = new Message([
            'id' => 'msg_123',
            'mdn' => [
                'status' => 'processed',
                'receivedAt' => '2024-01-01T12:02:00Z',
            ],
        ]);

        $this->assertTrue($message->hasMdn());
        $this->assertEquals(['status' => 'processed', 'receivedAt' => '2024-01-01T12:02:00Z'], $message->getMdn());
    }

    public function testMessageMetadata(): void
    {
        $message = new Message([
            'id' => 'msg_123',
            'metadata' => [
                'orderId' => 'PO-2024-001',
                'department' => 'procurement',
            ],
        ]);

        $metadata = $message->getMetadata();
        $this->assertEquals('PO-2024-001', $metadata['orderId']);
        $this->assertEquals('procurement', $metadata['department']);
    }
}
