import type { CmsPageSectionRecord } from './types';

export function sortedPageSections(sections: CmsPageSectionRecord[]) {
    return [...sections].sort(
        (left, right) => left.sort_order - right.sort_order,
    );
}

export function reorderedPageSections(
    sections: CmsPageSectionRecord[],
    sourceId: number,
    targetId: number,
) {
    if (sourceId === targetId) {
        return null;
    }

    const next = [...sections];
    const sourceIndex = next.findIndex((item) => item.id === sourceId);
    const targetIndex = next.findIndex((item) => item.id === targetId);

    if (sourceIndex === -1 || targetIndex === -1) {
        return null;
    }

    next.splice(targetIndex, 0, next.splice(sourceIndex, 1)[0]);

    return withNormalizedOrder(next);
}

export function movedPageSections(
    sections: CmsPageSectionRecord[],
    sectionId: number,
    direction: -1 | 1,
) {
    const next = [...sections];
    const currentIndex = next.findIndex((item) => item.id === sectionId);
    const nextIndex = currentIndex + direction;

    if (currentIndex === -1 || nextIndex < 0 || nextIndex >= next.length) {
        return null;
    }

    [next[currentIndex], next[nextIndex]] = [
        next[nextIndex],
        next[currentIndex],
    ];

    return withNormalizedOrder(next);
}

function withNormalizedOrder(sections: CmsPageSectionRecord[]) {
    return sections.map((item, index) => ({
        ...item,
        sort_order: index + 1,
    }));
}
