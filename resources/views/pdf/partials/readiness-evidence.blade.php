<table class="data">
    <thead>
        <tr>
            <th>{{ trans('app.readiness.report_check') }}</th>
            <th>{{ trans('app.readiness.report_status') }}</th>
            <th>{{ trans('app.readiness.report_evidence_note') }}</th>
            <th>{{ trans('app.readiness.report_confirmed_by') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($checks as $check)
            <tr>
                <td>{{ $check['label'] }}</td>
                <td>{{ $check['is_confirmed'] ? trans('app.readiness.confirmed') : trans('app.readiness.evidence_required') }}</td>
                <td>{{ $check['evidence'] ?: trans('app.readiness.report_not_recorded') }}</td>
                <td>{{ trim(($check['confirmed_by'] ?? '').' '.($check['confirmed_at'] ?? '')) ?: '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
