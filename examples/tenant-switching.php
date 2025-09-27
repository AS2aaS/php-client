<?php

/**
 * Tenant Switching Examples
 * 
 * Demonstrates how to work with multiple tenants in AS2aaS
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AS2aaS\Client;
use AS2aaS\Exceptions\AS2Error;

echo "=== AS2aaS Tenant Switching Examples ===\n\n";

try {
    // Initialize with account-level API key
    $as2 = new Client('pk_live_your_account_key'); // Use account key for multi-tenant
    
    echo "1. Account Information\n";
    $account = $as2->accounts()->get();
    echo "Account: {$account->getName()}\n";
    echo "Plan: {$account->getPlan()}\n";
    echo "Tenants: {$account->getTenantCount()}\n\n";
    
    echo "2. List Available Tenants\n";
    $tenants = $as2->accounts()->listTenants();
    echo "Found " . count($tenants) . " tenants:\n";
    
    foreach ($tenants as $tenant) {
        echo "  - {$tenant->getName()} (ID: {$tenant->getId()})\n";
    }
    echo "\n";
    
    // Method 1: Using Client's setTenant method
    echo "3. Method 1: Client-level tenant switching\n";
    
    if (!empty($tenants)) {
        $firstTenant = $tenants[0];
        
        // Set tenant context on the client
        $as2->setTenant($firstTenant->getId());
        echo "✓ Set tenant context to: {$firstTenant->getName()}\n";
        
        // Now all tenant-scoped operations use this tenant
        $partners = $as2->partners()->list();
        echo "✓ Partners in this tenant: " . count($partners) . "\n";
        
        $messages = $as2->messages()->list();
        echo "✓ Messages in this tenant: " . count($messages['data']) . "\n";
        
        $certificates = $as2->certificates()->list();
        echo "✓ Certificates in this tenant: " . count($certificates) . "\n\n";
        
        // Switch to different tenant
        if (count($tenants) > 1) {
            $secondTenant = $tenants[1];
            $as2->setTenant($secondTenant->getId());
            echo "✓ Switched to: {$secondTenant->getName()}\n";
            
            $partners = $as2->partners()->list();
            echo "✓ Partners in this tenant: " . count($partners) . "\n\n";
        }
    }
    
    // Method 2: Using Tenants module switch method
    echo "4. Method 2: Tenants module switching\n";
    
    if (!empty($tenants)) {
        $tenant = $tenants[0];
        
        // Switch using tenants module (this also updates client context)
        $switchedTenant = $as2->tenants()->switch($tenant->getId());
        echo "✓ Switched via tenants module to: {$switchedTenant->getName()}\n";
        
        // Verify the client context was updated
        $currentTenantId = $as2->getCurrentTenant();
        echo "✓ Current tenant ID in client: {$currentTenantId}\n\n";
    }
    
    // Method 3: Using tenant-specific API key
    echo "5. Method 3: Tenant-specific API key\n";
    echo "If you have a tenant key (tk_live_*), it's automatically scoped:\n";
    echo "\$tenantAs2 = new Client('tk_live_your_tenant_key');\n";
    echo "// No need to switch - already scoped to specific tenant\n";
    echo "\$partners = \$tenantAs2->partners()->list();\n\n";
    
    // Method 4: Account-level operations (no tenant header needed)
    echo "6. Account-level operations (no tenant context needed)\n";
    
    // Clear tenant context for account-level operations
    $as2->setTenant(null);
    echo "✓ Cleared tenant context\n";
    
    // These operations work at account level
    $account = $as2->accounts()->get();
    echo "✓ Account operations work without tenant context\n";
    
    $tenants = $as2->accounts()->listTenants();
    echo "✓ Tenant listing works at account level\n";
    
    // Billing operations also work at account level
    try {
        $usage = $as2->billing()->getUsage();
        echo "✓ Billing operations work at account level\n";
    } catch (AS2Error $e) {
        echo "○ Billing not available (expected): {$e->getMessage()}\n";
    }
    
    echo "\n=== Tenant Switching Examples Complete ===\n";
    
    echo "\nKey Points:\n";
    echo "- Account/Billing/Tenants modules: No X-Tenant-ID header needed\n";
    echo "- Partners/Messages/Certificates: Require X-Tenant-ID header\n";
    echo "- Client automatically adds header based on current tenant context\n";
    echo "- Tenant keys (tk_*) are automatically scoped to their tenant\n";
    echo "- Account keys (pk_*) can switch between tenants\n";

} catch (AS2Error $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Code: {$e->getErrorCode()}\n";
    if ($e->getDetails()) {
        echo "Details: " . json_encode($e->getDetails(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "Unexpected error: {$e->getMessage()}\n";
}
