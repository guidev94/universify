function getCSRFToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

async function api(url, options = {}) {
    const defaults = {
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': getCSRFToken(),
        },
    };

    const config = { ...defaults, ...options };
    if (options.headers) {
        config.headers = { ...defaults.headers, ...options.headers };
    }

    const response = await fetch(url, config);

    if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(data.error || `Erro ${response.status}`);
    }

    const contentType = response.headers.get('Content-Type') || '';
    if (contentType.includes('application/json')) {
        return response.json();
    }
    return response;
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function showToast(message, type = 'info') {
    document.querySelectorAll('.toast').forEach(t => t.remove());
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3500);
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: '2-digit' });
}

function tierBadge(tier) {
    const map = { hot: 'badge-hot', warm: 'badge-warm', cold: 'badge-cold' };
    const labels = { hot: 'Quente', warm: 'Morno', cold: 'Frio' };
    return `<span class="badge ${map[tier] || 'badge-cold'}">${labels[tier] || tier}</span>`;
}

function statusBadge(status) {
    const map = {
        new: 'badge-new', contacted: 'badge-contacted',
        negotiating: 'badge-negotiating', closed_won: 'badge-closed-won', closed_lost: 'badge-closed-lost'
    };
    const labels = {
        new: 'Novo', contacted: 'Contatado',
        negotiating: 'Negociando', closed_won: 'Fechado', closed_lost: 'Perdido'
    };
    return `<span class="badge ${map[status] || 'badge-new'}">${labels[status] || status}</span>`;
}

function scoreBadge(score) {
    let cls = 'score-cold';
    if (score >= 80) cls = 'score-hot';
    else if (score >= 50) cls = 'score-warm';
    return `<span class="score-pill ${cls}">${score}</span>`;
}
