import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type {
    CmsBuilderPageProps,
    CmsBuilderPanel,
    CmsPageSectionRecord,
    CmsPreviewLocale,
    CmsPreviewWidth,
    CmsSaveState,
    CmsSectionRecord,
} from './types';

export function useCmsBuilder(props: CmsBuilderPageProps) {
    const { locale, t } = useTranslator();
    const sortedFromServer = [...props.page.page_sections].sort(
        (a, b) => a.sort_order - b.sort_order,
    );
    const [orderedSections, setOrderedSections] =
        useState<CmsPageSectionRecord[]>(sortedFromServer);
    const [selectedId, setSelectedId] = useState<number | null>(
        sortedFromServer[0]?.id ?? null,
    );
    const [draggingId, setDraggingId] = useState<number | null>(null);
    const [mobilePanel, setMobilePanel] = useState<CmsBuilderPanel>('sections');
    const [previewLocale, setPreviewLocale] = useState<CmsPreviewLocale>('en');
    const [previewWidth, setPreviewWidth] = useState<CmsPreviewWidth>(() =>
        typeof window !== 'undefined' &&
        window.matchMedia('(max-width: 767.98px)').matches
            ? 'mobile'
            : 'desktop',
    );
    const [saveState, setSaveState] = useState<CmsSaveState>('saved');
    const attachForm = useForm({
        cms_section_id: String(props.sections[0]?.id ?? ''),
        sort_order: String((props.page.page_sections.length || 0) + 1),
        is_visible: true,
    });
    const selected =
        orderedSections.find((item) => item.id === selectedId) ??
        orderedSections[0] ??
        null;
    const selectedLibraryRecord = props.sections.find(
        (section) => section.id === selected?.cms_section_id,
    );
    const visibleSections = orderedSections.filter(
        (item) => item.is_visible && item.section,
    );
    const localizedPageTitle =
        locale === 'ar'
            ? props.page.title_ar || props.page.title_en
            : props.page.title_en ||
              props.page.title_ar ||
              t('cms.untitled_page');
    const localizedSectionName = (
        section: CmsSectionRecord | null | undefined,
        targetLocale: CmsPreviewLocale = locale === 'ar' ? 'ar' : 'en',
    ) => {
        if (!section) {
            return t('cms.missing_section');
        }

        return targetLocale === 'ar'
            ? section.name_ar || section.name_en || t('cms.missing_section')
            : section.name_en || section.name_ar || t('cms.missing_section');
    };
    const persistSectionOrder = (
        nextSections: CmsPageSectionRecord[],
        previousSections = orderedSections,
    ) => {
        setOrderedSections(
            nextSections.map((item, index) => ({
                ...item,
                sort_order: index + 1,
            })),
        );
        setSaveState('saving');
        router.put(
            `/cms/pages/${props.page.id}/sections/reorder`,
            { ordered_ids: nextSections.map((item) => item.id) },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => setSaveState('saved'),
                onError: () => {
                    setOrderedSections(previousSections);
                    setSaveState('error');
                },
            },
        );
    };
    const attachSection = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        attachForm.post(`/cms/pages/${props.page.id}/sections`, {
            preserveScroll: true,
            preserveState: false,
            onStart: () => setSaveState('saving'),
            onSuccess: () => setSaveState('saved'),
            onError: () => setSaveState('error'),
        });
    };
    const reorder = (targetId: number) => {
        if (!draggingId || draggingId === targetId) {
            setDraggingId(null);

            return;
        }

        const previous = [...orderedSections];
        const next = [...orderedSections];
        const fromIndex = next.findIndex((item) => item.id === draggingId);
        const toIndex = next.findIndex((item) => item.id === targetId);

        if (fromIndex === -1 || toIndex === -1) {
            setDraggingId(null);

            return;
        }

        next.splice(toIndex, 0, next.splice(fromIndex, 1)[0]);
        setDraggingId(null);
        persistSectionOrder(next, previous);
    };
    const moveSection = (sectionId: number, direction: -1 | 1) => {
        const previous = [...orderedSections];
        const next = [...orderedSections];
        const currentIndex = next.findIndex((item) => item.id === sectionId);
        const nextIndex = currentIndex + direction;

        if (currentIndex === -1 || nextIndex < 0 || nextIndex >= next.length) {
            return;
        }

        [next[currentIndex], next[nextIndex]] = [
            next[nextIndex],
            next[currentIndex],
        ];
        persistSectionOrder(next, previous);
    };
    const toggleVisibility = (item: CmsPageSectionRecord) => {
        const previous = [...orderedSections];
        setOrderedSections((current) =>
            current.map((section) =>
                section.id === item.id
                    ? { ...section, is_visible: !section.is_visible }
                    : section,
            ),
        );
        setSaveState('saving');
        router.put(
            `/cms/page-sections/${item.id}`,
            { is_visible: !item.is_visible },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => setSaveState('saved'),
                onError: () => {
                    setOrderedSections(previous);
                    setSaveState('error');
                },
            },
        );
    };
    const removeSection = (item: CmsPageSectionRecord) => {
        if (!window.confirm(t('cms.remove_confirm'))) {
            return;
        }

        router.delete(`/cms/page-sections/${item.id}`, {
            preserveScroll: true,
            preserveState: false,
        });
    };

    return {
        attachForm,
        attachSection,
        draggingId,
        libraryLimitReached: props.libraryLimitReached,
        localizedPageTitle,
        localizedSectionName,
        mobilePanel,
        moveSection,
        orderedSections,
        page: props.page,
        previewLocale,
        previewWidth,
        removeSection,
        reorder,
        saveState,
        sections: props.sections,
        selected,
        selectedLibraryRecord,
        setDraggingId,
        setMobilePanel,
        setPreviewLocale,
        setPreviewWidth,
        setSelectedId,
        timeline: props.timeline,
        toggleVisibility,
        visibleSections,
    };
}

export type CmsBuilderController = ReturnType<typeof useCmsBuilder>;
