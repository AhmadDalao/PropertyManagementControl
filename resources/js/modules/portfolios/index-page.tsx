import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { PortfolioMetrics } from './portfolio-metrics';
import { PortfolioTable } from './portfolio-table';
import type { PortfolioIndexPageProps } from './types';

export default function PortfoliosIndexPage() {
    const { props } = usePage<PortfolioIndexPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('portfolios.title')} />

            <WorkspaceHeader
                eyebrow={t('portfolios.workspace_eyebrow')}
                title={t('portfolios.title')}
                description={t('portfolios.workspace_description')}
                actions={
                    props.canCreate
                        ? [
                              {
                                  label: t('portfolios.create_portfolio'),
                                  href: '/portfolios/create',
                                  icon: 'bi-plus-lg',
                                  tone: 'primary',
                              },
                          ]
                        : []
                }
            />

            <PortfolioMetrics
                insights={props.portfolioInsights}
                locale={props.app.locale}
            />
            <PortfolioTable {...props} />
        </AdminLayout>
    );
}
