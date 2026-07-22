<?php

namespace App\Modules\ShowcaseData\Generators;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Lease;
use App\Models\Portfolio;
use App\Models\ShowcaseDataset;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\ShowcaseData\Support\ShowcasePdf;
use Illuminate\Support\Facades\Storage;

class ShowcaseDocumentBuilder
{
    public function __construct(
        private readonly ShowcasePdf $pdf,
    ) {}

    /** @param list<array{lease:Lease, tenant:TenantProfile, unit:Asset, user:User}> $records */
    public function build(
        ShowcaseDataset $dataset,
        Portfolio $portfolio,
        User $manager,
        array $records,
    ): void {
        foreach ($records as $record) {
            foreach (['lease_contract', 'signed_contract'] as $type) {
                $path = "showcase/{$dataset->key}/documents/{$record['lease']->code}-{$type}.pdf";
                $content = $this->pdf->make("{$record['lease']->code} {$type}");
                Storage::disk('local')->put($path, $content);
                Document::query()->updateOrCreate(
                    [
                        'portfolio_id' => $portfolio->id,
                        'documentable_type' => $record['lease']->getMorphClass(),
                        'documentable_id' => $record['lease']->id,
                        'type' => $type,
                    ],
                    [
                        'uploaded_by_user_id' => $manager->id,
                        'title_en' => ucfirst(str_replace('_', ' ', $type))." {$record['lease']->code}",
                        'title_ar' => ($type === 'signed_contract' ? 'العقد الموقع ' : 'عقد الإيجار ').$record['lease']->code,
                        'disk' => 'local',
                        'file_path' => $path,
                        'original_name' => basename($path),
                        'mime_type' => 'application/pdf',
                        'file_size' => strlen($content),
                        'is_public' => DocumentOptions::canShowInPortal('lease', $type),
                        'meta_json' => ['showcase' => true, 'dataset_key' => $dataset->key],
                    ],
                );
            }
        }
    }
}
