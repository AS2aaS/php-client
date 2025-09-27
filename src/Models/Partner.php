<?php

declare(strict_types=1);

namespace AS2aaS\Models;

use DateTime;

/**
 * Partner model
 */
class Partner extends BaseModel
{
    protected array $fillable = [
        'id',
        'name',
        'as2_id',
        'url',
        'type',
        'mdn_mode',
        'sign',
        'encrypt',
        'compress',
        'active',
        'health_status',
        'health_score',
        'usage_count',
        'configuration',
        'created_at',
        'updated_at',
    ];

    protected array $casts = [
        'active' => 'boolean',
        'sign' => 'boolean',
        'encrypt' => 'boolean', 
        'compress' => 'boolean',
        'health_score' => 'int',
        'usage_count' => 'int',
        'configuration' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get partner ID
     */
    public function getId(): string
    {
        return (string) $this->attributes['id'];
    }

    /**
     * Get partner name
     */
    public function getName(): string
    {
        return $this->attributes['name'];
    }

    /**
     * Get AS2 ID
     */
    public function getAs2Id(): string
    {
        return $this->attributes['as2_id'];
    }

    /**
     * Get partner URL
     */
    public function getUrl(): string
    {
        return $this->attributes['url'];
    }

    /**
     * Check if partner is active
     */
    public function isActive(): bool
    {
        return $this->attributes['active'] ?? true;
    }

    /**
     * Get partner type
     */
    public function getType(): string
    {
        return $this->attributes['type'] ?? 'tenant';
    }

    /**
     * Check if this is a master partner
     */
    public function isMasterPartner(): bool
    {
        return $this->getType() === 'master';
    }

    /**
     * Check if settings can be overridden
     */
    public function canOverrideSettings(): bool
    {
        return $this->getType() === 'inherited';
    }

    /**
     * Get security configuration
     */
    public function getSecurity(): array
    {
        return [
            'sign' => $this->isSigningEnabled(),
            'encrypt' => $this->isEncryptionEnabled(),
            'compress' => $this->isCompressionEnabled(),
        ];
    }

    /**
     * Check if signing is enabled
     */
    public function isSigningEnabled(): bool
    {
        // Check configuration first (master partners), then direct field (tenant partners)
        if (isset($this->attributes['configuration']['sign'])) {
            return (bool) $this->attributes['configuration']['sign'];
        }
        return $this->attributes['sign'] ?? true;
    }

    /**
     * Check if encryption is enabled
     */
    public function isEncryptionEnabled(): bool
    {
        // Check configuration first (master partners), then direct field (tenant partners)
        if (isset($this->attributes['configuration']['encrypt'])) {
            return (bool) $this->attributes['configuration']['encrypt'];
        }
        return $this->attributes['encrypt'] ?? true;
    }

    /**
     * Check if compression is enabled
     */
    public function isCompressionEnabled(): bool
    {
        // Check configuration first (master partners), then direct field (tenant partners)
        if (isset($this->attributes['configuration']['compress'])) {
            return (bool) $this->attributes['configuration']['compress'];
        }
        return $this->attributes['compress'] ?? false;
    }

    /**
     * Get MDN mode
     */
    public function getMdnMode(): string
    {
        // Check configuration first (master partners), then direct field (tenant partners)
        if (isset($this->attributes['configuration']['mdn_mode'])) {
            return $this->attributes['configuration']['mdn_mode'];
        }
        return $this->attributes['mdn_mode'] ?? 'async';
    }

    /**
     * Get effective configuration (merged with defaults)
     */
    public function getEffectiveConfig(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'as2Id' => $this->getAs2Id(),
            'url' => $this->getUrl(),
            'active' => $this->isActive(),
            'type' => $this->getType(),
            'security' => $this->getSecurity(),
            'mdnMode' => $this->getMdnMode(),
        ];
    }

    /**
     * Get created at timestamp
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->attributes['created_at'] ?? null;
    }

    /**
     * Get updated at timestamp
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->attributes['updated_at'] ?? null;
    }

    /**
     * Get health status
     */
    public function getHealthStatus(): ?string
    {
        return $this->attributes['health_status'] ?? null;
    }

    /**
     * Get health score
     */
    public function getHealthScore(): ?int
    {
        return $this->attributes['health_score'] ?? null;
    }

    /**
     * Get usage count
     */
    public function getUsageCount(): int
    {
        return $this->attributes['usage_count'] ?? 0;
    }

    /**
     * Get configuration array (for master partners)
     */
    public function getConfiguration(): ?array
    {
        return $this->attributes['configuration'] ?? null;
    }
}
