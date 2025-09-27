<?php

declare(strict_types=1);

namespace AS2aaS\Tests\Models;

use AS2aaS\Models\Certificate;
use PHPUnit\Framework\TestCase;
use DateTime;

class CertificateTest extends TestCase
{
    public function testCertificateCreation(): void
    {
        $data = [
            'id' => 'cert_123',
            'name' => 'Test Certificate',
            'type' => 'identity',
            'usage' => 'both',
            'subject' => 'CN=Test Company',
            'issuer' => 'CN=Test CA',
            'active' => true,
            'expiresAt' => '2025-12-31T23:59:59Z',
            'fingerprint' => 'abc123def456',
            'created_at' => '2024-01-01T12:00:00Z',
        ];

        $certificate = new Certificate($data);

        $this->assertEquals('cert_123', $certificate->getId());
        $this->assertEquals('Test Certificate', $certificate->getName());
        $this->assertEquals('identity', $certificate->getType());
        $this->assertEquals('both', $certificate->getUsage());
        $this->assertEquals('CN=Test Company', $certificate->getSubject());
        $this->assertEquals('CN=Test CA', $certificate->getIssuer());
        $this->assertTrue($certificate->isActive());
        $this->assertEquals('abc123def456', $certificate->getFingerprint());
    }

    public function testCertificateExpiry(): void
    {
        // Expired certificate
        $expiredCert = new Certificate([
            'id' => 'cert_expired',
            'name' => 'Expired Certificate',
            'expiresAt' => '2020-01-01T00:00:00Z'
        ]);

        $this->assertTrue($expiredCert->isExpired());
        $this->assertTrue($expiredCert->isExpiringSoon(30));

        // Future certificate
        $futureCert = new Certificate([
            'id' => 'cert_future',
            'name' => 'Future Certificate',
            'expiresAt' => '2030-01-01T00:00:00Z'
        ]);

        $this->assertFalse($futureCert->isExpired());
        $this->assertFalse($futureCert->isExpiringSoon(30));

        // Soon expiring certificate
        $soonExpiring = new Certificate([
            'id' => 'cert_soon',
            'name' => 'Soon Expiring Certificate',
            'expiresAt' => (new DateTime('+15 days'))->format('c')
        ]);

        $this->assertFalse($soonExpiring->isExpired());
        $this->assertTrue($soonExpiring->isExpiringSoon(30));
        $this->assertFalse($soonExpiring->isExpiringSoon(10));
    }

    public function testCertificateDefaults(): void
    {
        $certificate = new Certificate([
            'id' => 'cert_123',
            'name' => 'Test Certificate'
        ]);

        $this->assertEquals('identity', $certificate->getType());
        $this->assertEquals('both', $certificate->getUsage());
        $this->assertTrue($certificate->isActive());
        $this->assertNull($certificate->getPartnerId());
    }
}
