import { Head } from '@inertiajs/react';

import { localizedPageField } from './content';
import type { PublicPageRecord } from './types';

export function PublicPageHead({
    page,
    locale,
}: {
    page: PublicPageRecord;
    locale: 'en' | 'ar';
}) {
    const title =
        localizedPageField(page, 'seo_title', locale) ||
        localizedPageField(page, 'title', locale);
    const description = localizedPageField(page, 'seo_description', locale);

    return (
        <Head title={title}>
            {description ? (
                <meta name="description" content={description} />
            ) : null}
        </Head>
    );
}
