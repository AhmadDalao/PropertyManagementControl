import type { SavedReportOutput } from './types';

export function SavedReportOutputCard({
    output,
}: {
    output: SavedReportOutput;
}) {
    return (
        <article
            className={`pmc-saved-report-output-card is-${output.format.toLowerCase()}`}
        >
            <header>
                <span aria-hidden="true">
                    <i className={`bi ${output.icon}`} />
                </span>
                <strong>{output.format}</strong>
            </header>
            <div>
                <h2>{output.title}</h2>
                <p>{output.description}</p>
                <small>{output.subtitle}</small>
            </div>
            <a href={output.href} className="btn btn-primary">
                <i className="bi bi-download" aria-hidden="true" />
                {output.label}
            </a>
        </article>
    );
}
