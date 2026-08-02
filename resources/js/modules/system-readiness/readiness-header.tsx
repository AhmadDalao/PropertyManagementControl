import { WorkspaceHeader } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

export function ReadinessHeader({ portfolioId }: { portfolioId?: number }) {
    const { t } = useTranslator();
    const query = portfolioId ? `?portfolio_id=${portfolioId}` : '';

    return (
        <WorkspaceHeader
            eyebrow={t('readiness.eyebrow')}
            title={t('readiness.title')}
            description={t('readiness.description')}
            actions={[
                {
                    label: t('readiness.run_checks'),
                    href: `/system/readiness${query}`,
                    icon: 'bi-arrow-clockwise',
                },
                {
                    label: t('readiness.download_pdf'),
                    href: `/system/readiness/report.pdf${query}`,
                    icon: 'bi-file-earmark-pdf',
                    tone: 'quiet',
                    native: true,
                },
                {
                    label: t('readiness.download_word'),
                    href: `/system/readiness/report.docx${query}`,
                    icon: 'bi-file-earmark-word',
                    tone: 'quiet',
                    native: true,
                },
                {
                    label: t('readiness.download_excel'),
                    href: `/system/readiness/report.xlsx${query}`,
                    icon: 'bi-file-earmark-excel',
                    tone: 'primary',
                    native: true,
                },
            ]}
        />
    );
}
