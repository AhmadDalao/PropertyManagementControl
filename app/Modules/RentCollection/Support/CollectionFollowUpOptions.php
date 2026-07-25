<?php

namespace App\Modules\RentCollection\Support;

final class CollectionFollowUpOptions
{
    /** @var list<string> */
    public const CONTACT_METHODS = ['phone', 'email', 'whatsapp', 'in_person', 'other'];

    /** @var list<string> */
    public const OUTCOMES = ['contacted', 'no_answer', 'promise_to_pay', 'disputed', 'payment_arranged'];

    /** @var list<string> */
    public const STATES = ['untracked', 'due', 'promised', 'broken', 'scheduled'];
}
