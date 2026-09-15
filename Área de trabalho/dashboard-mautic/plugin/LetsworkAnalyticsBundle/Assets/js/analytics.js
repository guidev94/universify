/**
 * Letswork Analytics Dashboard — Premium interactions
 */

Mautic.lwaEsc = function(s) { return (s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'\\"').replace(/</g,'').replace(/>/g,'').replace(/\n/g,' '); };

Mautic.letsworkAnalyticsOnLoad = function(container) {
    Mautic.lwaRenderCharts();
    Mautic.lwaAnimateKpis();
    Mautic.lwaInitClickableCards();

    // Close panel on Escape
    mQuery(document).on('keydown.lwa', function(e) {
        if (e.key === 'Escape') Mautic.lwaCloseKpiDetail();
    });
};

/**
 * Make all KPI cards with data-kpi-id clickable (JS fallback for Twig cache issues)
 */
Mautic.lwaInitClickableCards = function() {
    mQuery('.lwa-kpi-card[data-kpi-id]').each(function() {
        var $card = mQuery(this);
        if (!$card.hasClass('lwa-kpi-clickable')) {
            $card.addClass('lwa-kpi-clickable');
            $card.attr('role', 'button');
            $card.css('cursor', 'pointer');
            var kpiId = $card.data('kpi-id');
            var title = $card.find('.lwa-kpi-title').text();
            $card.on('click', function() {
                Mautic.lwaShowKpiDetail(kpiId, title);
            });
            $card.append('<i class="ri-arrow-right-s-line lwa-kpi-arrow"></i>');
        }
    });

    // Also handle cards WITHOUT data-kpi-id but inside specific sections
    // Mapeia cards por título → kpiId contextual (Conversão, Pipeline, Perdas)
    // NÃO inclui cards da Overview (Prospecção, Frio, etc) pois já têm onclick próprio
    var kpiMappings = {
        'Tempo até Reunião': 'tempo_reuniao',
        'Tempo até Proposta': 'tempo_proposta',
        'Valor em Negociação': 'valor_negociacao',
        'Total de Leads': 'conv_total_leads',
        'Convertidos': 'conv_ganhos',
        'Taxa Geral': 'taxa_geral',
        'Leads Perdidos': 'leads_perdidos',
        'Motivos Registrados': 'motivos_perda',
        'Origens Afetadas': 'origens_afetadas',
        'Total Leads UTM': 'utm_leads',
        'Sources': 'utm_sources',
        'Campanhas UTM': 'utm_campaigns'
    };

    // kpiMappings só tem cards de Conversão/Pipeline/Perdas
    // Cards da Overview (Prospecção, Frio, etc) NÃO estão no map, então não são afetados

    mQuery('.lwa-kpi-card').each(function() {
        var $card = mQuery(this);
        var title = $card.find('.lwa-kpi-title').text().trim();
        var kpiId = kpiMappings[title];
        if (!kpiId) return; // Only handle mapped cards (conversao, pipeline, perdas)
        // Override any existing onclick with contextual one
        $card.off('click').removeAttr('onclick');
        $card.addClass('lwa-kpi-clickable');
        $card.attr('role', 'button');
        $card.attr('data-kpi-id', kpiId);
        $card.css('cursor', 'pointer');
        $card.on('click', function(e) {
            e.stopPropagation();
            Mautic.lwaShowKpiDetail(kpiId, title);
        });
        if (!$card.find('.lwa-kpi-arrow').length) {
            $card.append('<i class="ri-arrow-right-s-line lwa-kpi-arrow"></i>');
        }
    });
};

Mautic.letsworkAnalyticsOnUnload = function() {
    mQuery(document).off('keydown.lwa');
    if (window.lwaChartInstances) {
        window.lwaChartInstances.forEach(function(c) { if (c && c.destroy) c.destroy(); });
        window.lwaChartInstances = [];
    }
};

/**
 * Chart.js rendering
 */
Mautic.lwaRenderCharts = function() {
    window.lwaChartInstances = [];

    mQuery('.lwa-chart').each(function() {
        var $canvas = mQuery(this);
        var chartType = $canvas.data('chart-type') || 'line';
        var chartData = $canvas.data('chart-data');

        if (!chartData || !chartData.labels || chartData.labels.length === 0) {
            $canvas.parent().html('<div class="lwa-empty-state"><i class="ri-bar-chart-line"></i><p>Sem dados para exibir</p></div>');
            return;
        }

        // Sanitize: remove undefined/null from labels and data
        chartData.labels = chartData.labels.map(function(l) { return l || ''; });
        if (chartData.data) chartData.data = chartData.data.map(function(d) { return d || 0; });
        if (chartData.datasets) {
            chartData.datasets.forEach(function(ds) {
                if (ds.data) ds.data = ds.data.map(function(d) { return d || 0; });
            });
        }

        var ctx = this.getContext('2d');
        var config = Mautic.lwaGetChartConfig(chartType, chartData);
        var chart = new Chart(ctx, config);
        window.lwaChartInstances.push(chart);
    });
};

Mautic.lwaGetChartConfig = function(type, data) {
    var colors = data.colors || ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#dc2626', '#64748b'];

    var datasets;

    // If data already has datasets (multi-series like emails), use them directly
    if (data.datasets && data.datasets.length > 0) {
        datasets = data.datasets.map(function(ds, i) {
            ds.label = ds.label || '';
            ds.borderColor = ds.borderColor || colors[i % colors.length];
            ds.backgroundColor = ds.backgroundColor || (Mautic.lwaHexToRgba(colors[i % colors.length], 0.1));
            ds.borderWidth = ds.borderWidth || 2;
            ds.tension = ds.tension || 0.4;
            ds.fill = ds.fill !== undefined ? ds.fill : true;
            ds.pointRadius = ds.pointRadius || 3;
            ds.pointHoverRadius = ds.pointHoverRadius || 6;
            return ds;
        });
        var hasLabels = datasets.some(function(ds) { return ds.label && ds.label !== ''; });
        return {
            type: type === 'bar' ? 'bar' : 'line',
            data: { labels: data.labels, datasets: datasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: { legend: { display: hasLabels, position: 'top', labels: { usePointStyle: true, font: { size: 11 } } }, tooltip: { backgroundColor: 'rgba(15,23,42,0.92)', padding: { x: 14, y: 10 }, cornerRadius: 10 } },
                scales: { x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8', maxRotation: 45 } }, y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.03)' }, ticks: { font: { size: 10 }, color: '#94a3b8' } } },
                animation: { duration: 800, easing: 'easeOutQuart' }
            }
        };
    }

    if (type === 'doughnut' || type === 'pie') {
        datasets = [{
            data: data.data,
            backgroundColor: colors.slice(0, data.data.length),
            borderWidth: 3,
            borderColor: 'rgba(255,255,255,0.95)',
            hoverBorderWidth: 4,
            hoverOffset: 12,
            cutout: '70%',
            spacing: 2
        }];
    } else if (type === 'bar') {
        var barColors = data.data.map(function(_, i) { return colors[i % colors.length]; });
        datasets = [{
            data: data.data,
            backgroundColor: barColors.map(function(c) { return c + '90'; }),
            borderColor: barColors,
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
            maxBarThickness: 44,
            hoverBackgroundColor: barColors
        }];
    } else {
        datasets = [{
            data: data.data,
            borderColor: colors[0],
            backgroundColor: function(ctx) {
                var gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, ctx.chart.height);
                gradient.addColorStop(0, Mautic.lwaHexToRgba(colors[0], 0.15));
                gradient.addColorStop(1, Mautic.lwaHexToRgba(colors[0], 0.01));
                return gradient;
            },
            borderWidth: 2.5,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointHoverRadius: 7,
            pointBackgroundColor: '#fff',
            pointBorderColor: colors[0],
            pointBorderWidth: 2.5,
            pointHoverBorderWidth: 3,
            pointHoverBackgroundColor: colors[0]
        }];
    }

    return {
        type: type,
        data: { labels: data.labels, datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: {
                    display: (type === 'doughnut' || type === 'pie'),
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { size: 12, family: 'Source Sans 3', weight: '600' }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15,23,42,0.92)',
                    padding: { x: 14, y: 10 },
                    cornerRadius: 10,
                    titleFont: { size: 13, weight: '700' },
                    bodyFont: { size: 12 },
                    displayColors: true,
                    boxPadding: 4
                }
            },
            scales: (type === 'doughnut' || type === 'pie') ? {} : {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11, weight: '500' }, color: '#94a3b8', padding: 8 }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.03)', drawBorder: false },
                    ticks: { font: { size: 11, weight: '500' }, color: '#94a3b8', padding: 8 }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    };
};

/**
 * KPI Number Animation (count up)
 */
Mautic.lwaAnimateKpis = function() {
    mQuery('.lwa-kpi-value').each(function() {
        var $el = mQuery(this);
        var text = $el.text().trim();
        var target = parseFloat(text.replace(/[^0-9.,]/g, '').replace(',', '.'));
        if (isNaN(target) || target === 0) return;

        var isFloat = (target % 1 !== 0);
        var duration = 900;
        var startTime = null;

        function animate(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 4); // easeOutQuart
            var current = eased * target;

            if (isFloat) {
                $el.text(current.toFixed(1).replace('.', ','));
            } else {
                $el.text(Math.floor(current).toLocaleString('pt-BR'));
            }

            if (progress < 1) requestAnimationFrame(animate);
        }

        $el.text('0');
        requestAnimationFrame(animate);
    });
};

/**
 * KPI Detail Slide Panel
 */
Mautic.lwaKpiLeads = [];
Mautic.lwaKpiPage = 1;
Mautic.lwaKpiPerPage = 10;
Mautic.lwaCurrentKpi = '';

Mautic.lwaShowKpiDetail = function(kpiId, title) {
    var $panel = mQuery('#lwa-kpi-detail');
    Mautic.lwaCurrentKpi = kpiId;
    Mautic.lwaKpiPage = 1;
    mQuery('#lwa-kpi-detail-title').text(title);
    mQuery('#lwa-kpi-detail-body').html('<div style="padding:16px"><div class="lwa-skeleton lwa-skeleton-card"></div><div class="lwa-skeleton lwa-skeleton-line"></div><div class="lwa-skeleton lwa-skeleton-line lwa-skeleton-line--short"></div><div class="lwa-skeleton lwa-skeleton-card"></div></div>');
    mQuery('#lwa-kpi-pagination').html('');
    $panel.show();

    var dateFrom = mQuery('input[name="dateFrom"]').val() || '';
    var dateTo = mQuery('input[name="dateTo"]').val() || '';

    // Contexto do modal baseado no KPI
    var contextInfo = {
        'taxa_geral': {subtitle: 'Leads convertidos em clientes', icon: 'ri-trophy-line', highlight: 'status_comercial'},
        'conv_ganhos': {subtitle: 'Leads que fecharam negócio', icon: 'ri-trophy-line', highlight: 'status_comercial'},
        'tempo_reuniao': {subtitle: 'Leads que avançaram para reunião', icon: 'ri-calendar-check-line', highlight: 'status_comercial'},
        'tempo_proposta': {subtitle: 'Leads com proposta enviada ou em negociação', icon: 'ri-file-text-line', highlight: 'valor_potencial'},
        'valor_negociacao': {subtitle: 'Oportunidades ativas em negociação', icon: 'ri-money-dollar-circle-line', highlight: 'valor_potencial'},
        'leads_perdidos': {subtitle: 'Leads que não converteram', icon: 'ri-close-circle-line', highlight: 'motivo_perda'},
        'motivos_perda': {subtitle: 'Leads perdidos com motivo identificado', icon: 'ri-list-check', highlight: 'motivo_perda'},
        'origens_afetadas': {subtitle: 'Leads perdidos por canal de origem', icon: 'ri-global-line', highlight: 'origem_principal'}
    };

    mQuery.ajax({
        url: '/s/analytics/kpi-detail/' + kpiId,
        data: { dateFrom: dateFrom, dateTo: dateTo },
        dataType: 'json',
        success: function(response) {
            if (response.type === 'campaigns') {
                Mautic.lwaRenderCampaignsModal(response.data);
            } else if (response.type === 'site_hits' || response.type === 'utm_data') {
                Mautic.lwaRenderContextualModal(kpiId, response);
            } else if (response.leads && response.leads.length > 0) {
                Mautic.lwaKpiLeads = response.leads;
                Mautic.lwaKpiContext = contextInfo[kpiId] || null;
                // Renderizador contextual
                if (Mautic.lwaRenderContextualModal(kpiId, response.leads)) {
                    // rendered by contextual
                } else if (response.leads[0] && response.leads[0].email_aberto !== undefined) {
                    Mautic.lwaRenderEmailKpiPage();
                } else {
                    Mautic.lwaRenderKpiPage();
                }
            } else {
                // Try contextual renderer even when empty (for engajamento cards)
                if (Mautic.lwaRenderContextualModal(kpiId, [])) {
                    // rendered contextual empty state
                } else {
                    mQuery('#lwa-kpi-detail-body').html('<div class="lwa-empty-state"><i class="ri-inbox-line"></i><p>Nenhum dado encontrado neste periodo</p></div>');
                }
            }
        },
        error: function() {
            mQuery('#lwa-kpi-detail-body').html('<div class="lwa-empty-state"><p>Erro ao carregar dados</p></div>');
        }
    });
};

/**
 * AI Contextual Tips — smart suggestions per KPI
 */
Mautic.lwaGetKpiTip = function(kpiId, leadCount) {
    var tips = {
        'prospeccao': {icon: 'ri-sparkling-line', color: '#3b82f6', text: 'Leads sem interação perdem interesse rápido. Priorize contato em até 48h após entrada.'},
        'temp_frio': {icon: 'ri-lightbulb-line', color: '#3b82f6', text: 'Leads frios respondem melhor a conteúdo educativo. Evite abordagem comercial direta.'},
        'temp_morno': {icon: 'ri-sparkling-line', color: '#f59e0b', text: 'Leads mornos já demonstraram interesse. É hora de oferecer diagnóstico ou case do segmento.'},
        'temp_quente': {icon: 'ri-fire-line', color: '#dc2626', text: 'Leads quentes precisam de ação IMEDIATA. Cada dia sem contato reduz 5% a chance de conversão.'},
        'temp_prioritario': {icon: 'ri-vip-crown-line', color: '#9333ea', text: 'Prioritários são sua receita mais provável. Garanta contato pessoal (ligação/reunião) esta semana.'},
        'reunioes': {icon: 'ri-calendar-check-line', color: '#10b981', text: 'Após reunião, envie resumo em até 2h. Leads que recebem follow-up rápido convertem 3x mais.'},
        'propostas': {icon: 'ri-file-text-line', color: '#7c3aed', text: 'Propostas abertas por mais de 7 dias esfriam. Ligue para entender objeções e ajustar.'},
        'ganhos': {icon: 'ri-trophy-line', color: '#10b981', text: 'Analise o que esses clientes têm em comum (segmento, dor, origem) para replicar o padrão.'},
        'perdidos': {icon: 'ri-alert-line', color: '#dc2626', text: 'Identifique o motivo #1 de perda e crie objeção-resposta para o time. Reative após 60 dias.'},
        'hot_no_followup': {icon: 'ri-alarm-warning-line', color: '#dc2626', text: 'URGENTE: Esses leads estão quentes MAS sem nenhuma ação agendada. Dinheiro na mesa.'},
    };
    var tip = tips[kpiId];
    if (!tip) return '';
    return '<div style="display:flex;gap:10px;align-items:flex-start;padding:12px 16px;background:' + tip.color + '08;border-left:3px solid ' + tip.color + ';border-radius:0 8px 8px 0;margin-bottom:16px"><i class="' + tip.icon + '" style="color:' + tip.color + ';font-size:16px;margin-top:2px;flex-shrink:0"></i><div style="font-size:12px;line-height:1.5;color:var(--lwa-gray-700)"><strong style="color:' + tip.color + '">Dica IA:</strong> ' + tip.text + (leadCount ? ' <span style="color:var(--lwa-gray-400)">(' + leadCount + ' leads)</span>' : '') + '</div></div>';
};

/**
 * Contextual Modal Renderers — each KPI gets its own visualization
 */
Mautic.lwaRenderContextualModal = function(kpiId, leads) {
    var renderers = {
        'tempo_reuniao': Mautic.lwaRenderTempoReuniao,
        'reunioes': Mautic.lwaRenderTempoReuniao,
        'tempo_proposta': Mautic.lwaRenderTempoProposta,
        'propostas': Mautic.lwaRenderTempoProposta,
        'valor_negociacao': Mautic.lwaRenderValorNegociacao,
        'taxa_geral': Mautic.lwaRenderTaxaGeral,
        'conv_total_leads': Mautic.lwaRenderTotalLeads,
        'conv_ganhos': Mautic.lwaRenderConvertidos,
        'ganhos': Mautic.lwaRenderConvertidos,
        'motivos_perda': Mautic.lwaRenderMotivosPerda,
        'origens_afetadas': Mautic.lwaRenderOrigensAfetadas,
        'leads_perdidos': Mautic.lwaRenderLeadsPerdidos,
        'site_visitas': Mautic.lwaRenderSiteVisitas,
        'site_visitantes': Mautic.lwaRenderSiteVisitas,
        'site_precos': Mautic.lwaRenderSiteVisitas,
        'engaj_forms': Mautic.lwaRenderEngajamento,
        'engaj_downloads': Mautic.lwaRenderEngajamento,
        'engaj_campanhas': Mautic.lwaRenderEngajamento,
        'utm_leads': Mautic.lwaRenderUtm,
        'utm_sources': Mautic.lwaRenderUtmSources,
        'utm_campaigns': Mautic.lwaRenderUtmCampaigns
    };
    if (renderers[kpiId]) {
        renderers[kpiId](leads);
        return true;
    }
    return false;
};

Mautic.lwaRenderTotalLeads = function(leads) {
    var porTemp = {};
    var porOrigem = {};
    leads.forEach(function(l) {
        var t = (l.temperatura||'sem temp').toLowerCase();
        var o = (l.origem_principal||'outros').replace(/_/g,' ');
        porTemp[t] = (porTemp[t]||0) + 1;
        porOrigem[o] = (porOrigem[o]||0) + 1;
    });
    var tempColors = {'frio':'#3b82f6','morno':'#f59e0b','quente':'#dc2626','prioritario':'#9333ea'};

    var html = '<div style="padding:8px 0">';
    html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">';
    html += '<div style="text-align:center;padding:16px;background:var(--lwa-gray-50);border-radius:10px"><div style="font-size:28px;font-weight:700;color:var(--lwa-primary)">' + leads.length + '</div><div style="font-size:11px;color:var(--lwa-gray-500)">Total de leads no período</div></div>';
    html += '<div style="text-align:center;padding:16px;background:var(--lwa-gray-50);border-radius:10px"><div style="font-size:28px;font-weight:700;color:#7c3aed">' + Object.keys(porOrigem).length + '</div><div style="font-size:11px;color:var(--lwa-gray-500)">Origens distintas</div></div>';
    html += '</div>';
    html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">';
    html += '<div><div style="font-size:12px;font-weight:600;color:var(--lwa-gray-400);margin-bottom:8px">POR TEMPERATURA</div>';
    html += '<table class="lwa-table lwa-table--compact" style="margin:0"><tbody>';
    Object.keys(porTemp).sort().forEach(function(t){ html += '<tr><td><span style="color:'+(tempColors[t]||'#64748b')+';font-weight:600">'+t.charAt(0).toUpperCase()+t.slice(1)+'</span></td><td><strong>'+porTemp[t]+'</strong></td></tr>'; });
    html += '</tbody></table></div>';
    html += '<div><div style="font-size:12px;font-weight:600;color:var(--lwa-gray-400);margin-bottom:8px">POR ORIGEM</div>';
    html += '<table class="lwa-table lwa-table--compact" style="margin:0"><tbody>';
    Object.keys(porOrigem).sort(function(a,b){return porOrigem[b]-porOrigem[a];}).forEach(function(o){ html += '<tr><td>'+o+'</td><td><strong>'+porOrigem[o]+'</strong></td></tr>'; });
    html += '</tbody></table></div></div></div>';
    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderTempoReuniao = function(leads) {
    var total = leads.length;
    var totalValor = leads.reduce(function(s,l){return s+parseFloat(l.valor_potencial||0);},0);

    var html = '<div class="lwa-modal-metrics lwa-modal-metrics--3">';
    html += '<div class="lwa-metric-card lwa-metric-card--blue"><div class="lwa-metric-value" style="color:#3b82f6">' + total + '</div><div class="lwa-metric-label">Total Reuniões</div></div>';
    html += '<div class="lwa-metric-card lwa-metric-card--green"><div class="lwa-metric-value" style="color:#10b981">R$ ' + (totalValor/1000).toFixed(0) + 'k</div><div class="lwa-metric-label">Valor em pipeline</div></div>';
    html += '<div class="lwa-metric-card lwa-metric-card--purple"><div class="lwa-metric-value" style="color:#7c3aed">' + (total > 0 ? 'R$ ' + (totalValor/total/1000).toFixed(1) + 'k' : '0') + '</div><div class="lwa-metric-label">Ticket médio</div></div>';
    html += '</div>';
    html += '<div class="lwa-modal-section-title">Reuniões Agendadas / Realizadas</div>';
    html += '<table class="lwa-table" style="margin:0"><thead><tr><th>Lead</th><th>Empresa</th><th>Status</th><th>Valor</th><th>Última interação</th></tr></thead><tbody>';
    leads.forEach(function(l) {
        var name = ((l.firstname||'')+' '+(l.lastname||'')).trim() || 'Sem nome';
        var status = (l.status_comercial||'').replace(/_/g, ' ');
        var valor = l.valor_potencial ? 'R$ ' + parseFloat(l.valor_potencial).toLocaleString('pt-BR') : '-';
        var date = l.data_ultima_interacao ? new Date(l.data_ultima_interacao).toLocaleDateString('pt-BR') : '-';
        html += '<tr class="lwa-table-row-interactive" onclick="Mautic.lwaOpenLeadModal('+l.id+')">';
        html += '<td><strong>'+name+'</strong></td>';
        html += '<td>'+(l.company||'-')+'</td>';
        var tempClass = (l.temperatura||'').toLowerCase().replace('á','a');
        html += '<td><span class="lwa-badge lwa-badge--'+tempClass+'" style="font-size:11px">'+status+'</span></td>';
        html += '<td style="color:#10b981;font-weight:600">'+valor+'</td>';
        html += '<td>'+date+'</td></tr>';
    });
    html += '</tbody></table>';
    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderTempoProposta = function(leads) {
    var totalValor = leads.reduce(function(s,l){return s+parseFloat(l.valor_potencial||0);},0);

    var html = '<div class="lwa-modal-metrics lwa-modal-metrics--3">';
    html += '<div class="lwa-metric-card lwa-metric-card--purple"><div class="lwa-metric-value" style="color:#8b5cf6">' + leads.length + '</div><div class="lwa-metric-label">Propostas ativas</div></div>';
    html += '<div class="lwa-metric-card lwa-metric-card--green"><div class="lwa-metric-value" style="color:#10b981">R$ ' + (totalValor/1000).toFixed(0) + 'k</div><div class="lwa-metric-label">Valor total</div></div>';
    html += '<div class="lwa-metric-card"><div class="lwa-metric-value">R$ ' + (leads.length>0?(totalValor/leads.length/1000).toFixed(1):'0') + 'k</div><div class="lwa-metric-label">Ticket médio</div></div>';
    html += '</div>';
    html += '<div class="lwa-modal-section-title">Leads com Proposta / Negociação</div>';
    html += '<table class="lwa-table" style="margin:0"><thead><tr><th>Lead</th><th>Empresa</th><th>Módulo</th><th>Valor</th><th>Status</th></tr></thead><tbody>';
    leads.sort(function(a,b){return parseFloat(b.valor_potencial||0)-parseFloat(a.valor_potencial||0);});
    leads.forEach(function(l) {
        var name = ((l.firstname||'')+' '+(l.lastname||'')).trim()||l.company||'Sem nome';
        var valor = l.valor_potencial ? 'R$ '+parseFloat(l.valor_potencial).toLocaleString('pt-BR') : '-';
        html += '<tr class="lwa-table-row-interactive" onclick="Mautic.lwaOpenLeadModal('+l.id+')">';
        html += '<td><strong>'+name+'</strong></td><td>'+(l.company||'-')+'</td>';
        html += '<td style="font-size:12px">'+(l.modulo_interesse||'-')+'</td>';
        html += '<td style="color:#10b981;font-weight:600">'+valor+'</td>';
        html += '<td><span class="lwa-badge lwa-badge--'+(l.temperatura||'').toLowerCase().replace('á','a')+'" style="font-size:10px">'+(l.status_comercial||'').replace(/_/g,' ')+'</span></td></tr>';
    });
    html += '</tbody></table>';
    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderValorNegociacao = function(leads) {
    var totalValor = leads.reduce(function(s,l){return s+parseFloat(l.valor_potencial||0);},0);
    var avgValor = leads.length > 0 ? totalValor / leads.length : 0;
    var porSegmento = {};
    leads.forEach(function(l){ var s=l.segmento||'Outros'; porSegmento[s]=(porSegmento[s]||0)+parseFloat(l.valor_potencial||0); });
    leads.sort(function(a,b){return parseFloat(b.valor_potencial||0)-parseFloat(a.valor_potencial||0);});

    var html = '<div class="lwa-modal-metrics lwa-modal-metrics--3">';
    html += '<div class="lwa-metric-card lwa-metric-card--green"><div class="lwa-metric-value" style="color:#10b981">R$ ' + (totalValor/1000).toFixed(0) + 'k</div><div class="lwa-metric-label">Valor total pipeline</div></div>';
    html += '<div class="lwa-metric-card lwa-metric-card--purple"><div class="lwa-metric-value" style="color:#7c3aed">' + leads.length + '</div><div class="lwa-metric-label">Oportunidades</div></div>';
    html += '<div class="lwa-metric-card lwa-metric-card--blue"><div class="lwa-metric-value" style="color:#3b82f6">R$ ' + (avgValor/1000).toFixed(1) + 'k</div><div class="lwa-metric-label">Ticket médio</div></div>';
    html += '</div>';
    html += '<div class="lwa-modal-grid-2">';
    html += '<div class="lwa-modal-section-title">Oportunidades em Negociação</div>';
    html += '<table class="lwa-table" style="margin:0"><thead><tr><th>Lead</th><th>Empresa</th><th>Valor</th><th>Segmento</th></tr></thead><tbody>';
    leads.forEach(function(l) {
        var name = ((l.firstname||'')+' '+(l.lastname||'')).trim()||l.company||'Sem nome';
        html += '<tr class="lwa-table-row-interactive" onclick="Mautic.lwaOpenLeadModal('+l.id+')">';
        html += '<td><strong>'+name+'</strong></td><td>'+(l.company||'-')+'</td>';
        html += '<td style="color:#10b981;font-weight:700">R$ '+(parseFloat(l.valor_potencial||0)/1000).toFixed(0)+'k</td>';
        html += '<td>'+(l.segmento||'-')+'</td></tr>';
    });
    html += '</tbody></table>';
    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderConvertidos = function(leads) {
    var totalValor = leads.reduce(function(s,l){return s+parseFloat(l.valor_potencial||0);},0);
    var porOrigem = {};
    var porSegmento = {};
    leads.forEach(function(l){
        var o = (l.origem_principal||'outros').replace(/_/g,' ');
        var s = l.segmento || 'Outros';
        porOrigem[o] = (porOrigem[o]||0) + 1;
        porSegmento[s] = (porSegmento[s]||0) + 1;
    });

    var html = '<div class="lwa-modal-metrics lwa-modal-metrics--3">';
    html += '<div class="lwa-metric-card lwa-metric-card--green"><div class="lwa-metric-value" style="color:#10b981">' + leads.length + '</div><div class="lwa-metric-label">Clientes ganhos</div></div>';
    html += '<div class="lwa-metric-card lwa-metric-card--green"><div class="lwa-metric-value" style="color:#10b981">R$ ' + (totalValor/1000).toFixed(0) + 'k</div><div class="lwa-metric-label">Receita gerada</div></div>';
    html += '<div class="lwa-metric-card lwa-metric-card--blue"><div class="lwa-metric-value" style="color:#3b82f6">R$ ' + (leads.length>0?(totalValor/leads.length/1000).toFixed(1):'0') + 'k</div><div class="lwa-metric-label">Ticket médio</div></div>';
    html += '</div>';

    html += '<div class="lwa-modal-grid-2">';
    html += '<div><div class="lwa-modal-section-title">Conversão por Origem</div>';
    html += '<table class="lwa-table lwa-table--compact" style="margin:0"><tbody>';
    Object.keys(porOrigem).sort(function(a,b){return porOrigem[b]-porOrigem[a];}).forEach(function(o){
        var pct = Math.round((porOrigem[o]/leads.length)*100);
        html += '<tr><td><strong>'+o+'</strong></td><td>'+porOrigem[o]+'</td><td>'+pct+'%</td></tr>';
    });
    html += '</tbody></table></div>';
    html += '<div><div class="lwa-modal-section-title">Por Segmento</div>';
    html += '<table class="lwa-table lwa-table--compact" style="margin:0"><tbody>';
    Object.keys(porSegmento).sort(function(a,b){return porSegmento[b]-porSegmento[a];}).forEach(function(s){
        html += '<tr><td><strong>'+s+'</strong></td><td>'+porSegmento[s]+' clientes</td></tr>';
    });
    html += '</tbody></table></div></div>';

    html += '<div class="lwa-modal-section-title" style="margin-top:16px">Clientes Ganhos</div>';
    html += '<table class="lwa-table" style="margin:0"><thead><tr><th>Cliente</th><th>Empresa</th><th>Origem</th><th>Valor</th></tr></thead><tbody>';
    leads.sort(function(a,b){return parseFloat(b.valor_potencial||0)-parseFloat(a.valor_potencial||0);});
    leads.forEach(function(l){
        var name=((l.firstname||'')+' '+(l.lastname||'')).trim()||'Sem nome';
        html += '<tr class="lwa-table-row-interactive" onclick="Mautic.lwaOpenLeadModal('+l.id+')"><td><strong>'+name+'</strong></td><td>'+(l.company||'-')+'</td><td style="font-size:12px">'+(l.origem_principal||'').replace(/_/g,' ')+'</td><td style="color:#10b981;font-weight:700">R$ '+(parseFloat(l.valor_potencial||0)/1000).toFixed(0)+'k</td></tr>';
    });
    html += '</tbody></table>';
    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderTaxaGeral = function(leads) {
    var totalGanhos = leads.length;
    var totalValor = leads.reduce(function(s,l){return s+parseFloat(l.valor_potencial||0);},0);
    var porOrigem = {};
    var porSegmento = {};
    leads.forEach(function(l) {
        var o = (l.origem_principal||'outros').replace(/_/g,' ');
        var s = l.segmento || 'Outros';
        porOrigem[o] = (porOrigem[o]||0) + 1;
        porSegmento[s] = (porSegmento[s]||0) + 1;
    });

    var html = '<div style="padding:8px 0">';
    html += '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px">';
    html += '<div style="text-align:center;padding:16px;background:#f0fdf4;border-radius:10px"><div style="font-size:28px;font-weight:700;color:#10b981">' + totalGanhos + '</div><div style="font-size:11px;color:var(--lwa-gray-500)">Clientes ganhos</div></div>';
    html += '<div style="text-align:center;padding:16px;background:var(--lwa-gray-50);border-radius:10px"><div style="font-size:28px;font-weight:700;color:#10b981">R$ ' + (totalValor/1000).toFixed(0) + 'k</div><div style="font-size:11px;color:var(--lwa-gray-500)">Receita gerada</div></div>';
    html += '<div style="text-align:center;padding:16px;background:var(--lwa-gray-50);border-radius:10px"><div style="font-size:28px;font-weight:700;color:#3b82f6">R$ ' + (totalGanhos>0?(totalValor/totalGanhos/1000).toFixed(1):'0') + 'k</div><div style="font-size:11px;color:var(--lwa-gray-500)">Ticket médio</div></div>';
    html += '</div>';
    html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">';
    html += '<div><div style="font-size:12px;font-weight:600;color:var(--lwa-gray-400);margin-bottom:8px">CONVERSÃO POR ORIGEM</div>';
    html += '<table class="lwa-table lwa-table--compact" style="margin:0"><tbody>';
    Object.keys(porOrigem).sort(function(a,b){return porOrigem[b]-porOrigem[a];}).forEach(function(o){
        var pct = Math.round((porOrigem[o]/totalGanhos)*100);
        html += '<tr><td>'+o+'</td><td><strong>'+porOrigem[o]+'</strong></td><td>'+pct+'%</td></tr>';
    });
    html += '</tbody></table></div>';
    html += '<div><div style="font-size:12px;font-weight:600;color:var(--lwa-gray-400);margin-bottom:8px">CONVERSÃO POR SEGMENTO</div>';
    html += '<table class="lwa-table lwa-table--compact" style="margin:0"><tbody>';
    Object.keys(porSegmento).sort(function(a,b){return porSegmento[b]-porSegmento[a];}).forEach(function(s){
        var pct = Math.round((porSegmento[s]/totalGanhos)*100);
        html += '<tr><td>'+s+'</td><td><strong>'+porSegmento[s]+'</strong></td><td>'+pct+'%</td></tr>';
    });
    html += '</tbody></table></div></div></div>';
    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderMotivosPerda = function(leads) {
    // Agrupar por motivo
    var grouped = {};
    leads.forEach(function(l) {
        var motivo = l.motivo_perda || 'Não informado';
        if (!grouped[motivo]) grouped[motivo] = [];
        grouped[motivo].push(l);
    });
    var html = '<div class="lwa-kd-header-bar"><span class="lwa-kd-count"><i class="ri-list-check"></i> <strong>' + Object.keys(grouped).length + '</strong> motivos identificados em ' + leads.length + ' leads</span></div>';
    Object.keys(grouped).sort(function(a,b) { return grouped[b].length - grouped[a].length; }).forEach(function(motivo) {
        var items = grouped[motivo];
        html += '<div style="margin-bottom:16px">';
        html += '<div style="font-weight:700;font-size:13px;color:#dc2626;margin-bottom:6px;display:flex;align-items:center;gap:6px"><i class="ri-alert-line"></i> ' + motivo + ' <span style="background:#fef2f2;padding:2px 8px;border-radius:10px;font-size:11px">' + items.length + '</span></div>';
        html += '<table class="lwa-table lwa-table--compact" style="margin:0 0 4px"><tbody>';
        items.forEach(function(l) {
            var name = ((l.firstname||'') + ' ' + (l.lastname||'')).trim() || 'Sem nome';
            html += '<tr class="lwa-table-row-interactive" onclick="Mautic.lwaOpenLeadModal(' + l.id + ')"><td>' + name + '</td><td>' + (l.company||'-') + '</td><td>' + (l.origem_principal||'').replace(/_/g,' ') + '</td></tr>';
        });
        html += '</tbody></table></div>';
    });
    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderOrigensAfetadas = function(leads) {
    // Agrupar por origem
    var grouped = {};
    leads.forEach(function(l) {
        var origem = (l.origem_principal || 'Não informada').replace(/_/g, ' ');
        if (!grouped[origem]) grouped[origem] = [];
        grouped[origem].push(l);
    });
    var html = '<div class="lwa-kd-header-bar"><span class="lwa-kd-count"><i class="ri-global-line"></i> <strong>' + Object.keys(grouped).length + '</strong> origens afetadas com ' + leads.length + ' perdas</span></div>';
    Object.keys(grouped).sort(function(a,b) { return grouped[b].length - grouped[a].length; }).forEach(function(origem) {
        var items = grouped[origem];
        html += '<div style="margin-bottom:16px">';
        html += '<div style="font-weight:700;font-size:13px;color:#3b82f6;margin-bottom:6px;display:flex;align-items:center;gap:6px"><i class="ri-global-line"></i> ' + origem + ' <span style="background:#eff6ff;padding:2px 8px;border-radius:10px;font-size:11px">' + items.length + ' perdas</span></div>';
        html += '<table class="lwa-table lwa-table--compact" style="margin:0 0 4px"><tbody>';
        items.forEach(function(l) {
            var name = ((l.firstname||'') + ' ' + (l.lastname||'')).trim() || 'Sem nome';
            html += '<tr class="lwa-table-row-interactive" onclick="Mautic.lwaOpenLeadModal(' + l.id + ')"><td>' + name + '</td><td>' + (l.company||'-') + '</td><td style="color:#dc2626">' + (l.motivo_perda||'-') + '</td></tr>';
        });
        html += '</tbody></table></div>';
    });
    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderLeadsPerdidos = function(leads) {
    var totalValor = leads.reduce(function(s,l){return s+parseFloat(l.valor_potencial||0);},0);
    var porMotivo = {};
    leads.forEach(function(l){ var m=l.motivo_perda||'Não informado'; porMotivo[m]=(porMotivo[m]||0)+1; });

    var html = '<div class="lwa-modal-metrics lwa-modal-metrics--3">';
    html += '<div class="lwa-metric-card lwa-metric-card--red"><div class="lwa-metric-value" style="color:#dc2626">' + leads.length + '</div><div class="lwa-metric-label">Leads perdidos</div></div>';
    html += '<div class="lwa-metric-card"><div class="lwa-metric-value">R$ ' + (totalValor/1000).toFixed(0) + 'k</div><div class="lwa-metric-label">Valor perdido</div></div>';
    html += '<div class="lwa-metric-card"><div class="lwa-metric-value">' + Object.keys(porMotivo).length + '</div><div class="lwa-metric-label">Motivos distintos</div></div>';
    html += '</div>';

    html += '<div class="lwa-modal-section-title">Motivos de Perda</div>';
    html += '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px">';
    Object.keys(porMotivo).sort(function(a,b){return porMotivo[b]-porMotivo[a];}).forEach(function(m){
        html += '<span style="padding:5px 12px;border-radius:8px;font-size:12px;font-weight:600;background:#fef2f2;color:#dc2626;border:1px solid #fecaca">'+m+' ('+porMotivo[m]+')</span>';
    });
    html += '</div>';

    html += '<div class="lwa-modal-section-title">Leads Perdidos</div>';
    html += '<table class="lwa-table" style="margin:0"><thead><tr><th>Lead</th><th>Empresa</th><th>Motivo</th><th>Valor</th><th>Ação</th></tr></thead><tbody>';
    leads.forEach(function(l) {
        var name = ((l.firstname||'')+' '+(l.lastname||'')).trim()||'Sem nome';
        var valor = l.valor_potencial ? 'R$ '+(parseFloat(l.valor_potencial)/1000).toFixed(0)+'k' : '-';
        html += '<tr>';
        html += '<td style="cursor:pointer" onclick="Mautic.lwaOpenLeadModal('+l.id+')"><strong>'+name+'</strong></td>';
        html += '<td>'+(l.company||'-')+'</td>';
        html += '<td style="color:#dc2626;font-size:12px">'+(l.motivo_perda||'Não informado')+'</td>';
        html += '<td>'+valor+'</td>';
        html += '<td><button onclick="Mautic.lwaGeneratePlaybook('+l.id+',\'reativacao\')" style="padding:4px 10px;border-radius:6px;border:1px solid #bbf7d0;background:#dcfce7;color:#16a34a;font-size:11px;font-weight:600;cursor:pointer"><i class="ri-refresh-line"></i> Reativar</button></td>';
        html += '</tr>';
    });
    html += '</tbody></table>';
    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderUtm = function(data) {
    var leads = Array.isArray(data) ? data : (data.leads || []);
    if (!leads.length) {
        mQuery('#lwa-kpi-detail-body').html('<div class="lwa-empty-state"><i class="ri-link"></i><p>Nenhum lead com UTM no período</p></div>');
        mQuery('#lwa-kpi-pagination').html('');
        return;
    }
    var porSource = {};
    var porCampaign = {};
    var porTemp = {};
    leads.forEach(function(l) {
        var s = l.utm_source || 'direto';
        var c = l.utm_campaign || 'sem campanha';
        var t = (l.temperatura || 'sem temp').toLowerCase();
        porSource[s] = (porSource[s]||0) + 1;
        porCampaign[c] = (porCampaign[c]||0) + 1;
        porTemp[t] = (porTemp[t]||0) + 1;
    });

    var html = '<div class="lwa-modal-metrics lwa-modal-metrics--3">';
    html += '<div class="lwa-metric-card lwa-metric-card--blue"><div class="lwa-metric-value" style="color:#3b82f6">' + leads.length + '</div><div class="lwa-metric-label">Leads com UTM</div></div>';
    html += '<div class="lwa-metric-card lwa-metric-card--purple"><div class="lwa-metric-value" style="color:#7c3aed">' + Object.keys(porSource).length + '</div><div class="lwa-metric-label">Sources</div></div>';
    html += '<div class="lwa-metric-card"><div class="lwa-metric-value">' + Object.keys(porCampaign).length + '</div><div class="lwa-metric-label">Campaigns</div></div>';
    html += '</div>';

    var tempColors = {'frio':'#3b82f6','morno':'#f59e0b','quente':'#dc2626','prioritario':'#9333ea'};
    html += '<div class="lwa-modal-section-title">Distribuição por Temperatura</div>';
    html += '<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">';
    Object.keys(porTemp).forEach(function(t) {
        var color = tempColors[t] || '#64748b';
        html += '<span style="padding:4px 12px;border-radius:6px;font-size:12px;font-weight:600;background:' + color + '15;color:' + color + '">' + t.charAt(0).toUpperCase()+t.slice(1) + ': ' + porTemp[t] + '</span>';
    });
    html += '</div>';

    html += '<div class="lwa-modal-grid-2">';
    html += '<div><div class="lwa-modal-section-title">Por Source</div>';
    html += '<table class="lwa-table lwa-table--compact" style="margin:0"><tbody>';
    Object.keys(porSource).sort(function(a,b){return porSource[b]-porSource[a];}).forEach(function(s) {
        html += '<tr><td><strong>'+s+'</strong></td><td>'+porSource[s]+' leads</td></tr>';
    });
    html += '</tbody></table></div>';
    html += '<div><div class="lwa-modal-section-title">Por Campaign</div>';
    html += '<table class="lwa-table lwa-table--compact" style="margin:0"><tbody>';
    Object.keys(porCampaign).sort(function(a,b){return porCampaign[b]-porCampaign[a];}).slice(0,8).forEach(function(c) {
        html += '<tr><td><strong>'+c+'</strong></td><td>'+porCampaign[c]+' leads</td></tr>';
    });
    html += '</tbody></table></div></div>';

    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderUtmSources = function(data) {
    var leads = Array.isArray(data) ? data : (data.leads || []);
    if (!leads.length) { mQuery('#lwa-kpi-detail-body').html('<div class="lwa-empty-state"><i class="ri-global-line"></i><p>Nenhum lead com UTM Source no período</p></div>'); mQuery('#lwa-kpi-pagination').html(''); return; }
    var porSource = {};
    var porSourceTemp = {};
    leads.forEach(function(l) {
        var s = l.utm_source || 'direto';
        var t = (l.temperatura || 'sem').toLowerCase();
        porSource[s] = (porSource[s]||0) + 1;
        if (!porSourceTemp[s]) porSourceTemp[s] = {};
        porSourceTemp[s][t] = (porSourceTemp[s][t]||0) + 1;
    });
    var tempColors = {'frio':'#3b82f6','morno':'#f59e0b','quente':'#dc2626','prioritario':'#9333ea'};
    var html = '<div class="lwa-modal-metrics lwa-modal-metrics--2">';
    html += '<div class="lwa-metric-card lwa-metric-card--blue"><div class="lwa-metric-value" style="color:#3b82f6">' + Object.keys(porSource).length + '</div><div class="lwa-metric-label">Sources identificadas</div></div>';
    html += '<div class="lwa-metric-card"><div class="lwa-metric-value">' + leads.length + '</div><div class="lwa-metric-label">Total leads rastreados</div></div>';
    html += '</div>';
    html += '<div class="lwa-modal-section-title">Performance por Source</div>';
    html += '<table class="lwa-table" style="margin:0"><thead><tr><th>Source</th><th>Leads</th><th>Frio</th><th>Morno</th><th>Quente</th><th>Prioritário</th></tr></thead><tbody>';
    Object.keys(porSource).sort(function(a,b){return porSource[b]-porSource[a];}).forEach(function(s) {
        var temps = porSourceTemp[s] || {};
        html += '<tr><td><strong>'+s+'</strong></td><td>'+porSource[s]+'</td>';
        html += '<td style="color:#3b82f6">'+(temps['frio']||0)+'</td>';
        html += '<td style="color:#f59e0b">'+(temps['morno']||0)+'</td>';
        html += '<td style="color:#dc2626">'+(temps['quente']||0)+'</td>';
        html += '<td style="color:#9333ea">'+(temps['prioritario']||temps['prioritário']||0)+'</td></tr>';
    });
    html += '</tbody></table>';
    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderUtmCampaigns = function(data) {
    var leads = Array.isArray(data) ? data : (data.leads || []);
    if (!leads.length) { mQuery('#lwa-kpi-detail-body').html('<div class="lwa-empty-state"><i class="ri-megaphone-line"></i><p>Nenhum lead com UTM Campaign no período</p></div>'); mQuery('#lwa-kpi-pagination').html(''); return; }
    var porCampaign = {};
    var porCampTemp = {};
    leads.forEach(function(l) {
        var c = l.utm_campaign || 'sem campanha';
        var t = (l.temperatura || 'sem').toLowerCase();
        porCampaign[c] = (porCampaign[c]||0) + 1;
        if (!porCampTemp[c]) porCampTemp[c] = {};
        porCampTemp[c][t] = (porCampTemp[c][t]||0) + 1;
    });
    var html = '<div class="lwa-modal-metrics lwa-modal-metrics--2">';
    html += '<div class="lwa-metric-card lwa-metric-card--purple"><div class="lwa-metric-value" style="color:#7c3aed">' + Object.keys(porCampaign).length + '</div><div class="lwa-metric-label">Campanhas ativas</div></div>';
    html += '<div class="lwa-metric-card"><div class="lwa-metric-value">' + leads.length + '</div><div class="lwa-metric-label">Leads em campanhas</div></div>';
    html += '</div>';
    html += '<div class="lwa-modal-section-title">Campanhas × Temperatura (conversão)</div>';
    html += '<table class="lwa-table" style="margin:0"><thead><tr><th>Campaign</th><th>Leads</th><th>Frio</th><th>Morno</th><th>Quente</th><th>Prioritário</th></tr></thead><tbody>';
    Object.keys(porCampaign).sort(function(a,b){return porCampaign[b]-porCampaign[a];}).forEach(function(c) {
        var temps = porCampTemp[c] || {};
        html += '<tr><td><strong>'+c+'</strong></td><td>'+porCampaign[c]+'</td>';
        html += '<td style="color:#3b82f6">'+(temps['frio']||0)+'</td>';
        html += '<td style="color:#f59e0b">'+(temps['morno']||0)+'</td>';
        html += '<td style="color:#dc2626">'+(temps['quente']||0)+'</td>';
        html += '<td style="color:#9333ea">'+(temps['prioritario']||temps['prioritário']||0)+'</td></tr>';
    });
    html += '</tbody></table>';
    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderEngajamento = function(leads) {
    var kpi = Mautic.lwaCurrentKpi;
    var titles = {'engaj_forms':'Formulários Preenchidos','engaj_downloads':'Downloads de Materiais','engaj_campanhas':'Leads em Campanhas'};
    var icons = {'engaj_forms':'ri-file-edit-line','engaj_downloads':'ri-download-2-line','engaj_campanhas':'ri-megaphone-line'};
    var descs = {'engaj_forms':'Leads que preencheram formulários no período. Dados vêm da tabela form_submissions do Mautic.','engaj_downloads':'Leads que baixaram materiais (PDFs, guias, checklists). Dados vêm da tabela asset_downloads do Mautic.','engaj_campanhas':'Leads atualmente ativos em campanhas de automação do Mautic.'};
    var title = titles[kpi] || 'Engajamento';
    var icon = icons[kpi] || 'ri-bar-chart-line';
    var desc = descs[kpi] || '';

    if (!leads || leads.length === 0) {
        var html = '<div style="text-align:center;padding:40px 20px">';
        html += '<i class="'+icon+'" style="font-size:48px;color:var(--lwa-gray-300);margin-bottom:16px;display:block"></i>';
        html += '<div style="font-size:16px;font-weight:600;color:var(--lwa-gray-700);margin-bottom:8px">'+title+'</div>';
        html += '<div style="font-size:13px;color:var(--lwa-gray-400);max-width:320px;margin:0 auto;line-height:1.5">'+desc+'</div>';
        html += '<div style="margin-top:16px;padding:12px;background:var(--lwa-gray-50);border-radius:8px;font-size:12px;color:var(--lwa-gray-500)"><i class="ri-information-line"></i> Nenhum dado encontrado no período. Esses dados são preenchidos automaticamente pelo Mautic quando leads interagem com formulários, materiais ou campanhas.</div>';
        html += '</div>';
        mQuery('#lwa-kpi-detail-body').html(html);
    } else {
        Mautic.lwaKpiLeads = leads;
        Mautic.lwaRenderKpiPage();
    }
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderSiteVisitas = function(data) {
    // data pode ser array de leads OU {type:'site_hits', leads:[], top_pages:[]}
    var leads = Array.isArray(data) ? data : (data.leads || []);
    var topPages = Array.isArray(data) ? [] : (data.top_pages || []);
    var totalViews = leads.reduce(function(s,l){return s+parseInt(l.page_views||0);},0);

    var html = '<div class="lwa-modal-metrics lwa-modal-metrics--3">';
    html += '<div class="lwa-metric-card lwa-metric-card--blue"><div class="lwa-metric-value" style="color:#3b82f6">' + totalViews + '</div><div class="lwa-metric-label">Page views</div></div>';
    html += '<div class="lwa-metric-card"><div class="lwa-metric-value">' + leads.length + '</div><div class="lwa-metric-label">Visitantes identificados</div></div>';
    html += '<div class="lwa-metric-card lwa-metric-card--green"><div class="lwa-metric-value" style="color:#10b981">' + Math.round(totalViews/(leads.length||1)) + '</div><div class="lwa-metric-label">Média por lead</div></div>';
    html += '</div>';

    if (topPages.length > 0) {
        html += '<div class="lwa-modal-section-title">Páginas mais visitadas (origem)</div>';
        html += '<table class="lwa-table lwa-table--compact" style="margin:0"><thead><tr><th>Página</th><th>URL</th><th>Views</th></tr></thead><tbody>';
        topPages.forEach(function(p) {
            html += '<tr><td><strong>'+(p.url_title||'-')+'</strong></td><td style="font-size:11px;color:var(--lwa-gray-400)">'+( p.url||'')+'</td><td><strong>'+p.views+'</strong></td></tr>';
        });
        html += '</tbody></table>';
    }

    if (leads.length > 0) {
        html += '<div class="lwa-modal-section-title" style="margin-top:16px">Leads que visitaram</div>';
        html += '<table class="lwa-table lwa-table--compact" style="margin:0"><thead><tr><th>Lead</th><th>Empresa</th><th>Views</th><th>Páginas</th></tr></thead><tbody>';
        leads.slice(0, 12).forEach(function(l) {
            var name = ((l.firstname||'')+' '+(l.lastname||'')).trim()||l.company||'Anônimo';
            html += '<tr class="lwa-table-row-interactive" onclick="Mautic.lwaOpenLeadModal('+l.id+')"><td><strong>'+name+'</strong></td><td>'+(l.company||'-')+'</td><td><strong>'+l.page_views+'</strong></td><td style="font-size:11px;color:var(--lwa-gray-500)">'+(l.paginas_visitadas||'-')+'</td></tr>';
        });
        html += '</tbody></table>';
    }

    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderKpiPage = function() {
    var leads = Mautic.lwaKpiLeads;
    var page = Mautic.lwaKpiPage;
    var perPage = Mautic.lwaKpiPerPage;
    var totalPages = Math.ceil(leads.length / perPage);
    var start = (page - 1) * perPage;
    var pageLeads = leads.slice(start, start + perPage);
    var kpi = Mautic.lwaCurrentKpi || '';
    var isPerdidos = kpi.indexOf('perdid') > -1 || kpi === 'leads_perdidos';

    var tempColors = {'frio':'#3b82f6','morno':'#f59e0b','quente':'#dc2626','prioritario':'#9333ea','prioritário':'#9333ea'};

    var html = Mautic.lwaGetKpiTip(kpi, leads.length);
    html += '<div class="lwa-kd-header-bar"><span class="lwa-kd-count"><strong>' + leads.length + '</strong> leads</span><div style="display:flex;gap:8px;align-items:center"><button onclick="Mautic.lwaExportCSV(\'#lwa-kpi-detail-body .lwa-table\',\'leads\')" style="font-size:11px;padding:4px 10px;border-radius:6px;border:1px solid var(--lwa-gray-200);background:white;cursor:pointer;display:flex;align-items:center;gap:4px"><i class="ri-download-2-line"></i> CSV</button><span class="lwa-kd-page">Pág ' + page + '/' + totalPages + '</span></div></div>';
    html += '<div class="lwa-search"><i class="ri-search-line"></i><input type="text" placeholder="Buscar lead..." onkeyup="Mautic.lwaFilterLeads(this.value)"></div>';
    html += '<table class="lwa-table" style="margin:0"><thead><tr><th>Lead</th><th>Empresa</th><th>Temperatura</th><th>Score</th><th>Origem</th>';
    if (isPerdidos) html += '<th>Motivo</th>';
    html += '</tr></thead><tbody>';

    pageLeads.forEach(function(lead) {
        var name = ((lead.firstname||'')+' '+(lead.lastname||'')).trim() || lead.company || 'Sem nome';
        var temp = (lead.temperatura||'').toLowerCase().replace('á','a');
        var tempLabel = lead.temperatura || '-';
        var tColor = tempColors[temp] || '#64748b';
        var score = lead.interesse_score || 0;
        var origin = (lead.origem_principal||'').replace(/_/g,' ');

        html += '<tr class="lwa-table-row-interactive" onclick="Mautic.lwaOpenLeadModal('+lead.id+')">';
        html += '<td><div style="display:flex;align-items:center;gap:10px"><div style="width:36px;height:36px;border-radius:10px;background:'+tColor+';color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0">'+name.charAt(0).toUpperCase()+'</div><div><div style="font-size:15px;font-weight:700">'+name+'</div><div style="font-size:12px;color:var(--lwa-gray-400)">'+(lead.cargo||'')+'</div></div></div></td>';
        html += '<td style="font-size:14px">'+(lead.company||'-')+'</td>';
        html += '<td><span class="lwa-badge lwa-badge--'+temp+'" style="font-size:12px">'+tempLabel+'</span></td>';
        html += '<td style="font-weight:700;color:'+tColor+'">'+score+'</td>';
        html += '<td style="font-size:13px">'+origin+'</td>';
        if (isPerdidos) html += '<td style="color:#dc2626;font-size:13px">'+(lead.motivo_perda||'-')+'</td>';
        html += '</tr>';
    });

    html += '</tbody></table>';
    mQuery('#lwa-kpi-detail-body').html(html);

    if (totalPages > 1) {
        var pag = '<button class="lwa-pagination-btn lwa-pagination-btn--nav" ' + (page <= 1 ? 'disabled' : 'onclick="Mautic.lwaKpiGoPage(' + (page-1) + ')"') + '><i class="ri-arrow-left-s-line"></i></button>';
        var startP = Math.max(1, page - 2);
        var endP = Math.min(totalPages, startP + 4);
        for (var i = startP; i <= endP; i++) {
            pag += '<button class="lwa-pagination-btn ' + (i === page ? 'active' : '') + '" onclick="Mautic.lwaKpiGoPage(' + i + ')">' + i + '</button>';
        }
        pag += '<button class="lwa-pagination-btn lwa-pagination-btn--nav" ' + (page >= totalPages ? 'disabled' : 'onclick="Mautic.lwaKpiGoPage(' + (page+1) + ')"') + '><i class="ri-arrow-right-s-line"></i></button>';
        mQuery('#lwa-kpi-pagination').html(pag);
    } else {
        mQuery('#lwa-kpi-pagination').html('');
    }
};

Mautic.lwaKpiGoPage = function(page) {
    Mautic.lwaKpiPage = page;
    Mautic.lwaRenderKpiPage();
    mQuery('#lwa-kpi-detail-body').scrollTop(0);
};

Mautic.lwaRenderCampaignsModal = function(campaigns) {
    if (!campaigns || campaigns.length === 0) {
        mQuery('#lwa-kpi-detail-body').html('<div class="lwa-empty-state"><i class="ri-mail-line"></i><p>Nenhuma campanha encontrada</p></div>');
        return;
    }

    var totalSent = 0, totalRead = 0;
    campaigns.forEach(function(c) { totalSent += parseInt(c.sent_count || 0); totalRead += parseInt(c.read_count || 0); });
    var avgRate = totalSent > 0 ? Math.round((totalRead / totalSent) * 100) : 0;

    var html = '<div class="lwa-kd-header-bar">';
    html += '  <span class="lwa-kd-count"><strong>' + campaigns.length + '</strong> campanhas de e-mail</span>';
    html += '  <span class="lwa-kd-page">' + totalSent + ' envios · ' + totalRead + ' aberturas · ' + avgRate + '% taxa</span>';
    html += '</div>';

    html += '<div class="lwa-campaigns-list">';
    campaigns.forEach(function(c, idx) {
        var rate = c.sent_count > 0 ? Math.round((c.read_count / c.sent_count) * 100) : 0;
        var date = c.date_added ? new Date(c.date_added).toLocaleDateString('pt-BR') : '-';
        var rateColor = rate >= 50 ? '#10b981' : (rate >= 30 ? '#f59e0b' : '#dc2626');

        html += '<div class="lwa-campaign-card" style="animation-delay:' + (idx * 0.05) + 's">';
        html += '  <div class="lwa-campaign-icon"><i class="ri-mail-send-line"></i></div>';
        html += '  <div class="lwa-campaign-info">';
        html += '    <div class="lwa-campaign-name">' + (c.name || 'Sem nome') + '</div>';
        html += '    <div class="lwa-campaign-subject">' + (c.subject || '') + '</div>';
        html += '    <div class="lwa-campaign-meta">';
        html += '      <span><i class="ri-send-plane-line"></i> ' + c.sent_count + ' enviados</span>';
        html += '      <span><i class="ri-mail-open-line"></i> ' + c.read_count + ' abertos</span>';
        html += '      <span><i class="ri-calendar-line"></i> ' + date + '</span>';
        html += '    </div>';
        html += '  </div>';
        html += '  <div class="lwa-campaign-rate" style="color:' + rateColor + '">';
        html += '    <span class="lwa-campaign-rate-value">' + rate + '%</span>';
        html += '    <span class="lwa-campaign-rate-label">abertura</span>';
        html += '  </div>';
        html += '</div>';
    });
    html += '</div>';

    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderEmailKpiPage = function() {
    var leads = Mautic.lwaKpiLeads;
    var page = Mautic.lwaKpiPage;
    var perPage = Mautic.lwaKpiPerPage;
    var totalPages = Math.ceil(leads.length / perPage);
    var start = (page - 1) * perPage;
    var pageLeads = leads.slice(start, start + perPage);

    var opened = leads.filter(function(l) { return l.email_aberto == 1; }).length;
    var rate = leads.length > 0 ? Math.round((opened / leads.length) * 100) : 0;

    var html = '<div class="lwa-kd-header-bar">';
    html += '  <span class="lwa-kd-count"><strong>' + leads.length + '</strong> e-mails disparados</span>';
    html += '  <span class="lwa-kd-page">' + opened + ' abertos (' + rate + '%)</span>';
    html += '</div>';

    html += '<table class="lwa-table" style="margin:0">';
    html += '<thead><tr><th>Lead</th><th>E-mail</th><th>Enviado em</th><th>Status</th></tr></thead>';
    html += '<tbody>';

    pageLeads.forEach(function(lead) {
        var name = ((lead.firstname || '') + ' ' + (lead.lastname || '')).trim() || lead.company || 'Sem nome';
        var date = lead.date_sent ? new Date(lead.date_sent).toLocaleDateString('pt-BR') : '-';
        var statusHtml = lead.email_aberto == 1
            ? '<span class="lwa-badge lwa-badge--morno" style="font-size:11px"><i class="ri-mail-open-line"></i> Aberto</span>'
            : '<span class="lwa-badge lwa-badge--frio" style="font-size:11px"><i class="ri-mail-line"></i> Enviado</span>';

        html += '<tr class="lwa-table-row-interactive" onclick="Mautic.lwaOpenLeadModal(' + lead.id + ')">';
        html += '  <td><strong>' + name + '</strong><br><small style="color:var(--text-secondary)">' + (lead.company || '') + '</small></td>';
        html += '  <td style="font-size:12px">' + (lead.email || '-') + '</td>';
        html += '  <td>' + date + '</td>';
        html += '  <td>' + statusHtml + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table>';
    mQuery('#lwa-kpi-detail-body').html(html);

    if (totalPages > 1) {
        var pag = '<button class="lwa-pagination-btn lwa-pagination-btn--nav" ' + (page <= 1 ? 'disabled' : 'onclick="Mautic.lwaKpiPage=' + (page-1) + ';Mautic.lwaRenderEmailKpiPage()"') + '><i class="ri-arrow-left-s-line"></i></button>';
        var startP = Math.max(1, page - 2);
        var endP = Math.min(totalPages, startP + 4);
        for (var i = startP; i <= endP; i++) {
            pag += '<button class="lwa-pagination-btn ' + (i === page ? 'active' : '') + '" onclick="Mautic.lwaKpiPage=' + i + ';Mautic.lwaRenderEmailKpiPage()">' + i + '</button>';
        }
        pag += '<button class="lwa-pagination-btn lwa-pagination-btn--nav" ' + (page >= totalPages ? 'disabled' : 'onclick="Mautic.lwaKpiPage=' + (page+1) + ';Mautic.lwaRenderEmailKpiPage()"') + '><i class="ri-arrow-right-s-line"></i></button>';
        mQuery('#lwa-kpi-pagination').html(pag);
    } else {
        mQuery('#lwa-kpi-pagination').html('');
    }
};

Mautic.lwaCloseKpiDetail = function() {
    mQuery('#lwa-kpi-detail').hide();
};

Mautic.lwaAiTab = function(tab, containerId) {
    var $container = mQuery('#' + (containerId || 'lwa-ai-tab-result'));
    $container.html('<div style="text-align:center;padding:20px;color:var(--lwa-gray-400)"><div class="lwa-spinner" style="margin:0 auto 8px"></div>Analisando com IA...</div>');
    $container.show();

    mQuery.ajax({
        url: '/s/analytics/ai/tab/' + tab,
        dataType: 'json',
        success: function(res) {
            var a = res.analysis || {};
            var html = '<div class="lwa-ai-tab-card">';
            html += '<div class="lwa-ai-tab-header"><i class="ri-sparkling-line"></i> ' + (a.titulo || 'Análise IA') + '</div>';

            if (a.insights && a.insights.length) {
                html += '<ul class="lwa-ai-tab-list">';
                a.insights.forEach(function(i) { html += '<li>' + i + '</li>'; });
                html += '</ul>';
            }

            if (a.acao_principal) html += '<div class="lwa-ai-tab-action"><strong>Ação principal:</strong> ' + a.acao_principal + '</div>';
            if (a.priorizacao) html += '<div class="lwa-ai-tab-action"><strong>Priorizar:</strong> ' + a.priorizacao + '</div>';
            if (a.melhor_segmento) html += '<div class="lwa-ai-tab-action"><strong>Melhor segmento:</strong> ' + a.melhor_segmento + ' — ' + (a.por_que || '') + '</div>';
            if (a.estrategia_reativacao) html += '<div class="lwa-ai-tab-action"><strong>Reativação:</strong> ' + a.estrategia_reativacao + '</div>';
            if (a.taxa_analise) html += '<div class="lwa-ai-tab-action"><strong>Taxa:</strong> ' + a.taxa_analise + '</div>';

            if (a.email_sugerido) html += '<div class="lwa-ai-tab-email"><i class="ri-mail-line"></i> Assunto sugerido: <em>"' + a.email_sugerido + '"</em></div>';
            if (a.email_reativacao) html += '<div class="lwa-ai-tab-email"><i class="ri-mail-line"></i> Email reativação: <em>"' + a.email_reativacao + '"</em></div>';
            if (a.mensagem_followup) html += '<div class="lwa-ai-tab-email"><i class="ri-chat-1-line"></i> Follow-up: <em>"' + a.mensagem_followup + '"</em></div>';
            if (a.campanha_sugerida) html += '<div class="lwa-ai-tab-email"><i class="ri-megaphone-line"></i> Campanha: <em>"' + a.campanha_sugerida + '"</em></div>';

            if (a.campanhas && a.campanhas.length) {
                html += '<div class="lwa-ai-tab-campaigns">';
                a.campanhas.forEach(function(c) {
                    html += '<div class="lwa-ai-tab-campaign"><strong>' + c.assunto + '</strong><br><small>' + c.objetivo + ' — ' + c.publico + '</small></div>';
                });
                html += '</div>';
            }

            html += '</div>';
            $container.html(html);
        },
        error: function() { $container.html('<p style="text-align:center;color:#dc2626;padding:16px">Erro ao consultar IA.</p>'); }
    });
};

Mautic.lwaLoadInsights = function() {
    var $container = mQuery('#lwa-insights .lwa-card-body');
    $container.html('<div style="text-align:center;padding:24px;color:var(--lwa-gray-400)"><div class="lwa-spinner" style="margin:0 auto 10px"></div>Analisando métricas com IA...</div>');

    var dateFrom = mQuery('input[name="dateFrom"]').val() || '';
    var dateTo = mQuery('input[name="dateTo"]').val() || '';

    mQuery.ajax({
        url: '/s/analytics/ai/insights',
        data: { dateFrom: dateFrom, dateTo: dateTo },
        dataType: 'json',
        success: function(res) {
            var insights = res.insights || [];
            if (!insights.length) { $container.html('<p style="text-align:center;color:var(--lwa-gray-400)">Sem insights disponíveis.</p>'); return; }

            var icons = { alerta: 'ri-error-warning-line', oportunidade: 'ri-lightbulb-line', melhoria: 'ri-arrow-up-circle-line', sucesso: 'ri-checkbox-circle-line' };
            var colors = { alerta: '#dc2626', oportunidade: '#f59e0b', melhoria: '#3b82f6', sucesso: '#10b981' };

            var html = '<div class="lwa-insights-grid">';
            insights.forEach(function(i) {
                if (typeof i === 'string') { i = {tipo:'melhoria', titulo:'Insight', descricao: i}; }
                if (!i || typeof i !== 'object') return;
                var titulo = typeof i.titulo === 'string' ? i.titulo : JSON.stringify(i.titulo || '');
                var descricao = typeof i.descricao === 'string' ? i.descricao : JSON.stringify(i.descricao || '');
                var icon = icons[i.tipo] || 'ri-information-line';
                var color = colors[i.tipo] || '#64748b';
                html += '<div class="lwa-insight-card" data-tipo="' + (i.tipo || '') + '">';
                html += '  <div class="lwa-insight-icon" style="color:' + color + '"><i class="' + icon + '"></i></div>';
                html += '  <div class="lwa-insight-content">';
                html += '    <div class="lwa-insight-title">' + titulo + '</div>';
                html += '    <div class="lwa-insight-desc">' + descricao + '</div>';
                html += '  </div>';
                html += '</div>';
            });
            html += '</div>';
            $container.html(html);
        },
        error: function() { $container.html('<p style="text-align:center;color:#dc2626">Erro ao consultar IA.</p>'); }
    });
};

Mautic.lwaShowSegmentoLeads = function(segmento) {
    var $panel = mQuery('#lwa-kpi-detail');
    Mautic.lwaKpiPage = 1;
    mQuery('#lwa-kpi-detail-title').text('Segmento — ' + segmento);
    mQuery('#lwa-kpi-detail-body').html('<div class="lwa-panel-loading"><div class="lwa-spinner"></div><span>Carregando leads...</span></div>');
    mQuery('#lwa-kpi-pagination').html('');
    $panel.show();

    var dateFrom = mQuery('input[name="dateFrom"]').val() || '';
    var dateTo = mQuery('input[name="dateTo"]').val() || '';

    mQuery.ajax({
        url: '/s/analytics/kpi-detail/segmento_' + encodeURIComponent(segmento),
        data: { dateFrom: dateFrom, dateTo: dateTo },
        dataType: 'json',
        success: function(response) {
            if (response.leads && response.leads.length > 0) {
                Mautic.lwaKpiLeads = response.leads;
                Mautic.lwaRenderKpiPage();
            } else {
                mQuery('#lwa-kpi-detail-body').html('<div class="lwa-empty-state"><i class="ri-inbox-line"></i><p>Nenhum lead encontrado neste segmento</p></div>');
            }
        },
        error: function() {
            mQuery('#lwa-kpi-detail-body').html('<div class="lwa-empty-state"><p>Erro ao carregar dados</p></div>');
        }
    });
};

Mautic.lwaShowModuloLeads = function(modulo) {
    var $panel = mQuery('#lwa-kpi-detail');
    Mautic.lwaKpiPage = 1;
    mQuery('#lwa-kpi-detail-title').text('Leads — ' + modulo);
    mQuery('#lwa-kpi-detail-body').html('<div class="lwa-panel-loading"><div class="lwa-spinner"></div><span>Carregando...</span></div>');
    mQuery('#lwa-kpi-pagination').html('');
    $panel.show();

    mQuery.ajax({
        url: '/s/analytics/kpi-detail/modulo',
        data: { modulo: modulo },
        dataType: 'json',
        success: function(response) {
            if (response.leads && response.leads.length > 0) {
                Mautic.lwaKpiLeads = response.leads;
                Mautic.lwaRenderKpiPage();
            } else {
                mQuery('#lwa-kpi-detail-body').html('<div class="lwa-empty-state"><i class="ri-inbox-line"></i><p>Nenhum lead encontrado</p></div>');
            }
        }
    });
};

/**
 * Lead Modal — Full detail + Actions
 */
Mautic.lwaOpenLeadModal = function(leadId) {
    mQuery('#lwa-kpi-detail').hide();
    var $modal = mQuery('#lwa-lead-modal');
    mQuery('#lwa-modal-title').text('Carregando...');
    mQuery('#lwa-modal-body').html('<div class="lwa-modal-loading"><i class="ri-loader-3-line ri-spin"></i></div>');
    $modal.show();

    mQuery.ajax({
        url: '/s/analytics/lead/' + leadId,
        dataType: 'json',
        success: function(res) {
            var l = res.lead;
            var name = ((l.firstname || '') + ' ' + (l.lastname || '')).trim() || l.company || 'Lead';
            mQuery('#lwa-modal-title').text(name);

            var temp = (l.temperatura || '').toLowerCase().replace('á','a');
            var tempColors = {'frio':'#3b82f6','morno':'#f59e0b','quente':'#dc2626','prioritario':'#9333ea'};
            var tempColor = tempColors[temp] || '#64748b';
            var score = parseInt(l.interesse_score) || 0;
            var scorePct = Math.min(100, Math.round((score / 200) * 100));

            var daysInFunnel = l.date_added ? Math.floor((Date.now() - new Date(l.date_added).getTime()) / 86400000) : 0;
            var icpScore = parseInt(l.icp_score) || 0;

            var html = '<div style="padding:4px 0">';

            // === HEADER ===
            html += '<div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--lwa-gray-100)">';
            html += '  <div style="width:56px;height:56px;border-radius:14px;background:'+tempColor+';color:white;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;flex-shrink:0">'+name.charAt(0).toUpperCase()+'</div>';
            html += '  <div style="flex:1">';
            html += '    <div style="font-size:20px;font-weight:700;color:var(--lwa-gray-900)">'+name+'</div>';
            html += '    <div style="font-size:14px;color:var(--lwa-gray-500);margin-top:2px">'+(l.cargo||'')+(l.cargo&&l.company?' · ':'')+(l.company||'')+'</div>';
            if (l.email) html += '    <div style="font-size:13px;color:var(--lwa-gray-400);margin-top:4px"><i class="ri-mail-line" style="margin-right:4px"></i>'+l.email+'</div>';
            html += '  </div>';
            html += '  <span class="lwa-badge lwa-badge--'+temp+'" style="font-size:13px;padding:5px 12px">'+(l.temperatura||'—')+'</span>';
            html += '</div>';

            // === META BADGES ===
            html += '<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px">';
            if (l.segmento) html += '<span style="font-size:11px;padding:3px 8px;border-radius:6px;background:#ede9fe;color:#7c3aed;font-weight:600">' + l.segmento + '</span>';
            if (icpScore > 0) html += '<span style="font-size:11px;padding:3px 8px;border-radius:6px;background:' + (icpScore >= 70 ? '#dcfce7;color:#16a34a' : '#fef3c7;color:#d97706') + ';font-weight:600">ICP: ' + icpScore + '%</span>';
            if (daysInFunnel > 0) html += '<span style="font-size:11px;padding:3px 8px;border-radius:6px;background:var(--lwa-gray-50);color:var(--lwa-gray-500);font-weight:600">Há ' + daysInFunnel + ' dias no funil</span>';
            if (l.responsavel_comercial) html += '<span style="font-size:11px;padding:3px 8px;border-radius:6px;background:#f0f9ff;color:#0369a1;font-weight:600"><i class="ri-user-line"></i> ' + l.responsavel_comercial + '</span>';
            if (l.motivo_perda && (l.status_comercial||'').toLowerCase() === 'perdido') html += '<span style="font-size:11px;padding:3px 8px;border-radius:6px;background:#fef2f2;color:#dc2626;font-weight:600">Perda: ' + l.motivo_perda + '</span>';
            html += '</div>';

            // === SCORE BAR ===
            html += '<div style="margin-bottom:20px">';
            html += '  <div style="display:flex;justify-content:space-between;margin-bottom:6px"><span style="font-size:13px;font-weight:600;color:var(--lwa-gray-600)">Score de Interesse</span><span style="font-size:16px;font-weight:700;color:'+tempColor+'">'+score+' pts</span></div>';
            html += '  <div style="background:var(--lwa-gray-100);border-radius:8px;height:10px;overflow:hidden"><div style="background:'+tempColor+';height:100%;width:'+scorePct+'%;border-radius:8px"></div></div>';
            html += '</div>';

            // === INFO CARDS ===
            html += '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:20px">';
            html += '<div style="padding:12px;background:var(--lwa-gray-50);border-radius:10px"><div style="font-size:10px;font-weight:600;color:var(--lwa-gray-400);text-transform:uppercase">Origem</div><div style="font-size:14px;font-weight:600;margin-top:4px">'+(l.origem_principal||'-').replace(/_/g,' ')+'</div></div>';
            html += '<div style="padding:12px;background:var(--lwa-gray-50);border-radius:10px"><div style="font-size:10px;font-weight:600;color:var(--lwa-gray-400);text-transform:uppercase">Módulo</div><div style="font-size:14px;font-weight:600;margin-top:4px">'+(l.modulo_interesse||'-')+'</div></div>';
            html += '<div style="padding:12px;background:var(--lwa-gray-50);border-radius:10px"><div style="font-size:10px;font-weight:600;color:var(--lwa-gray-400);text-transform:uppercase">Valor</div><div style="font-size:14px;font-weight:600;margin-top:4px;color:#10b981">'+(l.valor_potencial?'R$ '+parseFloat(l.valor_potencial).toLocaleString('pt-BR'):'-')+'</div></div>';
            html += '</div>';

            if (l.dor_principal) {
                html += '<div style="padding:12px;background:#fef2f2;border-radius:10px;margin-bottom:16px;border-left:3px solid #dc2626"><div style="font-size:10px;font-weight:600;color:#dc2626;text-transform:uppercase;margin-bottom:4px">Dor Principal</div><div style="font-size:14px;color:var(--lwa-gray-700)">'+l.dor_principal+'</div></div>';
            }

            // === AÇÕES RÁPIDAS ===
            html += '<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--lwa-gray-100)">';
            if (l.phone) html += '<button onclick="Mautic.lwaWhatsAppModal('+l.id+',\''+(l.phone||'').replace(/[^0-9]/g,'').replace(/'/g,"\\'")+'\',\''+Mautic.lwaEsc(name)+'\')" style="display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:8px;background:#dcfce7;color:#16a34a;font-size:13px;font-weight:600;text-decoration:none;border:1px solid #bbf7d0;cursor:pointer"><i class="ri-whatsapp-line"></i> WhatsApp</button>';
            html += '<button onclick="Mautic.lwaEmailLead('+l.id+',\''+(l.email||'').replace(/'/g,"\\'")+'\',\''+Mautic.lwaEsc(name)+'\')" style="display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:8px;background:#eff6ff;color:#3b82f6;font-size:13px;font-weight:600;border:1px solid #bfdbfe;cursor:pointer"><i class="ri-mail-send-line"></i> E-mail</button>';
            html += '<button onclick="Mautic.lwaScheduleFollowup('+l.id+',\''+Mautic.lwaEsc(name)+'\')" style="display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:8px;background:var(--lwa-gray-50);color:var(--lwa-gray-700);font-size:13px;font-weight:600;border:1px solid var(--lwa-gray-200);cursor:pointer"><i class="ri-calendar-todo-line"></i> Follow-up</button>';
            html += '<button onclick="Mautic.lwaPushToCrm('+l.id+',\''+Mautic.lwaEsc(name)+'\')" style="display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:8px;background:#f0f9ff;color:#0369a1;font-size:13px;font-weight:600;border:1px solid #bae6fd;cursor:pointer"><i class="ri-send-plane-line"></i> Enviar CRM</button>';
            html += '<button onclick="Mautic.lwaCalendarModal('+l.id+',\''+Mautic.lwaEsc(name)+'\')" style="display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:8px;background:#f0fdf4;color:#16a34a;font-size:13px;font-weight:600;border:1px solid #bbf7d0;cursor:pointer"><i class="ri-calendar-check-line"></i> Reunião</button>';
            html += '<button onclick="Mautic.lwaCnpjModal('+l.id+',\''+Mautic.lwaEsc(name)+'\',\''+(l.company||'').replace(/'/g,"\\'")+'\')" style="display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:8px;background:#faf5ff;color:#7c3aed;font-size:13px;font-weight:600;border:1px solid #ddd6fe;cursor:pointer"><i class="ri-building-2-line"></i> CNPJ</button>';
            html += '<button onclick="Mautic.lwaUpdateLead('+l.id+',\'temperatura\',\'Quente\')" style="display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:8px;background:#fef3c7;color:#d97706;font-size:13px;font-weight:600;border:1px solid #fde68a;cursor:pointer"><i class="ri-fire-line"></i> Quente</button>';
            html += '<button onclick="Mautic.lwaUpdateLead('+l.id+',\'temperatura\',\'Prioritário\')" style="display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:8px;background:#ede9fe;color:#7c3aed;font-size:13px;font-weight:600;border:1px solid #ddd6fe;cursor:pointer"><i class="ri-vip-crown-line"></i> Prioritário</button>';
            html += '</div>';

            // === PIPELINE ===
            html += '<div style="margin-bottom:16px"><div style="font-size:12px;font-weight:700;color:var(--lwa-gray-400);margin-bottom:8px">ETAPA DO PIPELINE</div>';
            html += '<div class="lwa-modal-status-bar">';
            var statuses = [{v:'prospeccao',l:'Prospecção'},{v:'engajado',l:'Engajado'},{v:'reuniao_agendada',l:'Reunião'},{v:'proposta_enviada',l:'Proposta'},{v:'negociacao',l:'Negociação'},{v:'ganho',l:'Ganho'},{v:'perdido',l:'Perdido'}];
            statuses.forEach(function(s) {
                var active = (l.status_comercial === s.v || (l.status_comercial||'').toLowerCase().replace(/ /g,'_').indexOf(s.v) > -1) ? ' lwa-status-btn--active' : '';
                html += '<button class="lwa-status-btn'+active+'" onclick="Mautic.lwaUpdateLead('+l.id+',\'status_comercial\',\''+s.v+'\')">'+s.l+'</button>';
            });
            html += '</div></div>';

            // === CAMPANHAS (carrega async) ===
            html += '<div id="lwa-lead-campaigns" style="margin-bottom:16px"></div>';

            // === SMART ALERT ===
            var tempLower = (l.temperatura||'').toLowerCase().replace('á','a');
            var alertMsg = '';
            if ((tempLower === 'quente' || tempLower === 'prioritario') && (!l.data_proximo_followup || l.data_proximo_followup < new Date().toISOString().substring(0,10))) {
                alertMsg = '<div style="display:flex;gap:8px;align-items:center;padding:10px 14px;background:#fef2f2;border-radius:8px;border-left:3px solid #dc2626;margin-bottom:12px;font-size:12px;color:#991b1b"><i class="ri-alarm-warning-line" style="font-size:16px;color:#dc2626;flex-shrink:0"></i><span><strong>Ação urgente:</strong> Lead ' + (l.temperatura||'quente') + ' sem follow-up agendado. Agende contato para não perder a janela.</span></div>';
            } else if (l.data_ultima_interacao) {
                var diasSem = Math.floor((Date.now() - new Date(l.data_ultima_interacao).getTime()) / 86400000);
                if (diasSem > 14) {
                    alertMsg = '<div style="display:flex;gap:8px;align-items:center;padding:10px 14px;background:#fffbeb;border-radius:8px;border-left:3px solid #f59e0b;margin-bottom:12px;font-size:12px;color:#92400e"><i class="ri-time-line" style="font-size:16px;color:#f59e0b;flex-shrink:0"></i><span><strong>' + diasSem + ' dias sem interação.</strong> Risco de esfriar — considere reativação ou novo canal.</span></div>';
                } else if (diasSem > 7 && tempLower !== 'frio') {
                    alertMsg = '<div style="display:flex;gap:8px;align-items:center;padding:10px 14px;background:#f0f9ff;border-radius:8px;border-left:3px solid #3b82f6;margin-bottom:12px;font-size:12px;color:#1e40af"><i class="ri-information-line" style="font-size:16px;color:#3b82f6;flex-shrink:0"></i><span>' + diasSem + ' dias sem interação. Bom momento para follow-up.</span></div>';
                }
            }
            html += alertMsg;

            // === IA & MENSAGENS ===
            html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px">';
            html += '<button class="lwa-modal-action-btn lwa-modal-action-btn--ai" onclick="Mautic.lwaAiSuggest('+l.id+')"><i class="ri-sparkling-line"></i> IA: Próxima Ação</button>';
            html += '<button class="lwa-modal-action-btn lwa-modal-action-btn--ai" onclick="Mautic.lwaLeadSummary('+l.id+')"><i class="ri-file-list-3-line"></i> Resumo IA</button>';
            if (!l.status_comercial || l.status_comercial === 'prospeccao' || l.status_comercial === 'Novo') {
                html += '<button class="lwa-modal-action-btn" onclick="Mautic.lwaGeneratePlaybook('+l.id+',\'inicial_linkedin\')"><i class="ri-linkedin-box-line"></i> Msg LinkedIn</button>';
            } else if (l.status_comercial === 'perdido' || l.status_comercial === 'Perdido') {
                html += '<button class="lwa-modal-action-btn" onclick="Mautic.lwaGeneratePlaybook('+l.id+',\'reativacao\')"><i class="ri-refresh-line"></i> Reativar</button>';
            } else {
                html += '<button class="lwa-modal-action-btn" onclick="Mautic.lwaGeneratePlaybook('+l.id+',\'diagnostico\')"><i class="ri-stethoscope-line"></i> Follow-up</button>';
            }
            html += '<button class="lwa-modal-action-btn" onclick="Mautic.lwaShowTimeline('+l.id+')"><i class="ri-time-line"></i> Timeline</button>';
            html += '<button class="lwa-modal-action-btn lwa-modal-action-btn--ai" onclick="Mautic.lwaPredictiveScore('+l.id+')"><i class="ri-bar-chart-grouped-line"></i> Score Preditivo</button>';
            html += '<button class="lwa-modal-action-btn" onclick="Mautic.lwaAutoClassify('+l.id+')"><i class="ri-robot-line"></i> Auto-classificar</button>';
            html += '</div>';

            // AI suggestion placeholder
            html += '<div id="lwa-ai-result"></div>';

            // Timeline placeholder
            html += '<div id="lwa-timeline-result"></div>';

            html += '</div>';
            mQuery('#lwa-modal-body').html(html);

            // Load campaigns async
            mQuery.ajax({
                url: '/s/analytics/lead/' + leadId + '/campaigns',
                dataType: 'json',
                success: function(cRes) {
                    var camps = cRes.campaigns || [];
                    if (camps.length > 0) {
                        var ch = '<div style="font-size:12px;font-weight:700;color:var(--lwa-gray-400);margin-bottom:6px">CAMPANHAS MAUTIC</div>';
                        ch += '<div style="display:flex;gap:6px;flex-wrap:wrap">';
                        camps.forEach(function(c) {
                            var color = c.manually_removed ? '#94a3b8' : '#10b981';
                            ch += '<span style="padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;background:'+color+'12;color:'+color+';border:1px solid '+color+'25">'+c.name+'</span>';
                        });
                        ch += '</div>';
                        mQuery('#lwa-lead-campaigns').html(ch);
                    }
                }
            });
        },
        error: function() {
            mQuery('#lwa-modal-body').html('<div class="lwa-empty-state"><p>Erro ao carregar lead</p></div>');
        }
    });
};

Mautic.lwaOpenLeadActions = function(leadId, leadName) {
    Mautic.lwaOpenLeadModal(leadId);
};

Mautic.lwaModalField = function(label, value) {
    return '<div class="lwa-modal-field"><span class="lwa-modal-field-label">' + label + '</span><span class="lwa-modal-field-value">' + (value || '-') + '</span></div>';
};

Mautic.lwaEmailLead = function(leadId, email, name) {
    if (!email) { Mautic.lwaToast('Lead sem e-mail cadastrado'); return; }

    mQuery('#lwa-modal-title').text('Enviar E-mail');
    var html = '<div class="lwa-form-section">';
    html += '<div class="lwa-form-header"><i class="ri-mail-send-line"></i> ' + name + '</div>';
    html += '<div class="lwa-form-meta"><i class="ri-mail-line"></i> ' + email + '</div>';
    html += '<div class="lwa-form-group"><label>Assunto</label><input type="text" id="lwa-email-subject" class="lwa-input" placeholder="Assunto do e-mail"></div>';
    html += '<div class="lwa-form-group"><label>Mensagem</label><textarea id="lwa-email-body" class="lwa-textarea" rows="5" placeholder="Escreva sua mensagem..."></textarea></div>';
    html += '<div class="lwa-form-actions">';
    html += '  <button class="lwa-btn lwa-btn--primary" onclick="Mautic.lwaSendEmail(' + leadId + ')"><i class="ri-send-plane-line"></i> Enviar</button>';
    html += '  <button class="lwa-btn lwa-btn--ai" onclick="Mautic.lwaAiEmailSuggest(' + leadId + ')"><i class="ri-sparkling-line"></i> Gerar com IA</button>';
    html += '  <button class="lwa-btn lwa-btn--ghost" onclick="Mautic.lwaOpenLeadModal(' + leadId + ')">Voltar</button>';
    html += '</div></div>';

    mQuery('#lwa-modal-title').text('Enviar E-mail');
    mQuery('#lwa-modal-body').html(html);
    mQuery('#lwa-lead-modal').show();
};

Mautic.lwaSendEmail = function(leadId) {
    var subject = mQuery('#lwa-email-subject').val().trim();
    var body = mQuery('#lwa-email-body').val().trim();
    if (!subject || !body) { Mautic.lwaToast('Preencha assunto e mensagem'); return; }

    mQuery.ajax({
        url: '/s/analytics/action/send-email',
        method: 'POST',
        data: { lead_id: leadId, subject: subject, body: body },
        headers: {'X-CSRF-Token': mauticAjaxCsrf},
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                Mautic.lwaToast(res.message);
                Mautic.lwaIaCoach(leadId, 'email_enviado', subject + ': ' + body.substring(0, 100));
                Mautic.lwaOpenLeadModal(leadId);
            } else {
                Mautic.lwaToast(res.error || 'Erro ao enviar');
            }
        },
        error: function() { Mautic.lwaToast('Erro de conexão'); }
    });
};

Mautic.lwaAiEmailSuggest = function(leadId) {
    mQuery('#lwa-email-subject').attr('disabled', true);
    mQuery('#lwa-email-body').attr('disabled', true).val('Gerando email com IA...');
    mQuery.ajax({
        url: '/s/analytics/ai/generate-email/' + leadId,
        dataType: 'json',
        success: function(res) {
            mQuery('#lwa-email-subject').attr('disabled', false);
            mQuery('#lwa-email-body').attr('disabled', false);
            if (res.error) {
                mQuery('#lwa-email-body').val('');
                Mautic.lwaToast(res.error);
                return;
            }
            if (res.assunto) mQuery('#lwa-email-subject').val(res.assunto);
            if (res.corpo) mQuery('#lwa-email-body').val(res.corpo.replace(/\\n/g, '\n'));
            Mautic.lwaToast('Email gerado — cenário: ' + (res.cenario_detectado || 'auto'));
        },
        error: function() {
            mQuery('#lwa-email-subject').attr('disabled', false);
            mQuery('#lwa-email-body').attr('disabled', false).val('');
            Mautic.lwaToast('Erro ao gerar email com IA');
        }
    });
};

Mautic.lwaScheduleFollowup = function(leadId, name) {
    var tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate() + 1);
    var in3 = new Date(); in3.setDate(in3.getDate() + 3);
    var in7 = new Date(); in7.setDate(in7.getDate() + 7);

    function fmtDate(d) { return d.toISOString().split('T')[0]; }
    function fmtLabel(d) { var dias = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb']; return dias[d.getDay()] + ' ' + d.getDate() + '/' + (d.getMonth()+1); }

    mQuery('#lwa-modal-title').text('Agendar Follow-up');
    var html = '<div class="lwa-form-section">';
    html += '<div class="lwa-form-header" style="display:flex;align-items:center;gap:10px"><i class="ri-calendar-todo-line" style="font-size:20px;color:#7c3aed"></i> <span>' + name + '</span></div>';

    html += '<div class="lwa-form-group"><label style="font-size:12px;font-weight:600;color:var(--lwa-gray-500);margin-bottom:8px">Quando?</label>';
    html += '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px">';
    html += '<div class="lwa-date-chip active" onclick="Mautic.lwaSelectFollowupDate(this,\'' + fmtDate(tomorrow) + '\')"><div style="font-size:16px;font-weight:700">Amanhã</div><div style="font-size:11px;color:var(--lwa-gray-400)">' + fmtLabel(tomorrow) + '</div></div>';
    html += '<div class="lwa-date-chip" onclick="Mautic.lwaSelectFollowupDate(this,\'' + fmtDate(in3) + '\')"><div style="font-size:16px;font-weight:700">+3 dias</div><div style="font-size:11px;color:var(--lwa-gray-400)">' + fmtLabel(in3) + '</div></div>';
    html += '<div class="lwa-date-chip" onclick="Mautic.lwaSelectFollowupDate(this,\'' + fmtDate(in7) + '\')"><div style="font-size:16px;font-weight:700">+7 dias</div><div style="font-size:11px;color:var(--lwa-gray-400)">' + fmtLabel(in7) + '</div></div>';
    html += '<div class="lwa-date-chip" onclick="mQuery(\'#lwa-followup-custom\').toggle();mQuery(\'.lwa-date-chip\').removeClass(\'active\');mQuery(this).addClass(\'active\')"><div style="font-size:14px;font-weight:700"><i class="ri-calendar-line"></i></div><div style="font-size:11px;color:var(--lwa-gray-400)">Escolher</div></div>';
    html += '</div>';
    html += '<input type="date" id="lwa-followup-custom" class="lwa-input" style="display:none;margin-top:8px" onchange="mQuery(\'#lwa-followup-date\').val(this.value)">';
    html += '<input type="hidden" id="lwa-followup-date" value="' + fmtDate(tomorrow) + '">';
    html += '</div>';

    html += '<div class="lwa-form-group"><label style="font-size:12px;font-weight:600;color:var(--lwa-gray-500);margin-bottom:8px">Tipo de ação</label>';
    html += '<div style="display:flex;flex-wrap:wrap;gap:6px">';
    html += '<label class="lwa-action-chip active" onclick="mQuery(\'.lwa-action-chip\').removeClass(\'active\');mQuery(this).addClass(\'active\')"><input type="radio" name="lwa-followup-tipo" value="ligar" checked style="display:none"><i class="ri-phone-line"></i> Ligar</label>';
    html += '<label class="lwa-action-chip" onclick="mQuery(\'.lwa-action-chip\').removeClass(\'active\');mQuery(this).addClass(\'active\')"><input type="radio" name="lwa-followup-tipo" value="email" style="display:none"><i class="ri-mail-line"></i> Email</label>';
    html += '<label class="lwa-action-chip" onclick="mQuery(\'.lwa-action-chip\').removeClass(\'active\');mQuery(this).addClass(\'active\')"><input type="radio" name="lwa-followup-tipo" value="whatsapp" style="display:none"><i class="ri-whatsapp-line"></i> WhatsApp</label>';
    html += '<label class="lwa-action-chip" onclick="mQuery(\'.lwa-action-chip\').removeClass(\'active\');mQuery(this).addClass(\'active\')"><input type="radio" name="lwa-followup-tipo" value="reuniao" style="display:none"><i class="ri-video-chat-line"></i> Reunião</label>';
    html += '<label class="lwa-action-chip" onclick="mQuery(\'.lwa-action-chip\').removeClass(\'active\');mQuery(this).addClass(\'active\')"><input type="radio" name="lwa-followup-tipo" value="linkedin" style="display:none"><i class="ri-linkedin-line"></i> LinkedIn</label>';
    html += '</div></div>';

    html += '<div class="lwa-form-group"><label style="font-size:12px;font-weight:600;color:var(--lwa-gray-500)">Observação</label><textarea id="lwa-followup-note" class="lwa-textarea" rows="2" placeholder="Ex: Enviar proposta revisada, confirmar reunião..."></textarea></div>';
    html += '<div style="padding:10px 12px;background:#faf5ff;border-radius:8px;border-left:3px solid #7c3aed;margin-bottom:16px;font-size:12px;color:#6b21a8"><i class="ri-notification-3-line"></i> Notificação automática 1 dia antes no sino do dashboard.</div>';
    html += '<div class="lwa-form-actions">';
    html += '  <button class="lwa-btn lwa-btn--primary" onclick="Mautic.lwaConfirmFollowup(' + leadId + ')"><i class="ri-calendar-check-line"></i> Agendar</button>';
    html += '  <button class="lwa-btn lwa-btn--ghost" onclick="Mautic.lwaOpenLeadModal(' + leadId + ')">Voltar</button>';
    html += '</div></div>';

    mQuery('#lwa-modal-body').html(html);
    mQuery('#lwa-lead-modal').show();
};

Mautic.lwaSelectFollowupDate = function(el, date) {
    mQuery('.lwa-date-chip').removeClass('active');
    mQuery(el).addClass('active');
    mQuery('#lwa-followup-date').val(date);
    mQuery('#lwa-followup-custom').hide();
};

Mautic.lwaConfirmFollowup = function(leadId) {
    var date = mQuery('#lwa-followup-date').val();
    var note = mQuery('#lwa-followup-note').val().trim();
    var tipo = mQuery('input[name="lwa-followup-tipo"]:checked').val() || 'ligar';
    if (!date) { Mautic.lwaToast('Selecione uma data'); return; }
    var today = new Date().toISOString().split('T')[0];
    if (date < today) { Mautic.lwaToast('Data não pode ser no passado'); return; }

    var fullNote = '[' + tipo.toUpperCase() + '] ' + (note || '');

    mQuery.ajax({
        url: '/s/analytics/action/followup',
        method: 'POST',
        data: { lead_id: leadId, date: date, note: fullNote.trim() },
        headers: {'X-CSRF-Token': mauticAjaxCsrf},
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                Mautic.lwaToast(res.message);
                Mautic.lwaOpenLeadModal(leadId);
                // Refresh notification count
                Mautic.lwaLoadNotifications();
            } else {
                Mautic.lwaToast(res.error || 'Erro ao agendar');
            }
        },
        error: function() { Mautic.lwaToast('Erro de conexão'); }
    });
};

/**
 * AI Suggestion (Gemini)
 */
Mautic.lwaAiSuggest = function(leadId) {
    var $result = mQuery('#lwa-ai-result');
    $result.html('<div class="lwa-ai-loading"><i class="ri-sparkling-line ri-spin"></i> Analisando lead com IA...</div>');

    mQuery.ajax({
        url: '/s/analytics/ai/suggest/' + leadId,
        dataType: 'json',
        timeout: 25000,
        success: function(res) {
            var s = res.suggestion;
            if (s.error) { $result.html('<div class="lwa-ai-error"><i class="ri-error-warning-line"></i> ' + s.error + '</div>'); return; }

            var probColor = s.probabilidade_conversao === 'Alta' ? '#10b981' : s.probabilidade_conversao === 'Media' ? '#f59e0b' : '#dc2626';

            var html = '<div class="lwa-ai-card">';
            html += '<div class="lwa-ai-header"><i class="ri-sparkling-line"></i> Análise Estratégica IA</div>';
            html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:12px 0">';
            html += '<div style="padding:10px 12px;background:var(--lwa-gray-50);border-radius:8px"><div style="font-size:10px;font-weight:600;color:var(--lwa-gray-400);text-transform:uppercase">Intenção</div><div style="font-size:13px;font-weight:600;margin-top:3px">' + (s.intencao || '-') + '</div></div>';
            html += '<div style="padding:10px 12px;background:var(--lwa-gray-50);border-radius:8px"><div style="font-size:10px;font-weight:600;color:var(--lwa-gray-400);text-transform:uppercase">Probabilidade</div><div style="font-size:13px;font-weight:700;margin-top:3px;color:' + probColor + '">' + (s.probabilidade_conversao || '-') + '</div></div>';
            html += '</div>';
            html += '<div style="padding:12px;background:#f0fdf4;border-radius:8px;border-left:3px solid #10b981;margin-bottom:12px"><div style="font-size:10px;font-weight:700;color:#16a34a;text-transform:uppercase;margin-bottom:4px">Próxima Ação</div><div style="font-size:14px;font-weight:600;color:#15803d">' + (s.proxima_acao || '-') + '</div></div>';
            if (s.dica_comercial && s.dica_comercial !== '-') {
                html += '<div style="font-size:12px;color:var(--lwa-gray-500);margin-bottom:12px;padding-left:12px;border-left:2px solid var(--lwa-gray-200)"><strong>Dica:</strong> ' + s.dica_comercial + '</div>';
            }
            if (s.mensagem_followup && s.mensagem_followup !== '-') {
                html += '<div style="padding:12px;background:var(--lwa-gray-50);border-radius:8px;border-left:3px solid #7c3aed;margin-bottom:10px"><div style="font-size:10px;font-weight:600;color:#7c3aed;text-transform:uppercase;margin-bottom:4px">Mensagem sugerida</div><p style="margin:0;font-size:13px;line-height:1.6;color:var(--lwa-gray-700)">' + s.mensagem_followup + '</p></div>';
                html += '<div style="display:flex;gap:6px">';
                html += '<button class="lwa-modal-action-btn" onclick="Mautic.lwaCopyText(this)" data-text="' + (s.mensagem_followup || '').replace(/"/g, '&quot;') + '"><i class="ri-file-copy-line"></i> Copiar</button>';
                html += '<button class="lwa-modal-action-btn lwa-modal-action-btn--ai" onclick="Mautic.lwaAiSuggest(' + leadId + ')"><i class="ri-refresh-line"></i> Regenerar</button>';
                html += '</div>';
            }
            html += '</div>';
            $result.html(html);
        },
        error: function(xhr) {
            var msg = 'Erro ao consultar IA.';
            if (xhr.status === 0) msg = 'Timeout — a IA demorou. Tente novamente.';
            $result.html('<div class="lwa-ai-error"><i class="ri-error-warning-line"></i> ' + msg + '</div>');
        }
    });
};

Mautic.lwaCopyText = function(btn) {
    var text = mQuery(btn).data('text');
    navigator.clipboard.writeText(text).then(function() {
        Mautic.lwaToast('Mensagem copiada!');
    });
};

Mautic.lwaUpdateLead = function(leadId, field, value) {
    mQuery.ajax({
        url: '/s/analytics/action/update-lead',
        method: 'POST',
        data: { lead_id: leadId, field: field, value: value, mauticform: {formCsrf: mauticAjaxCsrf} },
        headers: {'X-CSRF-Token': mauticAjaxCsrf},
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                Mautic.lwaToast(res.message);
                Mautic.lwaOpenLeadModal(leadId);
            } else if (res.flashes) {
                Mautic.lwaToast('Erro de sessão. Recarregue a página.');
            } else {
                Mautic.lwaToast(res.error || 'Erro');
            }
        }
    });
};

Mautic.lwaCloseModal = function() {
    mQuery('#lwa-lead-modal').hide();
};

Mautic.lwaToast = function(message, type) {
    if (!mQuery('.lwa-toast-container').length) mQuery('body').append('<div class="lwa-toast-container"></div>');
    type = type || (message.toLowerCase().indexOf('erro') > -1 ? 'error' : 'success');
    var icons = {success:'ri-check-line', error:'ri-error-warning-line', warning:'ri-alert-line', info:'ri-information-line'};
    var $toast = mQuery('<div class="lwa-toast-item lwa-toast-item--' + type + '"><i class="' + (icons[type]||icons.info) + '"></i> ' + message + '<div class="lwa-toast-progress"></div></div>');
    mQuery('.lwa-toast-container').append($toast);
    setTimeout(function() { $toast.css('opacity','0'); setTimeout(function() { $toast.remove(); }, 300); }, 3500);
};

/**
 * Fila de Leads Incompletos
 */
Mautic.lwaShowIncompleteQueue = function() {
    var $panel = mQuery('#lwa-kpi-detail');
    Mautic.lwaKpiPage = 1;
    mQuery('#lwa-kpi-detail-title').text('Leads Incompletos — Enriquecimento');
    mQuery('#lwa-kpi-detail-body').html('<div class="lwa-panel-loading"><div class="lwa-spinner"></div><span>Carregando fila...</span></div>');
    mQuery('#lwa-kpi-pagination').html('');
    $panel.show();

    mQuery.ajax({
        url: '/s/analytics/kpi-detail/incomplete',
        dataType: 'json',
        success: function(response) {
            var leads = response.leads || [];
            if (!leads.length) {
                mQuery('#lwa-kpi-detail-body').html('<div class="lwa-empty-state"><i class="ri-checkbox-circle-line"></i><p>Nenhum lead incompleto na fila</p></div>');
                return;
            }

            var html = '<div class="lwa-kd-header-bar"><span class="lwa-kd-count"><strong>' + leads.length + '</strong> leads aguardando enriquecimento</span></div>';
            html += '<div class="lwa-kd-grid">';
            leads.forEach(function(lead, idx) {
                var name = ((lead.firstname || '') + ' ' + (lead.lastname || '')).trim() || lead.company || 'Sem nome';
                var motivo = (lead.motivo_lead_incompleto || 'sem_email_telefone').replace(/_/g, ' ');
                html += '<div class="lwa-kd-card" style="animation-delay:' + (idx * 0.03) + 's">';
                html += '  <div class="lwa-kd-avatar" style="background:#f59e0b">' + name.charAt(0).toUpperCase() + '</div>';
                html += '  <div class="lwa-kd-info">';
                html += '    <div class="lwa-kd-name">' + name + '</div>';
                html += '    <div class="lwa-kd-role">' + (lead.company || '') + ' · ' + (lead.cargo || '') + '</div>';
                html += '  </div>';
                html += '  <span class="lwa-kd-status-pill" style="background:#fef3c7;color:#d97706;border:1px solid #fde68a"><i class="ri-alert-line"></i> ' + motivo + '</span>';
                html += '  <button class="lwa-action-btn" onclick="Mautic.lwaEnrichLead(' + lead.id + ',\'' + name.replace(/'/g, "\\'") + '\')"><i class="ri-edit-line"></i></button>';
                html += '</div>';
            });
            html += '</div>';
            mQuery('#lwa-kpi-detail-body').html(html);
        }
    });
};

Mautic.lwaEnrichLead = function(leadId, name) {
    var $modal = mQuery('#lwa-lead-modal');
    mQuery('#lwa-modal-title').text('Enriquecer Lead');
    var html = '<div class="lwa-form-section">';
    html += '<div class="lwa-form-header"><i class="ri-user-add-line"></i> ' + name + '</div>';
    html += '<div class="lwa-form-group"><label>E-mail</label><input type="email" id="lwa-enrich-email" class="lwa-input" placeholder="email@empresa.com"></div>';
    html += '<div class="lwa-form-group"><label>Telefone</label><input type="tel" id="lwa-enrich-phone" class="lwa-input" placeholder="(00) 00000-0000"></div>';
    html += '<div class="lwa-form-info"><i class="ri-information-line"></i> Ao completar email e telefone, o lead sai da fila de enriquecimento.</div>';
    html += '<div class="lwa-form-actions">';
    html += '  <button class="lwa-btn lwa-btn--primary" onclick="Mautic.lwaConfirmEnrich(' + leadId + ')"><i class="ri-check-line"></i> Salvar</button>';
    html += '  <button class="lwa-btn lwa-btn--ghost" onclick="Mautic.lwaCloseModal()">Cancelar</button>';
    html += '</div></div>';
    mQuery('#lwa-modal-body').html(html);
    $modal.show();
};

Mautic.lwaConfirmEnrich = function(leadId) {
    var email = mQuery('#lwa-enrich-email').val().trim();
    var phone = mQuery('#lwa-enrich-phone').val().trim();
    if (!email && !phone) { Mautic.lwaToast('Preencha pelo menos um campo'); return; }

    mQuery.ajax({
        url: '/s/analytics/action/update-lead',
        method: 'POST',
        data: { lead_id: leadId, field: 'enrich', email: email, phone: phone },
        headers: {'X-CSRF-Token': mauticAjaxCsrf},
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                Mautic.lwaToast(res.message);
                Mautic.lwaCloseModal();
                Mautic.lwaShowIncompleteQueue();
            } else {
                Mautic.lwaToast(res.error || 'Erro');
            }
        }
    });
};

/**
 * Playbook Messages
 */
Mautic.lwaGeneratePlaybook = function(leadId, tipo) {
    var $result = mQuery('#lwa-ai-result');
    var tipoLabels = {'inicial_linkedin':'LinkedIn','diagnostico':'Diagnóstico','reativacao':'Reativação','checklist':'Conteúdo'};
    $result.html('<div class="lwa-ai-loading"><i class="ri-sparkling-line ri-spin"></i> IA gerando ' + (tipoLabels[tipo]||tipo) + '...</div>');

    mQuery.ajax({
        url: '/s/analytics/ai/tab/playbook_' + tipo + '_' + leadId,
        dataType: 'json',
        timeout: 25000,
        success: function(res) {
            var data = res.analysis || res;
            if (data.error) { $result.html('<div class="lwa-ai-error"><i class="ri-error-warning-line"></i> ' + data.error + '</div>'); return; }

            var msg = data.message || '';
            var html = '<div class="lwa-ai-card">';
            html += '<div class="lwa-ai-header"><i class="ri-sparkling-line"></i> ' + (tipoLabels[tipo]||tipo) + ' — gerado por IA</div>';
            html += '<div class="lwa-ai-message" style="padding:14px;background:var(--lwa-gray-50);border-radius:10px;border-left:3px solid #7c3aed;margin:10px 0"><p style="white-space:pre-line;line-height:1.6;margin:0;font-size:14px">' + msg + '</p></div>';
            if (data.contexto) {
                html += '<div style="font-size:12px;color:var(--lwa-gray-400);margin-bottom:10px;padding-left:14px;border-left:2px solid var(--lwa-gray-200)"><em>' + data.contexto + '</em></div>';
            }
            if (tipo === 'inicial_linkedin') {
                html += '<div style="font-size:11px;color:var(--lwa-gray-400);margin-bottom:8px">' + msg.length + '/280 caracteres</div>';
            }
            html += '<div style="display:flex;gap:6px;margin-top:8px">';
            html += '  <button class="lwa-modal-action-btn" onclick="Mautic.lwaCopyText(this)" data-text="' + msg.replace(/"/g, '&quot;').replace(/\n/g, '&#10;') + '"><i class="ri-file-copy-line"></i> Copiar</button>';
            html += '  <button class="lwa-modal-action-btn lwa-modal-action-btn--ai" onclick="Mautic.lwaGeneratePlaybook(' + leadId + ',\'' + tipo + '\')"><i class="ri-refresh-line"></i> Regenerar</button>';
            html += '</div>';
            html += '</div>';
            $result.html(html);
        },
        error: function(xhr) {
            var msg = 'Erro ao gerar mensagem.';
            if (xhr.status === 0) msg = 'Timeout — tente novamente.';
            $result.html('<div class="lwa-ai-error"><i class="ri-error-warning-line"></i> ' + msg + '</div>');
        }
    });
};

/**
 * Timeline do Lead
 */
Mautic.lwaShowTimeline = function(leadId) {
    var $container = mQuery('#lwa-timeline-result');
    $container.html('<div class="lwa-ai-loading"><i class="ri-time-line ri-spin"></i> Carregando timeline...</div>');

    mQuery.ajax({
        url: '/s/analytics/lead/' + leadId + '/timeline',
        dataType: 'json',
        timeout: 15000,
        success: function(res) {
            var items = res.timeline || [];
            if (!items.length) { $container.html('<div style="text-align:center;padding:20px;color:var(--lwa-gray-400)"><i class="ri-time-line" style="font-size:24px;display:block;margin-bottom:8px"></i>Sem histórico registrado.</div>'); return; }

            var icons = { note: 'ri-sticky-note-line', email: 'ri-mail-line', page: 'ri-globe-line', form: 'ri-file-edit-line', download: 'ri-download-2-line', score: 'ri-arrow-up-circle-line', push: 'ri-send-plane-line', whatsapp: 'ri-whatsapp-line' };
            var colors = { note: '#64748b', email: '#3b82f6', page: '#10b981', form: '#7c3aed', download: '#f59e0b', score: '#9333ea', push: '#0369a1', whatsapp: '#25d366' };

            var html = '<div class="lwa-timeline">';
            html += '<div class="lwa-ai-header" style="margin-top:16px"><i class="ri-time-line"></i> Timeline <span style="font-size:11px;color:var(--lwa-gray-400);font-weight:400">(' + items.length + ' eventos)</span></div>';

            items.sort(function(a, b) { return (b.date || '').localeCompare(a.date || ''); });

            items.forEach(function(item) {
                var icon = icons[item.type] || 'ri-record-circle-line';
                var color = colors[item.type] || '#94a3b8';
                var date = '';
                if (item.date) {
                    try {
                        var d = new Date(item.date);
                        if (!isNaN(d.getTime())) {
                            date = d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'});
                        }
                    } catch(e) {}
                }
                var safeText = (item.text || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                html += '<div class="lwa-timeline-item">';
                html += '  <div class="lwa-timeline-icon" style="color:' + color + ';background:' + color + '12"><i class="' + icon + '"></i></div>';
                html += '  <div class="lwa-timeline-content">';
                html += '    <div class="lwa-timeline-text">' + safeText + '</div>';
                html += '    <div class="lwa-timeline-meta">' + date + (item.user && item.user !== 'system' ? ' · ' + item.user : '') + '</div>';
                html += '  </div>';
                html += '</div>';
            });
            html += '</div>';
            $container.html(html);
        },
        error: function(xhr) {
            var msg = xhr.status === 0 ? 'Timeout — tente novamente.' : 'Erro ao carregar timeline.';
            $container.html('<div style="color:#dc2626;padding:12px;text-align:center"><i class="ri-error-warning-line"></i> ' + msg + '</div>');
        }
    });
};

/**
 * Resumo Consolidado IA
 */
Mautic.lwaLeadSummary = function(leadId) {
    var $result = mQuery('#lwa-ai-result');
    $result.html('<div class="lwa-ai-loading"><i class="ri-sparkling-line ri-spin"></i> Gerando resumo consolidado...</div>');

    mQuery.ajax({
        url: '/s/analytics/ai/tab/summary_' + leadId,
        dataType: 'json',
        timeout: 25000,
        success: function(res) {
            var s = res.analysis || res.summary || {};
            if (s.error) { $result.html('<div class="lwa-ai-error"><i class="ri-error-warning-line"></i> ' + s.error + '</div>'); return; }
            if (!s.resumo && !s.proxima_acao && !s.titulo) {
                $result.html('<div class="lwa-ai-error"><i class="ri-error-warning-line"></i> IA não retornou dados. Verifique a GEMINI_API_KEY.</div>');
                return;
            }

            var html = '<div class="lwa-ai-card">';
            html += '<div class="lwa-ai-header"><i class="ri-file-list-3-line"></i> Resumo Consolidado</div>';
            if (s.resumo) html += '<div class="lwa-ai-row" style="margin-bottom:12px;line-height:1.6">' + s.resumo + '</div>';

            if (s.principais_interacoes && s.principais_interacoes.length) {
                html += '<div class="lwa-ai-row"><strong>Interações:</strong> ' + s.principais_interacoes.join(' • ') + '</div>';
            }
            if (s.dores_identificadas && s.dores_identificadas.length) {
                html += '<div class="lwa-ai-row"><strong>Dores:</strong> ' + s.dores_identificadas.join(', ') + '</div>';
            }
            if (s.objecoes && s.objecoes.length) {
                html += '<div class="lwa-ai-row"><strong>Objeções:</strong> ' + s.objecoes.join(', ') + '</div>';
            }
            if (s.proxima_acao) html += '<div class="lwa-ai-row"><strong>Próxima Ação:</strong> ' + s.proxima_acao + '</div>';
            if (s.justificativa_acao) html += '<div class="lwa-ai-row" style="color:var(--lwa-gray-500);font-size:12px"><em>' + s.justificativa_acao + '</em></div>';
            if (s.probabilidade) html += '<div class="lwa-ai-row"><strong>Probabilidade:</strong> <span style="font-weight:700;color:' + (s.probabilidade === 'Alta' ? '#10b981' : s.probabilidade === 'Media' ? '#f59e0b' : '#dc2626') + '">' + s.probabilidade + '</span></div>';

            if (s.mensagem_sugerida) {
                html += '<div class="lwa-ai-message" style="margin-top:12px;padding:12px;background:var(--lwa-gray-50);border-radius:8px;border-left:3px solid #7c3aed"><strong style="font-size:11px;text-transform:uppercase;color:var(--lwa-gray-400)">Mensagem sugerida:</strong><p style="margin:6px 0 0;line-height:1.5">' + s.mensagem_sugerida + '</p></div>';
                html += '<button class="lwa-modal-action-btn" style="margin-top:8px" onclick="Mautic.lwaCopyText(this)" data-text="' + (s.mensagem_sugerida || '').replace(/"/g, '&quot;') + '"><i class="ri-file-copy-line"></i> Copiar mensagem</button>';
            }

            html += '</div>';
            $result.html(html);
        },
        error: function(xhr) {
            var msg = 'Erro ao gerar resumo.';
            if (xhr.status === 0) msg = 'Timeout — a IA demorou demais. Tente novamente.';
            $result.html('<div class="lwa-ai-error"><i class="ri-error-warning-line"></i> ' + msg + '</div>');
        }
    });
};

/**
 * Score Event (adicionar pontos ao lead)
 */
Mautic.lwaAddScoreEvent = function(leadId, event, description) {
    mQuery.ajax({
        url: '/s/analytics/action/update-lead',
        method: 'POST',
        data: { lead_id: leadId, field: 'score_event', event: event, description: description || '' },
        headers: {'X-CSRF-Token': mauticAjaxCsrf},
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                Mautic.lwaToast('Score: ' + res.points_added + ' pts → ' + res.score + ' (' + res.temperatura + ')');
                Mautic.lwaOpenLeadModal(leadId);
            } else {
                Mautic.lwaToast(res.error || 'Erro');
            }
        }
    });
};

/**
 * Filter leads in table
 */
Mautic.lwaFilterLeads = function(query) {
    var q = query.toLowerCase();
    mQuery('#lwa-kpi-detail-body .lwa-table tbody tr').each(function() {
        var text = mQuery(this).text().toLowerCase();
        mQuery(this).toggle(text.indexOf(q) > -1);
    });
};

/**
 * Push Lead to CRM — Modal
 */
Mautic.lwaPushToCrm = function(leadId, leadName) {
    mQuery('#lwa-modal-title').text('Enviar para CRM');
    var html = '<div class="lwa-form-section">';
    html += '<div class="lwa-form-header" style="display:flex;align-items:center;gap:10px"><i class="ri-send-plane-line" style="font-size:20px;color:#0369a1"></i> <span>' + leadName + '</span></div>';
    html += '<div style="margin:16px 0;padding:14px;background:#f0f9ff;border-radius:10px;border-left:3px solid #0369a1">';
    html += '<div style="font-size:12px;font-weight:600;color:#0369a1;margin-bottom:4px">O que acontece ao enviar?</div>';
    html += '<div style="font-size:12px;color:var(--lwa-gray-600);line-height:1.5">O lead será atribuído ao responsável selecionado e receberá o evento de atribuição no CRM.</div>';
    html += '</div>';
    html += '<div class="lwa-form-group"><label>Responsável Comercial</label><input type="text" id="lwa-crm-responsavel" class="lwa-input" placeholder="Nome do responsável que receberá o lead" autocomplete="off"></div>';
    html += '<div class="lwa-form-group"><label>Prioridade</label>';
    html += '<div style="display:flex;gap:8px">';
    html += '<label class="lwa-radio-card" onclick="mQuery(\'.lwa-radio-card\').removeClass(\'active\');mQuery(this).addClass(\'active\')"><input type="radio" name="lwa-crm-prioridade" value="alta" style="display:none"><i class="ri-fire-line" style="color:#dc2626"></i> Alta</label>';
    html += '<label class="lwa-radio-card active" onclick="mQuery(\'.lwa-radio-card\').removeClass(\'active\');mQuery(this).addClass(\'active\')"><input type="radio" name="lwa-crm-prioridade" value="media" checked style="display:none"><i class="ri-flashlight-line" style="color:#f59e0b"></i> Média</label>';
    html += '<label class="lwa-radio-card" onclick="mQuery(\'.lwa-radio-card\').removeClass(\'active\');mQuery(this).addClass(\'active\')"><input type="radio" name="lwa-crm-prioridade" value="baixa" style="display:none"><i class="ri-timer-line" style="color:#10b981"></i> Baixa</label>';
    html += '</div></div>';
    html += '<div class="lwa-form-group"><label>Observação (opcional)</label><textarea id="lwa-crm-obs" class="lwa-textarea" rows="2" placeholder="Ex: Lead quente, já pediu proposta..."></textarea></div>';
    html += '<div class="lwa-form-actions">';
    html += '  <button class="lwa-btn lwa-btn--primary" onclick="Mautic.lwaConfirmPushCrm(' + leadId + ')"><i class="ri-send-plane-line"></i> Enviar para CRM</button>';
    html += '  <button class="lwa-btn lwa-btn--ghost" onclick="Mautic.lwaOpenLeadModal(' + leadId + ')">Cancelar</button>';
    html += '</div></div>';

    mQuery('#lwa-modal-body').html(html);
    mQuery('#lwa-lead-modal').show();
    setTimeout(function() { mQuery('#lwa-crm-responsavel').focus(); }, 100);
};

Mautic.lwaConfirmPushCrm = function(leadId) {
    var responsavel = mQuery('#lwa-crm-responsavel').val().trim();
    if (!responsavel) { Mautic.lwaToast('Informe o responsável'); mQuery('#lwa-crm-responsavel').focus(); return; }
    var prioridade = mQuery('input[name="lwa-crm-prioridade"]:checked').val() || 'media';
    var obs = mQuery('#lwa-crm-obs').val().trim();

    mQuery.ajax({
        url: '/s/analytics/action/update-lead',
        method: 'POST',
        data: { lead_id: leadId, field: 'webhook_event', source: 'analytics', event: 'letswork.meeting.scheduled', responsavel_comercial: responsavel, prioridade: prioridade, observacao: obs },
        headers: {'X-CSRF-Token': mauticAjaxCsrf},
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                Mautic.lwaToast('Lead enviado para ' + responsavel + ' (' + prioridade + ')');
                Mautic.lwaOpenLeadModal(leadId);
            } else {
                Mautic.lwaToast(res.error || 'Erro ao enviar');
            }
        },
        error: function() { Mautic.lwaToast('Erro de conexão'); }
    });
};

/**
 * Notification Bell
 */
Mautic.lwaToggleNotifications = function() {
    var $dropdown = mQuery('#lwa-notif-dropdown');
    if ($dropdown.is(':visible')) { $dropdown.hide(); return; }
    $dropdown.html('<div class="lwa-notif-empty"><i class="ri-loader-3-line ri-spin"></i></div>').show();
    Mautic.lwaLoadNotifications();
};

Mautic.lwaLoadNotifications = function() {
    mQuery.ajax({
        url: '/s/analytics/notifications',
        dataType: 'json',
        success: function(res) {
            var totalCount = res.count || 0;

            // Also fetch smart alerts
            mQuery.ajax({
                url: '/s/analytics/smart-alerts',
                dataType: 'json',
                success: function(alertRes) {
                    var alerts = alertRes.alerts || [];
                    totalCount += alerts.length;
                    var $badge = mQuery('#lwa-notif-badge');
                    if (totalCount > 0) { $badge.text(totalCount > 99 ? '99+' : totalCount).show(); } else { $badge.hide(); }

                    var html = '<div class="lwa-notif-header"><span><i class="ri-notification-3-line"></i> Central de Alertas</span><span style="font-size:11px;color:var(--lwa-gray-400)">' + totalCount + ' itens</span></div>';
                    html += '<div class="lwa-notif-list">';

                    // Smart Alerts (priority)
                    var severityColors = {critical:'#dc2626', high:'#f59e0b', medium:'#3b82f6'};
                    alerts.slice(0, 5).forEach(function(a) {
                        var color = severityColors[a.severity] || '#64748b';
                        html += '<div class="lwa-notif-item" style="border-left:3px solid ' + color + '" onclick="Mautic.lwaOpenLeadModal(' + a.lead_id + ');mQuery(\'#lwa-notif-dropdown\').hide()">';
                        html += '<div class="lwa-notif-icon" style="color:' + color + '"><i class="' + (a.icon||'ri-alert-line') + '"></i></div>';
                        html += '<div class="lwa-notif-body"><div class="lwa-notif-title">' + a.title + '</div><div class="lwa-notif-meta">' + (a.action||a.description) + '</div></div>';
                        html += '</div>';
                    });

                    // Follow-ups
                    if (res.followups && res.followups.length) {
                        if (alerts.length) html += '<div style="padding:6px 16px;font-size:10px;font-weight:700;color:var(--lwa-gray-400);text-transform:uppercase;border-top:1px solid var(--lwa-gray-100)">Follow-ups</div>';
                        res.followups.forEach(function(l) {
                            var name = ((l.firstname||'') + ' ' + (l.lastname||'')).trim() || l.company || 'Lead';
                            var date = l.data_proximo_followup ? l.data_proximo_followup.substring(0, 10) : '';
                            var isOverdue = date && date < new Date().toISOString().substring(0, 10);
                            var dateLabel = isOverdue ? 'Vencido ' + date.split('-').reverse().join('/') : date.split('-').reverse().join('/');
                            var cls = isOverdue ? 'overdue' : 'today';
                            html += '<div class="lwa-notif-item lwa-notif-item--' + cls + '" onclick="Mautic.lwaOpenLeadModal(' + l.id + ');mQuery(\'#lwa-notif-dropdown\').hide()">';
                            html += '<div class="lwa-notif-icon lwa-notif-icon--' + cls + '"><i class="ri-calendar-check-line"></i></div>';
                            html += '<div class="lwa-notif-body"><div class="lwa-notif-title">' + name + '</div><div class="lwa-notif-meta">' + dateLabel + ' • ' + (l.company||'') + '</div></div>';
                            html += '</div>';
                        });
                    }

                    if (res.hot_no_followup > 0) {
                        html += '<div class="lwa-notif-item lwa-notif-item--overdue" onclick="Mautic.lwaShowKpiDetail(\'hot_no_followup\',\'Sem Follow-up\');mQuery(\'#lwa-notif-dropdown\').hide()">';
                        html += '<div class="lwa-notif-icon lwa-notif-icon--hot"><i class="ri-fire-line"></i></div>';
                        html += '<div class="lwa-notif-body"><div class="lwa-notif-title">' + res.hot_no_followup + ' leads quentes sem ação</div><div class="lwa-notif-meta">Clique para ver</div></div>';
                        html += '</div>';
                    }

                    if (!alerts.length && (!res.followups || !res.followups.length) && !res.hot_no_followup) {
                        html += '<div class="lwa-notif-empty"><i class="ri-check-double-line" style="font-size:24px;margin-bottom:8px;display:block"></i>Tudo em dia!</div>';
                    }

                    html += '</div>';
                    mQuery('#lwa-notif-dropdown').html(html);
                },
                error: function() {
                    // Fallback: show just follow-ups without alerts
                    Mautic.lwaRenderNotifFallback(res);
                }
            });
        },
        error: function() {
            mQuery('#lwa-notif-dropdown').html('<div class="lwa-notif-empty">Erro ao carregar</div>');
        }
    });
};

Mautic.lwaRenderNotifFallback = function(res) {
    var count = res.count || 0;
    var $badge = mQuery('#lwa-notif-badge');
    if (count > 0) { $badge.text(count).show(); } else { $badge.hide(); }
    var html = '<div class="lwa-notif-header"><span>Lembretes</span><span style="font-size:11px;color:var(--lwa-gray-400)">' + count + '</span></div><div class="lwa-notif-list">';
    if (res.followups && res.followups.length) {
        res.followups.forEach(function(l) {
            var name = ((l.firstname||'') + ' ' + (l.lastname||'')).trim() || l.company || 'Lead';
            html += '<div class="lwa-notif-item" onclick="Mautic.lwaOpenLeadModal(' + l.id + ');mQuery(\'#lwa-notif-dropdown\').hide()"><div class="lwa-notif-body"><div class="lwa-notif-title">' + name + '</div></div></div>';
        });
    }
    html += '</div>';
    mQuery('#lwa-notif-dropdown').html(html);
};

// Auto-load notification count on page load
mQuery(document).ready(function() {
    // Load notification badge count
    mQuery.ajax({
        url: '/s/analytics/notifications',
        dataType: 'json',
        success: function(res) {
            var count = res.count || 0;
            if (count > 0) mQuery('#lwa-notif-badge').text(count).show();
        }
    });
    // Auto-load Smart Alerts + AI Insights if on overview tab
    if (mQuery('#lwa-smart-alerts').length) {
        Mautic.lwaLoadSmartAlerts();
    }
    if (mQuery('#lwa-insights').length) {
        setTimeout(function() { Mautic.lwaLoadInsights(); }, 800);
    }
    // Close dropdown on outside click
    mQuery(document).on('click', function(e) {
        if (!mQuery(e.target).closest('.lwa-notification-bell').length) {
            mQuery('#lwa-notif-dropdown').hide();
        }
    });
    // Keyboard shortcuts
    mQuery(document).on('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) return;
        if (e.key === 'k' && !e.ctrlKey) { window.location.href = mQuery('.lwa-tab[href*="kanban"]').attr('href') || '#'; }
        if (e.key === 'i' && !e.ctrlKey) { window.location.href = mQuery('.lwa-tab[href*="inbox"]').attr('href') || '#'; }
        if (e.key === 'o' && !e.ctrlKey) { window.location.href = mQuery('.lwa-tab[href*="overview"]').attr('href') || '#'; }
        if (e.key === '/' && !e.ctrlKey) { e.preventDefault(); mQuery('.lwa-search input').first().focus(); }
    });
});

/**
 * Export CSV
 */
Mautic.lwaExportCSV = function(tableSelector, filename) {
    var table = document.querySelector(tableSelector || '.lwa-table');
    if (!table) { Mautic.lwaToast('Nenhuma tabela para exportar'); return; }
    var csv = [];
    var rows = table.querySelectorAll('tr');
    rows.forEach(function(row) {
        var cols = row.querySelectorAll('td, th');
        var rowData = [];
        cols.forEach(function(col) { rowData.push('"' + col.textContent.trim().replace(/"/g, '""') + '"'); });
        csv.push(rowData.join(','));
    });
    var blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = (filename || 'leads_export') + '_' + new Date().toISOString().slice(0,10) + '.csv';
    link.click();
    Mautic.lwaToast('CSV exportado!');
};

/**
 * Utility
 */
Mautic.lwaHexToRgba = function(hex, alpha) {
    var r = parseInt(hex.slice(1, 3), 16);
    var g = parseInt(hex.slice(3, 5), 16);
    var b = parseInt(hex.slice(5, 7), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
};

/**
 * Predictive Score — AI probability to close
 */
Mautic.lwaPredictiveScore = function(leadId) {
    var $result = mQuery('#lwa-ai-result');
    $result.html('<div class="lwa-ai-loading"><i class="ri-bar-chart-grouped-line ri-spin"></i> Calculando probabilidade...</div>');

    mQuery.ajax({
        url: '/s/analytics/ai/predictive-score/' + leadId,
        dataType: 'json',
        timeout: 10000,
        success: function(res) {
            if (res.error) { $result.html('<div class="lwa-ai-error"><i class="ri-error-warning-line"></i> ' + res.error + '</div>'); return; }
            if (res.probability === null) { $result.html('<div class="lwa-ai-error">' + (res.reason || 'Dados insuficientes') + '</div>'); return; }

            var prob = res.probability;
            var color = prob >= 70 ? '#10b981' : prob >= 40 ? '#f59e0b' : '#dc2626';
            var label = prob >= 70 ? 'Alta' : prob >= 40 ? 'Média' : 'Baixa';

            var html = '<div class="lwa-ai-card">';
            html += '<div class="lwa-ai-header"><i class="ri-bar-chart-grouped-line"></i> Score Preditivo</div>';
            html += '<div style="text-align:center;margin:16px 0">';
            html += '<div style="font-size:48px;font-weight:800;color:' + color + '">' + prob + '%</div>';
            html += '<div style="font-size:13px;color:var(--lwa-gray-500)">Probabilidade de fechar (' + label + ')</div>';
            html += '</div>';
            html += '<div class="lwa-predictive-bar"><div class="lwa-predictive-fill" style="width:' + prob + '%;background:' + color + '"></div></div>';

            if (res.factors && res.factors.length) {
                html += '<div class="lwa-predictive-factors" style="margin-top:14px">';
                res.factors.forEach(function(f) {
                    var fColor = f.positive ? '#10b981' : '#dc2626';
                    var fIcon = f.positive ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line';
                    html += '<div class="lwa-predictive-factor lwa-predictive-factor--' + (f.positive?'positive':'negative') + '">';
                    html += '<i class="' + fIcon + '"></i> <span style="color:' + fColor + ';font-weight:600">' + f.impact + '</span> ' + f.label;
                    html += '</div>';
                });
                html += '</div>';
            }
            if (res.data_quality) html += '<div style="font-size:10px;color:var(--lwa-gray-400);margin-top:10px;text-align:center">Baseado em ' + res.data_quality + '</div>';
            html += '</div>';
            $result.html(html);
        },
        error: function() { $result.html('<div class="lwa-ai-error">Erro ao calcular score preditivo</div>'); }
    });
};

/**
 * Auto-classify Lead via IA
 */
Mautic.lwaAutoClassify = function(leadId) {
    var $result = mQuery('#lwa-ai-result');
    $result.html('<div class="lwa-ai-loading"><i class="ri-robot-line ri-spin"></i> Classificando lead com IA...</div>');

    mQuery.ajax({
        url: '/s/analytics/ai/auto-classify/' + leadId,
        dataType: 'json',
        timeout: 25000,
        success: function(res) {
            if (res.error) { $result.html('<div class="lwa-ai-error"><i class="ri-error-warning-line"></i> ' + res.error + '</div>'); return; }

            var c = res.classification || {};
            var html = '<div class="lwa-ai-card">';
            html += '<div class="lwa-ai-header"><i class="ri-robot-line"></i> Auto-classificação IA</div>';
            html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:12px 0">';
            html += '<div style="padding:10px 12px;background:var(--lwa-gray-50);border-radius:8px"><div style="font-size:10px;font-weight:600;color:var(--lwa-gray-400);text-transform:uppercase">Segmento</div><div style="font-size:14px;font-weight:700;margin-top:3px;color:#7c3aed">' + (c.segmento || '-') + '</div></div>';
            html += '<div style="padding:10px 12px;background:var(--lwa-gray-50);border-radius:8px"><div style="font-size:10px;font-weight:600;color:var(--lwa-gray-400);text-transform:uppercase">Módulo Provável</div><div style="font-size:14px;font-weight:700;margin-top:3px">' + (c.modulo_provavel || '-') + '</div></div>';
            html += '<div style="padding:10px 12px;background:var(--lwa-gray-50);border-radius:8px"><div style="font-size:10px;font-weight:600;color:var(--lwa-gray-400);text-transform:uppercase">ICP Score</div><div style="font-size:14px;font-weight:700;margin-top:3px;color:' + ((c.icp_score||0) >= 70 ? '#10b981' : '#f59e0b') + '">' + (c.icp_score || '0') + '/100</div></div>';
            html += '<div style="padding:10px 12px;background:var(--lwa-gray-50);border-radius:8px"><div style="font-size:10px;font-weight:600;color:var(--lwa-gray-400);text-transform:uppercase">Temperatura</div><div style="font-size:14px;font-weight:700;margin-top:3px">' + (c.temperatura_sugerida || '-') + '</div></div>';
            html += '</div>';
            if (c.icp_justificativa) html += '<div style="font-size:12px;color:var(--lwa-gray-500);padding:8px 12px;border-left:2px solid #7c3aed"><em>' + c.icp_justificativa + '</em></div>';
            if (c.perfil) html += '<div style="font-size:12px;margin-top:8px;padding:8px 12px;background:#f0fdf4;border-radius:6px;color:#166534"><strong>Perfil:</strong> ' + c.perfil + '</div>';
            html += '<div style="margin-top:10px;text-align:center"><span style="background:#dcfce7;color:#16a34a;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600"><i class="ri-check-line"></i> Lead atualizado</span></div>';
            html += '</div>';
            $result.html(html);
        },
        error: function() { $result.html('<div class="lwa-ai-error">Erro ao classificar lead</div>'); }
    });
};

/**
 * WhatsApp Modal — Send via API or fallback to wa.me
 */
Mautic.lwaWhatsAppModal = function(leadId, phone, name) {
    mQuery('#lwa-modal-title').text('WhatsApp');
    var waLink = 'https://wa.me/55' + phone;
    var html = '<div class="lwa-form-section">';
    html += '<div class="lwa-form-header" style="display:flex;align-items:center;gap:10px"><i class="ri-whatsapp-line" style="font-size:20px;color:#25d366"></i> <span>' + name + '</span></div>';
    html += '<div class="lwa-form-meta"><i class="ri-phone-line"></i> +55 ' + phone + '</div>';
    html += '<div class="lwa-form-group"><label>Mensagem</label><textarea id="lwa-wa-message" class="lwa-textarea" rows="4" placeholder="Olá ' + (name.split(' ')[0]) + ', tudo bem?..."></textarea></div>';
    html += '<div class="lwa-form-actions">';
    html += '  <button class="lwa-btn" style="background:#25d366;color:white" onclick="Mautic.lwaSendWhatsApp(' + leadId + ')"><i class="ri-send-plane-line"></i> Enviar via API</button>';
    html += '  <a href="' + waLink + '" target="_blank" class="lwa-btn lwa-btn--ghost" style="text-decoration:none"><i class="ri-external-link-line"></i> Abrir WhatsApp Web</a>';
    html += '  <button class="lwa-btn lwa-btn--ai" onclick="Mautic.lwaGenerateWaMessage(' + leadId + ')"><i class="ri-sparkling-line"></i> Gerar com IA</button>';
    html += '  <button class="lwa-btn lwa-btn--ghost" onclick="Mautic.lwaOpenLeadModal(' + leadId + ')">Voltar</button>';
    html += '</div></div>';

    mQuery('#lwa-modal-body').html(html);
    mQuery('#lwa-lead-modal').show();
};

Mautic.lwaSendWhatsApp = function(leadId) {
    var message = mQuery('#lwa-wa-message').val().trim();
    if (!message) { Mautic.lwaToast('Escreva uma mensagem'); return; }

    mQuery.ajax({
        url: '/s/analytics/action/send-whatsapp',
        method: 'POST',
        data: { lead_id: leadId, message: message },
        headers: {'X-CSRF-Token': mauticAjaxCsrf},
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                Mautic.lwaToast(res.message);
                Mautic.lwaIaCoach(leadId, 'whatsapp_enviado', message.substring(0, 100));
                Mautic.lwaOpenLeadModal(leadId);
            } else {
                Mautic.lwaToast(res.error || 'Erro ao enviar');
            }
        },
        error: function() { Mautic.lwaToast('Erro de conexão'); }
    });
};

Mautic.lwaGenerateWaMessage = function(leadId) {
    mQuery('#lwa-wa-message').val('Gerando mensagem...');
    mQuery.ajax({
        url: '/s/analytics/ai/tab/playbook_inicial_linkedin_' + leadId,
        dataType: 'json',
        success: function(res) {
            var data = res.analysis || res;
            mQuery('#lwa-wa-message').val(data.message || 'Erro ao gerar');
        },
        error: function() { mQuery('#lwa-wa-message').val(''); }
    });
};

/**
 * Smart Alerts Panel — AI-driven notifications
 */
Mautic.lwaLoadSmartAlerts = function() {
    var $container = mQuery('#lwa-smart-alerts');
    if (!$container.length) return;
    $container.html('<div style="text-align:center;padding:16px;color:var(--lwa-gray-400)"><i class="ri-loader-3-line ri-spin"></i> Analisando sinais...</div>');

    mQuery.ajax({
        url: '/s/analytics/smart-alerts',
        dataType: 'json',
        success: function(res) {
            var alerts = res.alerts || [];
            if (!alerts.length) {
                $container.html('<div style="text-align:center;padding:20px;color:var(--lwa-gray-400);font-size:13px"><i class="ri-check-double-line" style="font-size:20px;display:block;margin-bottom:6px"></i>Nenhum alerta no momento.</div>');
                return;
            }

            var severityColors = {critical: '#dc2626', high: '#f59e0b', medium: '#3b82f6'};
            var severityBg = {critical: '#fef2f2', high: '#fffbeb', medium: '#eff6ff'};

            var shown = alerts.slice(0, 5);
            var html = '<div style="display:flex;flex-direction:column;gap:6px">';
            shown.forEach(function(a) {
                var color = severityColors[a.severity] || '#64748b';
                var bg = severityBg[a.severity] || '#f8fafc';
                html += '<div class="lwa-smart-alert" style="display:flex;gap:10px;padding:10px 14px;background:' + bg + ';border-radius:8px;border-left:3px solid ' + color + ';cursor:pointer" onclick="Mautic.lwaOpenLeadModal(' + a.lead_id + ')">';
                html += '<i class="' + a.icon + '" style="font-size:14px;color:' + color + ';margin-top:1px;flex-shrink:0"></i>';
                html += '<div style="flex:1;min-width:0">';
                html += '<div style="font-size:12px;font-weight:600;color:var(--lwa-gray-800);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + a.title + '</div>';
                html += '<div style="font-size:11px;color:' + color + ';margin-top:2px">' + (a.action||a.description) + '</div>';
                html += '</div></div>';
            });
            if (alerts.length > 5) {
                html += '<div style="text-align:center;padding:6px;font-size:11px;color:var(--lwa-gray-400)">+ ' + (alerts.length - 5) + ' mais alertas no sino <i class="ri-notification-3-line"></i></div>';
            }
            html += '</div>';
            $container.html(html);
        },
        error: function() { $container.html('<div style="color:#dc2626;font-size:12px;padding:12px">Erro ao carregar alertas</div>'); }
    });
};

/**
 * Trigger Mautic Campaign from modal
 */
Mautic.lwaTriggerCampaign = function(leadId, action, label) {
    mQuery.ajax({
        url: '/s/analytics/action/trigger-campaign',
        method: 'POST',
        data: { lead_id: leadId, action: action },
        headers: {'X-CSRF-Token': mauticAjaxCsrf},
        dataType: 'json',
        success: function(res) {
            Mautic.lwaToast(res.success ? res.message : (res.error || 'Erro'));
            if (res.success) Mautic.lwaOpenLeadModal(leadId);
        },
        error: function() { Mautic.lwaToast('Erro de conexão'); }
    });
};

/**
 * IA Coach — Feedback pós-interação
 */
Mautic.lwaIaCoach = function(leadId, action, content) {
    setTimeout(function() {
        mQuery.ajax({
            url: '/s/analytics/ai/coach/' + leadId + '?action=' + encodeURIComponent(action) + '&content=' + encodeURIComponent(content || ''),
            dataType: 'json',
            timeout: 15000,
            success: function(res) {
                if (res.error || !res.feedback) return;
                var nota = parseInt(res.nota) || 7;
                var color = nota >= 8 ? '#10b981' : nota >= 5 ? '#f59e0b' : '#dc2626';
                var html = '<div style="position:fixed;bottom:20px;right:20px;width:320px;background:white;border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,0.15);border:1px solid var(--lwa-gray-200);z-index:9999;padding:16px;animation:lwa-slideInRight 0.3s ease" id="lwa-coach-popup">';
                html += '<div style="display:flex;align-items:center;gap:8px;margin-bottom:10px"><i class="ri-robot-line" style="font-size:18px;color:#7c3aed"></i><strong style="font-size:13px">Coach IA</strong><span style="margin-left:auto;font-size:12px;font-weight:700;color:' + color + '">' + nota + '/10</span></div>';
                html += '<div style="font-size:12px;color:var(--lwa-gray-700);line-height:1.5;margin-bottom:8px">' + res.feedback + '</div>';
                if (res.sugestao) html += '<div style="font-size:11px;color:#7c3aed;font-weight:600"><i class="ri-lightbulb-line"></i> ' + res.sugestao + (res.timing ? ' (' + res.timing + ')' : '') + '</div>';
                html += '<button onclick="mQuery(\'#lwa-coach-popup\').remove()" style="position:absolute;top:8px;right:8px;background:none;border:none;cursor:pointer;color:var(--lwa-gray-400);font-size:16px"><i class="ri-close-line"></i></button>';
                html += '</div>';
                mQuery('#lwa-coach-popup').remove();
                mQuery('body').append(html);
                setTimeout(function() { mQuery('#lwa-coach-popup').remove(); }, 12000);
            }
        });
    }, 1500);
};

/**
 * Google Calendar — Schedule meeting from modal
 */
Mautic.lwaCalendarModal = function(leadId, leadName) {
    var tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate() + 1);
    var defaultDate = tomorrow.toISOString().split('T')[0];

    mQuery('#lwa-modal-title').text('Agendar Reunião');
    var html = '<div class="lwa-form-section">';
    html += '<div class="lwa-form-header" style="display:flex;align-items:center;gap:10px"><i class="ri-calendar-check-line" style="font-size:20px;color:#10b981"></i> <span>' + leadName + '</span></div>';
    html += '<div style="padding:12px;background:#f0fdf4;border-radius:10px;border-left:3px solid #10b981;margin-bottom:16px;font-size:12px;color:#166534"><i class="ri-information-line"></i> A reunião será criada no Google Calendar e o lead avança para "Reunião Agendada".</div>';
    html += '<div class="lwa-form-group"><label>Título</label><input type="text" id="lwa-cal-title" class="lwa-input" placeholder="Reunião de diagnóstico" value="Reunião — ' + leadName + '"></div>';
    html += '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">';
    html += '<div class="lwa-form-group"><label>Data</label><input type="date" id="lwa-cal-date" class="lwa-input" value="' + defaultDate + '"></div>';
    html += '<div class="lwa-form-group"><label>Horário</label><input type="time" id="lwa-cal-time" class="lwa-input" value="10:00"></div>';
    html += '<div class="lwa-form-group"><label>Duração</label><select id="lwa-cal-duration" class="lwa-input"><option value="15">15 min</option><option value="30" selected>30 min</option><option value="45">45 min</option><option value="60">1 hora</option></select></div>';
    html += '</div>';
    html += '<div class="lwa-form-group"><label>Observações (opcional)</label><textarea id="lwa-cal-desc" class="lwa-textarea" rows="2" placeholder="Pauta, links de acesso, notas..."></textarea></div>';
    html += '<div class="lwa-form-actions">';
    html += '  <button class="lwa-btn lwa-btn--primary" onclick="Mautic.lwaCreateCalendarEvent(' + leadId + ')"><i class="ri-calendar-check-line"></i> Criar no Google Calendar</button>';
    html += '  <button class="lwa-btn lwa-btn--ghost" onclick="Mautic.lwaOpenLeadModal(' + leadId + ')">Cancelar</button>';
    html += '</div></div>';

    mQuery('#lwa-modal-body').html(html);
    mQuery('#lwa-lead-modal').show();
};

Mautic.lwaCreateCalendarEvent = function(leadId) {
    var data = {
        lead_id: leadId,
        title: mQuery('#lwa-cal-title').val().trim(),
        date: mQuery('#lwa-cal-date').val(),
        time: mQuery('#lwa-cal-time').val(),
        duration: mQuery('#lwa-cal-duration').val(),
        description: mQuery('#lwa-cal-desc').val().trim()
    };
    if (!data.date) { Mautic.lwaToast('Selecione uma data'); return; }

    mQuery.ajax({
        url: '/s/analytics/action/calendar-event',
        method: 'POST',
        data: data,
        headers: {'X-CSRF-Token': mauticAjaxCsrf},
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                Mautic.lwaToast(res.message);
                window.open(res.calendar_url, '_blank');
                Mautic.lwaOpenLeadModal(leadId);
            } else {
                Mautic.lwaToast(res.error || 'Erro');
            }
        },
        error: function() { Mautic.lwaToast('Erro de conexão'); }
    });
};

/**
 * CNPJ Enrichment — Fetch company data
 */
Mautic.lwaCnpjModal = function(leadId, leadName, company) {
    mQuery('#lwa-modal-title').text('Enriquecer via CNPJ');
    var html = '<div class="lwa-form-section">';
    html += '<div class="lwa-form-header" style="display:flex;align-items:center;gap:10px"><i class="ri-building-2-line" style="font-size:20px;color:#7c3aed"></i> <span>' + (company || leadName) + '</span></div>';
    html += '<div style="padding:12px;background:#faf5ff;border-radius:10px;border-left:3px solid #7c3aed;margin-bottom:16px;font-size:12px;color:#6b21a8"><i class="ri-sparkling-line"></i> Consulta gratuita — puxa dados oficiais da Receita Federal (razão social, porte, segmento, sócios, capital social).</div>';
    html += '<div class="lwa-form-group"><label>CNPJ da Empresa</label><input type="text" id="lwa-cnpj-input" class="lwa-input" placeholder="00.000.000/0001-00" maxlength="18" oninput="this.value=this.value.replace(/\\D/g,\'\').replace(/(\\d{2})(\\d)/,\'$1.$2\').replace(/(\\d{3})(\\d)/,\'$1.$2\').replace(/(\\d{3})(\\d)/,\'$1/$2\').replace(/(\\d{4})(\\d)/,\'$1-$2\')"></div>';
    html += '<div id="lwa-cnpj-result"></div>';
    html += '<div class="lwa-form-actions">';
    html += '  <button class="lwa-btn lwa-btn--primary" onclick="Mautic.lwaEnrichCnpj(' + leadId + ')"><i class="ri-search-line"></i> Consultar CNPJ</button>';
    html += '  <button class="lwa-btn lwa-btn--ghost" onclick="Mautic.lwaOpenLeadModal(' + leadId + ')">Voltar</button>';
    html += '</div></div>';

    mQuery('#lwa-modal-body').html(html);
    mQuery('#lwa-lead-modal').show();
    setTimeout(function() { mQuery('#lwa-cnpj-input').focus(); }, 100);
};

Mautic.lwaEnrichCnpj = function(leadId) {
    var cnpj = mQuery('#lwa-cnpj-input').val().replace(/\D/g, '');
    if (cnpj.length !== 14) { Mautic.lwaToast('CNPJ deve ter 14 dígitos'); return; }

    var $result = mQuery('#lwa-cnpj-result');
    $result.html('<div style="text-align:center;padding:16px;color:var(--lwa-gray-400)"><i class="ri-loader-3-line ri-spin"></i> Consultando Receita Federal...</div>');

    mQuery.ajax({
        url: '/s/analytics/action/enrich-cnpj',
        method: 'POST',
        data: { lead_id: leadId, cnpj: cnpj },
        headers: {'X-CSRF-Token': mauticAjaxCsrf},
        dataType: 'json',
        success: function(res) {
            if (res.error) { $result.html('<div class="lwa-ai-error"><i class="ri-error-warning-line"></i> ' + res.error + '</div>'); return; }

            var d = res.data;
            var html = '<div style="margin-top:16px;padding:16px;background:var(--lwa-gray-50);border-radius:10px">';
            html += '<div style="font-size:14px;font-weight:700;margin-bottom:10px;color:var(--lwa-gray-800)">' + (d.razao_social || '-') + '</div>';
            html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:12px">';
            html += '<div><span style="color:var(--lwa-gray-400)">Fantasia:</span> ' + (d.nome_fantasia || '-') + '</div>';
            html += '<div><span style="color:var(--lwa-gray-400)">Porte:</span> <strong>' + (d.porte || '-') + '</strong></div>';
            html += '<div><span style="color:var(--lwa-gray-400)">Segmento:</span> <strong style="color:#7c3aed">' + (d.segmento || '-') + '</strong></div>';
            html += '<div><span style="color:var(--lwa-gray-400)">Capital Social:</span> R$ ' + (d.capital_social ? parseFloat(d.capital_social).toLocaleString('pt-BR') : '-') + '</div>';
            html += '<div><span style="color:var(--lwa-gray-400)">Atividade:</span> ' + (d.atividade_principal || '-') + '</div>';
            html += '<div><span style="color:var(--lwa-gray-400)">Local:</span> ' + (d.municipio || '') + '/' + (d.uf || '') + '</div>';
            html += '<div><span style="color:var(--lwa-gray-400)">Situação:</span> ' + (d.situacao || '-') + '</div>';
            html += '<div><span style="color:var(--lwa-gray-400)">Abertura:</span> ' + (d.abertura || '-') + '</div>';
            html += '</div>';
            if (d.socios && d.socios.length) {
                html += '<div style="margin-top:10px;font-size:12px"><span style="color:var(--lwa-gray-400)">Sócios:</span> ' + d.socios.join(', ') + '</div>';
            }
            html += '<div style="margin-top:12px;text-align:center"><span style="background:#dcfce7;color:#16a34a;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600"><i class="ri-check-line"></i> Lead atualizado com sucesso</span></div>';
            html += '</div>';
            $result.html(html);
            Mautic.lwaToast('Lead enriquecido com dados do CNPJ');
        },
        error: function() { $result.html('<div class="lwa-ai-error">Erro de conexão</div>'); }
    });
};
