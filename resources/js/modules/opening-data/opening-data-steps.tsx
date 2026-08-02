import { useTranslator } from '@/lib/i18n';

const steps = [
    ['bi-file-earmark-arrow-down', 'opening_data.step_template'],
    ['bi-shield-check', 'opening_data.step_upload'],
    ['bi-database-check', 'opening_data.step_commit'],
] as const;

export function OpeningDataSteps() {
    const { t } = useTranslator();

    return (
        <ol className="pmc-opening-steps">
            {steps.map(([icon, key], index) => (
                <li key={key}>
                    <span>{index + 1}</span>
                    <i className={`bi ${icon}`} aria-hidden="true" />
                    <strong>{t(key)}</strong>
                </li>
            ))}
        </ol>
    );
}
