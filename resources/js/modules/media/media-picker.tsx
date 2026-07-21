import { useDeferredValue, useRef, useState } from 'react';

import '../../../css/styles/media.css';

import { useTranslator } from '@/lib/i18n';

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
    const { direction, locale, t } = useTranslator();
    const detailsRef = useRef<HTMLDetailsElement>(null);
    const [search, setSearch] = useState('');
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
    const title = (option: MediaPickerOption) =>
        (locale === 'ar'
            ? option.title_ar || option.title_en
            : option.title_en || option.title_ar) || t('media.untitled');
    const alt = (option: MediaPickerOption) =>
        (locale === 'ar'
            ? option.alt_text_ar || option.alt_text_en
            : option.alt_text_en || option.alt_text_ar) || title(option);

    const select = (option: MediaPickerOption) => {
        onChange(option.url);

        close();
    };

    const close = () => {
        if (detailsRef.current) {
            detailsRef.current.open = false;
            detailsRef.current.querySelector('summary')?.focus();
        }
    };

    return (
        <div className="pmc-media-picker-control">
            {selected ? (
                <div className="pmc-media-picker-selected">
                    <img src={selected.url} alt={alt(selected)} />
                    <div>
                        <span>{t('media.selected_image')}</span>
                        <strong>{title(selected)}</strong>
                        <small>
                            {selected.width && selected.height
                                ? `${selected.width} x ${selected.height} px`
                                : ''}
                        </small>
                    </div>
                    <button
                        type="button"
                        className="btn btn-outline-danger btn-sm"
                        onClick={() => onChange('')}
                    >
                        <i className="bi bi-x-lg" />
                        <span>{t('media.clear_image')}</span>
                    </button>
                </div>
            ) : null}

            <details
                ref={detailsRef}
                className="pmc-media-picker"
                dir={direction}
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
                <div className="pmc-media-picker-panel">
                    <header>
                        <div className="pmc-media-picker-copy">
                            <strong>{t('media.choose_image')}</strong>
                            <span>{t('media.picker_description')}</span>
                            <a
                                href="/media-files"
                                className="btn btn-light btn-sm"
                                target="_blank"
                                rel="noreferrer"
                            >
                                {t('media.open_library')}
                            </a>
                        </div>
                        <button
                            type="button"
                            className="btn btn-light btn-sm pmc-media-picker-close"
                            aria-label={t('actions.close')}
                            onClick={close}
                        >
                            <i className="bi bi-x-lg" />
                        </button>
                    </header>
                    <label className="pmc-media-picker-search">
                        <span className="visually-hidden">
                            {t('actions.search')}
                        </span>
                        <i className="bi bi-search" />
                        <input
                            className="form-control"
                            type="search"
                            value={search}
                            placeholder={t('table.search')}
                            onChange={(event) =>
                                setSearch(event.currentTarget.value)
                            }
                        />
                    </label>
                    {visibleOptions.length > 0 ? (
                        <div className="pmc-media-picker-grid">
                            {visibleOptions.map((option) => (
                                <button
                                    type="button"
                                    key={option.id}
                                    className={
                                        option.url === value ? 'active' : ''
                                    }
                                    onClick={() => select(option)}
                                >
                                    <img
                                        src={option.url}
                                        alt={alt(option)}
                                        loading="lazy"
                                    />
                                    <span>{title(option)}</span>
                                </button>
                            ))}
                        </div>
                    ) : (
                        <p className="pmc-inline-empty">
                            {t('media.no_picker_images')}
                        </p>
                    )}
                </div>
            </details>
        </div>
    );
}
