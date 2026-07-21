import { useTranslator } from '@/lib/i18n';

import type { ResourceTimelineEntry } from './types';

export function HistoryTimeline({
    timeline,
}: {
    timeline: ResourceTimelineEntry[];
}) {
    const { t, text } = useTranslator();

    return (
        <article className="pmc-card p-4 pmc-side-panel">
            <div className="pmc-kicker mb-2">{t('common.history')}</div>
            <h2>{t('resource.audit_trail')}</h2>
            {timeline.length > 0 ? (
                <div className="pmc-history-timeline">
                    {timeline.map((item) => (
                        <div key={item.id}>
                            <span />
                            <strong>{text(item.event)}</strong>
                            <small>
                                {item.causer ?? t('resource.system')} ·{' '}
                                {item.created_at ?? ''}
                            </small>
                            {item.description ? (
                                <p>{item.description}</p>
                            ) : null}
                        </div>
                    ))}
                </div>
            ) : (
                <p className="pmc-empty-inline">
                    {t('resource.no_audit_events')}
                </p>
            )}
        </article>
    );
}
