import { useTranslator } from '@/lib/i18n';

import { headlineTargetKeys, showcaseLabel } from './showcase-labels';

export function DataLabTargetPlan({
    targets,
}: {
    targets: Record<string, number>;
}) {
    const { locale, t } = useTranslator();

    return (
        <details className="pmc-showcase-target-plan">
            <summary>
                <div>
                    <span>{t('showcase.target_plan')}</span>
                    <strong>{t('showcase.target_plan_title')}</strong>
                    <small>{t('showcase.target_plan_description')}</small>
                </div>
                <div className="pmc-showcase-target-headlines">
                    {headlineTargetKeys.map((key) => (
                        <span key={key}>
                            <small>{showcaseLabel(key, t)}</small>
                            <strong>
                                {(targets[key] ?? 0).toLocaleString(locale)}
                            </strong>
                        </span>
                    ))}
                </div>
                <i className="bi bi-chevron-down" aria-hidden="true" />
            </summary>
            <div className="pmc-showcase-targets">
                {Object.entries(targets).map(([key, value]) => (
                    <article key={key}>
                        <span>{showcaseLabel(key, t)}</span>
                        <strong>{value.toLocaleString(locale)}</strong>
                    </article>
                ))}
            </div>
        </details>
    );
}
