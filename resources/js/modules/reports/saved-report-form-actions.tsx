import type { InertiaFormProps } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { SavedReportFormData } from './types';

type SavedReportFormActionsProps = {
    form: InertiaFormProps<SavedReportFormData>;
    mode: 'create' | 'edit';
};

export function SavedReportFormActions({
    form,
    mode,
}: SavedReportFormActionsProps) {
    const { t } = useTranslator();

    return (
        <footer>
            <a href="/reports/saved" className="btn btn-light">
                {t('actions.cancel')}
            </a>
            <button
                type="submit"
                className="btn btn-primary"
                disabled={form.processing}
            >
                {form.processing
                    ? t('actions.working')
                    : t(
                          mode === 'edit'
                              ? 'reports.update_saved_report'
                              : 'reports.create_saved_report',
                      )}
            </button>
        </footer>
    );
}
