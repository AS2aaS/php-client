<?php

declare(strict_types=1);

namespace AS2aaS\Testing;

use AS2aaS\Models\Partner;

/**
 * Mock Master Partners module
 */
class MockMasterPartners
{
    private MockData $mockData;

    public function __construct(MockData $mockData)
    {
        $this->mockData = $mockData;
    }

    public function list(): array
    {
        return array_values($this->mockData->masterPartners);
    }

    public function create(array $partnerData): Partner
    {
        $partnerId = $this->mockData->getNextId('masterPartners', 'prt_master');
        
        $partnerData['id'] = $partnerId;
        $partnerData['type'] = 'master';
        $partnerData['active'] = true;
        $partnerData['health_status'] = 'excellent';
        $partnerData['health_score'] = 100;
        $partnerData['usage_count'] = 0;
        $partnerData['created_at'] = date('c');

        $partner = new Partner($partnerData);
        $this->mockData->masterPartners[$partnerId] = $partner;

        return $partner;
    }

    public function get(string $partnerId): Partner
    {
        if (!isset($this->mockData->masterPartners[$partnerId])) {
            throw new \Exception("Master partner with ID '{$partnerId}' not found");
        }

        return $this->mockData->masterPartners[$partnerId];
    }

    public function update(string $partnerId, array $data): Partner
    {
        $partner = $this->get($partnerId);
        $partner->fill($data);
        $partner->setAttribute('updated_at', date('c'));

        return $partner;
    }

    public function delete(string $partnerId): bool
    {
        if (!isset($this->mockData->masterPartners[$partnerId])) {
            throw new \Exception("Master partner with ID '{$partnerId}' not found");
        }

        unset($this->mockData->masterPartners[$partnerId]);
        
        // Remove inheritance relationships
        foreach ($this->mockData->inheritance as $key => $inheritance) {
            if ($inheritance['master_partner_id'] === $partnerId) {
                unset($this->mockData->inheritance[$key]);
            }
        }

        return true;
    }

    public function inherit(string $masterPartnerId, array $inheritanceData): array
    {
        $masterPartner = $this->get($masterPartnerId);
        $tenantIds = $inheritanceData['tenant_ids'] ?? [];
        $overrideSettings = $inheritanceData['override_settings'] ?? [];

        $results = [];

        foreach ($tenantIds as $tenantId) {
            // Create inheritance record
            $inheritanceId = uniqid('inh_');
            $this->mockData->inheritance[$inheritanceId] = [
                'id' => $inheritanceId,
                'master_partner_id' => $masterPartnerId,
                'tenant_id' => $tenantId,
                'override_settings' => $overrideSettings,
                'created_at' => date('c')
            ];

            // Create inherited partner in tenant scope
            $inheritedPartnerId = 'prt_inherited_' . uniqid();
            $inheritedData = [
                'id' => $inheritedPartnerId,
                'name' => $masterPartner->getName(),
                'as2_id' => $masterPartner->getAs2Id(),
                'url' => $overrideSettings['url'] ?? $masterPartner->getUrl(),
                'type' => 'inherited',
                'tenant_id' => $tenantId,
                'master_partner_id' => $masterPartnerId,
                'mdn_mode' => $overrideSettings['mdn_mode'] ?? $masterPartner->getMdnMode(),
                'sign' => $masterPartner->isSigningEnabled(),
                'encrypt' => $masterPartner->isEncryptionEnabled(),
                'compress' => $masterPartner->isCompressionEnabled(),
                'active' => true,
                'created_at' => date('c')
            ];

            $inheritedPartner = new Partner($inheritedData);
            $this->mockData->partners[$inheritedPartnerId] = $inheritedPartner;

            $results[] = [
                'tenant_id' => $tenantId,
                'inherited_partner_id' => $inheritedPartnerId,
                'inheritance_id' => $inheritanceId
            ];
        }

        return [
            'success' => true,
            'inherited_count' => count($results),
            'results' => $results
        ];
    }

    public function removeInheritance(string $masterPartnerId, array $tenantIds): bool
    {
        foreach ($tenantIds as $tenantId) {
            // Remove inheritance records
            foreach ($this->mockData->inheritance as $key => $inheritance) {
                if ($inheritance['master_partner_id'] === $masterPartnerId && 
                    $inheritance['tenant_id'] === $tenantId) {
                    unset($this->mockData->inheritance[$key]);
                }
            }

            // Remove inherited partners
            foreach ($this->mockData->partners as $key => $partner) {
                if ($partner->getType() === 'inherited' && 
                    $partner->getAttribute('master_partner_id') === $masterPartnerId &&
                    $partner->getAttribute('tenant_id') === $tenantId) {
                    unset($this->mockData->partners[$key]);
                }
            }
        }

        return true;
    }

    public function getInheritanceStatus(string $masterPartnerId): array
    {
        $masterPartner = $this->get($masterPartnerId);
        $inheritances = array_filter($this->mockData->inheritance, 
            fn($i) => $i['master_partner_id'] === $masterPartnerId);

        return [
            'master_partner' => $masterPartner->toArray(),
            'inherited_by_tenants' => array_values($inheritances),
            'inheritance_stats' => [
                'total_inherited' => count($inheritances),
                'active_inherited' => count($inheritances)
            ]
        ];
    }

    public function getHealth(): array
    {
        return [
            'total_partners' => count($this->mockData->masterPartners),
            'healthy_partners' => count($this->mockData->masterPartners),
            'average_health_score' => 100,
            'last_check' => date('c')
        ];
    }
}
