let leadsChart = null;
let tierChart = null;

async function loadStats() {
    try {
        const data = await api('/api/dashboard/stats');
        document.getElementById('statTotal').textContent = data.total.toLocaleString('pt-BR');
        document.getElementById('statHot').textContent = data.hot.toLocaleString('pt-BR');
        document.getElementById('statWarm').textContent = data.warm.toLocaleString('pt-BR');
        document.getElementById('statCold').textContent = data.cold.toLocaleString('pt-BR');
        document.getElementById('statEmail').textContent = data.with_email.toLocaleString('pt-BR');
        document.getElementById('statPhone').textContent = data.with_phone.toLocaleString('pt-BR');
        document.getElementById('statWhatsapp').textContent = data.with_whatsapp.toLocaleString('pt-BR');
        document.getElementById('statAvgScore').textContent = data.avg_score;

        document.getElementById('statRevenue').textContent = `R$ ${data.revenue.toLocaleString('pt-BR', {minimumFractionDigits: 0})}`;
        document.getElementById('statDeals').textContent = data.deals_closed.toLocaleString('pt-BR');
        document.getElementById('statConversion').textContent = `${data.conversion_rate}%`;
        document.getElementById('statNegotiating').textContent = data.negotiating.toLocaleString('pt-BR');
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
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
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
                        ticks: { color: '#64748b', font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.03)' },
                        ticks: { color: '#64748b', font: { size: 10 } }
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
                labels: ['Hot', 'Warm', 'Cold'],
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
    } catch (e) {
        console.error('Erro ao carregar charts:', e);
    }
}

async function loadRecentJobs() {
    try {
        const data = await api('/api/dashboard/recent-jobs');
        const tbody = document.getElementById('recentJobsTable');

        if (!data.jobs || data.jobs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-zinc-600 py-8">Nenhuma extração realizada ainda</td></tr>';
            return;
        }

        tbody.innerHTML = data.jobs.map(job => {
            const sourceLabel = job.source === 'both' ? 'Ambos' : job.source === 'maps' ? 'Maps' : 'Web';
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
                    <td><span class="text-xs px-2 py-0.5 rounded ${statusColor}">${job.status}</span></td>
                    <td class="text-zinc-500">${formatDate(job.started_at)}</td>
                </tr>
            `;
        }).join('');
    } catch (e) {
        console.error('Erro ao carregar jobs:', e);
    }
}

loadStats();
loadCharts();
loadRecentJobs();
