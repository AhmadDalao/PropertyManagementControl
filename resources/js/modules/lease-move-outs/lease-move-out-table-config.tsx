import type { MobileTableConfig, TableColumn } from '@/components/data-table';
import { StatusBadge } from '@/components/operations';
import { useTranslator } from '@/lib/i18n';
import type { UiTranslationKey } from '@/lib/i18n';
import { currency, humanDate, localizedNumber } from '@/lib/utils';

import { useLeaseMoveOutCells } from './lease-move-out-cells';
import {
    moveOutStateLabel,
    moveOutStateTone,
    requirementCount,
} from './lease-move-out-labels';
import type { LeaseMoveOutRecord } from './types';

export function useLeaseMoveOutTableConfig(locale: string): {
    columns: Array<TableColumn<LeaseMoveOutRecord>>;
    mobileCard: MobileTableConfig<LeaseMoveOutRecord>;
} {
    const { t } = useTranslator();
    const cells = useLeaseMoveOutCells(locale);
    const state = (moveOut: LeaseMoveOutRecord) => (
        <StatusBadge
            value={moveOut.state}
            label={moveOutStateLabel(moveOut, t)}
            tone={moveOutStateTone(moveOut.state)}
        />
    );
    const requirements = (moveOut: LeaseMoveOutRecord) => (
        <div className="pmc-stacked-cell">
            <strong>
                {t('lease_move_outs.requirements_count', undefined, {
                    completed: localizedNumber(
                        requirementCount(moveOut),
                        locale,
                    ),
                    total: localizedNumber(4, locale),
                })}
            </strong>
            <span>
                {t(
                    `lease_move_outs.deposit_${moveOut.deposit_disposition}` as UiTranslationKey,
                )}
            </span>
        </div>
    );
    const columns: Array<TableColumn<LeaseMoveOutRecord>> = [
        {
            key: 'lease',
            label: t('lease_move_outs.lease'),
            render: (moveOut) => (
                <div className="pmc-primary-cell">
                    <strong>{moveOut.code}</strong>
                    <span>
                        {t(
                            `status.${moveOut.lease_status}` as UiTranslationKey,
                        )}
                    </span>
                    {state(moveOut)}
                </div>
            ),
        },
        {
            key: 'tenant',
            label: t('lease_move_outs.tenant'),
            render: cells.tenant,
        },
        {
            key: 'property',
            label: t('lease_move_outs.property_asset'),
            render: cells.propertyAsset,
        },
        {
            key: 'move_out',
            label: t('lease_move_outs.move_out_date'),
            render: (moveOut) => (
                <div className="pmc-stacked-cell">
                    <strong>{humanDate(moveOut.move_out_date, locale)}</strong>
                    <span>
                        {t(
                            `lease_move_outs.reason_${moveOut.reason}` as UiTranslationKey,
                        )}
                    </span>
                </div>
            ),
        },
        {
            key: 'handover',
            label: t('lease_move_outs.handover'),
            render: requirements,
        },
        {
            key: 'balance',
            label: t('lease_move_outs.outstanding'),
            render: (moveOut) => (
                <strong>
                    {currency(
                        moveOut.outstanding_amount,
                        locale,
                        moveOut.currency,
                    )}
                </strong>
            ),
        },
        {
            key: 'actions',
            label: t('lease_move_outs.actions'),
            className: 'text-end',
            render: cells.actions,
        },
    ];

    return {
        columns,
        mobileCard: {
            title: (moveOut) => moveOut.code,
            subtitle: cells.tenant,
            status: state,
            meta: [
                {
                    label: t('lease_move_outs.property_asset'),
                    value: cells.propertyAsset,
                },
                {
                    label: t('lease_move_outs.move_out_date'),
                    value: (moveOut) =>
                        humanDate(moveOut.move_out_date, locale),
                },
                {
                    label: t('lease_move_outs.handover'),
                    value: requirements,
                },
            ],
            actions: cells.primaryAction,
        },
    };
}
