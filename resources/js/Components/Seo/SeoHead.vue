<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    keywords: { type: [String, Array], default: '' },
    image: { type: String, default: '' },
    url: { type: String, default: '' },
    type: { type: String, default: 'website' },
    robots: { type: String, default: '' },
    noindex: { type: Boolean, default: false },
    canonical: { type: String, default: '' },
    jsonLd: { type: [Object, Array], default: null },
});

const page = usePage();

const seo = computed(() => page.props.seo ?? {});

const siteName = computed(() => seo.value.siteName ?? 'VentureLens');

const fullTitle = computed(() => {
    if (!props.title) {
        return siteName.value;
    }

    if (props.title.includes(siteName.value)) {
        return props.title;
    }

    return `${props.title} | ${siteName.value}`;
});

const metaDescription = computed(
    () => props.description || seo.value.description || '',
);

const keywordsContent = computed(() => {
    if (Array.isArray(props.keywords)) {
        return props.keywords.join(', ');
    }

    if (props.keywords) {
        return props.keywords;
    }

    return (seo.value.keywords ?? []).join(', ');
});

const absoluteUrl = (path) => {
    const base = (seo.value.appUrl ?? '').replace(/\/$/, '');
    if (!path) {
        return base;
    }

    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    return `${base}${path.startsWith('/') ? path : `/${path}`}`;
};

const pageUrl = computed(() => {
    if (props.url) {
        return absoluteUrl(props.url);
    }

    if (props.canonical) {
        return absoluteUrl(props.canonical);
    }

    const path = page.url?.split('?')[0] ?? '/';

    return absoluteUrl(path);
});

const canonicalUrl = computed(() => {
    if (props.canonical) {
        return absoluteUrl(props.canonical);
    }

    return pageUrl.value;
});

const ogImage = computed(() => absoluteUrl(props.image || seo.value.ogImage || '/images/og-default.svg'));

const robotsContent = computed(() => {
    if (props.robots) {
        return props.robots;
    }

    if (props.noindex) {
        return 'noindex, nofollow';
    }

    return 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
});

const jsonLdScripts = computed(() => {
    if (!props.jsonLd) {
        return [];
    }

    return Array.isArray(props.jsonLd) ? props.jsonLd : [props.jsonLd];
});
</script>

<template>
    <Head :title="title || undefined">
        <meta head-key="description" name="description" :content="metaDescription" />
        <meta v-if="keywordsContent" head-key="keywords" name="keywords" :content="keywordsContent" />
        <meta head-key="author" name="author" :content="seo.author ?? 'VentureLens'" />
        <meta head-key="robots" name="robots" :content="robotsContent" />
        <meta head-key="googlebot" name="googlebot" :content="robotsContent" />

        <link head-key="canonical" rel="canonical" :href="canonicalUrl" />

        <meta head-key="og:type" property="og:type" :content="type" />
        <meta head-key="og:site_name" property="og:site_name" :content="siteName" />
        <meta head-key="og:title" property="og:title" :content="fullTitle" />
        <meta head-key="og:description" property="og:description" :content="metaDescription" />
        <meta head-key="og:url" property="og:url" :content="pageUrl" />
        <meta head-key="og:image" property="og:image" :content="ogImage" />
        <meta head-key="og:image:width" property="og:image:width" :content="String(seo.ogImageWidth ?? 1200)" />
        <meta head-key="og:image:height" property="og:image:height" :content="String(seo.ogImageHeight ?? 630)" />
        <meta head-key="og:locale" property="og:locale" :content="(seo.locale ?? 'en').replace('_', '-')" />

        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <meta v-if="seo.twitterHandle" head-key="twitter:site" name="twitter:site" :content="seo.twitterHandle" />
        <meta head-key="twitter:title" name="twitter:title" :content="fullTitle" />
        <meta head-key="twitter:description" name="twitter:description" :content="metaDescription" />
        <meta head-key="twitter:image" name="twitter:image" :content="ogImage" />

        <component
            :is="'script'"
            v-for="(schema, index) in jsonLdScripts"
            :key="`json-ld-${index}`"
            type="application/ld+json"
            v-html="JSON.stringify(schema)"
        />
    </Head>
</template>
