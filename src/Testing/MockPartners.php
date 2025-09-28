<?php

declare(strict_types=1);

namespace AS2aaS\Testing;

use AS2aaS\Exceptions\AS2PartnerError;
use AS2aaS\Models\Partner;

/**
 * Mock Partners module
 */
class MockPartners
{
    private MockData $mockData;
    private ?string $tenantId;

    public function __construct(MockData $mockData, ?string $tenantId = null)
    {
        $this->mockData = $mockData;
        $this->tenantId = $tenantId;
    }

    public function list(array $options = []): array
    {
        $partners = array_values($this->mockData->partners);

        // Filter by tenant context if set
        if ($this->tenantId) {
            $partners = array_filter($partners, function($partner) {
                return $partner->getAttribute('tenant_id') === $this->tenantId || 
                       $partner->getType() === 'inherited';
            });
        }

        // Apply filters
        if (isset($options['type'])) {
            $partners = array_filter($partners, fn($p) => $p->getType() === $options['type']);
        }

        if (isset($options['active'])) {
            $partners = array_filter($partners, fn($p) => $p->isActive() === $options['active']);
        }

        if (isset($options['search'])) {
            $search = strtolower($options['search']);
            $partners = array_filter($partners, function($p) use ($search) {
                return str_contains(strtolower($p->getName()), $search) ||
                       str_contains(strtolower($p->getAs2Id()), $search);
            });
        }

        return array_values($partners);
    }

    public function getByAs2Id(string $as2Id): Partner
    {
        foreach ($this->mockData->partners as $partner) {
            if ($partner->getAs2Id() === $as2Id) {
                return $partner;
            }
        }

        throw new AS2PartnerError("Partner with AS2 ID '{$as2Id}' not found", 'partner_not_found');
    }

    public function getByName(string $name): Partner
    {
        foreach ($this->mockData->partners as $partner) {
            if (strcasecmp($partner->getName(), $name) === 0) {
                return $partner;
            }
        }

        // Try partial match
        foreach ($this->mockData->partners as $partner) {
            if (stripos($partner->getName(), $name) !== false) {
                return $partner;
            }
        }

        throw new AS2PartnerError("Partner with name '{$name}' not found", 'partner_not_found');
    }

    public function get(string $partnerId): Partner
    {
        if (!isset($this->mockData->partners[$partnerId])) {
            throw new AS2PartnerError("Partner with ID '{$partnerId}' not found", 'partner_not_found');
        }

        return $this->mockData->partners[$partnerId];
    }

    public function create(array $partnerData): Partner
    {
        $partnerId = $this->mockData->getNextId('partners', 'prt');
        
        $partnerData['id'] = $partnerId;
        $partnerData['tenant_id'] = $this->tenantId;
        $partnerData['type'] = 'tenant';
        $partnerData['active'] = $partnerData['active'] ?? true;
        $partnerData['created_at'] = date('c');

        $partner = new Partner($partnerData);
        $this->mockData->partners[$partnerId] = $partner;

        return $partner;
    }

    public function update(string $partnerId, array $partnerData): Partner
    {
        if (!isset($this->mockData->partners[$partnerId])) {
            throw new AS2PartnerError("Partner with ID '{$partnerId}' not found", 'partner_not_found');
        }

        $partner = $this->mockData->partners[$partnerId];
        $partner->fill($partnerData);
        $partner->setAttribute('updated_at', date('c'));

        return $partner;
    }

    public function delete(string $partnerId): bool
    {
        if (!isset($this->mockData->partners[$partnerId])) {
            throw new AS2PartnerError("Partner with ID '{$partnerId}' not found", 'partner_not_found');
        }

        unset($this->mockData->partners[$partnerId]);
        return true;
    }

    public function test($partner, array $options = []): array
    {
        return [
            'success' => true,
            'message' => 'Mock test successful',
            'error' => null,
            'details' => [],
            'duration' => 100
        ];
    }
}
