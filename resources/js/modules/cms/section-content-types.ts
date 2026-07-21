export type ContentField = {
    key: string;
    label: string;
    type?: 'text' | 'textarea' | 'media';
};

export type ContentCollection = {
    key: string;
    label: string;
    itemLabel: string;
    fields: ContentField[];
};

export type SectionContentSchema = {
    description: string;
    fields: ContentField[];
    collections: ContentCollection[];
};
