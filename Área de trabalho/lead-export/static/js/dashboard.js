let leadsChart = null;
let tierChart = null;
let statValues = {};
let isFirstLoad = true;
let tierModalPage = 1;
let tierModalCurrentTier = 'all';
let tierModalTotal = 0;

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

        document.getElementById('statAvgScore').textContent = data.avg_score;
        document.getElementById('statRevenue').textContent = `R$ ${data.revenue.toLocaleString('pt-BR', {minimumFractionDigits: 0})}`;
        document.getElementById('statDeals').textContent = data.deals_closed.toLocaleString('pt-BR');
        document.getElementById('statConversion').textContent = `${data.conversion_rate}%`;
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

        const ctxDoughnut = document.getElementById('tierChart').getContext('2d');
        if (tierChart) tierChart.destroy();

        const tiers = data.tiers || {};
        tierChart = new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: ['Quente', 'Morno', 'Frio'],
                datasets: [{
                    data: [tiers.hot || 0, tiers.warm || 0, tiers.cold || 0],
                    backgroundColor: ['#f59e0b', '#f97316', '#475569'],
                    borderWidth: 0,
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#94a3b8', font: { size: 11 }, padding: 16 }
                    }
                }
            }
        });

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

async function openTierModal(tier) {
    tierModalCurrentTier = tier;
    tierModalPage = 1;
    document.getElementById('tierModal').classList.remove('hidden');
    await loadTierModalPage();
}

async function loadTierModalPage() {
    const modal = document.getElementById('tierModal');
    const title = document.getElementById('tierModalTitle');
    const body = document.getElementById('tierModalBody');
    const footer = document.getElementById('tierModalFooter');

    const titles = { hot: 'Leads Quentes', warm: 'Leads Mornos', cold: 'Leads Frios', all: 'Todos os Leads' };
    title.textContent = titles[tierModalCurrentTier] || 'Leads';

    body.innerHTML = '<div class="flex justify-center py-8"><div class="w-6 h-6 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"></div></div>';

    try {
        const params = tierModalCurrentTier === 'all'
            ? `per_page=15&page=${tierModalPage}&sort=score&order=desc`
            : `tier=${tierModalCurrentTier}&per_page=15&page=${tierModalPage}&sort=score&order=desc`;
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
            const emailUrl = lead.email ? `mailto:${lead.email}` : '';

            return `
                <div class="flex items-center justify-between p-3 rounded-xl hover:bg-white/[0.03] transition-colors border border-transparent hover:border-white/5">
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-medium text-zinc-100 truncate">${escapeHtml(lead.name)}</p>
                        <p class="text-[11px] text-zinc-500 truncate mt-0.5">${lead.email || lead.phone || lead.website || '—'}</p>
                    </div>
                    <div class="flex items-center gap-2 ml-3">
                        <span class="text-[11px] font-bold ${lead.tier === 'hot' ? 'text-amber-400' : lead.tier === 'warm' ? 'text-orange-400' : 'text-zinc-500'}">${lead.score}</span>
                        ${whatsappUrl ? `<a href="${whatsappUrl}" target="_blank" class="p-1.5 rounded-lg hover:bg-green-500/10"><svg class="w-3.5 h-3.5 text-green-400" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg></a>` : ''}
                        ${emailUrl ? `<a href="${emailUrl}" class="p-1.5 rounded-lg hover:bg-blue-500/10"><svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></a>` : ''}
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

loadStats();
loadCharts();
loadRecentJobs();
loadSchedulerStatus();

setInterval(() => { loadStats(); loadRecentJobs(); loadCharts(); }, 30000);
setInterval(loadSchedulerStatus, 15000);
