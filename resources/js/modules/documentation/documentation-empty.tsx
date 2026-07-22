import { useTranslator } from '@/lib/i18n';

export function DocumentationEmpty({ onClear }: { onClear: () => void }) {
    const { t } = useTranslator();

    return (
        <section className="pmc-doc-empty" role="status">
            <i className="bi bi-search" />
            <div>
                <strong>{t('docs.no_results')}</strong>
                <span>{t('docs.no_results_help')}</span>
            </div>
            <button
                type="button"
                className="btn btn-outline-secondary"
                onClick={onClear}
            >
                {t('docs.clear_search')}
            </button>
        </section>
    );
}
