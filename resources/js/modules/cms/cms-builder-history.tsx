import { useTranslator } from '@/lib/i18n';
import { dateTime } from '@/lib/utils';

import type { CmsTimelineRecord } from './types';

export function CmsBuilderHistory({
    timeline,
}: {
    timeline: CmsTimelineRecord[];
}) {
    const { locale, t, text } = useTranslator();

    return (
        <details className="pmc-cms-history">
            <summary>
                <span>{t('cms.recent_activity')}</span>
                <strong>{timeline.length}</strong>
            </summary>
            <div className="pmc-history-timeline">
                {timeline.map((event) => (
                    <div key={event.id}>
                        <span />
                        <strong>{text(event.event)}</strong>
                        <small>
                            {event.causer ?? t('cms.system_actor')} ·{' '}
                            {dateTime(event.created_at, locale)}
                        </small>
                    </div>
                ))}
            </div>
        </details>
    );
}
