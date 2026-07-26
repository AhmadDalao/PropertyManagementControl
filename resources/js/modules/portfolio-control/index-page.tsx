import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/portfolio-control.css';

import { WorkspaceHeader, WorkspacePanel } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { PortfolioControlFilters } from './portfolio-control-filters';
import { PortfolioControlMetrics } from './portfolio-control-metrics';
import { PropertyControlGrid } from './property-control-grid';
import type { PortfolioControlProps } from './types';

export default function PortfolioControlPage() {
    const { props } = usePage<PortfolioControlProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('portfolio_control.title')} />
            <WorkspaceHeader
                eyebrow={t('portfolio_control.eyebrow')}
                title={t('portfolio_control.title')}
                description={t('portfolio_control.description')}
                actions={[
                    {
                        label: t('nav.action_center'),
                        href: '/action-center',
                        icon: 'bi-collection',
                        tone: 'primary',
                    },
                    {
                        label: t('nav.property_map'),
                        href: '/property-map',
                        icon: 'bi-map',
                    },
                ]}
            />
            <PortfolioControlMetrics summary={props.summary} />
            <WorkspacePanel
                eyebrow={t('portfolio_control.workspace_eyebrow')}
                title={t('portfolio_control.directory')}
                description={t('portfolio_control.directory_description')}
            >
                <PortfolioControlFilters
                    filters={props.filters}
                    counts={props.counts}
                    portfolioOptions={props.portfolioOptions}
                />
                <PropertyControlGrid properties={props.properties} />
            </WorkspacePanel>
        </AdminLayout>
    );
}
