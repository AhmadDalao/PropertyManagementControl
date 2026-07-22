import { ArchiveAction } from '@/components/archive-action';
import { RecordActions } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import { TenantProfileCompleteness } from './tenant-profile-completeness';
import type { TenantRecord } from './types';

export function useTenantTableCells() {
    const { t } = useTranslator();

    const tenantCell = (tenant: TenantRecord) => (
        <div className="pmc-primary-cell">
            <strong>
                {tenant.user?.name ??
                    tenant.company_name ??
                    t('tenants.tenant_number', undefined, { id: tenant.id })}
            </strong>
            <span>{tenant.user?.email ?? t('tenants.no_email')}</span>
            {tenant.user?.phone ? <small>{tenant.user.phone}</small> : null}
        </div>
    );
    const profileCell = (tenant: TenantRecord) => (
        <div className="pmc-stacked-cell">
            <strong>{t(`tenants.${tenant.profile_type}`)}</strong>
            <span>
                {tenant.company_name ??
                    tenant.national_id ??
                    t('tenants.identity_not_recorded')}
            </span>
            <TenantProfileCompleteness tenant={tenant} />
        </div>
    );
    const rentalCell = (tenant: TenantRecord) => (
        <div className="pmc-stacked-cell">
            <strong>
                {t('tenants.active_leases', undefined, {
                    count: tenant.active_leases_count ?? 0,
                })}
            </strong>
            <span>
                {t('tenants.activity', undefined, {
                    total: tenant.leases_count ?? 0,
                    service: tenant.open_requests_count ?? 0,
                })}
            </span>
        </div>
    );
    const actions = (tenant: TenantRecord) => (
        <RecordActions
            showHref={`/tenants/${tenant.id}`}
            editHref={`/tenants/${tenant.id}/edit`}
        >
            {tenant.status !== 'blocked' ? (
                <ArchiveAction
                    href={`/tenants/${tenant.id}`}
                    confirmMessage={t('tenants.archive_confirm', undefined, {
                        name: tenant.user?.name ?? t('tenants.this_tenant'),
                    })}
                />
            ) : null}
        </RecordActions>
    );

    return { tenantCell, profileCell, rentalCell, actions };
}
