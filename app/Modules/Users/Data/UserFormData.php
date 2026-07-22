<?php

namespace App\Modules\Users\Data;

use App\Models\User;

final readonly class UserFormData
{
    /** @param array<string, mixed> $defaults */
    public function __construct(
        public User $actor,
        public ?User $target = null,
        public array $defaults = [],
    ) {}
}
