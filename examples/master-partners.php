<?php

/**
 * Master Partners Management Examples
 * 
 * Demonstrates how to create and manage master partners at the account level
 * and inherit them to specific tenants
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AS2aaS\Client;
use AS2aaS\Exceptions\AS2Error;

echo "=== AS2aaS Master Partners Management ===\n\n";

try {
    // Initialize with account-level API key (required for master partner operations)
    $as2 = new Client('pk_live_i1TtOrhc6oLlrYMwRPGAv21wo9AoIGnx4kmF6Tq2');
    
    echo "1. Account Information\n";
    $account = $as2->accounts()->get();
    echo "Account: {$account->getName()}\n";
    echo "Plan: {$account->getPlan()}\n";
    echo "Status: {$account->getStatus()}\n\n";
    
    echo "2. List Existing Master Partners\n";
    try {
        $masterPartners = $as2->accounts()->masterPartners()->list();
        echo "Found " . count($masterPartners) . " master partners:\n";
        
        foreach ($masterPartners as $partner) {
            echo "  - {$partner->getName()} ({$partner->getAs2Id()})\n";
            echo "    Type: {$partner->getType()}\n";
            echo "    URL: {$partner->getUrl()}\n";
            echo "    Active: " . ($partner->isActive() ? 'Yes' : 'No') . "\n";
        }
    } catch (AS2Error $e) {
        echo "Master partners listing not available: {$e->getMessage()}\n";
        $masterPartners = [];
    }
    echo "\n";
    
    echo "3. Create New Master Partner\n";
    try {
        $newMasterPartner = $as2->accounts()->masterPartners()->create([
            'name' => 'Global Healthcare Partner',
            'as2_id' => 'GLOBAL-HEALTH-' . time(), // Unique AS2 ID
            'url' => 'https://global-health.example.com/as2',
            'sign' => true,
            'encrypt' => true,
            'compress' => false,
            'mdn_mode' => 'async'
        ]);
        
        echo "✓ Created master partner: {$newMasterPartner->getName()}\n";
        echo "  ID: {$newMasterPartner->getId()}\n";
        echo "  AS2 ID: {$newMasterPartner->getAs2Id()}\n";
        echo "  Type: {$newMasterPartner->getType()}\n";
        
    } catch (AS2Error $e) {
        echo "Master partner creation not available: {$e->getMessage()}\n";
        $newMasterPartner = null;
    }
    echo "\n";
    
    echo "4. List Available Tenants\n";
    try {
        $tenants = $as2->accounts()->listTenants();
        echo "Found " . count($tenants) . " tenants:\n";
        
        foreach ($tenants as $tenant) {
            echo "  - {$tenant->getName()} (ID: {$tenant->getId()})\n";
        }
        
        $targetTenant = !empty($tenants) ? $tenants[0] : null;
        
    } catch (AS2Error $e) {
        echo "Tenant listing not available: {$e->getMessage()}\n";
        $targetTenant = null;
    }
    echo "\n";
    
    echo "5. Inherit Master Partner to Tenant\n";
    if ($newMasterPartner && $targetTenant) {
        try {
            $inheritedPartner = $as2->accounts()->masterPartners()->inherit(
                $newMasterPartner->getId(),
                [
                    'tenantId' => $targetTenant->getId(),
                    'overrideSettings' => [
                        'url' => 'https://tenant-specific.global-health.com/as2',
                        'mdn_mode' => 'sync' // Override to sync for this tenant
                    ]
                ]
            );
            
            echo "✓ Inherited master partner to tenant: {$targetTenant->getName()}\n";
            echo "  Partner ID: {$inheritedPartner->getId()}\n";
            echo "  Type: {$inheritedPartner->getType()}\n";
            echo "  Overridden URL: {$inheritedPartner->getUrl()}\n";
            echo "  MDN Mode: {$inheritedPartner->getMdnMode()}\n";
            
        } catch (AS2Error $e) {
            echo "Master partner inheritance not available: {$e->getMessage()}\n";
        }
    } else {
        echo "Skipping inheritance (no master partner or tenant available)\n";
    }
    echo "\n";
    
    echo "6. Check Inheritance Status\n";
    if ($newMasterPartner) {
        try {
            $inheritanceStatus = $as2->accounts()->masterPartners()->getInheritanceStatus(
                $newMasterPartner->getId()
            );
            
            echo "Inheritance status for {$newMasterPartner->getName()}:\n";
            print_r($inheritanceStatus);
            
        } catch (AS2Error $e) {
            echo "Inheritance status not available: {$e->getMessage()}\n";
        }
    }
    echo "\n";
    
    echo "7. View Partners from Tenant Perspective\n";
    if ($targetTenant) {
        // Switch to tenant context to see both tenant-specific and inherited partners
        $as2->setTenant($targetTenant->getId());
        
        try {
            $tenantPartners = $as2->partners()->list();
            echo "Partners visible to tenant '{$targetTenant->getName()}':\n";
            
            foreach ($tenantPartners as $partner) {
                echo "  - {$partner->getName()} ({$partner->getAs2Id()})\n";
                echo "    Type: {$partner->getType()}\n";
                echo "    Can Override: " . ($partner->canOverrideSettings() ? 'Yes' : 'No') . "\n";
                echo "    Is Master: " . ($partner->isMasterPartner() ? 'Yes' : 'No') . "\n";
            }
            
        } catch (AS2Error $e) {
            echo "Tenant partners listing not available: {$e->getMessage()}\n";
        }
        
        // Clear tenant context
        $as2->setTenant(null);
    }
    echo "\n";
    
    echo "8. Master Partner Management Operations\n";
    if ($newMasterPartner) {
        try {
            // Update master partner
            $updatedPartner = $as2->accounts()->masterPartners()->update(
                $newMasterPartner->getId(),
                ['name' => $newMasterPartner->getName() . ' (Updated)']
            );
            echo "✓ Updated master partner name\n";
            
            // Get specific master partner
            $retrievedPartner = $as2->accounts()->masterPartners()->get($newMasterPartner->getId());
            echo "✓ Retrieved master partner: {$retrievedPartner->getName()}\n";
            
        } catch (AS2Error $e) {
            echo "Master partner operations not available: {$e->getMessage()}\n";
        }
    }
    echo "\n";
    
    echo "=== Master Partners Management Complete ===\n\n";
    
    echo "Key Concepts:\n";
    echo "• Master partners are created at the account level\n";
    echo "• They can be inherited by multiple tenants\n";
    echo "• Each tenant can override specific settings (URL, MDN mode, etc.)\n";
    echo "• Inherited partners appear in tenant partner lists with type 'inherited'\n";
    echo "• Use account-level API keys (pk_*) for master partner operations\n";
    echo "• Use tenant context switching to see inherited partners\n";

} catch (AS2Error $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Code: {$e->getErrorCode()}\n";
    if ($e->getDetails()) {
        echo "Details: " . json_encode($e->getDetails(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "Unexpected error: {$e->getMessage()}\n";
}
