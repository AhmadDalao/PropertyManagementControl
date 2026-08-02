import { useTranslator } from '@/lib/i18n';
import { dateTime } from '@/lib/utils';

import { formatBackupBytes } from './format';
import type { SystemBackupRecord } from './types';

export function BackupCard({
    backup,
    busy,
    onPrune,
}: {
    backup: SystemBackupRecord;
    busy: boolean;
    onPrune: (backup: SystemBackupRecord) => void;
}) {
    const { locale, t } = useTranslator();
    const finishedAt =
        backup.completed_at ?? backup.failed_at ?? backup.started_at;

    return (
        <article className={`pmc-backup-card is-${backup.status}`}>
            <header>
                <div>
                    <span>
                        {t('backups.run_number', undefined, {
                            id: backup.id,
                        })}
                    </span>
                    <h3>{backup.status_label}</h3>
                </div>
                <span className={`pmc-backup-status is-${backup.status}`}>
                    {backup.status_label}
                </span>
            </header>

            <dl className="pmc-backup-facts">
                <div>
                    <dt>{t('backups.archive_size')}</dt>
                    <dd>{formatBackupBytes(backup.archive_bytes, locale)}</dd>
                </div>
                <div>
                    <dt>{t('backups.database')}</dt>
                    <dd>
                        {backup.table_count.toLocaleString(locale)}{' '}
                        {t('backups.tables')}
                    </dd>
                </div>
                <div>
                    <dt>{t('backups.records')}</dt>
                    <dd>{backup.database_row_count.toLocaleString(locale)}</dd>
                </div>
                <div>
                    <dt>{t('backups.documents')}</dt>
                    <dd>{backup.document_count.toLocaleString(locale)}</dd>
                </div>
            </dl>

            <div className="pmc-backup-meta">
                <p>
                    <span>{t('backups.trigger')}</span>
                    <strong>{backup.trigger_label}</strong>
                </p>
                <p>
                    <span>{t('backups.initiated_by')}</span>
                    <strong>
                        {backup.initiated_by ?? t('backups.system_process')}
                    </strong>
                </p>
                <p>
                    <span>{t('backups.finished_at')}</span>
                    <strong>
                        {finishedAt
                            ? dateTime(finishedAt, locale)
                            : t('backups.pending')}
                    </strong>
                </p>
            </div>

            {backup.archive_sha256 ? (
                <details className="pmc-backup-checksum">
                    <summary>{t('backups.checksum')}</summary>
                    <code dir="ltr">{backup.archive_sha256}</code>
                </details>
            ) : null}

            {backup.failure_summary ? (
                <p className="pmc-backup-failure" role="alert">
                    <i
                        className="bi bi-exclamation-triangle"
                        aria-hidden="true"
                    />
                    {backup.failure_summary}
                </p>
            ) : null}

            {backup.status === 'completed' && !backup.archive_available ? (
                <p className="pmc-backup-failure" role="alert">
                    <i
                        className="bi bi-exclamation-triangle"
                        aria-hidden="true"
                    />
                    {t('backups.archive_unavailable')}
                </p>
            ) : null}

            <footer>
                {backup.can_download ? (
                    <a
                        href={backup.download_url}
                        className="btn btn-dark"
                        download
                    >
                        <i className="bi bi-download" aria-hidden="true" />
                        {t('backups.download')}
                    </a>
                ) : null}
                {backup.can_prune ? (
                    <button
                        type="button"
                        className="btn btn-outline-danger"
                        disabled={busy}
                        onClick={() => onPrune(backup)}
                    >
                        <i className="bi bi-trash3" aria-hidden="true" />
                        {t('backups.prune')}
                    </button>
                ) : null}
            </footer>
        </article>
    );
}
