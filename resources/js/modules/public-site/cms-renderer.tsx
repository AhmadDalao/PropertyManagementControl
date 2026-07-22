import type { ComponentType } from 'react';

import {
    FeatureGridSection,
    MetricsSection,
    RoleCardsSection,
} from './sections/card-sections';
import { DashboardPreviewSection } from './sections/dashboard-preview-section';
import { FaqSection } from './sections/faq-section';
import { FinalCtaSection } from './sections/final-cta-section';
import { GenericSection } from './sections/generic-section';
import { HeroSection } from './sections/hero-section';
import { OperationsStripSection } from './sections/operations-strip-section';
import { WorkflowSection } from './sections/workflow-section';
import type { CmsContent, CmsRendererProps } from './types';

const sectionComponents: Record<
    string,
    ComponentType<{ content: CmsContent }>
> = {
    hero: HeroSection,
    metrics: MetricsSection,
    role_cards: RoleCardsSection,
    workflow: WorkflowSection,
    dashboard_preview: DashboardPreviewSection,
    feature_grid: FeatureGridSection,
    operations_strip: OperationsStripSection,
    faq: FaqSection,
    final_cta: FinalCtaSection,
};

export function CmsRenderer({ sections, locale }: CmsRendererProps) {
    return (
        <>
            {sections.map((item) => {
                if (!item.section) {
                    return null;
                }

                const Section =
                    sectionComponents[item.section.section_type] ??
                    GenericSection;
                const content =
                    (locale === 'ar'
                        ? item.section.content_ar
                        : item.section.content_en) ?? {};

                return <Section key={item.id} content={content} />;
            })}
        </>
    );
}
