import { Link, router } from '@inertiajs/react';

import { useTranslator } from '@/lib/i18n';

import type { ResourceAction } from './types';

export function ActionLink({ action }: { action: ResourceAction }) {
    const { text } = useTranslator();
    const className = `btn btn-${action.variant === 'danger' ? 'outline-danger' : action.variant === 'primary' ? 'primary' : action.variant === 'light' ? 'light' : 'outline-secondary'}`;

    if (action.external) {
        return (
            <a
                href={action.href}
                className={className}
                target="_blank"
                rel="noreferrer"
            >
                {text(action.label)}
            </a>
        );
    }

    if (!action.method || action.method === 'get') {
        return (
            <Link href={action.href} className={className}>
                {text(action.label)}
            </Link>
        );
    }

    const performAction = () => {
        if (action.confirm && !window.confirm(text(action.confirm))) {
            return;
        }

        if (action.method === 'delete') {
            router.delete(action.href, { preserveScroll: true });

            return;
        }

        if (action.method === 'put') {
            router.put(action.href, {}, { preserveScroll: true });

            return;
        }

        router.post(action.href, {}, { preserveScroll: true });
    };

    return (
        <button type="button" className={className} onClick={performAction}>
            {text(action.label)}
        </button>
    );
}
