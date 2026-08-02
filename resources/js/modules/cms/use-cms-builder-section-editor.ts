import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import { jsonText, parseJsonObject } from './section-schema';
import type { CmsSectionRecord } from './types';

export function useCmsBuilderSectionEditor(
    pageId: number,
    section: CmsSectionRecord,
    onSaved: () => void,
) {
    const { t } = useTranslator();
    const [contentError, setContentError] = useState('');
    const form = useForm({
        section_type: section.section_type,
        name_en: section.name_en,
        name_ar: section.name_ar ?? '',
        status: section.status,
        content_en_json: jsonText(section.content_en ?? {}),
        content_ar_json: jsonText(section.content_ar ?? {}),
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
            settings_json: section.settings_json ?? {},
        }));
        form.put(`/cms/pages/${pageId}/sections/${section.id}/content`, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: onSaved,
        });
    };

    return { contentError, form, submit };
}
