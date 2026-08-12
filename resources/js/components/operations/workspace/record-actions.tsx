import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { useTranslator } from '@/lib/i18n';

export function RecordActions({
    showHref,
    editHref,
    children,
}: {
    showHref: string;
    editHref?: string;
    children?: ReactNode;
}) {
    const { t } = useTranslator();

    return (
        <details className="pmc-record-action-menu pmc-mobile-action-menu">
            <summary aria-label={t('common.more_actions', 'More actions')}>
                <i className="bi bi-three-dots" aria-hidden="true" />
            </summary>
            <div>
                <Link href={showHref} className="pmc-record-open">
                    <i className="bi bi-box-arrow-up-right" />
                    <span>{t('actions.open', 'Open')}</span>
                </Link>
                {editHref ? (
                    <Link
                        href={editHref}
                        aria-label={t('actions.edit_record', 'Edit record')}
                    >
                        <i className="bi bi-pencil" />
                        <span>{t('actions.edit', 'Edit')}</span>
                    </Link>
                ) : null}
                {children}
            </div>
        </details>
    );
}
