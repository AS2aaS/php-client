<?php

declare(strict_types=1);

namespace AS2aaS\Testing;

/**
 * Mock data storage for testing
 */
class MockData
{
    public array $accounts = [];
    public array $tenants = [];
    public array $partners = [];
    public array $masterPartners = [];
    public array $messages = [];
    public array $certificates = [];
    public array $webhooks = [];
    public array $inheritance = []; // Track master partner inheritance
    
    /**
     * Reset all mock data
     */
    public function reset(): void
    {
        $this->accounts = [];
        $this->tenants = [];
        $this->partners = [];
        $this->masterPartners = [];
        $this->messages = [];
        $this->certificates = [];
        $this->webhooks = [];
        $this->inheritance = [];
    }
    
    /**
     * Get next ID for a resource type
     */
    public function getNextId(string $type, string $prefix = ''): string
    {
        $collection = $this->{$type} ?? [];
        $nextId = count($collection) + 1;
        return $prefix ? $prefix . '_' . str_pad((string)$nextId, 3, '0', STR_PAD_LEFT) : (string)$nextId;
    }
}
