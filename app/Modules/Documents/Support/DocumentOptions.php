<?php

namespace App\Modules\Documents\Support;

final class DocumentOptions
{
    /** @var array<int, string> */
    public const ATTACHMENTS = ['lease', 'asset', 'payment', 'expense'];

    /** @var array<int, string> */
    public const TYPES = [
        'lease_contract',
        'signed_contract',
        'receipt',
        'payment_proof',
        'owner_report',
        'tenant_statement',
        'termination_notice',
        'move_out_inspection',
        'identity_document',
        'other',
    ];

    /** @var array<int, string> */
    public const UPLOAD_TYPES = [
        'lease_contract',
        'signed_contract',
        'receipt',
        'owner_report',
        'tenant_statement',
        'termination_notice',
        'move_out_inspection',
        'identity_document',
        'other',
    ];

    /** @var array<int, string> */
    public const VISIBILITIES = ['public', 'private'];

    /** @var array<string, array<int, string>> */
    private const PORTAL_TYPES = [
        'lease' => [
            'lease_contract',
            'signed_contract',
            'tenant_statement',
            'termination_notice',
            'move_out_inspection',
        ],
        'payment' => ['receipt'],
        'asset' => [],
        'expense' => [],
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

    public static function isPaymentProof(string $type): bool
    {
        return $type === 'payment_proof';
    }

    public static function label(string $value): string
    {
        $key = "app.documents.options.{$value}";

        return trans()->has($key)
            ? trans($key)
            : str($value)->replace('_', ' ')->headline()->toString();
    }

    private function __construct() {}
}
