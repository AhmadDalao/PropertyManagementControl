import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { ContentTranslations } from './types';
import { contentModuleLabel, missingFieldLabel } from './wording-labels';

export function ContentTranslationQueue({
    content,
    selectedModule,
    onModule,
}: {
    content: ContentTranslations;
    selectedModule: string;
    onModule: (module: string) => void;
}) {
    const { t } = useTranslator();

    return (
        <section className="pmc-wording-workspace">
            <header>
                <div>
                    <span>{t('wording.content_tab')}</span>
                    <strong>{t('wording.content_queue_title')}</strong>
                </div>
                <span className="pmc-wording-queue-count">{content.total}</span>
            </header>
            <div className="pmc-wording-module-chips">
                <button
                    type="button"
                    className={selectedModule === 'all' ? 'is-active' : ''}
                    onClick={() => onModule('all')}
                >
                    {t('common.overview')} {content.total}
                </button>
                {content.modules.map((module) => (
                    <button
                        key={module}
                        type="button"
                        className={selectedModule === module ? 'is-active' : ''}
                        onClick={() => onModule(module)}
                    >
                        {contentModuleLabel(module, t)}{' '}
                        {content.counts[module] ?? 0}
                    </button>
                ))}
            </div>
            {content.items.length > 0 ? (
                <div className="pmc-wording-content-grid">
                    {content.items.map((item, index) => (
                        <article key={`${item.module}:${item.href}:${index}`}>
                            <span>{contentModuleLabel(item.module, t)}</span>
                            <strong>{item.title}</strong>
                            <p>{item.subtitle}</p>
                            <small>
                                {t('wording.missing_field', undefined, {
                                    field: missingFieldLabel(item.missing, t),
                                })}
                            </small>
                            <Link
                                href={item.href}
                                className="btn btn-outline-secondary"
                            >
                                {t('wording.open_record')}
                                <i className="bi bi-arrow-up-right" />
                            </Link>
                        </article>
                    ))}
                </div>
            ) : (
                <div className="pmc-wording-empty">
                    <i className="bi bi-check2-circle" />
                    <strong>{t('wording.content_complete')}</strong>
                    <p>{t('wording.content_complete_description')}</p>
                </div>
            )}
        </section>
    );
}
