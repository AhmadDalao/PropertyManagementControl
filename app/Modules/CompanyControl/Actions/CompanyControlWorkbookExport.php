<?php

namespace App\Modules\CompanyControl\Actions;

use App\Modules\Exports\Support\XlsxWorkbook;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class CompanyControlWorkbookExport
{
    public function __construct(private XlsxWorkbook $workbook) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function download(array $data): BinaryFileResponse
    {
        $path = $this->workbook->createSheets([
            ['name' => 'Summary', 'rows' => $this->summaryRows($data)],
            ['name' => 'Portfolios', 'rows' => $this->portfolioRows($data['portfolios'])],
            ['name' => 'Financial', 'rows' => $this->financialRows($data['portfolios'])],
            ['name' => 'Valuation', 'rows' => $this->valuationRows($data['portfolios'])],
        ]);

        return response()->download(
            $path,
            'company-control-'.now()->format('Ymd-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, mixed>>
     */
    private function summaryRows(array $data): array
    {
        $summary = $data['summary'];

        return [
            [trans('app.company_control.export_title')],
            [trans('app.company_control.export_generated'), now()->toIso8601String()],
            [trans('app.company_control.data_source'), trans('app.company_control.source_'.$data['filters']['data_source'])],
            [trans('app.company_control.status'), trans('app.company_control.status_'.$data['filters']['status'])],
            [],
            [trans('app.company_control.metric'), trans('app.company_control.value')],
            [trans('app.company_control.portfolios_in_view'), $summary['portfolios']],
            [trans('app.company_control.needs_action'), $summary['needs_action']],
            [trans('app.company_control.properties'), $summary['properties']],
            [trans('app.company_control.active_accounts'), $summary['active_accounts']],
            [trans('app.company_control.occupancy'), $summary['occupancy_rate']],
            [trans('app.company_control.open_requests'), $summary['open_requests']],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $portfolios
     * @return array<int, array<int, mixed>>
     */
    private function portfolioRows(array $portfolios): array
    {
        $rows = [[
            trans('app.company_control.code'),
            trans('app.company_control.portfolio'),
            trans('app.company_control.status'),
            trans('app.company_control.data_source'),
            trans('app.company_control.owner'),
            trans('app.company_control.health'),
            trans('app.company_control.readiness'),
            trans('app.company_control.properties'),
            trans('app.company_control.rentable_units'),
            trans('app.company_control.occupied_units'),
            trans('app.company_control.occupancy'),
            trans('app.company_control.active_leases'),
            trans('app.company_control.expiring_leases'),
            trans('app.company_control.open_requests'),
            trans('app.company_control.active_accounts'),
            trans('app.company_control.managers'),
            trans('app.company_control.tenants'),
        ]];

        foreach ($portfolios as $portfolio) {
            $rows[] = [
                $portfolio['code'],
                app()->isLocale('ar') ? $portfolio['name_ar'] : $portfolio['name_en'],
                trans('app.status.'.$portfolio['status']),
                trans('app.company_control.source_'.($portfolio['is_showcase'] ? 'showcase' : 'live')),
                $portfolio['owner']['name'] ?? '',
                trans('app.company_control.attention_'.$portfolio['attention']),
                $portfolio['readiness']['score'],
                $portfolio['properties'],
                $portfolio['rentable_units'],
                $portfolio['occupied_units'],
                $portfolio['occupancy_rate'],
                $portfolio['active_leases'],
                $portfolio['expiring_leases'],
                $portfolio['open_requests'],
                $portfolio['accounts']['active'],
                $portfolio['accounts']['managers'],
                $portfolio['accounts']['tenants'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $portfolios
     * @return array<int, array<int, mixed>>
     */
    private function financialRows(array $portfolios): array
    {
        $rows = [[
            trans('app.company_control.code'),
            trans('app.company_control.portfolio'),
            trans('app.company_control.currency'),
            trans('app.company_control.scheduled_due'),
            trans('app.company_control.scheduled_paid'),
            trans('app.company_control.collection'),
            trans('app.company_control.arrears'),
            trans('app.company_control.collected'),
            trans('app.company_control.expenses'),
            trans('app.company_control.net_cash_flow'),
        ]];

        foreach ($portfolios as $portfolio) {
            foreach ($portfolio['currency_totals'] as $position) {
                $rows[] = [
                    $portfolio['code'],
                    app()->isLocale('ar') ? $portfolio['name_ar'] : $portfolio['name_en'],
                    $position['currency'],
                    $position['scheduled_due'],
                    $position['scheduled_paid'],
                    $position['collection_rate'],
                    $position['arrears'],
                    $position['collected'],
                    $position['expenses'],
                    $position['net'],
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $portfolios
     * @return array<int, array<int, mixed>>
     */
    private function valuationRows(array $portfolios): array
    {
        $rows = [[
            trans('app.company_control.code'),
            trans('app.company_control.portfolio'),
            trans('app.company_control.currency'),
            trans('app.company_control.valuation'),
        ]];

        foreach ($portfolios as $portfolio) {
            foreach ($portfolio['valuation_totals'] as $position) {
                $rows[] = [
                    $portfolio['code'],
                    app()->isLocale('ar') ? $portfolio['name_ar'] : $portfolio['name_en'],
                    $position['currency'],
                    $position['amount'],
                ];
            }
        }

        return $rows;
    }
}
