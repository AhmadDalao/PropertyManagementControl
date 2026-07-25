import { Link } from '@inertiajs/react';

import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import type { OperationsDashboardProps } from '../types';

type ReadinessStatus = NonNullable<OperationsDashboardProps['readinessStatus']>;

export function LaunchReadinessPanel({ status }: { status: ReadinessStatus }) {
    const { locale, t } = useTranslator();

    return (
        <WorkspacePanel
            className="pmc-dashboard-launch-readiness"
            eyebrow={t('readiness.eyebrow')}
            title={t('dashboard.launch_control_title')}
            description={t('dashboard.launch_control_description')}
            action={{
                label: t('readiness.open_readiness'),
                href: '/system/readiness',
            }}
        >
            {status.operational_portfolios === 0 ? (
                <div className="pmc-dashboard-live-launch" role="note">
                    <i className="bi bi-buildings" aria-hidden="true" />
                    <div>
                        <strong>{t('readiness.live_portfolio_title')}</strong>
                        <p>{t('readiness.live_portfolio_description')}</p>
                    </div>
                    <Link href="/portfolios/create">
                        {t('readiness.create_live_portfolio')}
                        <i
                            className="bi bi-arrow-up-right"
                            aria-hidden="true"
                        />
                    </Link>
                </div>
            ) : null}
            <div className="pmc-dashboard-status-grid">
                <Link href="/system/readiness">
                    <span>{t('dashboard.platform_gate_status')}</span>
                    <strong>{t(`readiness.status_${status.status}`)}</strong>
                </Link>
                <Link href="/system/readiness">
                    <span>{t('dashboard.automatic_blockers')}</span>
                    <strong>
                        {localizedNumber(status.automatic_blocked, locale)}
                    </strong>
                </Link>
                <Link href="/system/readiness">
                    <span>{t('dashboard.automatic_attention')}</span>
                    <strong>
                        {localizedNumber(status.automatic_attention, locale)}
                    </strong>
                </Link>
                <Link href="/system/readiness">
                    <span>{t('dashboard.evidence_remaining')}</span>
                    <strong>
                        {localizedNumber(status.evidence_remaining, locale)}
                    </strong>
                </Link>
            </div>
            {status.showcase_portfolios > 0 ? (
                <div className="pmc-dashboard-data-context" role="note">
                    <i className="bi bi-database" aria-hidden="true" />
                    <div>
                        <strong>{t('dashboard.showcase_totals_title')}</strong>
                        <p>
                            {t(
                                'dashboard.showcase_totals_description',
                                undefined,
                                {
                                    portfolios: localizedNumber(
                                        status.showcase_portfolios,
                                        locale,
                                    ),
                                    assets: localizedNumber(
                                        status.showcase_assets,
                                        locale,
                                    ),
                                    users: localizedNumber(
                                        status.showcase_users,
                                        locale,
                                    ),
                                },
                            )}
                        </p>
                        <span>
                            {t(
                                'dashboard.operational_portfolio_count',
                                undefined,
                                {
                                    count: localizedNumber(
                                        status.operational_portfolios,
                                        locale,
                                    ),
                                },
                            )}
                        </span>
                    </div>
                    <Link href="/system/showcase-data">
                        {t('dashboard.review_showcase_data')}
                        <i
                            className="bi bi-arrow-up-right"
                            aria-hidden="true"
                        />
                    </Link>
                </div>
            ) : null}
        </WorkspacePanel>
    );
}
