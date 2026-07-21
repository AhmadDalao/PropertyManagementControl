import { router } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { PropertyMapPortfolioOption } from './types';

export function PropertyMapPortfolioFilter({
    options,
    selectedPortfolio,
}: {
    options: PropertyMapPortfolioOption[];
    selectedPortfolio: string;
}) {
    const { t } = useTranslator();

    return (
        <label>
            <span>{t('map.portfolio')}</span>
            <select
                className="form-select"
                value={selectedPortfolio}
                onChange={(event) => {
                    const portfolioId = event.currentTarget.value;

                    router.get(
                        '/property-map',
                        portfolioId === 'all'
                            ? {}
                            : { portfolio_id: portfolioId },
                        {
                            preserveScroll: true,
                            preserveState: true,
                            replace: true,
                        },
                    );
                }}
            >
                <option value="all">{t('map.all_portfolios')}</option>
                {options.map((portfolio) => (
                    <option key={portfolio.id} value={portfolio.id}>
                        {portfolio.name}
                    </option>
                ))}
            </select>
        </label>
    );
}
