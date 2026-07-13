let leadsChart = null;
let statValues = {};
let isFirstLoad = true;
let tierModalPage = 1;
let tierModalCurrentTier = 'all';
let tierModalTotal = 0;
let tierModalMetricParams = '';

function animateValue(el, start, end, duration) {
    if (start === end) { el.textContent = end.toLocaleString('pt-BR'); return; }
    const range = end - start;
    const startTime = performance.now();
    function step(timestamp) {
        const progress = Math.min((timestamp - startTime) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(start + range * eased).toLocaleString('pt-BR');
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

async function loadStats() {
    try {
        const data = await api('/api/dashboard/stats');

        const fields = [
            { id: 'statTotal', key: 'total' },
            { id: 'statHot', key: 'hot' },
            { id: 'statWarm', key: 'warm' },
            { id: 'statCold', key: 'cold' },
            { id: 'statEmail', key: 'with_email' },
            { id: 'statPhone', key: 'with_phone' },
            { id: 'statWhatsapp', key: 'with_whatsapp' },
            { id: 'statHasAds', key: 'with_ads' },
            { id: 'statHasPixel', key: 'with_pixel' },
            { id: 'statVerified', key: 'verified' },
        ];

        for (const { id, key } of fields) {
            const el = document.getElementById(id);
            if (!el) continue;
            const newVal = data[key] || 0;

            if (isFirstLoad) {
                animateValue(el, 0, newVal, 800);
            } else {
                el.textContent = newVal.toLocaleString('pt-BR');
            }
            statValues[key] = newVal;
        }

        const avgEl = document.getElementById('statAvgScore');
        if (avgEl) avgEl.textContent = data.avg_score;
        document.getElementById('statRevenue').textContent = `R$ ${data.revenue.toLocaleString('pt-BR', {minimumFractionDigits: 0})}`;
        document.getElementById('statDeals').textContent = data.deals_closed.toLocaleString('pt-BR');
        document.getElementById('statConversion').textContent = `${data.conversion_rate}%`;
        document.getElementById('statContacted').textContent = (data.contacted || 0).toLocaleString('pt-BR');
        document.getElementById('statNegotiating').textContent = data.negotiating.toLocaleString('pt-BR');

        isFirstLoad = false;
    } catch (e) {
        console.error('Erro ao carregar stats:', e);
    }
}

async function loadCharts() {
    try {
        const data = await api('/api/dashboard/chart-data');

        const ctxLine = document.getElementById('leadsChart').getContext('2d');
        if (leadsChart) leadsChart.destroy();

        leadsChart = new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: data.labels.map(d => {
                    const parts = d.split('-');
                    return `${parts[2]}/${parts[1]}`;
                }),
                datasets: [{
                    label: 'Leads',
                    data: data.values,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.08)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointBackgroundColor: '#2563eb',
                    pointHitRadius: 10,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.03)' },
                        ticks: { color: '#64748b', font: { size: 10 }, maxTicksLimit: 8 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.03)' },
                        ticks: { color: '#64748b', font: { size: 10 }, stepSize: 1 }
                    }
                }
            }
        });

        // Activity feed is loaded separately (no doughnut chart)

        // Fontes badges
        if (data.sources) {
            const container = document.getElementById('sourceBadges');
            if (container) {
                container.innerHTML = Object.entries(data.sources).map(([src, cnt]) =>
                    `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/[0.03] border border-white/5 text-[10px] text-zinc-400">
                        <span class="font-semibold text-zinc-200">${cnt}</span> ${src}
                    </span>`
                ).join('');
            }
        }
    } catch (e) {
        console.error('Erro ao carregar charts:', e);
    }
}

async function loadRecentJobs() {
    try {
        const data = await api('/api/dashboard/recent-jobs');
        const tbody = document.getElementById('recentJobsTable');

        if (!data.jobs || data.jobs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-zinc-600 py-8">Nenhuma extração realizada ainda</td></tr>';
            return;
        }

        const sourceLabels = { both: 'Todas', maps: 'Mapa', website: 'Site' };
        const statusLabels = { completed: 'concluído', running: 'rodando', error: 'erro', pending: 'pendente' };

        tbody.innerHTML = data.jobs.map(job => {
            const sourceLabel = sourceLabels[job.source] || job.source;
            const statusLabel = statusLabels[job.status] || job.status;
            const statusColors = {
                completed: 'text-emerald-400 bg-emerald-500/10',
                running: 'text-blue-400 bg-blue-500/10',
                error: 'text-red-400 bg-red-500/10',
                pending: 'text-zinc-400 bg-zinc-500/10',
            };
            const statusColor = statusColors[job.status] || statusColors.pending;
            return `
                <tr>
                    <td class="font-medium text-white">${escapeHtml(job.keyword)}</td>
                    <td>${escapeHtml(job.location) || '—'}</td>
                    <td><span class="text-xs px-2 py-0.5 rounded bg-white/5">${sourceLabel}</span></td>
                    <td class="font-semibold">${job.quantity_found}</td>
                    <td><span class="text-xs px-2 py-0.5 rounded ${statusColor}">${statusLabel}</span></td>
                    <td class="text-zinc-500">${formatDate(job.started_at)}</td>
                    <td><button onclick="deleteJob(${job.id})" class="p-1 rounded hover:bg-red-500/10 transition-colors" title="Excluir"><svg class="w-3.5 h-3.5 text-red-400/60 hover:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>
                </tr>
            `;
        }).join('');
    } catch (e) {
        console.error('Erro ao carregar jobs:', e);
    }
}

async function loadSchedulerStatus() {
    try {
        const data = await api('/api/system/health');
        const dot = document.getElementById('schedulerDot');
        const label = document.getElementById('schedulerLabel');
        const next = document.getElementById('schedulerNext');
        const rate = document.getElementById('schedulerRate');

        if (data.running) {
            dot.className = 'w-2 h-2 rounded-full bg-emerald-400 glow-dot';
            label.textContent = 'Auto-Crawl Ativo';
        } else {
            dot.className = 'w-2 h-2 rounded-full bg-red-400';
            label.textContent = 'Auto-Crawl Parado';
        }

        const mins = Math.floor(data.next_crawl_in / 60);
        const hours = Math.floor(mins / 60);
        const remainMins = mins % 60;
        next.textContent = hours > 0
            ? `Próximo em ${hours}h ${remainMins}min`
            : `Próximo em ${mins}min`;

        rate.textContent = `Sucesso: ${data.success_rate}%`;
    } catch (e) {
        const label = document.getElementById('schedulerLabel');
        if (label) label.textContent = 'Status indisponível';
    }
}

async function deleteJob(id) {
    if (!await confirmModal('Excluir esta extração?', 'Excluir', true)) return;
    try {
        await api(`/api/crawl/jobs/${id}`, { method: 'DELETE' });
        showToast('Extração excluída', 'success');
        loadRecentJobs();
        loadStats();
    } catch (e) {
        showToast(e.message, 'error');
    }
}

function openMetricModal(metric) {
    const configs = {
        deals: { title: 'Leads Fechados', params: 'status=closed_won' },
        contacted: { title: 'Leads Contatados', params: 'status=contacted' },
        negotiating: { title: 'Leads Negociando', params: 'status=negotiating' },
        email: { title: 'Leads com Email', params: 'has_email=1' },
        phone: { title: 'Leads com Telefone', params: 'has_phone=1' },
        whatsapp: { title: 'Leads com WhatsApp', params: 'has_whatsapp=1' },
    };
    const cfg = configs[metric];
    if (!cfg) return;
    tierModalCurrentTier = '__metric__';
    tierModalMetricParams = cfg.params;
    tierModalPage = 1;
    document.getElementById('tierModalTitle').textContent = cfg.title;
    document.getElementById('tierModal').classList.remove('hidden');
    loadTierModalPage();
}

async function openTierModal(tier) {
    tierModalCurrentTier = tier;
    tierModalMetricParams = '';
    tierModalPage = 1;
    document.getElementById('tierModal').classList.remove('hidden');
    await loadTierModalPage();
}

async function loadTierModalPage() {
    const modal = document.getElementById('tierModal');
    const title = document.getElementById('tierModalTitle');
    const body = document.getElementById('tierModalBody');
    const footer = document.getElementById('tierModalFooter');

    if (tierModalCurrentTier !== '__metric__') {
        const titles = { hot: 'Leads Quentes', warm: 'Leads Mornos', cold: 'Leads Frios', all: 'Todos os Leads' };
        title.textContent = titles[tierModalCurrentTier] || 'Leads';
    }

    body.innerHTML = '<div class="flex justify-center py-8"><div class="w-6 h-6 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"></div></div>';

    try {
        let params;
        if (tierModalCurrentTier === '__metric__') {
            params = `per_page=15&page=${tierModalPage}&sort=created_at&order=desc&${tierModalMetricParams}`;
        } else if (tierModalCurrentTier === 'all') {
            params = `per_page=15&page=${tierModalPage}&sort=created_at&order=desc`;
        } else {
            params = `tier=${tierModalCurrentTier}&per_page=15&page=${tierModalPage}&sort=created_at&order=desc`;
        }
        const data = await api(`/api/leads?${params}`);
        tierModalTotal = data.total;

        if (!data.leads || data.leads.length === 0) {
            body.innerHTML = '<p class="text-center text-zinc-500 py-8">Nenhum lead nesta categoria</p>';
            footer.innerHTML = '';
            return;
        }

        body.innerHTML = data.leads.map(lead => {
            const phone = lead.whatsapp || lead.phone || '';
            const whatsappUrl = phone ? `https://wa.me/${phone.replace(/\\D/g, '')}` : '';
            const emailUrl = lead.email ? `mailto:${lead.email.split(';')[0]}` : '';
            const tierColors = { hot: 'text-amber-400 bg-amber-500/10', warm: 'text-orange-400 bg-orange-500/10', cold: 'text-zinc-400 bg-zinc-500/10' };

            return `
                <div class="p-3 rounded-xl hover:bg-white/[0.03] transition-all border border-transparent hover:border-white/5 cursor-pointer group" onclick="openDashLeadDetail(${lead.id})">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg ${tierColors[lead.tier] || 'bg-zinc-500/10'} flex items-center justify-center shrink-0">
                            <span class="text-[11px] font-bold">${lead.score}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-[13px] font-medium text-zinc-100 truncate">${escapeHtml(lead.name)}</p>
                                ${lead.category ? `<span class="text-[9px] text-zinc-500 bg-zinc-800/50 px-1.5 py-0.5 rounded shrink-0">${escapeHtml(lead.category)}</span>` : ''}
                            </div>
                            <div class="flex items-center gap-3 mt-1">
                                ${lead.email ? `<span class="text-[10px] text-zinc-400 truncate max-w-[160px]">${escapeHtml(lead.email.split(';')[0])}</span>` : ''}
                                ${phone ? `<span class="text-[10px] text-zinc-500">${escapeHtml(phone)}</span>` : ''}
                            </div>
                            ${lead.website ? `<p class="text-[10px] text-blue-400/70 truncate mt-0.5">${escapeHtml(lead.website)}</p>` : ''}
                        </div>
                        <div class="flex items-center gap-1.5 ml-2 shrink-0">
                            ${whatsappUrl ? `<a href="${whatsappUrl}" target="_blank" onclick="event.stopPropagation()" class="p-1.5 rounded-lg hover:bg-green-500/10"><svg class="w-3.5 h-3.5 text-green-400" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg></a>` : ''}
                            ${emailUrl ? `<a href="${emailUrl}" onclick="event.stopPropagation()" class="p-1.5 rounded-lg hover:bg-blue-500/10"><svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></a>` : ''}
                            <svg class="w-4 h-4 text-zinc-600 group-hover:text-zinc-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        const totalPages = Math.ceil(data.total / 15);
        const from = (tierModalPage - 1) * 15 + 1;
        const to = Math.min(tierModalPage * 15, data.total);

        footer.innerHTML = `
            <div class="flex items-center justify-between pt-3 border-t border-white/5">
                <span class="text-[11px] text-zinc-500">${from}–${to} de ${data.total}</span>
                <div class="flex gap-1">
                    <button onclick="tierModalPrev()" class="btn-ghost !px-2 !py-1 text-[11px] ${tierModalPage <= 1 ? 'opacity-30 pointer-events-none' : ''}">← Anterior</button>
                    <button onclick="tierModalNext()" class="btn-ghost !px-2 !py-1 text-[11px] ${tierModalPage >= totalPages ? 'opacity-30 pointer-events-none' : ''}">Próximo →</button>
                </div>
            </div>
        `;
    } catch (e) {
        body.innerHTML = '<p class="text-center text-red-400 py-8">Erro ao carregar leads</p>';
    }
}

function tierModalPrev() { if (tierModalPage > 1) { tierModalPage--; loadTierModalPage(); } }
function tierModalNext() { tierModalPage++; loadTierModalPage(); }
function closeTierModal() { document.getElementById('tierModal').classList.add('hidden'); }
function closeDashLeadDetail() { document.getElementById('dashLeadDetailModal').classList.add('hidden'); }

async function openDashLeadDetail(id) {
    const lead = await api(`/api/leads/${id}`);
    if (!lead) return;

    const scoreColor = lead.score >= 80 ? '#fde047' : lead.score >= 50 ? '#fb923c' : '#a1a1aa';
    const circumference = 2 * Math.PI * 36;
    const offset = circumference - (lead.score / 100) * circumference;
    const phones = (lead.phone || '').split(';').filter(p => p.trim());
    const emails = (lead.email || '').split(';').filter(e => e.trim());
    const wa = lead.whatsapp || phones[0] || '';
    const tierGrad = { hot: 'from-amber-500/20 to-orange-500/10', warm: 'from-orange-500/15 to-yellow-500/5', cold: 'from-zinc-500/10 to-zinc-600/5' };

    const modal = document.getElementById('dashLeadDetailModal');
    document.getElementById('dashLeadDetailContent').innerHTML = `
        <div class="bg-gradient-to-br ${tierGrad[lead.tier] || tierGrad.cold} rounded-xl p-5 mb-5">
            <div class="flex items-start gap-4">
                <div class="relative w-20 h-20 shrink-0">
                    <svg viewBox="0 0 80 80" class="w-full h-full -rotate-90">
                        <circle cx="40" cy="40" r="36" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="6"/>
                        <circle cx="40" cy="40" r="36" fill="none" stroke="${scoreColor}" stroke-width="6" stroke-dasharray="${circumference}" stroke-dashoffset="${offset}" stroke-linecap="round"/>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-[16px] font-bold text-zinc-100">${lead.score}</span>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-[16px] font-bold text-zinc-100 truncate">${escapeHtml(lead.name)}</h3>
                    <p class="text-[11px] text-zinc-400 mt-0.5">${lead.category || 'Sem categoria'} · ${lead.source || ''}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold ${lead.tier === 'hot' ? 'bg-amber-500/20 text-amber-400' : lead.tier === 'warm' ? 'bg-orange-500/20 text-orange-400' : 'bg-zinc-500/20 text-zinc-400'}">${{hot:'QUENTE',warm:'MORNO',cold:'FRIO'}[lead.tier] || 'FRIO'}</span>
                        ${lead.site_verified ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/20 text-emerald-400">Verificado</span>' : ''}
                    </div>
                </div>
            </div>
        </div>
        <div class="space-y-3 mb-5">
            ${emails.length ? `<div>
                <label class="text-[10px] text-zinc-500 font-medium uppercase tracking-wide mb-1 block">Email${emails.length > 1 ? 's' : ''}</label>
                ${emails.map(e => `<div class="flex items-center gap-2 mb-1"><span class="text-[12px] text-zinc-200 truncate flex-1">${escapeHtml(e)}</span><button onclick="navigator.clipboard.writeText('${e}');showToast('Copiado!','success')" class="p-1 rounded hover:bg-white/5 shrink-0"><svg class="w-3 h-3 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></button></div>`).join('')}
            </div>` : '<p class="text-[11px] text-zinc-600">Sem email</p>'}
            ${phones.length ? `<div>
                <label class="text-[10px] text-zinc-500 font-medium uppercase tracking-wide mb-1 block">Telefone${phones.length > 1 ? 's' : ''}</label>
                ${phones.map(p => `<div class="flex items-center gap-2 mb-1"><span class="text-[12px] text-zinc-200">${escapeHtml(p)}</span><button onclick="navigator.clipboard.writeText('${p}');showToast('Copiado!','success')" class="p-1 rounded hover:bg-white/5 shrink-0"><svg class="w-3 h-3 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></button></div>`).join('')}
            </div>` : ''}
            ${lead.website ? `<div>
                <label class="text-[10px] text-zinc-500 font-medium uppercase tracking-wide mb-1 block">Website</label>
                <a href="${lead.website}" target="_blank" rel="noopener" class="text-[12px] text-blue-400 hover:underline truncate block">${escapeHtml(lead.website)}</a>
            </div>` : ''}
            ${lead.instagram ? `<div class="text-[12px] text-zinc-300">Instagram: <a href="https://instagram.com/${lead.instagram}" target="_blank" class="text-pink-400 hover:underline">@${escapeHtml(lead.instagram)}</a></div>` : ''}
            ${lead.facebook ? `<div class="text-[12px] text-zinc-300">Facebook: <a href="${lead.facebook}" target="_blank" class="text-blue-400 hover:underline">${escapeHtml(lead.facebook)}</a></div>` : ''}
        </div>
        <div class="flex items-center justify-center gap-2 flex-wrap">
            ${wa ? `<a href="https://wa.me/${wa.replace(/\\D/g, '')}" target="_blank" class="btn-ghost !py-2.5 text-[11px] text-center !border-green-500/20 hover:!bg-green-500/10 flex-1"><span class="text-green-400">WhatsApp</span></a>` : ''}
            ${emails[0] ? `<a href="mailto:${emails[0]}" class="btn-ghost !py-2.5 text-[11px] text-center !border-blue-500/20 hover:!bg-blue-500/10 flex-1"><span class="text-blue-400">Email</span></a>` : ''}
            ${lead.website ? `<a href="${lead.website}" target="_blank" rel="noopener" class="btn-ghost !py-2.5 text-[11px] text-center flex-1"><span class="text-zinc-300">Website</span></a>` : ''}
            <a href="/leads" class="btn-ghost !py-2.5 text-[11px] text-center flex-1"><span class="text-zinc-300">Ver na lista</span></a>
        </div>
        <button onclick="deleteDashLead(${lead.id})" class="w-full mt-4 p-2.5 rounded-xl text-[11px] text-red-400/60 hover:text-red-400 hover:bg-red-500/5 border border-red-500/10 hover:border-red-500/20 transition-all flex items-center justify-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Excluir este lead
        </button>
    `;
    modal.classList.remove('hidden');
}

async function deleteDashLead(id) {
    if (!await confirmModal('Excluir este lead permanentemente?', 'Excluir', true)) return;
    try {
        await api(`/api/leads/${id}`, { method: 'DELETE' });
        showToast('Lead excluído', 'success');
        closeDashLeadDetail();
        loadStats();
        loadActivityFeed();
    } catch (e) {
        showToast(e.message, 'error');
    }
}

async function loadActivityFeed() {
    try {
        const data = await api('/api/dashboard/activity');
        const container = document.getElementById('activityFeed');
        if (!data.feed || data.feed.length === 0) {
            container.innerHTML = '<p class="text-[11px] text-zinc-600 text-center py-6">Nenhuma atividade ainda</p>';
            return;
        }

        container.innerHTML = data.feed.map(item => {
            const timeAgo = getTimeAgo(item.at);

            if (item.type === 'job') {
                const pct = item.total > 0 ? Math.round((item.found / item.total) * 100) : 0;
                return `<div class="flex items-center gap-2.5 p-2 rounded-lg bg-amber-500/[0.03] border border-amber-500/10" data-job-uuid="${item.uuid || ''}">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse shrink-0"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-[11px] text-zinc-200 truncate">Extraindo: <span class="text-amber-400 font-medium">${escapeHtml(item.keyword)}</span></p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <div class="flex-1 h-1 rounded-full bg-zinc-800 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-emerald-400 transition-all duration-700" style="width: ${pct}%"></div>
                            </div>
                            <span class="text-[9px] text-zinc-400 font-mono job-progress-text">${item.found}/${item.total}</span>
                        </div>
                    </div>
                    <span class="text-[9px] text-zinc-600 shrink-0">${timeAgo}</span>
                </div>`;
            }

            if (item.type === 'activity') {
                const actionLabel = (item.action || '').replace(/_/g, ' ');
                return `<div class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-white/[0.02] transition-colors">
                    <span class="w-2 h-2 rounded-full bg-cyan-400 shrink-0"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-[11px] text-zinc-300 truncate">${escapeHtml(actionLabel)}: <span class="text-zinc-100 font-medium">${escapeHtml(item.name)}</span></p>
                        ${item.details ? `<p class="text-[9px] text-zinc-500 truncate">${escapeHtml(item.details)}</p>` : ''}
                    </div>
                    <span class="text-[9px] text-zinc-600 shrink-0">${timeAgo}</span>
                </div>`;
            }

            const tierDot = { hot: 'bg-amber-400', warm: 'bg-orange-400', cold: 'bg-zinc-500' };
            return `<div class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-white/[0.02] transition-colors cursor-pointer" onclick="openDashLeadDetail(${item.id})">
                <span class="w-2 h-2 rounded-full ${tierDot[item.tier] || 'bg-emerald-400'} shrink-0"></span>
                <div class="flex-1 min-w-0">
                    <p class="text-[11px] text-zinc-300 truncate"><span class="text-zinc-100 font-medium">${escapeHtml(item.name)}</span></p>
                    <p class="text-[9px] text-zinc-500">${item.source || '—'}</p>
                </div>
                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold ${item.tier === 'hot' ? 'text-amber-400 bg-amber-500/10' : item.tier === 'warm' ? 'text-orange-400 bg-orange-500/10' : 'text-zinc-400 bg-zinc-500/10'}">${{hot:'QUENTE',warm:'MORNO',cold:'FRIO'}[item.tier] || ''}</span>
                <span class="text-[9px] text-zinc-600 shrink-0">${timeAgo}</span>
            </div>`;
        }).join('');
    } catch (e) {}
}

function getTimeAgo(dateStr) {
    if (!dateStr) return '';
    const now = new Date();
    const date = new Date(dateStr.replace(' ', 'T'));
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60) return 'agora';
    if (diff < 3600) return Math.floor(diff / 60) + 'min';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h';
    return Math.floor(diff / 86400) + 'd';
}

loadStats();
loadCharts();
loadRecentJobs();
loadSchedulerStatus();
loadActivityFeed();

// Debounced reloads to avoid redundant calls from multiple SSE events
let loadStatsTimeout;
let loadActivityTimeout;

function debouncedLoadStats() {
    clearTimeout(loadStatsTimeout);
    loadStatsTimeout = setTimeout(loadStats, 1000);
}

function debouncedLoadActivity() {
    clearTimeout(loadActivityTimeout);
    loadActivityTimeout = setTimeout(loadActivityFeed, 1500);
}

// SSE real-time updates (with polling fallback)
if (typeof events !== 'undefined') {
    events.on('stats_updated', (data) => {
        const fields = [
            { id: 'statTotal', key: 'total' },
            { id: 'statHot', key: 'hot' },
            { id: 'statWarm', key: 'warm' },
            { id: 'statCold', key: 'cold' },
        ];
        for (const { id, key } of fields) {
            const el = document.getElementById(id);
            if (el && data[key] !== undefined) {
                el.textContent = data[key].toLocaleString('pt-BR');
            }
        }
    });

    events.on('lead_new', () => {
        debouncedLoadStats();
        debouncedLoadActivity();
    });

    events.on('job_progress', (data) => {
        if (!data || !data.uuid) return;
        const el = document.querySelector(`[data-job-uuid="${data.uuid}"]`);
        if (el) {
            const pct = data.total > 0 ? Math.round((data.progress / data.total) * 100) : 0;
            const bar = el.querySelector('.h-1 > div');
            const txt = el.querySelector('.job-progress-text');
            if (bar) bar.style.width = `${pct}%`;
            if (txt) txt.textContent = `${data.progress}/${data.total}`;
        } else {
            debouncedLoadActivity();
        }
    });

    events.on('job_completed', (data) => {
        debouncedLoadStats();
        loadRecentJobs();
        debouncedLoadActivity();
        if (data && data.keyword) {
            const typeLabel = data.type === 'auto' ? 'Auto-Crawl: ' : data.type === 'campaign' ? 'Campanha: ' : '';
            showToast(`${typeLabel}Extração "${data.keyword}" finalizada: ${data.found} leads salvos`, 'success');
        }
    });

    events.on('job_error', (data) => {
        loadRecentJobs();
        debouncedLoadActivity();
        if (data && data.keyword) {
            showToast(`Erro na extração "${data.keyword}"`, 'error');
        }
    });

    events.on('campaign_progress', (data) => {
        debouncedLoadStats();
        debouncedLoadActivity();
        if (data && data.added > 0) {
            showToast(`Campanha "${data.name}": +${data.added} leads`, 'info');
        }
    });

    events.on('pipeline_verify', () => debouncedLoadStats());
    events.on('pipeline_enrich', () => debouncedLoadStats());
}

// Fallback polling (SSE handles real-time, these are safety nets)
setInterval(() => { loadStats(); loadRecentJobs(); loadCharts(); }, 60000);
setInterval(loadSchedulerStatus, 30000);
setInterval(loadActivityFeed, 15000);
