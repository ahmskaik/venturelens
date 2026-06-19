import { ref, watch, onMounted } from 'vue';

const STORAGE_KEY = 'vl-evidence-explorer-theme';

export function useTheme() {
    const isDark = ref(false);

    function apply(dark) {
        isDark.value = dark;
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem(STORAGE_KEY, dark ? 'dark' : 'light');
    }

    function toggle() {
        apply(!isDark.value);
    }

    onMounted(() => {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored === 'dark' || stored === 'light') {
            apply(stored === 'dark');
        } else {
            apply(window.matchMedia('(prefers-color-scheme: dark)').matches);
        }
    });

    return { isDark, toggle };
}
