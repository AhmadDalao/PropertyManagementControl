import { TablePagination } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { PropertyControlCard } from './property-control-card';
import type { PortfolioControlProps } from './types';

export function PropertyControlGrid({
    properties,
}: Pick<PortfolioControlProps, 'properties'>) {
    const { t } = useTranslator();

    if (properties.data.length === 0) {
        return (
            <section className="pmc-portfolio-control-empty">
                <i className="bi bi-buildings" aria-hidden="true" />
                <strong>{t('portfolio_control.empty')}</strong>
                <p>{t('portfolio_control.empty_help')}</p>
            </section>
        );
    }

    return (
        <section aria-label={t('portfolio_control.directory')}>
            <div className="pmc-portfolio-control-grid">
                {properties.data.map((property) => (
                    <PropertyControlCard
                        key={property.id}
                        property={property}
                    />
                ))}
            </div>
            {properties.last_page > 1 ? (
                <TablePagination data={properties} />
            ) : null}
        </section>
    );
}
