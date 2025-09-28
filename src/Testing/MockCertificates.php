<?php

declare(strict_types=1);

namespace AS2aaS\Testing;

use AS2aaS\Models\Certificate;

/**
 * Mock Certificates module
 */
class MockCertificates
{
    private MockData $mockData;
    private ?string $tenantId;

    public function __construct(MockData $mockData, ?string $tenantId = null)
    {
        $this->mockData = $mockData;
        $this->tenantId = $tenantId;
    }

    public function upload(array $certificateData): Certificate
    {
        $certId = $this->mockData->getNextId('certificates', 'cert');
        
        $certificateData['id'] = $certId;
        $certificateData['active'] = true;
        $certificateData['type'] = $certificateData['type'] ?? 'identity';
        $certificateData['usage'] = $certificateData['usage'] ?? 'both';
        $certificateData['subject'] = 'CN=' . ($certificateData['name'] ?? 'Mock Certificate');
        $certificateData['issuer'] = 'CN=Mock CA';
        $certificateData['expiresAt'] = date('c', strtotime('+1 year'));
        $certificateData['fingerprint'] = md5(uniqid());
        $certificateData['created_at'] = date('c');

        $certificate = new Certificate($certificateData);
        $this->mockData->certificates[$certId] = $certificate;

        return $certificate;
    }

    public function list(array $options = []): array
    {
        $certificates = array_values($this->mockData->certificates);

        // Apply filters
        if (isset($options['type'])) {
            $certificates = array_filter($certificates, fn($c) => $c->getType() === $options['type']);
        }

        if (isset($options['active'])) {
            $certificates = array_filter($certificates, fn($c) => $c->isActive() === $options['active']);
        }

        if (isset($options['expiringWithin'])) {
            $certificates = array_filter($certificates, fn($c) => $c->isExpiringSoon($options['expiringWithin']));
        }

        return $certificates;
    }

    public function get(string $certificateId): Certificate
    {
        if (!isset($this->mockData->certificates[$certificateId])) {
            throw new \Exception("Certificate with ID '{$certificateId}' not found");
        }

        return $this->mockData->certificates[$certificateId];
    }

    public function delete(string $certificateId): bool
    {
        if (!isset($this->mockData->certificates[$certificateId])) {
            throw new \Exception("Certificate with ID '{$certificateId}' not found");
        }

        unset($this->mockData->certificates[$certificateId]);
        return true;
    }

    public function validate(string $certificateId): array
    {
        $certificate = $this->get($certificateId);
        
        return [
            'valid' => !$certificate->isExpired(),
            'issues' => $certificate->isExpired() ? ['Certificate expired'] : [],
            'details' => [
                'expires_at' => $certificate->getExpiresAt()?->format('c'),
                'days_until_expiry' => $certificate->getDaysUntilExpiry()
            ]
        ];
    }

    public function generateIdentity(array $certificateData): Certificate
    {
        $certificateData['type'] = 'identity';
        $certificateData['usage'] = 'both';
        
        return $this->upload($certificateData);
    }
}
