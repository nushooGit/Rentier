export type DocumentCategory =
    | 'lease_contract'
    | 'addendum'
    | 'commodatum'
    | 'handover_report'
    | 'invoice_receipt'
    | 'property_document'
    | 'other';

export type DocumentCategoryOption = {
    value: DocumentCategory;
    label: string;
};

export type DocumentPropertyOption = {
    id: number;
    name: string;
    city: string;
};

export type DocumentLeaseOption = {
    id: number;
    property_id: number;
    renter_name: string;
    start_date: string;
    end_date: string | null;
};

export type RentierDocument = {
    id: number;
    category: DocumentCategory;
    category_label: string;
    document_date: string;
    expires_on: string | null;
    original_name: string;
    mime_type: string;
    size_bytes: number;
    created_at: string | null;
    property: DocumentPropertyOption | null;
    lease: {
        id: number;
        renter_name: string;
    } | null;
};
