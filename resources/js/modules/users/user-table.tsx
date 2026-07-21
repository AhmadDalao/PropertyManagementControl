import { Link } from '@inertiajs/react';

import { ArchiveAction } from '@/components/archive-action';
import { DataTable, exportUrl } from '@/components/data-table';
import { RecordActions, StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { humanDate } from '@/lib/utils';

import type { UserIndexPageProps, UserRecord } from './types';
import { useUserFilterFields } from './user-filters';

type UserTableProps = Pick<
    UserIndexPageProps,
    | 'users'
    | 'filters'
    | 'counts'
    | 'portfolioOptions'
    | 'roleOptions'
    | 'statusOptions'
    | 'auth'
    | 'app'
>;

export function UserTable(props: UserTableProps) {
    const { locale, t } = useTranslator();
    const currentUserId = props.auth.user?.id;
    const filterFields = useUserFilterFields({
        statuses: props.statusOptions,
        roles: props.roleOptions,
        portfolios: props.portfolioOptions,
        includePortfolio:
            props.auth.user?.roles.includes('superadmin') ?? false,
    });
    const userHref = (user: UserRecord) =>
        user.id === currentUserId ? '/profile' : `/users/${user.id}`;
    const userCell = (user: UserRecord) => (
        <div className="pmc-primary-cell">
            <strong>{user.name}</strong>
            <span>{user.email}</span>
            {user.phone ? <small>{user.phone}</small> : null}
        </div>
    );
    const roleCell = (user: UserRecord) => (
        <div className="pmc-badge-stack">
            {(user.roles ?? []).map((role) => (
                <StatusBadge key={role.name} value={role.name} />
            ))}
        </div>
    );
    const portfolioCell = (user: UserRecord) => {
        const portfolioName =
            locale === 'ar'
                ? user.portfolio?.name_ar || user.portfolio?.name_en
                : user.portfolio?.name_en || user.portfolio?.name_ar;

        return (
            <div className="pmc-stacked-cell">
                <strong>{portfolioName ?? t('users.global_account')}</strong>
                <span>
                    {user.portfolio?.code ?? t('users.no_portfolio_code')}
                </span>
            </div>
        );
    };
    const accessCell = (user: UserRecord) => (
        <div className="pmc-stacked-cell">
            <StatusBadge value={user.status} />
            <span>
                {user.force_password_reset
                    ? t('users.password_reset_required')
                    : t('users.password_confirmed')}
            </span>
            {user.last_login_at ? (
                <small>
                    {t('users.last_seen', undefined, {
                        date: humanDate(user.last_login_at, props.app.locale),
                    })}
                </small>
            ) : null}
        </div>
    );
    const actions = (user: UserRecord) =>
        user.id === currentUserId ? (
            <div className="pmc-record-actions">
                <Link href="/profile" className="pmc-record-open">
                    {t('users.my_profile')}
                    <i className="bi bi-arrow-up-right" />
                </Link>
            </div>
        ) : (
            <RecordActions
                showHref={`/users/${user.id}`}
                editHref={`/users/${user.id}/edit`}
            >
                {user.status !== 'suspended' ? (
                    <ArchiveAction
                        href={`/users/${user.id}`}
                        label={t('users.suspend_user')}
                        confirmMessage={t('users.archive_confirm', undefined, {
                            name: user.name,
                        })}
                    />
                ) : null}
            </RecordActions>
        );

    return (
        <DataTable
            title={t('users.directory_title')}
            description={t('users.directory_description')}
            data={props.users}
            filters={props.filters}
            counts={props.counts}
            basePath="/users"
            rowHref={userHref}
            exportHref={exportUrl('/exports/users', props.filters)}
            filterFields={filterFields}
            emptyText={t('users.empty')}
            createHref="/users/create"
            createLabel={t('users.create_user')}
            mobileCard={{
                title: userCell,
                subtitle: roleCell,
                status: (user) => <StatusBadge value={user.status} />,
                meta: [
                    { label: t('users.portfolio'), value: portfolioCell },
                    { label: t('users.portal_access'), value: accessCell },
                    {
                        label: t('users.open_workload'),
                        value: (user) =>
                            t('users.assignment_count', undefined, {
                                count: user.open_assignments_count ?? 0,
                            }),
                    },
                ],
                actions,
            }}
            columns={[
                {
                    key: 'user',
                    label: t('users.user'),
                    render: userCell,
                },
                {
                    key: 'role',
                    label: t('users.role'),
                    render: roleCell,
                },
                {
                    key: 'portfolio',
                    label: t('users.portfolio'),
                    render: portfolioCell,
                },
                {
                    key: 'account',
                    label: t('users.portal_access'),
                    render: accessCell,
                },
                {
                    key: 'actions',
                    label: t('users.actions'),
                    className: 'text-end',
                    render: actions,
                },
            ]}
        />
    );
}
