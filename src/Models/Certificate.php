<?php

declare(strict_types=1);

namespace AS2aaS\Models;

use DateTime;

/**
 * Certificate model
 */
class Certificate extends BaseModel
{
    protected array $fillable = [
        'id',
        'name',
        'type',
        'usage',
        'subject',
        'issuer',
        'active',
        'expiresAt',
        'partnerId',
        'fingerprint',
        'createdAt',
        'updatedAt',
    ];

    protected array $casts = [
        'active' => 'boolean',
        'expiresAt' => 'datetime',
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime',
    ];

    /**
     * Get certificate ID
     */
    public function getId(): string
    {
        return $this->attributes['id'];
    }

    /**
     * Get certificate name
     */
    public function getName(): string
    {
        return $this->attributes['name'];
    }

    /**
     * Get certificate type
     */
    public function getType(): string
    {
        return $this->attributes['type'] ?? 'identity';
    }

    /**
     * Get certificate usage
     */
    public function getUsage(): string
    {
        return $this->attributes['usage'] ?? 'both';
    }

    /**
     * Get certificate subject
     */
    public function getSubject(): string
    {
        return $this->attributes['subject'] ?? '';
    }

    /**
     * Get certificate issuer
     */
    public function getIssuer(): string
    {
        return $this->attributes['issuer'] ?? '';
    }

    /**
     * Check if certificate is active
     */
    public function isActive(): bool
    {
        return $this->attributes['active'] ?? true;
    }

    /**
     * Get expiration date
     */
    public function getExpiresAt(): ?DateTime
    {
        return $this->attributes['expiresAt'] ?? null;
    }

    /**
     * Get partner ID (for partner certificates)
     */
    public function getPartnerId(): ?string
    {
        return $this->attributes['partnerId'] ?? null;
    }

    /**
     * Get certificate fingerprint
     */
    public function getFingerprint(): string
    {
        return $this->attributes['fingerprint'] ?? '';
    }

    /**
     * Check if certificate is expired
     */
    public function isExpired(): bool
    {
        $expiresAt = $this->getExpiresAt();
        return $expiresAt && $expiresAt < new DateTime();
    }

    /**
     * Check if certificate is expiring soon
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        $expiresAt = $this->getExpiresAt();
        if (!$expiresAt) {
            return false;
        }

        $threshold = new DateTime();
        $threshold->modify("+{$days} days");

        return $expiresAt <= $threshold;
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiry(): int
    {
        $expiresAt = $this->getExpiresAt();
        if (!$expiresAt) {
            return -1;
        }

        $now = new DateTime();
        $diff = $now->diff($expiresAt);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Get created at timestamp
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->attributes['createdAt'] ?? null;
    }

    /**
     * Get updated at timestamp
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->attributes['updatedAt'] ?? null;
    }
}
