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
    settings_json?: Record<string, unknown> | null;
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
