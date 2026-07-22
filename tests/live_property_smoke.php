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
    '/forgot-password' => 'auth/forgot-password',
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
    '/property-map' => 'admin/property-map/index',
    '/portfolios' => 'admin/portfolios/index',
    '/users' => 'admin/users/index',
    '/users/create' => 'admin/resource-form',
    '/assets' => 'admin/assets/index',
    '/assets/create' => 'admin/resource-form',
    '/tenants' => 'admin/tenants/index',
    '/leases' => 'admin/leases/index',
    '/leases?locale=ar' => 'admin/leases/index',
    '/leases/create' => 'admin/resource-form',
    '/leases/create?locale=ar' => 'admin/resource-form',
    '/payments?locale=en' => 'admin/payments/index',
    '/payments?locale=ar' => 'admin/payments/index',
    '/payments/create?locale=en' => 'admin/resource-form',
    '/payments/create?locale=ar' => 'admin/resource-form',
    '/maintenance-requests' => 'admin/maintenance/index',
    '/expenses' => 'admin/expenses/index',
    '/documents' => 'admin/documents/index',
    '/documents/create' => 'admin/resource-form',
    '/media-files' => 'admin/media/index',
    '/audit-logs' => 'admin/audit/index',
    '/cms' => 'admin/cms/index',
    '/wording' => 'admin/wording/index',
    '/system/showcase-data' => 'admin/showcase-data/index',
    '/system/showcase-data?locale=ar' => 'admin/showcase-data/index',
    '/cms/sections/create' => 'admin/cms/section-form',
    '/documentation' => 'admin/documentation/index',
    '/reports' => 'admin/reports/index',
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
smoke_note('Live smoke passed.');
