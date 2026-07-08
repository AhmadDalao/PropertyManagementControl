export type AppUser = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    portfolio_id?: number | null;
    preferred_locale: 'en' | 'ar';
    roles: string[];
};

export type Auth = {
    user: AppUser | null;
};
