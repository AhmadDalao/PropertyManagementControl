import type { ReactNode } from 'react';

export type WorkspaceAction = {
    label: string;
    href: string;
    icon?: string;
    tone?: 'primary' | 'secondary' | 'quiet';
    native?: boolean;
};

export type WorkspaceMetric = {
    label: string;
    value: ReactNode;
    detail?: ReactNode;
    icon: string;
    tone?: 'ink' | 'blue' | 'teal' | 'amber' | 'red';
    href?: string;
};
