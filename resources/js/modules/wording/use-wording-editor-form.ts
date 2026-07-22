import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import type { Translator } from '@/lib/i18n';

import type { WordingEntry, WordingFormData } from './types';

export function useWordingEditorForm(
    entry: WordingEntry,
    onClose: () => void,
    t: Translator,
) {
    const form = useForm<WordingFormData>({
        group: entry.group,
        key: entry.key,
        english: entry.english,
        arabic: entry.arabic,
    });
    const tokens = requiredTokens(entry);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.put('/wording', {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    const reset = () => {
        if (!window.confirm(t('wording.reset_confirm'))) {
            return;
        }

        router.delete('/wording', {
            data: { group: entry.group, key: entry.key },
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return { form, reset, submit, tokens };
}

function requiredTokens(entry: WordingEntry): string[] {
    return Array.from(
        new Set(
            `${entry.default_english} ${entry.default_arabic}`.match(
                /:[A-Za-z_]+/g,
            ) ?? [],
        ),
    );
}
