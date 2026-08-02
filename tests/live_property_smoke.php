<?php

declare(strict_types=1);

$options = getopt('', ['base-url:', 'email:', 'password:']);

if (! isset($options['base-url'], $options['email'], $options['password'])) {
    fwrite(STDERR, "Usage: php tests/live_property_smoke.php --base-url=https://property.example.com --email=admin@example.com --password=secret\n");
    exit(1);
}

$baseUrl = rtrim((string) $options['base-url'], '/');
$email = (string) $options['email'];
$password = (string) $options['password'];
$cookieFile = tempnam(sys_get_temp_dir(), 'property-smoke-');

if ($cookieFile === false) {
    fwrite(STDERR, "Could not create cookie jar.\n");
    exit(1);
}

register_shutdown_function(static function () use ($cookieFile): void {
    if (is_file($cookieFile)) {
        @unlink($cookieFile);
    }
});

function smoke_note(string $message): void
{
    echo '[property-smoke] '.$message.PHP_EOL;
}

function smoke_fail(string $message): never
{
    fwrite(STDERR, '[property-smoke] FAIL: '.$message.PHP_EOL);
    exit(1);
}

function smoke_request(string $baseUrl, string $cookieFile, string $method, string $path, array $data = [], array $headers = []): array
{
    $url = str_starts_with($path, 'http') ? $path : $baseUrl.$path;
    $ch = curl_init($url);

    if ($ch === false) {
        smoke_fail('Could not initialize cURL.');
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'PropertyControlSmoke/1.0',
        CURLOPT_TIMEOUT => 45,
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }

    if ($headers !== []) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $raw = curl_exec($ch);

    if ($raw === false) {
        smoke_fail('HTTP request failed for '.$url.': '.curl_error($ch));
    }

    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headersText = substr((string) $raw, 0, $headerSize);
    $body = substr((string) $raw, $headerSize);
    $location = null;

    foreach (preg_split("/\r\n|\n|\r/", trim($headersText)) ?: [] as $line) {
        if (stripos($line, 'Location:') === 0) {
            $location = trim(substr($line, 9));
        }
    }

    return [
        'status' => $status,
        'location' => $location,
        'headers' => $headersText,
        'body' => $body,
    ];
}

function smoke_xsrf_token(string $cookieFile): string
{
    $contents = file_get_contents($cookieFile);

    if ($contents === false) {
        smoke_fail('Could not read cookie jar.');
    }

    foreach (explode("\n", $contents) as $line) {
        $parts = preg_split('/\s+/', trim($line));

        if (($parts[5] ?? null) === 'XSRF-TOKEN') {
            return rawurldecode((string) ($parts[6] ?? ''));
        }
    }

    smoke_fail('Could not find XSRF token.');
}

function smoke_component(string $html): string
{
    $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    if (preg_match('/"component":"([^"]+)"/', $decoded, $matches)) {
        return str_replace('\/', '/', $matches[1]);
    }

    return '';
}

function smoke_page_payload(string $html): array
{
    if (! preg_match('/<script data-page="app" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
        smoke_fail('Could not find the Inertia page payload.');
    }

    try {
        $payload = json_decode(
            $matches[1],
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    } catch (JsonException) {
        smoke_fail('The Inertia page payload could not be decoded.');
    }

    if (! is_array($payload)) {
        smoke_fail('The Inertia page payload was invalid.');
    }

    return $payload;
}

$publicChecks = [
    '/' => 'public/home',
    '/login' => 'auth/login',
    '/account-recovery' => 'auth/forgot-password',
    '/?locale=ar' => 'public/home',
];

foreach ($publicChecks as $path => $expectedComponent) {
    $response = smoke_request($baseUrl, $cookieFile, 'GET', $path);

    if ($response['status'] !== 200) {
        smoke_fail("{$path} returned {$response['status']}.");
    }

    $component = smoke_component($response['body']);

    if ($component !== $expectedComponent) {
        smoke_fail("{$path} expected {$expectedComponent}, got {$component}.");
    }

    smoke_note("{$path} {$component}");
}

$health = smoke_request($baseUrl, $cookieFile, 'GET', '/up');

if ($health['status'] !== 200) {
    smoke_fail("/up returned {$health['status']}.");
}

smoke_note('/up healthy');

$loginPage = smoke_request($baseUrl, $cookieFile, 'GET', '/login');

if ($loginPage['status'] !== 200) {
    smoke_fail('Login page did not load.');
}

$securityHeaders = strtolower((string) $loginPage['headers']);

foreach ([
    'x-content-type-options: nosniff',
    'x-frame-options: sameorigin',
    'referrer-policy: strict-origin-when-cross-origin',
    'permissions-policy:',
] as $expectedHeader) {
    if (! str_contains($securityHeaders, $expectedHeader)) {
        smoke_fail("Missing security header: {$expectedHeader}");
    }
}

smoke_note('Security headers present');

$token = smoke_xsrf_token($cookieFile);
$login = smoke_request($baseUrl, $cookieFile, 'POST', '/login', [
    'email' => $email,
    'password' => $password,
    'remember' => '0',
], [
    'X-XSRF-TOKEN: '.$token,
    'X-Requested-With: XMLHttpRequest',
]);

if ($login['status'] !== 302 || ! str_contains((string) $login['location'], '/dashboard')) {
    smoke_fail('Login did not redirect to dashboard.');
}

$authChecks = [
    '/dashboard' => 'dashboard',
    '/company-control?locale=en' => 'admin/company-control/index',
    '/company-control?locale=ar' => 'admin/company-control/index',
    '/notifications?locale=en' => 'admin/notifications/index',
    '/notifications?locale=ar' => 'admin/notifications/index',
    '/action-center?locale=en' => 'admin/action-center/index',
    '/action-center?locale=ar' => 'admin/action-center/index',
    '/property-map' => 'admin/property-map/index',
    '/property-explorer' => 'admin/assets/explorer',
    '/portfolios' => 'admin/portfolios/index',
    '/opening-data?locale=en' => 'admin/opening-data/index',
    '/opening-data?locale=ar' => 'admin/opening-data/index',
    '/users' => 'admin/users/index',
    '/users/create' => 'admin/resource-form',
    '/assets' => 'admin/assets/index',
    '/assets/create' => 'admin/resource-form',
    '/assets/building-setup' => 'admin/assets/structure-create',
    '/tenants' => 'admin/tenants/index',
    '/leases' => 'admin/leases/index',
    '/leases?locale=ar' => 'admin/leases/index',
    '/leases/create' => 'admin/resource-form',
    '/leases/create?locale=ar' => 'admin/resource-form',
    '/lease-renewals?locale=en' => 'admin/lease-renewals/index',
    '/lease-renewals?locale=ar' => 'admin/lease-renewals/index',
    '/rent-collection?locale=en' => 'admin/rent-collection/index',
    '/rent-collection?locale=ar' => 'admin/rent-collection/index',
    '/payments?locale=en' => 'admin/payments/index',
    '/payments?locale=ar' => 'admin/payments/index',
    '/payments/create?locale=en' => 'admin/resource-form',
    '/payments/create?locale=ar' => 'admin/resource-form',
    '/maintenance-requests' => 'admin/maintenance/index',
    '/maintenance-requests?confirmation=pending&locale=ar' => 'admin/maintenance/index',
    '/maintenance-work-orders?locale=en' => 'admin/maintenance-work-orders/index',
    '/maintenance-work-orders?locale=ar' => 'admin/maintenance-work-orders/index',
    '/expenses?locale=en' => 'admin/expenses/index',
    '/expenses?locale=ar' => 'admin/expenses/index',
    '/expenses/create?locale=en' => 'admin/resource-form',
    '/expenses/create?locale=ar' => 'admin/resource-form',
    '/documents?locale=en' => 'admin/documents/index',
    '/documents?locale=ar' => 'admin/documents/index',
    '/documents/create?locale=en' => 'admin/resource-form',
    '/documents/create?locale=ar' => 'admin/resource-form',
    '/media-files?locale=en' => 'admin/media/index',
    '/media-files?locale=ar' => 'admin/media/index',
    '/media-files/create?locale=en' => 'admin/resource-form',
    '/media-files/create?locale=ar' => 'admin/resource-form',
    '/audit-logs' => 'admin/audit/index',
    '/cms' => 'admin/cms/index',
    '/wording' => 'admin/wording/index',
    '/system/showcase-data' => 'admin/showcase-data/index',
    '/system/showcase-data?locale=ar' => 'admin/showcase-data/index',
    '/system/readiness' => 'admin/system-readiness/index',
    '/system/readiness?locale=ar' => 'admin/system-readiness/index',
    '/system/email-delivery' => 'admin/email-delivery/index',
    '/system/email-delivery?locale=ar' => 'admin/email-delivery/index',
    '/system/backups' => 'admin/system-backups/index',
    '/system/backups?locale=ar' => 'admin/system-backups/index',
    '/cms/sections/create' => 'admin/cms/section-form',
    '/documentation' => 'admin/documentation/index',
    '/reports' => 'admin/reports/index',
    '/reports/saved' => 'admin/reports/saved',
    '/reports/saved/create' => 'admin/reports/saved-form',
    '/reports/statement' => 'admin/reports/statement',
    '/reports/rent-roll?locale=ar' => 'admin/reports/rent-roll',
    '/reports/arrears-aging?locale=ar' => 'admin/reports/arrears-aging',
];

foreach ($authChecks as $path => $expectedComponent) {
    $response = smoke_request($baseUrl, $cookieFile, 'GET', $path);

    if ($response['status'] !== 200) {
        smoke_fail("{$path} returned {$response['status']}.");
    }

    $component = smoke_component($response['body']);

    if ($component !== $expectedComponent) {
        smoke_fail("{$path} expected {$expectedComponent}, got {$component}.");
    }

    smoke_note("{$path} {$component}");
}

$savedReports = smoke_request($baseUrl, $cookieFile, 'GET', '/reports/saved?locale=ar');
$savedReportsPayload = smoke_page_payload($savedReports['body']);
$savedPresets = $savedReportsPayload['props']['savedPresets'] ?? [];

if (is_array($savedPresets) && isset($savedPresets[0]['show_url'])) {
    $savedReportDetail = smoke_request(
        $baseUrl,
        $cookieFile,
        'GET',
        (string) $savedPresets[0]['show_url'].'?locale=ar',
    );
    $savedReportPayload = smoke_page_payload($savedReportDetail['body']);
    $documents = $savedReportPayload['props']['detailPage']['documents'] ?? [];
    $badges = is_array($documents)
        ? array_column($documents, 'badge')
        : [];

    if ($savedReportDetail['status'] !== 200
        || smoke_component($savedReportDetail['body']) !== 'admin/resource-show'
        || $badges !== ['PDF', 'DOCX', 'XLSX']) {
        smoke_fail('Saved report detail did not expose the expected PDF, DOCX, and XLSX outputs.');
    }

    smoke_note($savedPresets[0]['show_url'].' saved report detail and outputs');
} else {
    smoke_note('No saved report available; detail smoke skipped without creating production data.');
}

$openingTemplate = smoke_request($baseUrl, $cookieFile, 'GET', '/opening-data/template');
$openingTemplateHeaders = strtolower((string) $openingTemplate['headers']);

if ($openingTemplate['status'] !== 200
    || ! str_contains(
        $openingTemplateHeaders,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    )
    || ! str_starts_with((string) $openingTemplate['body'], 'PK')) {
    smoke_fail('The opening-data template is not a valid XLSX download.');
}

smoke_note('/opening-data/template valid XLSX');

foreach ([
    'pdf' => 'application/pdf',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
] as $extension => $contentType) {
    $readinessReport = smoke_request(
        $baseUrl,
        $cookieFile,
        'GET',
        "/system/readiness/report.{$extension}?locale=ar",
    );
    $readinessHeaders = strtolower((string) $readinessReport['headers']);
    $signature = $extension === 'pdf' ? '%PDF-' : 'PK';

    if ($readinessReport['status'] !== 200
        || ! str_contains($readinessHeaders, $contentType)
        || ! str_starts_with((string) $readinessReport['body'], $signature)) {
        smoke_fail("Launch readiness {$extension} report was invalid.");
    }

    smoke_note("/system/readiness/report.{$extension} valid");
}

$dashboard = smoke_request($baseUrl, $cookieFile, 'GET', '/dashboard?locale=en');
$dashboardPayload = smoke_page_payload($dashboard['body']);
$composition = $dashboardPayload['props']['platformComposition'] ?? null;
$platformActivity = $dashboardPayload['props']['platformActivity'] ?? null;

if (! is_array($composition)
    || ! is_array($composition['portfolios'] ?? null)
    || ! is_array($composition['properties'] ?? null)
    || ! is_array($composition['accounts'] ?? null)) {
    smoke_fail('Superadmin dashboard is missing the global company composition.');
}

smoke_note('/dashboard global company composition present');

if (! is_array($platformActivity) || count($platformActivity) > 8) {
    smoke_fail('Superadmin dashboard platform activity is missing or unbounded.');
}

$activityKeys = [];

foreach ($platformActivity as $activity) {
    if (! is_array($activity)
        || ! is_string($activity['subject_url'] ?? null)
        || ! str_starts_with($activity['subject_url'], $baseUrl.'/')) {
        smoke_fail('Dashboard platform activity does not open a live record.');
    }

    $key = implode(':', [
        (string) ($activity['subject_type'] ?? ''),
        (string) ($activity['subject_id'] ?? ''),
        (string) ($activity['event'] ?? ''),
    ]);

    if (isset($activityKeys[$key])) {
        smoke_fail('Dashboard platform activity contains duplicate subject events.');
    }

    $activityKeys[$key] = true;
}

smoke_note('/dashboard bounded platform activity present');

$companyControl = smoke_request(
    $baseUrl,
    $cookieFile,
    'GET',
    '/company-control?locale=ar',
);
$companyPayload = smoke_page_payload($companyControl['body']);

if (($companyPayload['props']['filters']['data_source'] ?? null) !== 'live'
    || ! is_array($companyPayload['props']['summary'] ?? null)
    || ! is_array($companyPayload['props']['portfolios']['data'] ?? null)) {
    smoke_fail('Company control did not expose a bounded live-client decision view.');
}

$detailPortfolioId = null;
$detailPortfolioIsLive = false;

foreach ($companyPayload['props']['portfolios']['data'] as $portfolio) {
    if (! is_array($portfolio) || ($portfolio['is_showcase'] ?? true) !== false) {
        smoke_fail('Company control live scope included showcase data.');
    }

    $detailPortfolioId ??= is_numeric($portfolio['id'] ?? null)
        ? (int) $portfolio['id']
        : null;
    $detailPortfolioIsLive = $detailPortfolioId !== null;
}

if ($detailPortfolioId === null) {
    $allCompanyControl = smoke_request(
        $baseUrl,
        $cookieFile,
        'GET',
        '/company-control?locale=ar&data_source=all&status=all&per_page=12',
    );
    $allCompanyPayload = smoke_page_payload($allCompanyControl['body']);
    $fallbackPortfolio = $allCompanyPayload['props']['portfolios']['data'][0] ?? null;

    if ($allCompanyControl['status'] === 200 && is_array($fallbackPortfolio)) {
        $detailPortfolioId = is_numeric($fallbackPortfolio['id'] ?? null)
            ? (int) $fallbackPortfolio['id']
            : null;
        $detailPortfolioIsLive = ($fallbackPortfolio['is_showcase'] ?? true) === false;
    }
}

if ($detailPortfolioId === null) {
    smoke_fail('Company control did not return a portfolio for detail verification.');
}

$portfolioDetail = smoke_request(
    $baseUrl,
    $cookieFile,
    'GET',
    "/portfolios/{$detailPortfolioId}?locale=ar",
);
$portfolioPayload = smoke_page_payload($portfolioDetail['body']);
$portfolioProps = $portfolioPayload['props']['detailPage'] ?? [];
$portfolioProgress = $portfolioProps['progress'] ?? [];
$portfolioCards = $portfolioProps['decisionCards'] ?? [];
$progressIsValid = ! $detailPortfolioIsLive
    || (($portfolioProgress['collapseWhenComplete'] ?? null) === true
        && ($portfolioProgress['expandLabel'] ?? null) === 'عرض خطوات الإعداد');

if ($portfolioDetail['status'] !== 200
    || smoke_component($portfolioDetail['body']) !== 'admin/resource-show'
    || ! $progressIsValid
    || ! str_contains(
        (string) ($portfolioCards[1]['href'] ?? ''),
        "/assets?portfolio_id={$detailPortfolioId}",
    )
    || ! str_contains(
        (string) ($portfolioCards[2]['href'] ?? ''),
        "/payments?portfolio_id={$detailPortfolioId}&status=posted",
    )
    || ! str_contains(
        (string) ($portfolioCards[3]['href'] ?? ''),
        "/reports/statement?portfolio_id={$detailPortfolioId}",
    )) {
    smoke_fail('Portfolio detail did not expose the compact setup and scoped operating flow.');
}

smoke_note('/portfolios/{portfolio} compact setup and operating links valid');

$companyWorkbook = smoke_request(
    $baseUrl,
    $cookieFile,
    'GET',
    '/company-control/export.xlsx?data_source=live&status=active&locale=ar',
);
$companyWorkbookHeaders = strtolower((string) $companyWorkbook['headers']);

if ($companyWorkbook['status'] !== 200
    || ! str_contains(
        $companyWorkbookHeaders,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    )
    || ! str_starts_with((string) $companyWorkbook['body'], 'PK')) {
    smoke_fail('Company control workbook was invalid.');
}

smoke_note('/company-control live scope and XLSX export valid');

$maintenanceSignoffs = smoke_request(
    $baseUrl,
    $cookieFile,
    'GET',
    '/maintenance-requests?confirmation=pending&locale=ar',
);
$maintenancePayload = smoke_page_payload($maintenanceSignoffs['body']);
$maintenanceProps = $maintenancePayload['props'] ?? [];
$maintenanceRows = $maintenanceProps['requests']['data'] ?? [];
$maintenanceCounts = $maintenanceProps['counts'] ?? [];

if (($maintenanceProps['filters']['confirmation'] ?? null) !== 'pending') {
    smoke_fail('Maintenance tenant sign-off filter did not persist.');
}

if (is_array($maintenanceRows)) {
    foreach ($maintenanceRows as $row) {
        if (! is_array($row) || ($row['awaiting_confirmation'] ?? null) !== true) {
            smoke_fail('Maintenance tenant sign-off filter returned an unrelated row.');
        }
    }
}

$activeSignoffCount = false;

if (is_array($maintenanceCounts)) {
    foreach ($maintenanceCounts as $count) {
        if (is_array($count)
            && ($count['filter']['confirmation'] ?? null) === 'pending'
            && ($count['active'] ?? false) === true) {
            $activeSignoffCount = true;
            break;
        }
    }
}

if (! $activeSignoffCount) {
    smoke_fail('Maintenance tenant sign-off quick filter was not active.');
}

smoke_note('/maintenance-requests tenant sign-off queue scoped');

$workOrderWorkbook = smoke_request(
    $baseUrl,
    $cookieFile,
    'GET',
    '/exports/maintenance-work-orders?locale=ar&per_page=10',
);
$workOrderWorkbookHeaders = strtolower((string) $workOrderWorkbook['headers']);

if ($workOrderWorkbook['status'] !== 200
    || ! str_contains(
        $workOrderWorkbookHeaders,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    )
    || ! str_starts_with((string) $workOrderWorkbook['body'], 'PK')) {
    smoke_fail('Maintenance work-order workbook was invalid.');
}

smoke_note('/maintenance-work-orders register and XLSX export valid');

if (is_array($maintenanceRows) && isset($maintenanceRows[0]['id'])) {
    $maintenanceId = (int) $maintenanceRows[0]['id'];

    foreach ([
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ] as $extension => $contentType) {
        $download = smoke_request(
            $baseUrl,
            $cookieFile,
            'GET',
            "/maintenance-requests/{$maintenanceId}/service-report.{$extension}",
        );
        $headers = strtolower((string) $download['headers']);

        if ($download['status'] !== 200 || ! str_contains($headers, $contentType)) {
            smoke_fail("Maintenance closeout {$extension} download failed.");
        }

        $signature = $extension === 'pdf' ? '%PDF-' : 'PK';

        if (! str_starts_with((string) $download['body'], $signature)) {
            smoke_fail("Maintenance closeout {$extension} signature is invalid.");
        }

        smoke_note("/maintenance-requests/{$maintenanceId}/service-report.{$extension} valid");
    }
}

$tenantIndex = smoke_request($baseUrl, $cookieFile, 'GET', '/tenants?per_page=10&locale=en');
$tenantPayload = smoke_page_payload($tenantIndex['body']);
$tenantRows = $tenantPayload['props']['tenants']['data'] ?? [];

if (is_array($tenantRows) && isset($tenantRows[0]['id'])) {
    $tenantId = (int) $tenantRows[0]['id'];
    $tenantStatement = smoke_request(
        $baseUrl,
        $cookieFile,
        'GET',
        "/tenants/{$tenantId}/account-statement?locale=ar",
    );
    $tenantStatementComponent = smoke_component($tenantStatement['body']);

    if ($tenantStatement['status'] !== 200
        || $tenantStatementComponent !== 'admin/tenants/statement') {
        smoke_fail(
            "Tenant account statement {$tenantId} returned status "
            .$tenantStatement['status']." and component {$tenantStatementComponent}.",
        );
    }

    smoke_note("/tenants/{$tenantId}/account-statement admin/tenants/statement");

    foreach ([
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ] as $extension => $contentType) {
        $download = smoke_request(
            $baseUrl,
            $cookieFile,
            'GET',
            "/tenants/{$tenantId}/account-statement.{$extension}",
        );
        $downloadHeaders = strtolower((string) $download['headers']);
        $signature = $extension === 'pdf' ? '%PDF-' : 'PK';

        if ($download['status'] !== 200
            || ! str_contains($downloadHeaders, $contentType)
            || ! str_starts_with((string) $download['body'], $signature)) {
            smoke_fail("Tenant account statement {$extension} download was invalid.");
        }

        smoke_note("/tenants/{$tenantId}/account-statement.{$extension} valid");
    }
} else {
    smoke_note('No tenant available for the account statement check.');
}

$reportsIndex = smoke_request($baseUrl, $cookieFile, 'GET', '/reports?locale=ar');
$reportsPayload = smoke_page_payload($reportsIndex['body']);
$propertyOptions = $reportsPayload['props']['propertyOptions'] ?? [];
$reportLibrary = [];

foreach ($reportsPayload['props']['reportLibrary'] ?? [] as $group) {
    array_push($reportLibrary, ...($group['cards'] ?? []));
}

$hasArabicScope = false;
$rentRollCard = null;
$arrearsAgingCard = null;

foreach ($reportLibrary as $card) {
    if (($card['key'] ?? null) === 'rent-roll') {
        $rentRollCard = $card;
    }

    if (($card['key'] ?? null) === 'arrears-aging') {
        $arrearsAgingCard = $card;
    }

    $scope = [];

    foreach ($card['scope'] ?? [] as $item) {
        if (is_array($item) && isset($item['label'], $item['value'])) {
            $scope[$item['label']] = $item['value'];
        }
    }

    if (isset(
        $scope['الفترة المحددة'],
        $scope['نطاق المحفظة'],
        $scope['نطاق العقار'],
    )
        && $scope['الفترة المحددة'] !== ''
        && $scope['نطاق المحفظة'] !== ''
        && $scope['نطاق العقار'] !== '') {
        $hasArabicScope = true;
    }
}

if (! $hasArabicScope) {
    smoke_fail('The Arabic report library did not expose its applied scope.');
}

smoke_note('/reports Arabic report scope');

$emailDeliveryExport = smoke_request(
    $baseUrl,
    $cookieFile,
    'GET',
    '/system/email-delivery/export?locale=ar',
);

if ($emailDeliveryExport['status'] !== 200
    || ! str_contains(
        strtolower((string) $emailDeliveryExport['headers']),
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    )
    || ! str_starts_with((string) $emailDeliveryExport['body'], 'PK')) {
    smoke_fail('The email delivery workbook was invalid.');
}

smoke_note('/system/email-delivery/export valid XLSX');

if (! is_array($rentRollCard)
    || count($rentRollCard['downloads'] ?? []) !== 3
    || ! str_contains((string) ($rentRollCard['openHref'] ?? ''), '/reports/rent-roll')) {
    smoke_fail('The report library did not expose the rent roll and its three downloads.');
}

$rentRollResponse = smoke_request(
    $baseUrl,
    $cookieFile,
    'GET',
    '/reports/rent-roll?locale=ar&per_page=10',
);
$rentRollPayload = smoke_page_payload($rentRollResponse['body']);
$rentRollRecords = $rentRollPayload['props']['records']['data'] ?? null;
$rentRollScope = [];

foreach ($rentRollPayload['props']['scope'] ?? [] as $item) {
    if (is_array($item) && isset($item['label'], $item['value'])) {
        $rentRollScope[$item['label']] = $item['value'];
    }
}

if ($rentRollResponse['status'] !== 200
    || smoke_component($rentRollResponse['body']) !== 'admin/reports/rent-roll'
    || ! is_array($rentRollRecords)
    || count($rentRollRecords) > 10
    || ! isset(
        $rentRollScope['الحالة الحالية'],
        $rentRollScope['نطاق المحفظة'],
        $rentRollScope['نطاق العقار'],
    )) {
    smoke_fail('The Arabic rent roll did not expose a bounded schedule and exact scope.');
}

smoke_note('/reports/rent-roll Arabic schedule and scope');

if (! is_array($arrearsAgingCard)
    || count($arrearsAgingCard['downloads'] ?? []) !== 3
    || ! str_contains((string) ($arrearsAgingCard['openHref'] ?? ''), '/reports/arrears-aging')) {
    smoke_fail('The report library did not expose arrears aging and its three downloads.');
}

$arrearsAgingResponse = smoke_request(
    $baseUrl,
    $cookieFile,
    'GET',
    '/reports/arrears-aging?locale=ar&per_page=10',
);
$arrearsAgingPayload = smoke_page_payload($arrearsAgingResponse['body']);
$arrearsAgingRecords = $arrearsAgingPayload['props']['records']['data'] ?? null;
$arrearsAgingPositions = $arrearsAgingPayload['props']['currencyPositions'] ?? null;

if ($arrearsAgingResponse['status'] !== 200
    || smoke_component($arrearsAgingResponse['body']) !== 'admin/reports/arrears-aging'
    || ! is_array($arrearsAgingRecords)
    || count($arrearsAgingRecords) > 10
    || ! is_array($arrearsAgingPositions)) {
    smoke_fail('The Arabic arrears aging report did not expose a bounded schedule.');
}

smoke_note('/reports/arrears-aging Arabic schedule');

if (is_array($propertyOptions) && isset($propertyOptions[0]['id'])) {
    $propertyId = (int) $propertyOptions[0]['id'];
    $propertyReport = smoke_request(
        $baseUrl,
        $cookieFile,
        'GET',
        "/reports/properties/{$propertyId}?locale=ar",
    );

    if ($propertyReport['status'] !== 200
        || smoke_component($propertyReport['body']) !== 'admin/reports/property') {
        smoke_fail("Property report {$propertyId} did not load.");
    }

    smoke_note("/reports/properties/{$propertyId} admin/reports/property");
    $propertyReportPayload = smoke_page_payload($propertyReport['body']);
    $propertyDownloads = $propertyReportPayload['props']['property']['downloads'] ?? [];

    foreach ([
        'pdf' => ['application/pdf', '%PDF-'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'PK'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'PK'],
    ] as $format => [$contentType, $signature]) {
        $downloadPath = (string) ($propertyDownloads[$format] ?? '');

        if (! str_contains($downloadPath, "/reports/properties/{$propertyId}/operating-report.")) {
            smoke_fail("Property report {$format} did not use the dedicated operating-report route.");
        }

        $download = smoke_request($baseUrl, $cookieFile, 'GET', $downloadPath);

        if ($download['status'] !== 200
            || ! str_contains($download['headers'], $contentType)
            || ! str_starts_with((string) $download['body'], $signature)) {
            smoke_fail("Property operating report {$format} was invalid.");
        }
    }

    smoke_note("/reports/properties/{$propertyId} dedicated PDF, DOCX, and XLSX");

    $scopedReports = smoke_request(
        $baseUrl,
        $cookieFile,
        'GET',
        "/reports?locale=ar&property_id={$propertyId}",
    );
    $scopedReportCards = [];
    $propertyReportCard = null;

    foreach (smoke_page_payload($scopedReports['body'])['props']['reportLibrary'] ?? [] as $group) {
        array_push($scopedReportCards, ...($group['cards'] ?? []));
    }

    foreach ($scopedReportCards as $card) {
        if (($card['key'] ?? null) === 'property-operating-report') {
            $propertyReportCard = $card;
            break;
        }
    }

    if (! is_array($propertyReportCard)
        || count($propertyReportCard['downloads'] ?? []) !== 3) {
        smoke_fail('The property operating report did not expose PDF, Word, and Excel downloads.');
    }

    $propertyScope = [];

    foreach ($propertyReportCard['scope'] ?? [] as $item) {
        if (is_array($item) && isset($item['label'], $item['value'])) {
            $propertyScope[$item['label']] = $item['value'];
        }
    }

    if (! str_contains(
        (string) ($propertyScope['نطاق العقار'] ?? ''),
        (string) ($propertyOptions[0]['name'] ?? ''),
    )) {
        smoke_fail('The property operating report did not name its selected property scope.');
    }

    $scopedDocuments = smoke_request(
        $baseUrl,
        $cookieFile,
        'GET',
        "/documents?locale=ar&property_id={$propertyId}",
    );
    $scopedDocumentPayload = smoke_page_payload($scopedDocuments['body']);

    if ($scopedDocuments['status'] !== 200
        || smoke_component($scopedDocuments['body']) !== 'admin/documents/index'
        || (string) ($scopedDocumentPayload['props']['filters']['property_id'] ?? '') !== (string) $propertyId) {
        smoke_fail("Property-scoped document register {$propertyId} did not load.");
    }

    $scopedDocumentExport = smoke_request(
        $baseUrl,
        $cookieFile,
        'GET',
        "/exports/documents?locale=ar&property_id={$propertyId}",
    );

    if ($scopedDocumentExport['status'] !== 200
        || ! str_starts_with((string) $scopedDocumentExport['body'], 'PK')) {
        smoke_fail("Property-scoped document export {$propertyId} was invalid.");
    }

    smoke_note("/documents property {$propertyId} scoped index and XLSX");
} else {
    smoke_note('No property available for the dedicated operating report check.');
}

$leaseIndex = smoke_request($baseUrl, $cookieFile, 'GET', '/leases');
$leasePayload = smoke_page_payload($leaseIndex['body']);
$leaseRows = $leasePayload['props']['leases']['data'] ?? [];

if (is_array($leaseRows) && isset($leaseRows[0]['id'])) {
    $leaseId = (int) $leaseRows[0]['id'];
    $leaseDetail = smoke_request($baseUrl, $cookieFile, 'GET', '/leases/'.$leaseId);

    if ($leaseDetail['status'] !== 200 || smoke_component($leaseDetail['body']) !== 'admin/resource-show') {
        smoke_fail("Lease {$leaseId} detail did not load.");
    }

    smoke_note("/leases/{$leaseId} admin/resource-show");

    foreach (['contract', 'statement'] as $document) {
        $pdf = smoke_request($baseUrl, $cookieFile, 'GET', "/leases/{$leaseId}/{$document}");
        $pdfHeaders = strtolower((string) $pdf['headers']);

        if ($pdf['status'] !== 200 || ! str_contains($pdfHeaders, 'application/pdf')) {
            smoke_fail("Lease {$document} PDF returned an invalid response.");
        }

        if (! str_starts_with((string) $pdf['body'], '%PDF-')) {
            smoke_fail("Lease {$document} download was not a valid PDF.");
        }

        smoke_note("/leases/{$leaseId}/{$document} PDF");
    }

    $contractWord = smoke_request(
        $baseUrl,
        $cookieFile,
        'GET',
        "/leases/{$leaseId}/contract.docx",
    );
    $contractWordHeaders = strtolower((string) $contractWord['headers']);

    if ($contractWord['status'] !== 200
        || ! str_contains(
            $contractWordHeaders,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        )
        || ! str_contains($contractWordHeaders, '.docx')
        || ! str_starts_with((string) $contractWord['body'], 'PK')) {
        smoke_fail('Lease contract Word download was invalid.');
    }

    smoke_note("/leases/{$leaseId}/contract.docx Word .docx");
} else {
    smoke_note('No lease record available for non-destructive detail and PDF checks.');
}

$leaseExport = smoke_request($baseUrl, $cookieFile, 'GET', '/exports/leases');
$leaseExportHeaders = strtolower((string) $leaseExport['headers']);

if ($leaseExport['status'] !== 200) {
    smoke_fail("Lease export returned {$leaseExport['status']}.");
}

if (! str_contains($leaseExportHeaders, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
    smoke_fail('Lease export did not return the Excel workbook content type.');
}

if (! str_contains($leaseExportHeaders, '.xlsx') || ! str_starts_with((string) $leaseExport['body'], 'PK')) {
    smoke_fail('Lease export was not a valid .xlsx download.');
}

smoke_note('/exports/leases Excel .xlsx');

$leaseRenewalExport = smoke_request($baseUrl, $cookieFile, 'GET', '/exports/lease-renewals?queue=all&horizon=90&locale=en');
$leaseRenewalExportHeaders = strtolower((string) $leaseRenewalExport['headers']);

if ($leaseRenewalExport['status'] !== 200) {
    smoke_fail("Lease renewal export returned {$leaseRenewalExport['status']}.");
}

if (! str_contains($leaseRenewalExportHeaders, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
    smoke_fail('Lease renewal export did not return the Excel workbook content type.');
}

if (! str_contains($leaseRenewalExportHeaders, '.xlsx') || ! str_starts_with((string) $leaseRenewalExport['body'], 'PK')) {
    smoke_fail('Lease renewal export was not a valid .xlsx download.');
}

smoke_note('/exports/lease-renewals Excel .xlsx');

$rentCollectionExport = smoke_request($baseUrl, $cookieFile, 'GET', '/exports/rent-collection?status=actionable&locale=en');
$rentCollectionExportHeaders = strtolower((string) $rentCollectionExport['headers']);

if ($rentCollectionExport['status'] !== 200) {
    smoke_fail("Rent collection export returned {$rentCollectionExport['status']}.");
}

if (! str_contains($rentCollectionExportHeaders, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
    smoke_fail('Rent collection export did not return the Excel workbook content type.');
}

if (! str_contains($rentCollectionExportHeaders, '.xlsx') || ! str_starts_with((string) $rentCollectionExport['body'], 'PK')) {
    smoke_fail('Rent collection export was not a valid .xlsx download.');
}

smoke_note('/exports/rent-collection Excel .xlsx');

$actionCenterExport = smoke_request($baseUrl, $cookieFile, 'GET', '/action-center/export?locale=en');
$actionCenterExportHeaders = strtolower((string) $actionCenterExport['headers']);

if ($actionCenterExport['status'] !== 200) {
    smoke_fail("Action Center export returned {$actionCenterExport['status']}.");
}

if (! str_contains($actionCenterExportHeaders, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
    smoke_fail('Action Center export did not return the Excel workbook content type.');
}

if (! str_contains($actionCenterExportHeaders, '.xlsx') || ! str_starts_with((string) $actionCenterExport['body'], 'PK')) {
    smoke_fail('Action Center export was not a valid .xlsx download.');
}

smoke_note('/action-center/export Excel .xlsx');

$paymentIndex = smoke_request($baseUrl, $cookieFile, 'GET', '/payments?status=posted&per_page=10&locale=en');
$paymentPayload = smoke_page_payload($paymentIndex['body']);
$paymentRows = $paymentPayload['props']['payments']['data'] ?? [];

if (is_array($paymentRows) && isset($paymentRows[0]['id'])) {
    $paymentId = (int) $paymentRows[0]['id'];
    $paymentDetail = smoke_request($baseUrl, $cookieFile, 'GET', '/payments/'.$paymentId.'?locale=en');

    if ($paymentDetail['status'] !== 200 || smoke_component($paymentDetail['body']) !== 'admin/resource-show') {
        smoke_fail("Payment {$paymentId} detail did not load.");
    }

    smoke_note("/payments/{$paymentId} admin/resource-show");

    $receipt = smoke_request($baseUrl, $cookieFile, 'GET', "/payments/{$paymentId}/receipt");
    $receiptHeaders = strtolower((string) $receipt['headers']);

    if ($receipt['status'] !== 200 || ! str_contains($receiptHeaders, 'application/pdf')) {
        smoke_fail('Payment receipt returned an invalid response.');
    }

    if (! str_starts_with((string) $receipt['body'], '%PDF-')) {
        smoke_fail('Payment receipt download was not a valid PDF.');
    }

    smoke_note("/payments/{$paymentId}/receipt PDF");
} else {
    smoke_note('No posted payment available for non-destructive detail and receipt checks.');
}

$paymentExport = smoke_request($baseUrl, $cookieFile, 'GET', '/exports/payments?status=posted&locale=en');
$paymentExportHeaders = strtolower((string) $paymentExport['headers']);

if ($paymentExport['status'] !== 200) {
    smoke_fail("Payment export returned {$paymentExport['status']}.");
}

if (! str_contains($paymentExportHeaders, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
    smoke_fail('Payment export did not return the Excel workbook content type.');
}

if (! str_contains($paymentExportHeaders, '.xlsx') || ! str_starts_with((string) $paymentExport['body'], 'PK')) {
    smoke_fail('Payment export was not a valid .xlsx download.');
}

smoke_note('/exports/payments Excel .xlsx');

$expenseIndex = smoke_request($baseUrl, $cookieFile, 'GET', '/expenses?status=posted&per_page=10&locale=en');
$expensePayload = smoke_page_payload($expenseIndex['body']);
$expenseRows = $expensePayload['props']['expenses']['data'] ?? [];

if (is_array($expenseRows) && isset($expenseRows[0]['id'])) {
    $expenseId = (int) $expenseRows[0]['id'];
    $expenseDetail = smoke_request($baseUrl, $cookieFile, 'GET', '/expenses/'.$expenseId.'?locale=en');

    if ($expenseDetail['status'] !== 200 || smoke_component($expenseDetail['body']) !== 'admin/resource-show') {
        smoke_fail("Expense {$expenseId} detail did not load.");
    }

    smoke_note("/expenses/{$expenseId} admin/resource-show");
} else {
    smoke_note('No posted expense available for the non-destructive detail check.');
}

$expenseExport = smoke_request($baseUrl, $cookieFile, 'GET', '/exports/expenses?status=posted&locale=en');
$expenseExportHeaders = strtolower((string) $expenseExport['headers']);

if ($expenseExport['status'] !== 200) {
    smoke_fail("Expense export returned {$expenseExport['status']}.");
}

if (! str_contains($expenseExportHeaders, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
    smoke_fail('Expense export did not return the Excel workbook content type.');
}

if (! str_contains($expenseExportHeaders, '.xlsx') || ! str_starts_with((string) $expenseExport['body'], 'PK')) {
    smoke_fail('Expense export was not a valid .xlsx download.');
}

smoke_note('/exports/expenses Excel .xlsx');

$documentIndex = smoke_request($baseUrl, $cookieFile, 'GET', '/documents?per_page=10&locale=en');
$documentPayload = smoke_page_payload($documentIndex['body']);
$documentRows = $documentPayload['props']['documents']['data'] ?? [];

if (is_array($documentRows) && isset($documentRows[0]['id'])) {
    $documentId = (int) $documentRows[0]['id'];
    $documentDetail = smoke_request($baseUrl, $cookieFile, 'GET', '/documents/'.$documentId.'?locale=en');

    if ($documentDetail['status'] !== 200 || smoke_component($documentDetail['body']) !== 'admin/resource-show') {
        smoke_fail("Document {$documentId} detail did not load.");
    }

    smoke_note("/documents/{$documentId} admin/resource-show");

    $documentPdf = smoke_request($baseUrl, $cookieFile, 'GET', "/documents/{$documentId}/download");
    $documentPdfHeaders = strtolower((string) $documentPdf['headers']);

    if ($documentPdf['status'] !== 200 || ! str_contains($documentPdfHeaders, 'application/pdf')) {
        smoke_fail("Document {$documentId} returned an invalid PDF response.");
    }

    if (! str_starts_with((string) $documentPdf['body'], '%PDF-')) {
        smoke_fail("Document {$documentId} download was not a valid PDF.");
    }

    smoke_note("/documents/{$documentId}/download PDF");
} else {
    smoke_note('No document available for non-destructive detail and PDF checks.');
}

$documentExport = smoke_request($baseUrl, $cookieFile, 'GET', '/exports/documents?locale=ar');
$documentExportHeaders = strtolower((string) $documentExport['headers']);

if ($documentExport['status'] !== 200) {
    smoke_fail("Document export returned {$documentExport['status']}.");
}

if (! str_contains($documentExportHeaders, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
    smoke_fail('Document export did not return the Excel workbook content type.');
}

if (! str_contains($documentExportHeaders, '.xlsx') || ! str_starts_with((string) $documentExport['body'], 'PK')) {
    smoke_fail('Document export was not a valid .xlsx download.');
}

smoke_note('/exports/documents Excel .xlsx');

$mediaIndex = smoke_request($baseUrl, $cookieFile, 'GET', '/media-files?per_page=10&locale=en');
$mediaPayload = smoke_page_payload($mediaIndex['body']);
$mediaRows = $mediaPayload['props']['mediaFiles']['data'] ?? [];

if (is_array($mediaRows) && isset($mediaRows[0]['id'])) {
    $mediaId = (int) $mediaRows[0]['id'];
    $mediaDetail = smoke_request($baseUrl, $cookieFile, 'GET', '/media-files/'.$mediaId.'?locale=en');

    if ($mediaDetail['status'] !== 200 || smoke_component($mediaDetail['body']) !== 'admin/resource-show') {
        smoke_fail("Media {$mediaId} detail did not load.");
    }

    smoke_note("/media-files/{$mediaId} admin/resource-show");

    $image = smoke_request($baseUrl, $cookieFile, 'GET', "/media-files/{$mediaId}/file");
    $imageHeaders = strtolower((string) $image['headers']);

    if ($image['status'] !== 200 || ! str_contains($imageHeaders, 'content-type: image/')) {
        smoke_fail("Media {$mediaId} returned an invalid image response.");
    }

    if (strlen((string) $image['body']) < 8) {
        smoke_fail("Media {$mediaId} returned an empty image file.");
    }

    smoke_note("/media-files/{$mediaId}/file image");
} else {
    smoke_note('No media image available for the non-destructive detail and file checks.');
}

$mediaExport = smoke_request($baseUrl, $cookieFile, 'GET', '/exports/media-files?locale=ar');
$mediaExportHeaders = strtolower((string) $mediaExport['headers']);

if ($mediaExport['status'] !== 200) {
    smoke_fail("Media export returned {$mediaExport['status']}.");
}

if (! str_contains($mediaExportHeaders, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
    smoke_fail('Media export did not return the Excel workbook content type.');
}

if (! str_contains($mediaExportHeaders, '.xlsx') || ! str_starts_with((string) $mediaExport['body'], 'PK')) {
    smoke_fail('Media export was not a valid .xlsx download.');
}

smoke_note('/exports/media-files Excel .xlsx');

$reportExport = smoke_request($baseUrl, $cookieFile, 'GET', '/reports/export');
$reportHeaders = strtolower((string) $reportExport['headers']);

if ($reportExport['status'] !== 200) {
    smoke_fail("Report export returned {$reportExport['status']}.");
}

if (! str_contains($reportHeaders, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
    smoke_fail('Report export did not return the Excel workbook content type.');
}

if (! str_contains($reportHeaders, '.xlsx') || ! str_starts_with((string) $reportExport['body'], 'PK')) {
    smoke_fail('Report export was not a valid .xlsx download.');
}

smoke_note('/reports/export Excel .xlsx');

foreach ([
    'pdf' => ['application/pdf', '%PDF-'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'PK'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'PK'],
] as $extension => [$contentType, $signature]) {
    $rentRollExport = smoke_request(
        $baseUrl,
        $cookieFile,
        'GET',
        "/reports/rent-roll.{$extension}",
    );
    $rentRollHeaders = strtolower((string) $rentRollExport['headers']);

    if ($rentRollExport['status'] !== 200
        || ! str_contains($rentRollHeaders, $contentType)
        || ! str_starts_with((string) $rentRollExport['body'], $signature)) {
        smoke_fail("Rent roll {$extension} download was invalid.");
    }

    smoke_note("/reports/rent-roll.{$extension} valid");
}

foreach ([
    'pdf' => ['application/pdf', '%PDF-'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'PK'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'PK'],
] as $extension => [$contentType, $signature]) {
    $agingExport = smoke_request(
        $baseUrl,
        $cookieFile,
        'GET',
        "/reports/arrears-aging.{$extension}",
    );
    $agingHeaders = strtolower((string) $agingExport['headers']);

    if ($agingExport['status'] !== 200
        || ! str_contains($agingHeaders, $contentType)
        || ! str_starts_with((string) $agingExport['body'], $signature)) {
        smoke_fail("Arrears aging {$extension} download was invalid.");
    }

    smoke_note("/reports/arrears-aging.{$extension} valid");
}

$ownerStatementPdf = smoke_request($baseUrl, $cookieFile, 'GET', '/reports/statement.pdf');
$ownerStatementPdfHeaders = strtolower((string) $ownerStatementPdf['headers']);

if ($ownerStatementPdf['status'] !== 200) {
    smoke_fail("Owner statement PDF returned {$ownerStatementPdf['status']}.");
}

if (! str_contains($ownerStatementPdfHeaders, 'application/pdf') || ! str_starts_with((string) $ownerStatementPdf['body'], '%PDF-')) {
    smoke_fail('Owner statement PDF was not a valid PDF download.');
}

smoke_note('/reports/statement.pdf PDF');

$ownerStatementWord = smoke_request($baseUrl, $cookieFile, 'GET', '/reports/statement.docx');
$ownerStatementWordHeaders = strtolower((string) $ownerStatementWord['headers']);

if ($ownerStatementWord['status'] !== 200) {
    smoke_fail("Owner statement Word document returned {$ownerStatementWord['status']}.");
}

if (! str_contains($ownerStatementWordHeaders, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
    || ! str_contains($ownerStatementWordHeaders, '.docx')
    || ! str_starts_with((string) $ownerStatementWord['body'], 'PK')) {
    smoke_fail('Owner statement Word document was not a valid .docx download.');
}

smoke_note('/reports/statement.docx Word .docx');

$ownerStatementWorkbook = smoke_request($baseUrl, $cookieFile, 'GET', '/reports/statement.xlsx');
$ownerStatementWorkbookHeaders = strtolower((string) $ownerStatementWorkbook['headers']);

if ($ownerStatementWorkbook['status'] !== 200) {
    smoke_fail("Owner statement workbook returned {$ownerStatementWorkbook['status']}.");
}

if (! str_contains($ownerStatementWorkbookHeaders, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
    || ! str_contains($ownerStatementWorkbookHeaders, '.xlsx')
    || ! str_starts_with((string) $ownerStatementWorkbook['body'], 'PK')) {
    smoke_fail('Owner statement workbook was not a valid .xlsx download.');
}

smoke_note('/reports/statement.xlsx Excel .xlsx');
smoke_note('Live smoke passed.');
