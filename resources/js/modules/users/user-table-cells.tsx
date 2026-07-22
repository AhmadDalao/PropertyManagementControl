import { Link } from '@inertiajs/react';

import { ArchiveAction } from '@/components/archive-action';
import { RecordActions, StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { humanDate } from '@/lib/utils';

import type { UserRecord, UserTableProps } from './types';

export function useUserTableCells(props: UserTableProps) {
    const { locale, t } = useTranslator();
    const currentUserId = props.auth.user?.id;
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
            {user.roles.map((role) => (
                <StatusBadge key={role} value={role} />
            ))}
        </div>
    );
    const portfolioCell = (user: UserRecord) => {
        const name =
            locale === 'ar'
                ? user.portfolio?.name_ar || user.portfolio?.name_en
                : user.portfolio?.name_en || user.portfolio?.name_ar;

        return (
            <div className="pmc-stacked-cell">
                <strong>{name ?? t('users.global_account')}</strong>
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

    return { userHref, userCell, roleCell, portfolioCell, accessCell, actions };
}
