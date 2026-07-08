import type { ReactNode } from 'react';

type PageHeaderProps = {
    title: string;
    description: string;
    actions?: ReactNode;
};

export function PageHeader({ title, description, actions }: PageHeaderProps) {
    return (
        <div className="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-end mb-4">
            <div>
                <div className="pmc-kicker mb-2">Property management control</div>
                <h1 className="pmc-page-title mb-2">{title}</h1>
                <p className="text-secondary mb-0">{description}</p>
            </div>
            {actions ? <div className="d-flex gap-2">{actions}</div> : null}
        </div>
    );
}
