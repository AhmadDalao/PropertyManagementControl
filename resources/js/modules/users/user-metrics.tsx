import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { UserInsights } from './types';

export function UserMetrics({ insights }: { insights: UserInsights }) {
    const { t } = useTranslator();

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('users.accounts'),
                    value: insights.total,
                    detail: t('users.accounts_help'),
                    icon: 'bi-people',
                    tone: 'ink',
                },
                {
                    label: t('users.active_accounts'),
                    value: insights.active,
                    detail: t('users.active_accounts_help'),
                    icon: 'bi-person-check',
                    tone: 'teal',
                },
                {
                    label: t('users.suspended_accounts'),
                    value: insights.suspended,
                    detail: t('users.suspended_accounts_help'),
                    icon: 'bi-person-slash',
                    tone: insights.suspended > 0 ? 'red' : 'amber',
                },
                {
                    label: t('users.password_actions'),
                    value: insights.temporary_passwords,
                    detail: t('users.profile_gap_summary', undefined, {
                        count: insights.tenants_without_profile,
                    }),
                    icon: 'bi-key',
                    tone: insights.temporary_passwords > 0 ? 'amber' : 'blue',
                },
            ]}
        />
    );
}
