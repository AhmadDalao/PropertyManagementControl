import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { localizedNumber } from '@/lib/utils';

import type { NextAction } from '../types';

export function OperationsActionQueue({ actions }: { actions: NextAction[] }) {
    const { locale, t, text } = useTranslator();

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
                        <span>{localizedNumber(index + 1, locale, 2)}</span>
                        <i className={`bi ${action.icon}`} />
                        <div>
                            <strong>{text(action.label)}</strong>
                            <small>
                                {actionDescription(action, locale, t, text)}
                            </small>
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
    locale: string,
    t: ReturnType<typeof useTranslator>['t'],
    translate: (value: string) => string,
): string {
    if (action.label !== 'Complete property map') {
        return translate(action.description);
    }

    const [positions = '0', identities = '0'] =
        action.description.match(/\d+/g) ?? [];

    return t('dashboard.map_action_description', undefined, {
        positions: localizedNumber(Number(positions), locale),
        identities: localizedNumber(Number(identities), locale),
    });
}
