type ScopeItem = { label: string; value: string };

export function ReportCardScope({ scope }: { scope: ScopeItem[] }) {
    return (
        <dl
            className="pmc-report-card-scope"
            aria-label={scope
                .map((item) => `${item.label}: ${item.value}`)
                .join(', ')}
        >
            {scope.map((item) => (
                <div key={item.label}>
                    <i className="bi bi-check2" aria-hidden="true" />
                    <dt>{item.label}</dt>
                    <dd>{item.value}</dd>
                </div>
            ))}
        </dl>
    );
}
