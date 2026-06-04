let currentPage = 1;
let selectedIds = new Set();
let debounceTimer = null;

function getFilters() {
    return {
        search: document.getElementById('searchInput').value.trim(),
        tier: document.getElementById('filterTier').value,
        status: document.getElementById('filterStatus').value,
        source: document.getElementById('filterSource').value,
        sort: document.getElementById('sortBy').value,
        verified: document.getElementById('filterVerified').checked,
    };
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
    if (filters.verified) params.set('verified', '1');
    if (filters.sort === 'name') params.set('order', 'asc');

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

        return `
            <tr>
                <td><input type="checkbox" class="lead-checkbox rounded-sm cursor-pointer" data-id="${lead.id}" ${checked} onchange="toggleSelect(${lead.id})"></td>
                <td class="font-medium text-white cursor-pointer hover:text-blue-400 transition-colors" onclick="openLeadDetail(${lead.id})">${escapeHtml(lead.name)}</td>
                <td>${escapeHtml(lead.email) || '<span class="text-zinc-700">—</span>'}</td>
                <td class="text-zinc-300">${escapeHtml(lead.phone) || '<span class="text-zinc-700">—</span>'}</td>
                <td>${siteLink}</td>
                <td>${scoreBadge(lead.score)}</td>
                <td>${statusBadge(lead.status)}</td>
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
                        <button onclick="openCloseModal(${lead.id}, '${escapeHtml(lead.name)}')" title="Fechar Negócio" class="p-1.5 rounded-lg hover:bg-emerald-500/10 transition-colors">
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
    if (!confirm('Excluir este lead?')) return;
    try {
        await api(`/api/leads/${id}`, { method: 'DELETE' });
        showToast('Lead excluído', 'success');
        loadLeads(currentPage);
    } catch (e) {
        showToast(e.message, 'error');
    }
}

async function bulkDelete() {
    if (!confirm(`Excluir ${selectedIds.size} leads?`)) return;
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

['filterTier', 'filterStatus', 'filterSource', 'sortBy'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => loadLeads(1));
});

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
    body.innerHTML = '<div class="flex justify-center py-6"><div class="w-6 h-6 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"></div></div>';

    try {
        const lead = await api(`/api/leads/${id}`);
        title.textContent = lead.name;

        const tierColors = { hot: 'text-amber-400 bg-amber-500/10', warm: 'text-orange-400 bg-orange-500/10', cold: 'text-zinc-400 bg-zinc-500/10' };
        const tierLabels = { hot: 'Quente', warm: 'Morno', cold: 'Frio' };
        const statusLabels = { new: 'Novo', contacted: 'Contatado', negotiating: 'Negociando', closed_won: 'Fechado', closed_lost: 'Perdido' };

        const phone = lead.whatsapp || lead.phone || '';
        const whatsappUrl = phone ? `https://wa.me/${phone.replace(/\\D/g, '')}` : '';

        body.innerHTML = `
            <div class="flex items-center gap-3 mb-4">
                <span class="px-2.5 py-1 rounded-lg text-[11px] font-semibold ${tierColors[lead.tier] || tierColors.cold}">${tierLabels[lead.tier] || lead.tier}</span>
                <span class="text-[13px] font-bold text-zinc-200">Score: ${lead.score}</span>
                ${lead.site_verified ? '<span class="text-[10px] text-emerald-400">✓ Site verificado</span>' : ''}
            </div>

            <div class="space-y-3">
                ${lead.email ? `<div class="flex items-center gap-3 p-3 rounded-xl bg-white/[0.02] border border-white/5">
                    <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <div class="min-w-0"><p class="text-[10px] text-zinc-500">Email</p><p class="text-[13px] text-zinc-200 truncate">${escapeHtml(lead.email)}</p></div>
                </div>` : ''}

                ${lead.phone ? `<div class="flex items-center gap-3 p-3 rounded-xl bg-white/[0.02] border border-white/5">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <div class="min-w-0"><p class="text-[10px] text-zinc-500">Telefone</p><p class="text-[13px] text-zinc-200">${escapeHtml(lead.phone)}</p></div>
                </div>` : ''}

                ${lead.whatsapp ? `<div class="flex items-center gap-3 p-3 rounded-xl bg-white/[0.02] border border-white/5">
                    <svg class="w-4 h-4 text-green-400 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                    <div class="min-w-0"><p class="text-[10px] text-zinc-500">WhatsApp</p><p class="text-[13px] text-zinc-200">${escapeHtml(lead.whatsapp)}</p></div>
                </div>` : ''}

                ${lead.website ? `<div class="flex items-center gap-3 p-3 rounded-xl bg-white/[0.02] border border-white/5">
                    <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    <div class="min-w-0"><p class="text-[10px] text-zinc-500">Website</p><a href="${escapeHtml(lead.website)}" target="_blank" class="text-[13px] text-blue-400 hover:text-blue-300 truncate block">${escapeHtml(lead.website.replace(/https?:\/\/(www\.)?/, ''))}</a></div>
                </div>` : ''}
            </div>

            <div class="grid grid-cols-3 gap-3 mt-4">
                <div class="p-3 rounded-xl bg-white/[0.02] border border-white/5 text-center">
                    <p class="text-[10px] text-zinc-500">Fonte</p>
                    <p class="text-[12px] text-zinc-300 font-medium mt-0.5">${lead.source || '—'}</p>
                </div>
                <div class="p-3 rounded-xl bg-white/[0.02] border border-white/5 text-center">
                    <p class="text-[10px] text-zinc-500">Status</p>
                    <p class="text-[12px] text-zinc-300 font-medium mt-0.5">${statusLabels[lead.status] || lead.status}</p>
                </div>
                <div class="p-3 rounded-xl bg-white/[0.02] border border-white/5 text-center">
                    <p class="text-[10px] text-zinc-500">Categoria</p>
                    <p class="text-[12px] text-zinc-300 font-medium mt-0.5">${lead.category || '—'}</p>
                </div>
            </div>

            <div class="flex gap-2 mt-5">
                ${whatsappUrl ? `<a href="${whatsappUrl}" target="_blank" class="btn-ghost flex-1 text-center !text-green-400 !border-green-500/20 hover:!bg-green-500/10">WhatsApp</a>` : ''}
                ${lead.email ? `<a href="mailto:${lead.email}" class="btn-ghost flex-1 text-center !text-blue-400 !border-blue-500/20 hover:!bg-blue-500/10">Email</a>` : ''}
                <button onclick="closeLeadDetail(); openCloseModal(${lead.id}, '${escapeHtml(lead.name)}')" class="btn-accent flex-1">Fechar Negócio</button>
            </div>
        `;
    } catch (e) {
        body.innerHTML = '<p class="text-center text-red-400 py-6">Erro ao carregar lead</p>';
    }
}

function closeLeadDetail() {
    document.getElementById('leadDetailModal').classList.add('hidden');
}

loadLeads(1);
