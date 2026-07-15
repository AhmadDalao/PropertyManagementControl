import type { ReactNode } from 'react';

type PageHeaderProps = {
    title: string;
    description: string;
    eyebrow?: string;
    actions?: ReactNode;
};

export function PageHeader({
    title,
    description,
    eyebrow = 'Property operations',
    actions,
}: PageHeaderProps) {
    return (
        <section className="pmc-page-head">
            <div>
                <div className="pmc-kicker mb-2">{eyebrow}</div>
                <h1 className="pmc-page-title mb-2">{title}</h1>
                <p className="text-secondary mb-0">{description}</p>
            </div>
            {actions ? <div className="pmc-page-actions">{actions}</div> : null}
        </section>
    );
}
