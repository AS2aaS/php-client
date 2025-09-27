<?php

declare(strict_types=1);

namespace AS2aaS\Tests\Modules;

use AS2aaS\Http\HttpClient;
use AS2aaS\Modules\Partners;
use AS2aaS\Models\Partner;
use AS2aaS\Exceptions\AS2PartnerError;
use PHPUnit\Framework\TestCase;
use Mockery;

class PartnersTest extends TestCase
{
    private Partners $partners;
    private $mockHttpClient;

    protected function setUp(): void
    {
        $this->mockHttpClient = Mockery::mock(HttpClient::class);
        $this->partners = new Partners($this->mockHttpClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testListPartners(): void
    {
        $expectedResponse = [
            'data' => [
                [
                    'id' => 'prt_123',
                    'name' => 'Test Partner',
                    'as2_id' => 'TEST-PARTNER',
                    'url' => 'https://test.example.com/as2'
                ]
            ]
        ];

        $this->mockHttpClient
            ->shouldReceive('get')
            ->once()
            ->with('partners', [])
            ->andReturn($expectedResponse);

        $partners = $this->partners->list();

        $this->assertCount(1, $partners);
        $this->assertInstanceOf(Partner::class, $partners[0]);
        $this->assertEquals('Test Partner', $partners[0]->getName());
    }

    public function testGetByAs2Id(): void
    {
        $expectedResponse = [
            'data' => [
                [
                    'id' => 'prt_123',
                    'name' => 'McKesson',
                    'as2_id' => 'MCKESSON',
                    'url' => 'https://mckesson.example.com/as2'
                ]
            ]
        ];

        $this->mockHttpClient
            ->shouldReceive('get')
            ->once()
            ->with('partners', ['search' => 'MCKESSON'])
            ->andReturn($expectedResponse);

        $partner = $this->partners->getByAs2Id('MCKESSON');

        $this->assertInstanceOf(Partner::class, $partner);
        $this->assertEquals('McKesson', $partner->getName());
        $this->assertEquals('MCKESSON', $partner->getAs2Id());
    }

    public function testGetByAs2IdNotFound(): void
    {
        $this->mockHttpClient
            ->shouldReceive('get')
            ->once()
            ->with('partners', ['search' => 'NONEXISTENT'])
            ->andReturn(['data' => []]);

        $this->expectException(AS2PartnerError::class);
        $this->expectExceptionMessage("Partner with AS2 ID 'NONEXISTENT' not found");

        $this->partners->getByAs2Id('NONEXISTENT');
    }

    public function testCreatePartner(): void
    {
        $partnerData = [
            'name' => 'New Partner',
            'as2Id' => 'NEW-PARTNER',
            'url' => 'https://new.example.com/as2'
        ];

        $expectedResponse = [
            'id' => 'prt_new',
            'name' => 'New Partner',
            'as2_id' => 'NEW-PARTNER',
            'url' => 'https://new.example.com/as2',
            'active' => true
        ];

        $this->mockHttpClient
            ->shouldReceive('post')
            ->once()
            ->with('partners', Mockery::type('array'))
            ->andReturn($expectedResponse);

        $partner = $this->partners->create($partnerData);

        $this->assertInstanceOf(Partner::class, $partner);
        $this->assertEquals('New Partner', $partner->getName());
    }

    public function testCreatePartnerMissingRequiredField(): void
    {
        $this->expectException(AS2PartnerError::class);
        $this->expectExceptionMessage("Field 'name' is required");

        $this->partners->create([
            'as2Id' => 'TEST',
            'url' => 'https://test.com'
        ]);
    }
}
