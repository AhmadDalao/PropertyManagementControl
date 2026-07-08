import { Head, usePage } from '@inertiajs/react';

import { CmsRenderer } from '@/components/cms-renderer';
import { PublicLayout } from '@/layouts/public-layout';
import type { SharedProps } from '@/types';

type PageProps = SharedProps & {
    page: {
        title_en: string;
        title_ar: string;
        page_sections?: Array<{
            id: number;
            section?: {
                id?: number;
                section_type: string;
                content_en?: Record<string, unknown>;
                content_ar?: Record<string, unknown>;
            };
        }>;
    };
};

export default function PublicPage() {
    const { props } = usePage<PageProps>();
    const title = props.app.locale === 'ar' ? props.page.title_ar : props.page.title_en;

    return (
        <PublicLayout>
            <Head title={title} />
            <CmsRenderer
                sections={props.page.page_sections ?? []}
                locale={props.app.locale}
            />
        </PublicLayout>
    );
}
