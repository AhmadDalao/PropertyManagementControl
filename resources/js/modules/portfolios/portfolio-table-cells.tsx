import { ArchiveAction } from '@/components/archive-action';
import { RecordActions, StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import type { ModuleDefinition, PortfolioRecord } from './types';

type PortfolioCellProps = { portfolio: PortfolioRecord };

export function PortfolioIdentity({ portfolio }: PortfolioCellProps) {
    const { locale, t } = useTranslator();
    const secondaryName =
        locale === 'ar' ? portfolio.name_en : portfolio.name_ar;

    return (
        <div className="pmc-primary-cell">
            <strong>{portfolioName(portfolio, locale)}</strong>
            <span>
                {[portfolio.code, secondaryName].filter(Boolean).join(' · ')}
            </span>
            {portfolio.is_showcase ? (
                <small>{t('portfolios.showcase')}</small>
            ) : null}
        </div>
    );
}

export function PortfolioOwnerLocation({ portfolio }: PortfolioCellProps) {
    const { t } = useTranslator();

    return (
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
}

export function PortfolioOperations({ portfolio }: PortfolioCellProps) {
    const { t } = useTranslator();

    return (
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
}

export function PortfolioFinance({
    portfolio,
    locale,
}: PortfolioCellProps & { locale: string }) {
    const { t } = useTranslator();
    const revenue = portfolio.posted_revenue_total ?? 0;
    const expenses = portfolio.posted_expense_total ?? 0;

    return (
        <div className="pmc-stacked-cell">
            <strong>
                {currency(
                    portfolio.valuation_total ?? 0,
                    locale,
                    portfolio.default_currency,
                )}
            </strong>
            <span>
                {t('portfolios.net_amount', undefined, {
                    amount: currency(
                        revenue - expenses,
                        locale,
                        portfolio.default_currency,
                    ),
                })}
            </span>
        </div>
    );
}

export function PortfolioAccess({
    portfolio,
    definitions,
}: PortfolioCellProps & { definitions: ModuleDefinition[] }) {
    const { t } = useTranslator();

    return (
        <div className="pmc-stacked-cell">
            <StatusBadge value={portfolio.status} />
            <span>
                {t('portfolios.modules_enabled', undefined, {
                    count: definitions.filter(
                        ({ key }) => portfolio.module_settings?.[key] ?? true,
                    ).length,
                })}
            </span>
        </div>
    );
}

export function PortfolioActions({
    portfolio,
    canUpdate,
    canArchive,
}: PortfolioCellProps & { canUpdate: boolean; canArchive: boolean }) {
    const { locale, t } = useTranslator();

    return (
        <RecordActions
            showHref={`/portfolios/${portfolio.id}`}
            editHref={
                canUpdate ? `/portfolios/${portfolio.id}/edit` : undefined
            }
        >
            {canArchive && portfolio.status !== 'archived' ? (
                <ArchiveAction
                    href={`/portfolios/${portfolio.id}`}
                    confirmMessage={t('portfolios.archive_confirm', undefined, {
                        name: portfolioName(portfolio, locale),
                    })}
                />
            ) : null}
        </RecordActions>
    );
}

export function portfolioName(portfolio: PortfolioRecord, locale: string) {
    return locale === 'ar'
        ? portfolio.name_ar || portfolio.name_en
        : portfolio.name_en || portfolio.name_ar;
}
