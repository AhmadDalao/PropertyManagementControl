import { useTranslator } from '@/lib/i18n';

import type { VendorDetailPage } from './types';
import { VendorGuidanceCard } from './vendor-guidance-card';
import { VendorWorkOrderGrid } from './vendor-work-order-grid';

export function VendorWorkloadPanel({ detail }: { detail: VendorDetailPage }) {
    const { t } = useTranslator();

    return (
        <div className="pmc-vendor-section-layout">
            <main>
                <VendorWorkOrderGrid
                    title={t('maintenance_vendors.open_jobs')}
                    description={t('maintenance_vendors.open_jobs_help')}
                    orders={detail.workload.open}
                    allHref={detail.workload.allHref}
                />
                <VendorWorkOrderGrid
                    title={t('maintenance_vendors.recent_history')}
                    description={t('maintenance_vendors.recent_history_help')}
                    orders={detail.workload.history}
                    allHref={detail.workload.allHref}
                />
            </main>
            <aside>
                <VendorGuidanceCard notice={detail.notices.workload} />
            </aside>
        </div>
    );
}
