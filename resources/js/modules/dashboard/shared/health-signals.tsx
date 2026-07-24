import { Link } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';
import { percent } from '@/lib/utils';

export type HealthSignal = {
    label: string;
    value: number;
    href: string;
};

export function HealthSignals({ signals }: { signals: HealthSignal[] }) {
    const { locale, text } = useTranslator();

    return (
        <div className="pmc-health-signals">
            {signals.map((signal) => (
                <Link key={signal.label} href={signal.href}>
                    <div>
                        <span>{text(signal.label)}</span>
                        <strong>{percent(signal.value, locale)}</strong>
                    </div>
                    <div className="pmc-health-track">
                        <i
                            style={{
                                width: `${Math.min(100, Math.max(0, signal.value))}%`,
                            }}
                        />
                    </div>
                </Link>
            ))}
        </div>
    );
}
