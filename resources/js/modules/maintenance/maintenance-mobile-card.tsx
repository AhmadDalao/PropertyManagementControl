import type { MobileTableConfig } from '@/components/data-table';
import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import {
    MaintenanceActions,
    MaintenancePriority,
} from './maintenance-table-cells';
import type { MaintenanceRecord, MaintenanceTableProps } from './types';

export function useMaintenanceMobileCard(
    props: MaintenanceTableProps,
): MobileTableConfig<MaintenanceRecord> {
    const { locale, t } = useTranslator();

    return {
        title: (request) => `#${request.id} ${request.title}`,
        subtitle: (request) => (
            <div className="pmc-maintenance-mobile-summary">
                <span>{t(`status.${request.category}`)}</span>
                <div className="pmc-maintenance-mobile-state">
                    <span className="visually-hidden">
                        {t('maintenance.status')}
                    </span>
                    <StatusBadge value={request.status} />
                    <MaintenancePriority request={request} />
                    {request.awaiting_confirmation ? (
                        <StatusBadge
                            value="pending"
                            tone="warning"
                            label={t('maintenance.pending_confirmation')}
                        />
                    ) : null}
                    {request.is_overdue ? (
                        <StatusBadge value="overdue" tone="danger" />
                    ) : null}
                </div>
            </div>
        ),
        meta: [
            {
                label: t('maintenance.asset_tenant'),
                value: (request) =>
                    [
                        locale === 'ar'
                            ? request.asset?.title_ar || request.asset?.title_en
                            : request.asset?.title_en ||
                              request.asset?.title_ar,
                        request.tenant_profile?.user?.name,
                    ]
                        .filter(Boolean)
                        .join(' · ') || t('maintenance.no_asset'),
            },
            {
                label: t('maintenance.assignment'),
                value: (request) =>
                    request.assigned_to?.name ??
                    t('maintenance.unassigned_label'),
            },
        ],
        actions: (request) => (
            <MaintenanceActions request={request} mode={props.mode} />
        ),
    };
}
