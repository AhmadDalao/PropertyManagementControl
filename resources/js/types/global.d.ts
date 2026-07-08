import type { Auth } from '@/types/auth';
import type { NavigationItemRecord, SharedProps } from '@/types';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface PageProps extends SharedProps {
        auth: Auth;
        publicNavigation: {
            header: NavigationItemRecord[];
        };
        [key: string]: unknown;
    }
}
