export function ReportCardScope({ labels }: { labels: string[] }) {
    return (
        <div className="pmc-report-card-scope" aria-label={labels.join(', ')}>
            {labels.map((label) => (
                <span key={label}>
                    <i className="bi bi-check2" aria-hidden="true" />
                    {label}
                </span>
            ))}
        </div>
    );
}
