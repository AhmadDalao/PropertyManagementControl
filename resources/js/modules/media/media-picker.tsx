import { useDeferredValue, useEffect, useRef, useState } from 'react';

import '../../../css/styles/media.css';

import { useTranslator } from '@/lib/i18n';

import { MediaPickerPanel } from './media-picker-panel';
import { MediaPickerSelection } from './media-picker-selection';
import type { MediaPickerOption } from './types';

export function MediaPicker({
    value,
    options,
    onChange,
}: {
    value: string;
    options: MediaPickerOption[];
    onChange: (value: string) => void;
}) {
    const { direction, t } = useTranslator();
    const detailsRef = useRef<HTMLDetailsElement>(null);
    const [search, setSearch] = useState('');
    const [open, setOpen] = useState(false);
    const deferredSearch = useDeferredValue(search.trim().toLocaleLowerCase());
    const selected = options.find((option) => option.url === value);
    const visibleOptions = deferredSearch
        ? options.filter((option) =>
              [
                  option.title_en,
                  option.title_ar,
                  option.alt_text_en,
                  option.alt_text_ar,
              ]
                  .filter(Boolean)
                  .some((candidate) =>
                      String(candidate)
                          .toLocaleLowerCase()
                          .includes(deferredSearch),
                  ),
          )
        : options;

    useEffect(() => {
        const pickerElement = detailsRef.current;

        if (open) {
            document.body.classList.add('pmc-media-picker-open');
        }

        return () => {
            const anotherPickerIsOpen = Array.from(
                document.querySelectorAll('details.pmc-media-picker[open]'),
            ).some((picker) => picker !== pickerElement);

            if (!anotherPickerIsOpen) {
                document.body.classList.remove('pmc-media-picker-open');
            }
        };
    }, [open]);

    const close = () => {
        if (!detailsRef.current) {
            return;
        }

        detailsRef.current.open = false;
        setOpen(false);
        detailsRef.current.querySelector('summary')?.focus();
    };
    const select = (option: MediaPickerOption) => {
        onChange(option.url);
        close();
    };

    return (
        <div className="pmc-media-picker-control">
            {selected ? (
                <MediaPickerSelection
                    option={selected}
                    onClear={() => onChange('')}
                />
            ) : null}
            <details
                ref={detailsRef}
                className="pmc-media-picker"
                dir={direction}
                onToggle={(event) => {
                    const isOpen = event.currentTarget.open;
                    setOpen(isOpen);

                    if (isOpen) {
                        requestAnimationFrame(() =>
                            detailsRef.current
                                ?.querySelector<HTMLInputElement>(
                                    'input[type="search"]',
                                )
                                ?.focus(),
                        );
                    }
                }}
                onKeyDown={(event) => {
                    if (event.key === 'Escape' && detailsRef.current?.open) {
                        event.preventDefault();
                        close();
                    }
                }}
            >
                <summary className="btn btn-outline-secondary">
                    <i className="bi bi-images" />
                    {t('media.choose_image')}
                </summary>
                <MediaPickerPanel
                    options={visibleOptions}
                    search={search}
                    value={value}
                    onSearch={setSearch}
                    onSelect={select}
                    onClose={close}
                />
            </details>
        </div>
    );
}
