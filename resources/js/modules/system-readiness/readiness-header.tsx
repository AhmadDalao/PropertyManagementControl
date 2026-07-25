import { WorkspaceHeader } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

export function ReadinessHeader() {
    const { t } = useTranslator();

    return (
        <WorkspaceHeader
            eyebrow={t('readiness.eyebrow')}
            title={t('readiness.title')}
            description={t('readiness.description')}
            actions={[
                {
                    label: t('readiness.run_checks'),
                    href: '/system/readiness',
                    icon: 'bi-arrow-clockwise',
                },
            ]}
        />
    );
}
