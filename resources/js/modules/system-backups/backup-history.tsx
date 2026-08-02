import { router } from '@inertiajs/react';

import { TablePagination } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { BackupCard } from './backup-card';
import type { SystemBackupPageProps, SystemBackupRecord } from './types';

export function BackupHistory({
    props,
    busy,
    onPrune,
}: {
    props: SystemBackupPageProps;
    busy: boolean;
    onPrune: (backup: SystemBackupRecord) => void;
}) {
    const { t } = useTranslator();

    const changeStatus = (status: string) => {
        router.get('/system/backups', status === 'all' ? {} : { status }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    return (
        <section className="pmc-backup-history">
            <header>
                <div>
                    <span>{t('backups.history_eyebrow')}</span>
                    <h2>{t('backups.history_title')}</h2>
                    <p>{t('backups.history_description')}</p>
                </div>
                <label>
                    <span>{t('backups.filter_status')}</span>
                    <select
                        value={props.filters.status}
                        onChange={(event) => changeStatus(event.target.value)}
                    >
                        {props.statusOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </label>
            </header>

            {props.backups.data.length > 0 ? (
                <>
                    <div className="pmc-backup-grid">
                        {props.backups.data.map((backup) => (
                            <BackupCard
                                key={backup.id}
                                backup={backup}
                                busy={busy}
                                onPrune={onPrune}
                            />
                        ))}
                    </div>
                    <TablePagination data={props.backups} />
                </>
            ) : (
                <div className="pmc-backup-empty">
                    <i className="bi bi-archive" aria-hidden="true" />
                    <h3>{t('backups.empty_title')}</h3>
                    <p>{t('backups.empty_description')}</p>
                </div>
            )}
        </section>
    );
}
