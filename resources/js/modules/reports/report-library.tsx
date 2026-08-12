import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { ReportLibraryGroup } from './types';

export function ReportLibrary({ groups }: { groups: ReportLibraryGroup[] }) {
    const { t } = useTranslator();
    const cards = groups.flatMap((group) => group.cards);
    const featuredCards = cards.slice(0, 8);

    return (
        <section className="pmc-report-command-library">
            <header>
                <div>
                    <span>{t('reports.library_eyebrow')}</span>
                    <h2>{t('reports.quick_reports', 'Quick reports')}</h2>
                    <p>{t('reports.library_description')}</p>
                </div>
                <Link href="/reports/saved">
                    {t('reports.manage_saved_reports')}
                    <i className="bi bi-arrow-up-right" aria-hidden="true" />
                </Link>
            </header>

            <div className="pmc-report-quick-grid">
                {featuredCards.map((card) => (
                    <article className="pmc-report-library-card" key={card.key}>
                        <Link href={card.openHref}>
                            <i
                                className={`bi ${card.icon}`}
                                aria-hidden="true"
                            />
                            <strong>{card.title}</strong>
                            <span>{card.description}</span>
                        </Link>
                        {card.downloads.length > 0 ? (
                            <div>
                                {card.downloads.map((download) => (
                                    <a
                                        href={download.href}
                                        key={`${card.key}-${download.label}`}
                                        title={download.label}
                                    >
                                        <i
                                            className="bi bi-download"
                                            aria-hidden="true"
                                        />
                                        <span>{download.label}</span>
                                    </a>
                                ))}
                            </div>
                        ) : null}
                    </article>
                ))}
            </div>

            <div className="pmc-report-category-grid">
                {groups.map((group) => (
                    <article key={group.key}>
                        <header>
                            <h3>{group.title}</h3>
                            <span>{group.cards.length}</span>
                        </header>
                        <div>
                            {group.cards.map((card) => (
                                <Link href={card.openHref} key={card.key}>
                                    <i
                                        className={`bi ${card.icon}`}
                                        aria-hidden="true"
                                    />
                                    <span>{card.title}</span>
                                    <i
                                        className="bi bi-chevron-right"
                                        aria-hidden="true"
                                    />
                                </Link>
                            ))}
                        </div>
                    </article>
                ))}
            </div>
        </section>
    );
}
