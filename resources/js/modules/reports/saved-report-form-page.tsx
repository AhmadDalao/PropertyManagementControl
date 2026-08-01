import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';

import '../../../css/styles/reports.css';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { cleanReportFilters } from './report-query';
import { SavedReportFormActions } from './saved-report-form-actions';
import { SavedReportIdentitySection } from './saved-report-identity-section';
import { SavedReportScopeSection } from './saved-report-scope-section';
import type {
    ReportFilterValues,
    SavedReportFormData,
    SavedReportFormPageProps,
} from './types';

export default function SavedReportFormPage() {
    const { props } = usePage<SavedReportFormPageProps>();
    const { t } = useTranslator();
    const [filters, setFilters] = useState<ReportFilterValues>({
        period: props.filters.period,
        date_from: props.filters.date_from,
        date_to: props.filters.date_to,
        portfolio_id: props.filters.portfolio_id
            ? String(props.filters.portfolio_id)
            : 'all',
        property_id: props.filters.property_id
            ? String(props.filters.property_id)
            : 'all',
    });
    const form = useForm<SavedReportFormData>({
        resource: 'portfolio-report',
        title_en: props.preset?.title_en ?? '',
        title_ar: props.preset?.title_ar ?? '',
        visibility:
            props.preset?.visibility ?? props.visibilityOptions[0] ?? 'private',
        is_default: props.preset?.is_default ?? false,
        filters_json: cleanReportFilters(filters),
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            filters_json: cleanReportFilters(filters),
        }));

        if (props.mode === 'edit' && props.preset) {
            form.put(`/reports/saved/${props.preset.id}`);

            return;
        }

        form.post('/reports/saved');
    };

    return (
        <AdminLayout>
            <Head
                title={t(
                    props.mode === 'edit'
                        ? 'reports.edit_saved_report'
                        : 'reports.create_saved_report',
                )}
            />

            <WorkspaceHeader
                eyebrow={t('reports.saved_reports_eyebrow')}
                title={t(
                    props.mode === 'edit'
                        ? 'reports.edit_saved_report'
                        : 'reports.create_saved_report',
                )}
                description={t(
                    props.mode === 'edit'
                        ? 'reports.edit_saved_report_description'
                        : 'reports.create_saved_report_description',
                )}
                actions={[
                    {
                        label: t('actions.cancel'),
                        href: '/reports/saved',
                        icon: 'bi-arrow-left',
                        tone: 'quiet',
                    },
                ]}
            />

            <form className="pmc-saved-report-form" onSubmit={submit}>
                <SavedReportIdentitySection
                    form={form}
                    visibilityOptions={props.visibilityOptions}
                />
                <SavedReportScopeSection
                    auth={props.auth}
                    filters={filters}
                    portfolioOptions={props.portfolioOptions}
                    propertyContext={props.propertyContext}
                    propertyOptions={props.propertyOptions}
                    setFilters={setFilters}
                    visibility={form.data.visibility}
                />
                <SavedReportFormActions form={form} mode={props.mode} />
            </form>
        </AdminLayout>
    );
}
