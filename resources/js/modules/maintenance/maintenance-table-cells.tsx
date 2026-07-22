import { ArchiveAction } from '@/components/archive-action';
import { RecordActions, StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { currency, humanDate } from '@/lib/utils';

import type { MaintenanceRecord, MaintenanceTableProps } from './types';

type MaintenanceCellProps = { request: MaintenanceRecord };

export function MaintenanceIdentity({ request }: MaintenanceCellProps) {
    const { t } = useTranslator();

    return (
        <div className="pmc-primary-cell">
            <strong>
                #{request.id} {request.title}
            </strong>
            <span>{t(`status.${request.category}`)}</span>
            {request.is_overdue ? (
                <StatusBadge value="overdue" tone="danger" />
            ) : null}
        </div>
    );
}

export function MaintenanceAssetTenant({ request }: MaintenanceCellProps) {
    const { locale, t } = useTranslator();
    const asset =
        locale === 'ar'
            ? request.asset?.title_ar || request.asset?.title_en
            : request.asset?.title_en || request.asset?.title_ar;

    return (
        <div className="pmc-stacked-cell">
            <strong>{asset ?? t('maintenance.no_asset')}</strong>
            <span>
                {request.tenant_profile?.user?.name ??
                    t('maintenance.no_tenant')}
            </span>
        </div>
    );
}

export function MaintenanceAssignment({
    request,
    mode,
    app,
}: MaintenanceCellProps & Pick<MaintenanceTableProps, 'mode' | 'app'>) {
    const { t } = useTranslator();

    return (
        <div className="pmc-stacked-cell">
            <strong>
                {request.assigned_to?.name ?? t('maintenance.unassigned_label')}
            </strong>
            <span>
                {mode === 'manager'
                    ? `${currency(request.expense_total, app.locale)} ${t(
                          'maintenance.cost_entries',
                          undefined,
                          { count: request.expense_count },
                      )}`
                    : t('maintenance.owner_assignment')}
            </span>
        </div>
    );
}

export function MaintenancePriority({ request }: MaintenanceCellProps) {
    return (
        <StatusBadge
            value={request.priority}
            tone={
                request.priority === 'urgent'
                    ? 'danger'
                    : request.priority === 'high'
                      ? 'warning'
                      : 'neutral'
            }
        />
    );
}

export function MaintenanceStatusDue({ request }: MaintenanceCellProps) {
    const { locale, t } = useTranslator();

    return (
        <div className="pmc-stacked-cell">
            <StatusBadge value={request.status} />
            <span>
                {t('maintenance.due_label')} {humanDate(request.due_at, locale)}
            </span>
        </div>
    );
}

export function MaintenanceActions({
    request,
    mode,
}: MaintenanceCellProps & Pick<MaintenanceTableProps, 'mode'>) {
    const { t } = useTranslator();

    return (
        <RecordActions
            showHref={`/maintenance-requests/${request.id}`}
            editHref={
                mode === 'manager'
                    ? `/maintenance-requests/${request.id}/edit`
                    : undefined
            }
        >
            {!['cancelled', 'resolved'].includes(request.status) ? (
                <ArchiveAction
                    href={`/maintenance-requests/${request.id}`}
                    label={t('maintenance.cancel')}
                    confirmMessage={t('maintenance.cancel_confirm', undefined, {
                        id: request.id,
                    })}
                />
            ) : null}
        </RecordActions>
    );
}
