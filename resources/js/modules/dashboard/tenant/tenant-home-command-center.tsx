import type { TenantDashboardProps } from '../types';
import { TenantContractSnapshot } from './tenant-contract-snapshot';
import { TenantEmptyLease } from './tenant-empty-lease';
import { TenantFinancialSnapshot } from './tenant-financial-snapshot';
import { TenantHomeActions } from './tenant-home-actions';
import { TenantHomeHeader } from './tenant-home-header';
import { TenantRecentActivity } from './tenant-recent-activity';

export function TenantHomeCommandCenter({
    props,
}: {
    props: TenantDashboardProps;
}) {
    const lease = props.tenantPortal.lease;

    return (
        <div className="pmc-tenant-command-center">
            <TenantHomeHeader props={props} />
            {lease ? (
                <>
                    <div className="pmc-tenant-command-snapshots">
                        <TenantContractSnapshot lease={lease} />
                        <TenantFinancialSnapshot props={props} />
                    </div>
                    <TenantHomeActions props={props} />
                    <TenantRecentActivity props={props} />
                </>
            ) : (
                <TenantEmptyLease actions={props.nextActions} />
            )}
        </div>
    );
}
