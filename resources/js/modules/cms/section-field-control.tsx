import { useTranslator } from '@/lib/i18n';
import { MediaPicker } from '@/modules/media/media-picker';
import type { MediaPickerOption } from '@/modules/media/types';

import type { ContentField } from './section-content-types';

export function SectionFieldControl({
    field,
    value,
    mediaOptions,
    onChange,
}: {
    field: ContentField;
    value: string;
    mediaOptions: MediaPickerOption[];
    onChange: (value: string) => void;
}) {
    const { t } = useTranslator();
    const label = t(`cms.fields.${field.key}`, field.label);

    if (field.type === 'media') {
        return (
            <div className="pmc-resource-field">
                <span>{label}</span>
                <MediaPicker
                    value={value}
                    options={mediaOptions}
                    onChange={onChange}
                />
            </div>
        );
    }

    return (
        <label className="pmc-resource-field">
            <span>{label}</span>
            {field.type === 'textarea' ? (
                <textarea
                    className="form-control"
                    rows={3}
                    value={value}
                    onChange={(event) => onChange(event.currentTarget.value)}
                />
            ) : (
                <input
                    className="form-control"
                    value={value}
                    onChange={(event) => onChange(event.currentTarget.value)}
                />
            )}
        </label>
    );
}
