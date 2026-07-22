import { useDeferredValue, useState } from 'react';

import type {
    ControlCheck,
    DocumentationIndexPageProps,
    Guide,
    PageShortcut,
    WorkflowTrack,
} from './types';

export type DocumentationSearchResult = {
    query: string;
    setQuery: (query: string) => void;
    guides: Guide[];
    workflows: WorkflowTrack[];
    shortcuts: PageShortcut[];
    checks: ControlCheck[];
    hasResults: boolean;
};

export function useDocumentationSearch(
    props: DocumentationIndexPageProps,
): DocumentationSearchResult {
    const [query, setQuery] = useState('');
    const deferredQuery = useDeferredValue(query.trim().toLocaleLowerCase());
    const guides = props.guides.filter((guide) =>
        matches(
            [guide.title, guide.audience, guide.summary, ...guide.features],
            deferredQuery,
        ),
    );
    const workflows = props.workflowTracks.filter((workflow) =>
        matches(
            [
                workflow.title,
                workflow.summary,
                workflow.outcome,
                ...workflow.steps.map((step) => step.label),
            ],
            deferredQuery,
        ),
    );
    const shortcuts = props.pageShortcuts
        .filter((shortcut) =>
            matches(
                [shortcut.label, shortcut.category, shortcut.description],
                deferredQuery,
            ),
        )
        .slice(0, 8);
    const checks = props.controlChecks.filter((check) =>
        matches([check.title, check.summary, ...check.checks], deferredQuery),
    );

    return {
        query,
        setQuery,
        guides,
        workflows,
        shortcuts,
        checks,
        hasResults:
            guides.length +
                workflows.length +
                shortcuts.length +
                checks.length >
            0,
    };
}

function matches(values: string[], query: string): boolean {
    return query === '' || values.join(' ').toLocaleLowerCase().includes(query);
}
