<?php

declare(strict_types=1);

namespace AS2aaS\Modules;

/**
 * Utils module - Helper utilities and convenience methods
 */
class Utils extends BaseModule
{
    public function validateEDI(string $content, array $options = []): array
    {
        $data = [
            'content' => base64_encode($content),
            'strict' => $options['strict'] ?? false,
        ];

        if (isset($options['version'])) {
            $data['version'] = $options['version'];
        }

        return $this->httpClient->post('utils/validate-edi', $data);
    }

    public function detectContentType(string $content, ?string $filename = null): string
    {
        // Try filename first
        if ($filename) {
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $type = match ($extension) {
                'edi', 'x12' => 'application/edi-x12',
                'xml' => 'application/xml',
                'json' => 'application/json',
                'pdf' => 'application/pdf',
                'txt' => 'text/plain',
                'csv' => 'text/csv',
                default => null,
            };

            if ($type) {
                return $type;
            }
        }

        // Analyze content
        $trimmed = trim($content);

        if (str_starts_with($trimmed, 'ISA')) {
            return 'application/edi-x12';
        }

        if (str_starts_with($trimmed, 'UNA') || str_starts_with($trimmed, 'UNB')) {
            return 'application/edifact';
        }

        if (str_starts_with($trimmed, '<?xml') || str_starts_with($trimmed, '<')) {
            return 'application/xml';
        }

        if ($this->isJson($content)) {
            return 'application/json';
        }

        return 'application/octet-stream';
    }

    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 1) . ' ' . $units[$pow];
    }

    public function generateAs2Id(string $companyName): string
    {
        // Remove common company suffixes
        $name = preg_replace('/\b(inc|corp|corporation|llc|ltd|limited)\b/i', '', $companyName);
        
        // Convert to uppercase and replace spaces/special chars with hyphens
        $as2Id = strtoupper(trim($name));
        $as2Id = preg_replace('/[^A-Z0-9]+/', '-', $as2Id);
        $as2Id = trim($as2Id, '-');
        
        // Add AS2 suffix if not already present
        if (!str_ends_with($as2Id, '-AS2')) {
            $as2Id .= '-AS2';
        }

        return $as2Id;
    }

    private function isJson(string $content): bool
    {
        json_decode($content);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
