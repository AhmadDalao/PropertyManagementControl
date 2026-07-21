import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import {
    StatusBadge,
    WorkspacePanel,
    humanLabel,
} from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { ReportRecord } from './types';

export function ReportPulse({
    label,
    value,
    detail,
    icon,
    tone,
}: {
    label: string;
    value: string;
    detail: string;
    icon: string;
    tone: 'good' | 'warn' | 'risk';
}) {
    return (
        <article className={`pmc-report-pulse is-${tone}`}>
            <i className={`bi ${icon}`} aria-hidden="true" />
            <div>
                <span>{label}</span>
                <strong>{value}</strong>
                <small>{detail}</small>
            </div>
        </article>
    );
}

export function BreakdownBars({
    source,
    format,
}: {
    source: Record<string, number>;
    format: (value: number) => string;
}) {
    const { t, text } = useTranslator();
    const entries = Object.entries(source);
    const maximum = Math.max(...entries.map(([, value]) => value), 1);

    if (entries.length === 0) {
        return <ReportEmpty>{t('reports.no_data')}</ReportEmpty>;
    }

    return (
        <div className="pmc-report-bars">
            {entries.map(([label, value]) => (
                <div key={label}>
                    <div>
                        <span>{text(humanLabel(label))}</span>
                        <strong>{format(value)}</strong>
                    </div>
                    <div>
                        <i style={{ width: `${(value / maximum) * 100}%` }} />
                    </div>
                </div>
            ))}
        </div>
    );
}

export function BreakdownCards({ source }: { source: Record<string, number> }) {
    const { t, text } = useTranslator();
    const entries = Object.entries(source);

    if (entries.length === 0) {
        return <ReportEmpty>{t('reports.no_data')}</ReportEmpty>;
    }

    return (
        <div className="pmc-report-breakdown-cards">
            {entries.map(([label, value]) => (
                <div key={label}>
                    <span>{text(humanLabel(label))}</span>
                    <strong>{value.toLocaleString()}</strong>
                </div>
            ))}
        </div>
    );
}

export function ReportRecordSection({
    title,
    description,
    rows,
    empty,
}: {
    title: string;
    description: string;
    rows: ReportRecord[];
    empty: string;
}) {
    return (
        <WorkspacePanel
            title={title}
            description={description}
            className="pmc-report-record-panel"
        >
            {rows.length > 0 ? (
                <div className="pmc-report-record-cards">
                    {rows.slice(0, 6).map((row) => (
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
                            <i
                                className="bi bi-arrow-up-right"
                                aria-hidden="true"
                            />
                        </Link>
                    ))}
                </div>
            ) : (
                <ReportEmpty>{empty}</ReportEmpty>
            )}
        </WorkspacePanel>
    );
}

function ReportEmpty({ children }: { children: ReactNode }) {
    return <div className="pmc-command-empty">{children}</div>;
}
