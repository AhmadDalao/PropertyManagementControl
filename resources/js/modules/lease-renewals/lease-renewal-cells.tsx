import { Link } from '@inertiajs/react';

import { RecordActions } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import { localizedRenewalRecord } from './lease-renewal-labels';
import type { LeaseRenewalRecord } from './types';

export function useLeaseRenewalCells(locale: string) {
    const { t } = useTranslator();
    const tenant = (lease: LeaseRenewalRecord) => (
        <div className="pmc-stacked-cell">
            <strong>
                {lease.tenant?.name ?? t('lease_renewals.no_tenant')}
            </strong>
            <span>{lease.tenant?.email ?? t('lease_renewals.no_email')}</span>
        </div>
    );
    const propertyAsset = (lease: LeaseRenewalRecord) => (
        <div className="pmc-stacked-cell">
            <strong>
                {localizedRenewalRecord(lease.property, locale) ??
                    t('lease_renewals.no_property')}
            </strong>
            <span>
                {localizedRenewalRecord(lease.asset, locale) ??
                    t('lease_renewals.no_asset')}
                {lease.asset?.code ? ` · ${lease.asset.code}` : ''}
            </span>
        </div>
    );
    const renewalAction = (lease: LeaseRenewalRecord) =>
        lease.renewal ? (
            <Link
                href={`/leases/${lease.renewal.id}`}
                className="btn btn-outline-secondary btn-sm"
            >
                <i className="bi bi-arrow-up-right" />
                <span>{t('lease_renewals.open_renewal')}</span>
            </Link>
        ) : (
            <Link
                href={`/leases/${lease.id}/renew`}
                className="btn btn-primary btn-sm"
            >
                <i className="bi bi-file-earmark-plus" />
                <span>{t('lease_renewals.prepare_renewal')}</span>
            </Link>
        );
    const actions = (lease: LeaseRenewalRecord) => (
        <RecordActions showHref={`/leases/${lease.id}`}>
            {renewalAction(lease)}
        </RecordActions>
    );

    return {
        tenant,
        propertyAsset,
        renewalAction,
        actions,
    };
}
