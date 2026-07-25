import { router } from '@inertiajs/react';
import type { ChangeEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import { AutomaticCheckGrid } from './automatic-check-grid';
import type {
    PortfolioOption,
    PortfolioReadiness,
    ReadinessConfirmation,
} from './types';

export function PortfolioReadinessPanel({
    options,
    readiness,
    confirmations,
    renderConfirmations,
}: {
    options: PortfolioOption[];
    readiness: PortfolioReadiness | null;
    confirmations: ReadinessConfirmation[];
    renderConfirmations: (checks: ReadinessConfirmation[]) => React.ReactNode;
}) {
    const { locale, t } = useTranslator();

    const selectPortfolio = (event: ChangeEvent<HTMLSelectElement>) => {
        const portfolioId = event.currentTarget.value;
        router.get(
            '/system/readiness',
            portfolioId ? { portfolio_id: portfolioId } : {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const metrics = readiness
        ? [
              ['owners', readiness.metrics.owners],
              ['managers', readiness.metrics.managers],
              ['tenants', readiness.metrics.tenants],
              ['properties', readiness.metrics.properties],
              ['current_leases', readiness.metrics.current_leases],
              ['assignment_gaps', readiness.metrics.assignment_gaps],
          ]
        : [];

    return (
        <section className="pmc-readiness-section">
            <div className="pmc-readiness-section-heading with-control">
                <div>
                    <span>{t('readiness.portfolio_scope')}</span>
                    <h2>{t('readiness.portfolio_title')}</h2>
                    <p>{t('readiness.portfolio_description')}</p>
                </div>
                <label>
                    <span>{t('readiness.portfolio')}</span>
                    <select
                        className="form-select"
                        value={readiness?.portfolio.id ?? ''}
                        onChange={selectPortfolio}
                    >
                        {options.length === 0 ? (
                            <option value="">
                                {t('readiness.no_portfolios')}
                            </option>
                        ) : null}
                        {options.map((option) => (
                            <option key={option.id} value={option.id}>
                                {option.name} · {option.code}
                                {option.is_showcase
                                    ? ` · ${t('readiness.showcase')}`
                                    : ''}
                            </option>
                        ))}
                    </select>
                </label>
            </div>

            {readiness ? (
                <>
                    <div className="pmc-readiness-portfolio-banner">
                        <div>
                            <span>{readiness.portfolio.code}</span>
                            <strong>{readiness.portfolio.name}</strong>
                        </div>
                        {readiness.portfolio.is_showcase ? (
                            <span className="pmc-readiness-showcase">
                                {t('readiness.showcase_data')}
                            </span>
                        ) : null}
                    </div>
                    <div
                        className="pmc-readiness-portfolio-metrics"
                        role="list"
                    >
                        {metrics.map(([key, value]) => (
                            <article key={key} role="listitem">
                                <strong>
                                    {(value as number).toLocaleString(locale)}
                                </strong>
                                <span>
                                    {t(`readiness.metric_${key as string}`)}
                                </span>
                            </article>
                        ))}
                    </div>
                    <AutomaticCheckGrid checks={readiness.checks} />
                    <div className="pmc-readiness-subheading">
                        <h3>{t('readiness.portfolio_approvals')}</h3>
                        <p>{t('readiness.portfolio_approvals_help')}</p>
                    </div>
                    {renderConfirmations(confirmations)}
                </>
            ) : (
                <div className="pmc-readiness-empty">
                    <i className="bi bi-buildings" aria-hidden="true" />
                    <h3>{t('readiness.no_portfolio_title')}</h3>
                    <p>{t('readiness.no_portfolio_description')}</p>
                </div>
            )}
        </section>
    );
}
