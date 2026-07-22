import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import {
    defaultSectionContent,
    jsonText,
    parseJsonObject,
} from './section-schema';
import type { CmsSectionRecord } from './types';

export function useCmsSectionForm(section: CmsSectionRecord | null) {
    const { locale, t } = useTranslator();
    const sectionTitle = section
        ? locale === 'ar'
            ? section.name_ar || section.name_en || ''
            : section.name_en || section.name_ar || ''
        : '';
    const pageTitle = section
        ? t('cms.edit_section', undefined, { title: sectionTitle })
        : t('cms.create_section');
    const initialType = section?.section_type ?? 'hero';
    const [contentError, setContentError] = useState('');
    const form = useForm({
        section_type: initialType,
        name_en: section?.name_en ?? '',
        name_ar: section?.name_ar ?? '',
        status: section?.status ?? 'active',
        content_en_json: jsonText(
            section?.content_en ?? defaultSectionContent(initialType, 'en'),
        ),
        content_ar_json: jsonText(
            section?.content_ar ?? defaultSectionContent(initialType, 'ar'),
        ),
    });
    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const contentEn = parseJsonObject(form.data.content_en_json);
        const contentAr = parseJsonObject(form.data.content_ar_json);

        if (!contentEn || !contentAr) {
            setContentError(t('cms.invalid_json'));

            return;
        }

        setContentError('');
        form.transform((data) => ({
            section_type: data.section_type,
            name_en: data.name_en,
            name_ar: data.name_ar,
            status: data.status,
            content_en: contentEn,
            content_ar: contentAr,
            settings_json: {},
        }));

        if (section) {
            form.put(`/cms/sections/${section.id}`);

            return;
        }

        form.post('/cms/sections');
    };

    return { contentError, form, pageTitle, submit };
}

export type CmsSectionFormController = ReturnType<typeof useCmsSectionForm>;
