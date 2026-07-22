import { ArchiveAction } from '@/components/archive-action';
import type { MobileTableConfig, TableColumn } from '@/components/data-table';
import { RecordActions, StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { LeaseRecord } from './types';

export function useLeaseTableConfig(locale: string): {
    columns: Array<TableColumn<LeaseRecord>>;
    mobileCard: MobileTableConfig<LeaseRecord>;
} {
    const { t } = useTranslator();
    const tenantAsset = (lease: LeaseRecord) => (
        <div className="pmc-stacked-cell">
            <strong>
                {lease.tenant_profile?.user?.name ?? t('leases.no_tenant')}
            </strong>
            <span>
                {localizedAsset(lease, locale) ?? t('leases.no_asset')} ·{' '}
                {lease.leaseable?.code ?? t('leases.no_code')}
            </span>
        </div>
    );
    const actions = (lease: LeaseRecord) => (
        <RecordActions
            showHref={`/leases/${lease.id}`}
            editHref={`/leases/${lease.id}/edit`}
        >
            <a
                href={`/leases/${lease.id}/contract`}
                className="btn btn-outline-secondary btn-sm"
            >
                <i className="bi bi-file-earmark-pdf" />
                <span>{t('leases.contract_action')}</span>
            </a>
            {['draft', 'active'].includes(lease.status) ? (
                <ArchiveAction
                    href={`/leases/${lease.id}`}
                    label={t('leases.terminate')}
                    confirmMessage={t('leases.terminate_confirm', undefined, {
                        code: lease.code,
                    })}
                />
            ) : null}
        </RecordActions>
    );
    const columns: Array<TableColumn<LeaseRecord>> = [
        {
            key: 'lease',
            label: t('leases.lease'),
            render: (lease) => (
                <div className="pmc-primary-cell">
                    <strong>{lease.code}</strong>
                    <span>{frequencyLabel(lease.payment_frequency, t)}</span>
                    <StatusBadge
                        value={lease.status}
                        label={statusLabel(lease.status, t)}
                    />
                </div>
            ),
        },
        {
            key: 'tenant',
            label: t('leases.tenant_asset'),
            render: tenantAsset,
        },
        {
            key: 'period',
            label: t('leases.contract_period'),
            render: (lease) => (
                <div className="pmc-stacked-cell">
                    <strong>
                        {humanDate(lease.started_at, locale)} {t('leases.to')}{' '}
                        {humanDate(lease.ends_at, locale)}
                    </strong>
                    <span>
                        {t('leases.days_remaining', undefined, {
                            count: lease.days_remaining ?? 0,
                        })}{' '}
                        ·{' '}
                        {lease.signed_at
                            ? t('leases.signed')
                            : t('leases.unsigned')}
                    </span>
                </div>
            ),
        },
        {
            key: 'balance',
            label: t('leases.balance'),
            render: (lease) => (
                <div className="pmc-stacked-cell">
                    <strong>
                        {currency(
                            lease.balance_remaining,
                            locale,
                            lease.currency,
                        )}{' '}
                        {t('leases.left')}
                    </strong>
                    <span>
                        {currency(lease.total_paid, locale, lease.currency)}{' '}
                        {t('leases.paid_label')}
                    </span>
                </div>
            ),
        },
        {
            key: 'next',
            label: t('leases.next_due'),
            render: (lease) => (
                <div className="pmc-stacked-cell">
                    <strong>{humanDate(lease.next_due_date, locale)}</strong>
                    <span>
                        {lease.next_due_amount !== null &&
                        lease.next_due_amount !== undefined
                            ? currency(
                                  lease.next_due_amount,
                                  locale,
                                  lease.currency,
                              )
                            : t('leases.no_open_installment')}
                    </span>
                    {lease.overdue_count > 0 ? (
                        <StatusBadge
                            value="overdue"
                            label={t('leases.overdue_installments', undefined, {
                                count: lease.overdue_count,
                            })}
                            tone="danger"
                        />
                    ) : null}
                </div>
            ),
        },
        {
            key: 'actions',
            label: t('leases.actions'),
            className: 'text-end',
            render: actions,
        },
    ];

    return {
        columns,
        mobileCard: {
            title: (lease) => lease.code,
            subtitle: tenantAsset,
            status: (lease) => (
                <StatusBadge
                    value={lease.status}
                    label={statusLabel(lease.status, t)}
                />
            ),
            meta: [
                {
                    label: t('leases.contract_period'),
                    value: (lease) =>
                        `${humanDate(lease.started_at, locale)} ${t('leases.to')} ${humanDate(lease.ends_at, locale)}`,
                },
                {
                    label: t('leases.balance'),
                    value: (lease) =>
                        currency(
                            lease.balance_remaining,
                            locale,
                            lease.currency,
                        ),
                },
                {
                    label: t('leases.next_due'),
                    value: (lease) => humanDate(lease.next_due_date, locale),
                },
            ],
            actions,
        },
    };
}

function localizedAsset(lease: LeaseRecord, locale: string) {
    return locale === 'ar'
        ? lease.leaseable?.title_ar || lease.leaseable?.title_en
        : lease.leaseable?.title_en || lease.leaseable?.title_ar;
}

function statusLabel(status: string, t: ReturnType<typeof useTranslator>['t']) {
    return t(`status.${status}` as UiTranslationKey);
}

function frequencyLabel(
    frequency: string,
    t: ReturnType<typeof useTranslator>['t'],
) {
    return t(`leases.frequency_${frequency}` as UiTranslationKey);
}
