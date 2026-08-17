import { DetailCard } from '@/components/resource-cycle/detail-card';
import { RelatedRecordsTable } from '@/components/resource-cycle/related-records-table';

import type {
    TenantDetailPage,
    TenantRelatedKey,
    TenantSectionKey,
} from './types';

export function TenantRecordPanel({
    detail,
    sectionKeys = [],
    relatedKeys = [],
}: {
    detail: TenantDetailPage;
    sectionKeys?: TenantSectionKey[];
    relatedKeys?: TenantRelatedKey[];
}) {
    const sections = detail.sections.filter((section) =>
        sectionKeys.includes(section.key),
    );
    const related = detail.related.filter((table) =>
        relatedKeys.includes(table.key),
    );

    return (
        <div className="pmc-tenant-record-stack">
            {sections.map((section) => (
                <DetailCard section={section} key={section.key} />
            ))}
            {related.map((table) => (
                <RelatedRecordsTable table={table} key={table.key} />
            ))}
        </div>
    );
}
