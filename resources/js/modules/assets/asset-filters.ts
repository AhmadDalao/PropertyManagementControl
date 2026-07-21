import type { TableFilterField } from '@/components/data-table';

export function assetFilterFields(
    portfolioOptions: Array<{ id: number; name: string }>,
    includePortfolio: boolean,
): TableFilterField[] {
    const fields: TableFilterField[] = [
        {
            name: 'status',
            label: 'Status',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Active', value: 'active' },
                { label: 'Inactive', value: 'inactive' },
                { label: 'Archived', value: 'archived' },
            ],
        },
        {
            name: 'asset_type',
            label: 'Type',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Property', value: 'property' },
                { label: 'Building', value: 'building' },
                { label: 'Floor', value: 'floor' },
                { label: 'Unit', value: 'unit' },
                { label: 'Space', value: 'space' },
            ],
        },
        {
            name: 'usage_type',
            label: 'Usage',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Residential', value: 'residential' },
                { label: 'Commercial', value: 'commercial' },
                { label: 'Mixed', value: 'mixed' },
                { label: 'Personal', value: 'personal' },
            ],
        },
        {
            name: 'occupancy_status',
            label: 'Occupancy',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Vacant', value: 'vacant' },
                { label: 'Occupied', value: 'occupied' },
                { label: 'Reserved', value: 'reserved' },
                { label: 'Maintenance', value: 'maintenance' },
            ],
        },
        {
            name: 'rentable',
            label: 'Rentable',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Yes', value: 'yes' },
                { label: 'No', value: 'no' },
            ],
        },
    ];

    if (includePortfolio) {
        fields.push({
            name: 'portfolio_id',
            label: 'Portfolio',
            options: [
                { label: 'All', value: 'all' },
                ...portfolioOptions.map((portfolio) => ({
                    label: portfolio.name,
                    value: portfolio.id,
                })),
            ],
        });
    }

    return fields;
}
