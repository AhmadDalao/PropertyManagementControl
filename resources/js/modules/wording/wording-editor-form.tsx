import type { InertiaFormProps } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { WordingEntry, WordingFormData } from './types';

type WordingEditorFormProps = {
    entry: WordingEntry;
    form: InertiaFormProps<WordingFormData>;
    tokens: string[];
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    onReset: () => void;
};

export function WordingEditorForm({
    entry,
    form,
    tokens,
    onSubmit,
    onReset,
}: WordingEditorFormProps) {
    const { t } = useTranslator();

    return (
        <form onSubmit={onSubmit}>
            <p>{t('wording.edit_description')}</p>
            {tokens.length > 0 ? (
                <div className="pmc-wording-tokens">
                    {t('wording.required_tokens', undefined, {
                        tokens: tokens.join(', '),
                    })}
                </div>
            ) : null}
            <label>
                <span>{t('wording.english')}</span>
                <textarea
                    dir="ltr"
                    rows={5}
                    value={form.data.english}
                    onChange={(event) =>
                        form.setData('english', event.currentTarget.value)
                    }
                />
                {form.errors.english ? (
                    <small className="text-danger">{form.errors.english}</small>
                ) : null}
            </label>
            <label>
                <span>{t('wording.arabic')}</span>
                <textarea
                    dir="rtl"
                    rows={5}
                    value={form.data.arabic}
                    onChange={(event) =>
                        form.setData('arabic', event.currentTarget.value)
                    }
                />
                {form.errors.arabic ? (
                    <small className="text-danger">{form.errors.arabic}</small>
                ) : null}
            </label>
            <footer>
                {entry.customized ? (
                    <button
                        type="button"
                        className="btn btn-outline-secondary"
                        onClick={onReset}
                    >
                        {t('wording.reset')}
                    </button>
                ) : (
                    <span />
                )}
                <button
                    type="submit"
                    className="btn btn-primary"
                    disabled={form.processing}
                >
                    {form.processing ? t('wording.saving') : t('wording.save')}
                </button>
            </footer>
        </form>
    );
}
