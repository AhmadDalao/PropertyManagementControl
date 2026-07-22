import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';

const viewports = {
    mobile: { width: 390, height: 844 },
    tablet: { width: 768, height: 1024 },
    compactDesktop: { width: 1024, height: 900 },
    desktop: { width: 1440, height: 1000 },
} as const;

const breakpoints = [
    { name: 'mobile', width: 390, height: 844 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'compact-desktop', width: 1024, height: 900 },
    { name: 'desktop', width: 1440, height: 1000 },
] as const;

const localAccounts = [
    { role: 'superadmin', email: 'superadmin@propertycontrol.test' },
    { role: 'owner', email: 'owner@propertycontrol.test' },
    { role: 'manager', email: 'manager@propertycontrol.test' },
    { role: 'tenant', email: 'tenant@propertycontrol.test' },
] as const;

const primaryAdminRoutes = [
    '/dashboard',
    '/profile',
    '/property-map',
    '/portfolios',
    '/users',
    '/assets',
    '/tenants',
    '/leases',
    '/payments',
    '/maintenance-requests',
    '/expenses',
    '/documents',
    '/media-files',
    '/audit-logs',
    '/cms',
    '/wording',
    '/system/showcase-data',
    '/reports',
    '/documentation',
] as const;

test.describe('public shell', () => {
    for (const viewport of breakpoints) {
        test(`${viewport.name} landing and login have no horizontal overflow`, async ({
            page,
        }) => {
            await page.setViewportSize(viewport);

            for (const path of ['/', '/login', '/?locale=ar']) {
                await page.goto(path);
                await expect(page.locator('body')).toBeVisible();
                await expectNoHorizontalOverflow(page);
            }
        });
    }

    test('public and login pages have no serious accessibility violations', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);

        for (const path of ['/', '/login', '/?locale=ar']) {
            await page.goto(path);
            const results = await new AxeBuilder({ page })
                .withTags(['wcag2a', 'wcag2aa', 'wcag21aa'])
                .analyze();

            expect(results.violations).toEqual([]);
        }
    });

    test('mobile public navigation locks the page and restores focus', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/');

        const header = page.locator('.pmc-site-header');
        const trigger = page.locator('.pmc-site-menu');
        await expect(header).toBeVisible();
        expect(
            await header.evaluate(
                (node) => node.getBoundingClientRect().height,
            ),
        ).toBeLessThanOrEqual(64);

        await trigger.click();
        await expect(page.locator('body')).toHaveClass(/pmc-site-menu-open/);
        await expect(page.locator('.pmc-site-links')).toHaveClass(/is-open/);
        await expect(page.locator('.pmc-site-links a').first()).toBeFocused();

        await page.keyboard.press('Escape');
        await expect(page.locator('body')).not.toHaveClass(
            /pmc-site-menu-open/,
        );
        await expect(trigger).toBeFocused();

        await page.goto('/?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(page.locator('.pmc-hero-copy h1')).toContainText(
            'أدر محفظتك العقارية',
        );
    });
});

test.describe('authenticated administration', () => {
    test.beforeEach(async ({ page }) => {
        await login(
            page,
            process.env.E2E_EMAIL ?? localAccounts[0].email,
            process.env.E2E_PASSWORD ?? 'password',
        );
    });

    test('mobile drawer locks the page and restores focus', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.tablet);
        await page.goto('/dashboard');

        const topbar = page.locator('.pmc-console-topbar');
        await expect(topbar).toBeVisible();
        expect(
            await topbar.evaluate(
                (node) => node.getBoundingClientRect().height,
            ),
        ).toBeLessThanOrEqual(64);

        const trigger = page.locator('.pmc-menu-trigger');
        const sidebar = page.locator('.pmc-console-sidebar');
        await expect(sidebar).toHaveAttribute('inert', '');
        await trigger.click();
        await expect(page.locator('body')).toHaveClass(/pmc-drawer-open/);
        await expect(page.locator('.pmc-console-shell')).toHaveClass(/is-open/);
        await expect(sidebar).not.toHaveAttribute('inert');
        await expect(sidebar.locator('.pmc-sidebar-collapse')).toBeHidden();

        await page.keyboard.press('Escape');
        await expect(page.locator('body')).not.toHaveClass(/pmc-drawer-open/);
        await expect(trigger).toBeFocused();

        await trigger.click();
        await page.setViewportSize(viewports.desktop);
        await expect(page.locator('body')).not.toHaveClass(/pmc-drawer-open/);
        await expect(page.locator('.pmc-console-shell')).not.toHaveClass(
            /is-open/,
        );
    });

    test('desktop sidebar preference and account menu keyboard behavior persist', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.desktop);
        await page.goto('/dashboard');
        await page.evaluate(() =>
            window.localStorage.removeItem('property-sidebar-collapsed'),
        );
        await page.reload();

        const shell = page.locator('.pmc-console-shell');
        const navigationTrigger = page.locator('.pmc-menu-trigger');
        await expect(shell).not.toHaveClass(/is-collapsed/);
        await navigationTrigger.click();
        await expect(shell).toHaveClass(/is-collapsed/);
        expect(
            await page.evaluate(() =>
                window.localStorage.getItem('property-sidebar-collapsed'),
            ),
        ).toBe('1');

        await page.reload();
        await expect(shell).toHaveClass(/is-collapsed/);
        await navigationTrigger.click();
        await expect(shell).not.toHaveClass(/is-collapsed/);

        const accountTrigger = page.locator('.pmc-account-trigger');
        await accountTrigger.click();
        await expect(page.locator('.pmc-account-panel')).toBeVisible();
        await page.keyboard.press('Escape');
        await expect(page.locator('.pmc-account-panel')).toHaveCount(0);
        await expect(accountTrigger).toBeFocused();
    });

    test('global search is responsive, scoped, and localized', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/dashboard');

        const trigger = page.locator('[data-search-trigger]');
        await trigger.click();
        await expect(page.locator('body')).toHaveClass(/pmc-search-open/);
        await expect(
            page.locator('.pmc-mobile-search-sheet[role="dialog"]'),
        ).toBeVisible();

        const input = page.locator('.pmc-global-search-mobile input');
        await expect(input).toBeFocused();
        await input.fill('CORAL');
        await expect(
            page
                .locator('.pmc-global-search-mobile .pmc-global-search-group')
                .filter({ hasText: 'Assets' })
                .locator('a')
                .first(),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.keyboard.press('Escape');
        await expect(page.locator('body')).not.toHaveClass(/pmc-search-open/);
        await expect(trigger).toBeFocused();

        await page.goto('/dashboard?locale=ar');
        await page.locator('[data-search-trigger]').click();
        await page.locator('.pmc-global-search-mobile input').fill('CORAL');
        await expect(
            page
                .locator('.pmc-global-search-mobile')
                .getByText('الأصول', { exact: true }),
        ).toBeVisible();
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expectNoHorizontalOverflow(page);
    });

    test('core workspaces switch from tables to compact cards', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);

        for (const path of [
            '/dashboard',
            '/portfolios',
            '/users',
            '/assets',
            '/tenants',
            '/leases',
            '/payments',
            '/maintenance-requests',
            '/expenses',
            '/documents',
            '/media-files',
            '/audit-logs',
            '/reports',
            '/documentation',
            '/cms',
        ]) {
            await page.goto(path);
            await expectNoHorizontalOverflow(page);

            const topbar = page.locator('.pmc-console-topbar');
            await expect(topbar).toBeVisible();
            expect(
                await topbar.evaluate(
                    (node) => node.getBoundingClientRect().height,
                ),
            ).toBeLessThanOrEqual(64);

            const desktopTable = page.locator('.pmc-table-scroll');

            if ((await desktopTable.count()) > 0) {
                await expect(desktopTable).toBeHidden();
            }

            const mobileCards = page.locator('.pmc-mobile-record-card');

            if ((await mobileCards.count()) > 0) {
                await expect(mobileCards.first()).toBeVisible();
            }
        }
    });

    test('primary administration routes never overflow', async ({ page }) => {
        for (const viewport of breakpoints) {
            await page.setViewportSize(viewport);

            for (const path of primaryAdminRoutes) {
                await page.goto(path);
                await expectNoHorizontalOverflow(page);
            }
        }
    });

    test('Data Lab stays compact, accessible, and bilingual', async ({
        page,
    }) => {
        for (const viewport of [viewports.mobile, viewports.desktop]) {
            await page.setViewportSize(viewport);
            await page.goto('/system/showcase-data?locale=en');

            await expect(
                page.getByRole('heading', {
                    level: 1,
                    name: 'Production Data Lab',
                }),
            ).toBeVisible();
            await expect(
                page.locator('.pmc-showcase-target-plan'),
            ).not.toHaveAttribute('open', '');
            await expect(
                page.locator('.pmc-showcase-summary article'),
            ).toHaveCount(4);
            await expectNoHorizontalOverflow(page);
        }

        const purge = page
            .getByRole('button', { name: 'Purge tagged data' })
            .first();
        await expect(purge).toBeVisible();
        await purge.click();

        const dialog = page.getByRole('dialog', {
            name: 'Purge showcase data',
        });
        const confirmation = page.getByLabel('Confirmation text');
        await expect(dialog).toBeVisible();
        await expect(confirmation).toBeFocused();
        expect(
            await page.locator('body').evaluate((node) => node.style.overflow),
        ).toBe('hidden');

        await page.keyboard.press('Escape');
        await expect(dialog).toHaveCount(0);
        await expect(purge).toBeFocused();

        await page.setViewportSize(viewports.mobile);
        await page.goto('/system/showcase-data?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'مختبر بيانات الإنتاج',
            }),
        ).toBeVisible();
        await expect(page.getByText('سجل مجموعات البيانات')).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('property map uses one focused responsive workspace', async ({
        page,
    }) => {
        for (const viewport of breakpoints) {
            await page.setViewportSize(viewport);
            await page.goto('/property-map');

            await expect(
                page.getByTestId('property-map-workspace'),
            ).toBeVisible();
            await expect(page.getByTestId('property-map-canvas')).toBeVisible();
            await expect(
                page.getByTestId('property-map-directory'),
            ).toBeVisible();
            await expect(page.getByTestId('property-map-detail')).toBeVisible();
            await expectNoHorizontalOverflow(page);
        }

        const canvas = page.getByTestId('property-map-canvas');
        const positionedCount = Number(
            (await canvas.getAttribute('data-positioned-count')) ?? 0,
        );

        await expect(page.getByTestId('property-map-marker')).toHaveCount(
            positionedCount,
        );
        await expect(page.locator('.pmc-map-command-strip')).toHaveCount(0);
        await expect(page.locator('.pmc-map-setup-queue')).toHaveCount(0);
        await expect(page.locator('.pmc-zone-directory')).toHaveCount(0);
        await expect(page.locator('.pmc-map-cluster').first()).toBeVisible();

        const records = page.getByTestId('property-map-record');
        const recordCount = await records.count();
        expect(recordCount).toBe(12);

        if (recordCount > 1) {
            const secondRecord = records.nth(1);
            const title = await secondRecord
                .locator('button strong')
                .innerText();

            await secondRecord.locator('button').click();
            await expect(secondRecord).toHaveClass(/is-selected/);
            await expect(
                page.getByTestId('property-map-detail').getByRole('heading', {
                    name: title,
                }),
            ).toBeVisible();
        }

        await page.getByRole('button', { name: 'Next records' }).click();
        await expect(page.getByText('Page 2 of 4')).toBeVisible();
        await expect(records).toHaveCount(12);
    });

    test('Arabic property map is translated and RTL', async ({ page }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/property-map?locale=ar');

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByTestId('property-map-workspace').getByRole('heading', {
                name: 'العقارات ضمن النطاق',
            }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('Arabic administration is translated and RTL', async ({ page }) => {
        await page.setViewportSize(viewports.desktop);
        await page.goto('/dashboard?locale=ar');

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.locator('.pmc-console-nav').getByText('لوحة التحكم'),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('Arabic lease directory, detail, and create form stay focused on mobile', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/leases?locale=ar');

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { name: 'العقود', exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('سجل العقود', { exact: true }),
        ).toBeVisible();
        await expect(page.locator('.pmc-table-scroll')).toBeHidden();
        await expect(
            page.locator('.pmc-mobile-record-card').first(),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page
            .locator('.pmc-mobile-record-card .pmc-record-open')
            .first()
            .click();
        await expect(
            page.getByText('إجمالي المستحق', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('الأيام المتبقية', { exact: true }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/leases/create?locale=ar');
        await expect(
            page.getByRole('heading', { name: 'إنشاء عقد', exact: true }),
        ).toBeVisible();
        await expect(page.getByLabel('المستأجر')).toBeVisible();
        await expect(page.getByLabel('أصل متاح للتأجير')).toBeVisible();
        await expect(page.getByLabel('قيمة الإيجار')).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('Arabic payment directory, detail, and create form stay focused on mobile', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/payments?locale=ar&per_page=10');

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { name: 'الدفعات', exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('سجل الدفعات', { exact: true }),
        ).toBeVisible();
        await expect(page.locator('.pmc-table-scroll')).toBeHidden();
        const paymentCards = page.locator('.pmc-mobile-record-card');
        const paymentCardCount = await paymentCards.count();
        expect(paymentCardCount).toBeGreaterThan(0);
        expect(paymentCardCount).toBeLessThanOrEqual(10);
        await expect(page.locator('body')).not.toContainText('payments.');
        await expectNoHorizontalOverflow(page);

        const detailLink = paymentCards.locator('.pmc-record-open').first();
        await expect(detailLink).toBeVisible();
        await detailLink.click();
        await expect(
            page.getByText('تفاصيل الدفعة', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('المبلغ', { exact: true }).first(),
        ).toBeVisible();
        await expect(
            page.getByText('الموزع', { exact: true }).first(),
        ).toBeVisible();
        await expect(
            page.getByText('غير الموزع', { exact: true }).first(),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/payments/create?locale=ar');
        await expect(
            page.getByRole('heading', { name: 'تسجيل دفعة', exact: true }),
        ).toBeVisible();
        await expect(page.getByLabel(/^المحفظة/)).toBeVisible();
        await expect(page.getByLabel(/^العقد/)).toBeVisible();
        await expect(page.getByLabel(/^طريقة الدفع/)).toBeVisible();
        await expect(page.getByLabel(/^المبلغ/)).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('profile settings are focused, responsive, and fully localized', async ({
        page,
    }) => {
        for (const viewport of [viewports.mobile, viewports.desktop]) {
            await page.setViewportSize(viewport);
            await page.goto('/profile?locale=en');

            await expect(
                page.getByRole('heading', { level: 1, name: 'Profile' }),
            ).toBeVisible();
            await expect(page.locator('.pmc-profile-summary')).toBeVisible();
            await expect(
                page.getByLabel('Name', { exact: true }),
            ).toBeVisible();
            await expect(
                page.getByLabel('Current password', { exact: true }),
            ).toBeVisible();

            const cardIcons = page.locator('.pmc-profile-card-icon i');
            await expect(cardIcons).toHaveCount(2);

            for (const icon of await cardIcons.all()) {
                await expect
                    .poll(() =>
                        icon.evaluate(
                            (node) =>
                                window.getComputedStyle(node, '::before')
                                    .content,
                        ),
                    )
                    .not.toBe('none');
            }

            const formColumns = await page
                .locator('.pmc-profile-form-grid')
                .evaluate(
                    (node) =>
                        window
                            .getComputedStyle(node)
                            .gridTemplateColumns.split(' ').length,
                );
            expect(formColumns).toBe(viewport.width < 1200 ? 1 : 2);
            await expectNoHorizontalOverflow(page);
        }

        await page.setViewportSize(viewports.mobile);
        await page.goto('/profile?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'الملف الشخصي',
            }),
        ).toBeVisible();
        await expect(page.getByLabel('الاسم', { exact: true })).toBeVisible();
        await expect(
            page.getByText('بيانات الملف الشخصي', { exact: true }),
        ).toBeVisible();
        await expect(page.getByText('Profile details')).toHaveCount(0);
        await expectNoHorizontalOverflow(page);
    });

    test('Arabic tenant workspace, form, and financial detail stay localized', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/tenants?locale=ar&per_page=10');

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { name: 'المستأجرون' }),
        ).toBeVisible();
        await expect(page.locator('.pmc-mobile-record-card')).toHaveCount(10);
        await page.locator('.pmc-mobile-action-menu summary').first().click();
        await expect(page.getByRole('button', { name: 'أرشفة' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Archive' })).toHaveCount(
            0,
        );
        await expectNoHorizontalOverflow(page);

        await page.goto('/tenants/create?locale=ar');
        await expect(
            page.getByRole('heading', { name: 'إنشاء مستأجر' }),
        ).toBeVisible();
        await expect(
            page.getByLabel('اسم المستأجر', { exact: false }),
        ).toBeVisible();
        await expect(
            page.getByLabel('بريد تسجيل الدخول', { exact: false }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/tenants/1?locale=ar');
        await page.locator('.pmc-resource-tab-select select').selectOption({
            value: 'financial',
        });
        await expect(page).toHaveURL(/tab=financial/);
        await expect(
            page
                .locator('.pmc-resource-detail-card')
                .getByText('الموقف المالي'),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('Arabic expense workspace, form, and financial detail stay localized', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/expenses?locale=ar&per_page=10');

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { name: 'المصاريف' }),
        ).toBeVisible();
        await expect(page.locator('body')).not.toContainText(
            'expenses.category_',
        );
        await expect(page.locator('.pmc-mobile-record-card')).toHaveCount(10);
        await page.locator('.pmc-mobile-action-menu summary').first().click();
        await expect(
            page.getByRole('button', { name: 'إلغاء المصروف' }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'Void expense' }),
        ).toHaveCount(0);
        await expectNoHorizontalOverflow(page);

        await page.goto('/expenses/create?locale=ar');
        await expect(
            page.getByRole('heading', { name: 'تسجيل مصروف' }),
        ).toBeVisible();
        await expect(page.getByLabel(/^المحفظة/)).toBeVisible();
        await expect(page.getByLabel(/^عنوان المصروف/)).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/expenses/1?locale=ar');
        await page.locator('.pmc-resource-tab-select select').selectOption({
            value: 'financial',
        });
        await expect(page).toHaveURL(/tab=financial/);
        await expect(
            page.locator('.pmc-resource-detail-card').getByText('السجل المالي'),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('Arabic document workspace, PDF form, and detail stay localized on mobile', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/documents?locale=ar&per_page=10');

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { name: 'المستندات', exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('سجل المستندات', { exact: true }),
        ).toBeVisible();
        const cards = page.locator('.pmc-mobile-record-card');
        const cardCount = await cards.count();
        expect(cardCount).toBeGreaterThan(0);
        expect(cardCount).toBeLessThanOrEqual(10);
        await expect(page.locator('.pmc-table-scroll')).toBeHidden();
        await expect(page.locator('body')).not.toContainText(
            'documents.options.',
        );
        await expectNoHorizontalOverflow(page);

        await cards.locator('.pmc-record-open').first().click();
        await expect(
            page.getByText('تفاصيل المستند', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('سجل الملف', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'تنزيل PDF' }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/documents/create?locale=ar');
        await expect(
            page.getByRole('heading', { name: 'رفع المستند', exact: true }),
        ).toBeVisible();
        await expect(page.getByLabel(/^إرفاق بـ/)).toBeVisible();
        await expect(page.getByLabel(/^نوع المستند/)).toBeVisible();
        await expect(page.getByLabel(/^ملف PDF/)).toHaveAttribute(
            'accept',
            '.pdf,application/pdf',
        );
        await expectNoHorizontalOverflow(page);
    });

    test('Arabic maintenance workspace, form, and detail stay localized', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/maintenance-requests?locale=ar&per_page=10');

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { level: 1, name: 'الصيانة' }),
        ).toBeVisible();
        const cards = page.locator('.pmc-mobile-record-card');
        const cardCount = await cards.count();
        expect(cardCount).toBeGreaterThan(0);
        expect(cardCount).toBeLessThanOrEqual(10);
        await expect(page.locator('body')).not.toContainText('maintenance.');
        await expectNoHorizontalOverflow(page);

        const detailLink = cards
            .locator('a[href^="/maintenance-requests/"]')
            .first();
        await expect(detailLink).toBeVisible();
        const detailHref = await detailLink.getAttribute('href');
        expect(detailHref).toBeTruthy();

        await page.goto(`${detailHref}?locale=ar`);
        await expect(page.getByText('طلب صيانة')).toBeVisible();
        await expect(page.getByText('سياق الطلب')).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/maintenance-requests/create?locale=ar');
        await expect(
            page.getByRole('heading', { name: 'إنشاء طلب' }),
        ).toBeVisible();
        await expect(page.getByLabel(/^الأصل/)).toBeVisible();
        await expect(page.getByLabel(/^المستأجر/)).toBeVisible();
        await expect(page.getByLabel(/^وصف المشكلة/)).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('Arabic user workspace, form, and detail stay localized', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/users?locale=ar&per_page=10');

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { name: 'المستخدمون والأدوار' }),
        ).toBeVisible();
        await expect(page.locator('.pmc-mobile-record-card')).toHaveCount(10);
        await expect(page.locator('body')).not.toContainText('users.');
        await expectNoHorizontalOverflow(page);

        const userDetailLinks = page.locator(
            '.pmc-mobile-record-card a[href^="/users/"]',
        );
        await expect(userDetailLinks.first()).toBeVisible();
        const userHref = await userDetailLinks.first().getAttribute('href');
        expect(userHref).toBeTruthy();

        await page.goto(`${userHref}?locale=ar`);
        await expect(page.getByText('حساب المستخدم')).toBeVisible();
        await expect(page.getByText('الحساب والنطاق')).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/users/create?locale=ar');
        await expect(
            page.getByRole('heading', { name: 'إنشاء مستخدم' }),
        ).toBeVisible();
        await expect(page.getByLabel(/^المحفظة/)).toBeVisible();
        await expect(page.getByLabel(/^الدور/)).toBeVisible();
        await expect(page.getByLabel(/^الاسم الكامل/)).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('Arabic portfolio workspace, form, and detail stay localized', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/portfolios?locale=ar&per_page=10');

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { name: 'المحافظ' }),
        ).toBeVisible();
        const portfolioCards = page.locator('.pmc-mobile-record-card');
        const portfolioCardCount = await portfolioCards.count();
        expect(portfolioCardCount).toBeGreaterThan(0);
        expect(portfolioCardCount).toBeLessThanOrEqual(10);
        await expect(page.locator('body')).not.toContainText('portfolios.');
        await expectNoHorizontalOverflow(page);

        const portfolioDetailLink = page
            .locator('.pmc-mobile-record-card a[href^="/portfolios/"]')
            .first();
        await expect(portfolioDetailLink).toBeVisible();
        const portfolioHref = await portfolioDetailLink.getAttribute('href');
        expect(portfolioHref).toBeTruthy();

        await page.goto(`${portfolioHref}?locale=ar`);
        await expect(page.getByText('حساب المحفظة')).toBeVisible();
        await expect(page.getByText('ملف النشاط')).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/portfolios/create?locale=ar');
        await expect(
            page.getByRole('heading', { name: 'إنشاء محفظة' }),
        ).toBeVisible();
        await expect(page.getByLabel(/^الاسم بالإنجليزية/)).toBeVisible();
        await expect(page.getByLabel(/^الاسم بالعربية/)).toBeVisible();
        await expect(page.locator('input[type="checkbox"]')).toHaveCount(10);
        await expectNoHorizontalOverflow(page);
    });

    test('media workspace, upload form, detail, and CMS picker stay responsive and localized', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/media-files?locale=ar&per_page=10');

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { name: 'مكتبة الوسائط' }),
        ).toBeVisible();
        await expect(page.locator('body')).not.toContainText('media.');
        const mediaCards = page.locator('.pmc-mobile-record-card');
        await expectNoHorizontalOverflow(page);

        if ((await mediaCards.count()) > 0) {
            await expect(mediaCards.first()).toBeVisible();
            const mediaDetailHref = await mediaCards
                .first()
                .locator('a[href^="/media-files/"]')
                .first()
                .getAttribute('href');
            expect(mediaDetailHref).toBeTruthy();
            await page.goto(`${mediaDetailHref}?locale=ar`);
            await expect(page.getByText('سجل الوسائط')).toBeVisible();
            await expect(page.locator('body')).not.toContainText('media.');
            await expectNoHorizontalOverflow(page);
        } else {
            const mobileEmptyState = page.locator(
                '.pmc-mobile-record-list .pmc-empty-state',
            );
            await expect(mobileEmptyState).toBeVisible();
            await expect(mobileEmptyState).toContainText(
                'لا توجد سجلات مطابقة',
            );
        }

        await page.goto('/media-files/create?locale=ar');
        await expect(
            page.getByRole('heading', { name: 'رفع صورة' }),
        ).toBeVisible();
        await expect(page.getByLabel(/^المحفظة/)).toBeVisible();
        await expect(page.getByLabel(/^المجموعة/)).toBeVisible();
        await expect(page.getByLabel(/^ملف الصورة/)).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/cms/sections/create?locale=ar');
        const picker = page.locator('details.pmc-media-picker').first();
        await expect(picker).toHaveAttribute('dir', 'rtl');
        await picker.locator('summary').click();
        await expect(page.locator('body')).toHaveClass(/pmc-media-picker-open/);
        await expect(
            picker.getByText('اختر صورة عامة من مكتبة الوسائط العامة.'),
        ).toBeVisible();
        await expect(picker.locator('.pmc-media-picker-panel')).toBeVisible();
        await expectNoHorizontalOverflow(page);
        await page.keyboard.press('Escape');
        await expect(picker.locator('.pmc-media-picker-panel')).toBeHidden();
        await expect(page.locator('body')).not.toHaveClass(
            /pmc-media-picker-open/,
        );
        await expect(picker.locator('summary')).toBeFocused();
    });

    test('language buttons persist Arabic and English after reload', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/dashboard?locale=en');

        await page.getByRole('button', { name: 'Switch to Arabic' }).click();
        await expect(page.locator('html')).toHaveAttribute('lang', 'ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.locator('.pmc-console-nav').getByText('لوحة التحكم'),
        ).toBeVisible();

        await page.reload();
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');

        await page
            .getByRole('button', { name: 'التبديل إلى الإنجليزية' })
            .click();
        await expect(page.locator('html')).toHaveAttribute('lang', 'en');
        await expect(page.locator('html')).toHaveAttribute('dir', 'ltr');
    });

    test('CMS workspace and builder stay focused on mobile and desktop', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/cms?view=pages&locale=ar');

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { name: 'إدارة الموقع' }),
        ).toBeVisible();
        await expect(page.locator('.pmc-mobile-record-card')).toHaveCount(1);
        await expect(page.locator('.pmc-table-scroll')).toBeHidden();
        await expectNoHorizontalOverflow(page);

        await page
            .locator('.pmc-filter-chip')
            .filter({ hasText: 'منشور' })
            .click();
        await expect
            .poll(() => new URL(page.url()).searchParams.get('view'))
            .toBe('pages');
        await expect
            .poll(() => new URL(page.url()).searchParams.get('status'))
            .toBe('published');

        await page.goto('/cms?view=sections&locale=ar');
        await expect(page.locator('.pmc-cms-library-grid')).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/cms?view=navigation&locale=ar');
        await expect(page.locator('.pmc-cms-navigation-list')).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/cms?view=pages&locale=ar');
        const builderLink = page.locator(
            '.pmc-mobile-record-head > div > a[href^="/cms/pages/"]',
        );
        await expect(builderLink).toHaveCount(1);
        const builderHref = await builderLink.getAttribute('href');
        expect(builderHref).toBeTruthy();

        await page.goto(`${builderHref}?locale=ar`);
        await page.getByRole('button', { name: 'المعاينة' }).click();
        await expect(page.locator('.pmc-cms-preview-pane')).toBeVisible();
        await expect(page.locator('.pmc-cms-library-pane')).toBeHidden();
        await expect(page.locator('.pmc-cms-preview-frame')).toHaveClass(
            /is-mobile/,
        );
        await expectNoHorizontalOverflow(page);

        await page.getByRole('button', { name: 'الإعدادات' }).click();
        await expect(page.locator('.pmc-cms-inspector-pane')).toBeVisible();
        expect(await page.locator('.pmc-cms-outline article').count()).toBe(8);
        await expect(
            page.getByRole('button', { name: 'نقل القسم للأسفل' }),
        ).toBeVisible();
        const sectionEditHref = await page
            .locator('.pmc-cms-selection a[href^="/cms/sections/"]')
            .getAttribute('href');
        expect(sectionEditHref).toBeTruthy();
        await expectNoHorizontalOverflow(page);

        await page.setViewportSize(viewports.desktop);
        await page.reload();
        await expect(page.locator('.pmc-cms-library-pane')).toBeVisible();
        await expect(page.locator('.pmc-cms-preview-pane')).toBeVisible();
        await expect(page.locator('.pmc-cms-inspector-pane')).toBeVisible();
        await expect(page.locator('.pmc-cms-preview-frame')).toHaveClass(
            /is-desktop/,
        );
        await expectNoHorizontalOverflow(page);

        await page.setViewportSize(viewports.mobile);
        await page.goto(`${sectionEditHref}?locale=ar`);
        await expect(page.locator('.pmc-section-editor')).toBeVisible();
        await expect(page.locator('.pmc-section-language')).toHaveCount(2);
        await expect(page.locator('.pmc-section-json')).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('documentation search and guide pages stay focused, responsive, and Arabic', async ({
        page,
    }) => {
        for (const viewport of [viewports.mobile, viewports.desktop]) {
            await page.setViewportSize(viewport);
            await page.goto('/documentation?locale=en');

            await expect(
                page.getByRole('heading', {
                    level: 1,
                    name: 'Documentation',
                }),
            ).toBeVisible();
            await expect(page.locator('.pmc-doc-role-card')).toBeVisible();
            await page.getByLabel('Search guides').fill('no-such-guide');
            await expect(
                page.getByText('No guides match this search'),
            ).toBeVisible();
            await page.getByRole('button', { name: 'Clear search' }).click();

            const guide = page.locator('a[href^="/documentation/"]').first();
            await expect(guide).toBeVisible();
            await guide.click();
            await expect(page).toHaveURL(/\/documentation\/[^/?]+/);
            await expect(page.locator('.pmc-doc-detail-layout')).toBeVisible();
            await expect(
                page.locator('.pmc-doc-detail-content > section'),
            ).toHaveCount(3);
            await expect(page.locator('main main')).toHaveCount(0);

            const guideNavigationColumns = await page
                .locator('.pmc-doc-detail-layout > aside')
                .evaluate(
                    (node) =>
                        window
                            .getComputedStyle(node)
                            .gridTemplateColumns.split(' ').length,
                );
            expect(guideNavigationColumns).toBe(viewport.width < 1200 ? 3 : 1);
            await expectNoHorizontalOverflow(page);
        }

        await page.setViewportSize(viewports.mobile);
        await page.goto('/documentation?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { level: 1, name: 'دليل الاستخدام' }),
        ).toBeVisible();
        await expect(page.getByText('نقاط بداية مقترحة')).toBeVisible();

        await page.goto('/documentation/asset-control?locale=ar');
        await expect(
            page.getByRole('heading', { level: 1, name: 'إدارة الأصول' }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', { name: 'المزايا' }),
        ).toBeVisible();
        await expect(page.getByText('Features')).toHaveCount(0);
        await expectNoHorizontalOverflow(page);
    });

    test('reports use responsive cards, complete Arabic controls, and real XLSX export', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/reports?locale=ar');

        await expect(
            page.getByRole('heading', { name: 'التقارير', exact: true }),
        ).toBeVisible();
        await page.getByRole('button', { name: 'إظهار التصفيات' }).click();
        await expect(page.locator('#report-filter-panel')).toBeVisible();
        await expect(page.getByLabel('التاريخ من')).toBeVisible();
        await expect(page.getByLabel('التاريخ إلى')).toBeVisible();

        for (const tab of ['التحصيل', 'التكاليف', 'التشغيل']) {
            await page.getByRole('button', { name: tab, exact: true }).click();
            await expectNoHorizontalOverflow(page);
        }

        await expect(page.locator('.pmc-report-record-grid')).toBeVisible();
        await expect(page.locator('.pmc-table-scroll')).toHaveCount(0);

        const workbook = await page.request.get('/reports/export');
        expect(workbook.ok()).toBeTruthy();
        expect(workbook.headers()['content-type']).toContain(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
        expect((await workbook.body()).subarray(0, 2).toString()).toBe('PK');

        await page.setViewportSize(viewports.desktop);
        await page.goto('/reports');
        await expect(page.locator('.pmc-report-pulse-grid')).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('audit history uses localized metrics, mobile cards, filters, and real XLSX export', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/audit-logs?locale=ar');

        await expect(
            page.getByRole('heading', { name: 'سجل التدقيق', exact: true }),
        ).toBeVisible();
        await expect(page.locator('.pmc-metric-card')).toHaveCount(4);
        await expect(page.locator('.pmc-table-scroll')).toBeHidden();
        await expect(
            page.locator('.pmc-mobile-record-card').first(),
        ).toBeVisible();

        await page.locator('.pmc-mobile-filter-trigger').click();
        await expect(page.getByLabel('الحدث')).toBeVisible();
        await expect(page.getByLabel('نوع السجل')).toBeVisible();
        await expect(page.getByLabel('عدّله')).toBeVisible();
        await expect(page.getByLabel('التاريخ من')).toBeVisible();
        await expect(page.getByLabel('التاريخ إلى')).toBeVisible();
        await expectNoHorizontalOverflow(page);

        const workbook = await page.request.get('/audit-logs/export?locale=ar');
        expect(workbook.ok()).toBeTruthy();
        expect(workbook.headers()['content-type']).toContain(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
        expect((await workbook.body()).subarray(0, 2).toString()).toBe('PK');

        await page.setViewportSize(viewports.desktop);
        await page.goto('/audit-logs');
        await expect(page.locator('.pmc-table-scroll')).toBeVisible();
        await expect(page.locator('.pmc-data-table')).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('wording workspace keeps editing focused, responsive, and Arabic', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/wording');
        await expect(page.locator('.pmc-wording-row').first()).toBeVisible();
        await page.locator('.pmc-wording-row').first().click();

        const editor = page.locator('.pmc-wording-editor[role="dialog"]');
        await expect(editor).toBeVisible();
        await expect(editor.locator('textarea').first()).toBeFocused();
        expect(
            await page.locator('body').evaluate((node) => node.style.overflow),
        ).toBe('hidden');
        await expectNoHorizontalOverflow(page);

        await page.keyboard.press('Escape');
        await expect(editor).toBeHidden();
        expect(
            await page.locator('body').evaluate((node) => node.style.overflow),
        ).not.toBe('hidden');

        await page.goto('/wording?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await page.getByRole('button', { name: /ترجمة المحتوى/ }).click();
        await expect(
            page.getByRole('button', { name: /المحافظ/ }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('authenticated command-center routes have no serious accessibility violations', async ({
        page,
    }) => {
        for (const viewport of [viewports.mobile, viewports.desktop]) {
            await page.setViewportSize(viewport);

            for (const path of [
                '/dashboard',
                '/profile',
                '/assets',
                '/property-map',
                '/reports',
                '/audit-logs',
                '/media-files',
                '/media-files/create',
                '/cms',
                '/cms/sections/create',
                '/wording',
                '/system/showcase-data',
            ]) {
                await page.goto(path);
                const results = await new AxeBuilder({ page })
                    .withTags(['wcag2a', 'wcag2aa', 'wcag21aa'])
                    .analyze();

                expect(results.violations).toEqual([]);
            }
        }
    });
});

test.describe('local role dashboards', () => {
    const roleNavigation = {
        superadmin: {
            visible: ['/assets', '/cms', '/wording', '/system/showcase-data'],
            hidden: [] as string[],
        },
        owner: {
            visible: ['/assets', '/payments', '/users'],
            hidden: ['/cms', '/wording', '/system/showcase-data'],
        },
        manager: {
            visible: ['/assets', '/payments', '/users'],
            hidden: ['/cms', '/wording', '/system/showcase-data'],
        },
        tenant: {
            visible: ['/dashboard', '/maintenance-requests', '/documentation'],
            hidden: ['/assets', '/payments', '/users', '/cms'],
        },
    } as const;

    for (const account of localAccounts) {
        test(`${account.role} dashboard is scoped and responsive`, async ({
            page,
        }) => {
            await login(
                page,
                process.env[`E2E_${account.role.toUpperCase()}_EMAIL`] ??
                    account.email,
                process.env.E2E_PASSWORD ?? 'password',
            );
            await page.setViewportSize(viewports.mobile);
            await page.goto('/dashboard');

            await expect(page.locator('.pmc-console-main')).toBeVisible();
            await page.locator('.pmc-menu-trigger').click();

            const navigation = page.locator('.pmc-console-nav');

            for (const href of roleNavigation[account.role].visible) {
                await expect(
                    navigation.locator(`a[href="${href}"]`),
                ).toBeVisible();
            }

            for (const href of roleNavigation[account.role].hidden) {
                await expect(
                    navigation.locator(`a[href="${href}"]`),
                ).toHaveCount(0);
            }

            await expect(
                navigation.locator('a[href="/dashboard"]'),
            ).toHaveAttribute('aria-current', 'page');
            await expectNoHorizontalOverflow(page);
        });
    }
});

async function login(page: Page, email: string, password: string) {
    await page.goto('/login');

    if (page.url().includes('/dashboard')) {
        await page.request.post('/logout');
        await page.goto('/login');
    }

    await page.locator('#login-email').fill(email);
    await page.locator('#login-password').fill(password);
    await page.getByRole('button', { name: /sign in|تسجيل الدخول/i }).click();
    await expect(page).toHaveURL(/\/dashboard/);
}

async function expectNoHorizontalOverflow(page: Page) {
    const overflow = await page.evaluate(() => {
        const root = document.documentElement;

        return Math.ceil(root.scrollWidth - root.clientWidth);
    });

    expect(overflow).toBeLessThanOrEqual(1);
}
