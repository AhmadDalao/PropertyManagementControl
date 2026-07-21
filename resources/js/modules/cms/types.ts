import type { FormDataConvertible } from '@inertiajs/core';

import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
} from '@/types';

export type CmsPageRecord = {
    id: number;
    title_en: string;
    title_ar?: string | null;
    slug: string;
    excerpt_en?: string | null;
    excerpt_ar?: string | null;
    status: string;
    is_homepage: boolean;
    is_visible: boolean;
    published_at?: string | null;
    created_at?: string | null;
    page_sections_count?: number;
    page_sections?: CmsPageSectionRecord[];
};

export type CmsSectionRecord = {
    id: number;
    name_en: string;
    name_ar?: string | null;
    section_type: string;
    status: string;
    content_en?: Record<string, unknown> | null;
    content_ar?: Record<string, unknown> | null;
    settings_json?: Record<string, unknown> | null;
    page_sections_count?: number;
};

export type CmsPageSectionRecord = {
    id: number;
    cms_page_id: number;
    cms_section_id: number;
    sort_order: number;
    is_visible: boolean;
    settings_json?: Record<string, FormDataConvertible> | null;
    section?: CmsSectionRecord | null;
};

export type NavigationRecord = {
    id: number;
    parent_id?: number | null;
    cms_page_id?: number | null;
    title_en: string;
    title_ar?: string | null;
    url?: string | null;
    location: string;
    target?: string | null;
    sort_order?: number | null;
    is_visible?: boolean;
    page?: CmsPageRecord | null;
    children?: NavigationRecord[];
};

export type CmsWorkspaceView = 'pages' | 'sections' | 'navigation';

export type CmsWorkspaceStats = {
    pages: number;
    published: number;
    sections: number;
    active_sections: number;
    navigation: number;
    visible_navigation: number;
};

export type CmsIndexPageProps = SharedProps & {
    view: CmsWorkspaceView;
    pages: PaginatedData<CmsPageRecord>;
    filters: TableFilters;
    counts: TableCount[];
    workspaceStats: CmsWorkspaceStats;
    sections: CmsSectionRecord[];
    sectionLimitReached: boolean;
    navigationItems: NavigationRecord[];
    navigationLimitReached: boolean;
};

export type CmsTimelineRecord = {
    id: number;
    event: string;
    causer?: string;
    created_at?: string;
};

export type CmsBuilderPageProps = SharedProps & {
    page: CmsPageRecord & { page_sections: CmsPageSectionRecord[] };
    sections: CmsSectionRecord[];
    libraryLimitReached: boolean;
    timeline: CmsTimelineRecord[];
};

export type CmsBuilderPanel = 'sections' | 'preview' | 'settings';
export type CmsPreviewLocale = 'en' | 'ar';
export type CmsPreviewWidth = 'desktop' | 'mobile';
export type CmsSaveState = 'saved' | 'saving' | 'error';
