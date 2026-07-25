<?php

namespace App\Modules\LeaseMoveOuts\Requests;

use App\Models\Lease;
use App\Modules\LeaseMoveOuts\Support\LeaseMoveOutOptions;
use App\Modules\Leases\Support\LeaseAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpsertLeaseMoveOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $lease = $this->route('lease');

        return $actor !== null
            && $lease instanceof Lease
            && app(LeaseAccess::class)->canManage($actor, $lease);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'move_out_date' => ['required', 'date'],
            'reason' => ['required', Rule::in(LeaseMoveOutOptions::REASONS)],
            'deposit_disposition' => ['required', Rule::in(LeaseMoveOutOptions::DEPOSIT_DISPOSITIONS)],
            'deposit_deduction_amount' => ['nullable', 'numeric', 'min:0'],
            'keys_returned' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'move_out_date' => trans('app.lease_move_outs.move_out_date'),
            'reason' => trans('app.lease_move_outs.reason'),
            'deposit_disposition' => trans('app.lease_move_outs.deposit_disposition'),
            'deposit_deduction_amount' => trans('app.lease_move_outs.deposit_deduction'),
            'keys_returned' => trans('app.lease_move_outs.keys_returned'),
            'notes' => trans('app.lease_move_outs.notes'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'keys_returned' => $this->boolean('keys_returned'),
            'deposit_deduction_amount' => $this->input('deposit_deduction_amount', 0),
        ]);
    }
}
