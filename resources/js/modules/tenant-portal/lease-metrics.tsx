import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate, localizedNumber } from '@/lib/utils';

import type { TenantLease } from './types';

export function LeaseMetrics({ lease }: { lease: TenantLease }) {
    const { locale, t } = useTranslator();

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('tenant_portal.days_left'),
                    value: localizedNumber(
                        Math.max(0, lease.days_remaining ?? 0),
                        locale,
                    ),
                    detail: humanDate(lease.ends_at, locale),
                    icon: 'bi-calendar3',
                    tone: 'ink',
                },
                {
                    label: t('tenant_portal.total_paid'),
                    value: currency(lease.total_paid, locale, lease.currency),
                    detail: t('tenant_portal.contract_to_date'),
                    icon: 'bi-check2-circle',
                    tone: 'teal',
                },
                {
                    label: t('tenant_portal.outstanding'),
                    value: currency(
                        lease.balance_remaining,
                        locale,
                        lease.currency,
                    ),
                    detail: t('tenant_portal.manual_payment_note'),
                    icon: 'bi-wallet2',
                    tone: lease.overdue > 0 ? 'red' : 'amber',
                },
                {
                    label: t('tenant_portal.next_due'),
                    value: currency(lease.due_now, locale, lease.currency),
                    detail: humanDate(lease.next_due_date, locale),
                    icon: 'bi-calendar-check',
                    tone: 'blue',
                },
            ]}
        />
    );
}
