import type { FormDataConvertible } from '@inertiajs/core';

export type ContentField = {
    key: string;
    label: string;
    type?: 'text' | 'textarea';
};

export type ContentCollection = {
    key: string;
    label: string;
    itemLabel: string;
    fields: ContentField[];
};

export type SectionContentSchema = {
    description: string;
    fields: ContentField[];
    collections: ContentCollection[];
};

export function sectionContentSchema(
    sectionType: string,
): SectionContentSchema {
    const heading: ContentField[] = [
        { key: 'eyebrow', label: 'Eyebrow' },
        { key: 'headline', label: 'Headline', type: 'textarea' },
    ];
    const body: ContentField = {
        key: 'body',
        label: 'Body',
        type: 'textarea',
    };

    switch (sectionType) {
        case 'hero':
            return {
                description:
                    'The first public message, portal actions, summary chips, and dashboard preview.',
                fields: [
                    { key: 'eyebrow', label: 'Eyebrow' },
                    {
                        key: 'headline',
                        label: 'Headline',
                        type: 'textarea',
                    },
                    {
                        key: 'subheadline',
                        label: 'Subheadline',
                        type: 'textarea',
                    },
                    { key: 'ctaPrimary', label: 'Primary action' },
                    { key: 'ctaSecondary', label: 'Secondary action' },
                ],
                collections: [
                    labelValueCollection('stats', 'Stat chips', 'Stat'),
                    labelValueCollection(
                        'preview',
                        'Dashboard preview',
                        'Tile',
                    ),
                ],
            };
        case 'role_cards':
        case 'feature_grid':
            return {
                description:
                    'A section heading followed by repeatable icon, title, and description cards.',
                fields: heading,
                collections: [
                    {
                        key: 'items',
                        label: 'Cards',
                        itemLabel: 'Card',
                        fields: [
                            { key: 'icon', label: 'Bootstrap icon' },
                            { key: 'title', label: 'Title' },
                            {
                                key: 'body',
                                label: 'Description',
                                type: 'textarea',
                            },
                        ],
                    },
                ],
            };
        case 'workflow':
            return {
                description:
                    'An ordered explanation of how owners and managers move through the system.',
                fields: heading,
                collections: [
                    {
                        key: 'steps',
                        label: 'Workflow steps',
                        itemLabel: 'Step',
                        fields: [
                            { key: 'title', label: 'Title' },
                            {
                                key: 'body',
                                label: 'Description',
                                type: 'textarea',
                            },
                        ],
                    },
                ],
            };
        case 'dashboard_preview':
            return {
                description:
                    'A dashboard story with a short explanation and visible operating metrics.',
                fields: [...heading, body],
                collections: [
                    labelValueCollection('metrics', 'Metrics', 'Metric'),
                ],
            };
        case 'operations_strip':
            return {
                description:
                    'A compact operational statement with label and value pairs.',
                fields: [
                    {
                        key: 'headline',
                        label: 'Headline',
                        type: 'textarea',
                    },
                    body,
                ],
                collections: [labelValueCollection('items', 'Items', 'Item')],
            };
        case 'faq':
            return {
                description:
                    'A bilingual list of practical questions and answers.',
                fields: heading,
                collections: [
                    {
                        key: 'items',
                        label: 'Questions',
                        itemLabel: 'Question',
                        fields: [
                            { key: 'question', label: 'Question' },
                            {
                                key: 'answer',
                                label: 'Answer',
                                type: 'textarea',
                            },
                        ],
                    },
                ],
            };
        case 'final_cta':
            return {
                description:
                    'The final call to action shown before the public footer.',
                fields: [
                    {
                        key: 'headline',
                        label: 'Headline',
                        type: 'textarea',
                    },
                    body,
                    { key: 'ctaPrimary', label: 'Action label' },
                ],
                collections: [],
            };
        case 'metrics':
            return {
                description: 'Simple label and value metric cards.',
                fields: [],
                collections: [
                    labelValueCollection('items', 'Metrics', 'Metric'),
                ],
            };
        default:
            return {
                description:
                    'A simple bilingual content section. Advanced JSON remains available for custom fields.',
                fields: [...heading, body],
                collections: [],
            };
    }
}

export function defaultSectionContent(
    sectionType: string,
    language: 'en' | 'ar',
): Record<string, unknown> {
    const ar = language === 'ar';

    switch (sectionType) {
        case 'hero':
            return {
                eyebrow: ar ? 'تشغيل العقارات' : 'Property operations',
                headline: ar
                    ? 'أدر محفظتك العقارية من مركز تحكم واحد.'
                    : 'Run your property portfolio from one control center.',
                subheadline: ar
                    ? 'تابع المباني والوحدات والمستأجرين والعقود والمدفوعات والصيانة من بوابة واحدة.'
                    : 'Track buildings, units, tenants, contracts, payments, and maintenance from one portal.',
                ctaPrimary: ar ? 'فتح البوابة' : 'Open Portal',
                ctaSecondary: ar ? 'استعراض المزايا' : 'Explore Features',
                stats: [
                    {
                        label: ar ? 'الأصول' : 'Assets',
                        value: ar ? 'مبان ووحدات' : 'Buildings and units',
                    },
                    {
                        label: ar ? 'العقود' : 'Contracts',
                        value: ar ? 'مدفوعة ومتأخرة' : 'Paid and due',
                    },
                    {
                        label: ar ? 'الصيانة' : 'Maintenance',
                        value: ar ? 'طلبات مباشرة' : 'Live requests',
                    },
                ],
                preview: [
                    {
                        label: ar ? 'الإشغال' : 'Occupancy',
                        value: ar ? 'مباشر' : 'Live',
                    },
                    {
                        label: ar ? 'التحصيل' : 'Collections',
                        value: ar ? 'متابعة' : 'Tracked',
                    },
                ],
            };
        case 'role_cards':
            return {
                eyebrow: ar ? 'بوابات حسب الدور' : 'Role-based portals',
                headline: ar
                    ? 'كل مستخدم يرى ما يخصه فقط.'
                    : 'Every user sees only what they should manage.',
                items: [
                    {
                        icon: 'bi-buildings',
                        title: ar ? 'المالك' : 'Owner',
                        body: ar
                            ? 'يتابع الأصول والمستأجرين والعقود.'
                            : 'Runs assets, tenants, leases, and payments.',
                    },
                    {
                        icon: 'bi-person-badge',
                        title: ar ? 'مدير العقار' : 'Property manager',
                        body: ar
                            ? 'يدير التشغيل اليومي والصيانة.'
                            : 'Runs daily operations and service.',
                    },
                ],
            };
        case 'workflow':
            return {
                eyebrow: ar ? 'مسار التشغيل' : 'Operating flow',
                headline: ar
                    ? 'كل خطوة مرتبطة بما بعدها.'
                    : 'Every step connects to the next one.',
                steps: [
                    {
                        title: ar ? 'إنشاء محفظة' : 'Create portfolio',
                        body: ar
                            ? 'ابدأ بحدود المالك.'
                            : 'Start with the owner boundary.',
                    },
                    {
                        title: ar ? 'إضافة الأصول' : 'Add assets',
                        body: ar
                            ? 'أضف المباني والطوابق والوحدات.'
                            : 'Add buildings, floors, and units.',
                    },
                    {
                        title: ar ? 'إنشاء العقد' : 'Create lease',
                        body: ar
                            ? 'اربط المستأجر بالأصل.'
                            : 'Connect the tenant to the asset.',
                    },
                ],
            };
        case 'dashboard_preview':
            return {
                eyebrow: ar ? 'وضوح لوحة التحكم' : 'Dashboard visibility',
                headline: ar
                    ? 'اعرف ما هو مستحق وما لم يتم حله.'
                    : 'Know what is due and unresolved.',
                body: ar
                    ? 'راقب قيمة المحفظة والإشغال والتحصيل والصيانة.'
                    : 'Monitor portfolio value, occupancy, collections, and service.',
                metrics: [
                    {
                        label: ar ? 'الأصول' : 'Assets',
                        value: ar ? 'مُدارة' : 'Managed',
                    },
                    {
                        label: ar ? 'المدفوعات' : 'Payments',
                        value: ar ? 'متابعة' : 'Tracked',
                    },
                ],
            };
        case 'feature_grid':
            return {
                eyebrow: ar ? 'المزايا' : 'Features',
                headline: ar
                    ? 'مصمم حول دورة العقار الحقيقية.'
                    : 'Built around the real property cycle.',
                items: [
                    {
                        icon: 'bi-diagram-3',
                        title: ar ? 'إدارة الأصول' : 'Asset control',
                        body: ar
                            ? 'مبان وطوابق ووحدات.'
                            : 'Buildings, floors, and units.',
                    },
                    {
                        icon: 'bi-cash-stack',
                        title: ar ? 'المدفوعات' : 'Payment tracking',
                        body: ar
                            ? 'مدفوع ومتبقي ومتأخر.'
                            : 'Paid, remaining, and overdue.',
                    },
                ],
            };
        case 'operations_strip':
            return {
                headline: ar
                    ? 'تابع التشغيل اليومي بدون فوضى.'
                    : 'Track daily operations without the mess.',
                body: ar
                    ? 'العقود والمدفوعات والصيانة والتقارير في دورة واحدة.'
                    : 'Leases, payments, maintenance, and reports in one cycle.',
                items: [
                    {
                        label: ar ? 'العقود' : 'Leases',
                        value: ar ? 'نشطة' : 'Active',
                    },
                    {
                        label: ar ? 'الصيانة' : 'Maintenance',
                        value: ar ? 'مفتوحة' : 'Open',
                    },
                ],
            };
        case 'faq':
            return {
                eyebrow: ar ? 'الأسئلة' : 'FAQ',
                headline: ar
                    ? 'أسئلة شائعة قبل البدء.'
                    : 'Questions before you start.',
                items: [
                    {
                        question: ar
                            ? 'هل يدعم العربية؟'
                            : 'Does it support Arabic?',
                        answer: ar
                            ? 'نعم، الواجهة والموقع يدعمان العربية والإنجليزية.'
                            : 'Yes. The admin and public website support English and Arabic.',
                    },
                ],
            };
        case 'final_cta':
            return {
                headline: ar ? 'ابدأ من البوابة.' : 'Start from the portal.',
                body: ar
                    ? 'سجل الدخول لإدارة الأصول والعقود والمدفوعات.'
                    : 'Sign in to manage assets, leases, and payments.',
                ctaPrimary: ar ? 'فتح البوابة' : 'Open Portal',
            };
        case 'metrics':
            return {
                items: [
                    {
                        label: ar ? 'الأصول' : 'Assets',
                        value: ar ? 'متابعة' : 'Tracked',
                    },
                    {
                        label: ar ? 'العقود' : 'Leases',
                        value: ar ? 'نشطة' : 'Active',
                    },
                ],
            };
        default:
            return {
                eyebrow: ar ? 'قسم قابل للتعديل' : 'Editable section',
                headline: ar ? 'عنوان القسم' : 'Section headline',
                body: ar
                    ? 'اكتب محتوى القسم هنا.'
                    : 'Write the section content here.',
            };
    }
}

export function parseJsonObject(
    value: string,
): Record<string, FormDataConvertible> | null {
    try {
        const parsed = JSON.parse(value || '{}') as unknown;

        return isPlainObject(parsed)
            ? (parsed as Record<string, FormDataConvertible>)
            : null;
    } catch {
        return null;
    }
}

export function safeJsonObject(value: string): Record<string, unknown> {
    try {
        const parsed = JSON.parse(value || '{}') as unknown;

        return isPlainObject(parsed) ? parsed : {};
    } catch {
        return {};
    }
}

export function isPlainObject(
    value: unknown,
): value is Record<string, unknown> {
    return value !== null && !Array.isArray(value) && typeof value === 'object';
}

export function readableSectionType(value: string) {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function jsonText(value: Record<string, unknown>) {
    return JSON.stringify(value, null, 2);
}

export function stringValue(value: unknown) {
    return typeof value === 'string' ? value : '';
}

function labelValueCollection(
    key: string,
    label: string,
    itemLabel: string,
): ContentCollection {
    return {
        key,
        label,
        itemLabel,
        fields: [
            { key: 'label', label: 'Label' },
            { key: 'value', label: 'Value' },
        ],
    };
}
