# AS2aaS PHP Client

A comprehensive PHP client library for AS2aaS (AS2 as a Service), designed to simplify AS2 messaging integration for business applications. This library abstracts the complexity of AS2 protocol implementation, enabling developers to integrate secure B2B messaging with just a few lines of code.

[![Latest Version](https://img.shields.io/packagist/v/as2aas/php-client.svg?style=flat-square)](https://packagist.org/packages/as2aas/php-client)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/github/workflow/status/as2aas/php-client/CI?style=flat-square)](https://github.com/as2aas/php-client/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/as2aas/php-client.svg?style=flat-square)](https://packagist.org/packages/as2aas/php-client)

## Overview

AS2aaS provides a cloud-based AS2 messaging service that handles the complex technical requirements of AS2 protocol implementation. This PHP client library offers a clean, intuitive API for integrating AS2 messaging capabilities into your business applications.

**Key Use Cases:**
- Healthcare and pharmaceutical supply chain (DSCSA compliance)
- Retail and manufacturing EDI transactions
- Financial services secure document exchange
- Any B2B integration requiring AS2 protocol compliance

## Installation

Install the package via Composer:

```bash
composer require as2aas/php-client
```

### Laravel Installation

For Laravel applications, the service provider is automatically registered via package discovery. Publish the configuration file:

```bash
php artisan vendor:publish --tag=as2aas-config
```

Add your API key to your `.env` file:

```env
AS2AAS_API_KEY=pk_live_your_api_key
AS2AAS_TIMEOUT=30000
AS2AAS_RETRIES=3
```

## Quick Start

```php
<?php

use AS2aaS\Client;

// Initialize the client (environment auto-detected from API key)
$as2 = new Client('pk_live_your_api_key'); // Production environment
// $as2 = new Client('pk_test_your_api_key'); // Test environment

// Get a trading partner
$partner = $as2->partners()->getByAs2Id('MCKESSON');

// Send a message
$message = $as2->messages()->send(
    $partner, 
    file_get_contents('purchase-order.edi'),
    'Purchase Order #PO-2024-001'
);

echo "Message sent! Status: " . $message->getStatus();
```

### Laravel Quick Start

```php
// Using Facade
use AS2aaS\Laravel\Facades\AS2;

$partner = AS2::partners()->getByAs2Id('MCKESSON');
$message = AS2::messages()->send($partner, $content, 'Purchase Order');

// Using Dependency Injection
use AS2aaS\Client;

class OrderController extends Controller
{
    public function sendOrder(Client $as2)
    {
        $partner = $as2->partners()->getByAs2Id('MCKESSON');
        $message = $as2->messages()->send($partner, $ediContent, 'Purchase Order');
        
        return response()->json(['message_id' => $message->getId()]);
    }
}
```

## Features

- **Simple API**: Send AS2 messages with minimal code complexity
- **Laravel Ready**: First-class Laravel integration with service provider and facades
- **Enterprise Architecture**: Account-based multi-tenant support
- **Master Partner Management**: Centralized partner configuration with inheritance
- **Automatic Security**: Built-in signing, encryption, and compression handling
- **Real-time Notifications**: Webhook integration for message status updates
- **Comprehensive Error Handling**: Detailed exception management with retry logic
- **Content Detection**: Automatic EDI, XML, and JSON content type recognition

## Configuration

### Environment Variables

Configure your API key using environment variables:

```bash
AS2AAS_API_KEY=pk_live_your_api_key
```

### Advanced Configuration

```php
use AS2aaS\Client;

$as2 = new Client([
    'apiKey' => 'pk_live_your_api_key',
    'timeout' => 30000,
    'retries' => 3,
    'defaultMdnMode' => 'async',
    'defaultSigning' => true,
    'defaultEncryption' => true,
]);

// API endpoint: https://api.as2aas.com/v1
// Test vs Live environment auto-detected by API from your key type
```

### Global Configuration

```php
Client::configure([
    'timeout' => 45000,
    'retries' => 5,
    'defaultMdnMode' => 'sync',
]);
```

## Core Usage Examples

### Partner Management

```php
// List all partners
$partners = $as2->partners()->list();

// Search for specific partners
$partners = $as2->partners()->list(['search' => 'McKesson']);

// Get partner by AS2 ID
$partner = $as2->partners()->getByAs2Id('MCKESSON');

// Get partner by name (supports partial matching)
$partner = $as2->partners()->getByName('McKesson Corporation');

// Create new partner
$partner = $as2->partners()->create([
    'name' => 'Regional Supplier',
    'as2_id' => 'REGIONAL-001',
    'url' => 'https://supplier.example.com/as2'
    // Uses sensible defaults: sign=true, encrypt=true, mdn_mode='async'
]);

// Test partner connectivity
$result = $as2->partners()->test($partner);
if ($result['success']) {
    echo 'Partner connectivity verified';
}
```

### Message Operations

```php
// Send message with content
$message = $as2->messages()->send(
    $partner,
    $ediContent,
    'Invoice #12345'
);

// Send file directly
$message = $as2->messages()->sendFile(
    $partner,
    './purchase-order.edi',
    'Purchase Order #PO-2024-001'
);

// Send with advanced options
$message = $as2->messages()->send($partner, $content, 'Urgent Order', [
    'priority' => 'high',
    'compress' => true,
    'metadata' => ['orderId' => 'PO-2024-001', 'department' => 'procurement']
]);

// Send batch messages
$results = $as2->messages()->sendBatch([
    [
        'partner' => 'MCKESSON',
        'content' => file_get_contents('order1.edi'),
        'subject' => 'Order #1'
    ],
    [
        'partner' => 'CARDINAL',
        'content' => file_get_contents('order2.edi'),
        'subject' => 'Order #2'
    ]
]);

// List messages
$recent = $as2->messages()->list(['limit' => 10]);
$failed = $as2->messages()->list(['status' => 'failed']);
$fromPartner = $as2->messages()->list(['partner' => 'MCKESSON']);

// Get message payload
$content = $as2->messages()->getPayload('msg_000001');

// Wait for delivery confirmation
try {
    $delivered = $as2->messages()->waitForDelivery($message->getId(), 60000);
    echo 'Message delivered successfully';
} catch (Exception $e) {
    echo 'Message delivery failed or timed out';
}

// Validate content before sending
$result = $as2->messages()->validate($ediContent);
if ($result['valid']) {
    echo "Valid {$result['format']} document";
}

// Send test message
$testResult = $as2->messages()->sendTest($partner, [
    'messageType' => 'sample_edi',
    'encrypt' => true,
    'sign' => true
]);
```

### Certificate Management

```php
// Upload certificate
$cert = $as2->certificates()->upload([
    'name' => 'My Company Identity',
    'file' => './certificates/identity.pem',
    'type' => 'identity'
]);

// List certificates
$certs = $as2->certificates()->list();
$expiring = $as2->certificates()->list(['expiringWithin' => 30]);

// Generate identity certificate
$cert = $as2->certificates()->generateIdentity([
    'commonName' => 'My Company AS2',
    'organization' => 'My Company Inc',
    'country' => 'US',
    'email' => 'admin@mycompany.com'
]);

// Download certificate
$files = $as2->certificates()->download('cert_000001');
```

### Webhook Event Handling

```php
// Verify webhook signature (in your webhook handler)
$isValid = $as2->webhooks()->verifySignature($payload, $signature, $secret);

// Handle webhook events
$as2->webhooks()->handleEvent($eventData, [
    'message.delivered' => function($data) {
        echo "Message {$data['id']} delivered to {$data['partner']['name']}";
    },
    'message.failed' => function($data) {
        echo "Message {$data['id']} failed: {$data['error']['message']}";
    }
]);
```

### Enterprise Features and Tenant Management

```php
// Account management (account-level operations)
$account = $as2->accounts()->get();
$tenants = $as2->accounts()->listTenants();

// Create tenant
$tenant = $as2->accounts()->createTenant([
    'name' => 'European Operations',
    'slug' => 'europe'
]);

// Tenant Switching - Method 1: Client-level switching
$as2->setTenant('1'); // Set tenant context
$partners = $as2->partners()->list(); // Now scoped to this tenant
$messages = $as2->messages()->list(); // Also scoped to this tenant

// Tenant Switching - Method 2: Tenants module switching  
$as2->tenants()->switch('1'); // Also updates client context
$partners = $as2->partners()->list(); // Scoped to switched tenant

// Check current tenant
$currentTenantId = $as2->getCurrentTenant();

// Clear tenant context (for account-level operations)
$as2->setTenant(null);
$allTenants = $as2->accounts()->listTenants(); // Works without tenant context

// Tenant-specific API keys (automatically scoped)
$tenantClient = new Client('tk_live_your_tenant_key'); // Pre-scoped to tenant
$partners = $tenantClient->partners()->list(); // No switching needed

// Master Partner Inheritance Flow (7 Steps)

// Step 1: Create master partner (account-level)
$masterPartner = $as2->accounts()->masterPartners()->create([
    'name' => 'ACME Corporation',
    'as2_id' => 'ACME-CORP-001',
    'url' => 'https://acme.example.com/as2',
    'mdn_mode' => 'async',
    'sign' => true,
    'encrypt' => true,
    'compress' => false
]);

// Step 2: List master partners
$masterPartners = $as2->accounts()->masterPartners()->list();

// Step 3: Check inheritance status
$status = $as2->accounts()->masterPartners()->getInheritanceStatus($masterPartner->getId());

// Step 4: Inherit to tenants
$as2->accounts()->masterPartners()->inherit($masterPartner->getId(), [
    'tenant_ids' => ['1', '2', '3'],
    'override_settings' => [
        'url' => 'https://tenant-specific.acme.com/as2',
        'mdn_mode' => 'sync'
    ]
]);

// Step 5: View tenant partners (inherited + specific)
$as2->setTenant('1');
$tenantPartners = $as2->partners()->list(); // Shows both inherited and tenant-specific

// Step 6: Remove inheritance (optional)
$as2->accounts()->masterPartners()->removeInheritance($masterPartner->getId(), ['2', '3']);

// Step 7: Update master partner (propagates to inherited)
$as2->accounts()->masterPartners()->update($masterPartner->getId(), [
    'name' => 'ACME Corporation (Updated)',
    'url' => 'https://new-acme.example.com/as2'
]);

// Billing (account-level operations)
$usage = $as2->billing()->getUsage();
$transactions = $as2->billing()->getTransactions();
```

#### Tenant Context Rules

- **Account-level modules** (accounts, tenants, billing): No `X-Tenant-ID` header needed
- **Tenant-scoped modules** (partners, messages, certificates, webhooks): Require `X-Tenant-ID` header
- **Automatic header management**: Client automatically adds header based on current tenant context
- **Default behavior**: 
  - Account keys (`pk_*`): Defaults to first tenant if no tenant set
  - Tenant keys (`tk_*`): Automatically scoped to their specific tenant

#### Master Partner Inheritance Flow

The complete 7-step inheritance process:

1. **Create Master Partner**: Account-level partner creation
2. **List Master Partners**: View all master partners with inheritance stats
3. **Check Inheritance Status**: See which tenants inherit which partners
4. **Inherit to Tenants**: Bulk or individual inheritance with custom settings
5. **View Tenant Partners**: Combined view of inherited + tenant-specific partners
6. **Remove Inheritance**: Selective removal from specific tenants
7. **Update Master Partner**: Changes automatically propagate to inherited partners

**Benefits:**
- Centralized Management: Create once, inherit everywhere
- Tenant Customization: Override settings per tenant (URL, MDN mode, etc.)
- Automatic Sync: Master partner changes propagate automatically
- Selective Control: Choose which tenants inherit which partners
- Audit Trail: Track all inheritance relationships and changes

### Testing and Development

```php
// Use test environment with test API key (API auto-detects from key)
$as2 = new Client('pk_test_your_key');

// Or use the createTest helper for clarity
$as2 = Client::createTest('pk_test_your_key');

// Sandbox operations for testing
$info = $as2->sandbox()->getInfo();
$samples = $as2->sandbox()->getSample('edi-850');

// Simulate incoming message in test environment
$incomingMessage = $as2->sandbox()->simulateIncoming([
    'partnerId' => 'prt_000001',
    'content' => $sampleEDIContent,
    'contentType' => 'application/edi-x12',
    'subject' => 'Test Purchase Order'
]);

// Send test messages to verify partner setup
$testResult = $as2->messages()->sendTest($partner, [
    'messageType' => 'sample_edi'
]);
```

### Utility Functions

```php
// Validate EDI
$result = $as2->utils()->validateEDI($ediContent);

// Detect content type
$contentType = $as2->utils()->detectContentType($content, 'invoice.edi');

// Format file size
echo $as2->utils()->formatFileSize(1048576); // "1.0 MB"

// Generate AS2 ID
$as2Id = $as2->utils()->generateAs2Id('Acme Corporation'); // "ACME-CORP-AS2"
```

## Laravel Integration

### Installation

The service provider is automatically registered. Publish the configuration:

```bash
php artisan vendor:publish --tag=as2aas-config
```

### Configuration

Add to your `.env` file:

```env
AS2AAS_API_KEY=pk_live_your_api_key
AS2AAS_TIMEOUT=30000
AS2AAS_RETRIES=3
AS2AAS_DEFAULT_MDN_MODE=async
```

### Usage in Laravel

```php
// Using dependency injection
use AS2aaS\Client;

class OrderController extends Controller
{
    public function sendOrder(Client $as2)
    {
        $partner = $as2->partners()->getByAs2Id('MCKESSON');
        $message = $as2->messages()->send($partner, $ediContent, 'Purchase Order');
        
        return response()->json(['message_id' => $message->getId()]);
    }
}

// Using facade
use AS2aaS\Laravel\Facades\AS2;

$partner = AS2::partners()->getByAs2Id('MCKESSON');
$message = AS2::messages()->send($partner, $content, $subject);

// Using service container
$as2 = app('as2aas');
$partners = $as2->partners()->list();
```

### Webhook Handling in Laravel

```php
// routes/web.php
Route::post('/webhooks/as2', [WebhookController::class, 'handle']);

// WebhookController.php
use AS2aaS\Laravel\Facades\AS2;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $signature = $request->header('X-Signature');
        $payload = $request->getContent();
        
        if (!AS2::webhooks()->verifySignature($payload, $signature, config('as2aas.webhooks.secret'))) {
            abort(401, 'Invalid signature');
        }
        
        $event = json_decode($payload, true);
        
        AS2::webhooks()->handleEvent($event, [
            'message.delivered' => function($data) {
                // Update order status
                Order::where('as2_message_id', $data['id'])->update(['status' => 'delivered']);
            },
            'message.failed' => function($data) {
                // Send notification
                Mail::to('admin@company.com')->send(new MessageFailedMail($data));
            }
        ]);
        
        return response('OK');
    }
}
```

## Error Handling

The client provides comprehensive error handling with specific exception types:

```php
use AS2aaS\Exceptions\AS2AuthenticationError;
use AS2aaS\Exceptions\AS2ValidationError;
use AS2aaS\Exceptions\AS2NetworkError;
use AS2aaS\Exceptions\AS2PartnerError;
use AS2aaS\Exceptions\AS2RateLimitError;

try {
    $message = $as2->messages()->send($partner, $content, $subject);
} catch (AS2PartnerError $e) {
    echo 'Partner issue: ' . $e->getMessage();
} catch (AS2RateLimitError $e) {
    echo 'Rate limited, retry in: ' . $e->getRetryAfter() . ' seconds';
} catch (AS2ValidationError $e) {
    echo 'Validation errors: ' . json_encode($e->getValidationErrors());
} catch (AS2NetworkError $e) {
    if ($e->isRetryable()) {
        echo 'Retryable network error';
    }
} catch (AS2AuthenticationError $e) {
    echo 'Authentication failed: ' . $e->getMessage();
}
```

## Multi-Tenant Architecture

AS2aaS supports enterprise multi-tenant architectures where you can manage multiple business entities or customers within a single account:

### Account and Tenant Management

```php
// Account-level operations
$account = $as2->accounts()->get();
$tenants = $as2->accounts()->listTenants();

// Create new tenant for a customer
$tenant = $as2->accounts()->createTenant([
    'name' => 'East Coast Division',
    'slug' => 'east-coast'
]);

// Switch tenant context for operations
$as2->setTenant($tenant->getId());

// All subsequent operations are scoped to this tenant
$partners = $as2->partners()->list();
$messages = $as2->messages()->list();
```

### Master Partner Inheritance

Create master partners at the account level and inherit them to specific tenants:

```php
// Create master partner (account-level)
$masterPartner = $as2->accounts()->masterPartners()->create([
    'name' => 'McKesson Corporation',
    'as2_id' => 'MCKESSON',
    'url' => 'https://as2.mckesson.com/receive'
]);

// Inherit to specific tenants with custom settings
$as2->accounts()->masterPartners()->inherit($masterPartner->getId(), [
    'tenant_ids' => ['1', '2'],
    'override_settings' => [
        'url' => 'https://tenant-specific.mckesson.com/as2',
        'mdn_mode' => 'sync'
    ]
]);

// View inherited partners from tenant perspective
$as2->setTenant('1');
$tenantPartners = $as2->partners()->list(); // Shows inherited + tenant-specific
```

## Industry-Specific Integration

### Healthcare and Pharmaceutical (DSCSA)

The client is designed to support Drug Supply Chain Security Act (DSCSA) compliance requirements:

```php
// Create trading account for pharmacy chain
$tenant = $as2->accounts()->createTenant([
    'name' => 'Regional Pharmacy Chain'
]);

// Set up major pharmaceutical partners
$mckesson = $as2->accounts()->masterPartners()->create([
    'name' => 'McKesson Pharmaceutical',
    'as2_id' => 'MCKESSON-PHARMA',
    'url' => 'https://as2.mckesson.com/dscsa'
]);

// Inherit to trading account
$as2->accounts()->masterPartners()->inherit($mckesson->getId(), [
    'tenant_ids' => [$tenant->getId()]
]);

// Send DSCSA transaction
$as2->setTenant($tenant->getId());
$dscsaData = json_encode([
    'transaction_type' => 'T3_VERIFICATION_REQUEST',
    'ndc' => '12345-678-90',
    'serial_number' => 'SN789012345'
]);

$message = $as2->messages()->send($partner, $dscsaData, 'T3 Verification Request');
```

## Testing

Run the test suite:

```bash
composer test
```

Run with coverage:

```bash
composer test-coverage
```

Run static analysis:

```bash
composer analyse
```

Check code style:

```bash
composer cs-check
```

## API Reference

### Client Initialization

- `new Client(string $apiKey)` - Initialize with API key
- `new Client(array $config)` - Initialize with configuration array
- `Client::createTest(string $apiKey)` - Create test environment client
- `Client::configure(array $config)` - Set global configuration

### Core Modules

- `partners()` - Partner management and lookup
- `messages()` - Message sending and receiving
- `certificates()` - Certificate upload and management
- `accounts()` - Account-level operations (enterprise)
- `tenants()` - Tenant management and context switching
- `webhooks()` - Webhook event handling
- `billing()` - Subscription and usage management
- `sandbox()` - Testing and development utilities
- `partnerships()` - Advanced partner onboarding
- `utils()` - Helper functions and content detection

## Requirements

- PHP 8.0 or higher
- Guzzle HTTP client 7.0+
- OpenSSL extension for certificate operations
- JSON extension for API communication

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## Security

If you discover any security-related issues, please email security@as2aas.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

- Documentation: https://docs.as2aas.com
- Support: support@as2aas.com
- Issues: https://github.com/as2aas/php-client/issues

## About AS2aaS

AS2aaS is a cloud-based AS2 messaging service that eliminates the complexity of implementing and maintaining AS2 infrastructure. Our platform handles the technical requirements of AS2 protocol compliance while providing a simple, developer-friendly API for integration.

Perfect for businesses requiring secure B2B document exchange, including healthcare organizations needing DSCSA compliance, retail companies managing EDI transactions, and any enterprise requiring AS2 protocol support.