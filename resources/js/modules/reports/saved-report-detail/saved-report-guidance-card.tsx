import type { SavedReportNotice } from './types';

export function SavedReportGuidanceCard({
    notice,
}: {
    notice: SavedReportNotice;
}) {
    return (
        <article className={`pmc-saved-report-guidance is-${notice.tone}`}>
            <span aria-hidden="true">
                <i className={`bi ${notice.icon}`} />
            </span>
            <h2>{notice.title}</h2>
            <p>{notice.description}</p>
        </article>
    );
}
