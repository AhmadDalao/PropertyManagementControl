import { router, usePage } from '@inertiajs/react';

import type { SharedProps } from '@/types';

export function LanguageSwitcher() {
    const {
        app: { locale },
    } = usePage<SharedProps>().props;

    return (
        <div className="btn-group" role="group" aria-label="Language switcher">
            {(['en', 'ar'] as const).map((item) => (
                <button
                    key={item}
                    type="button"
                    className={`btn btn-sm ${locale === item ? 'btn-primary' : 'btn-outline-secondary'}`}
                    onClick={() => router.post(`/locale/${item}`)}
                >
                    {item.toUpperCase()}
                </button>
            ))}
        </div>
    );
}
