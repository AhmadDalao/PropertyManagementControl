import { Link } from '@inertiajs/react';

import { DataTable } from '@/components/data-table';
import type {
    MobileTableConfig,
    TableColumn,
    TableFilterField,
} from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import { RentRollState } from './rent-roll-state';
import type { RentRollPageProps, RentRollRecord } from './rent-roll-types';

export function RentRollRecords({ props }: { props: RentRollPageProps }) {
    const { locale, t } = useTranslator();
    const columns: Array<TableColumn<RentRollRecord>> = [
        {
            key: 'space',
            label: t('reports.rent_roll_space'),
            render: (record) => <Space record={record} locale={locale} />,
        },
        {
            key: 'tenant',
            label: t('reports.rent_roll_tenant_lease'),
            render: (record) => <TenantLease record={record} />,
        },
        {
            key: 'status',
            label: t('reports.rent_roll_state'),
            render: (record) => <RentRollState state={record.state} />,
        },
        {
            key: 'rent',
            label: t('reports.rent_roll_contract_rent'),
            className: 'pmc-table-number',
            render: (record) =>
                record.lease ? (
                    <Money
                        value={currency(
                            record.lease.rent_amount,
                            locale,
                            record.lease.currency,
                        )}
                        detail={t(
                            `leases.frequency_${record.lease.payment_frequency}`,
                        )}
                    />
                ) : (
                    <span className="pmc-rent-roll-empty-value">-</span>
                ),
        },
        {
            key: 'balance',
            label: t('reports.rent_roll_balance'),
            className: 'pmc-table-number',
            render: (record) =>
                record.lease ? (
                    <Money
                        value={currency(
                            record.lease.balance,
                            locale,
                            record.lease.currency,
                        )}
                        detail={t('reports.rent_roll_due_detail', undefined, {
                            paid: currency(
                                record.lease.total_paid,
                                locale,
                                record.lease.currency,
                            ),
                            overdue: currency(
                                record.lease.overdue,
                                locale,
                                record.lease.currency,
                            ),
                        })}
                        risk={record.lease.overdue > 0}
                    />
                ) : (
                    <span className="pmc-rent-roll-empty-value">-</span>
                ),
        },
        {
            key: 'end',
            label: t('reports.rent_roll_end_date'),
            render: (record) => <LeaseEnd record={record} />,
        },
        {
            key: 'actions',
            label: t('common.actions'),
            render: (record) => <Actions record={record} />,
        },
    ];
    const filterFields: TableFilterField[] = [
        {
            name: 'state',
            label: t('reports.rent_roll_state'),
            options: props.stateOptions.map((state) => ({
                value: state,
                label: t(`reports.rent_roll_state_${state}`),
            })),
        },
        {
            name: 'portfolio_id',
            label: t('reports.portfolio'),
            clears: ['property_id'],
            options: [
                { value: 'all', label: t('reports.all_portfolios') },
                ...props.portfolioOptions.map((portfolio) => ({
                    value: portfolio.id,
                    label: portfolio.name,
                })),
            ],
        },
        {
            name: 'property_id',
            label: t('reports.property'),
            options: [
                { value: 'all', label: t('reports.all_properties') },
                ...props.propertyOptions.map((property) => ({
                    value: property.id,
                    label: property.name,
                })),
            ],
        },
    ];
    const mobile: MobileTableConfig<RentRollRecord> = {
        title: (record) => <Space record={record} locale={locale} />,
        subtitle: (record) =>
            record.lease?.tenant ?? t('reports.rent_roll_available'),
        status: (record) => <RentRollState state={record.state} />,
        meta: [
            {
                label: t('reports.rent_roll_contract_rent'),
                value: (record) =>
                    record.lease
                        ? currency(
                              record.lease.rent_amount,
                              locale,
                              record.lease.currency,
                          )
                        : '-',
            },
            {
                label: t('reports.rent_roll_outstanding'),
                value: (record) =>
                    record.lease
                        ? currency(
                              record.lease.balance,
                              locale,
                              record.lease.currency,
                          )
                        : '-',
            },
            {
                label: t('reports.rent_roll_end_date'),
                value: (record) =>
                    record.lease
                        ? humanDate(record.lease.ends_at, locale)
                        : '-',
            },
        ],
        actions: (record) => <Actions record={record} />,
    };

    return (
        <DataTable
            title={t('reports.rent_roll_records_title')}
            description={t('reports.rent_roll_records_description')}
            data={props.records}
            columns={columns}
            filters={props.filters}
            basePath="/reports/rent-roll"
            exportHref={props.downloads.xlsx}
            counts={props.counts}
            filterFields={filterFields}
            emptyText={t('reports.rent_roll_no_matches')}
            rowHref={(record) => record.links.asset}
            mobileCard={mobile}
        />
    );
}

function Space({ record, locale }: { record: RentRollRecord; locale: string }) {
    const title = localized(record, locale);
    const path = record.hierarchy
        .slice(0, -1)
        .map((item) => localized(item, locale))
        .filter(Boolean)
        .join(' / ');

    return (
        <span className="pmc-rent-roll-space">
            <strong>{title}</strong>
            <small>
                {record.code}
                {path ? ` · ${path}` : ''}
            </small>
        </span>
    );
}

function TenantLease({ record }: { record: RentRollRecord }) {
    const { t } = useTranslator();

    return record.lease ? (
        <span className="pmc-rent-roll-tenant">
            <strong>{record.lease.tenant}</strong>
            <small>{record.lease.code}</small>
        </span>
    ) : (
        <span className="pmc-rent-roll-tenant is-vacant">
            <strong>{t('reports.rent_roll_available')}</strong>
            <small>{t('reports.rent_roll_no_active_lease')}</small>
        </span>
    );
}

function Money({
    value,
    detail,
    risk = false,
}: {
    value: string;
    detail: string;
    risk?: boolean;
}) {
    return (
        <span className={`pmc-rent-roll-money ${risk ? 'is-risk' : ''}`}>
            <strong>{value}</strong>
            <small>{detail}</small>
        </span>
    );
}

function LeaseEnd({ record }: { record: RentRollRecord }) {
    const { locale, t } = useTranslator();

    if (!record.lease) {
        return <span className="pmc-rent-roll-empty-value">-</span>;
    }

    const days = record.lease.days_remaining ?? 0;

    return (
        <span className="pmc-rent-roll-end">
            <strong>{humanDate(record.lease.ends_at, locale)}</strong>
            <small>
                {days >= 0
                    ? t('reports.rent_roll_days_left', undefined, {
                          count: days,
                      })
                    : t('reports.rent_roll_expired_days', undefined, {
                          count: Math.abs(days),
                      })}
            </small>
        </span>
    );
}

function Actions({ record }: { record: RentRollRecord }) {
    const { t } = useTranslator();

    return (
        <div className="pmc-record-actions">
            <Link href={record.links.asset} className="pmc-record-open">
                {t('reports.rent_roll_open_asset')}
                <i className="bi bi-arrow-up-right" aria-hidden="true" />
            </Link>
            {record.links.lease ? (
                <Link
                    href={record.links.lease}
                    className="btn btn-outline-secondary btn-sm"
                >
                    <i className="bi bi-file-earmark-text" aria-hidden="true" />
                    <span>{t('reports.rent_roll_open_lease')}</span>
                </Link>
            ) : null}
        </div>
    );
}

function localized(
    record: { title_en?: string | null; title_ar?: string | null },
    locale: string,
): string {
    return locale === 'ar'
        ? record.title_ar || record.title_en || ''
        : record.title_en || record.title_ar || '';
}
