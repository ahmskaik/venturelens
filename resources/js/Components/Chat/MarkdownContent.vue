<script setup>
import { computed } from 'vue';
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { resolveTextDirection, textDirectionClasses } from '../../utils/textDirection';

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

const direction = computed(() => resolveTextDirection(props.content));
const directionClass = computed(() => textDirectionClasses(props.content));
</script>

<template>
    <div
        class="vl-markdown"
        :class="directionClass"
        :dir="direction"
        v-html="html"
    />
</template>
