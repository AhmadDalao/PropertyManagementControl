import { Link } from '@inertiajs/react';

import { RecordActions } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';

import { localizedMoveOutRecord } from './lease-move-out-labels';
import type { LeaseMoveOutRecord } from './types';

export function useLeaseMoveOutCells(locale: string) {
    const { t } = useTranslator();
    const tenant = (moveOut: LeaseMoveOutRecord) => (
        <div className="pmc-stacked-cell">
            <strong>
                {moveOut.tenant?.name ?? t('lease_move_outs.no_tenant')}
            </strong>
            <span>
                {moveOut.tenant?.email ?? t('lease_move_outs.no_email')}
            </span>
        </div>
    );
    const propertyAsset = (moveOut: LeaseMoveOutRecord) => (
        <div className="pmc-stacked-cell">
            <strong>
                {localizedMoveOutRecord(moveOut.property, locale) ??
                    t('lease_move_outs.no_property')}
            </strong>
            <span>
                {localizedMoveOutRecord(moveOut.asset, locale) ??
                    t('lease_move_outs.no_asset')}
                {moveOut.asset?.code ? ` · ${moveOut.asset.code}` : ''}
            </span>
        </div>
    );
    const primaryAction = (moveOut: LeaseMoveOutRecord) =>
        moveOut.status === 'planned' ? (
            <Link
                href={`/leases/${moveOut.lease_id}/move-out`}
                className="btn btn-primary btn-sm"
            >
                <i className="bi bi-pencil-square" />
                <span>{t('lease_move_outs.continue_handover')}</span>
            </Link>
        ) : (
            <Link
                href={`/leases/${moveOut.lease_id}`}
                className="btn btn-outline-secondary btn-sm"
            >
                <i className="bi bi-arrow-up-right" />
                <span>{t('lease_move_outs.open_lease')}</span>
            </Link>
        );
    const actions = (moveOut: LeaseMoveOutRecord) => (
        <RecordActions showHref={`/leases/${moveOut.lease_id}`}>
            {primaryAction(moveOut)}
        </RecordActions>
    );

    return {
        tenant,
        propertyAsset,
        primaryAction,
        actions,
    };
}
