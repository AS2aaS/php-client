<?php

declare(strict_types=1);

namespace AS2aaS\Modules;

use AS2aaS\Exceptions\AS2PartnerError;
use AS2aaS\Http\HttpClient;
use AS2aaS\Models\Partner;

/**
 * Partners module - Manage trading partners
 */
class Partners extends BaseModule
{
    /**
     * List all available partners
     */
    public function list(array $options = []): array
    {
        $query = [];

        if (isset($options['type'])) {
            $query['type'] = $options['type'];
        }

        if (isset($options['active'])) {
            $query['active'] = $options['active'] ? '1' : '0';
        }

        if (isset($options['search'])) {
            $query['search'] = $options['search'];
        }

        $response = $this->httpClient->get('partners', $query);

        return array_map(fn($data) => new Partner($data), $response['data'] ?? []);
    }

    /**
     * Get partner by AS2 identifier
     */
    public function getByAs2Id(string $as2Id): Partner
    {
        $partners = $this->list(['search' => $as2Id]);

        foreach ($partners as $partner) {
            if ($partner->getAs2Id() === $as2Id) {
                return $partner;
            }
        }

        throw new AS2PartnerError("Partner with AS2 ID '{$as2Id}' not found", 'partner_not_found');
    }

    /**
     * Get partner by human-readable name
     */
    public function getByName(string $name): Partner
    {
        $partners = $this->list(['search' => $name]);

        // Try exact match first
        foreach ($partners as $partner) {
            if (strcasecmp($partner->getName(), $name) === 0) {
                return $partner;
            }
        }

        // Try partial match
        foreach ($partners as $partner) {
            if (stripos($partner->getName(), $name) !== false) {
                return $partner;
            }
        }

        throw new AS2PartnerError("Partner with name '{$name}' not found", 'partner_not_found');
    }

    /**
     * Get partner by ID
     */
    public function get(string $partnerId): Partner
    {
        $response = $this->httpClient->get("partners/{$partnerId}");
        return new Partner($response);
    }

    /**
     * Create new tenant-specific partner
     */
    public function create(array $partnerData): Partner
    {
        // Validate required fields
        $required = ['name', 'as2Id', 'url'];
        foreach ($required as $field) {
            if (!isset($partnerData[$field])) {
                throw new AS2PartnerError("Field '{$field}' is required", 'validation_error');
            }
        }

        // Apply sensible defaults
        $data = array_merge([
            'sign' => true,
            'encrypt' => true,
            'compress' => false,
            'mdnMode' => 'async',
            'active' => true,
        ], $partnerData);

        $response = $this->httpClient->post('partners', $data);
        return new Partner($response);
    }

    /**
     * Update partner
     */
    public function update(string $partnerId, array $partnerData): Partner
    {
        $response = $this->httpClient->patch("partners/{$partnerId}", $partnerData);
        return new Partner($response);
    }

    /**
     * Delete partner
     */
    public function delete(string $partnerId): bool
    {
        $this->httpClient->delete("partners/{$partnerId}");
        return true;
    }

    /**
     * Test partner connectivity and configuration
     */
    public function test($partner, array $options = []): array
    {
        $partnerId = $partner instanceof Partner ? $partner->getId() : $partner;
        
        $data = [
            'type' => $options['type'] ?? 'ping',
            'timeout' => $options['timeout'] ?? 30000,
        ];

        $response = $this->httpClient->post("partners/{$partnerId}/test", $data);

        return [
            'success' => $response['success'] ?? false,
            'message' => $response['message'] ?? '',
            'error' => $response['error'] ?? null,
            'details' => $response['details'] ?? [],
            'duration' => $response['duration'] ?? 0,
        ];
    }

    /**
     * Get partner certificates
     */
    public function getCertificates(string $partnerId): array
    {
        $response = $this->httpClient->get("partners/{$partnerId}/certificates");
        return $response['data'] ?? [];
    }

    /**
     * Upload certificate for partner
     */
    public function uploadCertificate(string $partnerId, array $certificateData): array
    {
        $multipart = [
            [
                'name' => 'name',
                'contents' => $certificateData['name'],
            ],
            [
                'name' => 'usage',
                'contents' => $certificateData['usage'] ?? 'encryption',
            ],
        ];

        // Handle file upload
        if (isset($certificateData['file'])) {
            if (is_string($certificateData['file']) && file_exists($certificateData['file'])) {
                // File path
                $multipart[] = [
                    'name' => 'certificate',
                    'contents' => fopen($certificateData['file'], 'r'),
                    'filename' => basename($certificateData['file']),
                ];
            } else {
                // File content
                $multipart[] = [
                    'name' => 'certificate',
                    'contents' => $certificateData['file'],
                    'filename' => $certificateData['filename'] ?? 'certificate.pem',
                ];
            }
        }

        $response = $this->httpClient->upload("partners/{$partnerId}/certificates", $multipart);
        return $response;
    }

    /**
     * Send message to partner (convenience method)
     */
    public function sendMessage($partner, string $content, string $subject = '', array $options = []): \AS2aaS\Models\Message
    {
        $partnerId = $partner instanceof Partner ? $partner->getId() : $partner;
        
        // This would typically delegate to the Messages module
        // but included here for the fluent API as mentioned in spec
        $messages = new Messages($this->httpClient);
        return $messages->send($partnerId, $content, $subject, $options);
    }

    /**
     * Get partner health status
     */
    public function getHealth(string $partnerId): array
    {
        $response = $this->httpClient->get("partners/{$partnerId}/health");
        return $response;
    }

    /**
     * Search partners
     */
    public function search(string $query): array
    {
        return $this->list(['search' => $query]);
    }
}
