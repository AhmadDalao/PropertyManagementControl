import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type EmailDeliveryStatus = 'processing' | 'accepted' | 'failed';

export type EmailDeliveryRecord = {
    id: number;
    notification_id: string;
    recipient_email: string;
    subject: string;
    email_type: string;
    type_label: string;
    status: EmailDeliveryStatus;
    status_label: string;
    mailer: string;
    attempts: number;
    portfolio?: string | null;
    user?: string | null;
    created_at?: string | null;
    started_at?: string | null;
    accepted_at?: string | null;
    failed_at?: string | null;
    error_message?: string | null;
    notification_class?: string;
    transport_message_id?: string | null;
    url: string;
};

export type EmailDeliveryInsights = {
    total: number;
    accepted: number;
    failed: number;
    processing: number;
    acceptance_rate: number;
};

export type EmailDeliveryIndexPageProps = SharedProps & {
    deliveries: PaginatedData<EmailDeliveryRecord>;
    filters: TableFilters;
    counts: TableCount[];
    insights: EmailDeliveryInsights;
    typeOptions: Array<{ label: string; value: string }>;
};

export type EmailDeliveryShowPageProps = SharedProps & {
    delivery: EmailDeliveryRecord;
};
