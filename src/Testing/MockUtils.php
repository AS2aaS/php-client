<?php

declare(strict_types=1);

namespace AS2aaS\Testing;

/**
 * Mock Utils module
 */
class MockUtils
{
    public function detectContentType(string $content, ?string $filename = null): string
    {
        if ($filename) {
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            return match ($extension) {
                'edi', 'x12' => 'application/edi-x12',
                'xml' => 'application/xml',
                'json' => 'application/json',
                'pdf' => 'application/pdf',
                'txt' => 'text/plain',
                default => 'application/octet-stream',
            };
        }

        $trimmed = trim($content);
        
        if (str_starts_with($trimmed, 'ISA')) {
            return 'application/edi-x12';
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
        $name = preg_replace('/\b(inc|corp|corporation|llc|ltd|limited)\b/i', '', $companyName);
        $as2Id = strtoupper(trim($name));
        $as2Id = preg_replace('/[^A-Z0-9]+/', '-', $as2Id);
        $as2Id = trim($as2Id, '-');
        
        if (!str_ends_with($as2Id, '-AS2')) {
            $as2Id .= '-AS2';
        }

        return $as2Id;
    }

    public function validateEDI(string $content, array $options = []): array
    {
        $isEDI = str_starts_with(trim($content), 'ISA');
        
        return [
            'valid' => $isEDI,
            'format' => $isEDI ? 'EDI X12' : 'Unknown',
            'segments' => $isEDI ? 10 : 0,
            'errors' => $isEDI ? [] : ['Invalid EDI format'],
            'mock' => true
        ];
    }

    private function isJson(string $content): bool
    {
        json_decode($content);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
