import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';

import { sortedPageSections } from './cms-builder-order';
import type {
    CmsBuilderPageProps,
    CmsBuilderPanel,
    CmsPageSectionRecord,
    CmsPreviewLocale,
    CmsPreviewWidth,
    CmsSaveState,
    CmsSectionRecord,
} from './types';

export function useCmsBuilderState(props: CmsBuilderPageProps) {
    const { locale, t } = useTranslator();
    const [orderedSections, setOrderedSections] = useState<
        CmsPageSectionRecord[]
    >(() => sortedPageSections(props.page.page_sections));
    const [selectedId, setSelectedId] = useState<number | null>(
        orderedSections[0]?.id ?? null,
    );
    const [draggingId, setDraggingId] = useState<number | null>(null);
    const [mobilePanel, setMobilePanel] = useState<CmsBuilderPanel>('sections');
    const [previewLocale, setPreviewLocale] = useState<CmsPreviewLocale>(
        locale === 'ar' ? 'ar' : 'en',
    );
    const [previewWidth, setPreviewWidth] = useState<CmsPreviewWidth>(() =>
        typeof window !== 'undefined' &&
        window.matchMedia('(max-width: 767.98px)').matches
            ? 'mobile'
            : 'desktop',
    );
    const [saveState, setSaveState] = useState<CmsSaveState>('saved');
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
        (locale === 'ar'
            ? props.page.title_ar || props.page.title_en
            : props.page.title_en || props.page.title_ar) ||
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

    return {
        draggingId,
        localizedPageTitle,
        localizedSectionName,
        mobilePanel,
        orderedSections,
        previewLocale,
        previewWidth,
        saveState,
        selected,
        selectedLibraryRecord,
        setDraggingId,
        setMobilePanel,
        setOrderedSections,
        setPreviewLocale,
        setPreviewWidth,
        setSaveState,
        setSelectedId,
        visibleSections,
    };
}

export type CmsBuilderState = ReturnType<typeof useCmsBuilderState>;
