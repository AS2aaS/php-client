<?php

declare(strict_types=1);

namespace AS2aaS\Models;

use DateTime;

/**
 * Message model
 */
class Message extends BaseModel
{
    protected array $fillable = [
        'id',
        'message_id',
        'partner_id',
        'status',
        'direction',
        'subject',
        'content_type',
        'bytes',
        'mdn_mode',
        'created_at',
        'sent_at',
        'delivered_at',
        'mdn',
        'error',
        'metadata',
    ];

    protected array $casts = [
        'bytes' => 'int',
        'created_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'mdn' => 'array',
        'error' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get message ID
     */
    public function getId(): string
    {
        return $this->attributes['id'];
    }

    /**
     * Get AS2 message ID
     */
    public function getMessageId(): string
    {
        return $this->attributes['message_id'] ?? '';
    }

    /**
     * Get partner information
     */
    public function getPartner(): ?Partner
    {
        $partnerData = $this->attributes['partner'] ?? null;
        return $partnerData ? new Partner($partnerData) : null;
    }

    /**
     * Get message status
     */
    public function getStatus(): string
    {
        return $this->attributes['status'] ?? 'unknown';
    }

    /**
     * Get message direction
     */
    public function getDirection(): string
    {
        return $this->attributes['direction'] ?? 'outbound';
    }

    /**
     * Get message subject
     */
    public function getSubject(): ?string
    {
        return $this->attributes['subject'] ?? null;
    }

    /**
     * Get content type
     */
    public function getContentType(): string
    {
        return $this->attributes['content_type'] ?? 'application/octet-stream';
    }

    /**
     * Get message size in bytes
     */
    public function getSize(): int
    {
        return $this->attributes['bytes'] ?? 0;
    }

    /**
     * Check if message is delivered
     */
    public function isDelivered(): bool
    {
        return $this->getStatus() === 'delivered';
    }

    /**
     * Check if message failed
     */
    public function isFailed(): bool
    {
        return $this->getStatus() === 'failed';
    }

    /**
     * Check if message is pending
     */
    public function isPending(): bool
    {
        return in_array($this->getStatus(), ['queued', 'processing', 'sent']);
    }

    /**
     * Get status description
     */
    public function getStatusDescription(): string
    {
        return match ($this->getStatus()) {
            'queued' => 'Message is queued for processing',
            'processing' => 'Message is being processed',
            'sent' => 'Message has been sent to partner',
            'delivered' => 'Message delivery confirmed by partner',
            'failed' => 'Message delivery failed',
            'received' => 'Message received from partner',
            default => 'Unknown status',
        };
    }

    /**
     * Get created at timestamp
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->attributes['created_at'] ?? null;
    }

    /**
     * Get sent at timestamp
     */
    public function getSentAt(): ?DateTime
    {
        return $this->attributes['sent_at'] ?? null;
    }

    /**
     * Get delivered at timestamp
     */
    public function getDeliveredAt(): ?DateTime
    {
        return $this->attributes['delivered_at'] ?? null;
    }

    /**
     * Get MDN data
     */
    public function getMdn(): ?array
    {
        return $this->attributes['mdn'] ?? null;
    }

    /**
     * Get error data
     */
    public function getError(): ?array
    {
        return $this->attributes['error'] ?? null;
    }

    /**
     * Get metadata
     */
    public function getMetadata(): array
    {
        return $this->attributes['metadata'] ?? [];
    }

    /**
     * Check if message has MDN
     */
    public function hasMdn(): bool
    {
        return !empty($this->getMdn());
    }

    /**
     * Check if message has error
     */
    public function hasError(): bool
    {
        return !empty($this->getError());
    }

    /**
     * Get error message
     */
    public function getErrorMessage(): ?string
    {
        $error = $this->getError();
        return $error['message'] ?? null;
    }
}
