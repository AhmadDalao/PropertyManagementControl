import { Link } from '@inertiajs/react';

import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

export type DashboardRecord = {
    href: string;
    title: string;
    meta: string;
    value: string;
    status?: string;
    tone?: 'success' | 'warning' | 'danger';
};

export function DashboardRecordList({
    rows,
    empty,
}: {
    rows: DashboardRecord[];
    empty: string;
}) {
    const { text } = useTranslator();

    if (rows.length === 0) {
        return <div className="pmc-command-empty">{text(empty)}</div>;
    }

    return (
        <div className="pmc-command-list">
            {rows.map((row) => (
                <Link key={`${row.href}-${row.title}`} href={row.href}>
                    <div>
                        <strong>{row.title}</strong>
                        <span>{row.meta}</span>
                    </div>
                    {row.status ? (
                        <StatusBadge value={row.status} />
                    ) : (
                        <em className={`is-${row.tone ?? 'success'}`}>
                            {row.value}
                        </em>
                    )}
                </Link>
            ))}
        </div>
    );
}
