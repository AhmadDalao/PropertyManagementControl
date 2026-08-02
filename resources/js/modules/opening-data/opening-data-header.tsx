import { WorkspaceHeader } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

export function OpeningDataHeader() {
    const { t } = useTranslator();

    return (
        <WorkspaceHeader
            eyebrow={t('opening_data.eyebrow')}
            title={t('opening_data.title')}
            description={t('opening_data.description')}
            actions={[
                {
                    label: t('opening_data.download_template'),
                    href: '/opening-data/template',
                    icon: 'bi-file-earmark-excel',
                },
                {
                    label: t('nav.documentation'),
                    href: '/documentation/opening-data-onboarding',
                    icon: 'bi-journal-text',
                },
            ]}
        />
    );
}
