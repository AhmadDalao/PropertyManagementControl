import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

export function CmsSectionFormActions({
    editing,
    processing,
}: {
    editing: boolean;
    processing: boolean;
}) {
    const { t } = useTranslator();

    return (
        <div className="pmc-cms-form-actions">
            <Link href="/cms" className="btn btn-light">
                {t('actions.cancel')}
            </Link>
            <button
                type="submit"
                className="btn btn-primary"
                disabled={processing}
            >
                {editing ? t('cms.update_section') : t('cms.create_section')}
            </button>
        </div>
    );
}
