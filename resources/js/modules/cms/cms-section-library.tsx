import { Link } from '@inertiajs/react';

import { ArchiveAction } from '@/components/archive-action';
import { StatusBadge, WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { CmsSectionRecord } from './types';

export function CmsSectionLibrary({
    sections,
    limitReached,
}: {
    sections: CmsSectionRecord[];
    limitReached: boolean;
}) {
    const { locale, t } = useTranslator();

    return (
        <WorkspacePanel
            eyebrow={t('cms.reusable_blocks_eyebrow')}
            title={t('cms.section_library')}
            description={t('cms.section_library_description')}
            action={{
                label: t('cms.create_section'),
                href: '/cms/sections/create',
            }}
        >
            {limitReached ? (
                <div className="alert alert-warning" role="status">
                    {t('cms.library_limit_notice')}
                </div>
            ) : null}

            <div className="pmc-cms-library-grid">
                {sections.length > 0 ? (
                    sections.map((section) => {
                        const name =
                            locale === 'ar'
                                ? section.name_ar || section.name_en
                                : section.name_en || section.name_ar;

                        return (
                            <article
                                className="pmc-cms-library-card"
                                key={section.id}
                            >
                                <div className="pmc-cms-library-icon">
                                    <i
                                        className={`bi ${sectionIcon(section.section_type)}`}
                                    />
                                </div>
                                <div className="pmc-cms-library-copy">
                                    <span>
                                        {t(
                                            `cms.section_types.${section.section_type}`,
                                            section.section_type,
                                        )}
                                    </span>
                                    <strong>{name}</strong>
                                    <small>
                                        {section.name_ar ||
                                            t('cms.arabic_name_missing')}
                                    </small>
                                </div>
                                <div className="pmc-cms-library-meta">
                                    <StatusBadge value={section.status} />
                                    <span>
                                        {t('cms.section_page_uses', undefined, {
                                            count:
                                                section.page_sections_count ??
                                                0,
                                        })}
                                    </span>
                                </div>
                                <div className="pmc-cms-card-actions">
                                    <Link
                                        className="btn btn-outline-secondary btn-sm"
                                        href={`/cms/sections/${section.id}/edit`}
                                    >
                                        <i className="bi bi-pencil" />
                                        {t('cms.edit_copy')}
                                    </Link>
                                    {section.status !== 'archived' ? (
                                        <ArchiveAction
                                            href={`/cms/sections/${section.id}`}
                                            confirmMessage={t(
                                                'cms.archive_section_confirm',
                                                undefined,
                                                { title: name || '' },
                                            )}
                                        />
                                    ) : null}
                                </div>
                            </article>
                        );
                    })
                ) : (
                    <div className="pmc-inline-empty">
                        {t('cms.no_sections')}
                    </div>
                )}
            </div>
        </WorkspacePanel>
    );
}

function sectionIcon(type: string) {
    return (
        {
            hero: 'bi-window-fullscreen',
            role_cards: 'bi-people',
            workflow: 'bi-diagram-3',
            dashboard_preview: 'bi-speedometer2',
            feature_grid: 'bi-grid',
            operations_strip: 'bi-activity',
            faq: 'bi-question-circle',
            final_cta: 'bi-megaphone',
            metrics: 'bi-bar-chart',
            content: 'bi-text-paragraph',
        }[type] ?? 'bi-layout-text-window'
    );
}
