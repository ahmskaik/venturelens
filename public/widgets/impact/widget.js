(function () {
    'use strict';

    const script = document.currentScript;
    if (!script) {
        return;
    }

    const baseUrl = (script.dataset.baseUrl || window.location.origin).replace(/\/$/, '');
    const theme = script.dataset.theme === 'dark' ? 'dark' : 'light';
    const intervalSec = Math.max(30, parseInt(script.dataset.refresh || '60', 10));
    const targetId = script.dataset.target;

    let mount = targetId ? document.getElementById(targetId) : null;
    if (!mount) {
        mount = document.createElement('div');
        script.parentNode.insertBefore(mount, script.nextSibling);
    }

    if (!document.querySelector('link[data-vl-impact-widget-css]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = `${baseUrl}/widgets/impact/widget.css`;
        link.setAttribute('data-vl-impact-widget-css', '1');
        document.head.appendChild(link);
    }

    const root = document.createElement('div');
    root.className = 'vl-impact-widget';
    root.dataset.theme = theme;
    root.setAttribute('role', 'region');
    root.setAttribute('aria-label', 'VentureLens live impact metrics');
    mount.appendChild(root);

    function formatUsd(value) {
        const n = Number(value);
        if (Number.isNaN(n)) {
            return '—';
        }
        return '$' + n.toLocaleString(undefined, { maximumFractionDigits: 0 });
    }

    function formatNum(value) {
        const n = Number(value);
        if (Number.isNaN(n)) {
            return '—';
        }
        return n.toLocaleString();
    }

    function renderLoading() {
        root.innerHTML = '<p class="vl-impact-widget__loading">Loading live impact metrics…</p>';
    }

    function renderError(message) {
        root.innerHTML = '<p class="vl-impact-widget__error">' + escapeHtml(message) + '</p>';
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function card(label, value) {
        return (
            '<div class="vl-impact-widget__card">' +
            '<p class="vl-impact-widget__label">' + escapeHtml(label) + '</p>' +
            '<p class="vl-impact-widget__value">' + escapeHtml(value) + '</p>' +
            '</div>'
        );
    }

    function render(data) {
        const b = data.business || {};
        const a = data.activity || {};
        const ai = data.ai_operations || {};
        const imp = data.impact || {};
        const updated = data.generated_at
            ? data.generated_at.slice(0, 19).replace('T', ' ') + ' UTC'
            : '—';

        root.innerHTML =
            '<div class="vl-impact-widget__header">' +
            '<div>' +
            '<p class="vl-impact-widget__title">VentureLens Impact</p>' +
            '<p class="vl-impact-widget__subtitle">Live competition metrics</p>' +
            '</div>' +
            '<span class="vl-impact-widget__badge">Live</span>' +
            '</div>' +
            '<div class="vl-impact-widget__grid">' +
            card('Arms-length revenue', formatUsd(b.arms_length_revenue_usd)) +
            card('Paying customers', formatNum(b.arms_length_paying_customers)) +
            card('Applications screened', formatNum(a.applications_screened)) +
            card('Gemini API calls', formatNum(a.gemini_api_calls)) +
            card('AI decisions (L2–L3)', formatNum(ai.pct_decisions_by_ai) + '%') +
            card('Founder hours saved', formatNum(imp.founder_hours_saved)) +
            '</div>' +
            '<div class="vl-impact-widget__footer">' +
            '<span>Updated ' + escapeHtml(updated) + '</span>' +
            '<a href="' + escapeHtml(baseUrl + '/impact') + '" target="_blank" rel="noopener">Full report →</a>' +
            '</div>';
    }

    async function refresh() {
        try {
            const res = await fetch(baseUrl + '/api/v1/impact.json', {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }
            render(await res.json());
        } catch (e) {
            renderError('Could not load metrics. Try again later.');
        }
    }

    renderLoading();
    refresh();
    setInterval(refresh, intervalSec * 1000);
})();
