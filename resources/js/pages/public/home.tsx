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
        id: -3,
        section: {
            section_type: 'role_cards',
            content_en: {
                eyebrow: 'Role-based portals',
                headline: 'Every user sees only what they should manage.',
                items: [
                    {
                        icon: 'bi-shield-lock',
                        title: 'Superadmin',
                        body: 'Controls the system, CMS, portfolios, users, values, and platform health.',
                    },
                    {
                        icon: 'bi-buildings',
                        title: 'Owner',
                        body: 'Manages assets, tenants, leases, payments, documents, and reports.',
                    },
                    {
                        icon: 'bi-person-workspace',
                        title: 'Property manager',
                        body: 'Runs daily operations across units, tenants, contracts, and maintenance.',
                    },
                    {
                        icon: 'bi-person-badge',
                        title: 'Tenant',
                        body: 'Views rent, days left, documents, payments, and service requests.',
                    },
                ],
            },
            content_ar: {
                eyebrow: 'بوابات حسب الدور',
                headline: 'كل مستخدم يرى ما يخصه فقط.',
                items: [
                    {
                        icon: 'bi-shield-lock',
                        title: 'مالك النظام',
                        body: 'يدير النظام والموقع والمحافظ والمستخدمين والقيم وحالة المنصة.',
                    },
                    {
                        icon: 'bi-buildings',
                        title: 'المالك',
                        body: 'يدير الأصول والمستأجرين والعقود والمدفوعات والمستندات والتقارير.',
                    },
                    {
                        icon: 'bi-person-workspace',
                        title: 'مدير العقار',
                        body: 'يتابع التشغيل اليومي للوحدات والمستأجرين والعقود والصيانة.',
                    },
                    {
                        icon: 'bi-person-badge',
                        title: 'المستأجر',
                        body: 'يعرض الإيجار والأيام المتبقية والمستندات والمدفوعات وطلبات الخدمة.',
                    },
                ],
            },
        },
    },
    {
        id: -4,
        section: {
            section_type: 'workflow',
            content_en: {
                eyebrow: 'Operating flow',
                headline:
                    'From portfolio setup to tenant service, every step stays connected.',
                steps: [
                    {
                        title: 'Create portfolio',
                        body: 'Start with the owner account and portfolio boundary.',
                    },
                    {
                        title: 'Add assets',
                        body: 'Build buildings, floors, units, spaces, values, and occupancy.',
                    },
                    {
                        title: 'Assign control',
                        body: 'Track who owns and who manages each asset.',
                    },
                    {
                        title: 'Create tenant',
                        body: 'Add profile, contacts, documents, and portal account.',
                    },
                    {
                        title: 'Generate lease',
                        body: 'Create contract, installments, deposit, and signed PDFs.',
                    },
                    {
                        title: 'Track operations',
                        body: 'Monitor balances, receipts, expenses, and maintenance.',
                    },
                ],
            },
            content_ar: {
                eyebrow: 'مسار التشغيل',
                headline: 'من إعداد المحفظة إلى خدمة المستأجر، كل خطوة مرتبطة.',
                steps: [
                    {
                        title: 'إنشاء محفظة',
                        body: 'ابدأ بحساب المالك وحدود المحفظة.',
                    },
                    {
                        title: 'إضافة الأصول',
                        body: 'أضف المباني والطوابق والوحدات والمساحات والقيم والإشغال.',
                    },
                    {
                        title: 'تعيين التحكم',
                        body: 'حدد من يملك ومن يدير كل أصل.',
                    },
                    {
                        title: 'إنشاء مستأجر',
                        body: 'أضف الملف وجهات الاتصال والمستندات وحساب البوابة.',
                    },
                    {
                        title: 'إنشاء العقد',
                        body: 'أنشئ العقد والأقساط والتأمين والملفات الموقعة.',
                    },
                    {
                        title: 'متابعة التشغيل',
                        body: 'راقب الأرصدة والإيصالات والمصاريف والصيانة.',
                    },
                ],
            },
        },
    },
    {
        id: -5,
        section: {
            section_type: 'dashboard_preview',
            content_en: {
                eyebrow: 'Dashboard visibility',
                headline: 'Know what is owned, rented, due, and unresolved.',
                body: 'The control center focuses on portfolio value, occupancy, lease health, rent collection, maintenance backlog, and documents.',
                metrics: [
                    {
                        label: 'Managed assets',
                        value: 'Buildings / floors / units',
                    },
                    {
                        label: 'Financial health',
                        value: 'Paid / remaining / overdue',
                    },
                    {
                        label: 'Service queue',
                        value: 'Electrical / plumbing / general',
                    },
                ],
            },
            content_ar: {
                eyebrow: 'وضوح لوحة التحكم',
                headline:
                    'اعرف ما تملكه وما تم تأجيره وما هو مستحق وما لم يتم حله.',
                body: 'يركز مركز التحكم على قيمة المحفظة والإشغال وحالة العقود والتحصيل وطلبات الصيانة والمستندات.',
                metrics: [
                    {
                        label: 'الأصول المُدارة',
                        value: 'مبان / طوابق / وحدات',
                    },
                    {
                        label: 'الحالة المالية',
                        value: 'مدفوع / متبقي / متأخر',
                    },
                    {
                        label: 'طلبات الخدمة',
                        value: 'كهرباء / سباكة / عام',
                    },
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
                        body: 'Generate contracts, track periods, signed PDFs, and days remaining.',
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
    {
        id: -6,
        section: {
            section_type: 'operations_strip',
            content_en: {
                headline: 'Payments and maintenance stay tied to the lease.',
                body: 'Receipts, contract balance, deposits, expenses, and service requests all roll into owner and superadmin reporting.',
                items: [
                    { label: 'Receipts', value: 'PDF-ready' },
                    { label: 'Arrears', value: 'By installment' },
                    { label: 'Requests', value: 'Tenant submitted' },
                ],
            },
            content_ar: {
                headline: 'المدفوعات والصيانة مرتبطة بالعقد.',
                body: 'الإيصالات ورصيد العقد والتأمين والمصاريف وطلبات الخدمة تظهر في تقارير المالك ومالك النظام.',
                items: [
                    { label: 'الإيصالات', value: 'جاهزة كملف PDF' },
                    { label: 'المتأخرات', value: 'حسب القسط' },
                    { label: 'الطلبات', value: 'من المستأجر' },
                ],
            },
        },
    },
    {
        id: -7,
        section: {
            section_type: 'faq',
            content_en: {
                eyebrow: 'FAQ',
                headline: 'Answers before the first login.',
                items: [
                    {
                        question: 'Does it support Arabic?',
                        answer: 'Yes. The public site and portal support English and Arabic, including RTL layout.',
                    },
                    {
                        question: 'Can tenants log in?',
                        answer: 'Yes. Owners or managers create tenant accounts so tenants can view rent, contracts, documents, and maintenance.',
                    },
                    {
                        question: 'Is payment gateway included?',
                        answer: 'Not in v1. Payments are manual and tracked through installments, receipts, and balances.',
                    },
                ],
            },
            content_ar: {
                eyebrow: 'الأسئلة الشائعة',
                headline: 'إجابات قبل أول تسجيل دخول.',
                items: [
                    {
                        question: 'هل يدعم العربية؟',
                        answer: 'نعم. الموقع والبوابة يدعمان العربية والإنجليزية مع اتجاه RTL.',
                    },
                    {
                        question: 'هل يمكن للمستأجر الدخول؟',
                        answer: 'نعم. ينشئ المالك أو المدير حساب المستأجر لعرض الإيجار والعقود والمستندات والصيانة.',
                    },
                    {
                        question: 'هل توجد بوابة دفع؟',
                        answer: 'ليس في الإصدار الأول. المدفوعات يدوية وتُتابع من خلال الأقساط والإيصالات والأرصدة.',
                    },
                ],
            },
        },
    },
    {
        id: -8,
        section: {
            section_type: 'final_cta',
            content_en: {
                headline:
                    'Start with one portfolio. Grow into the full operation.',
                body: 'Create the owner, add assets, attach tenants, generate leases, and let the reporting tell the truth.',
                ctaPrimary: 'Open Portal',
            },
            content_ar: {
                headline: 'ابدأ بمحفظة واحدة ثم وسّع التشغيل بالكامل.',
                body: 'أنشئ المالك، أضف الأصول، اربط المستأجرين، أنشئ العقود، ودع التقارير تعرض الحقيقة.',
                ctaPrimary: 'فتح البوابة',
            },
        },
    },
];
