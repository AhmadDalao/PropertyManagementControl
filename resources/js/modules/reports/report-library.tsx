import { Link } from '@inertiajs/react';
import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';

import { ReportCardScope } from './report-card-scope';
import {
    ReportLibraryTabs,
    resolveReportLibraryGroup,
} from './report-library-tabs';
import type { ReportLibraryGroup } from './types';

export function ReportLibrary({ groups }: { groups: ReportLibraryGroup[] }) {
    const { t } = useTranslator();
    const [activeGroup, setActiveGroup] = useState(() => {
        if (typeof window === 'undefined') {
            return groups[0]?.key ?? '';
        }

        return resolveReportLibraryGroup(
            new URLSearchParams(window.location.search).get('library_group'),
            groups,
        );
    });
    const selectedGroup =
        groups.find((group) => group.key === activeGroup) ?? groups[0];
    const selectGroup = (group: string) => {
        setActiveGroup(group);
        const url = new URL(window.location.href);
        url.searchParams.set('library_group', group);
        window.history.replaceState({}, '', url);
    };

    return (
        <div className="pmc-report-library">
            <header className="pmc-report-library-intro">
                <div>
                    <span>{t('reports.library_eyebrow')}</span>
                    <h2>{t('reports.library_title')}</h2>
                    <p>{t('reports.library_description')}</p>
                </div>
                <div className="pmc-report-library-note">
                    <i className="bi bi-funnel" aria-hidden="true" />
                    <span>{t('reports.library_filter_note')}</span>
                </div>
            </header>

            <ReportLibraryTabs
                active={selectedGroup?.key ?? ''}
                groups={groups}
                onSelect={selectGroup}
            />

            {selectedGroup ? (
                <section
                    id="report-library-panel"
                    className="pmc-report-library-group"
                    role="tabpanel"
                    aria-labelledby={`report-library-tab-${selectedGroup.key}`}
                >
                    <header>
                        <div>
                            <h3>{selectedGroup.title}</h3>
                            <p>{selectedGroup.description}</p>
                        </div>
                        <span>{selectedGroup.cards.length}</span>
                    </header>

                    <div className="pmc-report-library-grid">
                        {selectedGroup.cards.map((card) => (
                            <article
                                className="pmc-report-library-card"
                                key={card.key}
                            >
                                <div className="pmc-report-library-card-head">
                                    <span>
                                        <i
                                            className={`bi ${card.icon}`}
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <div>
                                        <h4>{card.title}</h4>
                                        <p>{card.description}</p>
                                    </div>
                                </div>

                                <ReportCardScope scope={card.scope} />

                                <div className="pmc-report-library-card-actions">
                                    <Link
                                        className="pmc-report-source-link"
                                        href={card.openHref}
                                    >
                                        <i
                                            className="bi bi-arrow-up-right"
                                            aria-hidden="true"
                                        />
                                        {card.openLabel}
                                    </Link>
                                    {card.downloads.map((download) => (
                                        <a
                                            className="pmc-report-download-link"
                                            href={download.href}
                                            key={`${card.key}-${download.label}`}
                                        >
                                            <i
                                                className="bi bi-download"
                                                aria-hidden="true"
                                            />
                                            {download.label}
                                        </a>
                                    ))}
                                </div>
                            </article>
                        ))}
                    </div>
                </section>
            ) : null}
        </div>
    );
}
