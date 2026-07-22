import type { MobileTableConfig, TableColumn } from '@/components/data-table';
import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import {
    PortfolioAccess,
    PortfolioActions,
    PortfolioFinance,
    PortfolioIdentity,
    PortfolioOperations,
    PortfolioOwnerLocation,
} from './portfolio-table-cells';
import type { PortfolioRecord, PortfolioTableProps } from './types';

export function usePortfolioTableConfig(props: PortfolioTableProps): {
    columns: Array<TableColumn<PortfolioRecord>>;
    mobileCard: MobileTableConfig<PortfolioRecord>;
} {
    const { t } = useTranslator();
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
            title: identity,
            subtitle: (portfolio) => <StatusBadge value={portfolio.status} />,
            status: (portfolio) => portfolio.code,
            meta: [
                {
                    label: t('portfolios.owner_location'),
                    value: ownerLocation,
                },
                {
                    label: t('portfolios.operations'),
                    value: operations,
                },
                { label: t('portfolios.finance'), value: finance },
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
