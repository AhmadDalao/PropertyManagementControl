import type { MobileTableConfig, TableColumn } from '@/components/data-table';
import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import {
    PortfolioAccess,
    PortfolioActions,
    PortfolioFinance,
    PortfolioIdentity,
    PortfolioOperations,
    PortfolioOwnerLocation,
    portfolioName,
} from './portfolio-table-cells';
import type { PortfolioRecord, PortfolioTableProps } from './types';

export function usePortfolioTableConfig(props: PortfolioTableProps): {
    columns: Array<TableColumn<PortfolioRecord>>;
    mobileCard: MobileTableConfig<PortfolioRecord>;
} {
    const { locale, t } = useTranslator();
    const identity = (portfolio: PortfolioRecord) => (
        <PortfolioIdentity portfolio={portfolio} />
    );
    const ownerLocation = (portfolio: PortfolioRecord) => (
        <PortfolioOwnerLocation portfolio={portfolio} />
    );
    const operations = (portfolio: PortfolioRecord) => (
        <PortfolioOperations portfolio={portfolio} />
    );
    const finance = (portfolio: PortfolioRecord) => (
        <PortfolioFinance portfolio={portfolio} locale={props.app.locale} />
    );
    const actions = (portfolio: PortfolioRecord) => (
        <PortfolioActions
            portfolio={portfolio}
            canUpdate={props.canUpdate}
            canArchive={props.canArchive}
        />
    );

    return {
        mobileCard: {
            title: (portfolio) => portfolioName(portfolio, locale),
            subtitle: (portfolio) => portfolio.code,
            status: (portfolio) => <StatusBadge value={portfolio.status} />,
            meta: [
                {
                    label: t('portfolios.owner'),
                    value: (portfolio) =>
                        portfolio.owner?.name ??
                        t('portfolios.owner_not_assigned'),
                },
                {
                    label: t('portfolios.operations'),
                    value: (portfolio) =>
                        t('portfolios.assets_users', undefined, {
                            assets: portfolio.assets_count ?? 0,
                            users: portfolio.users_count ?? 0,
                        }),
                },
                {
                    label: t('portfolios.finance'),
                    value: (portfolio) =>
                        currency(
                            portfolio.valuation_total ?? 0,
                            props.app.locale,
                            portfolio.default_currency,
                        ),
                },
            ],
            actions,
        },
        columns: [
            {
                key: 'portfolio',
                label: t('portfolios.portfolio'),
                render: identity,
            },
            {
                key: 'owner-location',
                label: t('portfolios.owner_location'),
                render: ownerLocation,
            },
            {
                key: 'operations',
                label: t('portfolios.operations'),
                render: operations,
            },
            {
                key: 'finance',
                label: t('portfolios.finance'),
                render: finance,
            },
            {
                key: 'access',
                label: t('portfolios.access'),
                render: (portfolio) => (
                    <PortfolioAccess
                        portfolio={portfolio}
                        definitions={props.moduleDefinitions}
                    />
                ),
            },
            {
                key: 'actions',
                label: t('portfolios.actions'),
                className: 'text-end',
                render: actions,
            },
        ],
    };
}
