/**
 * Shared SEO copy for public VentureLens pages.
 * Titles are passed to SeoHead without the site suffix (app.js adds " — VentureLens").
 */
export const seoDefaults = {
    home: {
        title: 'AI Startup Application Screening for Incubators & Accelerators',
        description:
            'Screen every startup application in minutes with Gemini AI scoring, risk flags, committee-ready summaries, and a full audit trail. Built for incubators and accelerators.',
        keywords: [
            'startup application screening',
            'incubator software',
            'accelerator tools',
            'AI venture evaluation',
            'cohort intake platform',
        ],
    },
    impact: {
        title: 'Live Impact & AI Operations Metrics',
        description:
            'Public, auto-computed VentureLens metrics: paying customers, AI screening volume, agent autonomy levels, and business viability KPIs from production data.',
        keywords: ['VentureLens impact', 'AI operations metrics', 'startup screening KPIs', 'Gemini XPRIZE'],
    },
    login: {
        title: 'Sign In',
        description:
            'Sign in to VentureLens to manage cohort intake, review AI-screened startup applications, and run your incubator selection workflow.',
        keywords: ['VentureLens login', 'incubator dashboard', 'accelerator sign in'],
    },
    register: {
        title: 'Start Free Trial for Incubators',
        description:
            'Create your VentureLens incubator account. Screen startup applications with AI, manage cohorts, and give committees structured scores and summaries.',
        keywords: ['incubator registration', 'accelerator software trial', 'startup screening SaaS'],
    },
    founderRegister: {
        title: 'Founder Registration',
        description:
            'Register as a startup founder on VentureLens. Track your accelerator applications, update your venture profile, and monitor screening status.',
        keywords: ['founder registration', 'startup application portal', 'accelerator applicant account'],
    },
    apply: (programName, organizationName) => ({
        title: `Apply to ${programName}`,
        description: `Submit your startup application to ${programName}${organizationName ? ` by ${organizationName}` : ''}. VentureLens powers structured intake and AI-powered screening.`,
        keywords: ['startup application', programName, 'accelerator apply', 'incubator intake'].filter(Boolean),
    }),
    applyStatus: {
        title: 'Application Status',
        description: 'Track your startup application screening status.',
        noindex: true,
    },
};

export function buildHomeJsonLd(appUrl, seo) {
    const base = appUrl.replace(/\/$/, '');

    return {
        '@context': 'https://schema.org',
        '@graph': [
            {
                '@type': 'Organization',
                '@id': `${base}/#organization`,
                name: seo.siteName,
                url: base,
                logo: `${base}/images/venturelens-logo.png`,
                description: seo.description,
                email: seo.organization?.contactEmail,
            },
            {
                '@type': 'WebSite',
                '@id': `${base}/#website`,
                url: base,
                name: seo.siteName,
                description: seo.tagline,
                publisher: { '@id': `${base}/#organization` },
                inLanguage: seo.locale?.replace('_', '-') ?? 'en',
            },
            {
                '@type': 'SoftwareApplication',
                '@id': `${base}/#software`,
                name: seo.siteName,
                applicationCategory: 'BusinessApplication',
                operatingSystem: 'Web',
                description: seo.description,
                offers: {
                    '@type': 'Offer',
                    price: '199',
                    priceCurrency: 'USD',
                    description: 'Per cohort subscription pricing',
                },
                featureList: [
                    'Gemini AI application screening',
                    'Risk flag detection',
                    'Committee decision workflows',
                    'Founder communication drafts',
                    'Multi-cohort intake management',
                ],
                provider: { '@id': `${base}/#organization` },
            },
        ],
    };
}

export function buildImpactJsonLd(appUrl, seo) {
    const base = appUrl.replace(/\/$/, '');

    return {
        '@context': 'https://schema.org',
        '@type': 'WebPage',
        name: 'VentureLens Impact Report',
        description: seo.impact?.description ?? seoDefaults.impact.description,
        url: `${base}/impact`,
        isPartOf: { '@id': `${base}/#website` },
        about: { '@id': `${base}/#software` },
        inLanguage: seo.locale?.replace('_', '-') ?? 'en',
    };
}

export function buildApplyJsonLd(appUrl, program) {
    const base = appUrl.replace(/\/$/, '');

    return {
        '@context': 'https://schema.org',
        '@type': 'WebPage',
        name: `Apply to ${program.name}`,
        description: program.description || `Startup application intake for ${program.name}.`,
        url: `${base}/apply/${program.slug}`,
        isPartOf: { '@id': `${base}/#website` },
    };
}
