import { ActionLink } from '@/components/resource-cycle/action-link';

import type { WorkOrderNotice } from './types';

export function WorkOrderGuidanceCard({ notice }: { notice: WorkOrderNotice }) {
    return (
        <article
            className={`pmc-work-order-guidance is-${notice.tone ?? 'muted'}`}
        >
            <span aria-hidden="true">
                <i className={`bi ${notice.icon}`} />
            </span>
            <h2>{notice.title}</h2>
            <p>{notice.description}</p>
            {notice.actions.length > 0 ? (
                <div>
                    {notice.actions.map((action) => (
                        <ActionLink
                            action={action}
                            key={`${action.href}-${action.label}`}
                        />
                    ))}
                </div>
            ) : null}
        </article>
    );
}
