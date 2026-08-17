import { DetailCard } from '@/components/resource-cycle/detail-card';

import type { ExpenseDetailPage } from './types';

export function ExpenseFinancialPanel({
    detail,
}: {
    detail: ExpenseDetailPage;
}) {
    const financial = detail.sections.find(
        (section) => section.key === 'financial',
    );

    return (
        <div className="pmc-expense-record-stack">
            {financial ? <DetailCard section={financial} /> : null}
        </div>
    );
}
