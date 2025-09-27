<?php

/**
 * Quick Message Test - No Delivery Wait
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AS2aaS\Client;

try {
    $as2 = new Client('pk_live_BjMrZeli5IqjNhp4G73uWgBCWVz8JAgGqnx2aUk3');
    $as2->setTenant('1');
    
    $partners = $as2->partners()->list();
    if (empty($partners)) {
        echo "❌ No partners\n";
        exit(1);
    }
    
    $partner = $partners[0];
    $testData = json_encode(['test' => true, 'timestamp' => time()]);
    
    $message = $as2->messages()->send(
        $partner,
        $testData,
        'Quick Test Message',
        ['contentType' => 'application/json']
    );
    
    echo "✅ Message sent!\n";
    echo "   ID: {$message->getId()}\n";
    echo "   Status: {$message->getStatus()}\n";
    echo "   Size: " . $as2->utils()->formatFileSize($message->getSize()) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    exit(1);
}
