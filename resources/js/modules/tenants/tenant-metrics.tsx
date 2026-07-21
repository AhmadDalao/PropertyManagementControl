import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { TenantInsights } from './types';

export function TenantMetrics({ insights }: { insights: TenantInsights }) {
    const { t } = useTranslator();
    const profileGaps = insights.missing_emergency + insights.missing_address;

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('tenants.tenant_profiles'),
                    value: insights.total,
                    detail: t('tenants.company_profiles', undefined, {
                        count: insights.companies,
                    }),
                    icon: 'bi-people',
                    tone: 'ink',
                },
                {
                    label: t('tenants.active_profiles'),
                    value: insights.active,
                    detail: t('tenants.active_profiles_help'),
                    icon: 'bi-person-check',
                    tone: 'teal',
                },
                {
                    label: t('tenants.without_active_lease'),
                    value: insights.without_active_lease,
                    detail: t('tenants.without_active_lease_help'),
                    icon: 'bi-file-earmark-x',
                    tone: insights.without_active_lease > 0 ? 'amber' : 'blue',
                },
                {
                    label: t('tenants.profile_gaps_label'),
                    value: profileGaps,
                    detail: t('tenants.profile_gaps', undefined, {
                        emergency: insights.missing_emergency,
                        address: insights.missing_address,
                    }),
                    icon: 'bi-person-exclamation',
                    tone: profileGaps > 0 ? 'red' : 'amber',
                },
            ]}
        />
    );
}
