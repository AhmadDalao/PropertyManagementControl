import { useTranslator } from '@/lib/i18n';

import type { WordingTab } from './types';

export function WordingTabs({
    active,
    totalLabels,
    contentTotal,
    onChange,
}: {
    active: WordingTab;
    totalLabels: number;
    contentTotal: number;
    onChange: (tab: WordingTab) => void;
}) {
    const { t } = useTranslator();

    return (
        <nav className="pmc-wording-tabs" aria-label={t('wording.title')}>
            <button
                type="button"
                className={active === 'wording' ? 'is-active' : ''}
                onClick={() => onChange('wording')}
                aria-current={active === 'wording' ? 'page' : undefined}
            >
                <i className="bi bi-type" />
                {t('wording.system_tab')}
                <span>{totalLabels}</span>
            </button>
            <button
                type="button"
                className={active === 'content' ? 'is-active' : ''}
                onClick={() => onChange('content')}
                aria-current={active === 'content' ? 'page' : undefined}
            >
                <i className="bi bi-translate" />
                {t('wording.content_tab')}
                <span>{contentTotal}</span>
            </button>
        </nav>
    );
}
