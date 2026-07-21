import { WorkspaceHeader } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { TenantDashboardProps } from '../types';

export function TenantHeader({ props }: { props: TenantDashboardProps }) {
    const { t, text } = useTranslator();
    const lease = props.tenantPortal.lease;
    const isArabic = props.app.locale === 'ar';

    return (
        <WorkspaceHeader
            eyebrow="Tenant portal"
            title={
                (isArabic
                    ? lease?.leaseable?.title_ar || lease?.leaseable?.title_en
                    : lease?.leaseable?.title_en ||
                      lease?.leaseable?.title_ar) ?? text('Your rental portal')
            }
            description={
                lease
                    ? `${lease.code} · ${lease.leaseable?.code ?? t('dashboard.rental_asset')}`
                    : text(
                          'Your owner or manager needs to activate a lease before payment and document information appears.',
                      )
            }
            actions={[
                {
                    label: 'Request maintenance',
                    href: '/maintenance-requests/create',
                    icon: 'bi-tools',
                    tone: 'primary',
                },
                {
                    label: 'Tenant guide',
                    href: '/documentation',
                    icon: 'bi-journal-text',
                    tone: 'quiet',
                },
            ]}
        />
    );
}
