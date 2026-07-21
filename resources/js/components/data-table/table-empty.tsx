import { Link } from '@inertiajs/react';

export function TableEmpty({
    title,
    message,
    createHref,
    createLabel,
}: {
    title: string;
    message: string;
    createHref?: string;
    createLabel: string;
}) {
    return (
        <div className="pmc-empty-state">
            <i className="bi bi-search" />
            <strong>{title}</strong>
            <span>{message}</span>
            {createHref ? (
                <Link href={createHref} className="btn btn-primary btn-sm">
                    <i className="bi bi-plus-lg" />
                    {createLabel}
                </Link>
            ) : null}
        </div>
    );
}
