<?php

/**
 * Minimal Test - Verify AS2aaS Integration
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AS2aaS\Client;

try {
    $as2 = new Client('pk_live_BjMrZeli5IqjNhp4G73uWgBCWVz8JAgGqnx2aUk3');
    $as2->setTenant('1');
    
    $partners = $as2->partners()->list();
    
    if (empty($partners)) {
        echo "❌ No partners found\n";
        exit(1);
    }
    
    $partner = $partners[0];
    echo "✅ Found partner: {$partner->getName()} ({$partner->getAs2Id()})\n";
    echo "✅ Ready to send messages when API is complete\n";
    
} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    exit(1);
}
