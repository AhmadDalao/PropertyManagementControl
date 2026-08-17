import { DetailCard } from '@/components/resource-cycle/detail-card';
import { useTranslator } from '@/lib/i18n';

import type { VendorDetailPage } from './types';
import { VendorGuidanceCard } from './vendor-guidance-card';
import { VendorWorkOrderGrid } from './vendor-work-order-grid';

export function VendorSectionPanel({
    detail,
    sectionKey,
}: {
    detail: VendorDetailPage;
    sectionKey: 'schedule' | 'financial';
}) {
    const { t } = useTranslator();
    const section = detail.sections.find(
        (candidate) => candidate.key === sectionKey,
    );
    const orders =
        sectionKey === 'schedule'
            ? detail.workload.open
            : detail.workload.history;

    return (
        <div className="pmc-vendor-section-layout">
            <main>
                {section ? <DetailCard section={section} /> : null}
                <VendorWorkOrderGrid
                    title={t(
                        sectionKey === 'schedule'
                            ? 'maintenance_vendors.open_jobs'
                            : 'maintenance_vendors.recent_history',
                    )}
                    description={t(
                        sectionKey === 'schedule'
                            ? 'maintenance_vendors.open_jobs_help'
                            : 'maintenance_vendors.recent_history_help',
                    )}
                    orders={orders}
                    allHref={detail.workload.allHref}
                />
            </main>
            <aside>
                <VendorGuidanceCard notice={detail.notices[sectionKey]} />
            </aside>
        </div>
    );
}
