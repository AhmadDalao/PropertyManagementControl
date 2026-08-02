import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import { ShowcaseBadge } from './showcase-badge';
import { TableEmpty } from './table-empty';
import { isShowcaseRow } from './table-utils';
import type { DataTableRow, MobileTableConfig } from './types';

type MobileRecordListProps<T extends DataTableRow> = {
    rows: T[];
    config: MobileTableConfig<T>;
    emptyText: string;
    createHref?: string;
    createLabel: string;
    rowKey?: (row: T) => string | number;
    rowHref?: (row: T) => string;
};

export function MobileRecordList<T extends DataTableRow>({
    rows,
    config,
    emptyText,
    createHref,
    createLabel,
    rowKey,
    rowHref,
}: MobileRecordListProps<T>) {
    const { t, text } = useTranslator();

    return (
        <div className="pmc-mobile-record-list">
            {rows.length > 0 ? (
                rows.map((row, index) => {
                    const actions = config.actions?.(row);
                    const status = config.status?.(row);

                    return (
                        <article
                            className="pmc-mobile-record-card"
                            key={rowKey ? rowKey(row) : (row.id ?? index)}
                        >
                            <div className="pmc-mobile-record-head">
                                <div className="pmc-mobile-record-identity">
                                    {rowHref ? (
                                        <Link
                                            href={rowHref(row)}
                                            className="pmc-mobile-record-title-link"
                                        >
                                            {config.title(row)}
                                            <i
                                                className="bi bi-arrow-up-right"
                                                aria-hidden="true"
                                            />
                                        </Link>
                                    ) : (
                                        config.title(row)
                                    )}
                                    {config.subtitle ? (
                                        <div className="pmc-mobile-record-subtitle">
                                            {config.subtitle(row)}
                                        </div>
                                    ) : null}
                                    {isShowcaseRow(row) ? (
                                        <ShowcaseBadge
                                            label={t('showcase.badge')}
                                        />
                                    ) : null}
                                </div>
                                {status || actions ? (
                                    <div className="pmc-mobile-record-controls">
                                        {status}
                                        {actions ? (
                                            <details className="pmc-mobile-action-menu">
                                                <summary
                                                    aria-label={t(
                                                        'common.more_actions',
                                                        'More actions',
                                                    )}
                                                >
                                                    <i className="bi bi-three-dots" />
                                                </summary>
                                                <div>{actions}</div>
                                            </details>
                                        ) : null}
                                    </div>
                                ) : null}
                            </div>
                            {config.meta && config.meta.length > 0 ? (
                                <dl
                                    className={`pmc-mobile-record-meta is-${Math.min(config.meta.length, 3)}`}
                                >
                                    {config.meta.slice(0, 3).map((item) => (
                                        <div key={item.label}>
                                            <dt>{text(item.label)}</dt>
                                            <dd>{item.value(row)}</dd>
                                        </div>
                                    ))}
                                </dl>
                            ) : null}
                        </article>
                    );
                })
            ) : (
                <TableEmpty
                    title={t('table.no_matches', 'No matching records')}
                    message={text(emptyText)}
                    createHref={createHref}
                    createLabel={text(createLabel)}
                />
            )}
        </div>
    );
}
