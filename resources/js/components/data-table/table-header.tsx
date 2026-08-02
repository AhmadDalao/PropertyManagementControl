import { useTranslator } from '@/lib/i18n';

import type { TableExportLink } from './types';

export function TableHeader({
    title,
    description,
    total,
    exportHref,
    exportLinks,
}: {
    title: string;
    description?: string;
    total: number;
    exportHref?: string;
    exportLinks?: TableExportLink[];
}) {
    const { t, text } = useTranslator();
    const downloads =
        exportLinks ??
        (exportHref
            ? [
                  {
                      href: exportHref,
                      icon: 'bi-file-earmark-excel',
                      label: t('actions.export_xlsx', 'Export Excel (.xlsx)'),
                  },
              ]
            : []);

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
            {downloads.length > 0 ? (
                <div className="pmc-table-export-actions">
                    <div className="pmc-table-export-desktop">
                        {downloads.map((download) => (
                            <a
                                className="btn btn-outline-secondary pmc-export-button"
                                href={download.href}
                                key={`${download.href}-${download.label}`}
                            >
                                <i
                                    className={`bi ${download.icon ?? 'bi-download'}`}
                                    aria-hidden="true"
                                />
                                <span>{download.label}</span>
                            </a>
                        ))}
                    </div>
                    {downloads.length > 1 ? (
                        <details className="pmc-table-export-menu">
                            <summary className="btn btn-outline-secondary pmc-export-button">
                                <i
                                    className="bi bi-download"
                                    aria-hidden="true"
                                />
                                <span>{t('actions.download_report')}</span>
                            </summary>
                            <div>
                                {downloads.map((download) => (
                                    <a
                                        href={download.href}
                                        key={`${download.href}-${download.label}-menu`}
                                    >
                                        <i
                                            className={`bi ${download.icon ?? 'bi-download'}`}
                                            aria-hidden="true"
                                        />
                                        <span>{download.label}</span>
                                    </a>
                                ))}
                            </div>
                        </details>
                    ) : null}
                </div>
            ) : null}
        </div>
    );
}
