type StatCardProps = {
    title: string;
    value: string | number | null | undefined;
    hint?: string;
    tone?: 'default' | 'accent' | 'teal';
};

export function StatCard({
    title,
    value,
    hint,
    tone = 'default',
}: StatCardProps) {
    const toneClass =
        tone === 'accent'
            ? 'pmc-card--accent'
            : tone === 'teal'
              ? 'pmc-card--teal'
              : '';

    return (
        <div className={`pmc-card p-4 h-100 ${toneClass}`}>
            <div className="pmc-kicker mb-3">{title}</div>
            <div className="pmc-metric mb-2">{value ?? '-'}</div>
            {hint ? <div className="text-secondary small">{hint}</div> : null}
        </div>
    );
}
