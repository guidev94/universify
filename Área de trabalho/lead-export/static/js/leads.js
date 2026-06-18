let currentPage = 1;
let selectedIds = new Set();
let debounceTimer = null;
let activePills = { has_email: false, has_phone: false, has_website: false };

function getFilters() {
    return {
        search: document.getElementById('searchInput').value.trim(),
        tier: document.getElementById('filterTier').value,
        status: document.getElementById('filterStatus').value,
        source: document.getElementById('filterSource').value,
        category: document.getElementById('filterCategory').value,
        sort: document.getElementById('sortBy').value,
        verified: document.getElementById('filterVerified').checked,
        days: document.getElementById('filterDays') ? document.getElementById('filterDays').value : '',
        has_email: activePills.has_email,
        has_phone: activePills.has_phone,
        has_website: activePills.has_website,
    };
}

function togglePill(btn, key) {
    activePills[key] = !activePills[key];
    if (activePills[key]) {
        btn.classList.add('!border-blue-500/40', '!text-blue-400', '!bg-blue-500/10');
    } else {
        btn.classList.remove('!border-blue-500/40', '!text-blue-400', '!bg-blue-500/10');
    }
    loadLeads(1);
}

function applyPreset(preset) {
    // Reset all filters first
    document.getElementById('filterTier').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterSource').value = '';
    document.getElementById('filterDays').value = '';
    document.getElementById('filterVerified').checked = false;
    activePills = { has_email: false, has_phone: false, has_website: false };
    document.querySelectorAll('.filter-pill[data-filter]').forEach(b => b.classList.remove('!border-blue-500/40', '!text-blue-400', '!bg-blue-500/10'));

    if (preset === 'hot_new') {
        document.getElementById('filterTier').value = 'hot';
        document.getElementById('filterStatus').value = 'new';
    } else if (preset === 'this_week') {
        document.getElementById('filterDays').value = '7';
    } else if (preset === 'no_contact') {
        document.getElementById('filterStatus').value = 'new';
        activePills.has_phone = true;
        const btn = document.querySelector('[data-filter="has_phone"]');
        if (btn) btn.classList.add('!border-blue-500/40', '!text-blue-400', '!bg-blue-500/10');
    }
    loadLeads(1);
}

async function loadLeads(page = 1) {
    currentPage = page;
    const filters = getFilters();

    const params = new URLSearchParams({
        page: page,
        per_page: 25,
        sort: filters.sort,
        order: 'desc',
    });

    if (filters.search) params.set('search', filters.search);
    if (filters.tier) params.set('tier', filters.tier);
    if (filters.status) params.set('status', filters.status);
    if (filters.source) params.set('source', filters.source);
    if (filters.category) params.set('category', filters.category);
    if (filters.verified) params.set('verified', '1');
    if (filters.sort === 'name') params.set('order', 'asc');
    if (filters.days) params.set('created_days', filters.days);
    if (filters.has_email) params.set('has_email', '1');
    if (filters.has_phone) params.set('has_phone', '1');
    if (filters.has_website) params.set('has_website', '1');

    try {
        const data = await api(`/api/leads?${params}`);
        renderTable(data.leads);
        renderPagination(data.page, data.pages, data.total);

        const from = data.leads.length ? (data.page - 1) * 25 + 1 : 0;
        const to = from + data.leads.length - 1;
        document.getElementById('showingFrom').textContent = from;
        document.getElementById('showingTo').textContent = to > 0 ? to : 0;
        document.getElementById('totalLeads').textContent = data.total;
    } catch (e) {
        showToast('Erro ao carregar leads', 'error');
    }
}

function renderTable(leads) {
    const tbody = document.getElementById('leadsTableBody');

    if (!leads || leads.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-zinc-600 py-12">Nenhum lead encontrado</td></tr>';
        return;
    }

    tbody.innerHTML = leads.map(lead => {
        const checked = selectedIds.has(lead.id) ? 'checked' : '';
        const whatsappLink = lead.whatsapp
            ? `<a href="https://wa.me/${lead.whatsapp.replace(/\\D/g, '')}" target="_blank" rel="noopener" class="text-green-400 hover:text-green-300 text-xs">${escapeHtml(lead.whatsapp)}</a>`
            : '<span class="text-zinc-600">—</span>';

        const siteLink = lead.website
            ? `<a href="${escapeHtml(lead.website)}" target="_blank" rel="noopener" class="text-blue-400 hover:text-blue-300 text-[11px] truncate max-w-[120px] inline-block">${escapeHtml(lead.website.replace(/https?:\/\/(www\.)?/, '').split('/')[0])}</a>`
            : '<span class="text-zinc-700">—</span>';

        const rowClass = lead.tier === 'hot' ? 'border-l-2 border-l-amber-400/60 bg-amber-500/[0.02]' : lead.tier === 'warm' ? 'border-l-2 border-l-orange-400/40' : '';
        const qualBadge = lead.score >= 80 ? '<span class="ml-1 px-1 py-0.5 rounded text-[8px] font-bold bg-amber-500/15 text-amber-400">QUALIFICADO</span>' : '';

        return `
            <tr class="${rowClass}">
                <td><input type="checkbox" class="lead-checkbox rounded-sm cursor-pointer" data-id="${lead.id}" ${checked} onchange="toggleSelect(${lead.id})"></td>
                <td class="font-medium text-white cursor-pointer hover:text-blue-400 transition-colors" onclick="openLeadDetail(${lead.id})">${escapeHtml(lead.name)}${qualBadge}</td>
                <td class="group">${lead.email ? `<span class="inline-flex items-center gap-1"><a href="mailto:${escapeHtml(lead.email.split(';')[0])}" class="text-zinc-300 hover:text-blue-400 truncate max-w-[140px] inline-block text-[12px]">${escapeHtml(lead.email.split(';')[0])}</a><button onclick="event.stopPropagation(); navigator.clipboard.writeText('${lead.email.split(';')[0]}'); showToast('Email copiado!','success')" class="opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-white/5 transition-all" title="Copiar"><svg class="w-3 h-3 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></button></span>` : '<span class="text-zinc-700">—</span>'}</td>
                <td class="group">${lead.phone ? `<span class="inline-flex items-center gap-1"><a href="tel:${lead.phone.split(';')[0]}" class="text-zinc-300 hover:text-emerald-400 text-[12px]">${escapeHtml(lead.phone.split(';')[0])}</a><button onclick="event.stopPropagation(); navigator.clipboard.writeText('${lead.phone.split(';')[0]}'); showToast('Telefone copiado!','success')" class="opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-white/5 transition-all" title="Copiar"><svg class="w-3 h-3 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></button></span>` : '<span class="text-zinc-700">—</span>'}</td>
                <td>${siteLink}</td>
                <td>${scoreBadge(lead.score)}</td>
                <td>${statusBadge(lead.status)}</td>
                <td class="text-[11px] text-zinc-500 whitespace-nowrap">${formatDateTime(lead.created_at)}</td>
                <td>
                    <div class="flex items-center gap-0.5">
                        <button onclick="sendWhatsApp(${lead.id})" title="WhatsApp" class="p-1.5 rounded-lg hover:bg-green-500/10 transition-colors">
                            <svg class="w-3.5 h-3.5 text-green-400" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.612.638l4.687-1.228A11.953 11.953 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.347 0-4.518-.803-6.237-2.15l-.364-.298-3.02.792.83-3.042-.327-.39A9.96 9.96 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/></svg>
                        </button>
                        <button onclick="sendEmail(${lead.id})" title="Email" class="p-1.5 rounded-lg hover:bg-blue-500/10 transition-colors">
                            <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </button>
                        <button onclick="changeStatus(${lead.id}, 'negotiating')" title="Negociando" class="p-1.5 rounded-lg hover:bg-orange-500/10 transition-colors">
                            <svg class="w-3.5 h-3.5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </button>
                        <button onclick="openCloseModal(${lead.id}, this.dataset.name)" data-name="${escapeHtml(lead.name).replace(/'/g, '&#39;')}" title="Fechar Negócio" class="p-1.5 rounded-lg hover:bg-emerald-500/10 transition-colors">
                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </button>
                        <button onclick="deleteLead(${lead.id})" title="Excluir" class="btn-delete p-1.5 rounded-lg hover:bg-red-500/10 transition-colors">
                            <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function renderPagination(current, total, totalItems) {
    const container = document.getElementById('pagination');
    if (total <= 1) { container.innerHTML = ''; return; }

    let html = '';
    const prevDisabled = current <= 1 ? 'opacity-30 pointer-events-none' : '';
    const nextDisabled = current >= total ? 'opacity-30 pointer-events-none' : '';

    html += `<button onclick="loadLeads(${current - 1})" class="btn-secondary !px-2 !py-1 text-xs ${prevDisabled}">←</button>`;

    const start = Math.max(1, current - 2);
    const end = Math.min(total, current + 2);

    for (let i = start; i <= end; i++) {
        const active = i === current ? '!bg-blue-500/20 !border-blue-500/30 !text-blue-400' : '';
        html += `<button onclick="loadLeads(${i})" class="btn-secondary !px-3 !py-1 text-xs ${active}">${i}</button>`;
    }

    html += `<button onclick="loadLeads(${current + 1})" class="btn-secondary !px-2 !py-1 text-xs ${nextDisabled}">→</button>`;
    container.innerHTML = html;
}

function toggleSelect(id) {
    if (selectedIds.has(id)) selectedIds.delete(id);
    else selectedIds.add(id);
    updateBulkBar();
}

function updateBulkBar() {
    const bar = document.getElementById('bulkActions');
    if (selectedIds.size > 0) {
        bar.classList.remove('hidden');
        document.getElementById('selectedCount').textContent = selectedIds.size;
    } else {
        bar.classList.add('hidden');
    }
}

function clearSelection() {
    selectedIds.clear();
    document.querySelectorAll('.lead-checkbox').forEach(cb => cb.checked = false);
    updateBulkBar();
}

async function changeStatus(id, status) {
    try {
        await api(`/api/leads/${id}`, { method: 'PATCH', body: JSON.stringify({ status }) });
        showToast(`Lead marcado como ${status}`, 'success');
        loadLeads(currentPage);
    } catch (e) {
        showToast(e.message, 'error');
    }
}

async function deleteLead(id) {
    if (!await confirmModal('Excluir este lead?', 'Excluir', true)) return;
    try {
        await api(`/api/leads/${id}`, { method: 'DELETE' });
        showToast('Lead excluído', 'success');
        loadLeads(currentPage);
    } catch (e) {
        showToast(e.message, 'error');
    }
}

async function bulkDelete() {
    if (!await confirmModal(`Excluir ${selectedIds.size} leads?`, 'Excluir Todos', true)) return;
    try {
        await api('/api/leads/bulk-delete', {
            method: 'POST',
            body: JSON.stringify({ ids: Array.from(selectedIds) }),
        });
        showToast(`${selectedIds.size} leads excluídos`, 'success');
        selectedIds.clear();
        updateBulkBar();
        loadLeads(currentPage);
    } catch (e) {
        showToast(e.message, 'error');
    }
}

document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.lead-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = this.checked;
        const id = parseInt(cb.dataset.id);
        if (this.checked) selectedIds.add(id);
        else selectedIds.delete(id);
    });
    updateBulkBar();
});

['searchInput'].forEach(id => {
    document.getElementById(id).addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadLeads(1), 400);
    });
});

['filterTier', 'filterStatus', 'filterSource', 'filterCategory', 'sortBy', 'filterDays'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', () => loadLeads(1));
});

async function loadCategories() {
    try {
        const data = await api('/api/leads/categories');
        const select = document.getElementById('filterCategory');
        data.categories.forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat;
            opt.textContent = cat.charAt(0).toUpperCase() + cat.slice(1);
            select.appendChild(opt);
        });
    } catch (e) {}
}
loadCategories();

document.getElementById('filterVerified').addEventListener('change', () => loadLeads(1));

async function sendWhatsApp(id) {
    try {
        const data = await api(`/api/leads/${id}/action/whatsapp`);
        window.open(data.url, '_blank');
        showToast('WhatsApp aberto — lead marcado como contatado', 'success');
        loadLeads(currentPage);
    } catch (e) {
        showToast(e.message, 'error');
    }
}

async function sendEmail(id) {
    try {
        const data = await api(`/api/leads/${id}/action/email`);
        window.open(data.url, '_blank');
        showToast('Email aberto — lead marcado como contatado', 'success');
        loadLeads(currentPage);
    } catch (e) {
        showToast(e.message, 'error');
    }
}

let closeModalLeadId = null;

function openCloseModal(id, name) {
    closeModalLeadId = id;
    document.getElementById('closeModalName').textContent = name;
    document.getElementById('closeDealValue').value = '';
    document.getElementById('closeDealNotes').value = '';
    document.getElementById('closeModal').classList.remove('hidden');
}

function hideCloseModal() {
    document.getElementById('closeModal').classList.add('hidden');
    closeModalLeadId = null;
}

async function submitClose() {
    if (!closeModalLeadId) return;
    const value = parseFloat(document.getElementById('closeDealValue').value) || 0;
    const notes = document.getElementById('closeDealNotes').value.trim();

    try {
        await api(`/api/leads/${closeModalLeadId}/close`, {
            method: 'PATCH',
            body: JSON.stringify({ deal_value: value, deal_notes: notes }),
        });
        showToast(`Negócio fechado! R$ ${value.toLocaleString('pt-BR')}`, 'success');
        hideCloseModal();
        loadLeads(currentPage);
    } catch (e) {
        showToast(e.message, 'error');
    }
}

async function openLeadDetail(id) {
    const modal = document.getElementById('leadDetailModal');
    const title = document.getElementById('leadDetailName');
    const body = document.getElementById('leadDetailBody');

    modal.classList.remove('hidden');
    body.innerHTML = '<div class="flex justify-center py-8"><div class="w-6 h-6 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"></div></div>';
    title.textContent = '';

    try {
        const lead = await api(`/api/leads/${id}`);
        title.textContent = lead.name;

        const tierGradients = { hot: 'from-amber-500/20 to-orange-600/5', warm: 'from-orange-500/15 to-amber-500/5', cold: 'from-zinc-500/15 to-zinc-700/5' };
        const tierLabels = { hot: 'Quente', warm: 'Morno', cold: 'Frio' };
        const tierColors = { hot: 'text-amber-400', warm: 'text-orange-400', cold: 'text-zinc-400' };
        const statusLabels = { new: 'Novo', contacted: 'Contatado', negotiating: 'Negociando', closed_won: 'Fechado', closed_lost: 'Perdido' };

        const emails = (lead.email || '').split(';').filter(e => e.trim());
        const phones = (lead.phone || '').split(';').filter(p => p.trim());
        const mainPhone = lead.whatsapp || (phones[0] || '');
        const whatsappUrl = mainPhone ? `https://wa.me/${mainPhone.replace(/\D/g, '')}` : '';
        const domain = lead.website ? lead.website.replace(/https?:\/\/(www\.)?/, '').split('/')[0] : '';

        const scorePercent = Math.min(lead.score, 100);
        const scoreColor = lead.score >= 80 ? '#f59e0b' : lead.score >= 50 ? '#f97316' : '#64748b';

        body.innerHTML = `
            <div class="rounded-2xl bg-gradient-to-br ${tierGradients[lead.tier] || tierGradients.cold} p-5 mb-4 border border-white/5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider ${tierColors[lead.tier] || tierColors.cold}">${tierLabels[lead.tier] || '—'}</span>
                            ${lead.site_verified ? '<span class="text-[10px] text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full">&#10003; Verificado</span>' : ''}
                        </div>
                        <p class="text-[11px] text-zinc-500">${lead.source || 'Fonte desconhecida'} &middot; ${lead.category || 'Sem categoria'}</p>
                    </div>
                    <div class="text-right">
                        <div class="relative w-14 h-14">
                            <svg class="w-14 h-14 -rotate-90" viewBox="0 0 56 56">
                                <circle cx="28" cy="28" r="24" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="4"/>
                                <circle cx="28" cy="28" r="24" fill="none" stroke="${scoreColor}" stroke-width="4" stroke-linecap="round" stroke-dasharray="${scorePercent * 1.508} 200"/>
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-[14px] font-bold text-zinc-100">${lead.score}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between mb-3">
                <p class="text-[10px] text-zinc-500 font-semibold uppercase tracking-wider">Contatos</p>
                <select onchange="changeLeadStatus(${lead.id}, this.value)" class="select-glass !py-1 !px-2 !text-[11px] !rounded-lg">
                    ${['new','contacted','negotiating','closed_won','closed_lost'].map(s =>
                        `<option value="${s}" ${lead.status === s ? 'selected' : ''}>${statusLabels[s]}</option>`
                    ).join('')}
                </select>
            </div>

            <div class="space-y-1.5">
                ${emails.map(email => `
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-white/[0.02] border border-white/5 group hover:border-blue-500/20 transition-colors">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-7 h-7 rounded-lg bg-blue-500/10 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <a href="mailto:${escapeHtml(email.trim())}" class="text-[12px] text-zinc-200 hover:text-blue-400 truncate transition-colors">${escapeHtml(email.trim())}</a>
                        </div>
                        <button onclick="navigator.clipboard.writeText('${email.trim()}'); showToast('Email copiado!','success')" class="opacity-0 group-hover:opacity-100 p-1.5 rounded-lg hover:bg-white/5 transition-all">
                            <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                    </div>
                `).join('')}

                ${phones.map(p => `
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-white/[0.02] border border-white/5 group hover:border-emerald-500/20 transition-colors">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-7 h-7 rounded-lg bg-emerald-500/10 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <span class="text-[12px] text-zinc-200">${escapeHtml(p.trim())}</span>
                        </div>
                        <button onclick="navigator.clipboard.writeText('${p.trim()}'); showToast('Telefone copiado!','success')" class="opacity-0 group-hover:opacity-100 p-1.5 rounded-lg hover:bg-white/5 transition-all">
                            <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                    </div>
                `).join('')}

                ${lead.website ? `
                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white/[0.02] border border-white/5 hover:border-blue-500/20 transition-colors">
                        <div class="w-7 h-7 rounded-lg bg-blue-500/10 flex items-center justify-center shrink-0">
                            <img src="https://www.google.com/s2/favicons?domain=${domain}&sz=16" alt="" class="w-4 h-4 rounded-sm" onerror="this.style.display='none'">
                        </div>
                        <a href="${escapeHtml(lead.website)}" target="_blank" class="text-[12px] text-blue-400 hover:text-blue-300 truncate transition-colors">${escapeHtml(domain)}</a>
                    </div>
                ` : ''}
            </div>

            <div class="grid grid-cols-2 gap-2 mt-4">
                <div class="p-3 rounded-xl bg-white/[0.02] border border-white/5">
                    <p class="text-[9px] text-zinc-600 uppercase tracking-wide">Criado em</p>
                    <p class="text-[12px] text-zinc-300 font-medium mt-1">${formatDate(lead.created_at)}</p>
                </div>
                <div class="p-3 rounded-xl bg-white/[0.02] border border-white/5">
                    <p class="text-[9px] text-zinc-600 uppercase tracking-wide">Contatado em</p>
                    <p class="text-[12px] text-zinc-300 font-medium mt-1">${lead.contacted_at ? formatDate(lead.contacted_at) : 'Ainda não'}</p>
                </div>
            </div>

            <div class="flex items-center justify-center gap-2 mt-4">
                ${whatsappUrl ? `<a href="${whatsappUrl}" target="_blank" class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-green-500/[0.05] border border-green-500/15 hover:bg-green-500/10 transition-colors flex-1">
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                    <span class="text-[10px] font-semibold text-green-400">WhatsApp</span>
                </a>` : ''}
                ${emails[0] ? `<a href="mailto:${emails[0]}" class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-blue-500/[0.05] border border-blue-500/15 hover:bg-blue-500/10 transition-colors flex-1">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span class="text-[10px] font-semibold text-blue-400">Email</span>
                </a>` : ''}
                ${lead.website ? `<a href="${escapeHtml(lead.website)}" target="_blank" class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-violet-500/[0.05] border border-violet-500/15 hover:bg-violet-500/10 transition-colors flex-1">
                    <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span class="text-[10px] font-semibold text-violet-400">Site</span>
                </a>` : ''}
                <button onclick="closeLeadDetail(); openCloseModal(${lead.id}, this.dataset.name)" data-name="${escapeHtml(lead.name).replace(/'/g, '&#39;')}" class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-emerald-500/[0.05] border border-emerald-500/15 hover:bg-emerald-500/10 transition-colors flex-1">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-[10px] font-semibold text-emerald-400">Fechar</span>
                </button>
            </div>

            <!-- Tags -->
            <div class="mt-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] text-zinc-500 font-semibold uppercase tracking-wider">Tags</p>
                    <button onclick="document.getElementById('tagInput-${lead.id}').classList.toggle('hidden')" class="text-[10px] text-blue-400 hover:text-blue-300">+ Adicionar</button>
                </div>
                <div class="flex flex-wrap gap-1.5" id="leadTags-${lead.id}">
                    ${(lead.tags || '').split(',').filter(t => t.trim()).map(tag =>
                        `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-blue-500/10 text-blue-400 border border-blue-500/15">
                            ${escapeHtml(tag.trim())}
                            <button onclick="removeTag(${lead.id}, '${escapeHtml(tag.trim())}')" class="hover:text-red-400 transition-colors">&times;</button>
                        </span>`
                    ).join('') || '<span class="text-[10px] text-zinc-600">Sem tags</span>'}
                </div>
                <div id="tagInput-${lead.id}" class="hidden mt-2 flex gap-2">
                    <input type="text" id="newTag-${lead.id}" placeholder="Nova tag..." class="input-glass !py-1.5 !text-[11px] flex-1" onkeydown="if(event.key==='Enter')addTag(${lead.id})">
                    <button onclick="addTag(${lead.id})" class="btn-accent !py-1.5 !px-3 text-[10px]">Add</button>
                </div>
            </div>

            <!-- Activity Feed -->
            <div class="mt-4">
                <p class="text-[10px] text-zinc-500 font-semibold uppercase tracking-wider mb-2">Atividades</p>
                <div id="leadActivities-${lead.id}" class="space-y-1.5 max-h-[120px] overflow-y-auto">
                    <p class="text-[10px] text-zinc-600">Carregando...</p>
                </div>
            </div>

            <button onclick="deleteLeadFromModal(${lead.id})" class="w-full mt-4 p-2.5 rounded-xl text-[11px] text-red-400/60 hover:text-red-400 hover:bg-red-500/5 border border-red-500/10 hover:border-red-500/20 transition-all flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Excluir este lead
            </button>
        `;

        loadLeadActivities(lead.id);
    } catch (e) {
        body.innerHTML = '<p class="text-center text-red-400 py-6">Erro ao carregar lead</p>';
    }
}

function closeLeadDetail() {
    document.getElementById('leadDetailModal').classList.add('hidden');
}

async function changeLeadStatus(id, status) {
    try {
        await api(`/api/leads/${id}`, { method: 'PATCH', body: JSON.stringify({ status }) });
        showToast('Status atualizado', 'success');
        loadLeads(currentPage);
    } catch (e) {
        showToast(e.message, 'error');
    }
}

async function deleteLeadFromModal(id) {
    if (!await confirmModal('Excluir este lead permanentemente?', 'Excluir', true)) return;
    try {
        await api(`/api/leads/${id}`, { method: 'DELETE' });
        showToast('Lead excluído', 'success');
        closeLeadDetail();
        loadLeads(currentPage);
    } catch (e) {
        showToast(e.message, 'error');
    }
}

async function bulkChangeStatus(status) {
    if (!status || selectedIds.size === 0) return;
    try {
        const ids = Array.from(selectedIds);
        await Promise.all(ids.map(id =>
            api(`/api/leads/${id}`, { method: 'PATCH', body: JSON.stringify({ status }) })
        ));
        showToast(`${ids.length} leads atualizados para "${status}"`, 'success');
        document.getElementById('bulkStatusSelect').value = '';
        selectedIds.clear();
        updateBulkBar();
        loadLeads(currentPage);
    } catch (e) {
        showToast(e.message, 'error');
    }
}

async function bulkExportSelected() {
    if (selectedIds.size === 0) return;
    try {
        const response = await fetch('/api/export', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCSRFToken() },
            body: JSON.stringify({ format: 'xlsx', ids: Array.from(selectedIds) }),
        });

        if (!response.ok) {
            const err = await response.json();
            throw new Error(err.error || 'Erro ao exportar');
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `leads-selecionados-${new Date().toISOString().split('T')[0]}.xlsx`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
        showToast(`${selectedIds.size} leads exportados!`, 'success');
    } catch (e) {
        showToast(e.message, 'error');
    }
}

function openExportModal() {
    document.getElementById('exportModal').classList.remove('hidden');
}

function closeExportModal() {
    document.getElementById('exportModal').classList.add('hidden');
}

async function doInlineExport() {
    const tier = document.getElementById('expTier').value;
    const qty = document.getElementById('expQty').value;
    const format = document.querySelector('input[name="expFormat"]:checked').value;

    try {
        const response = await fetch('/api/export', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCSRFToken() },
            body: JSON.stringify({ format, tier, quantity: qty ? parseInt(qty) : null }),
        });

        if (!response.ok) {
            const err = await response.json();
            throw new Error(err.error || 'Erro ao exportar');
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `leads-${new Date().toISOString().split('T')[0]}.${format}`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);

        closeExportModal();
        showToast('Exportação concluída!', 'success');
    } catch (e) {
        showToast(e.message, 'error');
    }
}

async function addTag(leadId) {
    const input = document.getElementById(`newTag-${leadId}`);
    const tag = input.value.trim();
    if (!tag) return;
    try {
        const lead = await api(`/api/leads/${leadId}`);
        const existing = (lead.tags || '').split(',').filter(t => t.trim());
        if (!existing.includes(tag)) existing.push(tag);
        await api(`/api/leads/${leadId}/tags`, { method: 'PATCH', body: JSON.stringify({ tags: existing.join(',') }) });
        input.value = '';
        showToast('Tag adicionada!', 'success');
        openLeadDetail(leadId);
    } catch (e) {
        showToast(e.message, 'error');
    }
}

async function removeTag(leadId, tagToRemove) {
    try {
        const lead = await api(`/api/leads/${leadId}`);
        const existing = (lead.tags || '').split(',').filter(t => t.trim() && t.trim() !== tagToRemove);
        await api(`/api/leads/${leadId}/tags`, { method: 'PATCH', body: JSON.stringify({ tags: existing.join(',') }) });
        showToast('Tag removida', 'success');
        openLeadDetail(leadId);
    } catch (e) {
        showToast(e.message, 'error');
    }
}

async function loadLeadActivities(leadId) {
    const container = document.getElementById(`leadActivities-${leadId}`);
    try {
        const data = await api(`/api/leads/${leadId}/activities`);
        if (!data.activities || data.activities.length === 0) {
            container.innerHTML = '<p class="text-[10px] text-zinc-600">Nenhuma atividade registrada</p>';
            return;
        }
        container.innerHTML = data.activities.slice(0, 10).map(a => `
            <div class="flex items-center gap-2 p-1.5 rounded-lg bg-white/[0.01]">
                <div class="w-1.5 h-1.5 rounded-full ${a.action === 'status_change' ? 'bg-cyan-400' : a.action === 'contacted' ? 'bg-green-400' : 'bg-zinc-500'} shrink-0"></div>
                <span class="text-[10px] text-zinc-400 flex-1">${escapeHtml(a.action.replace(/_/g, ' '))}${a.details ? ': ' + escapeHtml(a.details) : ''}</span>
                <span class="text-[9px] text-zinc-600 shrink-0">${formatDateTime(a.created_at)}</span>
            </div>
        `).join('');
    } catch (e) {
        container.innerHTML = '<p class="text-[10px] text-zinc-600">—</p>';
    }
}

// Bulk delete by score
async function bulkDeleteByScore() {
    const scoreStr = prompt("Excluir leads com pontuação menor ou igual a:", "30");
    if (scoreStr === null) return;
    const max_score = parseInt(scoreStr);
    if (isNaN(max_score)) {
        showToast('Pontuação inválida', 'error');
        return;
    }
    try {
        const preview = await api('/api/leads/bulk-delete-by-score', {
            method: 'POST',
            body: JSON.stringify({ max_score: max_score, dry_run: true }),
        });
        if (preview.count === 0) {
            showToast(`Nenhum lead com pontuação ≤ ${max_score}`, 'info');
            return;
        }
        if (!await confirmModal(`Excluir ${preview.count} leads com pontuação ≤ ${max_score}?`, 'Excluir', true)) return;
        const result = await api('/api/leads/bulk-delete-by-score', {
            method: 'POST',
            body: JSON.stringify({ max_score: max_score }),
        });
        showToast(`${result.deleted} leads excluídos`, 'success');
        loadLeads(1);
    } catch (e) {
        showToast(e.message || 'Erro', 'error');
    }
}


// SSE real-time updates
if (typeof events !== 'undefined') {
    events.on('lead_new', () => {
        if (selectedIds.size === 0 && currentPage === 1) loadLeads(1);
    });
    events.on('stats_updated', () => {
        if (selectedIds.size === 0) loadLeads(currentPage);
    });
}

loadLeads(1);
// Fallback polling (longer since SSE handles real-time)
setInterval(() => { if (selectedIds.size === 0) loadLeads(currentPage); }, 60000);
