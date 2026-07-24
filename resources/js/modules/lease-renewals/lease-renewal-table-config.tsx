import type { MobileTableConfig, TableColumn } from '@/components/data-table';
import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';
import { currency, humanDate, localizedNumber } from '@/lib/utils';

import { useLeaseRenewalCells } from './lease-renewal-cells';
import {
    renewalEndTiming,
    renewalNoticeLabel,
    renewalStateLabel,
    renewalStateTone,
} from './lease-renewal-labels';
import type { LeaseRenewalRecord } from './types';

export function useLeaseRenewalTableConfig(locale: string): {
    columns: Array<TableColumn<LeaseRenewalRecord>>;
    mobileCard: MobileTableConfig<LeaseRenewalRecord>;
} {
    const { t } = useTranslator();
    const cells = useLeaseRenewalCells(locale);
    const columns: Array<TableColumn<LeaseRenewalRecord>> = [
        {
            key: 'lease',
            label: t('lease_renewals.lease'),
            render: (lease) => (
                <div className="pmc-primary-cell">
                    <strong>{lease.code}</strong>
                    <span>
                        {t(`status.${lease.status}` as UiTranslationKey)}
                    </span>
                    <StatusBadge
                        value={lease.renewal_state}
                        label={renewalStateLabel(lease, t)}
                        tone={renewalStateTone(lease.renewal_state)}
                    />
                </div>
            ),
        },
        {
            key: 'tenant',
            label: t('lease_renewals.tenant'),
            render: cells.tenant,
        },
        {
            key: 'property',
            label: t('lease_renewals.property_asset'),
            render: cells.propertyAsset,
        },
        {
            key: 'end_date',
            label: t('lease_renewals.end_date'),
            render: (lease) => (
                <div className="pmc-stacked-cell">
                    <strong>{humanDate(lease.ends_at, locale)}</strong>
                    <span>{renewalEndTiming(lease, t, locale)}</span>
                </div>
            ),
        },
        {
            key: 'notice',
            label: t('lease_renewals.notice_plan'),
            render: (lease) => (
                <div className="pmc-stacked-cell">
                    <strong>{renewalNoticeLabel(lease, t, locale)}</strong>
                    <span>
                        {t('lease_renewals.notice_days', undefined, {
                            count: localizedNumber(
                                lease.renewal_notice_days,
                                locale,
                            ),
                        })}
                    </span>
                </div>
            ),
        },
        {
            key: 'balance',
            label: t('lease_renewals.balance'),
            render: (lease) => (
                <div className="pmc-stacked-cell">
                    <strong>
                        {currency(
                            lease.outstanding_amount,
                            locale,
                            lease.currency,
                        )}
                    </strong>
                    <span>
                        {t('lease_renewals.overdue_installments', undefined, {
                            count: localizedNumber(
                                lease.overdue_installments_count,
                                locale,
                            ),
                        })}
                    </span>
                </div>
            ),
        },
        {
            key: 'actions',
            label: t('lease_renewals.actions'),
            className: 'text-end',
            render: cells.actions,
        },
    ];

    return {
        columns,
        mobileCard: {
            title: (lease) => lease.code,
            subtitle: cells.tenant,
            status: (lease) => (
                <StatusBadge
                    value={lease.renewal_state}
                    label={renewalStateLabel(lease, t)}
                    tone={renewalStateTone(lease.renewal_state)}
                />
            ),
            meta: [
                {
                    label: t('lease_renewals.property_asset'),
                    value: cells.propertyAsset,
                },
                {
                    label: t('lease_renewals.end_date'),
                    value: (lease) =>
                        `${humanDate(lease.ends_at, locale)} · ${renewalEndTiming(lease, t, locale)}`,
                },
                {
                    label: t('lease_renewals.balance'),
                    value: (lease) =>
                        currency(
                            lease.outstanding_amount,
                            locale,
                            lease.currency,
                        ),
                },
            ],
            actions: cells.renewalAction,
        },
    };
}
