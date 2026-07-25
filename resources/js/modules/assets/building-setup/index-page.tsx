import { Head, usePage } from '@inertiajs/react';

import { ResourceHeader } from '@/components/resource-cycle';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import '../../../../css/styles/assets/building-setup.css';
import { BuildingSetupForm } from './building-setup-form';
import type { BuildingSetupPageProps } from './types';

export default function BuildingSetupIndexPage() {
    const { props } = usePage<BuildingSetupPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={props.buildingSetup.title} />
            <div className="pmc-building-setup">
                <ResourceHeader
                    eyebrow={t('assets.workspace_eyebrow')}
                    title={props.buildingSetup.title}
                    description={props.buildingSetup.description}
                    backHref={props.buildingSetup.backHref}
                    backLabel={t('assets.all_assets')}
                    actions={[
                        {
                            label: t('assets.builder.single_asset'),
                            href: '/assets/create',
                            variant: 'light',
                        },
                    ]}
                />
                <BuildingSetupForm payload={props.buildingSetup} />
            </div>
        </AdminLayout>
    );
}
