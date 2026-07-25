import type { ManagerPropertyAssignment } from './types';

export function PropertyAssignmentCard({
    property,
    checked,
    onChange,
    labels,
}: {
    property: ManagerPropertyAssignment;
    checked: boolean;
    onChange: () => void;
    labels: {
        assigned: string;
        currentManager: string;
        unassigned: string;
        replacesManager: string;
        children: string;
    };
}) {
    const replacesManager =
        checked && property.current_manager && !property.selected;

    return (
        <label
            className={`pmc-property-assignment-card${checked ? 'is-selected' : ''}`}
        >
            <span className="pmc-property-assignment-check">
                <input type="checkbox" checked={checked} onChange={onChange} />
                <span aria-hidden="true">
                    <i className="bi bi-check2" />
                </span>
            </span>

            <span className="pmc-property-assignment-copy">
                <span className="pmc-property-assignment-heading">
                    <span>
                        <strong>{property.title}</strong>
                        <small>
                            {property.code} · {property.asset_type}
                        </small>
                    </span>
                    {checked ? <em>{labels.assigned}</em> : null}
                </span>

                <span className="pmc-property-assignment-meta">
                    <span>
                        <i className="bi bi-diagram-3" />
                        {property.children_count} {labels.children}
                    </span>
                    <span>
                        <i className="bi bi-person-workspace" />
                        {labels.currentManager}:{' '}
                        {property.current_manager?.name ?? labels.unassigned}
                    </span>
                    {property.parent ? (
                        <span>
                            <i className="bi bi-building" />
                            {property.parent}
                        </span>
                    ) : null}
                </span>

                {replacesManager ? (
                    <span className="pmc-property-assignment-warning">
                        <i className="bi bi-exclamation-circle" />
                        {labels.replacesManager}
                    </span>
                ) : null}
            </span>
        </label>
    );
}
