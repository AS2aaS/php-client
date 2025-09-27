<?php

declare(strict_types=1);

namespace AS2aaS\Models;

/**
 * Account model
 */
class Account extends BaseModel
{
    protected array $fillable = [
        'id',
        'name',
        'slug',
        'plan_type',
        'status',
        'tenants_count',
        'created_at',
        'updated_at',
    ];

    protected array $casts = [
        'tenants_count' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getId(): string
    {
        return (string) $this->attributes['id'];
    }

    public function getName(): string
    {
        return $this->attributes['name'];
    }

    public function getPlan(): string
    {
        return $this->attributes['plan_type'] ?? 'free';
    }

    public function getTenantCount(): int
    {
        return $this->attributes['tenants_count'] ?? 0;
    }

    public function getStatus(): string
    {
        return $this->attributes['status'] ?? 'active';
    }

    public function getSlug(): string
    {
        return $this->attributes['slug'] ?? '';
    }
}
