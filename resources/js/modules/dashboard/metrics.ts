import type { DashboardPageProps } from './types';

export function operationsHealthScore(
    setupChecklist: Array<{ done: boolean }>,
    stats: DashboardPageProps['stats'],
): number {
    const setupScore =
        setupChecklist.length > 0
            ? (setupChecklist.filter((item) => item.done).length /
                  setupChecklist.length) *
              55
            : 0;
    const leaseScore = Number(stats.activeLeases ?? 0) > 0 ? 20 : 0;
    const revenueScore = Number(stats.monthlyRevenue ?? 0) > 0 ? 15 : 0;
    const servicePenalty = Math.min(Number(stats.openRequests ?? 0) * 4, 20);
    const arrearsPenalty = Number(stats.arrears ?? 0) > 0 ? 15 : 0;

    return Math.max(
        0,
        Math.min(
            100,
            Math.round(
                setupScore +
                    leaseScore +
                    revenueScore -
                    servicePenalty -
                    arrearsPenalty,
            ),
        ),
    );
}

export function operationsCycleSteps(
    setupChecklist: Array<{ label: string; done: boolean; href: string }>,
    stats: DashboardPageProps['stats'],
) {
    const done = (label: string) =>
        setupChecklist.find((item) => item.label === label)?.done ?? false;

    return [
        {
            label: 'Portfolio',
            description: 'Create the owner boundary and business profile.',
            done: done('Create portfolio'),
            href: '/portfolios',
            icon: 'bi-buildings',
        },
        {
            label: 'Assets',
            description: 'Model buildings, floors, units, and spaces.',
            done: done('Create assets'),
            href: '/assets',
            icon: 'bi-diagram-3',
        },
        {
            label: 'Tenants',
            description: 'Add tenant profiles and portal access.',
            done: done('Create profiles'),
            href: '/tenants',
            icon: 'bi-person-badge',
        },
        {
            label: 'Leases',
            description: 'Generate contracts and installments.',
            done: done('Create leases') || Number(stats.activeLeases ?? 0) > 0,
            href: '/leases',
            icon: 'bi-file-earmark-text',
        },
        {
            label: 'Payments',
            description: 'Post rent, receipts, and outstanding balances.',
            done: Number(stats.monthlyRevenue ?? 0) > 0,
            href: '/payments',
            icon: 'bi-cash-stack',
        },
        {
            label: 'Service',
            description: 'Track maintenance requests and expenses.',
            done: Number(stats.openRequests ?? 0) === 0,
            href: '/maintenance-requests',
            icon: 'bi-tools',
        },
    ];
}
