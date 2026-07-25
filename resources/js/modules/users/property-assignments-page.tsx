import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useDeferredValue, useState } from 'react';
import type { FormEvent } from 'react';

import '../../../css/styles/users/property-assignments.css';

import { WorkspaceHeader } from '@/components/operations';
import { AdminLayout } from '@/layouts/admin-layout';
import { useTranslator } from '@/lib/i18n';

import { PropertyAssignmentCard } from './property-assignment-card';
import {
    PropertyAssignmentOverview,
    PropertyAssignmentToolbar,
} from './property-assignment-controls';
import type { ManagerPropertyAssignmentPageProps } from './types';

export default function PropertyAssignmentsPage() {
    const { props } = usePage<ManagerPropertyAssignmentPageProps>();
    const { t } = useTranslator();
    const page = props.assignmentPage;
    const form = useForm({ asset_ids: page.selected_ids });
    const [search, setSearch] = useState('');
    const deferredSearch = useDeferredValue(search.trim().toLocaleLowerCase());
    const properties = page.properties.filter((property) =>
        `${property.title} ${property.code} ${property.parent ?? ''}`
            .toLocaleLowerCase()
            .includes(deferredSearch),
    );
    const selected = new Set(form.data.asset_ids);
    const childCount = page.properties
        .filter((property) => selected.has(property.id))
        .reduce((total, property) => total + property.children_count, 0);

    const toggle = (id: number) => {
        form.setData(
            'asset_ids',
            selected.has(id)
                ? form.data.asset_ids.filter((assetId) => assetId !== id)
                : [...form.data.asset_ids, id],
        );
    };
    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.put(page.action, { preserveScroll: true });
    };

    return (
        <AdminLayout>
            <Head
                title={t('users.assignment_title', undefined, {
                    name: page.manager.name,
                })}
            />
            <WorkspaceHeader
                eyebrow={t('users.assignment_workspace')}
                title={t('users.assignment_title', undefined, {
                    name: page.manager.name,
                })}
                description={t('users.assignment_description', undefined, {
                    portfolio: page.manager.portfolio ?? '—',
                })}
                actions={[
                    {
                        label: t('users.back_to_user'),
                        href: page.back_href,
                        icon: 'bi-arrow-left',
                        tone: 'quiet',
                    },
                ]}
            />

            <form
                className="pmc-property-assignment-workspace"
                onSubmit={submit}
            >
                <PropertyAssignmentOverview
                    available={page.properties.length}
                    selected={form.data.asset_ids.length}
                    children={childCount}
                />
                <PropertyAssignmentToolbar
                    search={search}
                    onSearch={setSearch}
                    selected={form.data.asset_ids.length}
                    total={page.properties.length}
                />

                {properties.length > 0 ? (
                    <div className="pmc-property-assignment-grid">
                        {properties.map((property) => (
                            <PropertyAssignmentCard
                                key={property.id}
                                property={property}
                                checked={selected.has(property.id)}
                                onChange={() => toggle(property.id)}
                                labels={{
                                    assigned: t('users.assigned'),
                                    currentManager: t('users.current_manager'),
                                    unassigned: t('users.unassigned'),
                                    replacesManager: t(
                                        'users.replaces_current_manager',
                                    ),
                                    children: t('users.child_assets'),
                                }}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="pmc-property-assignment-empty">
                        <i className="bi bi-buildings" />
                        <strong>
                            {search
                                ? t('users.no_properties_match')
                                : t('users.no_assignable_properties')}
                        </strong>
                        <p>{t('users.no_assignable_properties_help')}</p>
                    </div>
                )}

                {form.errors.asset_ids ? (
                    <p className="pmc-property-assignment-error" role="alert">
                        {form.errors.asset_ids}
                    </p>
                ) : null}

                <div className="pmc-property-assignment-actions">
                    <Link
                        href={page.back_href}
                        className="btn btn-outline-secondary"
                    >
                        {t('users.cancel')}
                    </Link>
                    <button
                        type="submit"
                        className="btn btn-primary"
                        disabled={form.processing}
                    >
                        {form.processing
                            ? t('users.saving_assignments')
                            : t('users.save_assignments')}
                    </button>
                </div>
            </form>
        </AdminLayout>
    );
}
