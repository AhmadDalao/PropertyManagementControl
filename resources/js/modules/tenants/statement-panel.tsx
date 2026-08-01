import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

export function StatementPanel({
    title,
    description,
    count,
    empty,
    limit,
    children,
}: {
    title: string;
    description: string;
    count: number;
    empty: string;
    limit?: number;
    children: ReactNode;
}) {
    const { t } = useTranslator();

    return (
        <section className="pmc-tenant-statement-panel">
            <header>
                <div>
                    <p>{title}</p>
                    <span>{description}</span>
                </div>
                <strong>{count}</strong>
            </header>
            {count > 0 ? (
                <div className="pmc-tenant-statement-grid">{children}</div>
            ) : (
                <div className="pmc-tenant-statement-empty">{empty}</div>
            )}
            {limit && count > limit ? (
                <p className="pmc-tenant-statement-limit">
                    {t('tenants.statement_row_limit', undefined, { limit })}
                </p>
            ) : null}
        </section>
    );
}

export function StatementRecord({
    href,
    title,
    subtitle,
    status,
    value,
    detail,
}: {
    href?: string | null;
    title: string;
    subtitle: string;
    status: string;
    value: string;
    detail: string;
}) {
    const content = (
        <>
            <div className="pmc-tenant-statement-record__head">
                <div>
                    <strong>{title}</strong>
                    <span>{subtitle}</span>
                </div>
                <StatusBadge value={status} />
            </div>
            <div className="pmc-tenant-statement-record__meta">
                <span>{detail}</span>
                <strong>{value}</strong>
            </div>
        </>
    );

    return href ? (
        <Link className="pmc-tenant-statement-record" href={href}>
            {content}
        </Link>
    ) : (
        <article className="pmc-tenant-statement-record">{content}</article>
    );
}
