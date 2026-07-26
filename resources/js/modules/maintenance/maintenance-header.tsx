import { WorkspaceHeader } from '@/components/operations';
import { canCreateOperationalRecord } from '@/lib/access';
import { useTranslator } from '@/lib/i18n';

import type { MaintenanceIndexPageProps } from './types';

export function MaintenanceHeader({
    auth,
    financialsEnabled,
    mode,
}: Pick<MaintenanceIndexPageProps, 'auth' | 'financialsEnabled' | 'mode'>) {
    const { t } = useTranslator();
    const canCreate = canCreateOperationalRecord(auth.user);

    return (
        <WorkspaceHeader
            eyebrow={t('maintenance.workspace_eyebrow')}
            title={t('maintenance.title')}
            description={
                mode === 'tenant'
                    ? t('maintenance.tenant_description')
                    : t('maintenance.manager_description')
            }
            actions={[
                ...(mode === 'manager'
                    ? [
                          {
                              label: t('maintenance.vendor_directory_action'),
                              href: '/maintenance-vendors',
                              icon: 'bi-building-check',
                              tone: 'quiet' as const,
                          },
                      ]
                    : []),
                ...(mode === 'manager' && financialsEnabled
                    ? [
                          {
                              label: t('maintenance.expenses_action'),
                              href: '/expenses',
                              icon: 'bi-receipt',
                              tone: 'quiet' as const,
                          },
                      ]
                    : []),
                ...(canCreate
                    ? [
                          {
                              label: t('maintenance.create_request'),
                              href: '/maintenance-requests/create',
                              icon: 'bi-plus-lg',
                              tone: 'primary' as const,
                          },
                      ]
                    : []),
            ]}
        />
    );
}
