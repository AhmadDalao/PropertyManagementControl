<?php

namespace App\Modules\Documents\Presenters;

use App\Modules\Documents\Data\DocumentDetailData;
use App\Modules\Shared\ResourcePresenter;

final class DocumentAccessPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<string, mixed> */
    public function section(DocumentDetailData $data): array
    {
        $document = $data->document;
        $portalVisible = $document->is_public && $data->portalEligible;
        $reviewStatus = data_get($document->meta_json, 'review_status');

        return [
            'key' => 'access',
            'title' => trans('app.documents.access_section'),
            'description' => trans('app.documents.access_section_help'),
            'items' => $this->resources->detailItems([
                ['label' => trans('app.documents.tenant_portal'), 'value' => $portalVisible ? trans('app.documents.visible') : trans('app.documents.internal')],
                ['label' => trans('app.documents.portal_eligibility'), 'value' => $data->portalEligible ? trans('app.documents.portal_eligible') : trans('app.documents.portal_management_only')],
                ['label' => trans('app.documents.authorized_audience'), 'value' => $portalVisible ? trans('app.documents.audience_tenant_management') : trans('app.documents.audience_management')],
                ['label' => trans('app.documents.storage_control'), 'value' => trans('app.documents.storage_private')],
                ['label' => trans('app.documents.download_control'), 'value' => trans('app.documents.download_authorized')],
                ['label' => trans('app.documents.proof_review'), 'value' => is_string($reviewStatus) ? trans("app.status.{$reviewStatus}") : null],
            ]),
        ];
    }
}
