import { useTranslator } from '@/lib/i18n';

import { mediaPickerAlt, mediaPickerTitle } from './media-picker-copy';
import type { MediaPickerOption } from './types';

export function MediaPickerSelection({
    option,
    onClear,
}: {
    option: MediaPickerOption;
    onClear: () => void;
}) {
    const { locale, t } = useTranslator();
    const fallback = t('media.untitled');

    return (
        <div className="pmc-media-picker-selected">
            <img
                src={option.url}
                alt={mediaPickerAlt(option, locale, fallback)}
            />
            <div>
                <span>{t('media.selected_image')}</span>
                <strong>{mediaPickerTitle(option, locale, fallback)}</strong>
                <small>
                    {option.width && option.height
                        ? `${option.width} x ${option.height} px`
                        : ''}
                </small>
            </div>
            <button
                type="button"
                className="btn btn-outline-danger btn-sm"
                onClick={onClear}
            >
                <i className="bi bi-x-lg" />
                <span>{t('media.clear_image')}</span>
            </button>
        </div>
    );
}
