import { useTranslator } from '@/lib/i18n';

import type { PortfolioDetailPage } from './types';

export function PortfolioModuleGrid({
    modules,
}: Pick<PortfolioDetailPage, 'modules'>) {
    const { t, text } = useTranslator();

    return (
        <section
            className="pmc-portfolio-module-panel"
            aria-labelledby="portfolio-module-title"
        >
            <header>
                <div>
                    <small>{t('portfolios.access')}</small>
                    <h2 id="portfolio-module-title">
                        {t('portfolios.module_access')}
                    </h2>
                    <p>{t('portfolios.module_access_help')}</p>
                </div>
            </header>
            <div className="pmc-portfolio-module-grid">
                {modules.map((module) => (
                    <article
                        className={
                            module.enabled ? 'is-enabled' : 'is-disabled'
                        }
                        key={module.key}
                    >
                        <span aria-hidden="true">
                            <i
                                className={`bi ${
                                    module.enabled
                                        ? 'bi-check2-circle'
                                        : 'bi-dash-circle'
                                }`}
                            />
                        </span>
                        <div>
                            <strong>{text(module.label)}</strong>
                            <p>{text(module.description)}</p>
                        </div>
                        <em>
                            {t(
                                module.enabled
                                    ? 'portfolios.enabled'
                                    : 'portfolios.disabled',
                            )}
                        </em>
                    </article>
                ))}
            </div>
        </section>
    );
}
