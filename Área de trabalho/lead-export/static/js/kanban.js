async function loadKanban() {
    try {
        const statuses = ['new', 'contacted', 'negotiating', 'closed_won', 'opportunities'];
        const response = await api('/api/leads?per_page=300&sort=score&order=desc');
        const leads = response.leads;

        // Clear lists and set counts
        statuses.forEach(s => {
            const list = document.getElementById(`list-${s}`);
            if (!list) return;
            list.innerHTML = '';
            
            let columnLeads = [];
            if (s === 'opportunities') {
                columnLeads = leads.filter(l => 
                    (l.site_quality === 'poor' && l.site_verified) || 
                    (!l.website && l.site_verified === 0)
                );
            } else {
                columnLeads = leads.filter(l => l.status === s);
            }

            const column = list.closest('.kanban-column');
            const countBadge = column.querySelector('.count-badge');
            
            // Animate count change
            const currentCount = parseInt(countBadge.textContent) || 0;
            if (currentCount !== columnLeads.length) {
                gsap.to({ val: currentCount }, {
                    val: columnLeads.length,
                    duration: 0.5,
                    onUpdate: function() {
                        countBadge.textContent = Math.round(this.targets()[0].val);
                    }
                });
            }

            columnLeads.forEach((lead, index) => {
                const card = createKanbanCard(lead);
                card.style.opacity = '0';
                list.appendChild(card);
                
                // Staggered entry animation
                gsap.to(card, {
                    opacity: 1,
                    y: 0,
                    delay: index * 0.03,
                    duration: 0.4,
                    ease: 'power2.out',
                    startAt: { y: 15 }
                });
            });
        });

        lucide.createIcons();
        initSortable();
    } catch (e) {
        showToast('Erro ao carregar kanban', 'error');
        console.error(e);
    }
}

function createKanbanCard(lead) {
    const card = document.createElement('div');
    card.className = 'kanban-card glass-card !p-4 hover:border-blue-500/40 transition-all active:scale-[0.97] group relative overflow-hidden';
    card.dataset.id = lead.id;
    
    // Tech insights logic
    let techBadge = '';
    let techReason = '';
    if (lead.site_quality === 'poor' && lead.site_verified) {
        techBadge = '<span class="px-1.5 py-0.5 rounded-md text-[8px] font-black bg-amber-500/20 text-amber-500 border border-amber-500/10">SITE ANTIGO</span>';
        techReason = 'Design obsoleto ou não responsivo';
    } else if (!lead.website && lead.site_verified === 0) {
        techBadge = '<span class="px-1.5 py-0.5 rounded-md text-[8px] font-black bg-red-500/20 text-red-400 border border-red-500/10">SEM SITE</span>';
        techReason = 'Empresa sem presença digital';
    } else if (lead.has_ads) {
        techBadge = '<span class="px-1.5 py-0.5 rounded-md text-[8px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/10">ADS SPENDER</span>';
        techReason = 'Investe em marketing digital';
    }

    const tierCls = {
        hot: 'bg-amber-500/20 text-amber-400 border-amber-500/20',
        warm: 'bg-orange-500/20 text-orange-400 border-orange-500/20',
        cold: 'bg-zinc-500/20 text-zinc-400 border-zinc-500/20'
    }[lead.tier] || 'bg-zinc-500/20 text-zinc-400';

    card.innerHTML = `
        <div class="flex justify-between items-start mb-3">
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-1.5">
                    <span class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider border ${tierCls}">${lead.tier}</span>
                    ${techBadge}
                </div>
            </div>
            <div class="flex flex-col items-end">
                <div class="flex items-center gap-1">
                    <span class="text-[13px] font-black text-zinc-100 tabular-nums">${lead.score}</span>
                    <div class="w-1.5 h-1.5 rounded-full ${lead.score >= 80 ? 'bg-amber-400' : lead.score >= 50 ? 'bg-orange-400' : 'bg-zinc-600'} shadow-[0_0_8px_currentColor]"></div>
                </div>
            </div>
        </div>
        
        <h4 class="text-[14px] font-bold text-zinc-100 truncate mb-1 group-hover:text-blue-400 transition-colors leading-tight">${escapeHtml(lead.name)}</h4>
        <div class="flex items-center gap-2 mb-4">
            <p class="text-[11px] text-zinc-500 truncate font-medium flex-1">${escapeHtml(lead.email || lead.phone || 'Sem contato')}</p>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-white/[0.04]">
            <div class="flex gap-1.5">
                ${lead.whatsapp ? `<div class="w-6 h-6 rounded-lg bg-green-500/10 flex items-center justify-center" title="WhatsApp Disponível"><i data-lucide="message-circle" class="w-3 h-3 text-green-500/80"></i></div>` : ''}
                ${lead.website ? `<div class="w-6 h-6 rounded-lg bg-blue-500/10 flex items-center justify-center" title="Website: ${escapeHtml(lead.website)}"><i data-lucide="globe" class="w-3 h-3 text-blue-500/80"></i></div>` : ''}
                ${lead.has_pixel ? `<div class="w-6 h-6 rounded-lg bg-purple-500/10 flex items-center justify-center" title="Pixel Detectado"><i data-lucide="code-2" class="w-3 h-3 text-purple-500/80"></i></div>` : ''}
            </div>
            <div class="flex items-center gap-2 text-zinc-700">
                <span class="text-[9px] font-bold uppercase tracking-tighter">${escapeHtml(lead.source || 'web')}</span>
                <i data-lucide="chevron-right" class="w-3 h-3 opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all"></i>
            </div>
        </div>

        <!-- Glow effect on hover -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
    `;
    
    card.onclick = () => window.location.href = `/leads?search=${encodeURIComponent(lead.name)}`;
    return card;
}

function initSortable() {
    const lists = document.querySelectorAll('.kanban-list');
    lists.forEach(el => {
        if (el.sortable) el.sortable.destroy();
        
        el.sortable = new Sortable(el, {
            group: 'kanban',
            animation: 250,
            ghostClass: 'kanban-ghost',
            dragClass: 'kanban-drag',
            fallbackOnBody: true,
            swapThreshold: 0.65,
            onStart: (evt) => {
                evt.item.classList.add('rotate-2');
                gsap.to(evt.item, { scale: 1.02, duration: 0.2 });
            },
            onEnd: async (evt) => {
                evt.item.classList.remove('rotate-2');
                gsap.to(evt.item, { scale: 1, duration: 0.2 });
                
                const leadId = evt.item.dataset.id;
                const newStatus = evt.to.closest('.kanban-column').dataset.status;
                
                if (newStatus === 'opportunities') {
                    showToast('Esta coluna é automática baseada em inteligência tech.', 'info');
                    loadKanban();
                    return;
                }

                const fromStatus = evt.from.closest('.kanban-column').dataset.status;
                if (fromStatus === newStatus) return;

                try {
                    await api(`/api/leads/${leadId}`, {
                        method: 'PATCH',
                        body: JSON.stringify({ status: newStatus })
                    });
                    showToast(`Lead movido para ${newStatus}`, 'success');
                    
                    // Trigger total refresh to update counts properly across columns
                    loadKanban();
                } catch (e) {
                    showToast('Erro ao atualizar status', 'error');
                    loadKanban();
                }
            }
        });
    });
}

async function columnActions(status) {
    const actions = [
        { id: 'move_negotiating', label: 'Mover todos para Negociando', icon: 'trending-up' },
        { id: 'move_contacted', label: 'Mover todos para Contatados', icon: 'message-square' },
        { id: 'delete_all', label: 'Excluir todos desta coluna', icon: 'trash-2', danger: true }
    ];

    // Filter out same-status move
    const filteredActions = actions.filter(a => !a.id.includes(status));

    const choice = await showActionMenu(filteredActions);
    if (!choice) return;

    if (choice === 'delete_all') {
        if (!await confirmModal('Excluir todos os leads desta coluna?', 'Excluir', true)) return;
        try {
            const res = await api('/api/leads/bulk-delete-column', {
                method: 'POST',
                body: JSON.stringify({ status })
            });
            showToast(`${res.deleted} leads excluídos`, 'success');
            loadKanban();
        } catch (e) { showToast('Erro ao excluir leads', 'error'); }
    } else {
        const toStatus = choice.replace('move_', '');
        try {
            const res = await api('/api/leads/bulk-status-update', {
                method: 'POST',
                body: JSON.stringify({ from_status: status, to_status: toStatus })
            });
            showToast(`${res.updated} leads movidos`, 'success');
            loadKanban();
        } catch (e) { showToast('Erro ao mover leads', 'error'); }
    }
}

async function showActionMenu(actions) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[150] bg-black/40 backdrop-blur-sm flex items-center justify-center p-4';
        
        const menu = document.createElement('div');
        menu.className = 'glass-card p-2 w-full max-w-[280px] fade-up';
        menu.innerHTML = actions.map(a => `
            <button class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/5 transition-colors text-[13px] font-medium ${a.danger ? 'text-red-400' : 'text-zinc-200'}" data-id="${a.id}">
                <i data-lucide="${a.icon}" class="w-4 h-4"></i>
                ${a.label}
            </button>
        `).join('');

        overlay.appendChild(menu);
        document.body.appendChild(overlay);
        lucide.createIcons();

        overlay.onclick = (e) => {
            if (e.target === overlay) { overlay.remove(); resolve(null); }
        };

        menu.querySelectorAll('button').forEach(btn => {
            btn.onclick = () => { overlay.remove(); resolve(btn.dataset.id); };
        });
    });
}

document.addEventListener('DOMContentLoaded', loadKanban);

document.addEventListener('DOMContentLoaded', loadKanban);
