import { Link } from '@inertiajs/react';

import { WorkspacePanel } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import type { DashboardSetupTarget } from '../types';

export function PortfolioSetupPanel({
    target,
}: {
    target: DashboardSetupTarget | null;
}) {
    const { locale, t } = useTranslator();

    if (!target || target.completed >= target.total || !target.next) {
        return null;
    }

    const progress = Math.round((target.completed / target.total) * 100);

    return (
        <WorkspacePanel
            className="pmc-dashboard-portfolio-setup"
            eyebrow={t('dashboard.portfolio_setup_eyebrow')}
            title={t('dashboard.portfolio_setup_title', undefined, {
                portfolio: target.name,
            })}
            description={t('dashboard.portfolio_setup_description')}
            action={{
                label: t('dashboard.open_portfolio_setup'),
                href: target.href,
            }}
        >
            <div className="pmc-dashboard-setup-body">
                <div className="pmc-dashboard-setup-progress">
                    <div>
                        <span>{target.code}</span>
                        <strong>
                            {t(
                                'dashboard.portfolio_setup_progress',
                                undefined,
                                {
                                    completed: localizedNumber(
                                        target.completed,
                                        locale,
                                    ),
                                    total: localizedNumber(
                                        target.total,
                                        locale,
                                    ),
                                },
                            )}
                        </strong>
                    </div>
                    <div
                        role="progressbar"
                        aria-label={t('dashboard.portfolio_setup_eyebrow')}
                        aria-valuemin={0}
                        aria-valuemax={target.total}
                        aria-valuenow={target.completed}
                    >
                        <span style={{ width: `${progress}%` }} />
                    </div>
                </div>

                <div className="pmc-dashboard-setup-next">
                    <i
                        className={`bi ${target.next.icon}`}
                        aria-hidden="true"
                    />
                    <div>
                        <span>{t('actions.next_step')}</span>
                        <strong>{target.next.label}</strong>
                        <p>{target.next.description}</p>
                    </div>
                    <Link href={target.next.href}>
                        {target.next.action_label}
                        <i
                            className="bi bi-arrow-up-right"
                            aria-hidden="true"
                        />
                    </Link>
                </div>
            </div>
        </WorkspacePanel>
    );
}
