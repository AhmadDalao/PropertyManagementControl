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
        await trigger.click();
        await expect(page.locator('body')).toHaveClass(/pmc-drawer-open/);
        await expect(page.locator('.pmc-console-shell')).toHaveClass(/is-open/);

        await page.keyboard.press('Escape');
        await expect(page.locator('body')).not.toHaveClass(/pmc-drawer-open/);
        await expect(trigger).toBeFocused();
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

    test('documentation uses focused guide pages', async ({ page }) => {
        await page.goto('/documentation');
        const guide = page.locator('a[href^="/documentation/"]').first();
        await expect(guide).toBeVisible();
        await guide.click();
        await expect(page).toHaveURL(/\/documentation\/[^/?]+/);
        await expect(page.locator('.pmc-doc-detail-layout')).toBeVisible();
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
                '/cms',
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
