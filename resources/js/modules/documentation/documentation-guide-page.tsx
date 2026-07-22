import { Head, usePage } from '@inertiajs/react';

import '../../../css/styles/documentation.css';

import { AdminLayout } from '@/layouts/admin-layout';

import { DocumentationGuideContent } from './documentation-guide-content';
import { DocumentationGuideHeader } from './documentation-guide-header';
import { DocumentationGuideNavigation } from './documentation-guide-navigation';
import { DocumentationRelatedGuides } from './documentation-related-guides';
import type { DocumentationGuidePageProps } from './types';

export default function DocumentationGuidePage() {
    const { props } = usePage<DocumentationGuidePageProps>();

    return (
        <AdminLayout>
            <Head title={props.guide.title} />
            <div className="pmc-documentation-page">
                <DocumentationGuideHeader guide={props.guide} />
                <div className="pmc-doc-detail-layout">
                    <DocumentationGuideNavigation guide={props.guide} />
                    <DocumentationGuideContent guide={props.guide} />
                </div>
                <DocumentationRelatedGuides guides={props.relatedGuides} />
            </div>
        </AdminLayout>
    );
}
