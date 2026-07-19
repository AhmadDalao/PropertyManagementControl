import type { ReactNode } from 'react';

import { useTranslator } from '@/lib/i18n';

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
    const { text } = useTranslator();

    return (
        <section className="pmc-page-head">
            <div>
                <div className="pmc-kicker mb-2">{text(eyebrow)}</div>
                <h1 className="pmc-page-title mb-2">{text(title)}</h1>
                <p className="text-secondary mb-0">{text(description)}</p>
            </div>
            {actions ? <div className="pmc-page-actions">{actions}</div> : null}
        </section>
    );
}
