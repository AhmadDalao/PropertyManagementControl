import { Head, usePage } from '@inertiajs/react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';
import type { MediaPickerOption } from '@/modules/media/types';
import type { SharedProps } from '@/types';

import { CmsSectionFormActions } from './cms-section-form-actions';
import { CmsSectionIdentity } from './cms-section-identity';
import { SectionContentEditor } from './section-content-editor';
import type { CmsSectionRecord } from './types';
import { useCmsSectionForm } from './use-cms-section-form';

type PageProps = SharedProps & {
    section: CmsSectionRecord | null;
    sectionTypes: Array<{ label: string; value: string }>;
    mediaOptions: MediaPickerOption[];
};

export default function CmsSectionFormPage() {
    const { props } = usePage<PageProps>();
    const { t } = useTranslator();
    const controller = useCmsSectionForm(props.section);

    return (
        <AdminLayout>
            <Head title={controller.pageTitle} />
            <WorkspaceHeader
                eyebrow={t('cms.section_form_eyebrow')}
                title={controller.pageTitle}
                description={t('cms.section_form_description')}
                actions={[
                    {
                        label: t('cms.back_to_control'),
                        href: '/cms',
                        icon: 'bi-arrow-left',
                        tone: 'secondary',
                    },
                ]}
            />
            <form className="pmc-cms-section-form" onSubmit={controller.submit}>
                <CmsSectionIdentity
                    controller={controller}
                    sectionTypes={props.sectionTypes}
                />
                <SectionContentEditor
                    sectionType={controller.form.data.section_type}
                    contentEnJson={controller.form.data.content_en_json}
                    contentArJson={controller.form.data.content_ar_json}
                    mediaOptions={props.mediaOptions}
                    onContentEnChange={(value) =>
                        controller.form.setData('content_en_json', value)
                    }
                    onContentArChange={(value) =>
                        controller.form.setData('content_ar_json', value)
                    }
                />
                {controller.contentError ? (
                    <div className="alert alert-danger">
                        {controller.contentError}
                    </div>
                ) : null}
                <CmsSectionFormActions
                    editing={Boolean(props.section)}
                    processing={controller.form.processing}
                />
            </form>
        </AdminLayout>
    );
}
