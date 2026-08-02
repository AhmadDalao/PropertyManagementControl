import { Head, router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import '../../../css/styles/daily-operations-reports.css';
import { ReportGenerator } from './report-generator';
import { ReportHistory } from './report-history';
import { ReportMetrics } from './report-metrics';
import type { DailyReportIndexProps } from './types';

export default function DailyOperationsReportIndexPage() {
    const { props } = usePage<DailyReportIndexProps>();
    const { t } = useTranslator();

    useEffect(() => {
        if (props.summary.active < 1) {
            return;
        }

        const interval = window.setInterval(() => {
            router.reload({ only: ['reports', 'summary'] });
        }, 5000);

        return () => window.clearInterval(interval);
    }, [props.summary.active]);

    return (
        <AdminLayout>
            <Head title={t('daily_reports.title')} />
            <WorkspaceHeader
                eyebrow={t('daily_reports.eyebrow')}
                title={t('daily_reports.title')}
                description={t('daily_reports.description')}
                actions={[
                    {
                        label: t('daily_reports.back_to_reports'),
                        href: '/reports?tab=library',
                        icon: 'bi-arrow-left',
                        tone: 'quiet',
                    },
                    {
                        label: t('daily_reports.open_action_center'),
                        href: '/action-center',
                        icon: 'bi-list-check',
                        tone: 'secondary',
                    },
                ]}
            />
            <ReportGenerator props={props} />
            <ReportMetrics summary={props.summary} />
            <ReportHistory props={props} />
        </AdminLayout>
    );
}
