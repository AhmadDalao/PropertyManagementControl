import { useTranslator } from '@/lib/i18n';

import type { Guide } from './types';

export function DocumentationGuideContent({ guide }: { guide: Guide }) {
    const { t } = useTranslator();

    return (
        <div className="pmc-doc-detail-content">
            <section id="features">
                <div className="pmc-kicker">{t('docs.feature_kicker')}</div>
                <h2>{t('docs.features')}</h2>
                <div className="pmc-doc-feature-grid">
                    {guide.features.map((feature, index) => (
                        <article key={feature}>
                            <span>{String(index + 1).padStart(2, '0')}</span>
                            <p>{feature}</p>
                        </article>
                    ))}
                </div>
            </section>

            <section id="steps">
                <div className="pmc-kicker">{t('docs.steps_kicker')}</div>
                <h2>{t('docs.how_to')}</h2>
                <ol className="pmc-doc-step-list">
                    {guide.steps.map((step) => (
                        <li key={step}>{step}</li>
                    ))}
                </ol>
            </section>

            <section id="rules">
                <div className="pmc-kicker">{t('docs.rules_kicker')}</div>
                <h2>{t('docs.rules')}</h2>
                <ul className="pmc-doc-rule-list">
                    {guide.rules.map((rule) => (
                        <li key={rule}>
                            <i className="bi bi-check2-circle" />
                            <span>{rule}</span>
                        </li>
                    ))}
                </ul>
            </section>
        </div>
    );
}
