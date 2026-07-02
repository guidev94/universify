let currentLeads = [];

async function loadPipeline() {
    const feed = document.getElementById('pipelineFeed');
    const source = document.getElementById('filterSource').value;
    const quality = document.getElementById('filterQuality').value;
    
    try {
        // Fetch more leads to have a good pool for the feed
        const data = await api(`/api/leads?per_page=300&sort=score&order=desc${source ? '&source='+source : ''}`);
        currentLeads = data.leads;

        if (!currentLeads.length) {
            feed.innerHTML = `
                <div class="flex flex-col items-center justify-center py-20 bg-white/[0.02] border border-white/5 rounded-3xl">
                    <i data-lucide="search-x" class="w-12 h-12 text-zinc-700 mb-4"></i>
                    <p class="text-zinc-500 font-medium">Nenhuma oportunidade encontrada com os filtros atuais.</p>
                </div>
            `;
            lucide.createIcons();
            return;
        }

        // Identify Priority Leads (Tech Opportunities)
        let priorityLeads = currentLeads.filter(l => 
            (l.site_quality === 'poor' && l.site_verified) || 
            (!l.website && l.site_verified === 0)
        );

        if (quality === 'poor') {
            priorityLeads = priorityLeads.filter(l => l.site_quality === 'poor' && l.site_verified);
        } else if (quality === 'none') {
            priorityLeads = priorityLeads.filter(l => !l.website && l.site_verified === 0);
        }

        const otherLeads = currentLeads.filter(l => 
            !priorityLeads.find(p => p.id === l.id) && l.status === 'new'
        );

        let html = '';

        if (priorityLeads.length) {
            html += `<div class="mb-6"><h3 class="text-[11px] font-black text-amber-500 uppercase tracking-[0.2em] mb-4 flex items-center gap-2"><i data-lucide="zap" class="w-3 h-3"></i> Alta Prioridade (Redesign Necessário)</h3>`;
            html += priorityLeads.map(l => renderOpportunityCard(l, true)).join('');
            html += `</div>`;
        }

        if (otherLeads.length) {
            html += `<div class="mb-6"><h3 class="text-[11px] font-black text-zinc-500 uppercase tracking-[0.2em] mb-4 flex items-center gap-2"><i data-lucide="clock" class="w-3 h-3"></i> Recém Capturados</h3>`;
            html += otherLeads.slice(0, 50).map(l => renderOpportunityCard(l, false)).join('');
            html += `</div>`;
        }

        feed.innerHTML = html;
        lucide.createIcons();

    } catch (e) {
        showToast('Erro ao carregar pipeline', 'error');
        console.error(e);
    }
}

function renderOpportunityCard(lead, isPriority) {
    const hasNoSite = !lead.website && lead.site_verified === 0;
    const isOldSite = lead.site_quality === 'poor' && lead.site_verified;
    const hasAds = lead.has_ads;
    
    let techBadge = '';
    if (hasNoSite) techBadge = '<span class="px-2 py-0.5 rounded-md bg-red-500/10 text-red-400 text-[10px] font-black border border-red-500/10">SEM SITE</span>';
    else if (isOldSite) techBadge = '<span class="px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-400 text-[10px] font-black border border-amber-500/10">SITE ANTIGO</span>';
    
    const adsBadge = hasAds ? '<span class="px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-400 text-[10px] font-black border border-emerald-500/10 ml-2">GASTA COM ADS</span>' : '';

    return `
        <div class="opportunity-card glass-card !p-6 mb-3 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 fade-up cursor-pointer group" id="lead-${lead.id}" onclick="openLeadDetail(${lead.id})">
            <div class="flex items-start gap-5 flex-1 min-w-0">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br ${isPriority ? 'from-amber-500/20 to-orange-600/10' : 'from-zinc-800 to-zinc-900'} border border-white/5 flex items-center justify-center shrink-0">
                    <span class="text-[18px] font-black text-zinc-100">${lead.score}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <h4 class="text-[16px] font-bold text-zinc-100 truncate group-hover:text-blue-400 transition-colors">${escapeHtml(lead.name)}</h4>
                        ${techBadge}
                        ${adsBadge}
                    </div>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[12px] text-zinc-500">
                        <span class="flex items-center gap-1.5"><i data-lucide="map-pin" class="w-3 h-3 text-zinc-600"></i> ${escapeHtml(lead.location || 'Brasil')}</span>
                        <span class="flex items-center gap-1.5"><i data-lucide="tag" class="w-3 h-3 text-zinc-600"></i> ${escapeHtml(lead.category || 'Lead')}</span>
                        ${lead.website ? `<a href="${lead.website}" target="_blank" onclick="event.stopPropagation()" class="flex items-center gap-1.5 text-blue-400 hover:underline"><i data-lucide="external-link" class="w-3 h-3"></i> Ver Site</a>` : ''}
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0" onclick="event.stopPropagation()">
                <button onclick="openSalesScript(${lead.id})" class="action-btn w-10 h-10 rounded-xl bg-white/5 border border-white/5 flex items-center justify-center text-zinc-400 hover:text-zinc-100 hover:bg-white/10" title="Script de Venda">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </button>
                <button onclick="contactLead(${lead.id}, 'whatsapp')" class="action-btn px-4 h-10 rounded-xl bg-green-500/10 border border-green-500/20 flex items-center gap-2 text-green-400 font-bold text-[12px] hover:bg-green-500/20">
                    <i data-lucide="message-circle" class="w-4 h-4"></i> WhatsApp
                </button>
                <button onclick="markContacted(${lead.id})" class="action-btn w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 hover:bg-blue-500/20" title="Marcar como Contatado">
                    <i data-lucide="check" class="w-4 h-4"></i>
                </button>
                <button onclick="archiveLead(${lead.id})" class="action-btn w-10 h-10 rounded-xl bg-red-500/5 border border-red-500/10 flex items-center justify-center text-red-400/50 hover:text-red-400 hover:bg-red-500/10" title="Arquivar">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    `;
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
                closeLeadDetail();
            }
        });
    } catch (e) { showToast('Erro ao arquivar', 'error'); }
}

async function exportPipeline(type) {
    try {
        showToast('Preparando exportação...', 'info');
        
        const payload = { format: 'xlsx' };
        if (type === 'poor') payload.site_quality = 'poor';
        else if (type === 'none') payload.site_quality = 'none';
        else if (type === 'ads') payload.has_ads = true;

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

async function exportPipeline(type) {
    closeExportModal();
    try {
        showToast('Preparando exportação...', 'info');
        
        const payload = { format: 'xlsx' };
        if (type === 'poor') payload.site_quality = 'poor';
        else if (type === 'none') payload.site_quality = 'none';
        else if (type === 'ads') payload.has_ads = true;

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

document.addEventListener('DOMContentLoaded', loadPipeline);
