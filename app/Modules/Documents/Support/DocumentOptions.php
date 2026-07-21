<?php

namespace App\Modules\Documents\Support;

final class DocumentOptions
{
    /** @var array<int, string> */
    public const ATTACHMENTS = ['lease', 'asset', 'payment'];

    /** @var array<int, string> */
    public const TYPES = [
        'lease_contract',
        'signed_contract',
        'receipt',
        'owner_report',
        'tenant_statement',
        'identity_document',
        'other',
    ];

    /** @var array<int, string> */
    public const VISIBILITIES = ['public', 'private'];

    /** @var array<string, array<int, string>> */
    private const PORTAL_TYPES = [
        'lease' => ['lease_contract', 'signed_contract', 'tenant_statement'],
        'payment' => ['receipt'],
        'asset' => [],
    ];

    public static function canShowInPortal(string $attachment, string $type): bool
    {
        return in_array($type, self::PORTAL_TYPES[$attachment] ?? [], true);
    }

    /** @return array<int, string> */
    public static function portalTypes(string $attachment): array
    {
        return self::PORTAL_TYPES[$attachment] ?? [];
    }

    private function __construct() {}
}
