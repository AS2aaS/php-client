<?php

/**
 * Testing with Mock Client Example
 * 
 * Demonstrates how to use the AS2aaS mock client for unit testing
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AS2aaS\Client;

echo "=== AS2aaS Mock Client Testing Example ===\n\n";

// Create mock client (no API calls)
$mockAs2 = Client::createMock();

echo "1. Mock Client Initialization\n";
echo "✅ Mock client created successfully\n";
echo "✅ Pre-seeded with default test data\n\n";

// Test default data
echo "2. Default Test Data\n";
$partners = $mockAs2->partners()->list();
echo "✅ Default partners: " . count($partners) . "\n";

foreach ($partners as $partner) {
    echo "   - {$partner->getName()} ({$partner->getAs2Id()})\n";
}

$tenants = $mockAs2->accounts()->listTenants();
echo "✅ Default tenants: " . count($tenants) . "\n";
foreach ($tenants as $tenant) {
    echo "   - {$tenant->getName()} (ID: {$tenant->getId()})\n";
}
echo "\n";

// Test partner operations
echo "3. Partner Operations\n";
$newPartner = $mockAs2->partners()->create([
    'name' => 'Mock Test Partner',
    'as2_id' => 'MOCK-TEST',
    'url' => 'https://mock-test.example.com/as2'
]);

echo "✅ Created partner: {$newPartner->getName()}\n";
echo "   ID: {$newPartner->getId()}\n";
echo "   Type: {$newPartner->getType()}\n\n";

// Test message operations
echo "4. Message Operations\n";
$message = $mockAs2->messages()->send(
    $newPartner,
    'Mock DSCSA transaction data',
    'Mock T3 Verification Request'
);

echo "✅ Sent message: {$message->getId()}\n";
echo "   Status: {$message->getStatus()}\n";
echo "   AS2 Message ID: {$message->getMessageId()}\n";
echo "   Size: " . $mockAs2->utils()->formatFileSize($message->getSize()) . "\n\n";

// Test tenant switching
echo "5. Tenant Context Switching\n";
$mockAs2->setTenant('1');
echo "✅ Switched to tenant: {$mockAs2->getCurrentTenant()}\n";

$tenantPartners = $mockAs2->partners()->list();
echo "✅ Tenant partners: " . count($tenantPartners) . "\n\n";

// Test master partner inheritance
echo "6. Master Partner Inheritance\n";
$masterPartner = $mockAs2->accounts()->masterPartners()->create([
    'name' => 'Mock McKesson',
    'as2_id' => 'MOCK-MCKESSON',
    'url' => 'https://mock-mckesson.example.com/as2'
]);

echo "✅ Created master partner: {$masterPartner->getName()}\n";

$inheritResult = $mockAs2->accounts()->masterPartners()->inherit(
    $masterPartner->getId(),
    [
        'tenant_ids' => ['1', '2'],
        'override_settings' => ['mdn_mode' => 'sync']
    ]
);

echo "✅ Inherited to tenants: {$inheritResult['inherited_count']}\n";

$status = $mockAs2->accounts()->masterPartners()->getInheritanceStatus($masterPartner->getId());
echo "✅ Inheritance status: {$status['inheritance_stats']['total_inherited']} tenants\n\n";

// Test webhook operations
echo "7. Webhook Operations\n";
$webhook = $mockAs2->webhooks()->create([
    'url' => 'https://mock-app.example.com/webhooks',
    'events' => ['message.delivered', 'message.failed'],
    'description' => 'Mock webhook'
]);

echo "✅ Created webhook: {$webhook->getId()}\n";
echo "   URL: {$webhook->getUrl()}\n";
echo "   Events: " . implode(', ', $webhook->getEvents()) . "\n\n";

// Test webhook signature verification
$payload = '{"test": "webhook", "message_id": "msg_123"}';
$signature = 'sha256=' . hash_hmac('sha256', $payload, $webhook->getSecret());
$isValid = $mockAs2->webhooks()->verifySignature($payload, $signature, $webhook->getSecret());
echo "✅ Webhook signature verification: " . ($isValid ? 'Valid' : 'Invalid') . "\n\n";

// Test utilities
echo "8. Utility Functions\n";
$ediContent = 'ISA*00*          *00*          *ZZ*SENDER*ZZ*RECEIVER*240101*1200*U*00401*000000001*0*T*>~';
echo "✅ Content type detection: " . $mockAs2->utils()->detectContentType($ediContent) . "\n";
echo "✅ AS2 ID generation: " . $mockAs2->utils()->generateAs2Id('Test Company Inc') . "\n";
echo "✅ File size formatting: " . $mockAs2->utils()->formatFileSize(1048576) . "\n\n";

// Show final mock data state
echo "9. Final Mock Data State\n";
$mockData = $mockAs2->getMockData();
echo "✅ Partners: " . count($mockData->partners) . "\n";
echo "✅ Master Partners: " . count($mockData->masterPartners) . "\n";
echo "✅ Messages: " . count($mockData->messages) . "\n";
echo "✅ Webhooks: " . count($mockData->webhooks) . "\n";
echo "✅ Inheritance Records: " . count($mockData->inheritance) . "\n\n";

echo "=== Mock Client Testing Complete ===\n\n";

echo "Key Benefits of Mock Client:\n";
echo "• No API calls - perfect for unit testing\n";
echo "• Same interface as real client\n";
echo "• Pre-seeded with realistic test data\n";
echo "• Supports all major operations\n";
echo "• Tenant context switching\n";
echo "• Master partner inheritance simulation\n";
echo "• Webhook signature verification\n";
echo "• Direct mock data access for assertions\n";
echo "• Laravel testing integration\n\n";

echo "Perfect for testing your DSCSA application logic!\n";
