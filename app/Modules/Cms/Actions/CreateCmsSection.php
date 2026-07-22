<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsSection;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Cms\Support\CmsInputGuard;
use Illuminate\Support\Facades\DB;

final class CreateCmsSection
{
    public function __construct(
        private readonly CmsAccess $access,
        private readonly CmsInputGuard $input,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): CmsSection
    {
        $this->access->ensureAdmin($actor);
        $data = $this->input->section($data);

        return DB::transaction(fn (): CmsSection => CmsSection::query()->create($data), 3);
    }
}
