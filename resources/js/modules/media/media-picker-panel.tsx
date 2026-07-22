import { useTranslator } from '@/lib/i18n';

import { mediaPickerAlt, mediaPickerTitle } from './media-picker-copy';
import type { MediaPickerOption } from './types';

export function MediaPickerPanel({
    options,
    search,
    value,
    onSearch,
    onSelect,
    onClose,
}: {
    options: MediaPickerOption[];
    search: string;
    value: string;
    onSearch: (value: string) => void;
    onSelect: (option: MediaPickerOption) => void;
    onClose: () => void;
}) {
    const { locale, t } = useTranslator();
    const fallback = t('media.untitled');

    return (
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
                    onClick={onClose}
                >
                    <i className="bi bi-x-lg" />
                </button>
            </header>
            <label className="pmc-media-picker-search">
                <span className="visually-hidden">{t('actions.search')}</span>
                <i className="bi bi-search" />
                <input
                    className="form-control"
                    type="search"
                    value={search}
                    placeholder={t('table.search')}
                    onChange={(event) => onSearch(event.currentTarget.value)}
                />
            </label>
            {options.length > 0 ? (
                <div className="pmc-media-picker-grid">
                    {options.map((option) => (
                        <button
                            type="button"
                            key={option.id}
                            className={option.url === value ? 'active' : ''}
                            onClick={() => onSelect(option)}
                        >
                            <img
                                src={option.url}
                                alt={mediaPickerAlt(option, locale, fallback)}
                                loading="lazy"
                            />
                            <span>
                                {mediaPickerTitle(option, locale, fallback)}
                            </span>
                        </button>
                    ))}
                </div>
            ) : (
                <p className="pmc-inline-empty">
                    {t('media.no_picker_images')}
                </p>
            )}
        </div>
    );
}
