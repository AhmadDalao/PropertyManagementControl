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
        expect(await mediaCards.count()).toBeGreaterThan(0);
        await expect(mediaCards.first()).toBeVisible();
        await expectNoHorizontalOverflow(page);

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
        await expect(
            picker.getByText('اختر صورة عامة من مكتبة الوسائط العامة.'),
        ).toBeVisible();
        await expect(picker.locator('.pmc-media-picker-panel')).toBeVisible();
        await expectNoHorizontalOverflow(page);
        await page.keyboard.press('Escape');
        await expect(picker.locator('.pmc-media-picker-panel')).toBeHidden();
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

    test('documentation uses focused guide pages', async ({ page }) => {
        await page.goto('/documentation');
        const guide = page.locator('a[href^="/documentation/"]').first();
        await expect(guide).toBeVisible();
        await guide.click();
        await expect(page).toHaveURL(/\/documentation\/[^/?]+/);
        await expect(page.locator('.pmc-doc-detail-layout')).toBeVisible();
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
