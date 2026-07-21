import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import { RecordActions, StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import { useTenantFilterFields } from './tenant-filters';
import type { TenantIndexPageProps, TenantRecord } from './types';

type TenantTableProps = Pick<
    TenantIndexPageProps,
    | 'tenants'
    | 'filters'
    | 'counts'
    | 'portfolioOptions'
    | 'profileTypeOptions'
    | 'statusOptions'
    | 'auth'
>;

export function TenantTable(props: TenantTableProps) {
    const { t } = useTranslator();
    const filterFields = useTenantFilterFields({
        statuses: props.statusOptions,
        profileTypes: props.profileTypeOptions,
        portfolios: props.portfolioOptions,
        includePortfolio:
            props.auth.user?.roles.includes('superadmin') ?? false,
    });
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
            <ProfileCompleteness tenant={tenant} />
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

    return (
        <DataTable
            title={t('tenants.directory_title')}
            description={t('tenants.directory_description')}
            data={props.tenants}
            filters={props.filters}
            counts={props.counts}
            basePath="/tenants"
            rowHref={(tenant) => `/tenants/${tenant.id}`}
            exportHref={exportUrl('/exports/tenants', props.filters)}
            filterFields={filterFields}
            emptyText={t('tenants.empty')}
            mobileCard={{
                title: tenantCell,
                subtitle: profileCell,
                status: (tenant) => <StatusBadge value={tenant.status} />,
                meta: [
                    {
                        label: t('tenants.rental_activity'),
                        value: rentalCell,
                    },
                    {
                        label: t('tenants.portal_account'),
                        value: (tenant) => (
                            <StatusBadge
                                value={tenant.user?.status ?? 'inactive'}
                            />
                        ),
                    },
                ],
                actions,
            }}
            columns={[
                {
                    key: 'tenant',
                    label: t('tenants.tenant'),
                    render: tenantCell,
                },
                {
                    key: 'profile',
                    label: t('tenants.profile'),
                    render: profileCell,
                },
                {
                    key: 'leases',
                    label: t('tenants.rental_activity'),
                    render: rentalCell,
                },
                {
                    key: 'status',
                    label: t('tenants.status'),
                    render: (tenant) => <StatusBadge value={tenant.status} />,
                },
                {
                    key: 'actions',
                    label: t('tenants.actions'),
                    className: 'text-end',
                    render: actions,
                },
            ]}
        />
    );
}

function ProfileCompleteness({ tenant }: { tenant: TenantRecord }) {
    const { t } = useTranslator();
    const missing = [
        tenant.emergency_contact_name && tenant.emergency_contact_phone
            ? null
            : t('tenants.emergency_contact'),
        tenant.address ? null : t('tenants.address'),
        tenant.profile_type === 'company' && !tenant.company_name
            ? t('tenants.company_name')
            : null,
    ].filter((item): item is string => Boolean(item));

    return missing.length === 0 ? (
        <StatusBadge value="complete" tone="success" />
    ) : (
        <StatusBadge
            value="incomplete"
            label={t('tenants.missing_fields', undefined, {
                fields: missing.join(', '),
            })}
            tone="warning"
        />
    );
}
