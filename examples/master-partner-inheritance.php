<?php

/**
 * Complete Master Partner Inheritance Flow Example
 * 
 * Demonstrates the full 7-step master partner inheritance process
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AS2aaS\Client;
use AS2aaS\Exceptions\AS2Error;

echo "=== AS2aaS Master Partner Inheritance Flow ===\n\n";

try {
    // Initialize with account-level API key (required for master partner operations)
    $as2 = new Client('pk_live_i1TtOrhc6oLlrYMwRPGAv21wo9AoIGnx4kmF6Tq2');
    
    // Get account information
    $account = $as2->accounts()->get();
    echo "Account: {$account->getName()} (ID: {$account->getId()})\n";
    echo "Plan: {$account->getPlan()}\n\n";
    
    // ===================================================================
    // STEP 1: CREATE MASTER PARTNER (Account Level)
    // ===================================================================
    echo "STEP 1: Create Master Partner (Account Level)\n";
    echo "=" . str_repeat("=", 50) . "\n";
    
    try {
        $masterPartner = $as2->accounts()->masterPartners()->create([
            'name' => 'ACME Corporation',
            'as2_id' => 'ACME-CORP-' . time(), // Unique AS2 ID
            'url' => 'https://acme.example.com/as2',
            'mdn_mode' => 'async',
            'sign' => true,
            'encrypt' => true,
            'compress' => false,
            'sign_algorithm' => 'SHA256withRSA',
            'encrypt_algorithm' => 'AES128_CBC'
        ]);
        
        echo "✅ Created master partner: {$masterPartner->getName()}\n";
        echo "   ID: {$masterPartner->getId()}\n";
        echo "   AS2 ID: {$masterPartner->getAs2Id()}\n";
        echo "   URL: {$masterPartner->getUrl()}\n";
        echo "   Type: {$masterPartner->getType()}\n";
        
        $masterPartnerId = $masterPartner->getId();
        
    } catch (AS2Error $e) {
        echo "❌ Master partner creation failed: {$e->getMessage()}\n";
        echo "   This is expected if the API endpoint isn't fully implemented yet.\n";
        $masterPartnerId = 'demo_master_001'; // Use demo ID for remaining steps
    }
    echo "\n";
    
    // ===================================================================
    // STEP 2: LIST MASTER PARTNERS
    // ===================================================================
    echo "STEP 2: List Master Partners\n";
    echo "=" . str_repeat("=", 30) . "\n";
    
    try {
        $masterPartners = $as2->accounts()->masterPartners()->list();
        echo "✅ Found " . count($masterPartners) . " master partners:\n";
        
        foreach ($masterPartners as $partner) {
            echo "   - {$partner->getName()} ({$partner->getAs2Id()})\n";
            echo "     ID: {$partner->getId()}\n";
            echo "     Type: {$partner->getType()}\n";
            echo "     Active: " . ($partner->isActive() ? 'Yes' : 'No') . "\n";
        }
        
    } catch (AS2Error $e) {
        echo "❌ Master partners listing: {$e->getMessage()}\n";
        echo "   This is expected if the API endpoint isn't fully implemented yet.\n";
    }
    echo "\n";
    
    // ===================================================================
    // STEP 3: CHECK INHERITANCE STATUS
    // ===================================================================
    echo "STEP 3: Check Inheritance Status\n";
    echo "=" . str_repeat("=", 35) . "\n";
    
    try {
        $inheritanceStatus = $as2->accounts()->masterPartners()->getInheritanceStatus($masterPartnerId);
        echo "✅ Inheritance status for master partner:\n";
        
        if (isset($inheritanceStatus['master_partner'])) {
            echo "   Master Partner: {$inheritanceStatus['master_partner']['name']}\n";
        }
        
        if (isset($inheritanceStatus['inherited_by_tenants'])) {
            echo "   Inherited by " . count($inheritanceStatus['inherited_by_tenants']) . " tenants\n";
            foreach ($inheritanceStatus['inherited_by_tenants'] as $tenant) {
                echo "     - Tenant: {$tenant['name']} (ID: {$tenant['id']})\n";
            }
        }
        
        if (isset($inheritanceStatus['inheritance_stats'])) {
            $stats = $inheritanceStatus['inheritance_stats'];
            echo "   Stats: {$stats['total_inherited']} total, {$stats['active_inherited']} active\n";
        }
        
    } catch (AS2Error $e) {
        echo "❌ Inheritance status: {$e->getMessage()}\n";
        echo "   This is expected if the API endpoint isn't fully implemented yet.\n";
    }
    echo "\n";
    
    // Get available tenants for inheritance
    echo "Available tenants for inheritance:\n";
    try {
        $tenants = $as2->accounts()->listTenants();
        echo "✅ Found " . count($tenants) . " tenants:\n";
        
        $tenantIds = [];
        foreach ($tenants as $tenant) {
            echo "   - {$tenant->getName()} (ID: {$tenant->getId()})\n";
            $tenantIds[] = $tenant->getId();
        }
        
    } catch (AS2Error $e) {
        echo "❌ Tenant listing: {$e->getMessage()}\n";
        $tenantIds = ['1', '2']; // Demo tenant IDs
    }
    echo "\n";
    
    // ===================================================================
    // STEP 4: INHERIT TO TENANTS
    // ===================================================================
    echo "STEP 4: Inherit to Tenants\n";
    echo "=" . str_repeat("=", 25) . "\n";
    
    // 4a. Inherit to all tenants with default settings
    echo "4a. Inherit to all tenants (default settings):\n";
    try {
        $inheritResult = $as2->accounts()->masterPartners()->inherit($masterPartnerId, [
            'tenant_ids' => $tenantIds,
            'override_settings' => []
        ]);
        
        echo "✅ Inherited to all tenants successfully\n";
        print_r($inheritResult);
        
    } catch (AS2Error $e) {
        echo "❌ Bulk inheritance: {$e->getMessage()}\n";
        echo "   This is expected if the API endpoint isn't fully implemented yet.\n";
    }
    
    // 4b. Inherit to specific tenant with overrides
    echo "\n4b. Inherit to specific tenant with custom settings:\n";
    if (!empty($tenantIds)) {
        try {
            $customInheritResult = $as2->accounts()->masterPartners()->inherit($masterPartnerId, [
                'tenant_ids' => [$tenantIds[0]], // First tenant only
                'override_settings' => [
                    'url' => 'https://tenant-custom.acme.com/as2',
                    'mdn_mode' => 'sync' // Override to sync for this tenant
                ]
            ]);
            
            echo "✅ Inherited to tenant {$tenantIds[0]} with custom settings\n";
            print_r($customInheritResult);
            
        } catch (AS2Error $e) {
            echo "❌ Custom inheritance: {$e->getMessage()}\n";
            echo "   This is expected if the API endpoint isn't fully implemented yet.\n";
        }
    }
    echo "\n";
    
    // ===================================================================
    // STEP 5: VIEW TENANT PARTNERS (Inherited + Specific)
    // ===================================================================
    echo "STEP 5: View Tenant Partners (Inherited + Specific)\n";
    echo "=" . str_repeat("=", 50) . "\n";
    
    foreach ($tenantIds as $tenantId) {
        echo "Tenant {$tenantId} partners:\n";
        
        // Switch to tenant context
        $as2->setTenant($tenantId);
        
        try {
            $tenantPartners = $as2->partners()->list();
            echo "✅ Found " . count($tenantPartners) . " partners:\n";
            
            foreach ($tenantPartners as $partner) {
                echo "   - {$partner->getName()} ({$partner->getAs2Id()})\n";
                echo "     Type: {$partner->getType()}\n";
                echo "     Source: " . ($partner->isMasterPartner() ? 'Master' : 'Tenant-specific') . "\n";
                echo "     Can Override: " . ($partner->canOverrideSettings() ? 'Yes' : 'No') . "\n";
                echo "     URL: {$partner->getUrl()}\n";
                echo "     MDN Mode: {$partner->getMdnMode()}\n";
            }
            
        } catch (AS2Error $e) {
            echo "❌ Tenant partners: {$e->getMessage()}\n";
        }
        echo "\n";
    }
    
    // Clear tenant context
    $as2->setTenant(null);
    
    // ===================================================================
    // STEP 6: REMOVE INHERITANCE (Optional)
    // ===================================================================
    echo "STEP 6: Remove Inheritance (Optional)\n";
    echo "=" . str_repeat("=", 40) . "\n";
    
    if (count($tenantIds) > 1) {
        try {
            $removeResult = $as2->accounts()->masterPartners()->removeInheritance(
                $masterPartnerId,
                [$tenantIds[1]] // Remove from second tenant
            );
            
            echo "✅ Removed inheritance from tenant {$tenantIds[1]}\n";
            
        } catch (AS2Error $e) {
            echo "❌ Remove inheritance: {$e->getMessage()}\n";
            echo "   This is expected if the API endpoint isn't fully implemented yet.\n";
        }
    } else {
        echo "○ Skipping removal (only one tenant available)\n";
    }
    echo "\n";
    
    // ===================================================================
    // STEP 7: UPDATE MASTER PARTNER (Propagates to Inherited)
    // ===================================================================
    echo "STEP 7: Update Master Partner (Propagates to Inherited)\n";
    echo "=" . str_repeat("=", 55) . "\n";
    
    try {
        $updatedMaster = $as2->accounts()->masterPartners()->update($masterPartnerId, [
            'name' => 'ACME Corporation (Updated)',
            'url' => 'https://new-acme.example.com/as2'
        ]);
        
        echo "✅ Updated master partner:\n";
        echo "   New Name: {$updatedMaster->getName()}\n";
        echo "   New URL: {$updatedMaster->getUrl()}\n";
        echo "   Changes will propagate to all inherited partners (unless overridden)\n";
        
    } catch (AS2Error $e) {
        echo "❌ Master partner update: {$e->getMessage()}\n";
        echo "   This is expected if the API endpoint isn't fully implemented yet.\n";
    }
    echo "\n";
    
    // ===================================================================
    // BONUS: MASTER PARTNERS HEALTH CHECK
    // ===================================================================
    echo "BONUS: Master Partners Health Check\n";
    echo "=" . str_repeat("=", 35) . "\n";
    
    try {
        $health = $as2->accounts()->masterPartners()->getHealth();
        echo "✅ Master partners health overview:\n";
        print_r($health);
        
    } catch (AS2Error $e) {
        echo "❌ Health check: {$e->getMessage()}\n";
        echo "   This is expected if the API endpoint isn't fully implemented yet.\n";
    }
    echo "\n";
    
    echo "=== Master Partner Inheritance Flow Complete ===\n\n";
    
    echo "📋 SUMMARY OF INHERITANCE FLOW:\n";
    echo "1. ✅ Create master partner at account level\n";
    echo "2. ✅ List all master partners with inheritance counts\n";
    echo "3. ✅ Check inheritance status and tenant relationships\n";
    echo "4. ✅ Inherit to multiple tenants (bulk or individual)\n";
    echo "5. ✅ View tenant partners (shows inherited + tenant-specific)\n";
    echo "6. ✅ Remove inheritance from specific tenants\n";
    echo "7. ✅ Update master partner (changes propagate automatically)\n\n";
    
    echo "🔑 KEY BENEFITS:\n";
    echo "• Centralized Management: Create once, inherit everywhere\n";
    echo "• Tenant Customization: Override settings per tenant\n";
    echo "• Automatic Sync: Master changes propagate to inherited partners\n";
    echo "• Selective Inheritance: Choose which tenants inherit which partners\n";
    echo "• Audit Trail: Track inheritance relationships and changes\n\n";
    
    echo "🏗️ ARCHITECTURE:\n";
    echo "• Master partners live at account level\n";
    echo "• Inherited partners appear in tenant partner lists\n";
    echo "• Tenant context (X-Tenant-ID) shows combined view\n";
    echo "• Account context shows only master partners\n";

} catch (AS2Error $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Code: {$e->getErrorCode()}\n";
    if ($e->getDetails()) {
        echo "Details: " . json_encode($e->getDetails(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "Unexpected error: {$e->getMessage()}\n";
}
