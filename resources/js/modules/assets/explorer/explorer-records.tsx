import { Link } from '@inertiajs/react';

import { TablePagination } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { ExplorerRecordCard } from './explorer-record-card';
import type { PropertyExplorerPayload } from './types';

export function ExplorerRecords({
    explorer,
    canCreate,
}: {
    explorer: PropertyExplorerPayload;
    canCreate: boolean;
}) {
    const { t } = useTranslator();
    const records = explorer.records;

    if (!records) {
        return null;
    }

    return (
        <section className="pmc-explorer-records">
            <header>
                <div>
                    <span>{t('assets.explorer.inside_selected')}</span>
                    <h2>{t('assets.explorer.records_title')}</h2>
                    <p>{t('assets.explorer.records_help')}</p>
                </div>
                <strong>{records.total}</strong>
            </header>

            {records.data.length > 0 ? (
                <>
                    <div className="pmc-explorer-record-grid">
                        {records.data.map((asset) => (
                            <ExplorerRecordCard key={asset.id} asset={asset} />
                        ))}
                    </div>
                    <TablePagination data={records} />
                </>
            ) : (
                <div className="pmc-explorer-empty">
                    <i className="bi bi-diagram-3" aria-hidden="true" />
                    <strong>{t('assets.explorer.no_records')}</strong>
                    <p>{t('assets.explorer.no_records_help')}</p>
                    {canCreate && explorer.selected ? (
                        <Link
                            href={explorer.selected.add_child_href}
                            className="btn btn-primary"
                        >
                            <i className="bi bi-plus-lg" aria-hidden="true" />
                            {t('assets.explorer.add_inside')}
                        </Link>
                    ) : null}
                </div>
            )}
        </section>
    );
}
