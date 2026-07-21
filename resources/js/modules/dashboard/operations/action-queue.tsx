import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { NextAction } from '../types';

export function OperationsActionQueue({ actions }: { actions: NextAction[] }) {
    const { t, text } = useTranslator();

    if (actions.length === 0) {
        return null;
    }

    return (
        <section className="pmc-action-queue" aria-label={text('Next actions')}>
            <div className="pmc-action-queue-label">
                <span>{text('Today')}</span>
                <strong>{text('Next actions')}</strong>
            </div>
            <div className="pmc-action-queue-grid">
                {actions.map((action, index) => (
                    <Link
                        key={`${action.href}-${action.label}`}
                        href={action.href}
                    >
                        <span>{String(index + 1).padStart(2, '0')}</span>
                        <i className={`bi ${action.icon}`} />
                        <div>
                            <strong>{text(action.label)}</strong>
                            <small>{actionDescription(action, t, text)}</small>
                        </div>
                        <i className="bi bi-arrow-up-right" />
                    </Link>
                ))}
            </div>
        </section>
    );
}

function actionDescription(
    action: NextAction,
    t: ReturnType<typeof useTranslator>['t'],
    translate: (value: string) => string,
): string {
    if (action.href !== '/property-map') {
        return translate(action.description);
    }

    const [positions = '0', identities = '0'] =
        action.description.match(/\d+/g) ?? [];

    return t('dashboard.map_action_description', undefined, {
        positions,
        identities,
    });
}
