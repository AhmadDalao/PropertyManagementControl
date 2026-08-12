import { Link } from '@inertiajs/react';

import type { MobileTableConfig, TableColumn } from '@/components/data-table';
import { RecordActions, StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';
import { currency, humanDate, localizedNumber } from '@/lib/utils';

import type { RentCollectionRecord } from './types';

export function useRentCollectionTableConfig(locale: string): {
    columns: Array<TableColumn<RentCollectionRecord>>;
    mobileCard: MobileTableConfig<RentCollectionRecord>;
} {
    const { t } = useTranslator();
    const tenantLease = (installment: RentCollectionRecord) => (
        <div className="pmc-stacked-cell">
            <strong>
                {installment.tenant?.name ?? t('rent_collection.no_tenant')}
            </strong>
            <span>
                {installment.lease?.code ?? t('rent_collection.no_lease')}
            </span>
        </div>
    );
    const propertyAsset = (installment: RentCollectionRecord) => (
        <div className="pmc-stacked-cell">
            <strong>
                {localizedRecord(installment.property, locale) ??
                    t('rent_collection.no_property')}
            </strong>
            <span>
                {localizedRecord(installment.asset, locale) ??
                    t('rent_collection.no_asset')}
                {installment.asset?.code ? ` · ${installment.asset.code}` : ''}
            </span>
        </div>
    );
    const postPayment = (installment: RentCollectionRecord) =>
        installment.outstanding_amount > 0 && installment.lease ? (
            <Link
                href={`/payments/create?lease_id=${installment.lease.id}`}
                className="btn btn-primary btn-sm"
            >
                <i className="bi bi-cash-stack" />
                <span>{t('rent_collection.post_payment')}</span>
            </Link>
        ) : null;
    const manageFollowUp = (installment: RentCollectionRecord) => (
        <Link
            href={`/rent-collection/${installment.id}/follow-up`}
            className="btn btn-primary btn-sm"
        >
            <i className="bi bi-telephone-forward" />
            <span>{t('rent_collection.manage_follow_up')}</span>
        </Link>
    );
    const followUp = (installment: RentCollectionRecord) => (
        <div className="pmc-badge-stack">
            <StatusBadge
                value={installment.follow_up.state}
                label={t(
                    `rent_collection.follow_up_state_${installment.follow_up.state}` as UiTranslationKey,
                )}
                tone={followUpTone(installment.follow_up.state)}
            />
            <span>{followUpTiming(installment, t, locale)}</span>
            {installment.follow_up.assigned_to ? (
                <span>
                    {t('rent_collection.assigned_person', undefined, {
                        name: installment.follow_up.assigned_to.name,
                    })}
                </span>
            ) : null}
        </div>
    );
    const actions = (installment: RentCollectionRecord) => (
        <RecordActions
            showHref={
                installment.lease
                    ? `/leases/${installment.lease.id}`
                    : '/leases'
            }
        >
            {manageFollowUp(installment)}
            {postPayment(installment)}
        </RecordActions>
    );
    const columns: Array<TableColumn<RentCollectionRecord>> = [
        {
            key: 'installment',
            label: t('rent_collection.installment'),
            render: (installment) => (
                <div className="pmc-primary-cell">
                    <strong>{installment.label}</strong>
                    <span>
                        {t(
                            `rent_collection.type_${installment.line_type}` as UiTranslationKey,
                        )}{' '}
                        ·{' '}
                        {t('rent_collection.sequence', undefined, {
                            sequence: localizedNumber(
                                installment.sequence,
                                locale,
                            ),
                        })}
                    </span>
                    <StatusBadge
                        value={installment.status}
                        label={t(
                            `rent_collection.status_${installment.status}` as UiTranslationKey,
                        )}
                    />
                </div>
            ),
        },
        {
            key: 'tenant',
            label: t('rent_collection.tenant_lease'),
            render: tenantLease,
        },
        {
            key: 'property',
            label: t('rent_collection.property_asset'),
            render: propertyAsset,
        },
        {
            key: 'due_date',
            label: t('rent_collection.due_date'),
            render: (installment) => (
                <div className="pmc-stacked-cell">
                    <strong>{humanDate(installment.due_date, locale)}</strong>
                    <span>{timingLabel(installment, t, locale)}</span>
                </div>
            ),
        },
        {
            key: 'balance',
            label: t('rent_collection.balance'),
            render: (installment) => (
                <div className="pmc-stacked-cell">
                    <strong>
                        {currency(
                            installment.outstanding_amount,
                            locale,
                            installment.currency,
                        )}
                    </strong>
                    <span>
                        {t('rent_collection.paid_of_due', undefined, {
                            paid: currency(
                                installment.amount_paid,
                                locale,
                                installment.currency,
                            ),
                            due: currency(
                                installment.amount_due,
                                locale,
                                installment.currency,
                            ),
                        })}
                    </span>
                </div>
            ),
        },
        {
            key: 'follow_up',
            label: t('rent_collection.follow_up_status'),
            render: followUp,
        },
        {
            key: 'actions',
            label: t('rent_collection.actions'),
            className: 'text-end',
            render: actions,
        },
    ];

    return {
        columns,
        mobileCard: {
            title: (installment) => installment.label,
            subtitle: tenantLease,
            status: followUp,
            meta: [
                {
                    label: t('rent_collection.property_asset'),
                    value: propertyAsset,
                },
                {
                    label: t('rent_collection.due_date'),
                    value: (installment) =>
                        `${humanDate(installment.due_date, locale)} · ${timingLabel(installment, t, locale)}`,
                },
                {
                    label: t('rent_collection.outstanding'),
                    value: (installment) =>
                        currency(
                            installment.outstanding_amount,
                            locale,
                            installment.currency,
                        ),
                },
            ],
            actions,
        },
    };
}

function followUpTiming(
    installment: RentCollectionRecord,
    t: ReturnType<typeof useTranslator>['t'],
    locale: string,
): string {
    const followUp = installment.follow_up;

    if (followUp.state === 'untracked') {
        return t('rent_collection.follow_up_not_started');
    }

    if (followUp.state === 'broken' && followUp.promised_on) {
        return t('rent_collection.promise_missed_on', undefined, {
            date: humanDate(followUp.promised_on, locale),
        });
    }

    if (followUp.state === 'promised' && followUp.promised_on) {
        return t('rent_collection.promise_due_on', undefined, {
            date: humanDate(followUp.promised_on, locale),
        });
    }

    if (followUp.next_follow_up_on) {
        return t('rent_collection.follow_up_on', undefined, {
            date: humanDate(followUp.next_follow_up_on, locale),
        });
    }

    return t('rent_collection.follow_up_complete');
}

function followUpTone(
    state: RentCollectionRecord['follow_up']['state'],
): 'success' | 'warning' | 'danger' | 'neutral' | 'blue' {
    if (state === 'settled') {
        return 'success';
    }

    if (state === 'broken' || state === 'due') {
        return 'danger';
    }

    if (state === 'promised') {
        return 'blue';
    }

    if (state === 'untracked') {
        return 'warning';
    }

    return 'neutral';
}

function localizedRecord(
    record:
        | { title_en?: string | null; title_ar?: string | null }
        | null
        | undefined,
    locale: string,
): string | null {
    if (!record) {
        return null;
    }

    return locale === 'ar'
        ? record.title_ar || record.title_en || null
        : record.title_en || record.title_ar || null;
}

function timingLabel(
    installment: RentCollectionRecord,
    t: ReturnType<typeof useTranslator>['t'],
    locale: string,
): string {
    if (installment.status === 'paid') {
        return t('rent_collection.settled');
    }

    if (installment.days_overdue > 0) {
        return t('rent_collection.days_overdue', undefined, {
            count: localizedNumber(installment.days_overdue, locale),
        });
    }

    if (installment.days_until_due > 0) {
        return t('rent_collection.due_in_days', undefined, {
            count: localizedNumber(installment.days_until_due, locale),
        });
    }

    return t('rent_collection.due_today');
}
