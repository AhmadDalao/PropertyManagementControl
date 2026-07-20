import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import type { LeaseBalance, NextAction } from './types';

export const chartColors = [
    '#ef6c2f',
    '#0c8a7c',
    '#ffca4b',
    '#24314a',
    '#38bdf8',
];

export function SectionTitle({
    eyebrow,
    title,
    actionHref,
    actionLabel,
}: {
    eyebrow: string;
    title: string;
    actionHref?: string;
    actionLabel?: string;
}) {
    const { text } = useTranslator();

    return (
        <div className="pmc-section-title">
            <div>
                <div className="pmc-kicker mb-2">{text(eyebrow)}</div>
                <h2>{text(title)}</h2>
            </div>
            {actionHref && actionLabel ? (
                <Link
                    href={actionHref}
                    className="btn btn-outline-secondary btn-sm"
                >
                    {text(actionLabel)}
                </Link>
            ) : null}
        </div>
    );
}

export function NextActionDeck({ actions }: { actions: NextAction[] }) {
    const { t, text } = useTranslator();

    if (actions.length === 0) {
        return null;
    }

    return (
        <section className="pmc-next-action-grid">
            {actions.map((action) => (
                <Link
                    key={`${action.href}-${action.label}`}
                    href={action.href}
                    className="pmc-next-action-card"
                >
                    <i className={`bi ${action.icon}`} />
                    <div>
                        <span>{t('dashboard.next_action')}</span>
                        <strong>{text(action.label)}</strong>
                        <small>{text(action.description)}</small>
                    </div>
                    <em>
                        {t('actions.open')}
                        <i className="bi bi-arrow-right-short" />
                    </em>
                </Link>
            ))}
        </section>
    );
}

export function CycleMap({
    steps,
}: {
    steps: Array<{
        label: string;
        description: string;
        done: boolean;
        href: string;
        icon: string;
    }>;
}) {
    const { t, text } = useTranslator();

    return (
        <section className="pmc-cycle-map">
            <div className="pmc-section-title">
                <div>
                    <div className="pmc-kicker mb-2">
                        {t('dashboard.operating_cycle')}
                    </div>
                    <h2>{t('dashboard.workflow_order')}</h2>
                </div>
                <Link
                    href="/documentation"
                    className="btn btn-outline-secondary btn-sm"
                >
                    {t('dashboard.how_it_works')}
                </Link>
            </div>
            <div className="pmc-cycle-rail">
                {steps.map((step, index) => (
                    <Link
                        key={step.label}
                        href={step.href}
                        className={step.done ? 'is-done' : ''}
                    >
                        <span>{index + 1}</span>
                        <i className={`bi ${step.icon}`} />
                        <strong>{text(step.label)}</strong>
                        <small>{text(step.description)}</small>
                    </Link>
                ))}
            </div>
        </section>
    );
}

export function LeaseList({
    leases,
    locale,
    empty,
    showBalanceOnly = false,
}: {
    leases: LeaseBalance[];
    locale: 'en' | 'ar';
    empty: string;
    showBalanceOnly?: boolean;
}) {
    const { t, text } = useTranslator();

    if (leases.length === 0) {
        return <InlineEmptyState message={empty} />;
    }

    return (
        <div className="pmc-lease-list">
            {leases.map((lease) => (
                <Link key={lease.id} href="/leases">
                    <div>
                        <strong>{lease.code}</strong>
                        <span>
                            {lease.tenant ?? text('No tenant')} ·{' '}
                            {lease.asset ?? text('No asset')}
                        </span>
                    </div>
                    <em>
                        {showBalanceOnly
                            ? currency(
                                  lease.balance_remaining,
                                  locale,
                                  lease.currency,
                              )
                            : t('dashboard.days_count', undefined, {
                                  count: lease.days_remaining ?? 0,
                              })}
                    </em>
                </Link>
            ))}
        </div>
    );
}

export function MiniMetricList({
    source,
    empty,
}: {
    source: Record<string, number>;
    empty: string;
}) {
    const { text } = useTranslator();
    const entries = Object.entries(source);

    if (entries.length === 0) {
        return <InlineEmptyState message={empty} />;
    }

    return (
        <div className="pmc-mini-metric-list">
            {entries.map(([label, value]) => (
                <div key={label}>
                    <span>{text(label.replaceAll('_', ' '))}</span>
                    <strong>{value}</strong>
                </div>
            ))}
        </div>
    );
}

export function ActivityTable({
    rows,
    empty,
}: {
    rows: Array<{ id: number; title: string; meta: string; value: string }>;
    empty: string;
}) {
    if (rows.length === 0) {
        return <InlineEmptyState message={empty} />;
    }

    return (
        <div className="pmc-activity-list">
            {rows.map((row) => (
                <div key={row.id}>
                    <div>
                        <strong>{row.title}</strong>
                        <span>{row.meta}</span>
                    </div>
                    <em>{row.value}</em>
                </div>
            ))}
        </div>
    );
}

export function ChartEmptyState({
    icon,
    title,
    message,
}: {
    icon: string;
    title: string;
    message: string;
}) {
    const { text } = useTranslator();

    return (
        <div className="pmc-chart-empty">
            <i className={`bi ${icon}`} />
            <strong>{text(title)}</strong>
            <span>{text(message)}</span>
        </div>
    );
}

export function InlineEmptyState({ message }: { message: string }) {
    const { text } = useTranslator();

    return <div className="pmc-inline-empty">{text(message)}</div>;
}
