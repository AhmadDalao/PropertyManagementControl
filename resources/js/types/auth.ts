export type AppUser = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    portfolio_id?: number | null;
    preferred_locale: 'en' | 'ar';
    status?: string;
    force_password_reset?: boolean;
    last_login_at?: string | null;
    roles: string[];
};

export type Auth = {
    user: AppUser | null;
};
