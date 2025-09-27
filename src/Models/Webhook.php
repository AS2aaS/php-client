<?php

declare(strict_types=1);

namespace AS2aaS\Models;

/**
 * Webhook model
 */
class Webhook extends BaseModel
{
    protected array $fillable = [
        'id',
        'url',
        'events',
        'secret',
        'description',
        'active',
        'createdAt',
        'updatedAt',
    ];

    protected array $casts = [
        'events' => 'array',
        'active' => 'boolean',
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime',
    ];

    public function getId(): string
    {
        return $this->attributes['id'];
    }

    public function getUrl(): string
    {
        return $this->attributes['url'];
    }

    public function getEvents(): array
    {
        return $this->attributes['events'] ?? [];
    }

    public function getSecret(): string
    {
        return $this->attributes['secret'] ?? '';
    }

    public function getDescription(): ?string
    {
        return $this->attributes['description'] ?? null;
    }

    public function isActive(): bool
    {
        return $this->attributes['active'] ?? true;
    }
}
