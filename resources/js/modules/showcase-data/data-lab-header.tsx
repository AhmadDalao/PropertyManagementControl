import { WorkspaceHeader } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

export function DataLabHeader() {
    const { t } = useTranslator();

    return (
        <WorkspaceHeader
            eyebrow={t('showcase.system_control')}
            title={t('showcase.title')}
            description={t('showcase.description')}
            actions={[
                {
                    label: t('showcase.refresh'),
                    href: '/system/showcase-data',
                    icon: 'bi-arrow-clockwise',
                },
            ]}
        />
    );
}
