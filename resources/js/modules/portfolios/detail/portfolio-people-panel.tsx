import { DetailCard } from '@/components/resource-cycle/detail-card';
import { RelatedRecordsTable } from '@/components/resource-cycle/related-records-table';

import { PortfolioModuleGrid } from './portfolio-module-grid';
import type { PortfolioDetailPage } from './types';

export function PortfolioPeoplePanel({
    detail,
}: {
    detail: PortfolioDetailPage;
}) {
    const ownership = detail.sections.find(
        (section) => section.key === 'ownership',
    );
    const people = detail.related.find((table) => table.key === 'people');

    return (
        <div className="pmc-portfolio-record-stack">
            {ownership ? <DetailCard section={ownership} /> : null}
            <PortfolioModuleGrid modules={detail.modules} />
            {people ? <RelatedRecordsTable table={people} /> : null}
        </div>
    );
}
