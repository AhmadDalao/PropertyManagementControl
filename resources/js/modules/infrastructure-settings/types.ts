import type { SharedProps } from '@/types';

export type InfrastructureStatus = 'ready' | 'attention' | 'blocked';

export type InfrastructureStatusCheck = {
    key: 'mail' | 'scheduler' | 'queue';
    label: string;
    description: string;
    detail: string;
    status: InfrastructureStatus;
};

export type InfrastructureSettings = {
    mail_enabled: boolean;
    mail_host: string;
    mail_port: number;
    mail_scheme: 'smtp' | 'smtps';
    mail_username: string;
    mail_from_address: string;
    mail_from_name: string;
    password_configured: boolean;
    smtp_ready: boolean;
    scheduler_php_binary: string;
    scheduler_command: string;
    scheduler_artisan_path: string;
    updated_at?: string | null;
    updated_by?: string | null;
};

export type InfrastructureSettingsInput = {
    mail_enabled: boolean;
    mail_host: string;
    mail_port: number | string;
    mail_scheme: 'smtp' | 'smtps';
    mail_username: string;
    mail_password: string;
    clear_mail_password: boolean;
    mail_from_address: string;
    mail_from_name: string;
    scheduler_php_binary: string;
};

export type InfrastructureSettingsPageProps = SharedProps & {
    settings: InfrastructureSettings;
    statusChecks: InfrastructureStatusCheck[];
    testTarget: string;
};
