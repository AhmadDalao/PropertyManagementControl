import { DetailCard } from '@/components/resource-cycle/detail-card';
import { useTranslator } from '@/lib/i18n';

import type { DocumentDetailPage } from './types';

export function DocumentAccessPanel({
    detail,
}: {
    detail: DocumentDetailPage;
}) {
    const { t } = useTranslator();
    const access = detail.sections.find((section) => section.key === 'access');

    return (
        <div className="pmc-document-policy-layout">
            <main>{access ? <DetailCard section={access} /> : null}</main>
            <aside>
                <article className="pmc-document-policy-card is-access">
                    <span aria-hidden="true">
                        <i className="bi bi-shield-lock" />
                    </span>
                    <h2>{t('documents.access_policy_title')}</h2>
                    <p>{t('documents.access_policy_help')}</p>
                </article>
            </aside>
        </div>
    );
}
