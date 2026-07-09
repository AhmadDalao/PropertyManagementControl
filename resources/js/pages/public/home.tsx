import { Head, Link } from '@inertiajs/react';
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
                <FallbackHome locale={props.app.locale} />
            )}
        </PublicLayout>
    );
}

function FallbackHome({ locale }: { locale: 'en' | 'ar' }) {
    const copy = {
        en: {
            kicker: 'Property operations',
            headline:
                'Control properties, tenants, contracts, payments, and maintenance.',
            body: 'A bilingual management portal for owners, managers, tenants, and the system owner.',
            primary: 'Open portal',
            secondary: 'Go to dashboard',
            metrics: [
                ['Assets', 'Buildings, floors, units, and spaces'],
                [
                    'Finance',
                    'Revenue, dues, receipts, expenses, and net position',
                ],
                ['Service', 'Tenant maintenance requests with owner follow-up'],
            ],
        },
        ar: {
            kicker: 'تشغيل العقارات',
            headline: 'إدارة العقارات والمستأجرين والعقود والمدفوعات والصيانة.',
            body: 'بوابة ثنائية اللغة للمالك ومدير العقار والمستأجر ومالك النظام.',
            primary: 'فتح البوابة',
            secondary: 'لوحة التحكم',
            metrics: [
                ['الأصول', 'مبان وطوابق ووحدات ومساحات'],
                ['المالية', 'إيرادات ومستحقات وإيصالات ومصاريف وصافي المركز'],
                ['الخدمة', 'طلبات صيانة من المستأجر مع متابعة المالك'],
            ],
        },
    }[locale];

    return (
        <>
            <section className="pmc-hero p-4 p-lg-5 position-relative mb-4">
                <span className="pmc-hero-blob pmc-hero-blob--orange top-0 start-0 translate-middle p-5" />
                <span className="pmc-hero-blob pmc-hero-blob--teal bottom-0 end-0 translate-middle p-5" />
                <div className="row g-4 align-items-center position-relative">
                    <div className="col-lg-8">
                        <div className="pmc-kicker mb-3">{copy.kicker}</div>
                        <h1 className="display-5 fw-bold mb-3">
                            {copy.headline}
                        </h1>
                        <p className="lead text-secondary mb-4">{copy.body}</p>
                        <div className="d-flex gap-2 flex-wrap">
                            <Link
                                href="/login"
                                className="btn btn-primary btn-lg"
                            >
                                <i className="bi bi-box-arrow-in-right me-2" />
                                {copy.primary}
                            </Link>
                            <Link
                                href="/dashboard"
                                className="btn btn-outline-secondary btn-lg"
                            >
                                <i className="bi bi-speedometer2 me-2" />
                                {copy.secondary}
                            </Link>
                        </div>
                    </div>
                    <div className="col-lg-4">
                        <div className="pmc-public-signal p-4">
                            <div className="pmc-kicker mb-2">PMC</div>
                            <div className="h2 fw-bold mb-1">Live Portal</div>
                            <div className="text-secondary">
                                property.ahmaddalao.com
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="row g-3">
                {copy.metrics.map(([label, value]) => (
                    <div key={label} className="col-md-4">
                        <div className="pmc-card p-4 h-100">
                            <div className="pmc-kicker mb-2">{label}</div>
                            <div className="fw-semibold">{value}</div>
                        </div>
                    </div>
                ))}
            </section>
        </>
    );
}
