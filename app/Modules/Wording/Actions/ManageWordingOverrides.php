<?php

namespace App\Modules\Wording\Actions;

use App\Models\LabelOverride;
use App\Modules\Wording\Support\RequiredTranslationTokens;
use App\Modules\Wording\Support\ResolvedUiTranslations;
use App\Modules\Wording\Support\TranslationDefaults;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ManageWordingOverrides
{
    public function __construct(
        private readonly TranslationDefaults $defaults,
        private readonly RequiredTranslationTokens $tokens,
        private readonly ResolvedUiTranslations $resolved,
    ) {}

    public function save(
        string $group,
        string $key,
        string $english,
        string $arabic,
    ): void {
        abort_unless(
            $this->defaults->isEditable($group, $key),
            422,
            trans('app.errors.unknown_wording_key'),
        );
        $this->guardRequiredTokens($group, $key, $english, $arabic);

        DB::transaction(function () use ($group, $key, $english, $arabic): void {
            $this->persistLocale($group, $key, 'en', $english);
            $this->persistLocale($group, $key, 'ar', $arabic);
        });

        $this->resolved->forget();
    }

    public function reset(string $group, string $key): void
    {
        abort_unless(
            $this->defaults->isEditable($group, $key),
            422,
            trans('app.errors.unknown_wording_key'),
        );

        $this->query($group, $key)
            ->whereIn('locale', ['en', 'ar'])
            ->delete();
        $this->resolved->forget();
    }

    private function persistLocale(
        string $group,
        string $key,
        string $locale,
        string $value,
    ): void {
        $query = $this->query($group, $key)->where('locale', $locale);
        $default = $this->defaults->value($group, $key, $locale);

        if ($value === $default) {
            $query->delete();

            return;
        }

        $override = $query->first() ?? new LabelOverride([
            'portfolio_id' => null,
            'group_name' => $group,
            'override_key' => $key,
            'locale' => $locale,
            'context_type' => null,
            'context_id' => null,
        ]);
        $override->value = $value;
        $override->save();
    }

    /**
     * @return Builder<LabelOverride>
     */
    private function query(string $group, string $key): Builder
    {
        return LabelOverride::query()
            ->whereNull('portfolio_id')
            ->whereNull('context_type')
            ->whereNull('context_id')
            ->where('group_name', $group)
            ->where('override_key', $key);
    }

    private function guardRequiredTokens(
        string $group,
        string $key,
        string $english,
        string $arabic,
    ): void {
        foreach (['en' => $english, 'ar' => $arabic] as $locale => $value) {
            $missing = $this->tokens->missing(
                $this->defaults->value($group, $key, $locale) ?? '',
                $value,
            );

            abort_if(
                $missing !== [],
                422,
                $this->resolved->translate(
                    'errors.wording_tokens_missing',
                    ['tokens' => implode(', ', $missing)],
                    app()->getLocale(),
                    'Required placeholders are missing: :tokens',
                ),
            );
        }
    }
}
