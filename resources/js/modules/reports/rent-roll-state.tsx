import { useTranslator } from '@/lib/i18n';

import type { RentRollState as State } from './rent-roll-types';

export function RentRollState({ state }: { state: State }) {
    const { t } = useTranslator();

    return (
        <span className={`pmc-rent-roll-state is-${state}`}>
            <i className={`bi ${icon(state)}`} aria-hidden="true" />
            {t(`reports.rent_roll_state_${state}`)}
        </span>
    );
}

function icon(state: State) {
    return {
        occupied: 'bi-building-check',
        vacant: 'bi-door-open',
        arrears: 'bi-exclamation-circle',
        expiring: 'bi-calendar2-week',
    }[state];
}
