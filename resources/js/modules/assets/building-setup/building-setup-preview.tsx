import { useTranslator } from '@/lib/i18n';

import {
    generatedUnitLabel,
    previewFloorNumbers,
    structureTotals,
} from './structure-plan';
import type { BuildingSetupValues } from './types';

export function BuildingSetupPreview({
    values,
    recordLimit,
}: {
    values: BuildingSetupValues;
    recordLimit: number;
}) {
    const { t } = useTranslator();
    const totals = structureTotals(values);
    const floors = previewFloorNumbers(values);
    const buildingTitle =
        values.title_en.trim() || values.title_ar.trim() || '';
    const prefix = values.code_prefix.trim() || 'BUILDING';
    const overLimit = totals.total > recordLimit;

    return (
        <aside className="pmc-building-setup-preview">
            <span>{t('assets.builder.preview_eyebrow')}</span>
            <h2>{t('assets.builder.preview_title')}</h2>
            <p>{t('assets.builder.preview_help')}</p>

            <div className="pmc-building-setup-metrics">
                <PreviewMetric
                    label={t('assets.builder.building_record')}
                    value={1}
                />
                <PreviewMetric
                    label={t('assets.builder.floor_records')}
                    value={totals.floors}
                />
                <PreviewMetric
                    label={t('assets.builder.rentable_records')}
                    value={totals.rentable}
                />
                <PreviewMetric
                    label={t('assets.builder.total_records')}
                    value={totals.total}
                    danger={overLimit}
                />
            </div>

            {overLimit ? (
                <div className="pmc-building-setup-warning" role="alert">
                    <i className="bi bi-exclamation-circle" />
                    <span>
                        {t('assets.builder.record_limit_error', undefined, {
                            limit: recordLimit,
                        })}
                    </span>
                </div>
            ) : null}

            <div className="pmc-building-setup-tree">
                <strong>{t('assets.builder.tree_title')}</strong>
                {buildingTitle ? (
                    <div>
                        <header>
                            <i className="bi bi-buildings" />
                            <span>
                                <strong>{buildingTitle}</strong>
                                <small>{prefix}</small>
                            </span>
                        </header>
                        <ul>
                            {floors.map((floor) => {
                                const firstUnit = generatedUnitLabel(floor, 1);
                                const lastUnit = generatedUnitLabel(
                                    floor,
                                    totals.unitsPerFloor,
                                );
                                const floorTitle =
                                    floor === 0
                                        ? t(
                                              'assets.builder.generated_ground_floor',
                                          )
                                        : t(
                                              'assets.builder.generated_floor',
                                              undefined,
                                              { number: floor },
                                          );
                                const recordKey =
                                    values.unit_type === 'space'
                                        ? 'assets.builder.generated_space'
                                        : 'assets.builder.generated_unit';

                                return (
                                    <li key={floor}>
                                        <i className="bi bi-diagram-3" />
                                        <span>
                                            <strong>{floorTitle}</strong>
                                            <small>
                                                {t(recordKey, undefined, {
                                                    number: firstUnit,
                                                })}
                                                {' - '}
                                                {t(recordKey, undefined, {
                                                    number: lastUnit,
                                                })}
                                            </small>
                                        </span>
                                    </li>
                                );
                            })}
                        </ul>
                        {totals.floors > floors.length ? (
                            <small>{t('assets.builder.sample_limited')}</small>
                        ) : null}
                    </div>
                ) : (
                    <p>{t('assets.builder.tree_empty')}</p>
                )}
            </div>

            <div className="pmc-building-setup-note">
                <i className="bi bi-check2-circle" />
                <span>{t('assets.builder.inheritance_note')}</span>
            </div>
        </aside>
    );
}

function PreviewMetric({
    label,
    value,
    danger = false,
}: {
    label: string;
    value: number;
    danger?: boolean;
}) {
    return (
        <div className={danger ? 'is-danger' : undefined}>
            <span>{label}</span>
            <strong>{value}</strong>
        </div>
    );
}
