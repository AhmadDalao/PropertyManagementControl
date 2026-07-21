import type { SharedProps } from '@/types';

export type ProfileRecord = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    preferred_locale: 'en' | 'ar';
    status: string;
    force_password_reset: boolean;
    last_login_at?: string | null;
    roles: string[];
    portfolio?: {
        id: number;
        name_en: string;
        name_ar: string;
        code: string;
        status: string;
    } | null;
    tenant_profile?: {
        id: number;
        profile_type: string;
        status: string;
    } | null;
};

export type ProfilePageProps = SharedProps & {
    profile: ProfileRecord;
};
