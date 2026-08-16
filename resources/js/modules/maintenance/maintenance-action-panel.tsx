import type { ResourceAction } from '@/components/resource-cycle';
import { ActionLink } from '@/components/resource-cycle/action-link';
import { useTranslator } from '@/lib/i18n';

export function MaintenanceActionPanel({
    actions,
}: {
    actions: ResourceAction[];
}) {
    const { t } = useTranslator();

    return (
        <section className="pmc-maintenance-panel pmc-maintenance-actions-panel">
            <header>
                <h2>{t('maintenance.available_actions')}</h2>
            </header>
            <div>
                {actions.length > 0 ? (
                    actions.map((action) => (
                        <ActionLink
                            action={action}
                            key={`${action.href}-${action.label}`}
                        />
                    ))
                ) : (
                    <p>{t('resource.no_available_actions')}</p>
                )}
            </div>
        </section>
    );
}
