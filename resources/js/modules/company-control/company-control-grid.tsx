import { TablePagination } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { CompanyControlCard } from './company-control-card';
import type { CompanyControlProps } from './types';

export function CompanyControlGrid({
    portfolios,
}: Pick<CompanyControlProps, 'portfolios'>) {
    const { t } = useTranslator();

    if (portfolios.data.length === 0) {
        return (
            <section className="pmc-company-control-empty">
                <i className="bi bi-building" aria-hidden="true" />
                <strong>{t('company_control.empty')}</strong>
                <p>{t('company_control.empty_help')}</p>
            </section>
        );
    }

    return (
        <section aria-label={t('company_control.directory')}>
            <div className="pmc-company-control-grid">
                {portfolios.data.map((portfolio) => (
                    <CompanyControlCard
                        key={portfolio.id}
                        portfolio={portfolio}
                    />
                ))}
            </div>
            {portfolios.last_page > 1 ? (
                <TablePagination data={portfolios} />
            ) : null}
        </section>
    );
}
