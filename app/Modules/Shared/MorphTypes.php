<?php

namespace App\Modules\Shared;

use Illuminate\Database\Eloquent\Model;

class MorphTypes
{
    /**
     * @return array<int, string>
     */
    public function for(Model $model): array
    {
        return array_values(array_unique([$model::class, $model->getMorphClass()]));
    }
}
