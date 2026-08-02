import type { ReactNode } from 'react';

export type WorkspaceDownload = {
    label: string;
    href: string;
    icon?: string;
};

type WorkspaceActionBase = {
    label: string;
    icon?: string;
    tone?: 'primary' | 'secondary' | 'quiet';
};

export type WorkspaceAction = WorkspaceActionBase &
    (
        | {
              href: string;
              native?: boolean;
              downloads?: never;
          }
        | {
              downloads: WorkspaceDownload[];
              href?: never;
              native?: never;
          }
    );

export type WorkspaceMetric = {
    label: string;
    value: ReactNode;
    detail?: ReactNode;
    icon: string;
    tone?: 'ink' | 'blue' | 'teal' | 'amber' | 'red';
    href?: string;
};
