<?php

namespace App\Modules\Wording\Support;

class RequiredTranslationTokens
{
    /**
     * @return array<int, string>
     */
    public function missing(string $default, string $value): array
    {
        preg_match_all('/:[A-Za-z_]+/', $default, $defaultMatches);
        preg_match_all('/:[A-Za-z_]+/', $value, $valueMatches);

        return array_values(array_diff(
            array_unique($defaultMatches[0]),
            array_unique($valueMatches[0]),
        ));
    }
}
