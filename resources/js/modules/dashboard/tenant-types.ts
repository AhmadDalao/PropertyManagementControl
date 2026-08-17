import type { SharedProps } from '@/types';

import type { NextAction } from './shared-types';

export type TenantDashboardProps = SharedProps & {
    mode: 'tenant';
    stats: {
        leaseCode: string | null;
        daysLeft: number | null;
        amountLeft: number;
        dueNow: number;
        overdue: number;
        paidAmount: number;
        maintenanceRequests: number;
        maintenanceConfirmations: number;
    };
    nextActions: NextAction[];
    tenantPortal: {
        lease: {
            id: number;
            code: string;
            status: string;
            days_remaining: number | null;
            balance_remaining: number;
            due_now: number;
            overdue: number;
            next_due_date?: string | null;
            total_paid: number;
            rent_amount: number;
            currency: string;
            started_at?: string | null;
            ends_at?: string | null;
            leaseable?: {
                title_en?: string;
                title_ar?: string;
                code?: string;
            } | null;
            contract_url: string;
            statement_url: string;
        } | null;
        documents: Array<{
            id: number;
            title_en: string;
            title_ar?: string | null;
            type: string;
            download_url: string;
        }>;
        payments: Array<{
            id: number;
            amount: number;
            currency: string;
            received_on: string | null;
            reference?: string | null;
            receipt_url: string;
        }>;
        requests: Array<{
            id: number;
            title: string;
            status: string;
            created_at: string | null;
        }>;
    };
};
