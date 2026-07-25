import { TablePagination } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import { ActionCenterCard } from './action-center-card';
import type { ActionCenterPageProps } from './types';

export function ActionCenterWorkspace({
    actionItems,
}: Pick<ActionCenterPageProps, 'actionItems'>) {
    const { locale, t } = useTranslator();

    return (
        <section className="pmc-action-workspace">
            <header>
                <div>
                    <span>{t('action_center.work_queue')}</span>
                    <h2>{t('action_center.work_queue_title')}</h2>
                </div>
                <p>
                    {t('action_center.result_count', undefined, {
                        count: localizedNumber(actionItems.total, locale),
                    })}
                </p>
            </header>

            {actionItems.data.length > 0 ? (
                <div className="pmc-action-card-grid" role="list">
                    {actionItems.data.map((item) => (
                        <div key={item.key} role="listitem">
                            <ActionCenterCard item={item} />
                        </div>
                    ))}
                </div>
            ) : (
                <div className="pmc-action-empty" role="status">
                    <i className="bi bi-check2-circle" aria-hidden="true" />
                    <div>
                        <strong>{t('action_center.empty_title')}</strong>
                        <p>{t('action_center.empty_description')}</p>
                    </div>
                </div>
            )}

            {actionItems.last_page > 1 ? (
                <TablePagination data={actionItems} />
            ) : null}
        </section>
    );
}
