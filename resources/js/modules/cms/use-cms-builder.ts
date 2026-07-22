import type { CmsBuilderPageProps } from './types';
import { useCmsBuilderActions } from './use-cms-builder-actions';
import { useCmsBuilderState } from './use-cms-builder-state';

export function useCmsBuilder(props: CmsBuilderPageProps) {
    const state = useCmsBuilderState(props);
    const actions = useCmsBuilderActions(props, state);

    return {
        ...state,
        ...actions,
        libraryLimitReached: props.libraryLimitReached,
        page: props.page,
        sections: props.sections,
        timeline: props.timeline,
    };
}

export type CmsBuilderController = ReturnType<typeof useCmsBuilder>;
