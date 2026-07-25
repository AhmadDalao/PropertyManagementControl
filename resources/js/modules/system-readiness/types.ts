import type { SharedProps } from '@/types';

export type ReadinessStatus = 'ready' | 'attention' | 'blocked';

export type AutomaticReadinessCheck = {
    key: string;
    label: string;
    description: string;
    status: ReadinessStatus;
    detail?: string;
    href?: string;
    action_label?: string;
    meta?: Record<string, string | number | null>;
};

export type ReadinessConfirmation = {
    key: string;
    label: string;
    description: string;
    is_confirmed: boolean;
    evidence: string | null;
    confirmed_at: string | null;
    confirmed_by: string | null;
    portfolio_id: number | null;
};

export type PortfolioOption = {
    id: number;
    name: string;
    code: string;
    is_showcase: boolean;
};

export type PortfolioReadiness = {
    portfolio: {
        id: number;
        name: string;
        code: string;
        status: string;
        is_showcase: boolean;
    };
    metrics: {
        owners: number;
        managers: number;
        tenants: number;
        properties: number;
        current_leases: number;
        assignment_gaps: number;
    };
    checks: AutomaticReadinessCheck[];
};

export type PortfolioLaunch = {
    live_portfolios: number;
    needs_live_portfolio: boolean;
    create_href: string;
};

export type SystemReadinessPageProps = SharedProps & {
    checkedAt: string;
    summary: {
        total: number;
        ready: number;
        attention: number;
        blocked: number;
    };
    systemChecks: AutomaticReadinessCheck[];
    systemConfirmations: ReadinessConfirmation[];
    portfolioOptions: PortfolioOption[];
    portfolioReadiness: PortfolioReadiness | null;
    portfolioConfirmations: ReadinessConfirmation[];
    portfolioLaunch: PortfolioLaunch;
    mailTest: {
        enabled: boolean;
        target: string;
    };
};
