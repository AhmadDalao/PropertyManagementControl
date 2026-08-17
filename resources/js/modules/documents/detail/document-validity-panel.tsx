import { Link } from '@inertiajs/react';

import { DetailCard } from '@/components/resource-cycle/detail-card';

import type { DocumentDetailPage } from './types';

export function DocumentValidityPanel({
    detail,
}: {
    detail: DocumentDetailPage;
}) {
    const validity = detail.sections.find(
        (section) => section.key === 'validity',
    );

    return (
        <div className="pmc-document-policy-layout">
            <main>{validity ? <DetailCard section={validity} /> : null}</main>
            <aside>
                <article className="pmc-document-policy-card is-validity">
                    <span aria-hidden="true">
                        <i className="bi bi-files" />
                    </span>
                    <h2>{detail.replacement.title}</h2>
                    <p>{detail.replacement.description}</p>
                    {detail.replacement.can_upload &&
                    detail.replacement.upload_url ? (
                        <Link
                            className="btn btn-primary"
                            href={detail.replacement.upload_url}
                        >
                            <i className="bi bi-upload" />
                            {detail.replacement.action_label}
                        </Link>
                    ) : (
                        <small>{detail.replacement.unavailable}</small>
                    )}
                </article>
            </aside>
        </div>
    );
}
