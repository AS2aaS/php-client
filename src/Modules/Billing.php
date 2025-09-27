<?php

declare(strict_types=1);

namespace AS2aaS\Modules;

/**
 * Billing module - Account-level billing management
 */
class Billing extends BaseModule
{
    public function getPlans(): array
    {
        $response = $this->httpClient->get('billing/plans');
        return $response['data'] ?? [];
    }

    public function getAccountBilling(?string $accountId = null): array
    {
        $path = $accountId ? "/accounts/{$accountId}/billing" : '/accounts/billing';
        return $this->httpClient->get($path);
    }

    public function subscribe(array $subscriptionData): array
    {
        return $this->httpClient->post('accounts/billing/subscribe', $subscriptionData);
    }

    public function addPaymentMethod(array $paymentData): array
    {
        return $this->httpClient->post('accounts/billing/payment-method', $paymentData);
    }

    public function getUsage(?string $accountId = null, array $options = []): array
    {
        $path = $accountId ? "/accounts/{$accountId}/billing/usage" : '/accounts/billing/usage';
        return $this->httpClient->get($path, $options);
    }

    public function getTransactions(?string $accountId = null, array $options = []): array
    {
        $path = $accountId ? "/accounts/{$accountId}/billing/transactions" : '/accounts/billing/transactions';
        $response = $this->httpClient->get($path, $options);
        return $response['data'] ?? [];
    }
}
