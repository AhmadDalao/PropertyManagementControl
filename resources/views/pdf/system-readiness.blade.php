<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ trans('app.readiness.report_title') }}</title>
    @include('pdf.partials.document-styles')
</head>
<body>
    @php
        $summary = $data['summary'];
        $portfolio = $data['portfolioReadiness']['portfolio'] ?? null;
        $statusLabel = static fn (string $status): string => trans("app.readiness.status_{$status}");
    @endphp

    <table class="document-header">
        <tr>
            <td>
                <span class="brand-mark">PMC</span>
                <h1 class="document-title">{{ trans('app.readiness.report_title') }}</h1>
            </td>
            <td class="document-meta">
                <div><span>{{ trans('app.readiness.report_generated_at_label') }}</span><strong>{{ $data['generatedAt'] }}</strong></div>
                <div><span>{{ trans('app.readiness.report_generated_by_label') }}</span><strong>{{ $data['preparedBy'] }}</strong></div>
                <div><span>{{ trans('app.readiness.portfolio') }}</span><strong>{{ $portfolio ? $portfolio['name'].' · '.$portfolio['code'] : trans('app.readiness.report_all_portfolios') }}</strong></div>
            </td>
        </tr>
    </table>

    <section class="section">
        <h2 class="section-title">{{ trans('app.readiness.current_decision') }}</h2>
        <table class="data">
            <thead><tr><th>{{ trans('app.readiness.report_status') }}</th><th class="amount">{{ trans('app.readiness.report_value') }}</th></tr></thead>
            <tbody>
                <tr><td>{{ trans('app.readiness.ready') }}</td><td class="amount">{{ $summary['ready'] }}</td></tr>
                <tr><td>{{ trans('app.readiness.attention') }}</td><td class="amount">{{ $summary['attention'] }}</td></tr>
                <tr><td>{{ trans('app.readiness.blocked') }}</td><td class="amount">{{ $summary['blocked'] }}</td></tr>
                <tr><td>{{ trans('app.readiness.report_total_checks') }}</td><td class="amount">{{ $summary['total'] }}</td></tr>
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title">{{ trans('app.readiness.report_system_checks') }}</h2>
        <table class="data">
            <thead><tr><th>{{ trans('app.readiness.report_check') }}</th><th>{{ trans('app.readiness.report_status') }}</th><th>{{ trans('app.readiness.report_detail') }}</th></tr></thead>
            <tbody>
                @foreach($data['systemChecks'] as $check)
                    <tr><td>{{ $check['label'] }}</td><td>{{ $statusLabel($check['status']) }}</td><td>{{ $check['detail'] ?? $check['description'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="section allow-break">
        <h2 class="section-title">{{ trans('app.readiness.report_evidence') }}</h2>
        @include('pdf.partials.readiness-evidence', ['checks' => $data['systemConfirmations']])
    </section>

    @if($data['portfolioReadiness'])
        <section class="section allow-break">
            <h2 class="section-title">{{ trans('app.readiness.report_portfolio_metrics') }}</h2>
            <table class="data">
                <thead><tr><th>{{ trans('app.readiness.report_metric') }}</th><th class="amount">{{ trans('app.readiness.report_value') }}</th></tr></thead>
                <tbody>
                    @foreach($data['portfolioReadiness']['metrics'] as $key => $value)
                        <tr><td>{{ trans("app.readiness.metric_{$key}") }}</td><td class="amount">{{ $value }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="section allow-break">
            <h2 class="section-title">{{ trans('app.readiness.report_portfolio_checks') }}</h2>
            <table class="data">
                <thead><tr><th>{{ trans('app.readiness.report_check') }}</th><th>{{ trans('app.readiness.report_status') }}</th><th>{{ trans('app.readiness.report_detail') }}</th></tr></thead>
                <tbody>
                    @foreach($data['portfolioReadiness']['checks'] as $check)
                        <tr><td>{{ $check['label'] }}</td><td>{{ $statusLabel($check['status']) }}</td><td>{{ $check['description'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="section allow-break">
            <h2 class="section-title">{{ trans('app.readiness.portfolio_approvals') }}</h2>
            @include('pdf.partials.readiness-evidence', ['checks' => $data['portfolioConfirmations']])
        </section>
    @endif

    <div class="document-footer">{{ trans('app.readiness.report_title') }}</div>
</body>
</html>
