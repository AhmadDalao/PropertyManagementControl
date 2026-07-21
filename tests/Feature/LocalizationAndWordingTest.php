<?php

namespace Tests\Feature;

use App\Modules\Wording\UiTranslationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocalizationAndWordingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_language_switch_persists_for_an_authenticated_user(): void
    {
        $owner = $this->createUserWithRole('owner', $this->createPortfolio(), [
            'preferred_locale' => 'en',
        ]);

        $this->actingAs($owner)
            ->from(route('dashboard').'?locale=en')
            ->post(route('locale.update', 'ar'))
            ->assertRedirect(route('dashboard'));

        $this->assertSame('ar', $owner->fresh()->preferred_locale);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('app.translations.nav.dashboard', 'لوحة التحكم')
            );
    }

    public function test_language_switch_persists_for_a_guest_session(): void
    {
        $this->from(route('login'))
            ->post(route('locale.update', 'ar'))
            ->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('app.translations.nav.login', 'تسجيل الدخول')
            );
    }

    public function test_only_superadmin_can_open_the_wording_workspace(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner', $this->createPortfolio());

        $this->actingAs($superadmin)
            ->get(route('wording.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/wording/index')
                ->has('entries')
                ->where('groups', fn (mixed $groups): bool => $groups instanceof Collection
                    ? $groups->contains('nav')
                    : is_array($groups) && in_array('nav', $groups, true))
            );

        $this->actingAs($owner)
            ->get(route('wording.index'))
            ->assertForbidden();
    }

    public function test_superadmin_can_customize_and_reset_bilingual_page_wording(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->put(route('wording.update'), [
                'group' => 'nav',
                'key' => 'dashboard',
                'english' => 'Control Center',
                'arabic' => 'مركز التحكم',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('label_overrides', [
            'portfolio_id' => null,
            'group_name' => 'nav',
            'override_key' => 'dashboard',
            'locale' => 'en',
            'value' => 'Control Center',
        ]);
        $this->assertDatabaseHas('label_overrides', [
            'portfolio_id' => null,
            'group_name' => 'nav',
            'override_key' => 'dashboard',
            'locale' => 'ar',
            'value' => 'مركز التحكم',
        ]);

        $this->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.translations.nav.dashboard', 'Control Center')
            );

        $this->withSession(['locale' => 'ar'])
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.translations.nav.dashboard', 'مركز التحكم')
            );

        $this->delete(route('wording.destroy'), [
            'group' => 'nav',
            'key' => 'dashboard',
        ])->assertRedirect();

        $this->assertDatabaseMissing('label_overrides', [
            'group_name' => 'nav',
            'override_key' => 'dashboard',
        ]);
    }

    public function test_unknown_wording_keys_are_rejected(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->put(route('wording.update'), [
                'group' => 'unknown',
                'key' => 'unsafe',
                'english' => 'Bad override',
                'arabic' => 'تجاوز غير صالح',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('label_overrides', 0);
    }

    public function test_wording_writes_and_resets_are_superadmin_only(): void
    {
        $owner = $this->createUserWithRole('owner', $this->createPortfolio());
        $payload = [
            'group' => 'nav',
            'key' => 'dashboard',
            'english' => 'Unsafe owner override',
            'arabic' => 'تعديل مالك غير مسموح',
        ];

        $this->actingAs($owner)
            ->put(route('wording.update'), $payload)
            ->assertForbidden();
        $this->actingAs($owner)
            ->delete(route('wording.destroy'), Arr::only($payload, ['group', 'key']))
            ->assertForbidden();
        $this->assertDatabaseCount('label_overrides', 0);
    }

    public function test_required_wording_placeholders_cannot_be_removed(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->put(route('wording.update'), [
                'group' => 'validation',
                'key' => 'required',
                'english' => 'This field is required.',
                'arabic' => 'هذا الحقل مطلوب.',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('label_overrides', 0);
    }

    public function test_wording_index_rejects_unsupported_filter_values(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->from(route('wording.index'))
            ->get(route('wording.index', [
                'state' => 'unsafe',
                'per_page' => 999,
                'content_module' => 'unknown',
            ]))
            ->assertRedirect(route('wording.index'))
            ->assertSessionHasErrors(['state', 'per_page', 'content_module']);
    }

    public function test_english_and_arabic_translation_catalogs_have_matching_keys_and_placeholders(): void
    {
        $this->assertTranslationParity(
            array_filter(
                Arr::dot(require lang_path('en/app.php')),
                fn (string $key): bool => $key !== 'text' && ! str_starts_with($key, 'text.'),
                ARRAY_FILTER_USE_KEY,
            ),
            array_filter(
                Arr::dot(require lang_path('ar/app.php')),
                fn (string $key): bool => $key !== 'text' && ! str_starts_with($key, 'text.'),
                ARRAY_FILTER_USE_KEY,
            ),
        );

        foreach (['auth', 'pagination', 'passwords', 'validation'] as $group) {
            $this->assertTranslationParity(
                Arr::dot(require lang_path("en/{$group}.php")),
                Arr::dot(require lang_path("ar/{$group}.php")),
            );
        }
    }

    public function test_database_overrides_apply_to_framework_validation_and_sentence_copy(): void
    {
        $catalog = app(UiTranslationCatalog::class);
        $sentence = 'Property operations, at a glance.';

        $catalog->save(
            'validation',
            'required',
            'The :attribute field is required for this record.',
            'حقل :attribute مطلوب لهذا السجل.',
        );
        $catalog->save(
            'text',
            $sentence,
            'Property work, clearly organized.',
            'عمليات العقار مرتبة بوضوح.',
        );

        app()->setLocale('ar');
        $catalog->applyLaravelOverrides('ar');

        $message = Validator::make([], ['name' => ['required']])
            ->errors()
            ->first('name');

        $this->assertSame('حقل الاسم مطلوب لهذا السجل.', $message);
        $this->assertSame(
            'عمليات العقار مرتبة بوضوح.',
            $catalog->forLocale('ar')['text'][$sentence],
        );
    }

    public function test_saving_wording_invalidates_a_dictionary_loaded_in_the_same_request(): void
    {
        $catalog = app(UiTranslationCatalog::class);
        $this->assertSame('Dashboard', $catalog->forLocale('en')['nav']['dashboard']);

        $catalog->save('nav', 'dashboard', 'Live Operations', 'العمليات المباشرة');

        $this->assertSame(
            'Live Operations',
            $catalog->forLocale('en')['nav']['dashboard'],
        );
        $catalog->reset('nav', 'dashboard');
        $this->assertSame('Dashboard', $catalog->forLocale('en')['nav']['dashboard']);
    }

    public function test_runtime_overrides_never_replace_the_immutable_editor_defaults(): void
    {
        $catalog = app(UiTranslationCatalog::class);
        $catalog->save('nav', 'dashboard', 'Runtime dashboard', 'لوحة وقت التشغيل');
        $catalog->applyLaravelOverrides('en');

        $freshCatalog = app()->make(UiTranslationCatalog::class);
        $entry = collect($freshCatalog->entries())->first(
            fn (array $item): bool => $item['group'] === 'nav'
                && $item['key'] === 'dashboard',
        );

        $this->assertIsArray($entry);
        $this->assertSame('Runtime dashboard', $entry['english']);
        $this->assertSame('Dashboard', $entry['default_english']);
    }

    public function test_wording_workspace_supports_pagination_filters_and_content_translation_queue(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $portfolio = $this->createPortfolio([
            'name_en' => 'Missing Arabic Portfolio',
            'name_ar' => '',
            'address' => 'English address only',
            'address_ar' => null,
        ]);
        $asset = $this->createAsset($portfolio, [
            'title_en' => 'Missing Arabic Asset',
            'title_ar' => '',
        ]);
        $catalog = app(UiTranslationCatalog::class);
        $catalog->save('nav', 'dashboard', 'Operations Center', 'مركز العمليات');
        $catalog->save('nav', 'assets', 'Property Records', 'السجلات العقارية');

        $this->actingAs($superadmin)
            ->get(route('wording.index', [
                'state' => 'customized',
                'search' => 'Operations Center',
                'per_page' => 10,
                'content_module' => 'assets',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('entries.total', 1)
                ->where('entries.per_page', 10)
                ->where('entries.data.0.group', 'nav')
                ->where('entries.data.0.key', 'dashboard')
                ->where('filters.state', 'customized')
                ->where('filters.contentModule', 'assets')
                ->where('contentTranslations.total', 1)
                ->where('contentTranslations.items.0.module', 'assets')
                ->where('contentTranslations.items.0.missing', 'title_ar')
                ->where('contentTranslations.items.0.href', route('assets.edit', $asset)));
    }

    /**
     * @param  array<string, mixed>  $english
     * @param  array<string, mixed>  $arabic
     */
    private function assertTranslationParity(array $english, array $arabic): void
    {
        $this->assertSame(array_keys($english), array_keys($arabic));

        foreach ($english as $key => $englishValue) {
            $arabicValue = $arabic[$key] ?? null;

            $this->assertIsString($englishValue, "English translation [{$key}] must be a string.");
            $this->assertIsString($arabicValue, "Arabic translation [{$key}] must be a string.");
            $this->assertNotSame('', trim($englishValue), "English translation [{$key}] is empty.");
            $this->assertNotSame('', trim($arabicValue), "Arabic translation [{$key}] is empty.");
            $this->assertSame(
                $this->placeholders($englishValue),
                $this->placeholders($arabicValue),
                "Translation placeholders differ for [{$key}].",
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function placeholders(string $value): array
    {
        preg_match_all('/:[A-Za-z_]+/', $value, $matches);
        $placeholders = array_values(array_unique($matches[0]));
        sort($placeholders);

        return $placeholders;
    }
}
