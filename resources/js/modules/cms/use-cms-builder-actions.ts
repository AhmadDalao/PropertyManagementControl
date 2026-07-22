import { router, useForm } from '@inertiajs/react';
import { useRef } from 'react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import { movedPageSections, reorderedPageSections } from './cms-builder-order';
import type { CmsBuilderPageProps, CmsPageSectionRecord } from './types';
import type { CmsBuilderState } from './use-cms-builder-state';

export function useCmsBuilderActions(
    props: CmsBuilderPageProps,
    state: CmsBuilderState,
) {
    const { t } = useTranslator();
    const mutationInFlight = useRef(false);
    const attachForm = useForm({
        cms_section_id: String(props.sections[0]?.id ?? ''),
        sort_order: String(state.orderedSections.length + 1),
        is_visible: true,
    });
    const startMutation = () => {
        if (mutationInFlight.current) {
            return false;
        }

        mutationInFlight.current = true;
        state.setSaveState('saving');

        return true;
    };
    const finishMutation = () => {
        mutationInFlight.current = false;
    };
    const persistSectionOrder = (
        next: CmsPageSectionRecord[],
        previous: CmsPageSectionRecord[],
    ) => {
        if (!startMutation()) {
            return;
        }

        state.setOrderedSections(next);
        router.put(
            `/cms/pages/${props.page.id}/sections/reorder`,
            { ordered_ids: next.map((item) => item.id) },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => state.setSaveState('saved'),
                onError: () => {
                    state.setOrderedSections(previous);
                    state.setSaveState('error');
                },
                onFinish: finishMutation,
            },
        );
    };
    const attachSection = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!startMutation()) {
            return;
        }

        attachForm.post(`/cms/pages/${props.page.id}/sections`, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => state.setSaveState('saved'),
            onError: () => state.setSaveState('error'),
            onFinish: finishMutation,
        });
    };
    const reorder = (targetId: number) => {
        const sourceId = state.draggingId;
        state.setDraggingId(null);

        if (!sourceId) {
            return;
        }

        const next = reorderedPageSections(
            state.orderedSections,
            sourceId,
            targetId,
        );

        if (next) {
            persistSectionOrder(next, state.orderedSections);
        }
    };
    const moveSection = (sectionId: number, direction: -1 | 1) => {
        const next = movedPageSections(
            state.orderedSections,
            sectionId,
            direction,
        );

        if (next) {
            persistSectionOrder(next, state.orderedSections);
        }
    };
    const toggleVisibility = (item: CmsPageSectionRecord) => {
        if (!startMutation()) {
            return;
        }

        const previous = state.orderedSections;
        state.setOrderedSections((current) =>
            current.map((section) =>
                section.id === item.id
                    ? { ...section, is_visible: !section.is_visible }
                    : section,
            ),
        );
        router.put(
            `/cms/page-sections/${item.id}`,
            { is_visible: !item.is_visible },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => state.setSaveState('saved'),
                onError: () => {
                    state.setOrderedSections(previous);
                    state.setSaveState('error');
                },
                onFinish: finishMutation,
            },
        );
    };
    const removeSection = (item: CmsPageSectionRecord) => {
        if (!window.confirm(t('cms.remove_confirm')) || !startMutation()) {
            return;
        }

        router.delete(`/cms/page-sections/${item.id}`, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => state.setSaveState('saved'),
            onError: () => state.setSaveState('error'),
            onFinish: finishMutation,
        });
    };

    return {
        attachForm,
        attachSection,
        isBusy: state.saveState === 'saving' || attachForm.processing,
        moveSection,
        removeSection,
        reorder,
        toggleVisibility,
    };
}
