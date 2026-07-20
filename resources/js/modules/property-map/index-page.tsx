import { Head, Link, router, usePage } from '@inertiajs/react';
import type { ChangeEvent } from 'react';

import { PageHeader } from '@/components/page-header';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import type { SharedProps } from '@/types';

import '../../../css/styles/property-map.css';

import { PropertyMapWorkspace } from './map-workspace';
import type { PropertyMapPayload } from './types';

type PropertyMapPageProps = SharedProps & {
    propertyMap: PropertyMapPayload;
    portfolioOptions: Array<{ id: number; name: string }>;
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

    const changePortfolio = (event: ChangeEvent<HTMLSelectElement>) => {
        const portfolioId = event.currentTarget.value;

        router.get(
            '/property-map',
            portfolioId === 'all' ? {} : { portfolio_id: portfolioId },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    };

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
                        <label>
                            <span>{t('map.portfolio')}</span>
                            <select
                                className="form-select"
                                value={selectedPortfolio}
                                onChange={changePortfolio}
                            >
                                <option value="all">
                                    {t('map.all_portfolios')}
                                </option>
                                {props.portfolioOptions.map((portfolio) => (
                                    <option
                                        key={portfolio.id}
                                        value={portfolio.id}
                                    >
                                        {portfolio.name}
                                    </option>
                                ))}
                            </select>
                        </label>
                    ) : null
                }
            />
        </AdminLayout>
    );
}
