<?php

declare(strict_types=1);

namespace AS2aaS\Models;

/**
 * Tenant model
 */
class Tenant extends BaseModel
{
    protected array $fillable = [
        'id',
        'account_id',
        'name',
        'slug',
        'status',
        'created_at',
        'updated_at',
    ];

    protected array $casts = [
        'account_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getId(): string
    {
        return (string) $this->attributes['id'];
    }

    public function getName(): string
    {
        return $this->attributes['name'] ?? '';
    }

    public function getSlug(): string
    {
        return $this->attributes['slug'] ?? '';
    }

    public function getAccountId(): int
    {
        return $this->attributes['account_id'] ?? 0;
    }

    public function getStatus(): string
    {
        return $this->attributes['status'] ?? 'active';
    }

    public function getMessageCount30d(): int
    {
        return $this->attributes['messageCount30d'] ?? 0;
    }

    public function isActive(): bool
    {
        return $this->getStatus() === 'active';
    }
}
