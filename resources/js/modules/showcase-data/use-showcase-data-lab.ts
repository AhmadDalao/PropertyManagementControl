import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import type { ShowcaseDataset } from './types';

export function useShowcaseDataLab(activeDatasets: number) {
    const [purgeDataset, setPurgeDataset] = useState<ShowcaseDataset | null>(
        null,
    );
    const [busyAction, setBusyAction] = useState<string | null>(null);

    useEffect(() => {
        if (activeDatasets === 0) {
            return;
        }

        const timer = window.setInterval(() => {
            router.reload({
                only: ['datasets', 'summary', 'canGenerate'],
            });
        }, 5000);

        return () => window.clearInterval(timer);
    }, [activeDatasets]);

    const generate = () => {
        setBusyAction('generate');
        router.post(
            '/system/showcase-data',
            {},
            {
                preserveScroll: true,
                onFinish: () => setBusyAction(null),
            },
        );
    };

    const retry = (dataset: ShowcaseDataset) => {
        setBusyAction(`retry-${dataset.id}`);
        router.post(
            `/system/showcase-data/${dataset.id}/retry`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setBusyAction(null),
            },
        );
    };

    return {
        busyAction,
        closePurge: () => setPurgeDataset(null),
        generate,
        openPurge: setPurgeDataset,
        purgeDataset,
        retry,
    };
}
