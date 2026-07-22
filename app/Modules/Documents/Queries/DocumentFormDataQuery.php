<?php

namespace App\Modules\Documents\Queries;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Data\DocumentFormData;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentAttachmentResolver;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Documents\Support\DocumentRules;

final class DocumentFormDataQuery
{
    public function __construct(
        private readonly DocumentAccess $access,
        private readonly DocumentAttachmentResolver $resolver,
        private readonly DocumentAttachments $attachments,
    ) {}

    /** @param array<string, mixed> $defaults */
    public function get(User $actor, ?Document $document = null, array $defaults = []): DocumentFormData
    {
        if ($document) {
            $this->access->ensureCanManage($actor, $document);
            $document->loadMissing('documentable');
            $alias = $this->attachments->aliasForDocument($document) ?? 'lease';

            return new DocumentFormData(
                actor: $actor,
                document: $document,
                defaults: [],
                attachmentAlias: $alias,
                attachmentId: (int) $document->documentable_id,
                type: $document->type,
                attachment: $this->attachments->present($document->documentable),
            );
        }

        $this->access->ensureManager($actor);
        $defaults = DocumentRules::normalize($defaults);
        $alias = in_array($defaults['documentable_type'] ?? null, DocumentOptions::ATTACHMENTS, true)
            ? (string) $defaults['documentable_type']
            : 'lease';
        $attachmentId = $this->id($defaults['documentable_id'] ?? null);
        $type = in_array($defaults['type'] ?? null, DocumentOptions::TYPES, true)
            ? (string) $defaults['type']
            : 'signed_contract';
        $attachment = null;

        if ($attachmentId) {
            $attachment = $this->attachments->present(
                $this->resolver->resolve($actor, $alias, $attachmentId),
            );
        }

        return new DocumentFormData(
            actor: $actor,
            document: null,
            defaults: $defaults,
            attachmentAlias: $alias,
            attachmentId: $attachmentId,
            type: $type,
            attachment: $attachment,
        );
    }

    private function id(mixed $value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id ? (int) $id : null;
    }
}
