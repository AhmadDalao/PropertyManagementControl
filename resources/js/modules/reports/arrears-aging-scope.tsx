import { useTranslator } from '@/lib/i18n';

export function ArrearsAgingScope({
    scope,
}: {
    scope: Array<{ label: string; value: string }>;
}) {
    const { t } = useTranslator();

    return (
        <section className="pmc-aging-scope">
            <header>
                <i className="bi bi-bounding-box" aria-hidden="true" />
                <span>
                    <strong>{t('reports.aging_scope_title')}</strong>
                    <small>{t('reports.aging_scope_help')}</small>
                </span>
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
