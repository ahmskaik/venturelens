<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    user: Object,
    organization: Object,
});

const open = ref(false);
const menuRef = ref(null);

const userInitial = computed(() => (props.user?.name?.[0] ?? '?').toUpperCase());

const screeningsRemaining = computed(() => {
    if (!props.organization) return 0;
    return Math.max(0, props.organization.screenings_quota - props.organization.screenings_used);
});

const usagePercent = computed(() => {
    if (!props.organization?.screenings_quota) return 0;
    return Math.min(100, Math.round((props.organization.screenings_used / props.organization.screenings_quota) * 100));
});

function toggle() {
    open.value = !open.value;
}

function close() {
    open.value = false;
}

function onDocumentClick(event) {
    if (menuRef.value && !menuRef.value.contains(event.target)) {
        close();
    }
}

onMounted(() => document.addEventListener('click', onDocumentClick));
onUnmounted(() => document.removeEventListener('click', onDocumentClick));
</script>

<template>
    <div ref="menuRef" class="relative">
        <button
            type="button"
            class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-700 transition hover:bg-slate-300"
            :title="user?.name"
            @click.stop="toggle"
        >
            {{ userInitial }}
        </button>

        <div
            v-show="open"
            class="absolute right-0 top-full z-50 mt-2 w-72 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card-lg"
        >
            <!-- Usage -->
            <div v-if="organization" class="border-b border-slate-100 p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-900">Screenings</span>
                    <Link href="/billing" class="rounded-lg bg-slate-900 px-3 py-1 text-xs font-medium text-white hover:bg-slate-800" @click="close">
                        Upgrade
                    </Link>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                    <span>Used {{ organization.screenings_used }} / {{ organization.screenings_quota }}</span>
                    <span>{{ screeningsRemaining }} left</span>
                </div>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-brand-600" :style="{ width: `${usagePercent}%` }" />
                </div>
            </div>

            <!-- Workspace -->
            <div v-if="organization" class="border-b border-slate-100 px-4 py-3">
                <p class="text-xs text-slate-500">Current workspace</p>
                <p class="mt-0.5 truncate text-sm font-semibold text-slate-900">{{ organization.name }}</p>
                <p class="text-xs capitalize text-slate-500">{{ organization.plan }} plan</p>
            </div>

            <!-- Links -->
            <nav class="py-1">
                <Link href="/ask" class="vl-menu-item" @click="close">Ask VentureLens</Link>
                <Link href="/settings" class="vl-menu-item" @click="close">Settings</Link>
                <Link href="/billing" class="vl-menu-item" @click="close">Subscription</Link>
                <Link href="/ai-operations" class="vl-menu-item" @click="close">AI operations</Link>
                <a href="/impact" target="_blank" class="vl-menu-item" @click="close">Impact report</a>
            </nav>

            <div class="border-t border-slate-100 p-2">
                <Link href="/logout" method="post" as="button" class="vl-menu-item w-full text-left text-red-600 hover:bg-red-50 hover:text-red-700" @click="close">
                    Sign out
                </Link>
            </div>
        </div>
    </div>
</template>
