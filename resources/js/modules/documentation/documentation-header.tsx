import { WorkspaceHeader } from '@/components/operations/workspace';
import { useTranslator } from '@/lib/i18n';

export function DocumentationHeader() {
    const { t } = useTranslator();

    return (
        <WorkspaceHeader
            eyebrow={t('docs.system_guide')}
            title={t('docs.title')}
            description={t('docs.description')}
        />
    );
}
