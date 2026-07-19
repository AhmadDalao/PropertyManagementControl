import { router, usePage } from '@inertiajs/react';

import type { SharedProps } from '@/types';

export function LanguageSwitcher() {
    const {
        app: { locale },
    } = usePage<SharedProps>().props;

    return (
        <div
            className="pmc-language-switcher"
            role="group"
            aria-label="Language switcher"
        >
            {(['en', 'ar'] as const).map((item) => (
                <button
                    key={item}
                    type="button"
                    className={locale === item ? 'active' : ''}
                    aria-pressed={locale === item}
                    onClick={() => router.post(`/locale/${item}`)}
                >
                    {item.toUpperCase()}
                </button>
            ))}
        </div>
    );
}
