import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { ExpenseMetrics } from './expense-metrics';
import { ExpenseTable } from './expense-table';
import type { ExpenseIndexPageProps } from './types';

export default function ExpensesIndexPage() {
    const { props } = usePage<ExpenseIndexPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('expenses.title')} />

            <WorkspaceHeader
                eyebrow={t('expenses.workspace_eyebrow')}
                title={t('expenses.title')}
                description={t('expenses.workspace_description')}
                actions={[
                    {
                        label: t('expenses.reports'),
                        href: '/reports',
                        icon: 'bi-bar-chart-line',
                    },
                    {
                        label: t('expenses.record_expense'),
                        href: '/expenses/create',
                        icon: 'bi-plus-lg',
                        tone: 'primary',
                    },
                ]}
            />

            <ExpenseMetrics
                expenseInsights={props.expenseInsights}
                app={props.app}
            />
            <ExpenseTable {...props} />
        </AdminLayout>
    );
}
