import type { GlobalSearchResult } from './types';

export function groupSearchResults(
    results: GlobalSearchResult[],
): Record<string, GlobalSearchResult[]> {
    return results.reduce<Record<string, GlobalSearchResult[]>>(
        (groups, result) => {
            groups[result.group] = [...(groups[result.group] ?? []), result];

            return groups;
        },
        {},
    );
}
