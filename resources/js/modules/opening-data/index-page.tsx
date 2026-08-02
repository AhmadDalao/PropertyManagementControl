import { Head, usePage } from '@inertiajs/react';

import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import '../../../css/styles/opening-data.css';
import { OpeningDataGuidance } from './opening-data-guidance';
import { OpeningDataHeader } from './opening-data-header';
import { OpeningDataPreview } from './opening-data-preview';
import { OpeningDataSteps } from './opening-data-steps';
import { OpeningDataUpload } from './opening-data-upload';
import type { OpeningDataPageProps } from './types';

export default function OpeningDataIndexPage() {
    const { props } = usePage<OpeningDataPageProps>();
    const { t } = useTranslator();

    return (
        <AdminLayout>
            <Head title={t('opening_data.title')} />
            <div className="pmc-opening-page">
                <OpeningDataHeader />
                <OpeningDataSteps />
                <div className="pmc-opening-layout">
                    <OpeningDataUpload payload={props.openingData} />
                    <OpeningDataGuidance payload={props.openingData} />
                </div>
                {props.openingData.preview ? (
                    <OpeningDataPreview preview={props.openingData.preview} />
                ) : null}
            </div>
        </AdminLayout>
    );
}
