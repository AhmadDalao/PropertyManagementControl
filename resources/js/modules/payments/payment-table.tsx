import { DataTable, exportUrl } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { paymentFilterFields } from './payment-filters';
import { usePaymentTableConfig } from './payment-table-config';
import type { PaymentIndexPageProps } from './types';

type PaymentTableProps = Pick<
    PaymentIndexPageProps,
    | 'payments'
    | 'filters'
    | 'counts'
    | 'portfolioOptions'
    | 'statusOptions'
    | 'typeOptions'
    | 'methodOptions'
    | 'auth'
    | 'app'
>;

export function PaymentTable(props: PaymentTableProps) {
    const { t } = useTranslator();
    const table = usePaymentTableConfig(props.app.locale);
    const filters = paymentFilterFields(
        {
            statuses: props.statusOptions,
            types: props.typeOptions,
            methods: props.methodOptions,
            portfolios: props.portfolioOptions,
            includePortfolio:
                props.auth.user?.roles.includes('superadmin') ?? false,
        },
        t,
    );

    return (
        <DataTable
            title={t('payments.ledger_title')}
            description={t('payments.ledger_description')}
            data={props.payments}
            filters={props.filters}
            counts={props.counts}
            basePath="/payments"
            rowHref={(payment) => `/payments/${payment.id}`}
            exportHref={exportUrl('/exports/payments', props.filters)}
            filterFields={filters}
            columns={table.columns}
            mobileCard={table.mobileCard}
            emptyText={t('payments.empty')}
        />
    );
}
