<?php

namespace App\Modules\RentCollection\Requests;

use App\Modules\RentCollection\Support\CollectionFollowUpOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCollectionFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'contact_method' => ['required', Rule::in(CollectionFollowUpOptions::CONTACT_METHODS)],
            'outcome' => ['required', Rule::in(CollectionFollowUpOptions::OUTCOMES)],
            'contacted_at' => ['required', 'date', 'before_or_equal:now'],
            'assigned_to_user_id' => ['required', 'integer', 'exists:users,id'],
            'next_follow_up_on' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.today()->addYear()->toDateString()],
            'promised_amount' => [
                Rule::requiredIf($this->input('outcome') === 'promise_to_pay'),
                'nullable',
                'numeric',
                'min:0.01',
            ],
            'promised_on' => [
                Rule::requiredIf($this->input('outcome') === 'promise_to_pay'),
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'note' => ['required', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'contact_method' => trans('app.rent_collection.contact_method'),
            'outcome' => trans('app.rent_collection.follow_up_outcome'),
            'contacted_at' => trans('app.rent_collection.contacted_at'),
            'assigned_to_user_id' => trans('app.rent_collection.assigned_to'),
            'next_follow_up_on' => trans('app.rent_collection.next_follow_up'),
            'promised_amount' => trans('app.rent_collection.promised_amount'),
            'promised_on' => trans('app.rent_collection.promised_on'),
            'note' => trans('app.rent_collection.follow_up_note'),
        ];
    }
}
