import { DataTable, exportUrl } from '@/components/data-table';
import { useTranslator } from '@/lib/i18n';

import { mediaFilterFields } from './media-filters';
import { useMediaTableConfig } from './media-table-config';
import type { MediaIndexPageProps } from './types';

export function MediaTable({ props }: { props: MediaIndexPageProps }) {
    const { locale, t } = useTranslator();
    const table = useMediaTableConfig(locale);

    return (
        <DataTable
            title={t('media.register_title')}
            description={t('media.register_description')}
            data={props.mediaFiles}
            filters={props.filters}
            counts={props.counts}
            basePath="/media-files"
            rowHref={(media) => `/media-files/${media.id}`}
            exportHref={exportUrl('/exports/media-files', props.filters)}
            filterFields={mediaFilterFields(props, t)}
            emptyText={t('media.no_matches')}
            createHref="/media-files/create"
            createLabel={t('media.upload_media')}
            columns={table.columns}
            mobileCard={table.mobileCard}
        />
    );
}
