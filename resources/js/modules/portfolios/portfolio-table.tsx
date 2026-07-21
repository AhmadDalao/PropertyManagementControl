import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import { RecordActions, StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import { usePortfolioFilterFields } from './portfolio-filters';
import type { PortfolioIndexPageProps, PortfolioRecord } from './types';

type PortfolioTableProps = Pick<
    PortfolioIndexPageProps,
    | 'portfolios'
    | 'filters'
    | 'counts'
    | 'canCreate'
    | 'canUpdate'
    | 'canArchive'
    | 'moduleDefinitions'
    | 'statusOptions'
    | 'app'
>;

export function PortfolioTable(props: PortfolioTableProps) {
    const { locale, t } = useTranslator();
    const filterFields = usePortfolioFilterFields(props.statusOptions);
    const portfolioName = (portfolio: PortfolioRecord) =>
        locale === 'ar'
            ? portfolio.name_ar || portfolio.name_en
            : portfolio.name_en || portfolio.name_ar;
    const portfolioCell = (portfolio: PortfolioRecord) => (
        <div className="pmc-primary-cell">
            <strong>{portfolioName(portfolio)}</strong>
            <span>
                {portfolio.code} · {portfolio.name_ar}
            </span>
            {portfolio.is_showcase ? (
                <small>{t('portfolios.showcase')}</small>
            ) : null}
        </div>
    );
    const ownerLocationCell = (portfolio: PortfolioRecord) => (
        <div className="pmc-stacked-cell">
            <strong>
                {portfolio.owner?.name ?? t('portfolios.owner_not_assigned')}
            </strong>
            <span>
                {[portfolio.city, portfolio.country]
                    .filter(Boolean)
                    .join(' · ') || t('portfolios.location_not_set')}
            </span>
        </div>
    );
    const operationsCell = (portfolio: PortfolioRecord) => (
        <div className="pmc-stacked-cell">
            <strong>
                {t('portfolios.assets_users', undefined, {
                    assets: portfolio.assets_count ?? 0,
                    users: portfolio.users_count ?? 0,
                })}
            </strong>
            <span>
                {t('portfolios.leases_service', undefined, {
                    leases: portfolio.active_leases_count ?? 0,
                    service: portfolio.open_maintenance_count ?? 0,
                })}
            </span>
        </div>
    );
    const financeCell = (portfolio: PortfolioRecord) => {
        const revenue = portfolio.posted_revenue_total ?? 0;
        const expenses = portfolio.posted_expense_total ?? 0;

        return (
            <div className="pmc-stacked-cell">
                <strong>
                    {currency(
                        portfolio.valuation_total ?? 0,
                        props.app.locale,
                        portfolio.default_currency,
                    )}
                </strong>
                <span>
                    {t('portfolios.net_amount', undefined, {
                        amount: currency(
                            revenue - expenses,
                            props.app.locale,
                            portfolio.default_currency,
                        ),
                    })}
                </span>
            </div>
        );
    };
    const accessCell = (portfolio: PortfolioRecord) => (
        <div className="pmc-stacked-cell">
            <StatusBadge value={portfolio.status} />
            <span>
                {t('portfolios.modules_enabled', undefined, {
                    count: enabledModuleCount(
                        props.moduleDefinitions,
                        portfolio.module_settings,
                    ),
                })}
            </span>
        </div>
    );
    const actions = (portfolio: PortfolioRecord) => (
        <RecordActions
            showHref={`/portfolios/${portfolio.id}`}
            editHref={
                props.canUpdate ? `/portfolios/${portfolio.id}/edit` : undefined
            }
        >
            {props.canArchive && portfolio.status !== 'archived' ? (
                <ArchiveAction
                    href={`/portfolios/${portfolio.id}`}
                    confirmMessage={t('portfolios.archive_confirm', undefined, {
                        name: portfolioName(portfolio),
                    })}
                />
            ) : null}
        </RecordActions>
    );

    return (
        <DataTable
            title={t('portfolios.directory_title')}
            description={t('portfolios.directory_description')}
            data={props.portfolios}
            filters={props.filters}
            counts={props.counts}
            basePath="/portfolios"
            rowHref={(portfolio) => `/portfolios/${portfolio.id}`}
            exportHref={exportUrl('/exports/portfolios', props.filters)}
            filterFields={filterFields}
            emptyText={t('portfolios.empty')}
            createHref={props.canCreate ? '/portfolios/create' : undefined}
            createLabel={t('portfolios.create_portfolio')}
            mobileCard={{
                title: portfolioCell,
                subtitle: (portfolio) => (
                    <StatusBadge value={portfolio.status} />
                ),
                status: (portfolio) => portfolio.code,
                meta: [
                    {
                        label: t('portfolios.owner_location'),
                        value: ownerLocationCell,
                    },
                    {
                        label: t('portfolios.operations'),
                        value: operationsCell,
                    },
                    {
                        label: t('portfolios.finance'),
                        value: financeCell,
                    },
                ],
                actions,
            }}
            columns={[
                {
                    key: 'portfolio',
                    label: t('portfolios.portfolio'),
                    render: portfolioCell,
                },
                {
                    key: 'owner-location',
                    label: t('portfolios.owner_location'),
                    render: ownerLocationCell,
                },
                {
                    key: 'operations',
                    label: t('portfolios.operations'),
                    render: operationsCell,
                },
                {
                    key: 'finance',
                    label: t('portfolios.finance'),
                    render: financeCell,
                },
                {
                    key: 'access',
                    label: t('portfolios.access'),
                    render: accessCell,
                },
                {
                    key: 'actions',
                    label: t('portfolios.actions'),
                    className: 'text-end',
                    render: actions,
                },
            ]}
        />
    );
}

function enabledModuleCount(
    definitions: Array<{ key: string }>,
    settings?: Record<string, boolean> | null,
) {
    return definitions.filter(
        (definition) => settings?.[definition.key] ?? true,
    ).length;
}
