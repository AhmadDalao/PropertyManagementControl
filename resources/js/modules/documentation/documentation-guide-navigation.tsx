import { useTranslator } from '@/lib/i18n';

import type { Guide } from './types';

export function DocumentationGuideNavigation({ guide }: { guide: Guide }) {
    const { t } = useTranslator();

    return (
        <aside aria-label={t('docs.guide_contents')}>
            <div className="pmc-doc-guide-icon" aria-hidden="true">
                <i className={`bi ${guide.icon}`} />
            </div>
            <span>{t('docs.guide_contents')}</span>
            <a href="#features">{t('docs.features')}</a>
            <a href="#steps">{t('docs.how_to')}</a>
            <a href="#rules">{t('docs.rules')}</a>
        </aside>
    );
}
