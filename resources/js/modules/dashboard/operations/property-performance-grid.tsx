import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import type { OperationsDashboardProps } from '../types';
import { PropertyPerformanceCard } from './property-performance-card';

export function PropertyPerformanceGrid({
    props,
}: {
    props: OperationsDashboardProps;
}) {
    const { t } = useTranslator();
    const selectedProperty = props.propertyFocus.selected;
    const action = {
        label: selectedProperty
            ? t('dashboard.open_focused_property')
            : t('dashboard.open_portfolio_control'),
        href: selectedProperty
            ? `/property-explorer?property_id=${selectedProperty.id}`
            : '/portfolio-control',
    };

    return (
        <WorkspacePanel
            className="pmc-property-performance"
            eyebrow={t('dashboard.properties')}
            title={t('dashboard.property_performance')}
            description={t('dashboard.property_performance_description')}
            action={action}
        >
            {props.propertyPerformance.length === 0 ? (
                <div className="pmc-command-empty">
                    {t('dashboard.no_property_performance')}
                </div>
            ) : (
                <div className="pmc-property-performance-grid">
                    {props.propertyPerformance.slice(0, 2).map((property) => (
                        <PropertyPerformanceCard
                            key={property.id}
                            property={property}
                            appLocale={props.app.locale}
                        />
                    ))}
                </div>
            )}
        </WorkspacePanel>
    );
}
