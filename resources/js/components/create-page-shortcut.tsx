import { Link } from '@inertiajs/react';

type CreatePageShortcutProps = {
    href: string;
    label: string;
    description: string;
    icon?: string;
};

export function CreatePageShortcut({
    href,
    label,
    description,
    icon = 'bi-plus-lg',
}: CreatePageShortcutProps) {
    return (
        <div className="pmc-create-page-shortcut">
            <div>
                <span>Create page</span>
                <strong>{description}</strong>
            </div>
            <Link href={href} className="pmc-create-page-button">
                <span>
                    <i className={`bi ${icon}`} />
                    {label}
                </span>
            </Link>
        </div>
    );
}
