import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import '../../../css/styles/infrastructure-settings.css';
import { InfrastructureSettingsForm } from './infrastructure-settings-form';
import { InfrastructureStatusGrid } from './infrastructure-status-grid';
import type { InfrastructureSettingsPageProps } from './types';

export default function InfrastructureSettingsIndexPage() {
    const { props } = usePage<InfrastructureSettingsPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('infrastructure_settings.title')} />
            <WorkspaceHeader
                eyebrow={t('infrastructure_settings.eyebrow')}
                title={t('infrastructure_settings.title')}
                description={t('infrastructure_settings.description')}
                actions={[
                    {
                        label: t('infrastructure_settings.open_readiness'),
                        href: '/system/readiness',
                        icon: 'bi-shield-check',
                        tone: 'quiet',
                    },
                    {
                        label: t('infrastructure_settings.open_delivery'),
                        href: '/system/email-delivery',
                        icon: 'bi-envelope-check',
                        tone: 'quiet',
                    },
                ]}
            />
            <InfrastructureStatusGrid checks={props.statusChecks} />
            <InfrastructureSettingsForm
                settings={props.settings}
                testTarget={props.testTarget}
            />
        </AdminLayout>
    );
}
