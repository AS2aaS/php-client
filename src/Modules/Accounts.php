<?php

declare(strict_types=1);

namespace AS2aaS\Modules;

use AS2aaS\Http\HttpClient;
use AS2aaS\Models\Account;
use AS2aaS\Models\Tenant;

/**
 * Accounts module - Account-level management for enterprise users
 */
class Accounts extends BaseModule
{
    private ?MasterPartners $masterPartners = null;

    /**
     * Get current account information
     */
    public function get(): Account
    {
        $response = $this->httpClient->get('accounts');
        
        // The API returns the current account in meta.current_account
        if (isset($response['meta']['current_account'])) {
            return new Account($response['meta']['current_account']);
        }
        
        // Fallback to first account if current_account not available
        if (isset($response['data'][0])) {
            return new Account($response['data'][0]);
        }
        
        throw new \AS2aaS\Exceptions\AS2Error('No account data found in response');
    }

    /**
     * Update account information
     */
    public function update(array $data): Account
    {
        $response = $this->httpClient->put('accounts', $data);
        return new Account($response);
    }

    /**
     * List all tenants in the account
     */
    public function listTenants(): array
    {
        $response = $this->httpClient->get('tenants');
        return array_map(fn($data) => new Tenant($data), $response['data'] ?? []);
    }

    /**
     * Create new tenant in the account
     */
    public function createTenant(array $tenantData): Tenant
    {
        // Auto-generate slug if not provided
        if (!isset($tenantData['slug']) && isset($tenantData['name'])) {
            $tenantData['slug'] = $this->generateSlug($tenantData['name']);
        }

        $response = $this->httpClient->post('tenants', $tenantData);
        return new Tenant($response['data'] ?? $response);
    }

    /**
     * Get master partners module
     */
    public function masterPartners(): MasterPartners
    {
        // Get current account ID for master partner operations
        if (!$this->masterPartners) {
            $account = $this->get();
            $this->masterPartners = new MasterPartners($this->httpClient, $account->getId());
        }
        return $this->masterPartners;
    }

    /**
     * Generate slug from name
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Add random suffix to ensure uniqueness
        $slug .= '-' . substr(md5(uniqid()), 0, 6);
        
        return $slug;
    }
}

/**
 * Master Partners submodule
 */
class MasterPartners extends BaseModule
{
    private string $accountId;

    public function __construct(HttpClient $httpClient, string $accountId)
    {
        parent::__construct($httpClient);
        $this->accountId = $accountId;
    }

    /**
     * List master partners
     */
    public function list(): array
    {
        $response = $this->httpClient->get("accounts/{$this->accountId}/partners");
        return array_map(fn($data) => new \AS2aaS\Models\Partner($data), $response['data'] ?? []);
    }

    /**
     * Create master partner
     */
    public function create(array $partnerData): \AS2aaS\Models\Partner
    {
        $response = $this->httpClient->post("accounts/{$this->accountId}/partners", $partnerData);
        return new \AS2aaS\Models\Partner($response['data'] ?? $response);
    }

    /**
     * Get master partner
     */
    public function get(string $partnerId): \AS2aaS\Models\Partner
    {
        $response = $this->httpClient->get("accounts/{$this->accountId}/partners/{$partnerId}");
        return new \AS2aaS\Models\Partner($response);
    }

    /**
     * Update master partner (propagates to inherited)
     */
    public function update(string $partnerId, array $data): \AS2aaS\Models\Partner
    {
        $response = $this->httpClient->put("accounts/{$this->accountId}/partners/{$partnerId}", $data);
        return new \AS2aaS\Models\Partner($response);
    }

    /**
     * Delete master partner
     */
    public function delete(string $partnerId): bool
    {
        $this->httpClient->delete("accounts/{$this->accountId}/partners/{$partnerId}");
        return true;
    }

    /**
     * Inherit master partner to tenants
     */
    public function inherit(string $masterPartnerId, array $inheritanceData): array
    {
        $response = $this->httpClient->post("accounts/{$this->accountId}/partners/{$masterPartnerId}/inherit", $inheritanceData);
        return $response;
    }

    /**
     * Remove inheritance from tenants
     */
    public function removeInheritance(string $masterPartnerId, array $tenantIds): bool
    {
        $this->httpClient->delete("accounts/{$this->accountId}/partners/{$masterPartnerId}/inherit", [
            'tenant_ids' => $tenantIds
        ]);
        return true;
    }

    /**
     * Get inheritance status
     */
    public function getInheritanceStatus(string $masterPartnerId): array
    {
        $response = $this->httpClient->get("accounts/{$this->accountId}/partners/{$masterPartnerId}/inheritance");
        return $response;
    }

    /**
     * Get master partners health overview
     */
    public function getHealth(): array
    {
        $response = $this->httpClient->get("accounts/{$this->accountId}/partners-health");
        return $response;
    }
}
