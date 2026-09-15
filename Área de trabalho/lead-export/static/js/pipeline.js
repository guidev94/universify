let selectedLeads = new Set();
const stages = ['new', 'contacted', 'negotiating', 'closed_won'];

async function loadPipeline() {
    const search = document.getElementById('pipelineSearch')?.value || '';
    const qualified = document.getElementById('filterQualified')?.value || '';
    const quality = document.getElementById('filterQuality')?.value || '';

    // Show loading in all columns
    stages.forEach(s => {
        const col = document.getElementById(`col-${s}`);
        if (col) col.innerHTML = '<div class="flex justify-center py-10 opacity-20"><div class="w-6 h-6 border-2 border-zinc-500 border-t-transparent rounded-full animate-spin"></div></div>';
    });

    try {
        // Fetch all leads for these stages (simplified for now, ideally one call or parallel)
        const promises = stages.map(s => {
            let url = `/api/leads?status=${s}&per_page=50&sort=score&order=desc`;
            if (search) url += `&search=${search}`;
            if (qualified) url += `&qualified=${qualified}`;
            if (quality) url += `&site_quality=${quality}`;
            return api(url).then(data => ({ stage: s, data }));
        });

        const results = await Promise.all(promises);
        
        results.forEach(res => {
            renderColumn(res.stage, res.data.leads, res.data.total);
        });

        // Update overall stats if needed
        const totalOpportunities = results.find(r => r.stage === 'new')?.data.total || 0;
        document.getElementById('statRecentCount').textContent = totalOpportunities;
        
        lucide.createIcons();
    } catch (e) {
        showToast('Erro ao sincronizar pipeline', 'error');
    }
}

function renderColumn(stage, leads, total) {
    const container = document.getElementById(`col-${stage}`);
    const countEl = document.getElementById(`count-${stage}`);
    if (!container || !countEl) return;

    countEl.textContent = total;

    if (!leads.length) {
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center py-10 opacity-20">
                <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Vazio</p>
            </div>
        `;
        return;
    }

    container.innerHTML = leads.map(l => renderKanbanCard(l)).join('');
}

function renderKanbanCard(lead) {
    const isOldSite = lead.site_quality === 'poor' && lead.site_verified;
    const hasNoSite = !lead.website && lead.site_verified === 0;
    
    let borderClass = 'border-white/5';
    if (lead.is_qualified) borderClass = 'border-emerald-500/30 bg-emerald-500/[0.02]';
    else if (isOldSite) borderClass = 'border-amber-500/30 bg-amber-500/[0.01]';
    else if (hasNoSite) borderClass = 'border-red-500/30';

    return `
        <div class="kanban-card group glass-card !p-4 mb-3 cursor-grab active:cursor-grabbing border ${borderClass} transition-all hover:border-white/20" 
             draggable="true" ondragstart="drag(event, ${lead.id})" id="card-${lead.id}" onclick="openLeadDetail(${lead.id})">
            
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0 flex-1">
                    <h4 class="text-[13px] font-bold text-zinc-100 truncate leading-tight mb-1 group-hover:text-accent transition-colors">${escapeHtml(lead.name)}</h4>
                    <p class="text-[10px] text-zinc-500 truncate">${escapeHtml(lead.category || 'Lead')}</p>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <div class="text-[11px] font-black ${lead.score >= 80 ? 'text-amber-500' : lead.score >= 50 ? 'text-blue-400' : 'text-zinc-500'}">${lead.score}</div>
                    ${lead.is_qualified ? '<i data-lucide="star" class="w-3 h-3 fill-emerald-400 text-emerald-400"></i>' : ''}
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    ${lead.has_ads ? '<div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_4px_rgba(16,185,129,0.5)]" title="Ads Ativo"></div>' : ''}
                    ${isOldSite ? '<i data-lucide="monitor-x" class="w-3.5 h-3.5 text-amber-500/50" title="Site Antigo"></i>' : ''}
                    ${hasNoSite ? '<i data-lucide="globe" class="w-3.5 h-3.5 text-red-500/50" title="Sem Site"></i>' : ''}
                </div>
                
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="event.stopPropagation(); contactLead(${lead.id}, 'whatsapp')" class="p-1.5 rounded-lg hover:bg-emerald-500/10 text-emerald-400 transition-colors">
                        <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                    </button>
                    <button onclick="event.stopPropagation(); openSalesScript(${lead.id})" class="p-1.5 rounded-lg hover:bg-white/10 text-zinc-400 transition-colors">
                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Drag and Drop Logic
function allowDrop(ev) {
    ev.preventDefault();
}

function drag(ev, id) {
    ev.dataTransfer.setData("lead_id", id);
    document.getElementById(`card-${id}`).style.opacity = "0.4";
}

async function drop(ev, stage) {
    ev.preventDefault();
    const id = ev.dataTransfer.getData("lead_id");
    const card = document.getElementById(`card-${id}`);
    if (card) card.style.opacity = "1";

    try {
        await api(`/api/leads/${id}`, {
            method: 'PATCH',
            body: JSON.stringify({ status: stage })
        });
        showToast('Status atualizado!', 'success');
        loadPipeline(); // Full reload to ensure counters and order are correct
    } catch (e) {
        showToast('Erro ao mover lead', 'error');
    }
}

async function clearColumn(stage) {
    if (!await confirmModal(`Deseja limpar todos os leads da coluna "${stage}"?`, 'Limpar Coluna', true)) return;
    try {
        await api('/api/leads/bulk-delete-column', {
            method: 'POST',
            body: JSON.stringify({ status: stage })
        });
        showToast('Coluna limpa com sucesso', 'success');
        loadPipeline();
    } catch (e) {
        showToast('Erro ao limpar coluna', 'error');
    }
}

async function contactLead(id, type) {
    try {
        const res = await api(`/api/leads/${id}/action/${type}`);
        window.open(res.url, '_blank');
        loadPipeline();
    } catch (e) { showToast('Erro ao iniciar contato', 'error'); }
}

async function openLeadDetail(id) {
    const content = document.getElementById('leadDetailContent');
    content.innerHTML = `
        <div class="flex items-center justify-center py-20 opacity-20">
            <div class="w-8 h-8 border-3 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
        </div>
    `;
    document.getElementById('leadDetailModal').classList.remove('hidden');

    try {
        const lead = await api(`/api/leads/${id}`);
        const hasNoSite = !lead.website && lead.site_verified === 0;
        const isOldSite = lead.site_quality === 'poor' && lead.site_verified;
        
        let techHtml = '';
        if (hasNoSite) {
            techHtml = `<div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 mb-6">
                <p class="text-[13px] font-bold flex items-center gap-2"><i data-lucide="alert-circle" class="w-4 h-4"></i> Empresa sem Website</p>
                <p class="text-[11px] mt-1 opacity-80">Oportunidade ideal para venda de criação de site do zero.</p>
            </div>`;
        } else if (isOldSite) {
            techHtml = `<div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 mb-6">
                <p class="text-[13px] font-bold flex items-center gap-2"><i data-lucide="monitor-x" class="w-4 h-4"></i> Website Antigo / Obsoleto</p>
                <p class="text-[11px] mt-1 opacity-80">Site com tecnologia ultrapassada (Tech Score: ${lead.tech_score}). Recomenda-se redesign completo.</p>
            </div>`;
        }

        content.innerHTML = `
            <div class="p-8">
                <div class="flex items-start gap-6 mb-8">
                    <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-blue-500/20 to-indigo-600/10 border border-white/5 flex items-center justify-center shrink-0">
                        <span class="text-[28px] font-black text-zinc-100">${lead.score}</span>
                    </div>
                    <div class="min-w-0 flex-1 pt-2">
                        <h3 class="text-[22px] font-black text-zinc-100 leading-tight mb-2">${escapeHtml(lead.name)}</h3>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-2.5 py-1 rounded-lg bg-white/5 border border-white/5 text-[10px] font-black uppercase tracking-wider text-zinc-400">${escapeHtml(lead.category || 'Lead')}</span>
                            ${lead.has_ads ? '<span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/10 text-[10px] font-black uppercase tracking-wider text-emerald-400">Ads Spender</span>' : ''}
                            <span class="px-2.5 py-1 rounded-lg bg-blue-500/10 border border-blue-500/10 text-[10px] font-black uppercase tracking-wider text-blue-400">${lead.tier}</span>
                            ${lead.is_qualified ? '<span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/10 text-[10px] font-black uppercase tracking-wider text-emerald-400">Qualificado</span>' : ''}
                        </div>
                    </div>
                </div>

                ${techHtml}

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                    <div>
                        <h4 class="text-[11px] font-black text-zinc-500 uppercase tracking-widest mb-4">Informações de Contato</h4>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-zinc-900 flex items-center justify-center text-zinc-500"><i data-lucide="mail" class="w-4 h-4"></i></div>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-zinc-600 font-bold uppercase">E-mail</p>
                                    <p class="text-[13px] text-zinc-300 truncate">${escapeHtml(lead.email || 'Não informado')}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-zinc-900 flex items-center justify-center text-zinc-500"><i data-lucide="phone" class="w-4 h-4"></i></div>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-zinc-600 font-bold uppercase">Telefone / WhatsApp</p>
                                    <p class="text-[13px] text-zinc-300">${escapeHtml(lead.whatsapp || lead.phone || 'Não informado')}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-zinc-900 flex items-center justify-center text-zinc-500"><i data-lucide="globe" class="w-4 h-4"></i></div>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-zinc-600 font-bold uppercase">Website</p>
                                    ${lead.website ? `<a href="${lead.website}" target="_blank" class="text-[13px] text-blue-400 hover:underline truncate block">${escapeHtml(lead.website)}</a>` : '<p class="text-[13px] text-zinc-500 italic">Nenhum site encontrado</p>'}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-[11px] font-black text-zinc-500 uppercase tracking-widest mb-4">Análise Técnica</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-[13px]">
                                <span class="text-zinc-500">CMS / Tecnologia</span>
                                <span class="text-zinc-200 font-bold">${escapeHtml(lead.cms || 'Desconhecido')}</span>
                            </div>
                            <div class="flex items-center justify-between text-[13px]">
                                <span class="text-zinc-500">Segurança SSL</span>
                                <span class="${lead.ssl_valid ? 'text-emerald-400' : 'text-red-400'} font-bold">${lead.ssl_valid ? 'Válido' : 'Inválido / Ausente'}</span>
                            </div>
                            <div class="flex items-center justify-between text-[13px]">
                                <span class="text-zinc-500">Facebook Pixel</span>
                                <span class="${lead.has_pixel ? 'text-emerald-400' : 'text-zinc-600'} font-bold">${lead.has_pixel ? 'Detectado' : 'Não encontrado'}</span>
                            </div>
                            <div class="flex items-center justify-between text-[13px]">
                                <span class="text-zinc-500">Investimento em Ads</span>
                                <span class="${lead.has_ads ? 'text-emerald-400' : 'text-zinc-600'} font-bold">${lead.has_ads ? 'Sim' : 'Não'}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 pt-8 border-t border-white/5">
                    <button onclick="openSalesScript(${lead.id})" class="btn-ghost flex-1 !py-3 flex items-center justify-center gap-2">
                        <i data-lucide="file-text" class="w-4 h-4"></i> Script de Venda
                    </button>
                    <button onclick="contactLead(${lead.id}, 'whatsapp')" class="btn-accent flex-1 !py-3 !bg-emerald-600 hover:!bg-emerald-500 !shadow-emerald-600/20 flex items-center justify-center gap-2">
                        <i data-lucide="message-circle" class="w-4 h-4"></i> WhatsApp
                    </button>
                </div>
            </div>
        `;
        lucide.createIcons();
    } catch (e) {
        showToast('Erro ao carregar detalhes', 'error');
        closeLeadDetail();
    }
}

function closeLeadDetail() {
    document.getElementById('leadDetailModal').classList.add('hidden');
}

function openExportModal() {
    document.getElementById('exportModal').classList.remove('hidden');
}

function closeExportModal() {
    document.getElementById('exportModal').classList.add('hidden');
}

function openSalesScript(id) {
    // We need to fetch the lead from the DOM or state
    api(`/api/leads/${id}`).then(lead => {
        let script = '';
        const name = lead.name.split(' ')[0];
        
        if (!lead.website) {
            script = `Olá ${name}! Notei que sua empresa tem uma presença forte no Google, mas ainda não encontrei seu site oficial. Hoje em dia, não ter um site passa menos credibilidade para o cliente final. Tenho uma proposta para criar sua vitrine digital profissional essa semana, podemos falar?`;
        } else if (lead.site_quality === 'poor') {
            script = `Olá ${name}! Dei uma olhada no site da sua empresa e notei que ele é um pouco antigo e não está adaptado para celulares modernos. Isso faz você perder muitos clientes que tentam acessar pelo WhatsApp. Eu trabalho com modernização de sites para aumentar conversão. Topa um café rápido online para eu te mostrar como podemos dobrar seus acessos?`;
        } else {
            script = `Olá ${name}! Vi o site da sua empresa e está muito bom. Notei apenas que dá para melhorar o tempo de carregamento e o SEO para vocês aparecerem ainda mais no Google. Trabalho com otimização de performance. Posso te enviar uma análise gratuita?`;
        }

        document.getElementById('scriptText').innerText = script;
        document.getElementById('salesScriptModal').classList.remove('hidden');
        window.currentScript = script;
    });
}

function closeSalesScript() {
    document.getElementById('salesScriptModal').classList.add('hidden');
}

function copyScript() {
    navigator.clipboard.writeText(window.currentScript);
    showToast('Script copiado!', 'success');
}

function resetAndLoad() {
    loadPipeline();
}

function filterPipeline() {
    const search = document.getElementById('pipelineSearch').value;
    if (search.length > 2 || search.length === 0) {
        loadPipeline();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadPipeline();
    setInterval(loadPipeline, 30000); // Sync every 30s
});

function renderOpportunityCard(lead, isPriority) {
    const hasNoSite = !lead.website && lead.site_verified === 0;
    const isOffline = lead.site_status === 'offline';
    const isOldSite = lead.site_quality === 'poor' && lead.site_verified;
    const hasAds = lead.has_ads;
    const isSelected = selectedLeads.has(lead.id);
    const isQualified = lead.is_qualified;
    
    let techBadge = '';
    let techIcon = 'globe';
    let iconClass = 'text-zinc-500 bg-white/5';

    if (isOffline) {
        techBadge = '<span class="px-2 py-0.5 rounded-md bg-fuchsia-500/10 text-fuchsia-400 text-[9px] font-black border border-fuchsia-500/10 tracking-wider">SITE FORA DO AR</span>';
        techIcon = 'plug-zap';
        iconClass = 'text-fuchsia-400 bg-fuchsia-500/10 border-fuchsia-500/10';
    } else if (hasNoSite) {
        techBadge = '<span class="px-2 py-0.5 rounded-md bg-red-500/10 text-red-400 text-[9px] font-black border border-red-500/10 tracking-wider">SEM SITE</span>';
        techIcon = 'alert-circle';
        iconClass = 'text-red-400 bg-red-500/10 border-red-500/10';
    } else if (isOldSite) {
        techBadge = '<span class="px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-400 text-[9px] font-black border border-amber-500/10 tracking-wider">REDESIGN</span>';
        techIcon = 'monitor-x';
        iconClass = 'text-amber-500 bg-amber-500/10 border-amber-500/10';
    }
    
    const adsBadge = hasAds ? '<span class="px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-400 text-[9px] font-black border border-emerald-500/10 tracking-wider ml-2">ADS ACTIVE</span>' : '';

    return `
        <div class="opportunity-card glass-card !p-5 mb-3 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-5 fade-up cursor-pointer group ${isSelected ? 'ring-2 ring-accent/30 bg-accent/[0.03]' : ''}" id="lead-${lead.id}" onclick="toggleLeadSelection(${lead.id})">
            <div class="flex items-center gap-5 flex-1 min-w-0">
                <div class="shrink-0 flex items-center gap-4" onclick="event.stopPropagation()">
                    <input type="checkbox" class="w-4 h-4 rounded border-white/10 bg-white/5 text-accent focus:ring-accent/50 cursor-pointer" ${isSelected ? 'checked' : ''} onchange="toggleLeadSelection(${lead.id})">
                    <div class="w-12 h-12 rounded-2xl ${iconClass} border flex items-center justify-center shrink-0 shadow-lg group-hover:scale-105 transition-transform" onclick="openLeadDetail(${lead.id})">
                        <i data-lucide="${techIcon}" class="w-5 h-5"></i>
                    </div>
                </div>
                
                <div class="min-w-0 flex-1" onclick="event.stopPropagation(); openLeadDetail(${lead.id})">
                    <div class="flex items-center gap-3 mb-1.5">
                        <h4 class="text-[15px] font-bold text-zinc-100 truncate group-hover:text-accent transition-colors">${escapeHtml(lead.name)}</h4>
                        ${techBadge}
                        ${adsBadge}
                        <button onclick="event.stopPropagation(); toggleQualify(${lead.id})" class="ml-auto lg:ml-2 p-1.5 rounded-lg hover:bg-white/10 transition-colors" title="Qualificar Lead">
                            <i data-lucide="star" class="w-4 h-4 ${isQualified ? 'fill-emerald-400 text-emerald-400' : 'text-zinc-700'}"></i>
                        </button>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-[11px] text-zinc-500 font-medium">
                        <span class="flex items-center gap-2"><i data-lucide="map-pin" class="w-3.5 h-3.5 text-zinc-600"></i> ${escapeHtml(lead.location || 'Brasil')}</span>
                        <span class="flex items-center gap-2"><i data-lucide="tag" class="w-3.5 h-3.5 text-zinc-600"></i> ${escapeHtml(lead.category || 'Lead')}</span>
                        <span class="flex items-center gap-2 text-zinc-400 font-bold"><i data-lucide="award" class="w-3.5 h-3.5 text-amber-500/50"></i> ${lead.score} <span class="text-[9px] text-zinc-600 uppercase">pts</span></span>
                        ${lead.website ? `<a href="${lead.website}" target="_blank" onclick="event.stopPropagation()" class="flex items-center gap-2 text-accent hover:text-accent-light transition-colors"><i data-lucide="external-link" class="w-3.5 h-3.5"></i> Website</a>` : ''}
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 w-full lg:w-auto pt-4 lg:pt-0 border-t lg:border-t-0 border-white/5 justify-between lg:justify-end" onclick="event.stopPropagation()">
                <div class="flex items-center gap-2">
                    <button onclick="openSalesScript(${lead.id})" class="action-btn w-9 h-9 rounded-xl bg-white/5 border border-white/5 flex items-center justify-center text-zinc-400 hover:text-zinc-100 hover:bg-white/10" title="Script de Venda">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                    </button>
                    <button onclick="contactLead(${lead.id}, 'whatsapp')" class="action-btn h-9 px-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center gap-2 text-emerald-400 font-bold text-[11px] hover:bg-emerald-500/20 transition-all uppercase tracking-wider">
                        <i data-lucide="message-circle" class="w-4 h-4"></i> WhatsApp
                    </button>
                </div>
                
                <div class="flex items-center gap-2">
                    <button onclick="markContacted(${lead.id})" class="action-btn w-9 h-9 rounded-xl bg-blue-500/5 border border-blue-500/10 flex items-center justify-center text-blue-400/60 hover:text-blue-400 hover:bg-blue-500/15" title="Mover p/ Contatados">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </button>
                    <button onclick="archiveLead(${lead.id})" class="action-btn w-9 h-9 rounded-xl bg-red-500/5 border border-red-500/10 flex items-center justify-center text-red-400/40 hover:text-red-400 hover:bg-red-500/10" title="Arquivar">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

async function toggleQualify(id) {
    try {
        const res = await api(`/api/leads/${id}/qualify`, { method: 'PATCH' });
        const lead = currentLeads.find(l => l.id === id);
        if (lead) lead.is_qualified = res.is_qualified;
        
        showToast(res.is_qualified ? 'Lead qualificado com sucesso' : 'Qualificação removida', 'success');
        
        const star = document.querySelector(`#lead-${id} [data-lucide="star"]`);
        if (star) {
            star.classList.toggle('fill-emerald-400', res.is_qualified);
            star.classList.toggle('text-emerald-400', res.is_qualified);
            star.classList.toggle('text-zinc-600', !res.is_qualified);
        }
        
        // Re-render to ensure correct grouping (Qualified leads move to top)
        setTimeout(() => renderPipeline(), 300);
    } catch (e) {
        showToast('Erro ao qualificar lead', 'error');
    }
}

function toggleLeadSelection(id) {
    const card = document.getElementById(`lead-${id}`);
    const checkbox = card?.querySelector('input[type="checkbox"]');
    
    if (selectedLeads.has(id)) {
        selectedLeads.delete(id);
        card?.classList.remove('border-blue-500/50', 'bg-blue-500/[0.04]');
        if (checkbox) checkbox.checked = false;
    } else {
        selectedLeads.add(id);
        card?.classList.add('border-blue-500/50', 'bg-blue-500/[0.04]');
        if (checkbox) checkbox.checked = true;
    }
    updateSelectionUI();
}

function updateSelectionUI() {
    const count = selectedLeads.size;
    const bar = document.getElementById('selectionActions');
    const countText = document.getElementById('selectedCount');
    
    if (count > 0) {
        bar.classList.remove('hidden');
        bar.classList.add('flex');
        countText.textContent = `${count} Sel.`;
    } else {
        bar.classList.add('hidden');
        bar.classList.remove('flex');
    }
}

function clearSelection() {
    selectedLeads.clear();
    const checks = document.querySelectorAll('input[type="checkbox"]');
    checks.forEach(c => c.checked = false);
    const cards = document.querySelectorAll('.opportunity-card');
    cards.forEach(c => c.classList.remove('border-blue-500/50', 'bg-blue-500/[0.04]'));
    updateSelectionUI();
}

function selectAllLeads() {
    currentLeads.forEach(l => selectedLeads.add(l.id));
    renderPipeline(); 
}

function selectGroup(group) {
    let pool = [];
    if (group === 'priority') {
        pool = currentLeads.filter(l => 
            (l.site_quality === 'poor' && l.site_verified) || 
            (!l.website && l.site_verified === 0)
        );
    } else if (group === 'recent') {
        const priorityIds = new Set(currentLeads.filter(l => 
            (l.site_quality === 'poor' && l.site_verified) || 
            (!l.website && l.site_verified === 0)
        ).map(l => l.id));
        pool = currentLeads.filter(l => !priorityIds.has(l.id));
    }
    
    pool.forEach(l => selectedLeads.add(l.id));
    renderPipeline();
}

async function exportSelected() {
    if (selectedLeads.size === 0) return;
    try {
        showToast('Preparando exportação...', 'info');
        const response = await api('/api/export', {
            method: 'POST',
            body: JSON.stringify({ 
                format: 'xlsx',
                ids: Array.from(selectedLeads)
            })
        });

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `leads_selecionados_${new Date().getTime()}.xlsx`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        showToast('Exportação concluída!', 'success');
        clearSelection();
    } catch (e) {
        showToast(e.message, 'error');
    }
}

async function exportByScore() {
    const minScore = document.getElementById('exportMinScore').value;
    closeExportModal();
    try {
        showToast(`Exportando leads com score ${minScore}+`, 'info');
        const response = await api('/api/export', {
            method: 'POST',
            body: JSON.stringify({ format: 'xlsx', score_min: minScore, sort: 'score', order: 'desc' })
        });
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `leads_score_${minScore}_${new Date().getTime()}.xlsx`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        showToast('Exportação concluída!', 'success');
    } catch (e) {
        showToast(e.message, 'error');
    }
}

async function batchAction(type) {
    if (!selectedLeads.size) return;
    
    const count = selectedLeads.size;
    let message = `Deseja processar ${count} leads?`;
    let payload = {};
    let toastMsg = 'Leads atualizados';

    if (type === 'archive') {
        message = `Deseja arquivar ${count} leads?`;
        payload = { status: 'closed_lost' };
        toastMsg = 'Leads arquivados';
    } else if (type === 'contacted') {
        message = `Deseja marcar ${count} leads como contatados?`;
        payload = { status: 'contacted' };
    } else if (type === 'qualify') {
        message = `Deseja qualificar ${count} leads selecionados?`;
        payload = { qualify: true };
        toastMsg = 'Leads qualificados';
    }

    if (!await confirmModal(message, 'Ação em Massa', type === 'archive')) return;

    try {
        showToast(`Processando ${count} leads...`, 'info');
        const ids = Array.from(selectedLeads);
        payload.ids = ids;
        
        await api('/api/leads/bulk-update', {
            method: 'PATCH',
            body: JSON.stringify(payload)
        });

        showToast(toastMsg, 'success');
        
        if (type !== 'qualify') {
            // Visual exit animation for status changes
            ids.forEach(id => {
                gsap.to(`#lead-${id}`, { 
                    x: type === 'archive' ? 0 : 30, 
                    scale: type === 'archive' ? 0.9 : 1,
                    opacity: 0, 
                    duration: 0.4, 
                    stagger: 0.05 
                });
            });

            setTimeout(() => {
                selectedLeads.clear();
                loadPipeline();
            }, 500);
        } else {
            // Just refresh current view to show stars
            loadPipeline();
        }

    } catch (e) {
        showToast('Erro no processamento em massa', 'error');
    }
}

async function contactLead(id, type) {
    try {
        const res = await api(`/api/leads/${id}/action/${type}`);
        window.open(res.url, '_blank');
        markContacted(id);
    } catch (e) { showToast('Erro ao iniciar contato', 'error'); }
}

async function markContacted(id) {
    try {
        await api(`/api/leads/${id}`, {
            method: 'PATCH',
            body: JSON.stringify({ status: 'contacted' })
        });
        showToast('Lead movido para Contatados', 'success');
        gsap.to(`#lead-${id}`, {
            x: 20, opacity: 0, duration: 0.3, onComplete: () => {
                const el = document.getElementById(`lead-${id}`);
                if (el) el.remove();
                selectedLeads.delete(id);
                updateSelectionUI();
                closeLeadDetail();
            }
        });
    } catch (e) { showToast('Erro ao atualizar status', 'error'); }
}

async function archiveLead(id) {
    if (!await confirmModal('Deseja arquivar este lead? Ele será removido da pipeline.', 'Arquivar', true)) return;
    try {
        await api(`/api/leads/${id}`, {
            method: 'PATCH',
            body: JSON.stringify({ status: 'closed_lost' })
        });
        gsap.to(`#lead-${id}`, {
            scale: 0.9, opacity: 0, duration: 0.2, onComplete: () => {
                const el = document.getElementById(`lead-${id}`);
                if (el) el.remove();
                selectedLeads.delete(id);
                updateSelectionUI();
                closeLeadDetail();
            }
        });
    } catch (e) { showToast('Erro ao arquivar', 'error'); }
}

async function exportPipeline(type) {
    closeExportModal();
    try {
        showToast('Preparando exportação...', 'info');
        
        const payload = { format: 'xlsx' };
        if (type === 'poor') payload.site_quality = 'poor';
        else if (type === 'none') payload.site_quality = 'none';
        else if (type === 'ads') payload.has_ads = true;
        else if (type === 'qualified') payload.is_qualified = true;
        else if (type === 'hot') {
            payload.score_min = 80;
            payload.sort = 'score';
            payload.order = 'desc';
        }
        else if (type === 'recent') {
            payload.quantity = 100;
            payload.sort = 'created_at';
            payload.order = 'desc';
        }

        const response = await api('/api/export', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `leads_pipeline_${type}_${new Date().getTime()}.xlsx`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        showToast('Exportação concluída!', 'success');
    } catch (e) {
        showToast(e.message, 'error');
    }
}

async function openLeadDetail(id) {
    const content = document.getElementById('leadDetailContent');
    content.innerHTML = `
        <div class="flex items-center justify-center py-20 opacity-20">
            <div class="w-8 h-8 border-3 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
        </div>
    `;
    document.getElementById('leadDetailModal').classList.remove('hidden');

    try {
        const lead = await api(`/api/leads/${id}`);
        const hasNoSite = !lead.website && lead.site_verified === 0;
        const isOldSite = lead.site_quality === 'poor' && lead.site_verified;
        
        let techHtml = '';
        if (hasNoSite) {
            techHtml = `<div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 mb-6">
                <p class="text-[13px] font-bold flex items-center gap-2"><i data-lucide="alert-circle" class="w-4 h-4"></i> Empresa sem Website</p>
                <p class="text-[11px] mt-1 opacity-80">Oportunidade ideal para venda de criação de site do zero.</p>
            </div>`;
        } else if (isOldSite) {
            techHtml = `<div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 mb-6">
                <p class="text-[13px] font-bold flex items-center gap-2"><i data-lucide="monitor-x" class="w-4 h-4"></i> Website Antigo / Obsoleto</p>
                <p class="text-[11px] mt-1 opacity-80">Site com tecnologia ultrapassada (Tech Score: ${lead.tech_score}). Recomenda-se redesign completo.</p>
            </div>`;
        }

        content.innerHTML = `
            <div class="p-8">
                <div class="flex items-start gap-6 mb-8">
                    <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-blue-500/20 to-indigo-600/10 border border-white/5 flex items-center justify-center shrink-0">
                        <span class="text-[28px] font-black text-zinc-100">${lead.score}</span>
                    </div>
                    <div class="min-w-0 flex-1 pt-2">
                        <h3 class="text-[22px] font-black text-zinc-100 leading-tight mb-2">${escapeHtml(lead.name)}</h3>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-2.5 py-1 rounded-lg bg-white/5 border border-white/5 text-[10px] font-black uppercase tracking-wider text-zinc-400">${escapeHtml(lead.category || 'Lead')}</span>
                            ${lead.has_ads ? '<span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/10 text-[10px] font-black uppercase tracking-wider text-emerald-400">Ads Spender</span>' : ''}
                            <span class="px-2.5 py-1 rounded-lg bg-blue-500/10 border border-blue-500/10 text-[10px] font-black uppercase tracking-wider text-blue-400">${lead.tier}</span>
                            ${lead.is_qualified ? '<span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/10 text-[10px] font-black uppercase tracking-wider text-emerald-400">Qualificado</span>' : ''}
                        </div>
                    </div>
                </div>

                ${techHtml}

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                    <div>
                        <h4 class="text-[11px] font-black text-zinc-500 uppercase tracking-widest mb-4">Informações de Contato</h4>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-zinc-900 flex items-center justify-center text-zinc-500"><i data-lucide="mail" class="w-4 h-4"></i></div>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-zinc-600 font-bold uppercase">E-mail</p>
                                    <p class="text-[13px] text-zinc-300 truncate">${escapeHtml(lead.email || 'Não informado')}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-zinc-900 flex items-center justify-center text-zinc-500"><i data-lucide="phone" class="w-4 h-4"></i></div>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-zinc-600 font-bold uppercase">Telefone / WhatsApp</p>
                                    <p class="text-[13px] text-zinc-300">${escapeHtml(lead.whatsapp || lead.phone || 'Não informado')}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-zinc-900 flex items-center justify-center text-zinc-500"><i data-lucide="globe" class="w-4 h-4"></i></div>
                                <div class="min-w-0">
                                    <p class="text-[10px] text-zinc-600 font-bold uppercase">Website</p>
                                    ${lead.website ? `<a href="${lead.website}" target="_blank" class="text-[13px] text-blue-400 hover:underline truncate block">${escapeHtml(lead.website)}</a>` : '<p class="text-[13px] text-zinc-500 italic">Nenhum site encontrado</p>'}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-[11px] font-black text-zinc-500 uppercase tracking-widest mb-4">Análise Técnica</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-[13px]">
                                <span class="text-zinc-500">CMS / Tecnologia</span>
                                <span class="text-zinc-200 font-bold">${escapeHtml(lead.cms || 'Desconhecido')}</span>
                            </div>
                            <div class="flex items-center justify-between text-[13px]">
                                <span class="text-zinc-500">Segurança SSL</span>
                                <span class="${lead.ssl_valid ? 'text-emerald-400' : 'text-red-400'} font-bold">${lead.ssl_valid ? 'Válido' : 'Inválido / Ausente'}</span>
                            </div>
                            <div class="flex items-center justify-between text-[13px]">
                                <span class="text-zinc-500">Facebook Pixel</span>
                                <span class="${lead.has_pixel ? 'text-emerald-400' : 'text-zinc-600'} font-bold">${lead.has_pixel ? 'Detectado' : 'Não encontrado'}</span>
                            </div>
                            <div class="flex items-center justify-between text-[13px]">
                                <span class="text-zinc-500">Investimento em Ads</span>
                                <span class="${lead.has_ads ? 'text-emerald-400' : 'text-zinc-600'} font-bold">${lead.has_ads ? 'Sim' : 'Não'}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 pt-8 border-t border-white/5">
                    <button onclick="openSalesScript(${lead.id})" class="btn-ghost flex-1 !py-3 flex items-center justify-center gap-2">
                        <i data-lucide="file-text" class="w-4 h-4"></i> Script de Venda
                    </button>
                    <button onclick="contactLead(${lead.id}, 'whatsapp')" class="btn-accent flex-1 !py-3 !bg-emerald-600 hover:!bg-emerald-500 !shadow-emerald-600/20 flex items-center justify-center gap-2">
                        <i data-lucide="message-circle" class="w-4 h-4"></i> WhatsApp
                    </button>
                    <button onclick="markContacted(${lead.id})" class="btn-ghost !py-3 !px-6"><i data-lucide="check" class="w-5 h-5 text-blue-400"></i></button>
                    <button onclick="archiveLead(${lead.id})" class="btn-ghost !py-3 !px-6 !border-red-500/10 hover:!bg-red-500/5"><i data-lucide="trash-2" class="w-5 h-5 text-red-500/60"></i></button>
                </div>
            </div>
        `;
        lucide.createIcons();
    } catch (e) {
        showToast('Erro ao carregar detalhes', 'error');
        closeLeadDetail();
    }
}

function closeLeadDetail() {
    document.getElementById('leadDetailModal').classList.add('hidden');
}

function openExportModal() {
    document.getElementById('exportModal').classList.remove('hidden');
}

function closeExportModal() {
    document.getElementById('exportModal').classList.add('hidden');
}

function openSalesScript(id) {
    const lead = currentLeads.find(l => l.id === id);
    if (!lead) return;

    let script = '';
    const name = lead.name.split(' ')[0];
    
    if (!lead.website) {
        script = `Olá ${name}! Notei que sua empresa tem uma presença forte no Google, mas ainda não encontrei seu site oficial. Hoje em dia, não ter um site passa menos credibilidade para o cliente final. Tenho uma proposta para criar sua vitrine digital profissional essa semana, podemos falar?`;
    } else if (lead.site_quality === 'poor') {
        script = `Olá ${name}! Dei uma olhada no site da sua empresa e notei que ele é um pouco antigo e não está adaptado para celulares modernos. Isso faz você perder muitos clientes que tentam acessar pelo WhatsApp. Eu trabalho com modernização de sites para aumentar conversão. Topa um café rápido online para eu te mostrar como podemos dobrar seus acessos?`;
    } else {
        script = `Olá ${name}! Vi o site da sua empresa e está muito bom. Notei apenas que dá para melhorar o tempo de carregamento e o SEO para vocês aparecerem ainda mais no Google. Trabalho com otimização de performance. Posso te enviar uma análise gratuita?`;
    }

    document.getElementById('scriptText').innerText = script;
    document.getElementById('salesScriptModal').classList.remove('hidden');
    window.currentScript = script;
}

function closeSalesScript() {
    document.getElementById('salesScriptModal').classList.add('hidden');
}

function copyScript() {
    navigator.clipboard.writeText(window.currentScript);
    showToast('Script copiado!', 'success');
}

// Polling for updates
let pipelineInterval;

function startPipelinePolling() {
    if (pipelineInterval) clearInterval(pipelineInterval);
    pipelineInterval = setInterval(() => {
        // Only poll if on first page and no search/filter active
        const search = document.getElementById('pipelineSearch').value;
        if (currentPage === 1 && !search) {
            silentLoadPipeline();
        }
    }, 15000); // Every 15 seconds
}

async function silentLoadPipeline() {
    const source = document.getElementById('filterSource').value;
    const quality = document.getElementById('filterQuality').value;
    const qualified = document.getElementById('filterQualified').value;
    
    try {
        let url = `/api/leads?per_page=${perPage}&page=${currentPage}&sort=score&order=desc&status=new`;
        if (source) url += `&source=${source}`;
        if (quality) url += `&site_quality=${quality}`;
        if (qualified) url += `&qualified=${qualified}`;

        const data = await api(url);
        
        // Only re-render if data actually changed
        if (JSON.stringify(data.leads.map(l => l.id)) !== JSON.stringify(currentLeads.map(l => l.id))) {
            currentLeads = data.leads;
            totalPages = data.pages;
            
            document.getElementById('totalPages').textContent = totalPages;
            document.getElementById('currentPage').textContent = currentPage;
            renderPipeline();
        }

    } catch (e) {
        console.error("Silent sync failed", e);
    }
}

// Event Bus Integration for Real-time Updates
function listenToPipelineEvents() {
    if (window.eventSource) {
        window.eventBus.on('pipeline_update', (data) => {
            console.log("Pipeline update detected via EventBus", data);
            silentLoadPipeline();
        });
        
        window.eventBus.on('lead_qualified', (data) => {
             silentLoadPipeline();
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadPipeline();
    startPipelinePolling();
    // Wait a bit for base.html to initialize eventSource/eventBus
    setTimeout(listenToPipelineEvents, 1000);
});
