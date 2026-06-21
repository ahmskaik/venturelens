<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    href: { type: String, default: '/' },
    dark: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
});

const logoSrc = computed(() =>
    props.dark ? '/images/venturelens-logo-light.png' : '/images/venturelens-logo.png',
);
</script>

<template>
    <Link :href="href" class="group inline-flex items-center">
        <img
            :src="logoSrc"
            alt="VentureLens — Application Screening"
            :class="compact ? 'h-7 w-auto max-w-[9.5rem]' : 'h-9 w-auto max-w-[11rem]'"
            width="824"
            height="255"
            decoding="async"
        />
    </Link>
</template>
