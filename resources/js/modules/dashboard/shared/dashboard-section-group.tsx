import { useId, useState } from 'react';
import type { ReactNode } from 'react';

type DashboardSectionGroupProps = {
    name: string;
    title: string;
    description: string;
    icon: string;
    defaultOpen?: boolean;
    children: ReactNode;
};

export function DashboardSectionGroup({
    name,
    title,
    description,
    icon,
    defaultOpen = false,
    children,
}: DashboardSectionGroupProps) {
    const [open, setOpen] = useState(defaultOpen);
    const contentId = useId();

    return (
        <section
            className={`pmc-dashboard-section-group ${open ? 'is-open' : ''}`}
            data-dashboard-group={name}
        >
            <button
                type="button"
                aria-expanded={open}
                aria-controls={contentId}
                onClick={() => setOpen((current) => !current)}
            >
                <span className="pmc-dashboard-section-icon">
                    <i className={`bi ${icon}`} aria-hidden="true" />
                </span>
                <span>
                    <strong>{title}</strong>
                    <small>{description}</small>
                </span>
                <i
                    className="bi bi-chevron-down pmc-dashboard-section-chevron"
                    aria-hidden="true"
                />
            </button>
            <div id={contentId} className="pmc-dashboard-section-body">
                {children}
            </div>
        </section>
    );
}
