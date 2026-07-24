import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { LanguageSwitcher } from '@/components/language-switcher';
import { useTranslator } from '@/lib/i18n';

import { AuthContextPanel } from './auth-context-panel';

export function AuthShell({
    kicker,
    title,
    description,
    children,
}: {
    kicker: string;
    title: string;
    description: string;
    children: ReactNode;
}) {
    const { t } = useTranslator();

    return (
        <main className="pmc-auth-page">
            <div className="container">
                <div className="pmc-auth-topbar">
                    <Link href="/" className="pmc-public-brand">
                        <span>PMC</span>
                        <strong>{t('login.brand')}</strong>
                    </Link>
                    <LanguageSwitcher />
                </div>

                <div className="row g-4 g-xl-5 align-items-center">
                    <div className="col-lg-6">
                        <AuthContextPanel />
                    </div>

                    <div className="col-lg-5 offset-lg-1">
                        <section
                            className="pmc-card pmc-login-panel p-4 p-lg-5 mx-auto"
                            aria-labelledby="pmc-auth-title"
                        >
                            <div className="pmc-kicker mb-3">{kicker}</div>
                            <h1
                                id="pmc-auth-title"
                                className="pmc-page-title mb-3"
                            >
                                {title}
                            </h1>
                            <p className="text-secondary mb-4">{description}</p>
                            {children}
                        </section>
                    </div>
                </div>
            </div>
        </main>
    );
}
