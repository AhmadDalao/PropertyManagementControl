<?php

namespace App\Modules\Portfolios\Presenters;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioAccess;
use App\Modules\Portfolios\Support\PortfolioOptions;
use App\Modules\Shared\ResourcePresenter;
use App\Support\PortfolioModules;

class PortfolioFormPresenter
{
    public function __construct(
        private readonly PortfolioAccess $access,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, ?Portfolio $portfolio = null): array
    {
        if ($portfolio) {
            $this->access->ensureCanUpdate($actor, $portfolio);
        } else {
            $this->access->ensureCanCreate($actor);
        }

        $fields = [
            ['name' => 'name_en', 'label' => trans('app.portfolios.name_en'), 'required' => true],
            ['name' => 'name_ar', 'label' => trans('app.portfolios.name_ar'), 'required' => true],
        ];

        if (! $portfolio) {
            $fields[] = [
                'name' => 'code',
                'label' => trans('app.portfolios.code'),
                'help' => trans('app.portfolios.code_help'),
            ];
        }

        $fields = [
            ...$fields,
            [
                'name' => 'status',
                'label' => trans('app.portfolios.status'),
                'type' => 'select',
                'required' => true,
                'help' => trans('app.portfolios.status_help'),
                'options' => collect(
                    $portfolio
                        ? PortfolioOptions::updateStatuses($actor, $portfolio)
                        : PortfolioOptions::CREATION_STATUSES,
                )->map(fn (string $status): array => [
                    'value' => $status,
                    'label' => trans("app.status.{$status}"),
                ])->all(),
            ],
            [
                'name' => 'default_currency',
                'label' => trans('app.portfolios.default_currency'),
                'placeholder' => 'SAR',
                'required' => true,
                'help' => trans('app.portfolios.currency_help'),
            ],
            ['name' => 'contact_email', 'label' => trans('app.portfolios.contact_email'), 'type' => 'email'],
            ['name' => 'contact_phone', 'label' => trans('app.portfolios.contact_phone')],
            ['name' => 'city', 'label' => trans('app.portfolios.city')],
            ['name' => 'country', 'label' => trans('app.portfolios.country')],
            ['name' => 'address', 'label' => trans('app.portfolios.address_en'), 'type' => 'textarea', 'rows' => 2],
            ['name' => 'address_ar', 'label' => trans('app.portfolios.address_ar'), 'type' => 'textarea', 'rows' => 2],
            ...$this->moduleFields(),
        ];
        $moduleFieldNames = collect(PortfolioOptions::moduleKeys())
            ->map(fn (string $module): string => PortfolioOptions::moduleField($module))
            ->all();
        $fields = $this->resources->sectionFields($fields, [
            trans('app.portfolios.identity_section') => [
                'description' => trans('app.portfolios.identity_section_help'),
                'fields' => ['name_en', 'name_ar', 'code', 'status', 'default_currency'],
            ],
            trans('app.portfolios.contact_section') => [
                'description' => trans('app.portfolios.contact_section_help'),
                'fields' => ['contact_email', 'contact_phone'],
            ],
            trans('app.portfolios.location_section') => [
                'description' => trans('app.portfolios.location_section_help'),
                'fields' => ['city', 'country', 'address', 'address_ar'],
            ],
            trans('app.portfolios.modules_section') => [
                'description' => trans('app.portfolios.modules_section_help'),
                'fields' => $moduleFieldNames,
            ],
        ]);
        $moduleSettings = PortfolioModules::normalize($portfolio?->module_settings);
        $initialValues = $portfolio
            ? [
                'name_en' => $portfolio->name_en,
                'name_ar' => $portfolio->name_ar,
                'code' => $portfolio->code,
                'status' => $portfolio->status,
                'default_currency' => $portfolio->default_currency,
                'contact_email' => $portfolio->contact_email ?? '',
                'contact_phone' => $portfolio->contact_phone ?? '',
                'city' => $portfolio->city ?? '',
                'country' => $portfolio->country ?? 'Saudi Arabia',
                'address' => $portfolio->address ?? '',
                'address_ar' => $portfolio->address_ar ?? '',
            ]
            : [
                'name_en' => '',
                'name_ar' => '',
                'code' => '',
                'status' => 'active',
                'default_currency' => 'SAR',
                'contact_email' => '',
                'contact_phone' => '',
                'city' => '',
                'country' => 'Saudi Arabia',
                'address' => '',
                'address_ar' => '',
            ];

        foreach ($moduleSettings as $module => $enabled) {
            $initialValues[PortfolioOptions::moduleField($module)] = $enabled;
        }

        return [
            'title' => $portfolio
                ? trans('app.portfolios.edit_portfolio')
                : trans('app.portfolios.create_portfolio'),
            'description' => $portfolio
                ? trans('app.portfolios.edit_description')
                : trans('app.portfolios.create_description'),
            'backHref' => $portfolio ? route('portfolios.show', $portfolio) : route('portfolios.index'),
            'backLabel' => $portfolio
                ? trans('app.portfolios.portfolio_detail')
                : trans('app.portfolios.all_portfolios'),
            'action' => $portfolio ? route('portfolios.update', $portfolio) : route('portfolios.store'),
            'method' => $portfolio ? 'put' : 'post',
            'submitLabel' => $portfolio
                ? trans('app.portfolios.update_portfolio')
                : trans('app.portfolios.create_portfolio'),
            'fields' => $fields,
            'initialValues' => $initialValues,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function moduleFields(): array
    {
        return collect(PortfolioModules::definitions())
            ->map(fn (array $definition): array => [
                'name' => PortfolioOptions::moduleField((string) $definition['key']),
                'label' => (string) $definition['label'],
                'help' => (string) $definition['description'],
                'type' => 'checkbox',
            ])
            ->all();
    }
}
