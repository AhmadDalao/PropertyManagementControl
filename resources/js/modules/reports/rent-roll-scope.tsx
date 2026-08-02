import { useTranslator } from '@/lib/i18n';

export function RentRollScope({
    scope,
}: {
    scope: Array<{ label: string; value: string }>;
}) {
    const { t } = useTranslator();

    return (
        <section className="pmc-rent-roll-scope">
            <header>
                <div>
                    <i className="bi bi-bounding-box" aria-hidden="true" />
                    <span>
                        <strong>{t('reports.rent_roll_scope_title')}</strong>
                        <small>{t('reports.rent_roll_scope_help')}</small>
                    </span>
                </div>
            </header>
            <dl>
                {scope.map((item) => (
                    <div key={item.label}>
                        <dt>{item.label}</dt>
                        <dd>{item.value}</dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}
