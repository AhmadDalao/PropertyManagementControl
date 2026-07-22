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
        <div className="pmc-record-actions">
            <Link href={showHref} className="pmc-record-open">
                {t('actions.open', 'Open')}
                <i className="bi bi-arrow-up-right" />
            </Link>
            {editHref ? (
                <Link
                    href={editHref}
                    className="btn btn-outline-secondary btn-sm"
                    aria-label={t('actions.edit_record', 'Edit record')}
                >
                    <i className="bi bi-pencil" />
                    <span>{t('actions.edit', 'Edit')}</span>
                </Link>
            ) : null}
            {children}
        </div>
    );
}
