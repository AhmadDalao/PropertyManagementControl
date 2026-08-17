import { DetailCard } from '@/components/resource-cycle/detail-card';
import { RelatedRecordsTable } from '@/components/resource-cycle/related-records-table';

import type {
    PortfolioDetailPage,
    PortfolioRelatedKey,
    PortfolioSectionKey,
} from './types';

export function PortfolioRecordPanel({
    detail,
    sectionKeys = [],
    relatedKeys = [],
}: {
    detail: PortfolioDetailPage;
    sectionKeys?: PortfolioSectionKey[];
    relatedKeys?: PortfolioRelatedKey[];
}) {
    const sections = sectionKeys
        .map((key) => detail.sections.find((section) => section.key === key))
        .filter((section) => section !== undefined);
    const related = relatedKeys
        .map((key) => detail.related.find((table) => table.key === key))
        .filter((table) => table !== undefined);

    return (
        <div className="pmc-portfolio-record-stack">
            {sections.map((section) => (
                <DetailCard key={section.key} section={section} />
            ))}
            {related.map((table) => (
                <RelatedRecordsTable key={table.key} table={table} />
            ))}
        </div>
    );
}
