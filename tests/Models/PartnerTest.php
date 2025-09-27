<?php

declare(strict_types=1);

namespace AS2aaS\Tests\Models;

use AS2aaS\Models\Partner;
use PHPUnit\Framework\TestCase;
use DateTime;

class PartnerTest extends TestCase
{
    public function testPartnerCreation(): void
    {
        $data = [
            'id' => 'prt_123',
            'name' => 'Test Partner',
            'as2_id' => 'TEST-PARTNER',
            'url' => 'https://test.example.com/as2',
            'active' => true,
            'type' => 'tenant',
            'sign' => true,
            'encrypt' => true,
            'compress' => false,
            'mdn_mode' => 'async',
        ];

        $partner = new Partner($data);

        $this->assertEquals('prt_123', $partner->getId());
        $this->assertEquals('Test Partner', $partner->getName());
        $this->assertEquals('TEST-PARTNER', $partner->getAs2Id());
        $this->assertEquals('https://test.example.com/as2', $partner->getUrl());
        $this->assertTrue($partner->isActive());
        $this->assertEquals('tenant', $partner->getType());
        $this->assertFalse($partner->isMasterPartner());
        $this->assertTrue($partner->isSigningEnabled());
        $this->assertTrue($partner->isEncryptionEnabled());
        $this->assertFalse($partner->isCompressionEnabled());
        $this->assertEquals('async', $partner->getMdnMode());
    }

    public function testPartnerDefaults(): void
    {
        $partner = new Partner([
            'id' => 'prt_123',
            'name' => 'Test Partner',
            'as2_id' => 'TEST-PARTNER',
            'url' => 'https://test.example.com/as2',
        ]);

        $this->assertTrue($partner->isActive());
        $this->assertEquals('tenant', $partner->getType());
        $this->assertTrue($partner->isSigningEnabled());
        $this->assertTrue($partner->isEncryptionEnabled());
        $this->assertFalse($partner->isCompressionEnabled());
        $this->assertEquals('async', $partner->getMdnMode());
    }

    public function testMasterPartner(): void
    {
        $partner = new Partner([
            'id' => 'prt_123',
            'name' => 'Master Partner',
            'as2_id' => 'MASTER-PARTNER',
            'url' => 'https://master.example.com/as2',
            'type' => 'master',
        ]);

        $this->assertTrue($partner->isMasterPartner());
        $this->assertFalse($partner->canOverrideSettings());
    }

    public function testInheritedPartner(): void
    {
        $partner = new Partner([
            'id' => 'prt_123',
            'name' => 'Inherited Partner',
            'as2_id' => 'INHERITED-PARTNER',
            'url' => 'https://inherited.example.com/as2',
            'type' => 'inherited',
        ]);

        $this->assertFalse($partner->isMasterPartner());
        $this->assertTrue($partner->canOverrideSettings());
    }

    public function testEffectiveConfig(): void
    {
        $partner = new Partner([
            'id' => 'prt_123',
            'name' => 'Test Partner',
            'as2_id' => 'TEST-PARTNER',
            'url' => 'https://test.example.com/as2',
            'sign' => false,
            'encrypt' => true,
            'compress' => true,
            'mdn_mode' => 'sync',
        ]);

        $config = $partner->getEffectiveConfig();

        $this->assertEquals('prt_123', $config['id']);
        $this->assertEquals('Test Partner', $config['name']);
        $this->assertEquals('TEST-PARTNER', $config['as2Id']);
        $this->assertFalse($config['security']['sign']);
        $this->assertTrue($config['security']['encrypt']);
        $this->assertTrue($config['security']['compress']);
        $this->assertEquals('sync', $config['mdnMode']);
    }

    public function testJsonSerialization(): void
    {
        $partner = new Partner([
            'id' => 'prt_123',
            'name' => 'Test Partner',
            'as2_id' => 'TEST-PARTNER',
            'url' => 'https://test.example.com/as2',
        ]);

        $json = json_encode($partner);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals('prt_123', $decoded['id']);
        $this->assertEquals('Test Partner', $decoded['name']);
    }
}
