export type ActionCenterFilterOption = {
    value: string | number;
    label: string;
};

export function ActionCenterFilterSelect({
    label,
    value,
    options,
    onChange,
}: {
    label: string;
    value: string | number;
    options: ActionCenterFilterOption[];
    onChange: (value: string) => void;
}) {
    return (
        <label className="pmc-action-filter-field">
            <span>{label}</span>
            <select
                className="form-select"
                value={value}
                onChange={(event) => onChange(event.target.value)}
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </label>
    );
}
