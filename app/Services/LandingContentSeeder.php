<?php

namespace App\Services;

use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\NavigationItem;
use Illuminate\Support\Facades\DB;

class LandingContentSeeder
{
    /**
     * Seed editable public landing content without touching operational data.
     *
     * @return array{page_id: int, sections: int, navigation_items: int}
     */
    public function seed(): array
    {
        return DB::transaction(function (): array {
            $page = CmsPage::query()->updateOrCreate(
                ['slug' => 'home'],
                [
                    'title_en' => 'Property Management Control',
                    'title_ar' => 'نظام إدارة العقارات',
                    'excerpt_en' => 'Run properties, leases, tenants, payments, maintenance, and documents from one bilingual control center.',
                    'excerpt_ar' => 'أدر العقارات والعقود والمستأجرين والمدفوعات والصيانة والمستندات من مركز تحكم ثنائي اللغة.',
                    'seo_title_en' => 'Property Management Control',
                    'seo_title_ar' => 'نظام إدارة العقارات',
                    'seo_description_en' => 'Bilingual property operations software for owners, managers, tenants, contracts, payments, maintenance, and reports.',
                    'seo_description_ar' => 'نظام ثنائي اللغة لإدارة العقارات والملاك والمديرين والمستأجرين والعقود والمدفوعات والصيانة والتقارير.',
                    'status' => 'published',
                    'is_homepage' => true,
                    'is_visible' => true,
                    'published_at' => now(),
                ],
            );

            CmsPage::query()
                ->where('id', '!=', $page->id)
                ->where('is_homepage', true)
                ->update(['is_homepage' => false]);

            foreach ($this->sections() as $index => $definition) {
                $section = CmsSection::query()->updateOrCreate(
                    [
                        'section_type' => $definition['section_type'],
                        'name_en' => $definition['name_en'],
                    ],
                    [
                        'name_ar' => $definition['name_ar'],
                        'content_en' => $definition['content_en'],
                        'content_ar' => $definition['content_ar'],
                        'settings_json' => ['seed_key' => $definition['seed_key']],
                        'status' => 'active',
                    ],
                );

                $page->pageSections()->updateOrCreate(
                    ['cms_section_id' => $section->id],
                    [
                        'sort_order' => $index + 1,
                        'is_visible' => true,
                        'settings_json' => ['seed_key' => $definition['seed_key']],
                    ],
                );
            }

            foreach ($this->navigationItems($page) as $index => $item) {
                NavigationItem::query()->updateOrCreate(
                    [
                        'parent_id' => null,
                        'location' => 'header',
                        'url' => $item['url'],
                    ],
                    [
                        'cms_page_id' => $item['cms_page_id'],
                        'title_en' => $item['title_en'],
                        'title_ar' => $item['title_ar'],
                        'target' => '_self',
                        'sort_order' => $index + 1,
                        'is_visible' => true,
                    ],
                );
            }

            return [
                'page_id' => $page->id,
                'sections' => count($this->sections()),
                'navigation_items' => count($this->navigationItems($page)),
            ];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sections(): array
    {
        return [
            [
                'seed_key' => 'landing.hero',
                'section_type' => 'hero',
                'name_en' => 'Landing hero',
                'name_ar' => 'واجهة الصفحة الرئيسية',
                'content_en' => [
                    'eyebrow' => 'Property operations',
                    'headline' => 'Run your property portfolio from one control center.',
                    'subheadline' => 'Track buildings, units, owners, tenants, contracts, payments, maintenance, documents, and reports in one bilingual portal.',
                    'ctaPrimary' => 'Open Portal',
                    'ctaSecondary' => 'Explore Features',
                    'stats' => [
                        ['label' => 'Asset tree', 'value' => 'Property to unit'],
                        ['label' => 'Languages', 'value' => 'English + Arabic'],
                        ['label' => 'Payments', 'value' => 'Manual tracking'],
                    ],
                    'preview' => [
                        ['label' => 'Occupancy', 'value' => 'Live'],
                        ['label' => 'Collections', 'value' => 'Due vs paid'],
                        ['label' => 'Maintenance', 'value' => 'Open queue'],
                    ],
                ],
                'content_ar' => [
                    'eyebrow' => 'تشغيل العقارات',
                    'headline' => 'أدر محفظتك العقارية من مركز تحكم واحد.',
                    'subheadline' => 'تابع المباني والوحدات والملاك والمستأجرين والعقود والمدفوعات والصيانة والمستندات والتقارير من بوابة ثنائية اللغة.',
                    'ctaPrimary' => 'فتح البوابة',
                    'ctaSecondary' => 'استعراض المزايا',
                    'stats' => [
                        ['label' => 'هيكل الأصول', 'value' => 'من العقار إلى الوحدة'],
                        ['label' => 'اللغات', 'value' => 'العربية + الإنجليزية'],
                        ['label' => 'المدفوعات', 'value' => 'متابعة يدوية'],
                    ],
                    'preview' => [
                        ['label' => 'الإشغال', 'value' => 'مباشر'],
                        ['label' => 'التحصيل', 'value' => 'مستحق ومدفوع'],
                        ['label' => 'الصيانة', 'value' => 'طلبات مفتوحة'],
                    ],
                ],
            ],
            [
                'seed_key' => 'landing.roles',
                'section_type' => 'role_cards',
                'name_en' => 'Portal roles',
                'name_ar' => 'أدوار البوابة',
                'content_en' => [
                    'eyebrow' => 'Role-based portals',
                    'headline' => 'Every user sees only what they should manage.',
                    'items' => [
                        ['icon' => 'bi-shield-lock', 'title' => 'Superadmin', 'body' => 'Owns the system, CMS, global users, portfolios, valuations, and platform health.'],
                        ['icon' => 'bi-buildings', 'title' => 'Owner', 'body' => 'Controls portfolio assets, tenants, leases, payments, documents, and reports.'],
                        ['icon' => 'bi-person-workspace', 'title' => 'Property manager', 'body' => 'Runs day-to-day operations across assigned buildings, units, tenants, and maintenance.'],
                        ['icon' => 'bi-person-badge', 'title' => 'Tenant', 'body' => 'Views contract status, payments, documents, and submits maintenance requests.'],
                    ],
                ],
                'content_ar' => [
                    'eyebrow' => 'بوابات حسب الدور',
                    'headline' => 'كل مستخدم يرى ما يخصه فقط.',
                    'items' => [
                        ['icon' => 'bi-shield-lock', 'title' => 'مالك النظام', 'body' => 'يدير النظام والموقع والمستخدمين والمحافظ والقيم وحالة المنصة.'],
                        ['icon' => 'bi-buildings', 'title' => 'المالك', 'body' => 'يتحكم في الأصول والمستأجرين والعقود والمدفوعات والمستندات والتقارير.'],
                        ['icon' => 'bi-person-workspace', 'title' => 'مدير العقار', 'body' => 'يتابع التشغيل اليومي للمباني والوحدات والمستأجرين والصيانة.'],
                        ['icon' => 'bi-person-badge', 'title' => 'المستأجر', 'body' => 'يعرض العقد والمدفوعات والمستندات ويرسل طلبات الصيانة.'],
                    ],
                ],
            ],
            [
                'seed_key' => 'landing.workflow',
                'section_type' => 'workflow',
                'name_en' => 'Operations workflow',
                'name_ar' => 'مسار التشغيل',
                'content_en' => [
                    'eyebrow' => 'Operating flow',
                    'headline' => 'From portfolio setup to tenant service, the workflow stays connected.',
                    'steps' => [
                        ['title' => 'Create portfolio', 'body' => 'Start with the owner account and portfolio boundary.'],
                        ['title' => 'Add buildings, floors, units', 'body' => 'Build the asset tree with values, usage, and occupancy.'],
                        ['title' => 'Assign owner and manager', 'body' => 'Track who owns and who manages each asset.'],
                        ['title' => 'Create tenant', 'body' => 'Add tenant profile, contacts, documents, and portal account.'],
                        ['title' => 'Generate lease', 'body' => 'Create the contract, installments, deposits, and signed PDFs.'],
                        ['title' => 'Track payments and maintenance', 'body' => 'Monitor due balances, receipts, expenses, and service requests.'],
                    ],
                ],
                'content_ar' => [
                    'eyebrow' => 'مسار التشغيل',
                    'headline' => 'من إعداد المحفظة إلى خدمة المستأجر، كل خطوة مرتبطة.',
                    'steps' => [
                        ['title' => 'إنشاء محفظة', 'body' => 'ابدأ بحساب المالك وحدود المحفظة.'],
                        ['title' => 'إضافة المباني والطوابق والوحدات', 'body' => 'ابن هيكل الأصول مع القيم والاستخدام والإشغال.'],
                        ['title' => 'تعيين المالك والمدير', 'body' => 'حدد من يملك ومن يدير كل أصل.'],
                        ['title' => 'إنشاء مستأجر', 'body' => 'أضف الملف وجهات الاتصال والمستندات وحساب البوابة.'],
                        ['title' => 'إنشاء العقد', 'body' => 'أنشئ العقد والأقساط والتأمين والمستندات الموقعة.'],
                        ['title' => 'متابعة المدفوعات والصيانة', 'body' => 'راقب المستحقات والإيصالات والمصاريف وطلبات الخدمة.'],
                    ],
                ],
            ],
            [
                'seed_key' => 'landing.dashboard_preview',
                'section_type' => 'dashboard_preview',
                'name_en' => 'Dashboard preview',
                'name_ar' => 'معاينة لوحة التحكم',
                'content_en' => [
                    'eyebrow' => 'Dashboard visibility',
                    'headline' => 'Know what is owned, rented, due, and unresolved.',
                    'body' => 'The control center focuses on portfolio value, occupancy, lease health, rent collection, maintenance backlog, and documents.',
                    'metrics' => [
                        ['label' => 'Managed assets', 'value' => 'Buildings / floors / units'],
                        ['label' => 'Financial health', 'value' => 'Paid / remaining / overdue'],
                        ['label' => 'Service queue', 'value' => 'Electrical / plumbing / general'],
                    ],
                ],
                'content_ar' => [
                    'eyebrow' => 'وضوح لوحة التحكم',
                    'headline' => 'اعرف ما تملكه وما تم تأجيره وما هو مستحق وما لم يتم حله.',
                    'body' => 'يركز مركز التحكم على قيمة المحفظة والإشغال وحالة العقود والتحصيل وطلبات الصيانة والمستندات.',
                    'metrics' => [
                        ['label' => 'الأصول المُدارة', 'value' => 'مبان / طوابق / وحدات'],
                        ['label' => 'الحالة المالية', 'value' => 'مدفوع / متبقي / متأخر'],
                        ['label' => 'طلبات الخدمة', 'value' => 'كهرباء / سباكة / عام'],
                    ],
                ],
            ],
            [
                'seed_key' => 'landing.features',
                'section_type' => 'feature_grid',
                'name_en' => 'Feature grid',
                'name_ar' => 'شبكة المزايا',
                'content_en' => [
                    'eyebrow' => 'Core modules',
                    'headline' => 'Built around the actual property lifecycle.',
                    'items' => [
                        ['icon' => 'bi-diagram-3', 'title' => 'Asset control', 'body' => 'Model property, building, floor, unit, and space relationships.'],
                        ['icon' => 'bi-file-earmark-text', 'title' => 'Lease lifecycle', 'body' => 'Generate contracts, track periods, signed PDFs, and days remaining.'],
                        ['icon' => 'bi-person-badge', 'title' => 'Tenant portal', 'body' => 'Give tenants rent summaries, contract downloads, and maintenance intake.'],
                        ['icon' => 'bi-cash-stack', 'title' => 'Payment tracking', 'body' => 'Post manual payments, allocate installments, and monitor balances.'],
                        ['icon' => 'bi-tools', 'title' => 'Maintenance requests', 'body' => 'Route electrical, plumbing, and general issues to owners or managers.'],
                        ['icon' => 'bi-graph-up-arrow', 'title' => 'Owner reports', 'body' => 'Review occupancy, revenue, expenses, arrears, and net position.'],
                    ],
                ],
                'content_ar' => [
                    'eyebrow' => 'الوحدات الأساسية',
                    'headline' => 'مصمم حول دورة حياة العقار الفعلية.',
                    'items' => [
                        ['icon' => 'bi-diagram-3', 'title' => 'التحكم بالأصول', 'body' => 'نمذج علاقة العقار والمبنى والطابق والوحدة والمساحة.'],
                        ['icon' => 'bi-file-earmark-text', 'title' => 'دورة العقد', 'body' => 'أنشئ العقود وتابع المدة والملفات الموقعة والأيام المتبقية.'],
                        ['icon' => 'bi-person-badge', 'title' => 'بوابة المستأجر', 'body' => 'اعرض للمستأجر المدفوعات والعقود وطلبات الصيانة.'],
                        ['icon' => 'bi-cash-stack', 'title' => 'متابعة المدفوعات', 'body' => 'سجل الدفعات اليدوية ووزع الأقساط وراقب الأرصدة.'],
                        ['icon' => 'bi-tools', 'title' => 'طلبات الصيانة', 'body' => 'وجه أعطال الكهرباء والسباكة والطلبات العامة للمالك أو المدير.'],
                        ['icon' => 'bi-graph-up-arrow', 'title' => 'تقارير المالك', 'body' => 'راجع الإشغال والإيرادات والمصاريف والمتأخرات وصافي المركز.'],
                    ],
                ],
            ],
            [
                'seed_key' => 'landing.operations_strip',
                'section_type' => 'operations_strip',
                'name_en' => 'Payments and maintenance',
                'name_ar' => 'المدفوعات والصيانة',
                'content_en' => [
                    'headline' => 'Payments and maintenance stay tied to the lease.',
                    'body' => 'Receipts, contract balance, deposits, expenses, and service requests all roll into owner and superadmin reporting.',
                    'items' => [
                        ['label' => 'Receipts', 'value' => 'PDF-ready'],
                        ['label' => 'Arrears', 'value' => 'Tracked by installment'],
                        ['label' => 'Requests', 'value' => 'Tenant submitted'],
                    ],
                ],
                'content_ar' => [
                    'headline' => 'المدفوعات والصيانة مرتبطة بالعقد.',
                    'body' => 'الإيصالات ورصيد العقد والتأمين والمصاريف وطلبات الخدمة تظهر في تقارير المالك ومالك النظام.',
                    'items' => [
                        ['label' => 'الإيصالات', 'value' => 'جاهزة كملف PDF'],
                        ['label' => 'المتأخرات', 'value' => 'حسب القسط'],
                        ['label' => 'الطلبات', 'value' => 'من المستأجر'],
                    ],
                ],
            ],
            [
                'seed_key' => 'landing.faq',
                'section_type' => 'faq',
                'name_en' => 'Landing FAQ',
                'name_ar' => 'أسئلة الصفحة',
                'content_en' => [
                    'eyebrow' => 'FAQ',
                    'headline' => 'Answers before the first login.',
                    'items' => [
                        ['question' => 'Does it support Arabic?', 'answer' => 'Yes. The public site and portal support English and Arabic, including RTL layout.'],
                        ['question' => 'Can tenants log in?', 'answer' => 'Yes. Owners or managers create tenant accounts so tenants can view rent, contract status, documents, and maintenance requests.'],
                        ['question' => 'Are contracts and receipts supported?', 'answer' => 'Yes. The system tracks generated contracts, signed uploads, receipts, and statements as documents.'],
                        ['question' => 'Is there an online payment gateway?', 'answer' => 'Not in v1. Payments are manual and tracked through installments, receipts, and balances.'],
                        ['question' => 'Will this run on Hostinger shared hosting?', 'answer' => 'Yes. The app is designed for Laravel on Hostinger with MySQL, public build assets, storage sync, and cron-friendly queues.'],
                    ],
                ],
                'content_ar' => [
                    'eyebrow' => 'الأسئلة الشائعة',
                    'headline' => 'إجابات قبل أول تسجيل دخول.',
                    'items' => [
                        ['question' => 'هل يدعم العربية؟', 'answer' => 'نعم. الموقع والبوابة يدعمان العربية والإنجليزية مع اتجاه RTL.'],
                        ['question' => 'هل يمكن للمستأجر الدخول؟', 'answer' => 'نعم. ينشئ المالك أو المدير حساب المستأجر لعرض الإيجار وحالة العقد والمستندات وطلبات الصيانة.'],
                        ['question' => 'هل يدعم العقود والإيصالات؟', 'answer' => 'نعم. يتابع النظام العقود والملفات الموقعة والإيصالات وكشوف الحساب كمستندات.'],
                        ['question' => 'هل يوجد بوابة دفع إلكترونية؟', 'answer' => 'ليس في الإصدار الأول. المدفوعات يدوية وتُتابع من خلال الأقساط والإيصالات والأرصدة.'],
                        ['question' => 'هل يعمل على استضافة Hostinger المشتركة؟', 'answer' => 'نعم. التطبيق مصمم للارافيل على Hostinger مع MySQL وملفات البناء وتزامن التخزين والمهام المجدولة.'],
                    ],
                ],
            ],
            [
                'seed_key' => 'landing.final_cta',
                'section_type' => 'final_cta',
                'name_en' => 'Final portal CTA',
                'name_ar' => 'دعوة الدخول النهائية',
                'content_en' => [
                    'headline' => 'Start with one portfolio. Grow into the full operation.',
                    'body' => 'Create the owner, add assets, attach tenants, generate leases, and let the reporting tell the truth.',
                    'ctaPrimary' => 'Open Portal',
                ],
                'content_ar' => [
                    'headline' => 'ابدأ بمحفظة واحدة ثم وسّع التشغيل بالكامل.',
                    'body' => 'أنشئ المالك، أضف الأصول، اربط المستأجرين، أنشئ العقود، ودع التقارير تعرض الحقيقة.',
                    'ctaPrimary' => 'فتح البوابة',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function navigationItems(CmsPage $homePage): array
    {
        return [
            ['cms_page_id' => $homePage->id, 'title_en' => 'Home', 'title_ar' => 'الرئيسية', 'url' => '/'],
            ['cms_page_id' => null, 'title_en' => 'Features', 'title_ar' => 'المزايا', 'url' => '#features'],
            ['cms_page_id' => null, 'title_en' => 'Workflow', 'title_ar' => 'مسار العمل', 'url' => '#workflow'],
            ['cms_page_id' => null, 'title_en' => 'FAQ', 'title_ar' => 'الأسئلة', 'url' => '#faq'],
        ];
    }
}
