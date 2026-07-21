export type GlobalSearchResult = {
    group: string;
    title: string;
    subtitle: string;
    badge: string;
    url: string;
};

export type GlobalSearchResponse = {
    ok: boolean;
    query: string;
    results: GlobalSearchResult[];
    message: string;
    direct_url: string;
};

export type SearchFieldProps = {
    className: string;
    query: string;
    placeholder: string;
    open: boolean;
    loading: boolean;
    payload: GlobalSearchResponse | null;
    groupedResults: Record<string, GlobalSearchResult[]>;
    setQuery: (value: string) => void;
    setOpen: (value: boolean) => void;
    resultsId: string;
    autoFocus?: boolean;
};
