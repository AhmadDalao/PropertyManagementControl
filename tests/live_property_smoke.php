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

$publicChecks = [
    '/' => 'public/home',
    '/login' => 'auth/login',
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

$loginPage = smoke_request($baseUrl, $cookieFile, 'GET', '/login');

if ($loginPage['status'] !== 200) {
    smoke_fail('Login page did not load.');
}

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
    '/payments' => 'admin/payments/index',
    '/maintenance-requests' => 'admin/maintenance/index',
    '/expenses' => 'admin/expenses/index',
    '/documents' => 'admin/documents/index',
    '/documents/create' => 'admin/resource-form',
    '/media-files' => 'admin/media/index',
    '/audit-logs' => 'admin/audit/index',
    '/cms' => 'admin/cms/index',
    '/wording' => 'admin/wording/index',
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
