import type { KeyboardEvent } from 'react';

import { useTranslator } from '@/lib/i18n';

import type { CmsBuilderController } from './use-cms-builder';

export function CmsBuilderOutline({
    builder,
}: {
    builder: CmsBuilderController;
}) {
    const { t } = useTranslator();
    const selectWithKeyboard = (
        event: KeyboardEvent<HTMLElement>,
        id: number,
    ) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        event.preventDefault();
        builder.setSelectedId(id);
    };

    return (
        <div className="pmc-cms-outline">
            {builder.orderedSections.map((item, index) => (
                <article
                    key={item.id}
                    className={`${builder.selected?.id === item.id ? 'active' : ''} ${
                        builder.draggingId === item.id ? 'is-dragging' : ''
                    }`}
                    draggable={!builder.isBusy}
                    role="button"
                    tabIndex={0}
                    aria-pressed={builder.selected?.id === item.id}
                    onDragStart={() => builder.setDraggingId(item.id)}
                    onDragOver={(event) => event.preventDefault()}
                    onDrop={() => builder.reorder(item.id)}
                    onDragEnd={() => builder.setDraggingId(null)}
                    onClick={() => builder.setSelectedId(item.id)}
                    onKeyDown={(event) => selectWithKeyboard(event, item.id)}
                >
                    <span className="pmc-cms-drag-handle" aria-hidden="true">
                        <i className="bi bi-grip-vertical" />
                    </span>
                    <div>
                        <span>
                            {index + 1}.{' '}
                            {item.section
                                ? t(
                                      `cms.section_types.${item.section.section_type}`,
                                      item.section.section_type,
                                  )
                                : t('cms.section')}
                        </span>
                        <strong>
                            {builder.localizedSectionName(item.section)}
                        </strong>
                    </div>
                    <span
                        className={item.is_visible ? 'is-visible' : 'is-hidden'}
                    >
                        {item.is_visible ? t('cms.visible') : t('cms.hidden')}
                    </span>
                </article>
            ))}
        </div>
    );
}
