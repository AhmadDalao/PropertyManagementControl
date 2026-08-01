import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import {
    StatusBadge,
    WorkspacePanel,
    humanLabel,
} from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, localizedNumber } from '@/lib/utils';

import type { CurrencyBreakdown, ReportRecord } from './types';

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
    formatLabel,
}: {
    source: CurrencyBreakdown[];
    formatLabel?: (label: string) => string;
}) {
    const { locale, t, text } = useTranslator();
    const groups = source.reduce<Record<string, CurrencyBreakdown[]>>(
        (result, item) => {
            (result[item.currency] ??= []).push(item);

            return result;
        },
        {},
    );

    if (source.length === 0) {
        return <ReportEmpty>{t('reports.no_data')}</ReportEmpty>;
    }

    return (
        <div className="pmc-report-currency-breakdowns">
            {Object.entries(groups).map(([currencyCode, entries]) => {
                const maximum = Math.max(
                    ...entries.map((item) => item.amount),
                    1,
                );

                return (
                    <section key={currencyCode}>
                        <header>
                            <span>{t('reports.currency')}</span>
                            <strong>{currencyCode}</strong>
                        </header>
                        <div className="pmc-report-bars">
                            {entries.map((item) => (
                                <div key={`${currencyCode}-${item.label}`}>
                                    <div>
                                        <span>
                                            {formatLabel
                                                ? formatLabel(item.label)
                                                : text(humanLabel(item.label))}
                                        </span>
                                        <strong>
                                            {currency(
                                                item.amount,
                                                locale,
                                                currencyCode,
                                            )}
                                        </strong>
                                    </div>
                                    <div>
                                        <i
                                            style={{
                                                width: `${(item.amount / maximum) * 100}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                );
            })}
        </div>
    );
}

export function BreakdownCards({ source }: { source: Record<string, number> }) {
    const { locale, t, text } = useTranslator();
    const entries = Object.entries(source);

    if (entries.length === 0) {
        return <ReportEmpty>{t('reports.no_data')}</ReportEmpty>;
    }

    return (
        <div className="pmc-report-breakdown-cards">
            {entries.map(([label, value]) => (
                <div key={label}>
                    <span>{text(humanLabel(label))}</span>
                    <strong>{localizedNumber(value, locale)}</strong>
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
