import { useTranslator } from '@/lib/i18n';

export function WordingPagination({
    current,
    last,
    onPage,
}: {
    current: number;
    last: number;
    onPage: (page: number) => void;
}) {
    const { t } = useTranslator();

    if (last <= 1) {
        return null;
    }

    return (
        <nav className="pmc-wording-pagination" aria-label={t('wording.title')}>
            <button
                type="button"
                className="btn btn-outline-secondary"
                disabled={current <= 1}
                onClick={() => onPage(current - 1)}
            >
                {t('wording.previous_page')}
            </button>
            <span>
                {t('wording.page_of', undefined, {
                    page: current,
                    pages: last,
                })}
            </span>
            <button
                type="button"
                className="btn btn-outline-secondary"
                disabled={current >= last}
                onClick={() => onPage(current + 1)}
            >
                {t('wording.next_page')}
            </button>
        </nav>
    );
}
