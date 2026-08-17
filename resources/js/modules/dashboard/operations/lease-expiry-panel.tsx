import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import { DashboardRecordList } from '../shared/record-list';
import type { OperationsDashboardProps } from '../types';
import { propertyFocusUrl } from './property-focus-url';

export function LeaseExpiryPanel({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { locale, t, text } = useTranslator();
    const propertyId = props.propertyFocus.selected?.id;

    return (
        <WorkspacePanel
            eyebrow={t('dashboard.contracts')}
            title={t('dashboard.lease_expiry')}
            description={t('dashboard.lease_expiry_description')}
            action={{
                label: t('dashboard.open_expiry_report'),
                href: propertyFocusUrl('/lease-renewals?queue=all', propertyId),
            }}
        >
            <DashboardRecordList
                empty={t('dashboard.no_expiring_leases')}
                rows={props.expiringLeases.slice(0, 4).map((lease) => ({
                    href: `/leases/${lease.id}`,
                    title: lease.code,
                    meta: `${lease.tenant ?? text('No tenant')} · ${lease.asset ?? text('No asset')}`,
                    value: t('dashboard.days_count', undefined, {
                        count: localizedNumber(
                            lease.days_remaining ?? 0,
                            locale,
                        ),
                    }),
                    tone:
                        Number(lease.days_remaining ?? 0) <= 30
                            ? 'danger'
                            : 'warning',
                }))}
            />
        </WorkspacePanel>
    );
}
