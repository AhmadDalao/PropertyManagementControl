import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, localizedNumber, percent } from '@/lib/utils';

import type { PropertyExplorerPayload } from './types';

export function ExplorerSummary({
    explorer,
}: {
    explorer: PropertyExplorerPayload;
}) {
    const { locale, t } = useTranslator();
    const { metrics, selected } = explorer;
    const occupancy =
        (metrics.rentable ?? 0) > 0
            ? ((metrics.occupied ?? 0) / (metrics.rentable ?? 1)) * 100
            : 0;

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('assets.explorer.structure'),
                    value: localizedNumber(metrics.assets ?? 0, locale),
                    detail: t('assets.explorer.structure_detail', undefined, {
                        floors: localizedNumber(metrics.floors ?? 0, locale),
                        units: localizedNumber(metrics.units ?? 0, locale),
                    }),
                    icon: 'bi-diagram-3',
                    tone: 'ink',
                },
                {
                    label: t('assets.explorer.occupancy_rate'),
                    value: percent(occupancy, locale),
                    detail: t('assets.explorer.occupancy_detail', undefined, {
                        occupied: localizedNumber(
                            metrics.occupied ?? 0,
                            locale,
                        ),
                        rentable: localizedNumber(
                            metrics.rentable ?? 0,
                            locale,
                        ),
                    }),
                    icon: 'bi-building-check',
                    tone: 'teal',
                },
                {
                    label: t('assets.explorer.active_tenancies'),
                    value: localizedNumber(metrics.active_leases ?? 0, locale),
                    detail: t('assets.explorer.tenant_count', undefined, {
                        count: localizedNumber(metrics.tenants ?? 0, locale),
                    }),
                    icon: 'bi-people',
                    tone: 'blue',
                },
                {
                    label: t('assets.explorer.arrears'),
                    value: currency(
                        metrics.arrears ?? 0,
                        locale,
                        selected?.currency,
                    ),
                    detail: t('assets.explorer.vacancy_detail', undefined, {
                        vacant: localizedNumber(metrics.vacant ?? 0, locale),
                        maintenance: localizedNumber(
                            metrics.maintenance ?? 0,
                            locale,
                        ),
                    }),
                    icon: 'bi-cash-stack',
                    tone: (metrics.arrears ?? 0) > 0 ? 'amber' : 'teal',
                },
            ]}
        />
    );
}
