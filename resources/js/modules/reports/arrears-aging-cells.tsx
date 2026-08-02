import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { ArrearsAgingRecord } from './arrears-aging-types';

export function TenantLease({ record }: { record: ArrearsAgingRecord }) {
    const { t } = useTranslator();

    return (
        <span className="pmc-aging-primary">
            <strong>
                {record.tenant?.name ?? t('rent_collection.no_tenant')}
            </strong>
            <small>{record.lease?.code ?? t('rent_collection.no_lease')}</small>
        </span>
    );
}

export function PropertySpace({ record }: { record: ArrearsAgingRecord }) {
    const { locale, t } = useTranslator();

    return (
        <span className="pmc-aging-primary">
            <strong>
                {localized(record.property, locale) ??
                    t('rent_collection.no_property')}
            </strong>
            <small>
                {localized(record.asset, locale) ??
                    t('rent_collection.no_asset')}
                {record.asset?.code ? ` · ${record.asset.code}` : ''}
            </small>
        </span>
    );
}

export function Installment({ record }: { record: ArrearsAgingRecord }) {
    return (
        <span className="pmc-aging-primary">
            <strong>{record.label}</strong>
            <small>#{record.sequence}</small>
        </span>
    );
}

export function DueAge({ record }: { record: ArrearsAgingRecord }) {
    const { locale, t } = useTranslator();

    return (
        <span className="pmc-aging-age">
            <strong>{humanDate(record.due_date, locale)}</strong>
            <small>
                {t('rent_collection.days_overdue', undefined, {
                    count: record.days_overdue,
                })}
            </small>
        </span>
    );
}

export function Outstanding({ record }: { record: ArrearsAgingRecord }) {
    const { locale, t } = useTranslator();

    return (
        <span className="pmc-aging-money">
            <strong>
                {currency(record.outstanding_amount, locale, record.currency)}
            </strong>
            <small>
                {t('rent_collection.paid_of_due', undefined, {
                    paid: currency(record.amount_paid, locale, record.currency),
                    due: currency(record.amount_due, locale, record.currency),
                })}
            </small>
        </span>
    );
}

export function FollowUp({ record }: { record: ArrearsAgingRecord }) {
    const { t } = useTranslator();
    const state = record.follow_up.state;

    return (
        <span className={`pmc-aging-follow-up is-${state}`}>
            <i aria-hidden="true" />
            <span>
                <strong>{t(`rent_collection.follow_up_state_${state}`)}</strong>
                <small>
                    {record.follow_up.assigned_to?.name ??
                        t('reports.aging_unassigned')}
                </small>
            </span>
        </span>
    );
}

export function AgingBucket({ record }: { record: ArrearsAgingRecord }) {
    const { t } = useTranslator();

    return (
        <span className={`pmc-aging-bucket is-${record.bucket}`}>
            {t(`reports.aging_bucket_${record.bucket}`)}
        </span>
    );
}

export function AgingActions({ record }: { record: ArrearsAgingRecord }) {
    const { t } = useTranslator();

    return (
        <div className="pmc-record-actions">
            <Link href={record.links.follow_up} className="pmc-record-open">
                {t('reports.aging_open_follow_up')}
                <i className="bi bi-arrow-up-right" aria-hidden="true" />
            </Link>
            {record.links.lease ? (
                <Link
                    href={record.links.lease}
                    className="btn btn-outline-secondary btn-sm"
                >
                    {t('reports.aging_open_lease')}
                </Link>
            ) : null}
        </div>
    );
}

function localized(
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
