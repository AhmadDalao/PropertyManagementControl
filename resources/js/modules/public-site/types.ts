import type { SharedProps } from '@/types';

export type CmsContent = Record<string, unknown>;
export type CmsItem = Record<string, unknown>;

export type PublicCmsSection = {
    id?: number;
    section_type: string;
    name_en?: string | null;
    name_ar?: string | null;
    content_en?: CmsContent | null;
    content_ar?: CmsContent | null;
};

export type PublicPageSection = {
    id: number;
    cms_page_id?: number;
    cms_section_id?: number;
    sort_order?: number;
    is_visible?: boolean;
    section?: PublicCmsSection | null;
};

export type PublicPageRecord = {
    id?: number;
    slug?: string;
    title_en: string;
    title_ar?: string | null;
    excerpt_en?: string | null;
    excerpt_ar?: string | null;
    seo_title_en?: string | null;
    seo_title_ar?: string | null;
    seo_description_en?: string | null;
    seo_description_ar?: string | null;
    page_sections?: PublicPageSection[];
};

export type CmsRendererProps = {
    sections: PublicPageSection[];
    locale: 'en' | 'ar';
};

export type PublicSitePageProps = SharedProps & {
    page: PublicPageRecord;
};
