import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import type { PropertyContext } from '@/types';

export function usePropertyContextSwitcher(
    context: PropertyContext,
    currentUrl: string,
) {
    const [updating, setUpdating] = useState(false);
    const [pickerOpen, setPickerOpen] = useState(false);
    const [search, setSearch] = useState('');
    const triggerRef = useRef<HTMLButtonElement>(null);
    const restoreFocusAfterClose = useRef(false);

    useEffect(() => {
        if (!pickerOpen && restoreFocusAfterClose.current) {
            restoreFocusAfterClose.current = false;
            triggerRef.current?.focus({ preventScroll: true });
        }
    }, [pickerOpen]);

    const closePicker = () => {
        restoreFocusAfterClose.current = true;
        setPickerOpen(false);
    };

    const openPicker = () => {
        restoreFocusAfterClose.current = false;
        setSearch('');
        setPickerOpen(true);
    };

    const changeProperty = (propertyId: string) => {
        if (
            propertyId === String(context.selected?.id ?? '') ||
            (propertyId === '' && context.selected === null)
        ) {
            closePicker();

            return;
        }

        const url = new URL(currentUrl, window.location.origin);

        url.searchParams.set('property_id', propertyId || 'all');
        url.searchParams.delete('page');

        if (propertyId) {
            url.searchParams.delete('portfolio_id');
        }

        setUpdating(true);
        setPickerOpen(false);
        router.get(
            url.pathname,
            Object.fromEntries(url.searchParams.entries()),
            {
                preserveScroll: true,
                preserveState: false,
                replace: true,
                onFinish: () => setUpdating(false),
            },
        );
    };

    return {
        changeProperty,
        closePicker,
        openPicker,
        pickerOpen,
        search,
        setSearch,
        triggerRef,
        updating,
    };
}
