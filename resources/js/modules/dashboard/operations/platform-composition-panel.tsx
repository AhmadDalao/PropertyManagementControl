import { Link } from '@inertiajs/react';

import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import type { OperationsDashboardProps } from '../types';

type Composition = NonNullable<OperationsDashboardProps['platformComposition']>;

export function PlatformCompositionPanel({
    composition,
}: {
    composition: Composition;
}) {
    const { locale, t } = useTranslator();
    const count = (value: number) => localizedNumber(value, locale);

    return (
        <WorkspacePanel
            className="pmc-platform-composition"
            eyebrow={t('dashboard.company_composition_eyebrow')}
            title={t('dashboard.company_composition_title')}
            description={t('dashboard.company_composition_description')}
            action={{
                label: t('dashboard.manage_company_accounts'),
                href: '/users',
            }}
        >
            <div className="pmc-platform-composition-grid">
                <article>
                    <header>
                        <i
                            className="bi bi-building-check"
                            aria-hidden="true"
                        />
                        <div>
                            <span>{t('dashboard.client_portfolios')}</span>
                            <strong>
                                {count(composition.portfolios.live_active)}
                            </strong>
                        </div>
                    </header>
                    <nav aria-label={t('dashboard.client_portfolios')}>
                        <Link href="/portfolios?status=inactive">
                            <span>{t('dashboard.inactive_live')}</span>
                            <strong>
                                {count(composition.portfolios.live_inactive)}
                            </strong>
                        </Link>
                        <Link href="/portfolios?status=archived">
                            <span>{t('dashboard.archived_live')}</span>
                            <strong>
                                {count(composition.portfolios.live_archived)}
                            </strong>
                        </Link>
                        <Link href="/system/showcase-data">
                            <span>{t('dashboard.showcase_portfolios')}</span>
                            <strong>
                                {count(composition.portfolios.showcase)}
                            </strong>
                        </Link>
                    </nav>
                </article>

                <article>
                    <header>
                        <i className="bi bi-buildings" aria-hidden="true" />
                        <div>
                            <span>{t('dashboard.managed_properties')}</span>
                            <strong>
                                {count(composition.properties.live)}
                            </strong>
                        </div>
                    </header>
                    <nav aria-label={t('dashboard.managed_properties')}>
                        <Link href="/portfolio-control">
                            <span>{t('dashboard.live_properties')}</span>
                            <strong>
                                {count(composition.properties.live)}
                            </strong>
                        </Link>
                        <Link href="/system/showcase-data">
                            <span>{t('dashboard.showcase_properties')}</span>
                            <strong>
                                {count(composition.properties.showcase)}
                            </strong>
                        </Link>
                        <Link href="/assets">
                            <span>{t('dashboard.all_asset_records')}</span>
                            <strong>
                                {count(composition.properties.asset_records)}
                            </strong>
                        </Link>
                    </nav>
                </article>

                <article>
                    <header>
                        <i className="bi bi-people" aria-hidden="true" />
                        <div>
                            <span>{t('dashboard.portal_accounts')}</span>
                            <strong>
                                {count(composition.accounts.live_active)}
                            </strong>
                        </div>
                    </header>
                    <nav aria-label={t('dashboard.portal_accounts')}>
                        <Link href="/users?status=inactive">
                            <span>{t('dashboard.inactive_accounts')}</span>
                            <strong>
                                {count(composition.accounts.live_inactive)}
                            </strong>
                        </Link>
                        <Link href="/system/showcase-data">
                            <span>{t('dashboard.showcase_accounts')}</span>
                            <strong>
                                {count(composition.accounts.showcase)}
                            </strong>
                        </Link>
                    </nav>
                    <div className="pmc-platform-role-counts">
                        <Link href="/users?role=superadmin">
                            {t('dashboard.superadmins')}{' '}
                            {count(composition.accounts.roles.superadmins)}
                        </Link>
                        <Link href="/users?role=owner">
                            {t('dashboard.owners')}{' '}
                            {count(composition.accounts.roles.owners)}
                        </Link>
                        <Link href="/users?role=property_manager">
                            {t('dashboard.managers')}{' '}
                            {count(composition.accounts.roles.managers)}
                        </Link>
                        <Link href="/users?role=tenant">
                            {t('dashboard.tenants')}{' '}
                            {count(composition.accounts.roles.tenants)}
                        </Link>
                    </div>
                </article>
            </div>

            <div className="pmc-platform-composition-note" role="note">
                <i className="bi bi-database" aria-hidden="true" />
                <p>{t('dashboard.company_composition_note')}</p>
            </div>
        </WorkspacePanel>
    );
}
