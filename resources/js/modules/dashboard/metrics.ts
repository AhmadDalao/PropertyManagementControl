import type { OperationsStats, SetupItem } from './types';

export function operationsHealthScore(
    setupChecklist: SetupItem[],
    stats: OperationsStats,
): number {
    const setupScore =
        setupChecklist.length > 0
            ? (setupChecklist.filter((item) => item.done).length /
                  setupChecklist.length) *
              55
            : 0;
    const leaseScore = stats.activeLeases > 0 ? 20 : 0;
    const revenueScore = stats.monthlyRevenue > 0 ? 15 : 0;
    const servicePenalty = Math.min(stats.openRequests * 4, 20);
    const arrearsPenalty = stats.arrears > 0 ? 15 : 0;

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
