import { useTranslator } from '@/lib/i18n';

import type { WordingEntry } from './types';
import { useWordingEditorDialog } from './use-wording-editor-dialog';
import { useWordingEditorForm } from './use-wording-editor-form';
import { WordingEditorForm } from './wording-editor-form';

type WordingEditorProps = {
    entry: WordingEntry;
    groupLabel: string;
    onClose: () => void;
};

export function WordingEditor({
    entry,
    groupLabel,
    onClose,
}: WordingEditorProps) {
    const { t } = useTranslator();
    const { dialogRef, trapFocus } = useWordingEditorDialog(onClose);
    const editor = useWordingEditorForm(entry, onClose, t);

    return (
        <div className="pmc-wording-editor-layer" role="presentation">
            <button
                type="button"
                className="pmc-wording-editor-backdrop"
                aria-label={t('actions.close')}
                onClick={onClose}
            />
            <aside
                ref={dialogRef}
                className="pmc-wording-editor"
                role="dialog"
                aria-modal="true"
                aria-labelledby="wording-editor-title"
                onKeyDown={trapFocus}
            >
                <header>
                    <div>
                        <span>{groupLabel}</span>
                        <h2 id="wording-editor-title">
                            {t('wording.edit_wording')}
                        </h2>
                        <code>
                            {entry.group}.{entry.key}
                        </code>
                    </div>
                    <button
                        type="button"
                        className="btn btn-sm btn-outline-secondary"
                        onClick={onClose}
                        aria-label={t('actions.close')}
                    >
                        <i className="bi bi-x-lg" />
                    </button>
                </header>
                <WordingEditorForm
                    entry={entry}
                    form={editor.form}
                    tokens={editor.tokens}
                    onSubmit={editor.submit}
                    onReset={editor.reset}
                />
            </aside>
        </div>
    );
}
