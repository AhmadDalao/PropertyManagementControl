import { ActionLink } from '@/components/resource-cycle/action-link';

import type { VendorNotice } from './types';

export function VendorGuidanceCard({ notice }: { notice: VendorNotice }) {
    return (
        <article className={`pmc-vendor-guidance is-${notice.tone}`}>
            <span aria-hidden="true">
                <i className={`bi ${notice.icon}`} />
            </span>
            <h2>{notice.title}</h2>
            <p>{notice.description}</p>
            <div>
                {notice.actions.map((action) => (
                    <ActionLink
                        action={action}
                        key={`${action.href}-${action.label}`}
                    />
                ))}
            </div>
        </article>
    );
}
