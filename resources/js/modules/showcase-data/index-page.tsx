import { Head, usePage } from '@inertiajs/react';

import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import '../../../css/styles/showcase-data.css';
import { DataLabHeader } from './data-lab-header';
import { DataLabOverview } from './data-lab-overview';
import { DataLabTargetPlan } from './data-lab-target-plan';
import { DatasetHistory } from './dataset-history';
import { PurgeDialog } from './purge-dialog';
import type { ShowcaseDataPageProps } from './types';
import { useShowcaseDataLab } from './use-showcase-data-lab';

export default function ShowcaseDataIndexPage() {
    const { props } = usePage<ShowcaseDataPageProps>();
    const { t } = useTranslator();
    const lab = useShowcaseDataLab(props.summary.active);

    return (
        <AdminLayout>
            <Head title={t('showcase.title')} />
            <DataLabHeader />
            <DataLabOverview
                summary={props.summary}
                canGenerate={props.canGenerate}
                legacyCandidates={props.legacyCandidates}
                busy={lab.busyAction !== null}
                onGenerate={lab.generate}
            />
            <DataLabTargetPlan targets={props.targets} />
            <DatasetHistory
                datasets={props.datasets}
                busyAction={lab.busyAction}
                onRetry={lab.retry}
                onPurge={lab.openPurge}
            />
            {lab.purgeDataset ? (
                <PurgeDialog
                    dataset={lab.purgeDataset}
                    onClose={lab.closePurge}
                />
            ) : null}
        </AdminLayout>
    );
}
