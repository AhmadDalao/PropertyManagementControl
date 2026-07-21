import type {
    ContentCollection,
    ContentField,
    SectionContentSchema,
} from './section-content-types';

export function sectionContentSchema(
    sectionType: string,
): SectionContentSchema {
    const heading: ContentField[] = [
        { key: 'eyebrow', label: 'Eyebrow' },
        { key: 'headline', label: 'Headline', type: 'textarea' },
    ];
    const body: ContentField = {
        key: 'body',
        label: 'Body',
        type: 'textarea',
    };

    switch (sectionType) {
        case 'hero':
            return {
                description:
                    'The first public message, portal actions, summary chips, and dashboard preview.',
                fields: [
                    { key: 'eyebrow', label: 'Eyebrow' },
                    {
                        key: 'headline',
                        label: 'Headline',
                        type: 'textarea',
                    },
                    {
                        key: 'subheadline',
                        label: 'Subheadline',
                        type: 'textarea',
                    },
                    { key: 'ctaPrimary', label: 'Primary action' },
                    { key: 'ctaSecondary', label: 'Secondary action' },
                    { key: 'image', label: 'Image', type: 'media' },
                    { key: 'imageAlt', label: 'Image description' },
                ],
                collections: [
                    labelValueCollection('stats', 'Stat chips', 'Stat'),
                    labelValueCollection(
                        'preview',
                        'Dashboard preview',
                        'Tile',
                    ),
                ],
            };
        case 'role_cards':
        case 'feature_grid':
            return {
                description:
                    'A section heading followed by repeatable icon, title, and description cards.',
                fields: heading,
                collections: [
                    {
                        key: 'items',
                        label: 'Cards',
                        itemLabel: 'Card',
                        fields: [
                            { key: 'icon', label: 'Bootstrap icon' },
                            { key: 'title', label: 'Title' },
                            {
                                key: 'body',
                                label: 'Description',
                                type: 'textarea',
                            },
                        ],
                    },
                ],
            };
        case 'workflow':
            return {
                description:
                    'An ordered explanation of how owners and managers move through the system.',
                fields: heading,
                collections: [
                    {
                        key: 'steps',
                        label: 'Workflow steps',
                        itemLabel: 'Step',
                        fields: [
                            { key: 'title', label: 'Title' },
                            {
                                key: 'body',
                                label: 'Description',
                                type: 'textarea',
                            },
                        ],
                    },
                ],
            };
        case 'dashboard_preview':
            return {
                description:
                    'A dashboard story with a short explanation and visible operating metrics.',
                fields: [...heading, body],
                collections: [
                    labelValueCollection('metrics', 'Metrics', 'Metric'),
                ],
            };
        case 'operations_strip':
            return {
                description:
                    'A compact operational statement with label and value pairs.',
                fields: [
                    {
                        key: 'headline',
                        label: 'Headline',
                        type: 'textarea',
                    },
                    body,
                ],
                collections: [labelValueCollection('items', 'Items', 'Item')],
            };
        case 'faq':
            return {
                description:
                    'A bilingual list of practical questions and answers.',
                fields: heading,
                collections: [
                    {
                        key: 'items',
                        label: 'Questions',
                        itemLabel: 'Question',
                        fields: [
                            { key: 'question', label: 'Question' },
                            {
                                key: 'answer',
                                label: 'Answer',
                                type: 'textarea',
                            },
                        ],
                    },
                ],
            };
        case 'final_cta':
            return {
                description:
                    'The final call to action shown before the public footer.',
                fields: [
                    {
                        key: 'headline',
                        label: 'Headline',
                        type: 'textarea',
                    },
                    body,
                    { key: 'ctaPrimary', label: 'Action label' },
                ],
                collections: [],
            };
        case 'metrics':
            return {
                description: 'Simple label and value metric cards.',
                fields: [],
                collections: [
                    labelValueCollection('items', 'Metrics', 'Metric'),
                ],
            };
        default:
            return {
                description:
                    'A simple bilingual content section. Advanced JSON remains available for custom fields.',
                fields: [...heading, body],
                collections: [],
            };
    }
}

function labelValueCollection(
    key: string,
    label: string,
    itemLabel: string,
): ContentCollection {
    return {
        key,
        label,
        itemLabel,
        fields: [
            { key: 'label', label: 'Label' },
            { key: 'value', label: 'Value' },
        ],
    };
}
