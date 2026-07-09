import 'bootstrap/dist/js/bootstrap.bundle.min.js';

import type { ResolvedComponent } from '@inertiajs/react';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Property Management Control';
type PageModule = { default: ResolvedComponent };

const pages = import.meta.glob<PageModule>('./pages/**/*.tsx');

createInertiaApp({
    title: (title) => (title ? `${title} | ${appName}` : appName),
    resolve: async (name) => {
        const page = pages[`./pages/${name}.tsx`];

        if (!page) {
            throw new Error(`Unknown page: ${name}`);
        }

        return (await page()).default;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#ef6c2f',
    },
});
