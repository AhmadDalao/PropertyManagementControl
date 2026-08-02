import { useTranslator } from '@/lib/i18n';

import { fieldLabel, sheetLabel } from './opening-data-labels';
import type { OpeningDataPreview } from './types';

export function OpeningDataIssues({
    preview,
}: {
    preview: OpeningDataPreview;
}) {
    const { t } = useTranslator();

    return (
        <section className="pmc-opening-issues">
            <h3>
                <i className="bi bi-list-check" aria-hidden="true" />
                {t('opening_data.issues')}
                <span>{preview.issue_count}</span>
            </h3>
            <ul>
                {preview.issues.map((issue, index) => (
                    <li
                        key={`${issue.sheet}-${issue.row}-${issue.field}-${index}`}
                    >
                        <strong>
                            {issue.row
                                ? t('opening_data.issue_location', undefined, {
                                      sheet: sheetLabel(issue.sheet, t),
                                      row: issue.row,
                                      field: fieldLabel(issue.field, t),
                                  })
                                : t(
                                      'opening_data.sheet_issue_location',
                                      undefined,
                                      {
                                          sheet: sheetLabel(issue.sheet, t),
                                          field: fieldLabel(issue.field, t),
                                      },
                                  )}
                        </strong>
                        <span>{issue.message}</span>
                    </li>
                ))}
            </ul>
            {preview.issues_truncated ? (
                <p>{t('opening_data.more_issues')}</p>
            ) : null}
        </section>
    );
}
