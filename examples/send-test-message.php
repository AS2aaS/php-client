<?php

/**
 * Minimal Test - Send Message to First Trading Partner
 * 
 * Simple test to verify message sending functionality
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AS2aaS\Client;
use AS2aaS\Exceptions\AS2Error;

try {
    // Initialize client
    $as2 = new Client('pk_live_BjMrZeli5IqjNhp4G73uWgBCWVz8JAgGqnx2aUk3');
    
    // Set tenant context
    $as2->setTenant('1');
    
    // Get first available trading partner
    $partners = $as2->partners()->list();
    
    if (empty($partners)) {
        echo "❌ No trading partners found\n";
        exit(1);
    }
    
    $partner = $partners[0];
    echo "📤 Sending test message to: {$partner->getName()} ({$partner->getAs2Id()})\n";
    
    // Sample DSCSA-style test data
    $testMessage = json_encode([
        'transaction_type' => 'TEST_MESSAGE',
        'timestamp' => date('c'),
        'from' => 'Test DSCSA Application',
        'test_data' => [
            'ndc' => '12345-678-90',
            'serial' => 'TEST-' . time(),
            'lot' => 'LOT-TEST-001'
        ]
    ]);
    
    // Send test message
    $message = $as2->messages()->send(
        $partner,
        $testMessage,
        'DSCSA Test Message - ' . date('Y-m-d H:i:s'),
        [
            'contentType' => 'application/json',
            'priority' => 'normal',
            'metadata' => [
                'test' => true,
                'transaction_type' => 'TEST_MESSAGE'
            ]
        ]
    );
    
    echo "✅ Message sent successfully!\n";
    echo "   Message ID: {$message->getId()}\n";
    echo "   AS2 Message ID: {$message->getMessageId()}\n";
    echo "   Status: {$message->getStatus()}\n";
    echo "   Size: " . $as2->utils()->formatFileSize($message->getSize()) . "\n";
    
    // Wait for delivery confirmation
    echo "\n⏳ Waiting for delivery confirmation...\n";
    $delivered = $as2->messages()->waitForDelivery($message->getId(), 30000); // 30 second timeout
    
    echo "✅ Message delivered!\n";
    echo "   Final Status: {$delivered->getStatus()}\n";
    echo "   Delivered At: {$delivered->getDeliveredAt()->format('Y-m-d H:i:s')}\n";
    
} catch (AS2Error $e) {
    echo "❌ AS2 Error: {$e->getMessage()}\n";
    if ($e->getErrorCode() !== 'unknown_error') {
        echo "   Code: {$e->getErrorCode()}\n";
    }
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    exit(1);
}
