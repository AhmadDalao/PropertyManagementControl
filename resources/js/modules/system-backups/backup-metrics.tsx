import { MetricGrid } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { dateTime } from '@/lib/utils';

import { formatBackupBytes } from './format';
import type { SystemBackupPageProps } from './types';

export function BackupMetrics({
    summary,
}: {
    summary: SystemBackupPageProps['summary'];
}) {
    const { locale, t } = useTranslator();

    return (
        <MetricGrid
            metrics={[
                {
                    label: t('backups.completed'),
                    value: summary.completed.toLocaleString(locale),
                    detail: t('backups.retention_help', undefined, {
                        count: summary.retention_count,
                    }),
                    icon: 'bi-database-check',
                    tone: 'ink',
                },
                {
                    label: t('backups.active'),
                    value: summary.active.toLocaleString(locale),
                    detail: t('backups.active_help'),
                    icon: 'bi-clock-history',
                    tone: summary.active > 0 ? 'amber' : 'teal',
                },
                {
                    label: t('backups.private_storage'),
                    value: formatBackupBytes(summary.stored_bytes, locale),
                    detail: t('backups.private_storage_help'),
                    icon: 'bi-shield-lock',
                    tone: 'teal',
                },
                {
                    label: t('backups.latest_backup'),
                    value: summary.latest_completed_at
                        ? dateTime(summary.latest_completed_at, locale)
                        : t('backups.never'),
                    detail: t('backups.latest_backup_help'),
                    icon: 'bi-archive',
                    tone: summary.latest_completed_at ? 'blue' : 'red',
                },
            ]}
        />
    );
}
