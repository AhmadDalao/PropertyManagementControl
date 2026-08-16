import type {
    DetailItem,
    DetailSection,
    RelatedTable,
    ResourceDocument,
    ResourceFormShellProps,
    ResourceHeaderProps,
    ResourceTimelineEntry,
    ResourceWorkflow,
} from '@/components/resource-cycle';
import type {
    PaginatedData,
    SharedProps,
    TableCount,
    TableFilters,
    PropertyOption,
} from '@/types';

export type PaymentRecord = {
    id: number;
    reference?: string | null;
    amount: number;
    currency: string;
    received_on?: string | null;
    status: string;
    type: string;
    method: string;
    allocated_amount: number;
    unallocated_amount: number;
    allocation_count: number;
    receipt_url: string;
    tenant_profile?: {
        user?: { name?: string | null };
    };
    lease?: {
        code?: string | null;
        leaseable?: {
            title_en?: string | null;
            title_ar?: string | null;
            code?: string | null;
        };
    };
};

export type PaymentInsights = {
    total: number;
    posted_count: number;
    pending_count: number;
    void_count: number;
    posted_amount: number;
    pending_amount: number;
    void_amount: number;
    allocated_amount: number;
    unallocated_amount: number;
    received_this_month: number;
    currency?: string | null;
    mixed_currencies: boolean;
};

export type PaymentIndexPageProps = SharedProps & {
    payments: PaginatedData<PaymentRecord>;
    paymentInsights: PaymentInsights;
    filters: TableFilters;
    counts: TableCount[];
    portfolioOptions: Array<{ id: number; name: string }>;
    propertyOptions: PropertyOption[];
    statusOptions: string[];
    typeOptions: string[];
    methodOptions: string[];
};

export type PaymentProof = {
    id: number;
    title: string;
    original_name: string;
    file_size: number;
    status: 'pending' | 'accepted' | 'rejected' | 'superseded';
    status_label: string;
    submission_note?: string | null;
    review_note?: string | null;
    submitted_by?: string | null;
    submitted_at?: string | null;
    reviewed_at?: string | null;
    download_url: string;
    review_url?: string | null;
};

export type PaymentEvidence = {
    can_submit: boolean;
    upload_url: string;
    receipt_url?: string | null;
    proofs: PaymentProof[];
};

export type PaymentDetail = {
    header: ResourceHeaderProps;
    workflow: ResourceWorkflow;
    stats: DetailItem[];
    sections: DetailSection[];
    related: RelatedTable[];
    documents: ResourceDocument[];
    evidence: PaymentEvidence;
    timeline: ResourceTimelineEntry[];
};

export type PaymentDetailPageProps = SharedProps & {
    detailPage: PaymentDetail;
};

export type PaymentFormPageProps = SharedProps & {
    formPage: ResourceFormShellProps;
};
