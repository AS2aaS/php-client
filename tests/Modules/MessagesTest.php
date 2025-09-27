<?php

declare(strict_types=1);

namespace AS2aaS\Tests\Modules;

use AS2aaS\Http\HttpClient;
use AS2aaS\Modules\Messages;
use AS2aaS\Models\Message;
use AS2aaS\Models\Partner;
use PHPUnit\Framework\TestCase;
use Mockery;

class MessagesTest extends TestCase
{
    private Messages $messages;
    private $mockHttpClient;

    protected function setUp(): void
    {
        $this->mockHttpClient = Mockery::mock(HttpClient::class);
        $this->messages = new Messages($this->mockHttpClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSendMessage(): void
    {
        $partner = new Partner([
            'id' => 'prt_123',
            'name' => 'Test Partner',
            'as2_id' => 'TEST-PARTNER'
        ]);

        $expectedResponse = [
            'id' => 'msg_123',
            'message_id' => 'AS2-MSG-123',
            'status' => 'queued',
            'partner_id' => 'prt_123'
        ];

        $this->mockHttpClient
            ->shouldReceive('requestWithOptions')
            ->once()
            ->with('POST', 'messages', Mockery::type('array'))
            ->andReturn($expectedResponse);

        $message = $this->messages->send($partner, 'test content', 'Test Subject');

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('msg_123', $message->getId());
        $this->assertEquals('AS2-MSG-123', $message->getMessageId());
        $this->assertEquals('queued', $message->getStatus());
    }

    public function testDetectContentType(): void
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->messages);
        $method = $reflection->getMethod('detectContentType');
        $method->setAccessible(true);

        // Test EDI X12
        $ediContent = 'ISA*00*          *00*          *ZZ*SENDER*ZZ*RECEIVER*240101*1200*U*00401*000000001*0*T*>~';
        $contentType = $method->invoke($this->messages, $ediContent);
        $this->assertEquals('application/edi-x12', $contentType);

        // Test XML
        $xmlContent = '<?xml version="1.0"?><root><element>value</element></root>';
        $contentType = $method->invoke($this->messages, $xmlContent);
        $this->assertEquals('application/xml', $contentType);

        // Test JSON
        $jsonContent = '{"key": "value"}';
        $contentType = $method->invoke($this->messages, $jsonContent);
        $this->assertEquals('application/json', $contentType);
    }

    public function testDetectContentTypeFromFilename(): void
    {
        $reflection = new \ReflectionClass($this->messages);
        $method = $reflection->getMethod('detectContentTypeFromFilename');
        $method->setAccessible(true);

        $this->assertEquals('application/edi-x12', $method->invoke($this->messages, 'test.edi'));
        $this->assertEquals('application/xml', $method->invoke($this->messages, 'test.xml'));
        $this->assertEquals('application/json', $method->invoke($this->messages, 'test.json'));
        $this->assertEquals('application/pdf', $method->invoke($this->messages, 'test.pdf'));
        $this->assertEquals('application/octet-stream', $method->invoke($this->messages, 'test.unknown'));
    }
}
