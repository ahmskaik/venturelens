<script setup>
import { computed } from 'vue';
import DOMPurify from 'dompurify';
import { marked } from 'marked';

const props = defineProps({
    content: {
        type: String,
        default: '',
    },
});

marked.setOptions({
    breaks: true,
    gfm: true,
});

const html = computed(() => {
    if (!props.content) {
        return '';
    }

    return DOMPurify.sanitize(marked.parse(props.content), {
        USE_PROFILES: { html: true },
    });
});
</script>

<template>
    <div class="vl-markdown" v-html="html" />
</template>
