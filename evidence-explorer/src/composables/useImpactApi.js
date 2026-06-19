import { ref, onMounted, onUnmounted } from 'vue';

const DEFAULT_BASE = 'https://venturelens.app';
const REFRESH_MS = 60_000;

export function useImpactApi() {
    const baseUrl = (import.meta.env.VITE_API_BASE_URL || DEFAULT_BASE).replace(/\/$/, '');
    const data = ref(null);
    const loading = ref(true);
    const error = ref(null);

    let timer = null;

    async function fetchImpact() {
        try {
            error.value = null;
            const res = await fetch(`${baseUrl}/api/v1/impact.json`, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }
            data.value = await res.json();
        } catch (e) {
            error.value = e.message || 'Failed to load metrics';
        } finally {
            loading.value = false;
        }
    }

    function refresh() {
        loading.value = !data.value;
        return fetchImpact();
    }

    onMounted(() => {
        fetchImpact();
        timer = setInterval(fetchImpact, REFRESH_MS);
    });

    onUnmounted(() => {
        if (timer) {
            clearInterval(timer);
        }
    });

    return { data, loading, error, refresh, baseUrl };
}
