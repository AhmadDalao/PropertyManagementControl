import { Head, Link, usePage } from '@inertiajs/react';

import '../../../css/styles/reports.css';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import { ReportPresetList } from './report-preset-list';
import type { SavedReportsPageProps } from './types';

export default function SavedReportsPage() {
    const { props } = usePage<SavedReportsPageProps>();
    const { locale, t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('reports.saved_reports_title')} />

            <WorkspaceHeader
                eyebrow={t('reports.saved_reports_eyebrow')}
                title={t('reports.saved_reports_title')}
                description={t('reports.saved_reports_description')}
                actions={[
                    {
                        label: t('reports.back_to_reports'),
                        href: '/reports',
                        icon: 'bi-arrow-left',
                        tone: 'quiet',
                    },
                    {
                        label: t('reports.create_saved_report'),
                        href: '/reports/saved/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <section className="pmc-saved-report-workspace">
                <header>
                    <div>
                        <span>{t('reports.saved_report_library')}</span>
                        <h2>{t('reports.reusable_report_views')}</h2>
                        <p>{t('reports.reusable_report_views_help')}</p>
                    </div>
                    <strong>
                        {t('reports.saved_report_count', undefined, {
                            count: localizedNumber(
                                props.savedPresets.length,
                                locale,
                            ),
                        })}
                    </strong>
                </header>

                {props.savedPresets.length > 0 ? (
                    <ReportPresetList presets={props.savedPresets} />
                ) : (
                    <div className="pmc-saved-report-empty">
                        <i className="bi bi-bookmark" aria-hidden="true" />
                        <div>
                            <h3>{t('reports.no_saved_reports_title')}</h3>
                            <p>{t('reports.no_saved_reports_description')}</p>
                        </div>
                        <Link
                            href="/reports/saved/create"
                            className="btn btn-primary"
                        >
                            {t('reports.create_saved_report')}
                        </Link>
                    </div>
                )}
            </section>
        </AdminLayout>
    );
}
