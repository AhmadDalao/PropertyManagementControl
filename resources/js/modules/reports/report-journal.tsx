import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { OperationalJournalEvent } from './types';

export function ReportJournal({
    events,
}: {
    events: OperationalJournalEvent[];
}) {
    const { locale, t } = useTranslator();

    return (
        <section
            className="pmc-report-journal"
            aria-labelledby="operational-journal-title"
        >
            <header className="pmc-report-journal__header">
                <div>
                    <span>{t('reports.journal_eyebrow')}</span>
                    <h2 id="operational-journal-title">
                        {t('reports.journal_title')}
                    </h2>
                    <p>{t('reports.journal_description')}</p>
                </div>
                <span className="pmc-report-journal__count">
                    {t('reports.journal_event_count', undefined, {
                        count: events.length,
                    })}
                </span>
            </header>

            {events.length === 0 ? (
                <div className="pmc-report-journal__empty">
                    <i className="bi bi-clock-history" aria-hidden="true" />
                    <strong>{t('reports.journal_no_activity')}</strong>
                    <span>{t('reports.journal_no_activity_help')}</span>
                </div>
            ) : (
                <div className="pmc-report-journal__list">
                    {events.map((event) => (
                        <Link
                            key={event.key}
                            href={event.href}
                            className={`pmc-report-journal__event is-${event.tone}`}
                            aria-label={`${event.type_label}: ${event.title}`}
                        >
                            <span className="pmc-report-journal__icon">
                                <i
                                    className={`bi ${event.icon}`}
                                    aria-hidden="true"
                                />
                            </span>
                            <span className="pmc-report-journal__content">
                                <span className="pmc-report-journal__type">
                                    {event.type_label}
                                </span>
                                <strong>{event.title}</strong>
                                {event.subtitle ? (
                                    <span>{event.subtitle}</span>
                                ) : null}
                            </span>
                            <span className="pmc-report-journal__meta">
                                {event.amount !== null &&
                                event.amount !== undefined ? (
                                    <strong className={`is-${event.direction}`}>
                                        {event.direction === 'outflow'
                                            ? '−'
                                            : '+'}
                                        {currency(
                                            event.amount,
                                            locale,
                                            event.currency ?? 'SAR',
                                        )}
                                    </strong>
                                ) : null}
                                <span>
                                    {humanDate(event.occurred_at, locale)}
                                </span>
                                <small>
                                    {t('reports.journal_by', undefined, {
                                        name: event.actor,
                                    })}
                                </small>
                            </span>
                            <i
                                className="bi bi-chevron-right pmc-report-journal__open"
                                aria-hidden="true"
                            />
                        </Link>
                    ))}
                </div>
            )}
        </section>
    );
}
