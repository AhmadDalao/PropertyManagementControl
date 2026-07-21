import { WorkspacePanel, humanLabel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency } from '@/lib/utils';

import { BreakdownBars, ReportRecordSection } from './report-visuals';
import type { ReportsPageProps } from './types';

export function ReportCosts({ props }: { props: ReportsPageProps }) {
    const { locale, t, text } = useTranslator();

    return (
        <>
            <div className="pmc-report-breakdown-grid is-single">
                <WorkspacePanel
                    eyebrow={t('reports.costs_eyebrow')}
                    title={t('reports.expense_categories')}
                    description={t('reports.expense_categories_description')}
                >
                    <BreakdownBars
                        source={props.charts.expenseByCategory}
                        format={(value) => currency(value, locale, 'SAR')}
                    />
                </WorkspacePanel>
            </div>
            <div className="pmc-report-record-grid">
                <ReportRecordSection
                    title={t('reports.recent_expenses')}
                    description={t('reports.recent_expenses_description')}
                    empty={t('reports.no_recent_expenses')}
                    rows={props.recentExpenses.map((expense) => ({
                        href: `/expenses/${expense.id}`,
                        title: expense.title,
                        meta: `${text(humanLabel(expense.category))} · ${expense.asset ?? t('reports.no_asset')}`,
                        value: currency(
                            expense.amount,
                            locale,
                            expense.currency,
                        ),
                        tone: 'warning',
                    }))}
                />
            </div>
        </>
    );
}
