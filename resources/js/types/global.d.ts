import type { NavigationItemRecord, SharedProps } from '@/types';
import type { Auth } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
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
