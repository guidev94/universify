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
        ads: document.getElementById('filterAds') ? document.getElementById('filterAds').value : '',
        quality: document.getElementById('filterQuality') ? document.getElementById('filterQuality').value : '',
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
    if (filters.ads) params.set('has_ads', filters.ads);
    if (filters.quality) params.set('site_quality', filters.quality);
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
    if (!tbody) return;

    if (!leads || leads.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-zinc-600 py-12">Nenhum lead encontrado</td></tr>';
        return;
    }

    // Renderização em pedaços para não travar a aba principal
    tbody.innerHTML = '';
    const CHUNK_SIZE = 25;
    let index = 0;

    function renderChunk() {
        const chunk = leads.slice(index, index + CHUNK_SIZE);
        const html = chunk.map(lead => {
            const checked = selectedIds.has(lead.id) ? 'checked' : '';
            const siteLink = lead.website
                ? `<a href="${escapeHtml(lead.website)}" target="_blank" rel="noopener" class="text-blue-400 hover:text-blue-300 text-[11px] truncate max-w-[120px] inline-block">${escapeHtml(lead.website.replace(/https?:\/\/(www\.)?/, '').split('/')[0])}</a>`
                : '<span class="text-zinc-700">—</span>';
            
            const adsIcon = lead.has_ads ? `<span class="ml-1 text-amber-500" title="Investe em Anúncios">📢</span>` : '';
            const rowClass = lead.tier === 'hot' ? 'border-l-2 border-l-amber-400/60 bg-amber-500/[0.02]' : lead.tier === 'warm' ? 'border-l-2 border-l-orange-400/40' : '';

            return `
                <tr class="${rowClass}">
                    <td><input type="checkbox" class="lead-checkbox rounded-sm cursor-pointer" data-id="${lead.id}" ${checked} onchange="toggleSelect(${lead.id})"></td>
                    <td class="font-medium text-white cursor-pointer hover:text-blue-400 transition-colors" onclick="openLeadDetail(${lead.id})">${escapeHtml(lead.name)}${adsIcon}</td>
                    <td class="group">${lead.email ? `<span class="inline-flex items-center gap-1"><a href="mailto:${escapeHtml(lead.email.split(';')[0])}" class="text-zinc-300 hover:text-blue-400 truncate max-w-[140px] inline-block text-[12px]">${escapeHtml(lead.email.split(';')[0])}</a></span>` : '<span class="text-zinc-700">—</span>'}</td>
                    <td class="group">${lead.phone ? `<span class="inline-flex items-center gap-1"><a href="tel:${lead.phone.split(';')[0]}" class="text-zinc-300 hover:text-emerald-400 text-[12px]">${escapeHtml(lead.phone.split(';')[0])}</a></span>` : '<span class="text-zinc-700">—</span>'}</td>
                    <td>${siteLink}</td>
                    <td>${scoreBadge(lead.score)}</td>
                    <td>${statusBadge(lead.status)}</td>
                    <td class="text-[11px] text-zinc-500 whitespace-nowrap">${formatDateTime(lead.created_at)}</td>
                    <td>
                        <div class="flex items-center gap-0.5">
                            <button onclick="sendWhatsApp(${lead.id})" class="p-1.5 rounded-lg hover:bg-green-500/10 transition-colors"><i data-lucide="message-square" class="w-3.5 h-3.5 text-green-400"></i></button>
                            <button onclick="deleteLead(${lead.id})" class="p-1.5 rounded-lg hover:bg-red-500/10 transition-colors"><i data-lucide="trash-2" class="w-3.5 h-3.5 text-red-400"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        tbody.insertAdjacentHTML('beforeend', html);
        lucide.createIcons();
        
        index += CHUNK_SIZE;
        if (index < leads.length) {
            requestAnimationFrame(renderChunk);
        }
    }

    renderChunk();
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

['filterTier', 'filterStatus', 'filterSource', 'filterCategory', 'sortBy', 'filterDays', 'filterAds', 'filterQuality'].forEach(id => {
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
    // Busca dados imediatamente sem esperar animação de modal
    const fetchLead = api(`/api/leads/${id}`);
    const modal = document.getElementById('leadDetailModal');
    const body = document.getElementById('leadDetailBody');
    
    if (!modal || !body) return;

    // Mostra modal vazio com skeleton/spinner se demorar
    modal.classList.remove('hidden');
    body.innerHTML = `<div class="p-12 text-center flex flex-col items-center gap-4">
        <div class="w-10 h-10 border-2 border-zinc-800 border-t-blue-500 rounded-full animate-spin"></div>
        <p class="text-zinc-500 text-sm">Buscando inteligência do lead...</p>
    </div>`;

    try {
        const lead = await fetchLead;
        
        body.innerHTML = `
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-xl font-bold text-zinc-100">${escapeHtml(lead.name)}</h2>
                    <div class="flex gap-2 items-center mt-1">
                        <span class="text-xs text-zinc-500 font-medium">${escapeHtml(lead.category) || 'Lead Qualificado'}</span>
                        <span class="w-1 h-1 rounded-full bg-zinc-700"></span>
                        <span class="text-[10px] text-zinc-500 uppercase tracking-tighter">${lead.source}</span>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <span class="px-3 py-1 rounded-full bg-amber-500/20 text-amber-500 text-xs font-bold border border-amber-500/20">Score ${lead.score}</span>
                    <span class="text-[10px] text-zinc-600 font-mono">${formatDateTime(lead.created_at)}</span>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
                <div class="p-4 rounded-xl bg-white/[0.02] border border-white/5 hover:bg-white/[0.04] transition-colors">
                    <p class="text-[9px] text-zinc-500 uppercase font-black mb-1.5 tracking-widest">Canais de Contato</p>
                    <div class="space-y-2">
                        ${lead.whatsapp ? `<p class="text-[13px] text-emerald-400 font-bold flex items-center gap-2"><i data-lucide="message-circle" class="w-3.5 h-3.5"></i> ${formatPhone(lead.whatsapp)}</p>` : ''}
                        ${lead.phone && lead.phone !== lead.whatsapp ? `<p class="text-[13px] text-zinc-300 flex items-center gap-2"><i data-lucide="phone" class="w-3.5 h-3.5"></i> ${lead.phone}</p>` : ''}
                        ${lead.email ? `<p class="text-[12px] text-blue-400 font-medium flex items-center gap-2 underline underline-offset-4 decoration-blue-500/30 truncate"><i data-lucide="mail" class="w-3.5 h-3.5"></i> ${lead.email.split(';')[0]}</p>` : ''}
                        ${!lead.whatsapp && !lead.email && !lead.phone ? '<p class="text-xs text-zinc-600 italic">Nenhum contato direto encontrado</p>' : ''}
                    </div>
                </div>
                <div class="p-4 rounded-xl bg-white/[0.02] border border-white/5 hover:bg-white/[0.04] transition-colors">
                    <p class="text-[9px] text-zinc-500 uppercase font-black mb-1.5 tracking-widest">Presença Digital</p>
                    ${lead.website ? `
                        <a href="${lead.website}" target="_blank" class="text-[13px] text-blue-400 font-bold hover:underline flex items-center gap-2 mb-2">
                            <i data-lucide="globe" class="w-3.5 h-3.5"></i> ${lead.website.replace(/https?:\/\//, '')}
                        </a>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            ${lead.site_quality === 'poor' ? '<span class="px-1.5 py-0.5 rounded bg-red-500/10 text-red-400 text-[9px] font-black border border-red-500/10">SITE OBSOLETO</span>' : ''}
                            ${lead.has_ads ? '<span class="px-1.5 py-0.5 rounded bg-purple-500/10 text-purple-400 text-[9px] font-black border border-purple-500/10">MARKETING ATIVO</span>' : ''}
                            ${lead.cms && lead.cms !== 'Custom/Other' ? `<span class="px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-400 text-[9px] font-bold border border-white/5">${lead.cms}</span>` : ''}
                        </div>
                    ` : '<p class="text-xs text-zinc-600 italic">Não possui website oficial</p>'}
                </div>
            </div>

            <div class="p-5 rounded-2xl bg-amber-500/[0.04] border border-amber-500/10 mb-6 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-20 h-20 bg-amber-500/5 rounded-full blur-2xl group-hover:bg-amber-500/10 transition-all"></div>
                <p class="text-[9px] text-amber-500 uppercase font-black mb-2 tracking-widest flex items-center gap-2">
                    <i data-lucide="sparkles" class="w-3 h-3"></i> Gancho Comercial / Auditoria Técnica
                </p>
                <p class="text-[14px] text-zinc-100 font-medium leading-relaxed italic">"${lead.pitch_hook || 'Lead com alto potencial para novos serviços de desenvolvimento.'}"</p>
            </div>

            ${lead.notes ? `
            <div class="mb-6">
                <p class="text-[9px] text-zinc-500 uppercase font-black mb-2 tracking-widest">Notas de Observação</p>
                <div class="p-4 rounded-xl bg-zinc-900/50 border border-white/5 text-[13px] text-zinc-400 whitespace-pre-wrap leading-relaxed">${escapeHtml(lead.notes)}</div>
            </div>` : ''}

            <div class="flex flex-wrap md:flex-nowrap gap-3 pt-6 border-t border-white/5">
                <button onclick="closeLeadDetail()" class="h-12 flex-1 px-4 rounded-xl bg-zinc-800 text-zinc-400 font-bold text-xs uppercase tracking-wider hover:bg-zinc-700 transition-all active:scale-95">Fechar</button>
                
                <button onclick="toggleFavoriteFromModal(${lead.id})" id="favBtnModal" class="h-12 w-12 rounded-xl border border-white/5 transition-all flex items-center justify-center active:scale-95 ${lead.is_favorite ? 'bg-yellow-400/20 text-yellow-400 border-yellow-400/30' : 'bg-white/[0.02] text-zinc-500'}" title="Favoritar">
                    <i data-lucide="star" class="w-5 h-5 ${lead.is_favorite ? 'fill-yellow-400' : ''}"></i>
                </button>

                ${lead.whatsapp ? `
                    <button onclick="openWppMessageModal(${lead.id})" class="h-12 flex-[2] px-6 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-900/40 transition-all flex items-center justify-center gap-2 active:scale-95">
                        <i data-lucide="message-circle" class="w-4 h-4"></i> Enviar Proposta WhatsApp
                    </button>
                ` : ''}
                
                <button onclick="deleteLeadFromModal(${lead.id})" class="h-12 w-12 rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white border border-red-500/20 transition-all flex items-center justify-center active:scale-95" title="Excluir Lead">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </button>
            </div>
        `;
        
        lucide.createIcons();
    } catch (e) {
        body.innerHTML = `<div class="p-12 text-center">
            <p class="text-red-400 text-sm">Erro ao carregar os dados deste lead. Tente novamente.</p>
            <button onclick="closeLeadDetail()" class="mt-4 px-6 py-2 bg-zinc-800 rounded-lg text-xs font-bold uppercase tracking-wider">Voltar</button>
        </div>`;
    }
}

function closeLeadDetail() {
    const modal = document.getElementById('leadDetailModal');
    if (modal) modal.classList.add('hidden');
}

async function toggleFavoriteFromModal(id) {
    await toggleLeadFavorite(id, 'favBtnModal');
}

// Aliases para compatibilidade entre abas
window.viewLead = openLeadDetail;
window.showLeadDetail = openLeadDetail;

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
    
    const colChecks = document.querySelectorAll('input[name="exp_col"]:checked');
    const columns = Array.from(colChecks).map(c => c.value);
    
    if (columns.length === 0) {
        showToast('Selecione pelo menos um campo', 'error');
        return;
    }

    try {
        const response = await fetch('/api/export', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCSRFToken() },
            body: JSON.stringify({ 
                format, 
                tier, 
                quantity: qty ? parseInt(qty) : null,
                columns: columns
            }),
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

async function generateAICopy(id) {
    const area = document.getElementById(`aiCopyArea-${id}`);
    const text = document.getElementById(`aiCopyText-${id}`);
    
    area.classList.remove('hidden');
    text.innerHTML = '<div class="flex items-center gap-2 py-2"><div class="w-3 h-3 border-2 border-purple-400 border-t-transparent rounded-full animate-spin"></div><span class="text-[11px] text-purple-400/70">Analisando inteligência do lead...</span></div>';
    
    try {
        const data = await api(`/api/leads/${id}/ai-copy`);
        text.textContent = data.message;
        text.dataset.fullMessage = data.message;
    } catch (e) {
        showToast('Erro ao gerar abordagem', 'error');
        area.classList.add('hidden');
    }
}

function copyAICopy(id) {
    const text = document.getElementById(`aiCopyText-${id}`).textContent;
    navigator.clipboard.writeText(text);
    showToast('Abordagem copiada!', 'success');
}

async function sendAIWhatsApp(id) {
    const text = document.getElementById(`aiCopyText-${id}`).textContent;
    const lead = await api(`/api/leads/${id}`);
    const phone = (lead.whatsapp || lead.phone || '').replace(/\D/g, '');
    
    if (!phone) {
        showToast('Lead sem telefone', 'error');
        return;
    }
    
    const url = `https://wa.me/${phone}?text=${encodeURIComponent(text)}`;
    window.open(url, '_blank');
    
    // Mark as contacted
    api(`/api/leads/${id}`, { method: 'PATCH', body: JSON.stringify({ status: 'contacted' }) });
    loadLeads(currentPage);
}

async function bulkAIContact() {
    if (selectedIds.size === 0) return;
    const ids = Array.from(selectedIds);
    
    if (!await confirmModal(`Gerar abordagem IA e abrir WhatsApp para ${ids.length} leads?`, 'Iniciar Lote', false)) return;
    
    showToast('Processando abordagens...', 'info');
    
    for (const id of ids) {
        try {
            const data = await api(`/api/leads/${id}/ai-copy`);
            const lead = await api(`/api/leads/${id}`);
            const phone = (lead.whatsapp || lead.phone || '').replace(/\D/g, '');
            
            if (phone) {
                const url = `https://wa.me/${phone}?text=${encodeURIComponent(data.message)}`;
                window.open(url, '_blank');
                // Mark as contacted
                await api(`/api/leads/${id}`, { method: 'PATCH', body: JSON.stringify({ status: 'contacted' }) });
            }
            // Small delay between opens to not crash browser
            await new Promise(r => setTimeout(r, 800));
        } catch (e) {
            console.error(`Error on bulk AI for lead ${id}`, e);
        }
    }
    
    selectedIds.clear();
    updateBulkBar();
    loadLeads(currentPage);
    showToast('Lote processado!', 'success');
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
    document.getElementById('cleanupModal').classList.remove('hidden');
    document.getElementById('cleanupTotal').textContent = '...';
    document.getElementById('cleanupTierDetails').innerHTML = '';
    
    // Load distribution chart or info
    try {
        const stats = await api('/api/leads/stats-by-score');
        const summary = document.getElementById('cleanupTierDetails');
        summary.innerHTML = `<p class="text-[10px] text-zinc-500 w-full mb-1">Distribuição total: ${stats.total} leads</p>`;
    } catch(e) {}
    
    updateCleanupPreview();
}

function hideCleanupModal() {
    document.getElementById('cleanupModal').classList.add('hidden');
}

let cleanupTimeout = null;
async function updateCleanupPreview() {
    const scoreEl = document.getElementById('cleanupScoreRange');
    const valueEl = document.getElementById('cleanupScoreValue');
    const totalEl = document.getElementById('cleanupTotal');
    const detailsEl = document.getElementById('cleanupTierDetails');
    const confirmBtn = document.getElementById('confirmCleanupBtn');

    if (!scoreEl) return;
    
    const score = scoreEl.value;
    valueEl.textContent = score;
    
    totalEl.textContent = '...';
    
    // Debounce preview request
    clearTimeout(cleanupTimeout);
    cleanupTimeout = setTimeout(async () => {
        try {
            const data = await api('/api/leads/bulk-delete-by-score', {
                method: 'POST',
                body: JSON.stringify({ max_score: parseInt(score), dry_run: true }),
            });
            
            totalEl.textContent = data.count;
            
            if (data.count === 0) {
                detailsEl.innerHTML = '<span class="text-[10px] text-zinc-600 italic">Nenhum lead nesta faixa</span>';
                confirmBtn.disabled = true;
                confirmBtn.style.opacity = 0.5;
            } else {
                confirmBtn.disabled = false;
                confirmBtn.style.opacity = 1;
                
                let html = '';
                const colors = { hot: 'text-amber-400', warm: 'text-orange-400', cold: 'text-zinc-500' };
                const labels = { hot: 'Quentes', warm: 'Mornos', cold: 'Frios' };
                
                if (data.details) {
                    for (const [tier, count] of Object.entries(data.details)) {
                        if (count > 0) {
                            html += `<span class="text-[10px] px-2 py-0.5 rounded bg-white/5 border border-white/5 ${colors[tier] || 'text-zinc-400'}">${count} ${labels[tier] || tier}</span>`;
                        }
                    }
                }
                detailsEl.innerHTML = html || '<span class="text-[10px] text-zinc-500">Pronto para excluir</span>';
            }
        } catch (e) {
            console.error('Preview error:', e);
            totalEl.textContent = '0';
            detailsEl.innerHTML = '<span class="text-[10px] text-red-400">Erro ao carregar prévia</span>';
        }
    }, 300);
}

async function executeCleanup() {
    const score = document.getElementById('cleanupScoreRange').value;
    const btn = document.getElementById('confirmCleanupBtn');
    
    const count = document.getElementById('cleanupTotal').textContent;
    if (count === "0") return;

    btn.disabled = true;
    btn.textContent = 'Excluindo...';

    try {
        const result = await api('/api/leads/bulk-delete-by-score', {
            method: 'POST',
            body: JSON.stringify({ max_score: parseInt(score) }),
        });
        showToast(`${result.deleted} leads excluídos com sucesso`, 'success');
        hideCleanupModal();
        loadLeads(1);
    } catch (e) {
        showToast(e.message || 'Erro ao excluir', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Confirmar Exclusão';
    }
}


// Gerenciador de Envio em Lote (Bulk WhatsApp)
let bulkQueue = [];
let isProcessingBulk = false;

async function bulkAIContact() {
    if (selectedIds.size === 0) {
        showToast('Selecione pelo menos um lead', 'warning');
        return;
    }

    const confirm = await confirmModal(
        `Deseja preparar o envio em lote para ${selectedIds.size} leads selecionados? 
         As mensagens serão abertas uma por uma para seu controle.`,
        'Iniciar Lote'
    );
    
    if (!confirm) return;

    bulkQueue = Array.from(selectedIds);
    selectedIds.clear();
    updateBulkBar();
    processNextInBulk();
}

async function processNextInBulk() {
    if (bulkQueue.length === 0) {
        isProcessingBulk = false;
        showToast('Lote finalizado!', 'success');
        loadLeads(currentPage);
        return;
    }

    isProcessingBulk = true;
    const leadId = bulkQueue.shift();
    
    // Abre o modal de proposta personalizada para este lead do lote
    await openWppMessageModal(leadId);
    
    // Adiciona um listener temporário no botão de envio para saber quando passar para o próximo
    const originalSend = window.sendFinalWpp;
    window.sendFinalWpp = async function(id, phone) {
        const msg = encodeURIComponent(document.getElementById('wppMessageArea').value);
        const cleanPhone = phone.replace(/\D/g, '');
        window.open(`https://wa.me/${cleanPhone}?text=${msg}`, '_blank');
        
        await api(`/api/leads/${id}`, { method: 'PATCH', body: JSON.stringify({ status: 'contacted' }) });
        document.getElementById('wppProposalModal').remove();
        
        showToast(`(${bulkQueue.length} restantes) Proposta enviada. Próximo...`, 'info');
        
        // Restaura e chama o próximo
        window.sendFinalWpp = originalSend;
        setTimeout(processNextInBulk, 500); 
    };
    
    // Se o usuário cancelar no meio do lote
    const modal = document.getElementById('wppProposalModal');
    const cancelBtn = modal.querySelector('button[onclick*="remove"]');
    cancelBtn.onclick = () => {
        modal.remove();
        bulkQueue = []; // Limpa fila
        isProcessingBulk = false;
        window.sendFinalWpp = originalSend;
        showToast('Envio em lote cancelado', 'warning');
    };
}
    try {
        const lead = await api(`/api/leads/${id}`);
        const modal = document.createElement('div');
        modal.id = 'wppProposalModal';
        modal.className = 'fixed inset-0 z-[210] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm';
        
        // Gera propostas baseadas nos ganchos detectados
        let defaultMsg = `Olá, tudo bem?\n\nVi o site da ${lead.name} e notei algumas oportunidades de melhoria técnica que podem aumentar suas vendas. Gostaria de apresentar uma proposta de atualização. Podemos conversar?`;
        
        if (lead.site_quality === 'poor' || lead.pitch_hook.includes('obsoleto')) {
            defaultMsg = `Olá! Sou desenvolvedor especialista em modernização digital.\n\nAnalisei o site da ${lead.name} e notei que ele utiliza tecnologias antigas que estão prejudicando seu rankeamento e conversão. Criei um esboço de como ficaria uma versão moderna e 100% responsiva para vocês. \n\nPodemos agendar um café virtual de 5 minutos?`;
        } else if (lead.has_ads) {
            defaultMsg = `Olá! Notei que a ${lead.name} investe em tráfego pago, mas o destino final (o site) não está otimizado para converter esses cliques em vendas reais.\n\nTrabalho com LPs de alta conversão e gostaria de mostrar como podemos reduzir seu custo por lead apenas ajustando a interface. \n\nTem interesse em ver os números?`;
        } else if (!lead.website) {
             defaultMsg = `Olá! Vi o perfil da ${lead.name} e notei que vocês ainda não possuem um site oficial. No mercado atual, isso passa uma imagem amadora e faz vocês perderem clientes para a concorrência.\n\nDesenvolvo sites profissionais de alta performance. Gostaria de uma proposta sem compromisso?`;
        }

        modal.innerHTML = `
            <div class="relative glass-card p-6 w-full max-w-lg border border-emerald-500/20 shadow-[0_0_50px_rgba(16,185,129,0.1)]">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center">
                        <i data-lucide="message-square" class="text-emerald-500 w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-zinc-100">Proposta via WhatsApp</h3>
                        <p class="text-xs text-zinc-500">Personalize sua abordagem para ${lead.name}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="p-3 rounded-xl bg-emerald-500/[0.03] border border-emerald-500/10 flex gap-3 items-start">
                        <i data-lucide="info" class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5"></i>
                        <p class="text-[11px] text-zinc-400 leading-tight">Dica: Cite os problemas técnicos detectados (${lead.site_quality === 'poor' ? 'Site Lento' : 'Sem SSL'}) para gerar mais autoridade.</p>
                    </div>
                    
                    <textarea id="wppMessageArea" rows="8" class="w-full bg-zinc-900/50 border border-white/5 rounded-xl p-4 text-sm text-zinc-200 outline-none focus:border-emerald-500/30 transition-all leading-relaxed">${defaultMsg}</textarea>
                </div>

                <div class="flex gap-3 mt-6">
                    <button onclick="document.getElementById('wppProposalModal').remove()" class="flex-1 py-3 rounded-xl bg-zinc-800 text-zinc-400 font-bold text-xs uppercase tracking-wider hover:bg-zinc-700 transition-all">Cancelar</button>
                    <button onclick="sendFinalWpp(${lead.id}, '${lead.whatsapp}')" class="flex-2 py-3 px-6 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2">
                        Enviar Agora <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        lucide.createIcons();
    } catch (e) {
        showToast('Erro ao preparar proposta', 'error');
    }
}

function sendFinalWpp(id, phone) {
    const msg = encodeURIComponent(document.getElementById('wppMessageArea').value);
    const cleanPhone = phone.replace(/\D/g, '');
    window.open(`https://wa.me/${cleanPhone}?text=${msg}`, '_blank');
    
    // Atualiza status e limpa modal
    api(`/api/leads/${id}`, { method: 'PATCH', body: JSON.stringify({ status: 'contacted' }) });
    document.getElementById('wppProposalModal').remove();
    showToast('Proposta enviada! Status atualizado para Contatado.', 'success');
    
    // Recarrega listagem se necessário
    if (typeof loadHotLeads === 'function') loadHotLeads();
    else loadLeads(currentPage);
}
let lastLeadLoad = 0;
if (typeof events !== 'undefined') {
    events.on('lead_new', (data) => {
        if (data && data.score >= 80) {
            showToast(`🔥 Novo Lead HOT: ${data.name}!`, 'success');
            try {
                const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
                audio.volume = 0.3;
                audio.play();
            } catch(e) {}
        }
        
        const now = Date.now();
        if (selectedIds.size === 0 && currentPage === 1 && (now - lastLeadLoad > 3000)) {
            lastLeadLoad = now;
            loadLeads(1);
        }
    });
    events.on('stats_updated', () => {
        const now = Date.now();
        if (selectedIds.size === 0 && (now - lastLeadLoad > 3000)) {
            lastLeadLoad = now;
            loadLeads(currentPage);
        }
    });
}

loadLeads(1);
// Fallback polling (longer since SSE handles real-time)
setInterval(() => { if (selectedIds.size === 0) loadLeads(currentPage); }, 60000);
