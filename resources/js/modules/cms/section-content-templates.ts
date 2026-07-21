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
                image: '',
                imageAlt: '',
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
