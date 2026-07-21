export function ShowcaseBadge({ label }: { label: string }) {
    return (
        <span className="pmc-table-showcase-badge">
            <i className="bi bi-stars" />
            {label}
        </span>
    );
}
