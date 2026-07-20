import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { useTranslator } from '@/lib/i18n';
import type { SharedProps } from '@/types';

export function LanguageSwitcher() {
    const {
        app: { locale },
    } = usePage<SharedProps>().props;
    const { t } = useTranslator();
    const [pendingLocale, setPendingLocale] = useState<'en' | 'ar' | null>(
        null,
    );

    const switchLanguage = (nextLocale: 'en' | 'ar') => {
        if (nextLocale === locale || pendingLocale !== null) {
            return;
        }

        router.post(
            `/locale/${nextLocale}`,
            {},
            {
                preserveScroll: true,
                onStart: () => setPendingLocale(nextLocale),
                onFinish: () => setPendingLocale(null),
            },
        );
    };

    return (
        <div
            className="pmc-language-switcher"
            role="group"
            aria-label={t('shell.language_switcher', 'Language switcher')}
        >
            {(['en', 'ar'] as const).map((item) => (
                <button
                    key={item}
                    type="button"
                    className={locale === item ? 'active' : ''}
                    aria-pressed={locale === item}
                    aria-label={
                        item === 'ar'
                            ? t('shell.switch_to_arabic', 'Switch to Arabic')
                            : t('shell.switch_to_english', 'Switch to English')
                    }
                    disabled={pendingLocale !== null}
                    onClick={() => switchLanguage(item)}
                >
                    {pendingLocale === item ? (
                        <span
                            className="spinner-border spinner-border-sm"
                            aria-hidden="true"
                        />
                    ) : (
                        item.toUpperCase()
                    )}
                </button>
            ))}
        </div>
    );
}
