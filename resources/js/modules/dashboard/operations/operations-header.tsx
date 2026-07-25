import { WorkspaceHeader } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { OperationsDashboardProps } from '../types';

export function OperationsHeader({
    mode,
    propertyFocus,
}: {
    mode: OperationsDashboardProps['mode'];
    propertyFocus: OperationsDashboardProps['propertyFocus'];
}) {
    const { t } = useTranslator();
    const managerNeedsAssignment =
        propertyFocus.assignment_restricted && !propertyFocus.has_assignments;

    return (
        <WorkspaceHeader
            eyebrow={
                mode === 'superadmin'
                    ? t('dashboard.system_overview')
                    : t('dashboard.portfolio_overview')
            }
            title={t('dashboard.operations_title')}
            description={t('dashboard.operations_description')}
            actions={
                mode === 'superadmin'
                    ? [
                          {
                              label: t('dashboard.create_portfolio'),
                              href: '/portfolios/create',
                              icon: 'bi-plus-lg',
                              tone: 'primary',
                          },
                          {
                              label: t('dashboard.create_user'),
                              href: '/users/create',
                              icon: 'bi-person-plus',
                          },
                          {
                              label: t('nav.reports'),
                              href: '/reports',
                              icon: 'bi-bar-chart-line',
                              tone: 'quiet',
                          },
                      ]
                    : managerNeedsAssignment
                      ? [
                            {
                                label: t(
                                    'dashboard.property_assignment_action',
                                ),
                                href: '/portfolios',
                                icon: 'bi-building',
                                tone: 'primary',
                            },
                        ]
                      : [
                            {
                                label: t('dashboard.start_tenancy'),
                                href: '/tenants/create?next=lease',
                                icon: 'bi-file-earmark-plus',
                                tone: 'primary',
                            },
                            {
                                label: t('dashboard.post_payment'),
                                href: '/payments/create',
                                icon: 'bi-cash-stack',
                            },
                            {
                                label: t('dashboard.new_maintenance'),
                                href: '/maintenance-requests/create',
                                icon: 'bi-tools',
                                tone: 'quiet',
                            },
                        ]
            }
        />
    );
}
