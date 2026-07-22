<?php

namespace App\Modules\Expenses\Requests;

use App\Models\ExpenseEntry;
use App\Modules\Expenses\Support\ExpenseAccess;
use App\Modules\Expenses\Support\ExpenseOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateExpenseRequest extends FormRequest
{
    use HasExpenseValidationAttributes;

    public function authorize(): bool
    {
        $actor = $this->user();
        $expense = $this->route('expense');

        return $actor !== null
            && $expense instanceof ExpenseEntry
            && app(ExpenseAccess::class)->canManage($actor, $expense);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'maintenance_request_id' => ['nullable', 'integer', 'exists:maintenance_requests,id'],
            'category' => ['required', Rule::in(ExpenseOptions::CATEGORIES)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'incurred_on' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,999999999999.99'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(ExpenseOptions::MUTABLE_STATUSES)],
        ];
    }
}
