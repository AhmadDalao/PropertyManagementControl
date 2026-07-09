import { Head } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';

import { CmsRenderer } from '@/components/cms-renderer';
import { PublicLayout } from '@/layouts/public-layout';
import type { SharedProps } from '@/types';

type PageProps = SharedProps & {
    page?: {
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
    } | null;
};

export default function HomePage() {
    const { props } = usePage<PageProps>();
    const title =
        props.app.locale === 'ar' ? props.page?.title_ar : props.page?.title_en;
    const sections = props.page?.page_sections ?? [];

    return (
        <PublicLayout>
            <Head title={title ?? 'Home'} />

            {sections.length > 0 ? (
                <CmsRenderer sections={sections} locale={props.app.locale} />
            ) : (
                <CmsRenderer
                    sections={fallbackSections}
                    locale={props.app.locale}
                />
            )}
        </PublicLayout>
    );
}

const fallbackSections = [
    {
        id: -1,
        section: {
            section_type: 'hero',
            content_en: {
                eyebrow: 'Property operations',
                headline:
                    'Run your property portfolio from one control center.',
                subheadline:
                    'Track buildings, units, owners, tenants, contracts, payments, maintenance, documents, and reports in one bilingual portal.',
                ctaPrimary: 'Open Portal',
                ctaSecondary: 'Explore Features',
                stats: [
                    { label: 'Asset tree', value: 'Property to unit' },
                    { label: 'Languages', value: 'English + Arabic' },
                    { label: 'Payments', value: 'Manual tracking' },
                ],
                preview: [
                    { label: 'Occupancy', value: 'Live' },
                    { label: 'Collections', value: 'Due vs paid' },
                    { label: 'Maintenance', value: 'Open queue' },
                ],
            },
            content_ar: {
                eyebrow: 'تشغيل العقارات',
                headline: 'أدر محفظتك العقارية من مركز تحكم واحد.',
                subheadline:
                    'تابع المباني والوحدات والملاك والمستأجرين والعقود والمدفوعات والصيانة والمستندات والتقارير من بوابة ثنائية اللغة.',
                ctaPrimary: 'فتح البوابة',
                ctaSecondary: 'استعراض المزايا',
                stats: [
                    { label: 'هيكل الأصول', value: 'من العقار إلى الوحدة' },
                    { label: 'اللغات', value: 'العربية + الإنجليزية' },
                    { label: 'المدفوعات', value: 'متابعة يدوية' },
                ],
                preview: [
                    { label: 'الإشغال', value: 'مباشر' },
                    { label: 'التحصيل', value: 'مستحق ومدفوع' },
                    { label: 'الصيانة', value: 'طلبات مفتوحة' },
                ],
            },
        },
    },
    {
        id: -2,
        section: {
            section_type: 'feature_grid',
            content_en: {
                eyebrow: 'Core modules',
                headline: 'Built around the actual property lifecycle.',
                items: [
                    {
                        icon: 'bi-diagram-3',
                        title: 'Asset control',
                        body: 'Model property, building, floor, unit, and space relationships.',
                    },
                    {
                        icon: 'bi-file-earmark-text',
                        title: 'Lease lifecycle',
                        body: 'Generate contracts, track periods, signed files, and days remaining.',
                    },
                    {
                        icon: 'bi-person-badge',
                        title: 'Tenant portal',
                        body: 'Give tenants rent summaries, contract downloads, and maintenance intake.',
                    },
                    {
                        icon: 'bi-cash-stack',
                        title: 'Payment tracking',
                        body: 'Post manual payments, allocate installments, and monitor balances.',
                    },
                ],
            },
            content_ar: {
                eyebrow: 'الوحدات الأساسية',
                headline: 'مصمم حول دورة حياة العقار الفعلية.',
                items: [
                    {
                        icon: 'bi-diagram-3',
                        title: 'التحكم بالأصول',
                        body: 'نمذج علاقة العقار والمبنى والطابق والوحدة والمساحة.',
                    },
                    {
                        icon: 'bi-file-earmark-text',
                        title: 'دورة العقد',
                        body: 'أنشئ العقود وتابع المدة والملفات الموقعة والأيام المتبقية.',
                    },
                    {
                        icon: 'bi-person-badge',
                        title: 'بوابة المستأجر',
                        body: 'اعرض للمستأجر المدفوعات والعقود وطلبات الصيانة.',
                    },
                    {
                        icon: 'bi-cash-stack',
                        title: 'متابعة المدفوعات',
                        body: 'سجل الدفعات اليدوية ووزع الأقساط وراقب الأرصدة.',
                    },
                ],
            },
        },
    },
];
