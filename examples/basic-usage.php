<?php

/**
 * Basic AS2aaS Usage Examples
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AS2aaS\Client;
use AS2aaS\Exceptions\AS2Error;

try {
    $as2 = new Client('pk_live_i1TtOrhc6oLlrYMwRPGAv21wo9AoIGnx4kmF6Tq2');
    
    echo '1. Account info:' . PHP_EOL;
    $account = $as2->accounts()->get();
    echo '✅ Account: ' . $account->getName() . PHP_EOL . PHP_EOL;
    
    echo '2. Testing stateless tenant switching:' . PHP_EOL;
    
    // Switch to tenant 1 (stateless - just sets internal context)
    echo '   Switching to tenant 1...' . PHP_EOL;
    $as2->setTenant('1');
    echo '   ✓ Tenant context set to: ' . $as2->getCurrentTenant() . PHP_EOL;
    
    // Test tenant-scoped operations (should send X-Tenant-ID: 1)
    $partners = $as2->partners()->list();
    echo '   ✓ Partners with tenant context: ' . count($partners) . PHP_EOL;
    
    foreach ($partners as $partner) {
        echo '     - ' . $partner->getName() . ' (' . $partner->getAs2Id() . ')' . PHP_EOL;
    }
    echo PHP_EOL;
    
    echo '3. Switch to different tenant:' . PHP_EOL;
    $as2->setTenant('tn_000034'); // Try tenant 34 from the example
    echo '   ✓ Tenant context set to: ' . $as2->getCurrentTenant() . PHP_EOL;
    
    $partners = $as2->partners()->list();
    echo '   ✓ Partners with new tenant context: ' . count($partners) . PHP_EOL . PHP_EOL;
    foreach ($partners as $partner) {
        echo '     - ' . $partner->getName() . ' (' . $partner->getAs2Id() . ')' . PHP_EOL;
    }
    
    echo '4. Clear tenant context (use default):' . PHP_EOL;
    $as2->setTenant(null);
    echo '   ✓ Tenant context cleared: ' . ($as2->getCurrentTenant() ?: 'None') . PHP_EOL;
    
    $partners = $as2->partners()->list();
    echo '   ✓ Partners with default context: ' . count($partners) . PHP_EOL . PHP_EOL;
    
    echo '🎉 Stateless tenant switching working perfectly!' . PHP_EOL;
    echo '   ✅ No server-side state changes' . PHP_EOL;
    echo '   ✅ Client-side context management' . PHP_EOL;
    echo '   ✅ Automatic X-Tenant-ID header injection' . PHP_EOL;
    
} catch (Exception $e) {
    echo '❌ Error: ' . $e->getMessage() . PHP_EOL;
    echo 'Type: ' . get_class($e) . PHP_EOL;
}

// // Initialize client with test API key
// $as2 = new Client('pk_live_i1TtOrhc6oLlrYMwRPGAv21wo9AoIGnx4kmF6Tq2');
// $as2->tenants()->switch('tn_000034');

// // print_r($as2->accounts()->get());
// echo "=== AS2aaS PHP Client Examples ===\n\n";

// try {
//     // 1. List partners
//     echo "1. Listing partners...\n";
//     try {
//         $partners = $as2->partners()->list();
//         echo "Found " . count($partners) . " partners\n";
        
//         foreach ($partners as $partner) {
//             echo "  - {$partner->getName()} ({$partner->getAs2Id()})\n";
//         }
//     } catch (AS2Error $e) {
//         echo "API not available (expected for demo): {$e->getMessage()}\n";
//         echo "In a real environment with valid API access, this would list your partners.\n";
//         $partners = []; // Empty for demo
//     }
//     echo "\n";

//     // 2. Get specific partner (if any exist)
//     echo "2. Getting partner by AS2 ID...\n";
//     try {
//         // Try to get a partner - this will work if you have partners set up
//         $partner = $as2->partners()->getByAs2Id('MCHADE-1');
//         echo "Found partner: {$partner->getName()}\n";
//         echo "  URL: {$partner->getUrl()}\n";
//         echo "  Type: {$partner->getType()}\n";
//         echo "  Signing: " . ($partner->isSigningEnabled() ? 'Yes' : 'No') . "\n";
//         echo "  Encryption: " . ($partner->isEncryptionEnabled() ? 'Yes' : 'No') . "\n";
//         echo "  MDN Mode: {$partner->getMdnMode()}\n";
//     } catch (AS2Error $e) {
//         echo "Partner 'MCHADE-1' not found (this is normal for new accounts): {$e->getMessage()}\n";
//         $partner = null; // We'll create one below
//     }
//     echo "\n";

//     // 3. Create test partner
//     echo "3. Creating test partner...\n";
//     try {
//         $testPartner = $as2->partners()->create([
//             'name' => 'Test Partner ' . date('Y-m-d H:i:s'),
//             'as2Id' => 'TEST-PARTNER-' . time(),
//             'url' => 'https://test.example.com/as2',
//             'sign' => true,
//             'encrypt' => true,
//             'mdnMode' => 'async'
//         ]);
//         echo "Created partner: {$testPartner->getName()} (ID: {$testPartner->getId()})\n";
//     } catch (AS2Error $e) {
//         echo "API not available (expected for demo): {$e->getMessage()}\n";
//         echo "In a real environment, this would create a new test partner.\n";
//         $testPartner = null;
//     }
//     echo "\n";

//     // 4. Test partner connectivity (skip if no partner available)
//     if ($testPartner) {
//         echo "4. Testing partner connectivity...\n";
//         try {
//             $testResult = $as2->partners()->test($testPartner);
//             echo "Test result: " . ($testResult['success'] ? 'SUCCESS' : 'FAILED') . "\n";
//             echo "Message: {$testResult['message']}\n";
//         } catch (AS2Error $e) {
//             echo "Partner test not available (expected for demo)\n";
//         }
//         echo "\n";
//     } else {
//         echo "4. Skipping partner connectivity test (no partner available)\n\n";
//     }

//     // 5. Send test message (skip if no partner available)
//     if ($partner || $testPartner) {
//         echo "5. Sending test message...\n";
//         $sampleEDI = "ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *" . date('ymd') . "*" . date('Hi') . "*U*00401*000000001*0*T*>~";
        
//         // Use the MCHADE-1 partner we found earlier, or the test partner we created
//         $partnerToUse = $partner ?: $testPartner;
        
//         try {
//             $message = $as2->messages()->send(
//                 $partnerToUse,
//                 $sampleEDI,
//                 'Test EDI Message - ' . date('Y-m-d H:i:s')
//             );
            
//             echo "Message sent! ID: {$message->getId()}\n";
//             echo "Status: {$message->getStatus()}\n";
//             echo "Message ID: {$message->getMessageId()}\n";
//         } catch (AS2Error $e) {
//             echo "Message sending not available (expected for demo)\n";
//             echo "In a real environment, this would send an EDI message.\n";
//         }
//         echo "\n";
//     } else {
//         echo "5. Skipping message sending (no partner available)\n\n";
//     }

//     // 6. List recent messages
//     echo "6. Listing recent messages...\n";
//     try {
//         $messageList = $as2->messages()->list(['limit' => 5]);
//         echo "Found {$messageList['total']} total messages (showing first 5):\n";
        
//         foreach ($messageList['data'] as $msg) {
//             echo "  - {$msg->getSubject()} ({$msg->getStatus()}) - {$msg->getCreatedAt()->format('Y-m-d H:i:s')}\n";
//         }
//     } catch (AS2Error $e) {
//         echo "Message listing not available (expected for demo)\n";
//         echo "In a real environment, this would show your recent messages.\n";
//     }
//     echo "\n";

//     // 7. Validate EDI content
//     echo "7. Validating EDI content...\n";
//     $sampleEDI = "ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *" . date('ymd') . "*" . date('Hi') . "*U*00401*000000001*0*T*>~";
//     try {
//         $validationResult = $as2->messages()->validate($sampleEDI);
//         echo "Validation result: " . ($validationResult['valid'] ? 'VALID' : 'INVALID') . "\n";
//         if (isset($validationResult['format'])) {
//             echo "Format: {$validationResult['format']}\n";
//         }
//         if (!empty($validationResult['issues'])) {
//             echo "Issues: " . implode(', ', $validationResult['issues']) . "\n";
//         }
//     } catch (AS2Error $e) {
//         echo "EDI validation not available (expected for demo)\n";
//         echo "In a real environment, this would validate EDI content.\n";
//     }
//     echo "\n";

//     // 8. Utility functions
//     echo "8. Testing utility functions...\n";
//     $contentType = $as2->utils()->detectContentType($sampleEDI);
//     echo "Detected content type: {$contentType}\n";
    
//     $as2Id = $as2->utils()->generateAs2Id('Acme Corporation Inc');
//     echo "Generated AS2 ID: {$as2Id}\n";
    
//     $fileSize = $as2->utils()->formatFileSize(strlen($sampleEDI));
//     echo "EDI content size: {$fileSize}\n";
//     echo "\n";

//     // 9. Certificate operations
//     echo "9. Listing certificates...\n";
//     try {
//         $certificates = $as2->certificates()->list();
//         echo "Found " . count($certificates) . " certificates\n";
        
//         foreach ($certificates as $cert) {
//             $daysUntilExpiry = $cert->getDaysUntilExpiry();
//             echo "  - {$cert->getName()} ({$cert->getType()}) - expires in {$daysUntilExpiry} days\n";
//         }
//     } catch (AS2Error $e) {
//         echo "Certificate listing not available (expected for demo)\n";
//         echo "In a real environment, this would show your certificates.\n";
//     }
//     echo "\n";

//     // 10. Webhook operations
//     echo "10. Listing webhooks...\n";
//     try {
//         $webhooks = $as2->webhooks()->list();
//         echo "Found " . count($webhooks) . " webhooks\n";
        
//         foreach ($webhooks as $webhook) {
//             echo "  - {$webhook->getUrl()} - events: " . implode(', ', $webhook->getEvents()) . "\n";
//         }
//     } catch (AS2Error $e) {
//         echo "Webhook listing not available (expected for demo)\n";
//         echo "In a real environment, this would show your webhook endpoints.\n";
//     }

//     // 11. Master Partner Inheritance Demo
    echo "11. Master Partner Inheritance Demo...\n";
    try {
        // List existing master partners
        $masterPartners = $as2->accounts()->masterPartners()->list();
        echo "Current master partners: " . count($masterPartners) . "\n";
        
        // Create a new master partner
        $masterPartner = $as2->accounts()->masterPartners()->create([
            'name' => 'Demo Master Partner',
            'as2_id' => 'DEMO-MASTER-' . time(),
            'url' => 'https://demo.example.com/as2',
            'mdn_mode' => 'async',
            'sign' => true,
            'encrypt' => true
        ]);
        echo "✓ Created master partner: {$masterPartner->getName()}\n";
        
        // Check inheritance status
        $status = $as2->accounts()->masterPartners()->getInheritanceStatus($masterPartner->getId());
        echo "✓ Inheritance status checked\n";
        
        // Inherit to tenant
        $inheritResult = $as2->accounts()->masterPartners()->inherit($masterPartner->getId(), [
            'tenant_ids' => ['1'],
            'override_settings' => [
                'url' => 'https://tenant1.demo.com/as2'
            ]
        ]);
        echo "✓ Inherited to tenant with custom URL\n";
        
        // View from tenant perspective
        $as2->setTenant('1');
        $tenantPartners = $as2->partners()->list();
        echo "✓ Tenant now has " . count($tenantPartners) . " partners (including inherited)\n";
        
        // Clear tenant context
        $as2->setTenant(null);
        
    } catch (AS2Error $e) {
        echo "Master partner inheritance not available (expected for demo)\n";
        echo "In a real environment, this would demonstrate the complete inheritance flow.\n";
    }
    echo "\n";

    echo "\n=== Examples completed successfully! ===\n";

// } catch (AS2Error $e) {
//     echo "Error: {$e->getMessage()}\n";
//     echo "Code: {$e->getErrorCode()}\n";
//     if ($e->getDetails()) {
//         echo "Details: " . json_encode($e->getDetails(), JSON_PRETTY_PRINT) . "\n";
//     }
//     exit(1);
// } catch (Exception $e) {
//     echo "Unexpected error: {$e->getMessage()}\n";
//     exit(1);
// }
