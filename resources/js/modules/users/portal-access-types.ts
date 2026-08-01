import type { ResourceHeaderProps } from '@/components/resource-cycle';
import type { SharedProps } from '@/types';

export type PortalAccessAccount = {
    name: string;
    email: string;
    status: string;
    status_label: string;
    role: string;
    portfolio: string;
    preferred_locale: 'en' | 'ar';
    password_change_required: boolean;
};

export type PortalAccessPayload = {
    header: ResourceHeaderProps;
    account: PortalAccessAccount;
    endpoint: string;
    expiresInMinutes: number;
    canGenerate: boolean;
};

export type PortalAccessLink = {
    url: string;
    expires_at: string;
    expires_in_minutes: number;
};

export type PortalAccessPageProps = SharedProps & {
    portalAccess: PortalAccessPayload;
};
