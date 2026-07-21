import { Head, Link, usePage } from '@inertiajs/react';

import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import type { SharedProps } from '@/types';

import '../../../css/styles/property-map.css';

import { PropertyMapWorkspace } from './map-workspace';
import { PropertyMapPortfolioFilter } from './portfolio-filter';
import type { PropertyMapPayload, PropertyMapPortfolioOption } from './types';

type PropertyMapPageProps = SharedProps & {
    propertyMap: PropertyMapPayload;
    portfolioOptions: PropertyMapPortfolioOption[];
    filters: {
        portfolio_id?: number | null;
    };
};

export default function PropertyMapPage() {
    const { props } = usePage<PropertyMapPageProps>();
    const { t } = useTranslator();
    const isSuperadmin = props.auth.user?.roles.includes('superadmin') ?? false;
    const selectedPortfolio = props.filters.portfolio_id
        ? String(props.filters.portfolio_id)
        : 'all';

    return (
        <AdminLayout>
            <Head title={t('map.title')} />

            <PageHeader
                title={t('map.title')}
                description={t('map.description')}
                actions={
                    <>
                        <Link href="/assets/create" className="btn btn-primary">
                            <i className="bi bi-plus-lg" />
                            {t('map.create_asset')}
                        </Link>
                        <Link
                            href="/assets"
                            className="btn btn-outline-secondary"
                        >
                            {t('map.asset_workspace')}
                        </Link>
                    </>
                }
            />

            <PropertyMapWorkspace
                assets={props.propertyMap.assets}
                summary={props.propertyMap.summary}
                config={props.propertyMap.config}
                toolbar={
                    isSuperadmin ? (
                        <PropertyMapPortfolioFilter
                            options={props.portfolioOptions}
                            selectedPortfolio={selectedPortfolio}
                        />
                    ) : null
                }
            />
        </AdminLayout>
    );
}
