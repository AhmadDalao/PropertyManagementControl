import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { DecisionCard } from './types';

export function DecisionCardGrid({ cards }: { cards: DecisionCard[] }) {
    const { t, text } = useTranslator();

    return (
        <section className="pmc-resource-decision-grid">
            {cards.map((card) => (
                <article
                    key={card.title}
                    className={`pmc-resource-decision-card pmc-resource-decision-${card.tone ?? 'muted'}`}
                >
                    <div>
                        {card.icon ? <i className={`bi ${card.icon}`} /> : null}
                        <span>{text(card.title)}</span>
                    </div>
                    <strong>{card.value}</strong>
                    {card.detail ? (
                        <p>
                            {typeof card.detail === 'string'
                                ? text(card.detail)
                                : card.detail}
                        </p>
                    ) : null}
                    {card.href ? (
                        <Link href={card.href}>
                            {card.actionLabel
                                ? text(card.actionLabel)
                                : t('actions.open')}
                            <i className="bi bi-arrow-right" />
                        </Link>
                    ) : null}
                </article>
            ))}
        </section>
    );
}
