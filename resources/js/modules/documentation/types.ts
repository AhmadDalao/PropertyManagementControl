import type { SharedProps } from '@/types';

export type QuickStart = {
    title: string;
    audience: string;
    summary: string;
    route: string;
    icon: string;
    steps?: string[];
};

export type RoleGuide = {
    role: string;
    title: string;
    summary: string;
    responsibilities: string[];
    routes: string[];
    icon: string;
};

export type Guide = {
    slug: string;
    title: string;
    audience: string;
    summary: string;
    route: string;
    icon: string;
    features: string[];
    steps: string[];
    rules: string[];
};

export type WorkflowTrack = {
    key: string;
    title: string;
    audience: string;
    summary: string;
    outcome: string;
    route: string;
    icon: string;
    steps: Array<{ label: string; route: string }>;
};

export type PageShortcut = {
    label: string;
    category: string;
    route: string;
    description: string;
    action: string;
    icon: string;
};

export type ControlCheck = {
    title: string;
    summary: string;
    route: string;
    icon: string;
    checks: string[];
};

export type ModuleStatus = {
    key: string;
    label: string;
    description: string;
    enabled: boolean;
};

export type DocumentationIndexPageProps = SharedProps & {
    audience: string;
    roleGuide: RoleGuide | null;
    guides: Guide[];
    quickStarts: QuickStart[];
    workflowTracks: WorkflowTrack[];
    pageShortcuts: PageShortcut[];
    controlChecks: ControlCheck[];
    moduleStatus: ModuleStatus[];
};

export type DocumentationGuidePageProps = SharedProps & {
    audience: string;
    guide: Guide;
    relatedGuides: Guide[];
};
