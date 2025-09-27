<?php

declare(strict_types=1);

namespace AS2aaS\Tests;

use AS2aaS\Client;
use AS2aaS\Exceptions\AS2AuthenticationError;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testClientInitializationWithApiKey(): void
    {
        $client = new Client('pk_test_example_key');
        $config = $client->getConfig();
        
        $this->assertEquals('pk_test_example_key', $config['apiKey']);
        $this->assertEquals('test', $config['environment']);
    }

    public function testClientInitializationWithConfig(): void
    {
        $client = new Client([
            'apiKey' => 'pk_live_example_key',
            'timeout' => 60000,
        ]);
        
        $config = $client->getConfig();
        $this->assertEquals('pk_live_example_key', $config['apiKey']);
        $this->assertEquals('live', $config['environment']);
        $this->assertEquals(60000, $config['timeout']);
    }

    public function testClientInitializationFromEnvironment(): void
    {
        $_ENV['AS2AAS_API_KEY'] = 'pk_test_env_key';
        
        $client = new Client();
        $config = $client->getConfig();
        
        $this->assertEquals('pk_test_env_key', $config['apiKey']);
        
        unset($_ENV['AS2AAS_API_KEY']);
    }

    public function testInvalidApiKeyThrowsException(): void
    {
        $this->expectException(AS2AuthenticationError::class);
        new Client('invalid_key_format');
    }

    public function testMissingApiKeyThrowsException(): void
    {
        // Ensure no environment variable is set
        unset($_ENV['AS2AAS_API_KEY']);
        
        // Also check getenv()
        if (getenv('AS2AAS_API_KEY')) {
            putenv('AS2AAS_API_KEY=');
        }
        
        $this->expectException(AS2AuthenticationError::class);
        new Client();
    }

    public function testCreateTestClient(): void
    {
        $client = Client::createTest('pk_test_example');
        $config = $client->getConfig();
        
        $this->assertEquals('test', $config['environment']);
    }


    public function testModuleAccess(): void
    {
        $client = new Client('pk_test_example_key');
        
        $this->assertInstanceOf(\AS2aaS\Modules\Partners::class, $client->partners());
        $this->assertInstanceOf(\AS2aaS\Modules\Messages::class, $client->messages());
        $this->assertInstanceOf(\AS2aaS\Modules\Certificates::class, $client->certificates());
        $this->assertInstanceOf(\AS2aaS\Modules\Accounts::class, $client->accounts());
        $this->assertInstanceOf(\AS2aaS\Modules\Tenants::class, $client->tenants());
        $this->assertInstanceOf(\AS2aaS\Modules\Webhooks::class, $client->webhooks());
        $this->assertInstanceOf(\AS2aaS\Modules\Billing::class, $client->billing());
        $this->assertInstanceOf(\AS2aaS\Modules\Sandbox::class, $client->sandbox());
        $this->assertInstanceOf(\AS2aaS\Modules\Partnerships::class, $client->partnerships());
        $this->assertInstanceOf(\AS2aaS\Modules\Utils::class, $client->utils());
    }

    public function testGlobalConfiguration(): void
    {
        Client::configure([
            'timeout' => 45000,
            'retries' => 5,
        ]);
        
        $client = new Client('pk_test_example_key');
        $config = $client->getConfig();
        
        $this->assertEquals(45000, $config['timeout']);
        $this->assertEquals(5, $config['retries']);
    }
}
