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
    '/company-control',
    '/portfolio-control',
    '/action-center',
    '/profile',
    '/property-map',
    '/property-explorer',
    '/portfolios',
    '/opening-data',
    '/users',
    '/assets',
    '/assets/building-setup',
    '/tenants',
    '/leases',
    '/lease-renewals',
    '/lease-move-outs',
    '/rent-collection',
    '/payments',
    '/maintenance-requests',
    '/maintenance-work-orders',
    '/maintenance-vendors',
    '/expenses',
    '/documents',
    '/notifications',
    '/media-files',
    '/audit-logs',
    '/cms',
    '/wording',
    '/system/showcase-data',
    '/system/readiness',
    '/system/email-delivery',
    '/system/backups',
    '/reports',
    '/reports/saved',
    '/reports/saved/create',
    '/reports/statement',
    '/reports/rent-roll',
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

    test('document titles use the localized product name without starter branding', async ({
        page,
    }) => {
        await page.goto('/login?locale=en');
        await expect(page).toHaveTitle(
            /Property Control Login \| Property Management Control$/,
        );
        await expect(page).not.toHaveTitle(/Laravel/);

        await page.goto('/login?locale=ar');
        await expect(page).toHaveTitle(
            /تسجيل الدخول إلى نظام العقارات \| نظام إدارة العقارات$/,
        );
        await expect(page).not.toHaveTitle(/Laravel/);
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

    test('property scope follows owners across operational pages in English and Arabic', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.desktop);
        await page.goto('/dashboard?property_id=all&locale=en');

        const trigger = page.locator('[data-property-scope-trigger]');
        await expect(trigger).toBeVisible();
        await expect(trigger).toHaveAttribute('data-selected-property', 'all');
        await expect(
            page.locator('.pmc-property-context').getByText('Property scope'),
        ).toBeVisible();
        await trigger.click();

        const dialog = page.locator('[data-property-scope-dialog]');
        const search = page.locator('[data-property-scope-search]');
        const options = dialog.locator('[data-property-scope-option]');
        await expect(dialog).toBeVisible();
        await expect(search).toBeFocused();
        const closeButton = dialog.getByRole('button', {
            name: 'Close property picker',
        });
        await page.keyboard.press('Shift+Tab');
        await expect(closeButton).toBeFocused();
        await page.keyboard.press('Tab');
        await expect(search).toBeFocused();
        expect(await options.count()).toBeGreaterThan(1);
        expect(await dialog.locator('section > h3').count()).toBeGreaterThan(0);

        await search.fill('not-a-real-property');
        await expect(options).toHaveCount(0);
        await expect(
            dialog.getByText('No properties found', { exact: true }),
        ).toBeVisible();
        await search.fill('');

        const firstOption = options.first();
        const propertyId =
            (await firstOption.getAttribute('data-property-scope-option')) ??
            '';
        expect(propertyId).not.toBe('');
        await firstOption.click();
        await expect(page).toHaveURL(new RegExp(`property_id=${propertyId}`));
        await expect(trigger).toHaveAttribute(
            'data-selected-property',
            propertyId,
        );

        const explorerLink = page.locator(
            `.pmc-nav-link[href="/property-explorer?property_id=${propertyId}"]`,
        );
        await expect(explorerLink).toBeVisible();
        await explorerLink.click();
        await expect(page).toHaveURL(
            new RegExp(`/property-explorer\\?property_id=${propertyId}`),
        );
        await expect(
            page.locator('[data-property-scope-trigger]'),
        ).toHaveAttribute('data-selected-property', propertyId);
        await expectNoHorizontalOverflow(page);

        await page.goto('/leases?locale=en');
        await expect(
            page.locator('[data-property-scope-trigger]'),
        ).toHaveAttribute('data-selected-property', propertyId);
        await expect(
            page.locator(
                `.pmc-nav-link[href="/payments?property_id=${propertyId}"]`,
            ),
        ).toBeVisible();

        await page.setViewportSize(viewports.mobile);
        await page.goto('/dashboard?locale=ar');
        await page.locator('.pmc-menu-trigger').click();
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.locator('.pmc-property-context').getByText('نطاق العقار'),
        ).toBeVisible();
        await expect(
            page.locator('[data-property-scope-trigger]'),
        ).toHaveAttribute('data-selected-property', propertyId);
        await page.locator('[data-property-scope-trigger]').click();
        await expect(
            page.locator('[data-property-scope-search]'),
        ).toHaveAttribute(
            'placeholder',
            'ابحث باسم العقار أو الرمز أو المحفظة...',
        );
        await expectMinimumTouchHeight(
            page,
            '[data-property-scope-search], [data-property-scope-clear]',
        );
        await expectNoHorizontalOverflow(page);
        const accessibility = await new AxeBuilder({ page })
            .include('[data-property-scope-dialog]')
            .withTags(['wcag2a', 'wcag2aa', 'wcag21aa'])
            .analyze();
        expect(accessibility.violations).toEqual([]);
        await page.keyboard.press('Escape');
        await expect(
            page.locator('[data-property-scope-trigger]'),
        ).toBeFocused();
    });

    test('portfolio control ranks every property with direct mobile actions and Arabic copy', async ({
        page,
    }) => {
        for (const viewport of [viewports.mobile, viewports.desktop]) {
            await page.setViewportSize(viewport);
            await page.goto('/portfolio-control?locale=en');

            await expect(
                page.getByRole('heading', {
                    level: 1,
                    name: 'Portfolio control',
                }),
            ).toBeVisible();
            await expect(
                page.locator('.pmc-portfolio-control-card').first(),
            ).toBeVisible();
            await expect(
                page.locator('.pmc-portfolio-control-grid'),
            ).toBeVisible();
            await expect(page.locator('.pmc-table-scroll')).toHaveCount(0);
            await expectMinimumTouchHeight(
                page,
                [
                    '.pmc-portfolio-control-chips button',
                    '.pmc-portfolio-control-filter-actions button',
                    '.pmc-portfolio-control-card footer a',
                ].join(', '),
            );
            await expectNoHorizontalOverflow(page);
        }

        await page.setViewportSize(viewports.mobile);
        await page.goto('/portfolio-control?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'تحكم المحفظة',
            }),
        ).toBeVisible();
        await expect(
            page.getByPlaceholder('العقار أو الرمز أو المحفظة'),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'تركيز لوحة التحكم' }).first(),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('company control compares client portfolios without tables or mobile overflow', async ({
        page,
    }) => {
        for (const viewport of [viewports.mobile, viewports.desktop]) {
            await page.setViewportSize(viewport);
            await page.goto('/company-control?locale=en');

            await expect(
                page.getByRole('heading', {
                    level: 1,
                    name: 'Company control',
                }),
            ).toBeVisible();
            await expect(
                page.locator('.pmc-company-control-card').first(),
            ).toBeVisible();
            await expect(
                page
                    .locator(
                        '.pmc-company-control-card a[href^="/reports/statement?portfolio_id="]',
                    )
                    .first(),
            ).toBeVisible();
            await expect(page.locator('.pmc-table-scroll')).toHaveCount(0);
            await expectMinimumTouchHeight(
                page,
                [
                    '.pmc-company-control-chips button',
                    '.pmc-company-control-filter-actions button',
                    '.pmc-company-control-card footer a',
                ].join(', '),
            );
            await expectNoHorizontalOverflow(page);
        }

        await page.setViewportSize(viewports.mobile);
        await page.goto('/company-control?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'تحكم الشركة',
            }),
        ).toBeVisible();
        await expect(
            page.getByPlaceholder('المحفظة أو الرمز أو المالك'),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
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
            '/action-center',
            '/portfolios',
            '/users',
            '/assets',
            '/tenants',
            '/leases',
            '/lease-renewals',
            '/lease-move-outs',
            '/rent-collection',
            '/payments',
            '/maintenance-requests',
            '/maintenance-work-orders',
            '/expenses',
            '/documents',
            '/media-files',
            '/audit-logs',
            '/system/email-delivery',
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
                const firstMobileCard = mobileCards.first();
                await expect(firstMobileCard).toBeVisible();

                const metadata = firstMobileCard.locator(
                    '.pmc-mobile-record-meta',
                );

                if ((await metadata.count()) > 0) {
                    const values = await metadata
                        .locator(':scope > div')
                        .count();
                    const columns = await metadata.evaluate((element) => {
                        return getComputedStyle(
                            element,
                        ).gridTemplateColumns.split(' ').length;
                    });

                    expect(columns).toBe(Math.min(values, 3));
                }
            }
        }
    });

    test('building setup is direct, responsive, and bilingual', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/assets/building-setup?locale=en');

        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'Set up a building',
            }),
        ).toBeVisible();
        await page.getByLabel('Building name (English)').fill('E2E Tower');
        await page.getByLabel('Building code prefix').fill('E2E-TOWER');
        await page.getByLabel('Number of floors').fill('3');
        await page.getByLabel('Units per floor').fill('5');

        const totalMetric = page
            .locator('.pmc-building-setup-metrics > div')
            .filter({ hasText: 'Total records' });
        await expect(totalMetric.locator('strong')).toHaveText('19');
        await expect(
            page.getByText('Unit 101 - Unit 105', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'Cancel', exact: true }),
        ).toBeVisible();
        await expectMinimumTouchHeight(
            page,
            '.pmc-building-setup-actions .btn',
        );
        await expectNoHorizontalOverflow(page);

        await page.goto('/assets/building-setup?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { level: 1, name: 'إعداد مبنى' }),
        ).toBeVisible();
        await expect(page.getByLabel('اسم المبنى بالإنجليزية')).toBeVisible();
        await expect(page.getByLabel('عدد الطوابق')).toBeVisible();
        await expect(
            page.getByText('إرسال واحد وهيكل مكتمل', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'إلغاء', exact: true }),
        ).toBeVisible();
        await expect(page.locator('body')).not.toContainText('assets.builder.');
        await expect(page.locator('body')).not.toContainText('common.cancel');
        await expectNoHorizontalOverflow(page);
    });

    test('rent collection stays direct, touch-safe, and bilingual', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.desktop);
        await page.goto('/rent-collection?locale=en');

        await expect(
            page.getByRole('heading', { name: 'Rent Collection' }),
        ).toBeVisible();
        await expect(
            page.locator('.pmc-operations-table > .pmc-table-scroll'),
        ).toBeVisible();
        await expect(page.locator('.pmc-mobile-record-list')).toBeHidden();
        await expectNoHorizontalOverflow(page);

        await page.setViewportSize(viewports.mobile);
        await page.reload();

        await expect(
            page.locator('.pmc-operations-table > .pmc-table-scroll'),
        ).toBeHidden();
        const firstMobileRecord = page
            .locator('.pmc-mobile-record-card')
            .first();
        await expect(firstMobileRecord).toBeVisible();
        await expect(
            firstMobileRecord.locator('.pmc-mobile-record-title-link'),
        ).toBeVisible();
        await expect(
            firstMobileRecord.locator(
                ':scope > .pmc-mobile-record-footer > .pmc-record-open',
            ),
        ).toHaveCount(0);
        expect(
            await firstMobileRecord.locator('dl').evaluate((element) => {
                return getComputedStyle(element).gridTemplateColumns.split(' ')
                    .length;
            }),
        ).toBe(3);
        expect(
            (await firstMobileRecord.boundingBox())?.height ?? 0,
        ).toBeLessThan(340);
        await expectNoHorizontalOverflow(page);
        await expectMinimumTouchHeight(
            page,
            [
                '.pmc-workspace-action',
                '.pmc-filter-chip',
                '.pmc-table-search .form-control',
                '.pmc-mobile-filter-trigger',
                '.pmc-active-filters button',
                '.pmc-mobile-record-title-link',
                '.pmc-mobile-action-menu > summary',
            ].join(', '),
        );

        await page.goto('/rent-collection?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { name: 'تحصيل الإيجارات' }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('operational table search updates results without submitting the filter form', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.desktop);
        await page.goto('/assets?locale=en');

        const search = page.locator('.pmc-table-search input');
        await search.fill('ROSE-101');
        await expect(page).toHaveURL(/search=ROSE-101/);
        await expect(search).toHaveAttribute('aria-busy', 'false');
        await expect(
            page.locator('.pmc-data-table').getByText('ROSE-101'),
        ).toBeVisible();

        await search.fill('');
        await expect(page).not.toHaveURL(/search=/);
        await expectNoHorizontalOverflow(page);
    });

    test('property explorer keeps hierarchy, tenant search, and Arabic context clear', async ({
        page,
    }) => {
        for (const viewport of breakpoints) {
            await page.setViewportSize(viewport);
            await page.goto('/property-explorer?locale=en');

            await expect(
                page.getByRole('heading', {
                    level: 1,
                    name: 'Property explorer',
                }),
            ).toBeVisible();
            await expect(
                page.getByTestId('property-explorer-record').first(),
            ).toBeVisible();
            await expectNoHorizontalOverflow(page);
        }

        await page.setViewportSize(viewports.mobile);
        const explorerTabs = page.locator('[data-explorer-view-tab]');
        await expect(explorerTabs).toHaveCount(3);
        await expect(
            page.locator('[data-explorer-view-tab="structure"]'),
        ).toHaveAttribute('aria-pressed', 'true');
        await expect(
            page.locator('[data-explorer-view-panel="structure"]'),
        ).toBeVisible();
        await expect(
            page.locator('[data-explorer-view-panel="record"]'),
        ).toBeHidden();
        await page.locator('[data-explorer-view-tab="overview"]').click();
        await expect(page).toHaveURL(/view=overview/);
        await expect(
            page.locator('[data-explorer-view-panel="structure"]'),
        ).toBeHidden();
        await expect(
            page.locator('[data-explorer-view-panel="record"]'),
        ).toBeVisible();
        await expect(
            page.locator('[data-explorer-focus-section="overview"]'),
        ).toBeVisible();
        await expect(
            page.locator('[data-explorer-focus-section="tenancy"]'),
        ).toBeHidden();

        await page.getByTestId('property-explorer-property').click();
        await page
            .locator('[data-property-scope-option]')
            .filter({ hasText: 'ROSE-TOWER' })
            .click();
        await expect(page).toHaveURL(/property_id=/);
        const search = page.getByTestId('property-explorer-search');
        await search.fill('Sara Tenant');
        await expect(page).toHaveURL(/search=Sara(?:%20|\+)Tenant/);
        await expect(page.getByTestId('property-explorer-record')).toHaveCount(
            1,
        );
        await expect(
            page.getByText('Sara Tenant', { exact: true }),
        ).toBeVisible();
        await expectMinimumTouchHeight(
            page,
            '.pmc-explorer-focus-actions .btn, .pmc-explorer-record footer a',
        );
        await expectNoHorizontalOverflow(page);
        const matchingUnit = page.locator(
            '.pmc-explorer-record footer a.is-primary',
        );
        await expect(matchingUnit).toHaveCount(1);
        await matchingUnit.click();
        await expect(
            page.locator('[data-explorer-view-tab="tenancy"]'),
        ).toHaveAttribute('aria-pressed', 'true');
        await expect(
            page.locator('[data-explorer-focus-section="tenancy"]'),
        ).toBeVisible();
        await expect(
            page.locator('[data-explorer-focus-section="overview"]'),
        ).toBeHidden();

        await page.goto('/property-explorer?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'مستكشف العقارات',
            }),
        ).toBeVisible();
        await expect(
            page.getByRole('searchbox', { name: 'البحث داخل العقار' }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('owner statement is concise, responsive, and bilingual', async ({
        page,
    }) => {
        for (const viewport of breakpoints) {
            await page.setViewportSize(viewport);
            await page.goto('/reports/statement?locale=en');

            await expect(
                page.getByRole('heading', {
                    level: 1,
                    name: 'Owner statement',
                }),
            ).toBeVisible();
            await expect(page.locator('.pmc-statement-context')).toBeVisible();
            await expect(
                page.locator(
                    '.pmc-workspace-actions a.pmc-workspace-action[href^="/reports?"]',
                ),
            ).toBeVisible();
            await expect(
                page.locator('.pmc-report-currency-grid article').first(),
            ).toBeVisible();
            await expect(
                page.locator('.pmc-statement-health-grid .pmc-report-pulse'),
            ).toHaveCount(3);
            await expect(page.getByRole('tab')).toHaveCount(5);
            await expect(
                page.getByRole('tab', { name: 'Overview' }),
            ).toHaveAttribute('aria-selected', 'true');
            await expect(page.locator('.pmc-report-comparison')).toHaveCount(0);
            await expect(
                page.locator('.pmc-statement-record-grid'),
            ).toHaveCount(0);
            await expect(
                page.locator('a[href^="/reports/statement.pdf"]'),
            ).toBeVisible();
            await expect(
                page.locator('a[href^="/reports/statement.docx"]'),
            ).toBeVisible();
            await expect(
                page.locator('a[href^="/reports/statement.xlsx"]'),
            ).toBeVisible();
            const filterTrigger = page.getByRole('button', {
                name: 'Show filters',
            });

            if (viewport.width < 768) {
                await expect(filterTrigger).toBeVisible();
                await filterTrigger.click();
            }

            await expect(page.getByLabel('Period')).toBeVisible();
            await expect(page.getByLabel('Portfolio')).toBeVisible();
            await expect(page.getByLabel(/^Property:/)).toBeVisible();

            await page.getByRole('tab', { name: 'What changed' }).click();
            await expect(page).toHaveURL(/tab=comparison/);
            await expect(
                page.locator('.pmc-report-comparison-card').first(),
            ).toBeVisible();

            await page
                .getByRole('tab', { name: 'Contracts in arrears' })
                .click();
            await expect(page).toHaveURL(/tab=arrears/);
            await expect(
                page.locator('.pmc-statement-record-grid'),
            ).toBeVisible();
            await expect(
                page.locator(
                    '.pmc-statement-record-grid .pmc-report-record-panel',
                ),
            ).toHaveCount(1);

            if (viewport.width < 768) {
                expect(
                    await page.evaluate(
                        () => document.documentElement.scrollHeight,
                    ),
                ).toBeLessThan(2600);
            }

            await expectNoHorizontalOverflow(page);
        }

        await page.setViewportSize(viewports.desktop);
        await page.goto('/reports/statement?locale=en');
        await page.getByLabel('Period').selectOption('last_month');
        await page.getByRole('button', { name: 'Apply', exact: true }).click();
        await expect(page).toHaveURL(/period=last_month/);
        await expect(
            page.locator('a[href^="/reports/statement.xlsx"]'),
        ).toHaveAttribute('href', /period=last_month/);

        await page.goto('/reports/statement?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { level: 1, name: 'كشف المالك' }),
        ).toBeVisible();
        await expect(page.getByRole('link', { name: 'رجوع' })).toBeVisible();
        await expect(page.getByLabel('فترة التقرير')).toBeVisible();
        await page.getByRole('tab', { name: 'ما الذي تغير' }).click();
        await expect(page).toHaveURL(/tab=comparison/);
        await expect(
            page.getByRole('heading', { name: 'ما الذي تغير' }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('tenant account statement stays compact, scoped, and bilingual', async ({
        page,
    }) => {
        for (const viewport of breakpoints) {
            await page.setViewportSize(viewport);
            await page.goto('/tenants/1/account-statement?locale=en');

            await expect(
                page.getByRole('heading', {
                    level: 1,
                    name: /account statement$/i,
                }),
            ).toBeVisible();
            await expect(
                page.locator('.pmc-tenant-statement-context'),
            ).toBeVisible();
            await expect(page.locator('.pmc-metric-card')).toHaveCount(4);
            await expect(
                page.locator('.pmc-tenant-financial-card').first(),
            ).toBeVisible();
            await expect(
                page.locator('a[href^="/tenants/1/account-statement.pdf"]'),
            ).toBeVisible();
            await expect(
                page.locator('a[href^="/tenants/1/account-statement.xlsx"]'),
            ).toBeVisible();
            await expect(
                page.getByRole('button', { name: 'Apply period' }),
            ).toBeVisible();
            await expectNoHorizontalOverflow(page);
        }

        await page.setViewportSize(viewports.mobile);
        await page.getByRole('button', { name: 'Payments' }).click();
        await expect(page).toHaveURL(/tab=payments/);
        await expect(
            page.getByText('Payment ledger', { exact: true }),
        ).toBeVisible();
        await expectMinimumTouchHeight(
            page,
            '.pmc-tenant-statement-tabs button, .pmc-tenant-statement-period button',
        );

        await page.goto('/tenants/1/account-statement?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: /^كشف حساب /,
            }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'الدفعات' }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'تطبيق الفترة' }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        const accessibility = await new AxeBuilder({ page })
            .withTags(['wcag2a', 'wcag2aa', 'wcag21aa'])
            .analyze();
        expect(accessibility.violations).toEqual([]);
    });

    test('action center keeps daily work prioritized, card-first, and bilingual', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.desktop);
        await page.goto('/action-center?locale=en');

        await expect(
            page.getByRole('heading', { level: 1, name: 'Action Center' }),
        ).toBeVisible();
        await expect(page.locator('.pmc-action-filter-form')).toBeVisible();
        await expect(page.locator('.pmc-action-card').first()).toBeVisible();
        await expect(page.locator('.pmc-action-card-grid')).toHaveCSS(
            'display',
            'grid',
        );
        await expect(page.locator('.pmc-action-card-grid')).not.toContainText(
            'tenant_notice',
        );
        await expect(page.locator('.pmc-action-card-grid')).not.toContainText(
            'natural_expiry',
        );
        await expectNoHorizontalOverflow(page);

        await page.setViewportSize(viewports.mobile);
        await page.reload();

        await expect(page.locator('.pmc-action-filter-form')).toBeHidden();
        await expect(page.locator('.pmc-action-card').first()).toBeVisible();
        await page.locator('.pmc-action-mobile-filter').click();
        await expect(page.locator('.pmc-action-filter-form')).toBeVisible();
        await expectMinimumTouchHeight(
            page,
            [
                '.pmc-workspace-action',
                '.pmc-action-type-chips a',
                '.pmc-action-mobile-filter',
                '.pmc-action-filter-form .form-control',
                '.pmc-action-filter-form .form-select',
                '.pmc-action-filter-actions .btn',
                '.pmc-action-open',
            ].join(', '),
        );
        await expectNoHorizontalOverflow(page);

        await page.goto('/action-center?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'مركز الإجراءات',
            }),
        ).toBeVisible();
        await expect(
            page.getByText('قائمة الأولويات', { exact: true }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('lease renewals stay direct, touch-safe, and bilingual', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.desktop);
        await page.goto('/lease-renewals?locale=en');

        await expect(
            page.getByRole('heading', { name: 'Lease renewals' }),
        ).toBeVisible();
        await expect(
            page.locator('.pmc-operations-table > .pmc-table-scroll'),
        ).toBeVisible();
        await expect(page.locator('.pmc-mobile-record-list')).toBeHidden();
        await expectNoHorizontalOverflow(page);

        await page.goto('/dashboard?locale=en');
        await expect(
            page.locator('a[href="/lease-renewals?queue=all"]'),
        ).toBeVisible();

        await page.setViewportSize(viewports.mobile);
        await page.goto('/lease-renewals?locale=en');

        await expect(
            page.locator('.pmc-operations-table > .pmc-table-scroll'),
        ).toBeHidden();
        await expect(
            page.locator('.pmc-mobile-record-card').first(),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
        await expectMinimumTouchHeight(
            page,
            [
                '.pmc-workspace-action',
                '.pmc-filter-chip',
                '.pmc-table-search .form-control',
                '.pmc-mobile-filter-trigger',
                '.pmc-mobile-record-title-link',
                '.pmc-mobile-action-menu > summary',
            ].join(', '),
        );

        await page.goto('/lease-renewals?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { name: 'تجديد العقود' }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('move-outs expose a responsive bilingual handover queue', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.desktop);
        await page.goto('/lease-move-outs?queue=all&horizon=all&locale=en');

        await expect(
            page.getByRole('heading', { level: 1, name: 'Move-outs' }),
        ).toBeVisible();
        await expect(
            page.locator('.pmc-operations-table > .pmc-table-scroll'),
        ).toBeVisible();
        await expect(page.locator('.pmc-mobile-record-list')).toBeHidden();
        await expectNoHorizontalOverflow(page);

        await page.goto('/dashboard?locale=en');
        await expect(
            page.locator('a[href^="/lease-move-outs"]').first(),
        ).toBeVisible();

        await page.setViewportSize(viewports.mobile);
        await page.goto('/lease-move-outs?queue=all&horizon=all&locale=en');

        await expect(
            page.locator('.pmc-operations-table > .pmc-table-scroll'),
        ).toBeHidden();
        await expect(
            page.locator('.pmc-mobile-record-card').first(),
        ).toBeVisible();
        await expectMinimumTouchHeight(
            page,
            [
                '.pmc-workspace-action',
                '.pmc-filter-chip',
                '.pmc-table-search .form-control',
                '.pmc-mobile-filter-trigger',
                '.pmc-mobile-record-title-link',
                '.pmc-mobile-action-menu > summary',
            ].join(', '),
        );
        await expectNoHorizontalOverflow(page);

        await page.goto('/lease-move-outs?queue=all&horizon=all&locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { level: 1, name: 'إخلاء الوحدات' }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
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

    test('opening data stays bilingual, responsive, and serves a real XLSX template', async ({
        page,
    }) => {
        for (const viewport of [
            viewports.mobile,
            viewports.tablet,
            viewports.desktop,
        ]) {
            await page.setViewportSize(viewport);
            await page.goto('/opening-data?locale=en');

            await expect(
                page.getByRole('heading', {
                    level: 1,
                    name: 'Import opening data',
                }),
            ).toBeVisible();
            await expect(page.locator('.pmc-opening-steps li')).toHaveCount(3);
            await expect(page.getByLabel('Target portfolio')).toBeVisible();
            await expect(
                page.getByText('Choose XLSX file', { exact: true }),
            ).toBeVisible();
            await expectNoHorizontalOverflow(page);
        }

        await page.setViewportSize(viewports.mobile);
        await page.goto('/opening-data?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'استيراد البيانات الافتتاحية',
            }),
        ).toBeVisible();
        await expect(page.getByLabel('المحفظة المستهدفة')).toBeVisible();
        await expect(
            page.getByText('اختر ملف XLSX', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('لم يتم اختيار ملف', { exact: true }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        const template = await page.request.get('/opening-data/template');
        expect(template.status()).toBe(200);
        expect(template.headers()['content-type']).toContain(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
        expect((await template.body()).subarray(0, 2).toString()).toBe('PK');
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

        const purge = page.getByRole('button', {
            name: 'Purge tagged data',
        });

        if ((await purge.count()) > 0) {
            await purge.first().click();

            const dialog = page.getByRole('dialog', {
                name: 'Purge showcase data',
            });
            const confirmation = page.getByLabel('Confirmation text');
            await expect(dialog).toBeVisible();
            await expect(confirmation).toBeFocused();
            expect(
                await page
                    .locator('body')
                    .evaluate((node) => node.style.overflow),
            ).toBe('hidden');

            await page.keyboard.press('Escape');
            await expect(dialog).toHaveCount(0);
            await expect(purge.first()).toBeFocused();
        } else {
            await expect(
                page.getByRole('button', {
                    name: 'Generate showcase dataset',
                }),
            ).toBeVisible();
        }

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

    test('launch readiness is measured, evidence-led, responsive, and bilingual', async ({
        page,
    }) => {
        for (const viewport of [viewports.mobile, viewports.desktop]) {
            await page.setViewportSize(viewport);
            await page.goto('/system/readiness?locale=en');

            await expect(
                page.getByRole('heading', {
                    level: 1,
                    name: 'Launch readiness',
                }),
            ).toBeVisible();
            await expect(page.locator('.pmc-readiness-check')).toHaveCount(16);
            await expect(
                page.locator('.pmc-readiness-check-detail'),
            ).toHaveCount(8);
            await expect(
                page.getByText('Hostinger cron command'),
            ).toBeVisible();
            await expect(
                page.getByText('Set up the Hostinger scheduler'),
            ).toBeVisible();
            await expect(
                page.getByText(
                    'Open Websites → Dashboard → Advanced → Cron Jobs in hPanel.',
                ),
            ).toBeVisible();
            await expect(
                page.getByRole('button', { name: 'Copy command' }),
            ).toBeVisible();
            const cronCommand = page.locator('.pmc-readiness-command > code');
            await expect(cronCommand).toContainText('schedule:run');
            await expect(cronCommand).not.toContainText('>');
            await expect(
                page.getByRole('link', { name: 'Open portfolio' }).first(),
            ).toBeVisible();
            await expect(
                page.getByRole('link', { name: 'Download PDF' }),
            ).toHaveAttribute(
                'href',
                /\/system\/readiness\/report\.pdf\?portfolio_id=\d+/,
            );
            await expect(
                page.getByRole('link', { name: 'Download Word' }),
            ).toHaveAttribute(
                'href',
                /\/system\/readiness\/report\.docx\?portfolio_id=\d+/,
            );
            await expect(
                page.getByRole('link', { name: 'Download Excel' }),
            ).toHaveAttribute(
                'href',
                /\/system\/readiness\/report\.xlsx\?portfolio_id=\d+/,
            );
            await expect(
                page
                    .locator('.pmc-readiness-evidence-grid')
                    .first()
                    .locator(':scope > article'),
            ).toHaveCount(4);
            await expectNoHorizontalOverflow(page);
        }

        await page.setViewportSize(viewports.mobile);
        const backupCard = page
            .locator('.pmc-readiness-evidence-grid > article')
            .filter({ hasText: 'Database backup verified' });
        const evidenceTrigger = backupCard.getByRole('button', {
            name: 'Add evidence',
        });
        await evidenceTrigger.click();

        const dialog = page.getByRole('dialog', {
            name: 'Database backup verified',
        });
        await expect(dialog).toBeVisible();
        await expect(page.getByLabel('Evidence note')).toBeFocused();
        expect(
            await page.locator('body').evaluate((node) => node.style.overflow),
        ).toBe('hidden');
        await expectNoHorizontalOverflow(page);

        await page.keyboard.press('Escape');
        await expect(dialog).toHaveCount(0);
        await expect(evidenceTrigger).toBeFocused();

        await page.goto('/system/readiness?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'جاهزية الإطلاق',
            }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', {
                name: 'سلامة البنية التشغيلية',
            }),
        ).toBeVisible();
        await expect(page.getByText('أمر Cron في Hostinger')).toBeVisible();
        await expect(page.getByText('إعداد جدولة Hostinger')).toBeVisible();
        await expect(
            page.getByText(
                'افتح المواقع ← لوحة التحكم ← متقدم ← مهام Cron في hPanel.',
            ),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'نسخ الأمر' }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'فتح المحفظة' }).first(),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'تنزيل PDF' }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'تنزيل Word' }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'تنزيل Excel' }),
        ).toBeVisible();
        await expect(page.locator('body')).not.toContainText('readiness.');
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

        if ((await page.locator('.pmc-map-cluster').count()) > 0) {
            await expect(
                page.locator('.pmc-map-cluster').first(),
            ).toBeVisible();
        }

        const records = page.getByTestId('property-map-record');
        const recordCount = await records.count();
        expect(recordCount).toBeGreaterThan(0);
        expect(recordCount).toBeLessThanOrEqual(12);

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

        const nextRecords = page.getByRole('button', {
            name: 'Next records',
        });

        if ((await nextRecords.count()) > 0) {
            await nextRecords.click();
            await expect(page.getByText(/^Page 2 of \d+$/)).toBeVisible();
            expect(await records.count()).toBeGreaterThan(0);
            expect(await records.count()).toBeLessThanOrEqual(12);
        }
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
        await expect(
            page
                .locator('.pmc-dashboard-launch-readiness')
                .getByRole('heading', { name: 'ضبط الإطلاق' }),
        ).toBeVisible();
        await expect(
            page
                .locator('.pmc-platform-composition')
                .getByRole('heading', { name: 'النطاق الفعلي للشركة' }),
        ).toBeVisible();
        const showcaseContext = page.locator('.pmc-dashboard-data-context');

        if ((await showcaseContext.count()) > 0) {
            await expect(
                showcaseContext.getByText(
                    'بيانات العرض مشمولة في جميع الإجماليات',
                ),
            ).toBeVisible();
        }

        await expectNoHorizontalOverflow(page);
    });

    test('notification inbox stays searchable, responsive, and bilingual', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/notifications?locale=en');

        await expect(
            page.getByRole('heading', { level: 1, name: 'Notifications' }),
        ).toBeVisible();
        await expect(
            page.getByPlaceholder(
                'Search titles, details, codes, or people...',
            ),
        ).toBeVisible();
        await expect(page.locator('a[href*="type=payment"]')).toContainText(
            'Payments',
        );
        await expectMinimumTouchHeight(
            page,
            '.pmc-filter-chips a, #notification-search, button[type="submit"]',
        );
        await expectNoHorizontalOverflow(page);

        await page.goto('/notifications?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { level: 1, name: 'الإشعارات' }),
        ).toBeVisible();
        await expect(
            page.getByPlaceholder(
                'ابحث في العناوين والتفاصيل والرموز والأشخاص...',
            ),
        ).toBeVisible();
        await expect(page.locator('a[href*="type=payment"]')).toContainText(
            'المدفوعات',
        );
        await expectNoHorizontalOverflow(page);
    });

    test('stateful records expose a compact next-step workflow on mobile', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);

        for (const path of [
            '/leases/1',
            '/payments/1',
            '/maintenance-requests/1',
            '/expenses/1',
        ]) {
            await page.goto(path);

            const workflow = page.locator('.pmc-resource-workflow');
            await expect(workflow).toBeVisible();
            await expect(
                workflow.locator('#pmc-resource-workflow-title'),
            ).toBeVisible();
            expect(
                await workflow.evaluate(
                    (node) =>
                        window
                            .getComputedStyle(node)
                            .gridTemplateColumns.split(' ').length,
                ),
            ).toBe(1);
            await expectNoHorizontalOverflow(page);
        }

        await page.goto('/leases/1');
        await page.getByRole('link', { name: 'Prepare renewal' }).click();
        await expect(
            page.getByRole('heading', { name: /Renew / }),
        ).toBeVisible();
        await expect(page.getByLabel('Status')).toHaveValue('draft');
        await expect(page.getByLabel('Available rentable asset')).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/leases/1?locale=ar');
        await expect(
            page.getByRole('heading', { name: 'جهّز هذا التأجير للتسليم' }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('owner tenancy onboarding stays direct and compact on mobile', async ({
        page,
    }) => {
        await page.locator('.pmc-account-trigger').click();
        await page.getByRole('menuitem', { name: 'Logout' }).click();
        await expect(page).toHaveURL(/\/$/);
        await login(page, 'owner@propertycontrol.test', 'password');
        await page.setViewportSize(viewports.mobile);

        await page.goto('/tenants/1?locale=en');
        const recordPaymentAction = page.getByRole('link', {
            name: 'Record payment',
            exact: true,
        });
        await expect(recordPaymentAction).toBeVisible();
        expect(
            (await recordPaymentAction.boundingBox())?.height ?? 0,
        ).toBeGreaterThanOrEqual(44);
        await expect(
            page.getByRole('link', { name: 'Edit tenant', exact: true }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/tenants/1?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('link', { name: 'تسجيل دفعة', exact: true }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'تعديل المستأجر', exact: true }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/dashboard?locale=en');

        await page.getByRole('link', { name: 'Start tenancy' }).click();
        await expect(
            page.getByRole('heading', {
                name: 'Add tenant and start tenancy',
            }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'Continue to lease' }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto(
            '/leases/create?tenant_profile_id=1&onboarding=1&locale=en',
        );
        await expect(
            page.getByRole('heading', { name: 'Set up the tenancy' }),
        ).toBeVisible();
        await expect(page.getByLabel('Status')).toHaveValue('draft');
        const addTenantAction = page.getByRole('link', {
            name: 'Add a new tenant',
        });
        await expect(addTenantAction).toBeVisible();
        expect(
            (await addTenantAction.boundingBox())?.height ?? 0,
        ).toBeGreaterThanOrEqual(44);
        await expectNoHorizontalOverflow(page);

        await page.goto('/leases/1?locale=en');
        const progress = page.locator('.pmc-resource-progress');
        await expect(progress).toBeVisible();
        await expect(progress.locator('li')).toHaveCount(6);
        expect(
            await progress
                .locator('ol')
                .evaluate(
                    (node) =>
                        window
                            .getComputedStyle(node)
                            .gridTemplateColumns.split(' ').length,
                ),
        ).toBe(1);
        const contractDownload = page.waitForEvent('download');
        await progress.getByRole('link', { name: 'Download contract' }).click();
        expect((await contractDownload).suggestedFilename()).toMatch(/\.pdf$/i);
        await expectNoHorizontalOverflow(page);

        await page.goto('/leases/1?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { name: 'جهّز هذا التأجير للتسليم' }),
        ).toBeVisible();
        await expect(page.locator('body')).not.toContainText(
            'Prepare this tenancy',
        );
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

        await page.locator('.pmc-mobile-record-title-link').first().click();
        const contractAction = page.getByRole('link', {
            name: 'العقد PDF',
        });
        await expect(contractAction).toHaveClass(/btn-primary/);
        expect(
            (await contractAction.boundingBox())?.height ?? 0,
        ).toBeGreaterThanOrEqual(44);
        const contractWordAction = page.getByRole('link', {
            name: 'العقد Word',
        });
        await expect(contractWordAction).toHaveAttribute(
            'href',
            /\/leases\/\d+\/contract\.docx$/,
        );
        expect(
            (await contractWordAction.boundingBox())?.height ?? 0,
        ).toBeGreaterThanOrEqual(44);
        await expect(
            page.getByText('إجمالي المستحق', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('الأيام المتبقية', { exact: true }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/leases/create?locale=ar');
        await expect(
            page.getByRole('link', { name: 'إضافة مستأجر جديد' }),
        ).toBeVisible();
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

        const detailLink = paymentCards
            .locator('.pmc-mobile-record-title-link')
            .first();
        await expect(detailLink).toBeVisible();
        await detailLink.click();
        const receiptAction = page.getByRole('link', {
            name: 'تنزيل الإيصال',
        });
        await expect(receiptAction).toHaveClass(/btn-primary/);
        expect(
            (await receiptAction.boundingBox())?.height ?? 0,
        ).toBeGreaterThanOrEqual(44);
        await expect(
            page.getByRole('link', { name: 'مراجعة الدفعة' }),
        ).toHaveClass(/btn-outline-secondary/);
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
        await expect(
            page.getByRole('option', { name: 'البيانات المالية' }),
        ).toHaveCount(0);
        await expect(
            page.getByText('سجل الدفعة', { exact: true }),
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
        await expectPagedMobileCards(page, 10);
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
        await expectPagedMobileCards(page, 10);
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
        await page.locator('.pmc-mobile-filter-trigger').click();
        const documentFilters = page.locator('.pmc-table-filter-panel');
        await expect(
            documentFilters.getByRole('combobox', {
                name: 'العقار',
                exact: true,
            }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await cards.locator('.pmc-mobile-record-title-link').first().click();
        await expect(
            page.getByText('تفاصيل المستند', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('سجل الملف', { exact: true }),
        ).toBeVisible();
        const downloadAction = page.getByRole('link', {
            name: 'تنزيل PDF',
        });
        await expect(downloadAction).toBeVisible();
        await expect(downloadAction).toHaveClass(/btn-primary/);
        expect(
            (await downloadAction.boundingBox())?.height ?? 0,
        ).toBeGreaterThanOrEqual(44);
        await expect(
            page.getByRole('link', { name: 'تعديل المستند' }),
        ).toHaveClass(/btn-outline-secondary/);
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
        const pendingSignoff = page.getByRole('button', {
            name: /بانتظار اعتماد المستأجر/,
        });
        await expect(pendingSignoff).toBeVisible();
        await pendingSignoff.click();
        await expect(page).toHaveURL(/confirmation=pending/);
        expect(await cards.count()).toBeGreaterThan(0);
        await expect(cards.first()).toContainText('بانتظار اعتماد المستأجر');
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
        const addPhotos = page.getByRole('link', { name: 'إضافة صور' });
        await expect(addPhotos).toBeVisible();
        expect(
            (await addPhotos.boundingBox())?.height ?? 0,
        ).toBeGreaterThanOrEqual(44);
        const editableReport = page.getByRole('link', {
            name: 'تنزيل التقرير القابل للتعديل DOCX',
        });
        await expect(editableReport).toBeVisible();
        expect(
            (await editableReport.boundingBox())?.height ?? 0,
        ).toBeGreaterThanOrEqual(44);
        await expect(
            page.getByRole('link', { name: 'تنزيل تقرير الخدمة PDF' }),
        ).toBeVisible();
        const addPhotosHref = await addPhotos.getAttribute('href');
        expect(addPhotosHref).toBeTruthy();
        await page.goto(`${addPhotosHref}?locale=ar`);
        const evidenceInput = page.getByLabel(/^صور توثيق العطل/);
        await expect(evidenceInput).toHaveAttribute('multiple', '');
        await expect(evidenceInput).toHaveAttribute(
            'accept',
            '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp',
        );
        await expectNoHorizontalOverflow(page);

        await page.goto('/maintenance-requests/create?locale=ar');
        await expect(
            page.getByRole('heading', { name: 'إنشاء طلب' }),
        ).toBeVisible();
        await expect(page.getByLabel(/^الأصل/)).toBeVisible();
        await expect(page.getByLabel(/^المستأجر/)).toBeVisible();
        await expect(page.getByLabel(/^وصف المشكلة/)).toBeVisible();
        await expect(page.getByLabel(/^صور توثيق العطل/)).toHaveAttribute(
            'multiple',
            '',
        );
        await expectNoHorizontalOverflow(page);
    });

    test('contractor directory and create form stay responsive and bilingual', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/maintenance-vendors?locale=en&per_page=10');

        await expect(
            page.getByRole('heading', { level: 1, name: 'Contractors' }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'Create contractor' }).first(),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/maintenance-vendors/create?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { name: 'إنشاء مقاول' }),
        ).toBeVisible();
        await expect(page.getByLabel(/^المحفظة/)).toBeVisible();
        await expect(page.getByLabel(/^اسم الشركة/)).toBeVisible();
        await expect(page.getByLabel(/^فئة الخدمة/)).toBeVisible();
        await expect(page.getByLabel(/^اسم جهة الاتصال/)).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('work order register is responsive, bilingual, and opens accountable job records', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/maintenance-work-orders?locale=ar&per_page=10');

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', { level: 1, name: 'أوامر العمل' }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'قائمة طلبات الصيانة' }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'دليل المقاولين' }),
        ).toBeVisible();
        const cards = page.locator('.pmc-mobile-record-card');
        const cardCount = await cards.count();
        expect(cardCount).toBeGreaterThan(0);
        expect(cardCount).toBeLessThanOrEqual(10);
        await expect(page.locator('body')).not.toContainText('work_orders.');
        await expectNoHorizontalOverflow(page);

        const detailLink = cards
            .locator('a[href^="/maintenance-work-orders/"]')
            .first();
        await expect(detailLink).toBeVisible();
        const detailHref = await detailLink.getAttribute('href');
        expect(detailHref).toBeTruthy();
        await page.goto(`${detailHref}?locale=ar`);
        await expect(page.getByText('أمر عمل صيانة')).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.setViewportSize(viewports.desktop);
        await page.goto('/maintenance-work-orders?locale=en&per_page=10');
        await expect(page.locator('.pmc-table-scroll')).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'Export Excel (.xlsx)' }),
        ).toHaveAttribute('href', /exports\/maintenance-work-orders/);
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
        await expectPagedMobileCards(page, 10);
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

    test('owner property assignments stay direct, responsive, and Arabic', async ({
        page,
    }) => {
        await page.context().clearCookies();
        await login(page, localAccounts[1].email, 'password');
        await page.setViewportSize(viewports.mobile);
        await page.goto(
            `/users?search=${encodeURIComponent(localAccounts[2].email)}&per_page=10&locale=en`,
        );

        const managerLink = page
            .locator('.pmc-mobile-record-card a[href^="/users/"]')
            .first();
        await expect(managerLink).toBeVisible();
        const managerHref = await managerLink.getAttribute('href');
        expect(managerHref).toBeTruthy();
        await page.goto(`${managerHref}?locale=en`);

        const assignmentLink = page.getByRole('link', {
            name: 'Manage property assignments',
        });
        await expect(assignmentLink).toBeVisible();
        await expect(assignmentLink).toHaveClass(/btn-primary/);
        expect(
            (await assignmentLink.boundingBox())?.height ?? 0,
        ).toBeGreaterThanOrEqual(44);
        await page.locator('.pmc-resource-action-menu summary').click();
        await expect(page.getByRole('link', { name: 'Edit user' })).toHaveClass(
            /btn-outline-secondary/,
        );
        const assignmentHref = await assignmentLink.getAttribute('href');
        expect(assignmentHref).toBeTruthy();

        await page.goto(
            `/users?search=${encodeURIComponent(localAccounts[3].email)}&per_page=10&locale=ar`,
        );
        const tenantUserLink = page
            .locator('.pmc-mobile-record-card a[href^="/users/"]')
            .first();
        await expect(tenantUserLink).toBeVisible();
        const tenantUserHref = await tenantUserLink.getAttribute('href');
        expect(tenantUserHref).toBeTruthy();
        await page.goto(`${tenantUserHref}?locale=ar`);
        const tenantProfileLink = page.getByRole('link', {
            name: 'فتح ملف المستأجر',
        });
        await expect(tenantProfileLink).toHaveClass(/btn-primary/);
        expect(
            (await tenantProfileLink.boundingBox())?.height ?? 0,
        ).toBeGreaterThanOrEqual(44);
        await page.locator('.pmc-resource-action-menu summary').click();
        await expect(
            page.getByRole('link', { name: 'تعديل المستخدم' }),
        ).toHaveClass(/btn-outline-secondary/);
        await expectNoHorizontalOverflow(page);

        await page.goto(`${assignmentHref}?locale=ar`);

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(page.getByText('صلاحيات مدير العقار')).toBeVisible();
        await expect(page.getByText('يشمل التعيين الواحد')).toBeVisible();
        await expect(
            page.getByPlaceholder('اسم العقار أو الرمز أو العقار الأب'),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'حفظ التعيينات' }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        const results = await new AxeBuilder({ page })
            .withTags(['wcag2a', 'wcag2aa', 'wcag21aa'])
            .analyze();
        expect(results.violations).toEqual([]);

        await page.setViewportSize(viewports.desktop);
        await expectNoHorizontalOverflow(page);
        expect(
            await page
                .locator('.pmc-property-assignment-search input')
                .evaluate((node) => node.getBoundingClientRect().height),
        ).toBeGreaterThanOrEqual(44);
    });

    test('owner can hand off tenant portal access securely on mobile', async ({
        context,
        page,
    }) => {
        await page.context().clearCookies();
        await context.grantPermissions(['clipboard-read', 'clipboard-write']);
        await login(page, localAccounts[1].email, 'password');
        await page.setViewportSize(viewports.mobile);
        await page.goto(
            `/users?search=${encodeURIComponent(localAccounts[3].email)}&per_page=10&locale=ar`,
        );

        const tenantUserLink = page
            .locator('.pmc-mobile-record-card a[href^="/users/"]')
            .first();
        await expect(tenantUserLink).toBeVisible();
        const tenantUserHref = await tenantUserLink.getAttribute('href');
        expect(tenantUserHref).toBeTruthy();
        await page.goto(`${tenantUserHref}?locale=ar`);

        const portalAccess = page.getByRole('link', {
            name: 'صلاحية البوابة',
        });
        await expect(portalAccess).toBeVisible();
        expect(
            (await portalAccess.boundingBox())?.height ?? 0,
        ).toBeGreaterThanOrEqual(44);
        await portalAccess.click();

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', {
                name: /صلاحية بوابة/,
                level: 1,
            }),
        ).toBeVisible();
        await expect(
            page.getByRole('heading', {
                name: 'إنشاء رابط إعداد لمرة واحدة',
            }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.getByRole('button', { name: 'إنشاء رابط الإعداد' }).click();
        const linkInput = page.getByLabel('رابط الإعداد');
        await expect(linkInput).toHaveValue(/\/reset-password\//);
        await expect(
            page.getByRole('link', { name: 'فتح الرابط' }),
        ).toBeVisible();

        await page.getByRole('button', { name: 'نسخ الرابط' }).click();
        await expect(
            page.getByRole('button', { name: 'تم نسخ الرابط' }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        const results = await new AxeBuilder({ page })
            .withTags(['wcag2a', 'wcag2aa', 'wcag21aa'])
            .analyze();
        expect(results.violations).toEqual([]);

        await page.setViewportSize(viewports.desktop);
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

        const livePortfolioCard = portfolioCards
            .filter({ hasNotText: 'بيانات استعراضية' })
            .first();
        const portfolioDetailLink = livePortfolioCard
            .locator('a[href^="/portfolios/"]')
            .first();
        await expect(portfolioDetailLink).toBeVisible();
        const portfolioHref = await portfolioDetailLink.getAttribute('href');
        expect(portfolioHref).toBeTruthy();
        const portfolioId = portfolioHref?.split('/').filter(Boolean).pop();
        expect(portfolioId).toBeTruthy();

        await page.goto(`${portfolioHref}?locale=ar`);
        await expect(page.getByText('حساب المحفظة')).toBeVisible();
        const createAssetAction = page.getByRole('link', {
            name: 'إنشاء أصل',
            exact: true,
        });
        await expect(createAssetAction).toBeVisible();
        expect(
            (await createAssetAction.boundingBox())?.height ?? 0,
        ).toBeGreaterThanOrEqual(44);
        await expect(
            page.getByRole('link', { name: 'إنشاء مستخدم', exact: true }),
        ).toBeVisible();
        await expect(page.getByText('ملف النشاط')).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/portfolios/1?locale=ar');
        const completedSetup = page.locator(
            '.pmc-resource-progress[data-progress-complete="true"]',
        );
        await expect(completedSetup).toBeVisible();
        const setupToggle = completedSetup.locator(
            '[data-resource-progress-toggle]',
        );
        await expect(setupToggle).toBeVisible();
        await expect(setupToggle).toHaveAccessibleName('عرض خطوات الإعداد');
        await expect(setupToggle).toHaveAttribute('aria-expanded', 'false');
        expect(
            (await setupToggle.boundingBox())?.height ?? 0,
        ).toBeGreaterThanOrEqual(44);
        await expect(
            completedSetup.locator('.pmc-resource-progress-details'),
        ).not.toBeVisible();
        await setupToggle.click();
        await expect(setupToggle).toHaveAccessibleName('إخفاء خطوات الإعداد');
        await expect(setupToggle).toHaveAttribute('aria-expanded', 'true');
        await expect(
            completedSetup.locator('.pmc-resource-progress-details'),
        ).toBeVisible();
        await expect(completedSetup.locator('li')).toHaveCount(6);
        await setupToggle.click();
        await expect(
            completedSetup.locator('.pmc-resource-progress-details'),
        ).not.toBeVisible();
        await expect(
            page.getByRole('link', { name: 'مراجعة العقارات' }),
        ).toHaveAttribute('href', /portfolio_id=1/);
        await expect(
            page.getByRole('link', { name: 'مراجعة الدفعات المرحلة' }),
        ).toHaveAttribute('href', /portfolio_id=1.*status=posted/);
        await expect(
            page.getByRole('link', { name: 'فتح كشف التشغيل' }),
        ).toHaveAttribute('href', /portfolio_id=1/);
        await expectNoHorizontalOverflow(page);

        await page.goto(
            `/users/create?portfolio_id=${portfolioId}&role=property_manager&setup_portfolio_id=${portfolioId}&locale=ar`,
        );
        await expect(
            page.getByRole('heading', {
                name: /إضافة مدير عقار إلى/,
            }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'العودة إلى إعداد المحفظة' }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', {
                name: 'إنشاء مدير عقار والمتابعة',
            }),
        ).toBeVisible();
        const setupPortfolio = page.getByLabel(/^المحفظة/);
        await expect(setupPortfolio.locator('option')).toHaveCount(1);
        await expect(setupPortfolio).toHaveValue(String(portfolioId));
        await expectNoHorizontalOverflow(page);

        await page.goto(
            `/assets/building-setup?portfolio_id=${portfolioId}&setup_portfolio_id=${portfolioId}&locale=ar`,
        );
        await expect(
            page.getByRole('heading', { name: /تسجيل أول عقار في/ }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'العودة إلى إعداد المحفظة' }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'إنشاء الهيكل والمتابعة' }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'إنشاء أصل منفرد' }),
        ).toHaveCount(0);
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
            const openImageAction = page.getByRole('link', {
                name: 'فتح الصورة',
            });
            await expect(openImageAction).toHaveClass(/btn-primary/);
            expect(
                (await openImageAction.boundingBox())?.height ?? 0,
            ).toBeGreaterThanOrEqual(44);
            await expect(
                page.getByRole('link', { name: 'تعديل الوسائط' }),
            ).toHaveClass(/btn-outline-secondary/);
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

            if (viewport.width < 768) {
                const guideRow = page.locator('.pmc-doc-guide-grid');
                const workflowRow = page.locator('.pmc-doc-workflow-grid');
                const shortcuts = page.locator('.pmc-doc-shortcut-list');
                await expect(guideRow).toBeVisible();
                await expect(workflowRow).toBeVisible();
                expect(
                    await guideRow.evaluate(
                        (node) => node.scrollWidth > node.clientWidth,
                    ),
                ).toBe(true);
                expect(
                    await workflowRow.evaluate(
                        (node) => node.scrollWidth > node.clientWidth,
                    ),
                ).toBe(true);
                expect(
                    await shortcuts.evaluate(
                        (node) =>
                            getComputedStyle(node).gridTemplateColumns.split(
                                ' ',
                            ).length,
                    ),
                ).toBe(2);
                expect(
                    await page.evaluate(
                        () => document.documentElement.scrollHeight,
                    ),
                ).toBeLessThan(3000);
            }

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
        await expect(
            page.getByRole('heading', {
                name: 'اختر الإجابة التي تحتاجها',
            }),
        ).toBeVisible();
        await expect(page.locator('.pmc-report-library-tabs')).toBeVisible();
        await expect(
            page.locator('.pmc-report-library-tabs [role="tab"]'),
        ).toHaveCount(4);
        await expect(page.locator('.pmc-report-library-card')).toHaveCount(3);
        await expect(
            page
                .locator('.pmc-report-card-scope')
                .getByText('الفترة المحددة', { exact: true }),
        ).toHaveCount(2);
        await expect(
            page
                .locator('.pmc-report-card-scope')
                .getByText('نطاق المحفظة', { exact: true }),
        ).toHaveCount(3);
        await expect(
            page
                .locator('.pmc-report-card-scope')
                .getByText('نطاق العقار', { exact: true }),
        ).toHaveCount(3);
        await expect(
            page
                .locator('.pmc-report-library-card')
                .filter({ hasText: 'سجل الإيجارات' }),
        ).toHaveCount(1);
        await expect(
            page.locator('a[href^="/reports/statement.pdf"]'),
        ).toBeVisible();
        await expect(
            page.locator('a[href^="/reports/statement.docx"]'),
        ).toBeVisible();
        await page.getByRole('tab', { name: 'التحصيل والتكاليف 3' }).click();
        await expect(page).toHaveURL(/library_group=finance/);
        await expect(page.locator('.pmc-report-library-card')).toHaveCount(3);
        expect(
            await page.evaluate(() => document.documentElement.scrollHeight),
        ).toBeLessThan(2200);
        await page.getByRole('button', { name: 'إظهار التصفيات' }).click();
        await expect(page.locator('#report-filter-panel')).toBeVisible();
        await expect(page.getByLabel('التاريخ من')).toBeVisible();
        await expect(page.getByLabel('التاريخ إلى')).toBeVisible();
        await page.getByLabel('فترة التقرير').selectOption('this_month');
        await expect(page.getByLabel('التاريخ من')).toHaveCount(0);
        await expect(page.getByLabel('التاريخ إلى')).toHaveCount(0);
        await expect(
            page.getByText('تتحدث التواريخ تلقائياً كلما تم فتح هذا التقرير.'),
        ).toBeVisible();
        await page.getByTestId('report-property-filter').click();
        await page.locator('[data-property-scope-search]').fill('Rose');
        await expect(
            page
                .locator('[data-property-scope-option]')
                .filter({ hasText: 'ROSE-TOWER' }),
        ).toBeVisible();
        await page
            .locator('[data-property-scope-option]')
            .filter({ hasText: 'ROSE-TOWER' })
            .click();
        await page.getByRole('button', { name: 'تطبيق', exact: true }).click();
        await expect(page).toHaveURL(/period=this_month/);
        await expect(page).toHaveURL(/library_group=finance/);
        await expect(page.locator('.pmc-report-library-card')).toHaveCount(3);
        await page.getByRole('tab', { name: 'حزم المالك 3' }).click();
        const propertyReportCard = page
            .locator('.pmc-report-library-card')
            .filter({ hasText: 'تقرير تشغيل العقار' });
        await expect(propertyReportCard).toHaveCount(1);
        await expect(
            propertyReportCard.getByRole('link', { name: 'تنزيل PDF' }),
        ).toBeVisible();
        await expect(
            propertyReportCard.getByRole('link', { name: 'تنزيل DOCX' }),
        ).toBeVisible();
        await expect(
            propertyReportCard.getByRole('link', { name: 'تنزيل XLSX' }),
        ).toBeVisible();
        await expect(propertyReportCard).toContainText('نطاق العقار');
        await expect(propertyReportCard).toContainText('ROSE-TOWER');
        await expect(
            propertyReportCard
                .locator('.pmc-report-card-scope > div')
                .filter({ hasText: 'نطاق المحفظة' }),
        ).toContainText('محفظة أحمد الرئيسية');

        await page.getByRole('link', { name: 'حفظ التقرير الحالي' }).click();
        await expect(page).toHaveURL(/\/reports\/saved\/create/);
        await expect(page).toHaveURL(/period=this_month/);
        await expect(page).toHaveURL(/property_id=\d+/);
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'إنشاء تقرير محفوظ',
            }),
        ).toBeVisible();
        await expect(page.getByLabel('فترة التقرير')).toHaveValue('this_month');
        await page
            .getByLabel('اسم العرض بالإنجليزية')
            .fill('Rolling property report');
        await page
            .getByLabel('اسم العرض بالعربية')
            .fill('تقرير العقار المتجدد');
        await page.getByRole('button', { name: 'إنشاء تقرير محفوظ' }).click();
        await expect(page).toHaveURL(/\/reports\/saved$/);

        const savedLibrary = page.locator('.pmc-saved-report-workspace');
        const savedCard = savedLibrary
            .locator('.pmc-report-preset-list article')
            .filter({ hasText: 'تقرير العقار المتجدد' });
        await expect(savedCard).toBeVisible();
        await expect(savedCard).toContainText('هذا الشهر');
        await expect(savedCard).toContainText('ROSE-TOWER');
        const savedDetailLink = savedCard.locator(
            '.pmc-report-preset-title',
        );
        await expect(savedDetailLink).toHaveCount(1);
        await savedDetailLink.click();
        await expect(page).toHaveURL(/\/reports\/saved\/\d+/);
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'تقرير العقار المتجدد',
            }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'تشغيل التقرير' }),
        ).toBeVisible();
        await expect(page.locator('.pmc-resource-decision-card')).toHaveCount(
            4,
        );
        await expect(page.getByText('PDF · DOCX · XLSX')).toBeVisible();
        await page
            .getByRole('combobox', { name: 'أقسام السجل' })
            .selectOption('documents');
        const savedReportFiles = page.locator('.pmc-document-strip > a');
        await expect(savedReportFiles).toHaveCount(3);
        await expect(
            savedReportFiles.filter({ hasText: 'PDF' }),
        ).toHaveAttribute('href', /operating-report\.pdf/);
        await expect(
            savedReportFiles.filter({ hasText: 'DOCX' }),
        ).toHaveAttribute('href', /operating-report\.docx/);
        await expect(
            savedReportFiles.filter({ hasText: 'XLSX' }),
        ).toHaveAttribute('href', /operating-report\.xlsx/);
        await expectNoHorizontalOverflow(page);

        await page.goto('/reports/saved?locale=ar');
        await expect(savedCard).toBeVisible();
        const savedWorkbookHref = await savedCard
            .getByRole('link', { name: 'تنزيل XLSX' })
            .getAttribute('href');
        expect(savedWorkbookHref).toContain('period=this_month');
        const savedWorkbook = await page.request.get(savedWorkbookHref!);
        expect(savedWorkbook.ok()).toBeTruthy();
        expect((await savedWorkbook.body()).subarray(0, 2).toString()).toBe(
            'PK',
        );
        await savedCard.getByText('إدارة', { exact: true }).click();
        await savedCard.getByRole('link', { name: 'تعديل' }).click();
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'تعديل التقرير المحفوظ',
            }),
        ).toBeVisible();
        await page
            .getByLabel('اسم العرض بالعربية')
            .fill('تقرير العقار المتجدد المعدل');
        await page
            .getByRole('button', { name: 'تحديث التقرير المحفوظ' })
            .click();

        const updatedCard = page
            .locator('.pmc-report-preset-list article')
            .filter({
                has: page.getByText('تقرير العقار المتجدد المعدل', {
                    exact: true,
                }),
            });
        await expect(updatedCard).toBeVisible();
        await updatedCard.getByText('إدارة', { exact: true }).click();
        await updatedCard.getByRole('button', { name: 'إنشاء نسخة' }).click();

        const duplicateCard = page
            .locator('.pmc-report-preset-list article')
            .filter({
                has: page.getByText('نسخة من تقرير العقار المتجدد المعدل', {
                    exact: true,
                }),
            });
        await expect(duplicateCard).toBeVisible();
        await duplicateCard.getByText('إدارة', { exact: true }).click();
        page.once('dialog', (dialog) => dialog.accept());
        await duplicateCard.getByRole('button', { name: 'إزالة' }).click();
        await expect(duplicateCard).toHaveCount(0);

        const updatedMenu = updatedCard.locator('.pmc-report-preset-menu');

        if ((await updatedMenu.getAttribute('open')) === null) {
            await updatedCard.getByText('إدارة', { exact: true }).click();
        }

        page.once('dialog', (dialog) => dialog.accept());
        await updatedCard.getByRole('button', { name: 'إزالة' }).click();
        await expect(updatedCard).toHaveCount(0);
        await expect(
            page.getByRole('heading', {
                name: 'لا توجد تقارير محفوظة بعد',
            }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);

        await page.goto('/reports?locale=ar&period=this_month');

        await page
            .getByRole('button', { name: 'نظرة عامة', exact: true })
            .click();
        await expect(
            page.getByRole('heading', { name: 'ما الذي تغير' }),
        ).toBeVisible();
        await expect(page.locator('.pmc-report-comparison-card')).toHaveCount(
            2,
        );
        await expect(
            page.locator('.pmc-report-comparison__period'),
        ).toContainText('مقارنة بالفترة');
        const collectedSource = page.getByRole('link', {
            name: 'فتح سجلات المحصل',
        });
        await expect(collectedSource).toHaveCount(1);
        await expect(collectedSource).toHaveAttribute(
            'href',
            /\/payments\?.*date_from=.*date_to=.*status=posted/,
        );
        const previousPeriodLinks = page.getByRole('link', {
            name: 'مراجعة الفترة السابقة',
        });
        await expect(previousPeriodLinks).toHaveCount(2);
        const previousPeriodHref = await previousPeriodLinks
            .first()
            .getAttribute('href');
        expect(previousPeriodHref).toContain('/reports?');
        expect(previousPeriodHref).toContain('period=custom');
        expect(previousPeriodHref).toContain('tab=overview');
        await expectNoHorizontalOverflow(page);

        for (const tab of ['التحصيل', 'التكاليف', 'التشغيل']) {
            await page.getByRole('button', { name: tab, exact: true }).click();
            await expectNoHorizontalOverflow(page);
        }

        await expect(
            page.getByRole('heading', { name: 'السجل التشغيلي' }),
        ).toBeVisible();
        await expect(page.locator('.pmc-report-journal')).toBeVisible();
        await expect(page.locator('.pmc-report-record-grid')).toBeVisible();
        await expect(page.locator('.pmc-table-scroll')).toHaveCount(0);

        const workbook = await page.request.get('/reports/export');
        expect(workbook.ok()).toBeTruthy();
        expect(workbook.headers()['content-type']).toContain(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
        expect((await workbook.body()).subarray(0, 2).toString()).toBe('PK');

        await page.setViewportSize(viewports.desktop);
        await page.goto('/reports?locale=en');
        await expect(
            page.locator('.pmc-report-library-grid').first(),
        ).toBeVisible();
        await page
            .getByRole('button', { name: 'Overview', exact: true })
            .click();
        await expect(page.locator('.pmc-report-pulse-grid')).toBeVisible();
        await page
            .getByRole('button', { name: 'Operations', exact: true })
            .click();
        await expect(
            page.getByRole('heading', { name: 'Operational journal' }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('rent roll keeps vacancies, contract positions, and exports usable at every width', async ({
        page,
    }) => {
        test.setTimeout(60_000);

        for (const viewport of breakpoints) {
            await page.setViewportSize(viewport);
            await page.goto('/reports/rent-roll?locale=ar');

            await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
            await expect(
                page.getByRole('heading', {
                    level: 1,
                    name: 'سجل الإيجارات',
                }),
            ).toBeVisible();
            await expect(page.locator('.pmc-rent-roll-scope')).toBeVisible();
            await expect(page.locator('.pmc-metric-card')).toHaveCount(4);
            await expect(
                page.locator('.pmc-rent-roll-financials'),
            ).toBeVisible();
            await expect(
                page.locator('.pmc-table-search input[type="search"]'),
            ).toBeVisible();

            if (viewport.width < 992) {
                await expect(page.locator('.pmc-table-scroll')).toBeHidden();
                await expect(
                    page.locator('.pmc-mobile-record-card').first(),
                ).toBeVisible();
            } else {
                await expect(page.locator('.pmc-table-scroll')).toBeVisible();
            }

            await expectNoHorizontalOverflow(page);
        }

        await page.setViewportSize(viewports.mobile);
        await page.goto('/reports/rent-roll?locale=ar');
        await page.locator('.pmc-mobile-filter-trigger').click();
        await expect(page.getByLabel('حالة التأجير')).toBeVisible();
        await page.getByLabel('حالة التأجير').selectOption('vacant');
        await expect(page).toHaveURL(/state=vacant/);
        await expect(page.locator('.pmc-filter-chip.active')).toContainText(
            'شاغرة',
        );
        await expectNoHorizontalOverflow(page);

        for (const [path, contentType, signature] of [
            ['/reports/rent-roll.pdf', 'application/pdf', '%PDF-'],
            [
                '/reports/rent-roll.docx',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'PK',
            ],
            [
                '/reports/rent-roll.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'PK',
            ],
        ] as const) {
            const response = await page.request.get(path);
            expect(response.ok()).toBeTruthy();
            expect(response.headers()['content-type']).toContain(contentType);
            expect(
                (await response.body())
                    .subarray(0, signature.length)
                    .toString(),
            ).toBe(signature);
        }
    });

    test('property operating report keeps one building scope across mobile drill-downs and downloads', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/portfolio-control?locale=ar');
        const reportLink = page
            .locator('a[href^="/reports/properties/"]')
            .first();
        await expect(reportLink).toBeVisible();
        const reportHref = await reportLink.getAttribute('href');
        expect(reportHref).toBeTruthy();

        await page.goto(`${reportHref}?locale=ar`);
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: /تقرير/,
            }),
        ).toBeVisible();
        await expect(
            page.locator('.pmc-property-report-context'),
        ).toBeVisible();
        await expect(
            page.locator('.pmc-property-report-structure article'),
        ).toHaveCount(7);
        await expect(page.getByLabel('التاريخ من')).toBeVisible();
        await expect(page.getByLabel('التاريخ إلى')).toBeVisible();

        for (const tab of ['نظرة عامة', 'التحصيل', 'التكاليف', 'التشغيل']) {
            await page.getByRole('button', { name: tab, exact: true }).click();
            await expectNoHorizontalOverflow(page);
        }

        const workbookLink = page
            .locator('a[href*="/operating-report.xlsx"]')
            .first();
        const workbookHref = await workbookLink.getAttribute('href');
        expect(workbookHref).toContain('/reports/properties/');
        const workbook = await page.request.get(workbookHref!);
        expect(workbook.ok()).toBeTruthy();
        expect(workbook.headers()['content-type']).toContain(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
        expect((await workbook.body()).subarray(0, 2).toString()).toBe('PK');

        for (const [selector, contentType, signature] of [
            ['a[href*="/operating-report.pdf"]', 'application/pdf', '%PDF-'],
            [
                'a[href*="/operating-report.docx"]',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'PK',
            ],
        ] as const) {
            const href = await page
                .locator(selector)
                .first()
                .getAttribute('href');
            const response = await page.request.get(href!);
            expect(response.ok()).toBeTruthy();
            expect(response.headers()['content-type']).toContain(contentType);
            expect(
                (await response.body())
                    .subarray(0, signature.length)
                    .toString(),
            ).toBe(signature);
        }

        const accessibility = await new AxeBuilder({ page })
            .withTags(['wcag2a', 'wcag2aa', 'wcag21aa'])
            .analyze();
        expect(accessibility.violations).toEqual([]);

        await page.setViewportSize(viewports.desktop);
        await page.reload();
        await expect(page.locator('.pmc-property-report-period')).toBeVisible();
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

    test('email delivery control explains transport evidence in Arabic, mobile cards, and XLSX', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/system/email-delivery?locale=ar');

        await expect(
            page.getByRole('heading', {
                name: 'إرسال البريد',
                exact: true,
            }),
        ).toBeVisible();
        await expect(page.locator('.pmc-metric-card')).toHaveCount(4);
        await expect(page.locator('.pmc-table-scroll')).toBeHidden();
        await expect(
            page.locator('.pmc-mobile-record-card').first(),
        ).toBeVisible();

        await page.locator('.pmc-mobile-filter-trigger').click();
        await expect(page.getByLabel('نوع البريد')).toBeVisible();
        await expect(page.getByLabel('التاريخ من')).toBeVisible();
        await expect(page.getByLabel('التاريخ إلى')).toBeVisible();
        await expectNoHorizontalOverflow(page);

        const acceptedCard = page
            .locator('.pmc-mobile-record-card')
            .filter({ has: page.getByText('مقبول', { exact: true }) });
        await acceptedCard.locator('.pmc-mobile-record-title-link').click();
        await expect(
            page.getByText('دليل الرسالة', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('دليل الناقل', { exact: true }),
        ).toBeVisible();
        await expect(page.locator('body')).toContainText(
            'ولا يثبت وصولها إلى صندوق الوارد',
        );
        await expectNoHorizontalOverflow(page);

        const workbook = await page.request.get(
            '/system/email-delivery/export?locale=ar',
        );
        expect(workbook.ok()).toBeTruthy();
        expect(workbook.headers()['content-type']).toContain(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
        expect((await workbook.body()).subarray(0, 2).toString()).toBe('PK');

        const accessibility = await new AxeBuilder({ page })
            .withTags(['wcag2a', 'wcag2aa', 'wcag21aa'])
            .analyze();
        expect(accessibility.violations).toEqual([]);

        await page.setViewportSize(viewports.desktop);
        await page.goto('/system/email-delivery?locale=en');
        await expect(page.locator('.pmc-table-scroll')).toBeVisible();
        await expect(page.locator('.pmc-data-table')).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('backup control stays compact, bilingual, and accessible', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/system/backups?locale=ar');

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'إدارة النسخ الاحتياطية',
            }),
        ).toBeVisible();
        await expect(page.locator('.pmc-backup-command')).toBeVisible();
        await expect(page.locator('.pmc-metric-card')).toHaveCount(4);
        await expect(page.locator('.pmc-backup-history')).toBeVisible();
        await expect(page.locator('.pmc-console-topbar')).toHaveCSS(
            'height',
            '64px',
        );
        await expectMinimumTouchHeight(
            page,
            '.pmc-backup-command .btn, .pmc-backup-history select',
        );
        await expectNoHorizontalOverflow(page);

        const accessibility = await new AxeBuilder({ page })
            .withTags(['wcag2a', 'wcag2aa', 'wcag21aa'])
            .analyze();
        expect(accessibility.violations).toEqual([]);

        await page.setViewportSize(viewports.desktop);
        await page.goto('/system/backups?locale=en');
        await expect(
            page.getByRole('heading', {
                level: 1,
                name: 'Backup Control',
            }),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

    test('wording workspace keeps editing focused, responsive, and Arabic', async ({
        page,
    }) => {
        await page.setViewportSize(viewports.mobile);
        await page.goto('/wording');
        await expect(page.locator('.pmc-wording-row').first()).toBeVisible();
        await expect(page.locator('.pmc-wording-row')).toHaveCount(10);
        await expect(page.getByLabel('Rows per page')).toHaveValue('10');
        expect(
            await page.evaluate(() => document.documentElement.scrollHeight),
        ).toBeLessThan(3200);
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

        await page.getByLabel('Rows per page').selectOption('25');
        await expect(page).toHaveURL(/per_page=25/);
        await expect(page.locator('.pmc-wording-row')).toHaveCount(25);

        await page.goto('/wording?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(page.getByLabel('عدد الصفوف في الصفحة')).toHaveValue('10');
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
                '/company-control',
                '/action-center',
                '/profile',
                '/assets',
                '/property-explorer',
                '/property-map',
                '/rent-collection',
                '/lease-renewals',
                '/reports',
                '/audit-logs',
                '/media-files',
                '/media-files/create',
                '/cms',
                '/cms/sections/create',
                '/wording',
                '/system/showcase-data',
                '/system/readiness',
                '/system/email-delivery',
                '/system/backups',
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
            visible: [
                '/action-center',
                '/company-control',
                '/property-explorer',
                '/rent-collection',
                '/lease-renewals',
                '/cms',
                '/wording',
                '/system/showcase-data',
                '/system/readiness',
                '/system/email-delivery',
                '/system/backups',
            ],
            hidden: [] as string[],
        },
        owner: {
            visible: [
                '/action-center',
                '/property-explorer',
                '/lease-renewals',
                '/rent-collection',
                '/payments',
                '/users',
            ],
            hidden: [
                '/cms',
                '/company-control',
                '/wording',
                '/system/showcase-data',
                '/system/readiness',
                '/system/email-delivery',
                '/system/backups',
            ],
        },
        manager: {
            visible: [
                '/action-center',
                '/property-explorer',
                '/lease-renewals',
                '/rent-collection',
                '/payments',
                '/users',
            ],
            hidden: [
                '/cms',
                '/company-control',
                '/wording',
                '/system/showcase-data',
                '/system/readiness',
                '/system/email-delivery',
                '/system/backups',
            ],
        },
        tenant: {
            visible: ['/dashboard', '/maintenance-requests', '/documentation'],
            hidden: [
                '/action-center',
                '/company-control',
                '/property-explorer',
                '/lease-renewals',
                '/rent-collection',
                '/payments',
                '/users',
                '/cms',
                '/system/readiness',
                '/system/email-delivery',
                '/system/backups',
            ],
        },
    } as const;

    test('dashboard uses one global property scope and stays usable in Arabic', async ({
        page,
    }) => {
        await login(
            page,
            process.env.E2E_SUPERADMIN_EMAIL ?? localAccounts[0].email,
            process.env.E2E_PASSWORD ?? 'password',
        );
        await page.setViewportSize(viewports.mobile);
        await page.goto('/dashboard?locale=en');

        await expect(page.locator('#dashboard-property-focus')).toHaveCount(0);
        await page.locator('.pmc-menu-trigger').click();
        const trigger = page.locator('[data-property-scope-trigger]');
        await trigger.click();
        const options = page.locator('[data-property-scope-option]');
        expect(await options.count()).toBeGreaterThan(0);
        const propertyId = await options
            .first()
            .getAttribute('data-property-scope-option');
        expect(propertyId).toBeTruthy();

        await options.first().click();
        await expect(page).toHaveURL(
            new RegExp(`[?&]property_id=${propertyId}(?:&|$)`),
        );
        await expect(page.locator('.pmc-dashboard-focus-action')).toBeVisible();
        await expect(page.locator('.pmc-metric-card').first()).toHaveAttribute(
            'href',
            new RegExp(`[?&]property_id=${propertyId}(?:&|$)`),
        );
        await expectMinimumTouchHeight(
            page,
            '[data-property-scope-trigger], .pmc-dashboard-focus-action',
        );
        await expectNoHorizontalOverflow(page);

        await page.locator('.pmc-menu-trigger').click();
        await trigger.click();
        await page.locator('[data-property-scope-clear]').click();
        await expect(trigger).toHaveAttribute('data-selected-property', 'all');
        await expect(page.locator('.pmc-dashboard-focus-action')).toHaveCount(
            0,
        );

        await page.goto('/dashboard?locale=ar');
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await page.locator('.pmc-menu-trigger').click();
        await expect(
            page.locator('.pmc-property-context').getByText('نطاق العقار'),
        ).toBeVisible();
        await trigger.click();
        await expect(
            page.locator('[data-property-scope-clear] strong'),
        ).toHaveText('جميع العقارات');
        await page.keyboard.press('Escape');
        await expect(
            page.locator('[data-property-scope-dialog]'),
        ).not.toBeVisible();
        await page.keyboard.press('Escape');
        await expect(page.locator('.pmc-console-sidebar')).toHaveAttribute(
            'aria-hidden',
            'true',
        );
        const systemGroup = page.locator('[data-dashboard-group="system"]');
        await systemGroup.locator(':scope > button').click();
        await expect(
            systemGroup.getByText('آخر التغييرات عبر العملاء'),
        ).toBeVisible();
        await expectNoHorizontalOverflow(page);
    });

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

            if (account.role === 'tenant') {
                await expect(
                    page.locator('[data-property-scope-trigger]'),
                ).toHaveCount(0);
            } else {
                await expect(
                    page.locator('[data-property-scope-trigger]'),
                ).toBeVisible();
            }

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
            await page.keyboard.press('Escape');
            await expect(navigation).not.toHaveClass(/\bis-open\b/);

            if (account.role === 'superadmin') {
                expect(
                    await page.evaluate(
                        () => document.documentElement.scrollHeight,
                    ),
                ).toBeLessThan(3500);
                const systemGroup = page.locator(
                    '[data-dashboard-group="system"]',
                );
                const systemToggle = systemGroup.locator(':scope > button');
                await expect(systemToggle).toHaveAttribute(
                    'aria-expanded',
                    'false',
                );
                await systemToggle.click();
                await expect(
                    page.locator('.pmc-dashboard-launch-readiness'),
                ).toBeVisible();
                await expect(
                    page.locator('.pmc-platform-composition'),
                ).toBeVisible();
                await expect(
                    page.locator('.pmc-platform-activity'),
                ).toBeVisible();
                expect(
                    await page.locator('[data-platform-activity]').count(),
                ).toBeGreaterThan(0);
                await expectMinimumTouchHeight(
                    page,
                    '.pmc-platform-composition-grid nav a, [data-platform-activity]',
                );
            } else {
                await expect(
                    page.locator('.pmc-dashboard-launch-readiness'),
                ).toHaveCount(0);
                await expect(
                    page.locator('.pmc-platform-composition'),
                ).toHaveCount(0);
                await expect(
                    page.locator('.pmc-platform-activity'),
                ).toHaveCount(0);
            }

            if (account.role !== 'tenant') {
                const todayGroup = page.locator(
                    '[data-dashboard-group="today"]',
                );
                const portfolioGroup = page.locator(
                    '[data-dashboard-group="portfolio"]',
                );
                await expect(
                    todayGroup.locator(':scope > button'),
                ).toHaveAttribute('aria-expanded', 'true');
                const workTabs = todayGroup.locator(
                    '.pmc-dashboard-today-tabs button',
                );
                await expect(workTabs).toHaveCount(4);
                await expect(
                    todayGroup.locator('[data-dashboard-work-panel="actions"]'),
                ).toBeVisible();
                await expect(
                    todayGroup.locator(
                        '[data-dashboard-work-panel="collections"]',
                    ),
                ).toBeHidden();
                await todayGroup
                    .locator('[data-dashboard-work-tab="collections"]')
                    .click();
                await expect(page).toHaveURL(/work=collections/);
                await expect(
                    todayGroup.locator('[data-dashboard-work-panel="actions"]'),
                ).toBeHidden();
                await expect(
                    todayGroup.locator(
                        '[data-dashboard-work-panel="collections"]',
                    ),
                ).toBeVisible();
                await expect(
                    portfolioGroup.locator(':scope > button'),
                ).toHaveAttribute('aria-expanded', 'false');
                await portfolioGroup.locator(':scope > button').click();
                await expect(
                    portfolioGroup.locator('.pmc-property-performance'),
                ).toBeVisible();
            }

            if (account.role === 'superadmin') {
                await page.setViewportSize(viewports.desktop);
                await page.reload();
                const desktopGroupToggles = page.locator(
                    '[data-dashboard-group] > button',
                );
                await expect(desktopGroupToggles).toHaveCount(3);
                await expect(desktopGroupToggles.first()).toBeHidden();
                await expect(
                    page.locator('.pmc-dashboard-launch-readiness'),
                ).toBeVisible();
                await expect(
                    page.locator('.pmc-platform-composition'),
                ).toBeVisible();
                await expect(
                    page.locator('.pmc-platform-activity'),
                ).toBeVisible();
                await expect(
                    page.locator('.pmc-property-performance'),
                ).toBeVisible();
            }

            await expectNoHorizontalOverflow(page);
        });
    }

    test('tenant dashboard panels are fully localized in Arabic', async ({
        page,
    }) => {
        await login(
            page,
            process.env.E2E_TENANT_EMAIL ?? localAccounts[3].email,
            process.env.E2E_PASSWORD ?? 'password',
        );
        await page.setViewportSize(viewports.mobile);
        await page.goto('/dashboard?locale=ar');

        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(
            page.getByText('سجل الدفعات', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('طلبات الصيانة', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('العقد والمستندات', { exact: true }),
        ).toBeVisible();
        await expect(page.locator('body')).not.toContainText('Payment history');
        await expect(page.locator('body')).not.toContainText(
            'Maintenance requests',
        );
        await expect(page.locator('body')).not.toContainText(
            'Lease and documents',
        );
        await expectNoHorizontalOverflow(page);
    });
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

async function expectPagedMobileCards(page: Page, pageSize: number) {
    const count = await page.locator('.pmc-mobile-record-card').count();

    expect(count).toBeGreaterThan(0);
    expect(count).toBeLessThanOrEqual(pageSize);
}

async function expectMinimumTouchHeight(page: Page, selector: string) {
    const heights = await page.locator(selector).evaluateAll((nodes) =>
        nodes
            .filter((node) => {
                const styles = window.getComputedStyle(node);

                return (
                    styles.display !== 'none' &&
                    styles.visibility !== 'hidden' &&
                    node.getBoundingClientRect().height > 0
                );
            })
            .map((node) => node.getBoundingClientRect().height),
    );

    expect(heights.length).toBeGreaterThan(0);

    for (const height of heights) {
        expect(height).toBeGreaterThanOrEqual(44);
    }
}
