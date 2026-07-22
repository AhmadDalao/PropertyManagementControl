import { WorkspaceHeader } from '@/components/operations/workspace';
import { useTranslator } from '@/lib/i18n';

import type { Guide } from './types';

export function DocumentationGuideHeader({ guide }: { guide: Guide }) {
    const { t } = useTranslator();

    return (
        <WorkspaceHeader
            eyebrow={t('docs.guide_suffix', undefined, {
                audience: guide.audience,
            })}
            title={guide.title}
            description={guide.summary}
            actions={[
                {
                    label: t('docs.open_workspace'),
                    href: guide.route,
                    icon: 'bi-arrow-up-right',
                    tone: 'primary',
                },
                {
                    label: t('docs.back_to_guides'),
                    href: '/documentation',
                    icon: 'bi-arrow-left',
                    tone: 'quiet',
                },
            ]}
        />
    );
}
