<?php

namespace App\Modules\Search\Contracts;

use App\Models\User;

interface SearchSource
{
    /** @return array<int, array{group:string,title:string,subtitle:string,badge:string,url:string}> */
    public function results(User $actor, string $query): array;

    public function directUrl(User $actor, string $query): ?string;
}
