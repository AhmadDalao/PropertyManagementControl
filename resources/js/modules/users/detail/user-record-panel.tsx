import { DetailCard } from '@/components/resource-cycle/detail-card';
import { RelatedRecordsTable } from '@/components/resource-cycle/related-records-table';

import type { UserDetailPage, UserRelatedKey, UserSectionKey } from './types';

export function UserRecordPanel({
    detail,
    sectionKeys = [],
    relatedKeys = [],
}: {
    detail: UserDetailPage;
    sectionKeys?: UserSectionKey[];
    relatedKeys?: UserRelatedKey[];
}) {
    const sections = sectionKeys
        .map((key) => detail.sections.find((section) => section.key === key))
        .filter((section) => section !== undefined);
    const related = relatedKeys
        .map((key) => detail.related.find((table) => table.key === key))
        .filter((table) => table !== undefined);

    return (
        <div className="pmc-user-record-stack">
            {sections.map((section) => (
                <DetailCard key={section.key} section={section} />
            ))}
            {related.map((table) => (
                <RelatedRecordsTable key={table.key} table={table} />
            ))}
        </div>
    );
}
