import { WorkspaceHeader } from '@/components/operations';
import { canCreateOperationalRecord } from '@/lib/access';
import { useTranslator } from '@/lib/i18n';

import type { MaintenanceIndexPageProps } from './types';

export function MaintenanceHeader({
    auth,
    mode,
}: Pick<MaintenanceIndexPageProps, 'auth' | 'mode'>) {
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
                              label: t(
                                  'maintenance.work_order_register_action',
                              ),
                              href: '/maintenance-work-orders',
                              icon: 'bi-clipboard2-check',
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
