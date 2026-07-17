import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import type { SharedProps } from '@/types';

import { SectionContentEditor } from './section-content-editor';
import {
    defaultSectionContent,
    jsonText,
    parseJsonObject,
} from './section-schema';
import type { CmsSectionRecord } from './types';

type PageProps = SharedProps & {
    section: CmsSectionRecord | null;
    sectionTypes: Array<{ label: string; value: string }>;
};

export default function CmsSectionFormPage() {
    const { props } = usePage<PageProps>();
    const section = props.section;
    const initialType = section?.section_type ?? 'hero';
    const [contentError, setContentError] = useState('');
    const form = useForm({
        section_type: initialType,
        name_en: section?.name_en ?? '',
        name_ar: section?.name_ar ?? '',
        status: section?.status ?? 'active',
        content_en_json: jsonText(
            section?.content_en ?? defaultSectionContent(initialType, 'en'),
        ),
        content_ar_json: jsonText(
            section?.content_ar ?? defaultSectionContent(initialType, 'ar'),
        ),
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const contentEn = parseJsonObject(form.data.content_en_json);
        const contentAr = parseJsonObject(form.data.content_ar_json);

        if (!contentEn || !contentAr) {
            setContentError(
                'The advanced JSON is invalid. Fix it or reload starter content.',
            );

            return;
        }

        setContentError('');
        form.transform((data) => ({
            section_type: data.section_type,
            name_en: data.name_en,
            name_ar: data.name_ar,
            status: data.status,
            content_en: contentEn,
            content_ar: contentAr,
            settings_json: {},
        }));

        if (section) {
            form.put(`/cms/sections/${section.id}`);

            return;
        }

        form.post('/cms/sections');
    };

    return (
        <AdminLayout>
            <Head
                title={section ? `Edit ${section.name_en}` : 'Create section'}
            />

            <WorkspaceHeader
                eyebrow="Website control"
                title={section ? `Edit ${section.name_en}` : 'Create section'}
                description="Define the reusable block once, then attach and order it from any page builder."
                actions={[
                    {
                        label: 'Back to website control',
                        href: '/cms',
                        icon: 'bi-arrow-left',
                        tone: 'secondary',
                    },
                ]}
            />

            <form className="pmc-cms-section-form" onSubmit={submit}>
                <section className="pmc-workspace-panel">
                    <div className="pmc-workspace-panel-head">
                        <div>
                            <span>Section identity</span>
                            <h2>Name and type</h2>
                            <p>
                                Internal names help editors find the block. The
                                type controls its public layout.
                            </p>
                        </div>
                    </div>

                    <div className="pmc-cms-section-basics">
                        <label className="pmc-resource-field">
                            <span>Section type</span>
                            <select
                                className="form-select"
                                value={form.data.section_type}
                                onChange={(event) =>
                                    form.setData(
                                        'section_type',
                                        event.currentTarget.value,
                                    )
                                }
                            >
                                {props.sectionTypes.map((type) => (
                                    <option key={type.value} value={type.value}>
                                        {type.label}
                                    </option>
                                ))}
                            </select>
                            {form.errors.section_type ? (
                                <em>{form.errors.section_type}</em>
                            ) : null}
                        </label>
                        <label className="pmc-resource-field">
                            <span>English internal name</span>
                            <input
                                className="form-control"
                                value={form.data.name_en}
                                onChange={(event) =>
                                    form.setData(
                                        'name_en',
                                        event.currentTarget.value,
                                    )
                                }
                                required
                            />
                            {form.errors.name_en ? (
                                <em>{form.errors.name_en}</em>
                            ) : null}
                        </label>
                        <label className="pmc-resource-field" dir="rtl">
                            <span>Arabic internal name</span>
                            <input
                                className="form-control"
                                value={form.data.name_ar}
                                onChange={(event) =>
                                    form.setData(
                                        'name_ar',
                                        event.currentTarget.value,
                                    )
                                }
                                required
                            />
                            {form.errors.name_ar ? (
                                <em>{form.errors.name_ar}</em>
                            ) : null}
                        </label>
                        <label className="pmc-resource-field">
                            <span>Status</span>
                            <select
                                className="form-select"
                                value={form.data.status}
                                onChange={(event) =>
                                    form.setData(
                                        'status',
                                        event.currentTarget.value,
                                    )
                                }
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="archived">Archived</option>
                            </select>
                        </label>
                    </div>
                </section>

                <SectionContentEditor
                    sectionType={form.data.section_type}
                    contentEnJson={form.data.content_en_json}
                    contentArJson={form.data.content_ar_json}
                    onContentEnChange={(value) =>
                        form.setData('content_en_json', value)
                    }
                    onContentArChange={(value) =>
                        form.setData('content_ar_json', value)
                    }
                />

                {contentError ? (
                    <div className="alert alert-danger">{contentError}</div>
                ) : null}

                <div className="pmc-cms-form-actions">
                    <Link href="/cms" className="btn btn-light">
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        className="btn btn-primary"
                        disabled={form.processing}
                    >
                        {section ? 'Update section' : 'Create section'}
                    </button>
                </div>
            </form>
        </AdminLayout>
    );
}
