import { usePage } from '@inertiajs/react';

import { CmsRenderer } from './cms-renderer';
import { PublicLayout } from './public-layout';
import { PublicPageHead } from './public-page-head';
import type { PublicSitePageProps } from './types';

export default function ContentPage() {
    const { props } = usePage<PublicSitePageProps>();

    return (
        <PublicLayout>
            <PublicPageHead page={props.page} locale={props.app.locale} />
            <CmsRenderer
                sections={props.page.page_sections ?? []}
                locale={props.app.locale}
            />
        </PublicLayout>
    );
}
