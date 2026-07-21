import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { LanguageSwitcher } from '@/components/language-switcher';
import { useTranslator } from '@/lib/i18n';

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
        <div className="pmc-auth-page">
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
                        <div className="pmc-auth-copy">
                            <div className="pmc-kicker mb-3">
                                {t('login.secure_portal')}
                            </div>
                            <h2>{t('login.headline')}</h2>
                            <p>{t('login.description')}</p>
                            <div className="pmc-auth-points">
                                <span>
                                    <i className="bi bi-shield-check" />
                                    {t('login.role_access')}
                                </span>
                                <span>
                                    <i className="bi bi-translate" />
                                    {t('login.bilingual')}
                                </span>
                                <span>
                                    <i className="bi bi-file-earmark-lock" />
                                    {t('login.private_documents')}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="col-lg-5 offset-lg-1">
                        <div className="pmc-card pmc-login-panel p-4 p-lg-5 mx-auto">
                            <div className="pmc-kicker mb-3">{kicker}</div>
                            <h1 className="pmc-page-title mb-3">{title}</h1>
                            <p className="text-secondary mb-4">{description}</p>
                            {children}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
