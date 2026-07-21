<?php

namespace App\Modules\Search\Presenters;

use App\Modules\Shared\ResourcePresenter;

class SearchResultPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array{group:string,title:string,subtitle:string,badge:string,url:string} */
    public function result(
        string $group,
        ?string $title,
        ?string $subtitle,
        ?string $badge,
        string $url,
    ): array {
        return [
            'group' => $group,
            'title' => $title ?: trans('app.search.untitled'),
            'subtitle' => $subtitle ?: '',
            'badge' => $badge ?: '',
            'url' => $url,
        ];
    }

    public function localized(?string $english, ?string $arabic): ?string
    {
        return $this->resources->localized($english, $arabic);
    }

    public function status(?string $status): string
    {
        if ($status === null || $status === '') {
            return '';
        }

        $key = "app.status.{$status}";

        return trans()->has($key)
            ? trans($key)
            : str($status)->replace('_', ' ')->headline()->toString();
    }
}
