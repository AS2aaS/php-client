<?php

declare(strict_types=1);

namespace AS2aaS\Modules;

/**
 * Partnerships module - Advanced partner onboarding and relationship management
 */
class Partnerships extends BaseModule
{
    public function initiateOnboarding(array $partnerData): array
    {
        return $this->httpClient->post('partnerships/onboarding', $partnerData);
    }

    public function getHealthDashboard(): array
    {
        return $this->httpClient->get('partnerships/health');
    }

    public function initiateCertificateExchange(string $partnerId): array
    {
        return $this->httpClient->post("partnerships/{$partnerId}/certificate-exchange");
    }
}
