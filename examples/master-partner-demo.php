<?php

/**
 * Master Partner Inheritance Flow - Complete Demo
 * 
 * This demonstrates the complete 7-step inheritance process
 * as implemented in the AS2aaS PHP Client
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AS2aaS\Client;

echo "=== AS2aaS Master Partner Inheritance Flow Demo ===\n\n";

try {
    // Initialize with account-level API key
    $as2 = new Client('pk_live_your_account_key');

echo "📋 COMPLETE 7-STEP INHERITANCE FLOW:\n";
echo str_repeat("=", 50) . "\n\n";

// ===================================================================
// STEP 1: CREATE MASTER PARTNER (Account Level)
// ===================================================================
echo "STEP 1: Create Master Partner (Account Level)\n";
echo str_repeat("-", 45) . "\n";

$masterPartner = $as2->accounts()->masterPartners()->create([
    'name' => 'ACME Corporation',
    'as2_id' => 'ACME-CORP-001',
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
echo "   Type: {$masterPartner->getType()}\n\n";

// ===================================================================
// STEP 2: LIST MASTER PARTNERS
// ===================================================================
echo "STEP 2: List Master Partners\n";
echo str_repeat("-", 30) . "\n";

$masterPartners = $as2->accounts()->masterPartners()->list();
echo "✅ Found " . count($masterPartners) . " master partners:\n";

foreach ($masterPartners as $partner) {
    echo "   - {$partner->getName()} ({$partner->getAs2Id()})\n";
    echo "     ID: {$partner->getId()}\n";
    echo "     Active: " . ($partner->isActive() ? 'Yes' : 'No') . "\n";
    echo "     Sign: " . ($partner->isSigningEnabled() ? 'Yes' : 'No') . "\n";
    echo "     Encrypt: " . ($partner->isEncryptionEnabled() ? 'Yes' : 'No') . "\n";
}
echo "\n";

// ===================================================================
// STEP 3: CHECK INHERITANCE STATUS
// ===================================================================
echo "STEP 3: Check Inheritance Status\n";
echo str_repeat("-", 35) . "\n";

$inheritanceStatus = $as2->accounts()->masterPartners()->getInheritanceStatus($masterPartner->getId());
echo "✅ Inheritance status for {$masterPartner->getName()}:\n";

if (isset($inheritanceStatus['master_partner'])) {
    echo "   Master Partner: {$inheritanceStatus['master_partner']['name']}\n";
}

if (isset($inheritanceStatus['inherited_by_tenants'])) {
    echo "   Inherited by " . count($inheritanceStatus['inherited_by_tenants']) . " tenants:\n";
    foreach ($inheritanceStatus['inherited_by_tenants'] as $tenant) {
        echo "     - {$tenant['name']} (ID: {$tenant['id']})\n";
    }
}

if (isset($inheritanceStatus['inheritance_stats'])) {
    $stats = $inheritanceStatus['inheritance_stats'];
    echo "   Stats: {$stats['total_inherited']} total, {$stats['active_inherited']} active\n";
}
echo "\n";

// ===================================================================
// STEP 4: INHERIT TO TENANTS
// ===================================================================
echo "STEP 4: Inherit to Tenants\n";
echo str_repeat("-", 25) . "\n";

// 4a. Bulk inheritance to multiple tenants
echo "4a. Bulk inheritance to all tenants:\n";
$bulkInheritResult = $as2->accounts()->masterPartners()->inherit($masterPartner->getId(), [
    'tenant_ids' => ['1', '2', '3'],
    'override_settings' => []
]);
echo "✅ Inherited to tenants 1, 2, 3 successfully\n";

// 4b. Specific inheritance with custom settings
echo "\n4b. Custom inheritance with overrides:\n";
$customInheritResult = $as2->accounts()->masterPartners()->inherit($masterPartner->getId(), [
    'tenant_ids' => ['2'],
    'override_settings' => [
        'url' => 'https://tenant2-custom.acme.com/as2',
        'mdn_mode' => 'sync'
    ]
]);
echo "✅ Inherited to tenant 2 with custom URL and sync MDN\n\n";

// ===================================================================
// STEP 5: VIEW TENANT PARTNERS (Inherited + Specific)
// ===================================================================
echo "STEP 5: View Tenant Partners (Inherited + Specific)\n";
echo str_repeat("-", 50) . "\n";

$tenantIds = ['1', '2', '3'];
foreach ($tenantIds as $tenantId) {
    echo "Tenant {$tenantId} partners:\n";
    
    // Switch to tenant context (sets X-Tenant-ID header)
    $as2->setTenant($tenantId);
    
    $tenantPartners = $as2->partners()->list();
    echo "✅ Found " . count($tenantPartners) . " partners:\n";
    
    foreach ($tenantPartners as $partner) {
        echo "   - {$partner->getName()} ({$partner->getAs2Id()})\n";
        echo "     Type: {$partner->getType()}\n";
        echo "     Source: " . ($partner->getType() === 'inherited' ? 'Inherited from master' : 'Tenant-specific') . "\n";
        echo "     Can Override: " . ($partner->canOverrideSettings() ? 'Yes' : 'No') . "\n";
        echo "     URL: {$partner->getUrl()}\n";
    }
    echo "\n";
}

// Clear tenant context
$as2->setTenant(null);

// ===================================================================
// STEP 6: REMOVE INHERITANCE (Optional)
// ===================================================================
echo "STEP 6: Remove Inheritance (Optional)\n";
echo str_repeat("-", 40) . "\n";

$removeResult = $as2->accounts()->masterPartners()->removeInheritance(
    $masterPartner->getId(),
    ['3'] // Remove from tenant 3
);
echo "✅ Removed inheritance from tenant 3\n\n";

// ===================================================================
// STEP 7: UPDATE MASTER PARTNER (Propagates to Inherited)
// ===================================================================
echo "STEP 7: Update Master Partner (Propagates to Inherited)\n";
echo str_repeat("-", 55) . "\n";

$updatedMaster = $as2->accounts()->masterPartners()->update($masterPartner->getId(), [
    'name' => 'ACME Corporation (Updated)',
    'url' => 'https://new-acme.example.com/as2'
]);

echo "✅ Updated master partner:\n";
echo "   New Name: {$updatedMaster->getName()}\n";
echo "   New URL: {$updatedMaster->getUrl()}\n";
echo "   ⚡ Changes automatically propagate to all inherited partners\n";
echo "   📝 Tenant-specific overrides are preserved\n\n";

// ===================================================================
// VERIFICATION: Check Final State
// ===================================================================
echo "VERIFICATION: Final State\n";
echo str_repeat("-", 25) . "\n";

// Check master partner final state
$finalMaster = $as2->accounts()->masterPartners()->get($masterPartner->getId());
echo "Master Partner Final State:\n";
echo "   Name: {$finalMaster->getName()}\n";
echo "   URL: {$finalMaster->getUrl()}\n";

// Check tenant partners after update
$as2->setTenant('1');
$finalTenantPartners = $as2->partners()->list();
echo "\nTenant 1 Partners After Master Update:\n";
foreach ($finalTenantPartners as $partner) {
    if ($partner->getType() === 'inherited') {
        echo "   - {$partner->getName()} (inherited, URL: {$partner->getUrl()})\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎉 MASTER PARTNER INHERITANCE FLOW COMPLETE!\n";
echo str_repeat("=", 60) . "\n\n";

echo "📊 FLOW SUMMARY:\n";
echo "1. ✅ Created master partner at account level\n";
echo "2. ✅ Listed all master partners with metadata\n";
echo "3. ✅ Checked inheritance status and relationships\n";
echo "4. ✅ Inherited to multiple tenants (bulk + custom)\n";
echo "5. ✅ Viewed combined tenant partner lists\n";
echo "6. ✅ Removed selective inheritance\n";
echo "7. ✅ Updated master with automatic propagation\n\n";

echo "🔑 KEY FEATURES IMPLEMENTED:\n";
echo "• Account-scoped master partner management\n";
echo "• Bulk inheritance to multiple tenants\n";
echo "• Per-tenant setting overrides\n";
echo "• Automatic change propagation\n";
echo "• Selective inheritance removal\n";
echo "• Health monitoring and audit trails\n";
echo "• Proper tenant context switching\n\n";

echo "🚀 Ready for production use when AS2aaS API is fully deployed!\n";

echo "\n" . str_repeat("=", 60) . "\n";

} catch (Exception $e) {
    echo "Note: This demo shows the complete implementation.\n";
    echo "Some operations may fail until the AS2aaS API is fully deployed.\n";
    echo "Error: {$e->getMessage()}\n";
}
