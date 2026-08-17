import { DetailCard } from '@/components/resource-cycle/detail-card';
import { RelatedRecordsTable } from '@/components/resource-cycle/related-records-table';

import type {
    AssetDetailPage,
    AssetRelatedKey,
    AssetSectionKey,
} from './types';

export function AssetRecordPanel({
    detail,
    sectionKeys = [],
    relatedKeys = [],
}: {
    detail: AssetDetailPage;
    sectionKeys?: AssetSectionKey[];
    relatedKeys?: AssetRelatedKey[];
}) {
    const sections = sectionKeys
        .map((key) => detail.sections.find((section) => section.key === key))
        .filter((section) => section !== undefined);
    const related = relatedKeys
        .map((key) => detail.related.find((table) => table.key === key))
        .filter((table) => table !== undefined);

    return (
        <div className="pmc-asset-record-stack">
            {sections.map((section) => (
                <DetailCard key={section.key} section={section} />
            ))}
            {related.map((table) => (
                <RelatedRecordsTable key={table.key} table={table} />
            ))}
        </div>
    );
}
