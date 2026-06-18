async function loadKanban() {
    try {
        const statuses = ['new', 'contacted', 'negotiating', 'closed_won'];
        const response = await api('/api/leads?per_page=100&sort=score&order=desc');
        const leads = response.leads;

        // Clear lists
        statuses.forEach(s => {
            const list = document.getElementById(`list-${s}`);
            list.innerHTML = '';
            const count = leads.filter(l => l.status === s).length;
            const column = list.closest('.kanban-column');
            column.querySelector('.count-badge').textContent = count;
        });

        // Group leads by status
        leads.forEach(lead => {
            const list = document.getElementById(`list-${lead.status}`);
            if (!list) return;

            const card = document.createElement('div');
            card.className = 'kanban-card glass-card !p-4 hover:border-blue-500/30 transition-all active:scale-[0.98]';
            card.dataset.id = lead.id;
            
            const tierColor = lead.tier === 'hot' ? 'text-amber-400' : lead.tier === 'warm' ? 'text-orange-400' : 'text-zinc-500';
            const domain = lead.website ? lead.website.replace(/https?:\/\/(www\.)?/, '').split('/')[0] : '';
            
            // Site quality badge
            let techBadge = '';
            if (lead.site_quality === 'poor' && lead.site_verified) {
                techBadge = '<span class="ml-1 px-1.5 py-0.5 rounded-[4px] text-[8px] font-bold bg-red-500/15 text-red-400 border border-red-500/10">SITE ANTIGO</span>';
            }

            card.innerHTML = `
                <div class="flex justify-between items-start mb-2">
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-bold uppercase tracking-tighter ${tierColor}">${lead.tier}</span>
                        ${techBadge}
                    </div>
                    <span class="text-[10px] font-bold text-zinc-600">#${lead.score}</span>
                </div>
                <h4 class="text-[13px] font-semibold text-zinc-100 truncate mb-1">${escapeHtml(lead.name)}</h4>
                <p class="text-[11px] text-zinc-500 truncate mb-3">${escapeHtml(lead.email || lead.phone || 'Sem contato')}</p>
                
                <div class="flex items-center justify-between pt-3 border-t border-white/5">
                    <div class="flex gap-1.5">
                        ${lead.whatsapp ? `<i data-lucide="message-square" class="w-3 h-3 text-green-500/70"></i>` : ''}
                        ${lead.website ? `<i data-lucide="globe" class="w-3 h-3 text-blue-500/70"></i>` : ''}
                        ${lead.cnpj ? `<i data-lucide="building-2" class="w-3 h-3 text-purple-500/70" title="CNPJ Disponível"></i>` : ''}
                    </div>
                    <span class="text-[9px] text-zinc-700 font-medium">${formatDate(lead.created_at)}</span>
                </div>
            `;
            
            card.onclick = () => window.location.href = `/leads?search=${encodeURIComponent(lead.name)}`;
            list.appendChild(card);
        });

        lucide.createIcons();
        initSortable();
    } catch (e) {
        showToast('Erro ao carregar kanban', 'error');
    }
}

function initSortable() {
    const lists = document.querySelectorAll('.kanban-list');
    lists.forEach(el => {
        new Sortable(el, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'kanban-ghost',
            onEnd: async (evt) => {
                const leadId = evt.item.dataset.id;
                const newStatus = evt.to.id.replace('list-', '');
                
                try {
                    await api(`/api/leads/${leadId}`, {
                        method: 'PATCH',
                        body: JSON.stringify({ status: newStatus })
                    });
                    showToast('Status atualizado', 'success');
                    
                    // Update counters
                    const fromCount = evt.from.closest('.kanban-column').querySelector('.count-badge');
                    const toCount = evt.to.closest('.kanban-column').querySelector('.count-badge');
                    fromCount.textContent = parseInt(fromCount.textContent) - 1;
                    toCount.textContent = parseInt(toCount.textContent) + 1;
                } catch (e) {
                    showToast('Erro ao mover lead', 'error');
                    loadKanban();
                }
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', loadKanban);
