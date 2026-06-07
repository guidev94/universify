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
    toast.innerHTML = `<span>${message}</span><button onclick="this.parentElement.remove()" class="ml-3 opacity-60 hover:opacity-100">✕</button>`;
    toast.style.transition = 'all 0.3s ease';
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(20px)'; setTimeout(() => toast.remove(), 300); }, 4000);
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: '2-digit' });
}

function formatDateTime(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }) + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
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

class EventStream {
    constructor() {
        this.handlers = {};
        this.connected = false;
        this.connect();
    }

    connect() {
        this.source = new EventSource('/api/events/stream');
        this.source.onmessage = (e) => {
            try {
                const event = JSON.parse(e.data);
                this.connected = true;
                if (this.handlers[event.type]) {
                    this.handlers[event.type].forEach(fn => fn(event.data));
                }
                if (this.handlers['*']) {
                    this.handlers['*'].forEach(fn => fn(event));
                }
            } catch (err) {}
        };
        this.source.onerror = () => {
            this.connected = false;
            this.source.close();
            setTimeout(() => this.connect(), 5000);
        };
    }

    on(type, handler) {
        if (!this.handlers[type]) this.handlers[type] = [];
        this.handlers[type].push(handler);
        return this;
    }

    off(type, handler) {
        if (this.handlers[type]) {
            this.handlers[type] = this.handlers[type].filter(h => h !== handler);
        }
        return this;
    }
}

const events = new EventStream();

function confirmModal(message, confirmText = 'Confirmar', danger = false) {
    return new Promise((resolve) => {
        const existing = document.getElementById('confirmModal');
        if (existing) existing.remove();

        const btnClass = danger ? '!bg-red-600 hover:!bg-red-500 !shadow-red-600/30' : '';
        const modal = document.createElement('div');
        modal.id = 'confirmModal';
        modal.className = 'fixed inset-0 z-[200] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" data-action="cancel"></div>
            <div class="relative glass-card p-6 w-full max-w-sm mx-auto">
                <p class="text-[14px] text-zinc-100 font-medium text-center mb-6">${message}</p>
                <div class="flex gap-3">
                    <button class="btn-ghost flex-1" data-action="cancel">Cancelar</button>
                    <button class="btn-accent flex-1 ${btnClass}" data-action="confirm">${confirmText}</button>
                </div>
            </div>
        `;

        modal.addEventListener('click', (e) => {
            const action = e.target.dataset.action || e.target.closest('[data-action]')?.dataset.action;
            if (action === 'confirm') { modal.remove(); resolve(true); }
            else if (action === 'cancel') { modal.remove(); resolve(false); }
        });

        document.body.appendChild(modal);
    });
}
