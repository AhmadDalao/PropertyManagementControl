import type { PaginatedData, SharedProps } from '@/types';

export type NotificationTone =
    'neutral' | 'blue' | 'success' | 'warning' | 'danger';

export type NotificationItem = {
    id: string;
    event: string;
    title: string;
    body: string;
    icon: string;
    tone: NotificationTone;
    resource_type: string;
    resource_label: string;
    target_href: string;
    read_href: string;
    read: boolean;
    created_at: string | null;
};

export type NotificationSummary = {
    unread_count: number;
    recent: NotificationItem[];
};

export type NotificationIndexPageProps = SharedProps & {
    filters: {
        status: 'all' | 'unread' | 'read';
        type: 'all' | 'maintenance_request' | 'payment' | 'lease' | 'document';
        search: string;
    };
    counts: {
        all: number;
        unread: number;
        read: number;
    };
    typeCounts: {
        all: number;
        maintenance_request: number;
        payment: number;
        lease: number;
        document: number;
    };
    notificationItems: PaginatedData<NotificationItem>;
};
