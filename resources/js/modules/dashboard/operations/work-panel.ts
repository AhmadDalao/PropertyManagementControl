import type { OperationsWorkSection } from '../types';

export function workPanelClass(
    section: OperationsWorkSection,
    active: OperationsWorkSection,
): string {
    return `pmc-dashboard-work-panel${section === active ? ' is-active' : ''}`;
}
