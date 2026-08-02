import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import '../../../css/styles/system-backups.css';
import { BackupHistory } from './backup-history';
import { BackupMetrics } from './backup-metrics';
import { PruneDialog } from './prune-dialog';
import type { SystemBackupPageProps, SystemBackupRecord } from './types';

export default function SystemBackupIndexPage() {
    const { props } = usePage<SystemBackupPageProps>();
    const { t } = useTranslator();
    const [busyAction, setBusyAction] = useState<'create' | 'prune' | null>(
        null,
    );
    const [pruneTarget, setPruneTarget] = useState<SystemBackupRecord | null>(
        null,
    );

    useEffect(() => {
        if (props.summary.active < 1) {
            return;
        }

        const interval = window.setInterval(() => {
            router.reload({
                only: ['backups', 'summary'],
            });
        }, 5000);

        return () => window.clearInterval(interval);
    }, [props.summary.active]);

    const createBackup = () => {
        setBusyAction('create');
        router.post(
            '/system/backups',
            {},
            {
                preserveScroll: true,
                onFinish: () => setBusyAction(null),
            },
        );
    };

    const pruneBackup = () => {
        if (!pruneTarget) {
            return;
        }

        setBusyAction('prune');
        router.delete(pruneTarget.prune_url, {
            preserveScroll: true,
            onSuccess: () => setPruneTarget(null),
            onFinish: () => setBusyAction(null),
        });
    };

    return (
        <AdminLayout>
            <Head title={t('backups.title')} />
            <WorkspaceHeader
                eyebrow={t('backups.eyebrow')}
                title={t('backups.title')}
                description={t('backups.description')}
                actions={[
                    {
                        label: t('backups.open_readiness'),
                        href: '/system/readiness',
                        icon: 'bi-shield-check',
                        tone: 'quiet',
                    },
                ]}
            />

            <section className="pmc-backup-command">
                <div>
                    <span>{t('backups.package_eyebrow')}</span>
                    <h2>{t('backups.package_title')}</h2>
                    <p>{t('backups.package_description')}</p>
                    <ul>
                        <li>
                            <i
                                className="bi bi-database-check"
                                aria-hidden="true"
                            />
                            {t('backups.includes_database')}
                        </li>
                        <li>
                            <i
                                className="bi bi-folder2-open"
                                aria-hidden="true"
                            />
                            {t('backups.includes_documents')}
                        </li>
                        <li>
                            <i
                                className="bi bi-shield-lock"
                                aria-hidden="true"
                            />
                            {t('backups.includes_manifest')}
                        </li>
                    </ul>
                </div>
                <div className="pmc-backup-command-action">
                    <button
                        type="button"
                        className="btn btn-warning"
                        disabled={
                            busyAction !== null || props.summary.active > 0
                        }
                        onClick={createBackup}
                    >
                        <i
                            className="bi bi-cloud-arrow-up"
                            aria-hidden="true"
                        />
                        {busyAction === 'create' || props.summary.active > 0
                            ? t('backups.creating')
                            : t('backups.create')}
                    </button>
                    <small>{t('backups.offsite_warning')}</small>
                </div>
            </section>

            <BackupMetrics summary={props.summary} />
            <BackupHistory
                props={props}
                busy={busyAction !== null}
                onPrune={setPruneTarget}
            />

            {pruneTarget ? (
                <PruneDialog
                    backup={pruneTarget}
                    busy={busyAction === 'prune'}
                    onCancel={() => setPruneTarget(null)}
                    onConfirm={pruneBackup}
                />
            ) : null}
        </AdminLayout>
    );
}
