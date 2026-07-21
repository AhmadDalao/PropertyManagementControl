import { useTranslator } from '@/lib/i18n';

export function TableHeader({
    title,
    description,
    total,
    exportHref,
}: {
    title: string;
    description?: string;
    total: number;
    exportHref?: string;
}) {
    const { t, text } = useTranslator();

    return (
        <div className="pmc-operations-head">
            <div>
                <div className="pmc-table-heading">
                    <span className="pmc-table-icon">
                        <i className="bi bi-view-list" />
                    </span>
                    <strong>{text(title)}</strong>
                    <span className="pmc-table-count">{total}</span>
                </div>
                {description ? (
                    <p className="pmc-table-copy">{text(description)}</p>
                ) : null}
            </div>
            {exportHref ? (
                <a
                    className="btn btn-outline-secondary pmc-export-button"
                    href={exportHref}
                >
                    <i className="bi bi-file-earmark-excel" />
                    <span>
                        {t('actions.export_xlsx', 'Export Excel (.xlsx)')}
                    </span>
                </a>
            ) : null}
        </div>
    );
}
