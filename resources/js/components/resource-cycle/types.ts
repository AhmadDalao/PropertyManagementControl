import type { ReactNode } from 'react';

export type ResourceAction = {
    label: string;
    href: string;
    method?: 'get' | 'post' | 'put' | 'delete';
    variant?: 'primary' | 'secondary' | 'danger' | 'light';
    confirm?: string;
    external?: boolean;
};

export type ResourceHeaderProps = {
    eyebrow?: string;
    title: string;
    description?: string;
    backHref?: string;
    backLabel?: string;
    actions?: ResourceAction[];
};

export type ResourceField = {
    name: string;
    label: string;
    type?:
        | 'text'
        | 'email'
        | 'password'
        | 'number'
        | 'date'
        | 'textarea'
        | 'select'
        | 'checkbox'
        | 'file'
        | 'hidden';
    options?: Array<{ label: string; value: string | number | boolean }>;
    required?: boolean;
    help?: string;
    placeholder?: string;
    rows?: number;
    step?: string;
    min?: string | number;
    max?: string | number;
    accept?: string;
    section?: string;
    sectionDescription?: string;
    reloadOnChange?: { queryKey: string };
};

export type DetailItem = {
    label: string;
    value?: ReactNode;
    href?: string | null;
    tone?: 'primary' | 'teal' | 'danger' | 'muted';
};

export type DetailSection = {
    title: string;
    description?: string;
    tab?: 'overview' | 'financial';
    items: DetailItem[];
};

export type DecisionCard = {
    title: string;
    value: ReactNode;
    detail?: ReactNode;
    href?: string;
    actionLabel?: string;
    tone?: 'primary' | 'teal' | 'danger' | 'muted';
    icon?: string;
};

export type ResourceSpotlight = {
    eyebrow?: string;
    title: string;
    subtitle?: string;
    description?: string;
    status?: string;
    items?: DetailItem[];
    actions?: ResourceAction[];
    image?: { src: string; alt: string };
};

export type RelatedCell = ReactNode | { label: string; href: string };

export type RelatedTable = {
    title: string;
    description?: string;
    columns: string[];
    rows: Array<Record<string, RelatedCell>>;
    emptyText?: string;
    actionHref?: string;
    actionLabel?: string;
};

export type ResourceDetailTab =
    'overview' | 'financial' | 'documents' | 'history' | 'related';

export type ResourceFormValue =
    string | number | boolean | File | null | undefined;

export type ResourceFormValues = Record<string, ResourceFormValue>;

export type ResourceDocument = {
    id: number;
    title: string;
    subtitle?: string;
    badge?: string;
    href: string;
};

export type ResourceTimelineEntry = {
    id: number;
    event: string;
    description?: string;
    causer?: string;
    created_at?: string;
};

export type ResourceFormShellProps = {
    title: string;
    description: string;
    backHref: string;
    backLabel: string;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
    fields: ResourceField[];
    initialValues: ResourceFormValues;
};

export type ResourceDetailShellProps = {
    header: ResourceHeaderProps;
    spotlight?: ResourceSpotlight;
    decisionCards?: DecisionCard[];
    stats?: DetailItem[];
    sections?: DetailSection[];
    related?: RelatedTable[];
    documents?: ResourceDocument[];
    timeline?: ResourceTimelineEntry[];
};
