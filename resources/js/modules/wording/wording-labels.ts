import { humanLabel } from '@/components/operations';
import type { Translator, UiTranslationKey } from '@/lib/i18n';

const CONTENT_MODULE_KEYS: Record<string, UiTranslationKey> = {
    portfolios: 'wording.module_portfolios',
    assets: 'wording.module_assets',
    documents: 'wording.module_documents',
    media: 'wording.module_media',
    cms_pages: 'wording.module_cms_pages',
    cms_sections: 'wording.module_cms_sections',
    navigation: 'wording.module_navigation',
    report_presets: 'wording.module_report_presets',
};

const MISSING_FIELD_KEYS: Record<string, UiTranslationKey> = {
    name_ar: 'wording.field_name_ar',
    address_ar: 'wording.field_address_ar',
    title_ar: 'wording.field_title_ar',
    alt_text_ar: 'wording.field_alt_text_ar',
    excerpt_ar: 'wording.field_excerpt_ar',
    content_ar: 'wording.field_content_ar',
};

export function wordingGroupLabel(group: string, t: Translator): string {
    return group === 'all'
        ? t('wording.all_areas')
        : t(`wording.group_${group}` as UiTranslationKey, humanLabel(group));
}

export function contentModuleLabel(module: string, t: Translator): string {
    const key = CONTENT_MODULE_KEYS[module];

    return key ? t(key) : humanLabel(module);
}

export function missingFieldLabel(field: string, t: Translator): string {
    const key = MISSING_FIELD_KEYS[field];

    return key ? t(key) : humanLabel(field);
}
