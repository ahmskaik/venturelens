export const AUTONOMY_LABELS = {
    0: 'L0 Observe',
    1: 'L1 Recommend',
    2: 'L2 Act w/ approval',
    3: 'L3 Autonomous',
};

export const AUTONOMY_CLASSES = {
    0: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    1: 'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
    2: 'bg-sky-50 text-sky-800 dark:bg-sky-950 dark:text-sky-200',
    3: 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
};

export const AGENT_CLASSES = {
    screening: 'bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-200',
    growth: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200',
    support: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-200',
    onboarding: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
    finance: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    success: 'bg-pink-100 text-pink-800 dark:bg-pink-950 dark:text-pink-200',
};

export const AGENT_NAMES = ['screening', 'growth', 'support', 'onboarding', 'finance', 'success'];

export function formatUsd(value) {
    const n = Number(value);
    if (Number.isNaN(n)) return '—';
    return '$' + n.toLocaleString(undefined, { maximumFractionDigits: 0 });
}

export function formatNum(value, decimals = 0) {
    const n = Number(value);
    if (Number.isNaN(n)) return '—';
    return n.toLocaleString(undefined, { maximumFractionDigits: decimals });
}

export function formatTimestamp(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function formatRelative(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    const diff = Date.now() - d.getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins}m ago`;
    const hours = Math.floor(mins / 60);
    if (hours < 48) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    return `${days}d ago`;
}

export function executionKey(row) {
    return `${row.created_at}|${row.agent_name}|${row.step}`;
}

export function mergeExecutions(sample, recent) {
    const map = new Map();
    for (const row of sample || []) {
        map.set(executionKey(row), row);
    }
    for (const row of recent || []) {
        map.set(executionKey(row), row);
    }
    return [...map.values()].sort(
        (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
    );
}
