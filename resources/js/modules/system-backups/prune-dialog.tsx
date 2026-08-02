import type { FormEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { SystemBackupRecord } from './types';

export function PruneDialog({
    backup,
    busy,
    onCancel,
    onConfirm,
}: {
    backup: SystemBackupRecord;
    busy: boolean;
    onCancel: () => void;
    onConfirm: (event: FormEvent) => void;
}) {
    const { t } = useTranslator();

    return (
        <div className="pmc-dialog-backdrop" role="presentation">
            <section
                className="pmc-backup-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="backup-prune-title"
            >
                <div className="pmc-backup-dialog-icon">
                    <i className="bi bi-trash3" aria-hidden="true" />
                </div>
                <h2 id="backup-prune-title">{t('backups.prune_title')}</h2>
                <p>
                    {t('backups.prune_description', undefined, {
                        id: backup.id,
                    })}
                </p>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        onConfirm(event);
                    }}
                >
                    <button
                        type="button"
                        className="btn btn-outline-secondary"
                        disabled={busy}
                        onClick={onCancel}
                    >
                        {t('actions.cancel')}
                    </button>
                    <button
                        type="submit"
                        className="btn btn-danger"
                        disabled={busy}
                    >
                        {busy
                            ? t('backups.pruning')
                            : t('backups.confirm_prune')}
                    </button>
                </form>
            </section>
        </div>
    );
}
