/**
 * Letswork Analytics Dashboard — Premium interactions
 */

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
        'Origens Afetadas': 'origens_afetadas'
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

        var ctx = this.getContext('2d');
        var config = Mautic.lwaGetChartConfig(chartType, chartData);
        var chart = new Chart(ctx, config);
        window.lwaChartInstances.push(chart);
    });
};

Mautic.lwaGetChartConfig = function(type, data) {
    var colors = data.colors || ['#dc2626', '#334155', '#64748b', '#94a3b8', '#cbd5e1', '#475569', '#1e293b', '#f59e0b'];

    var datasets;
    if (type === 'doughnut' || type === 'pie') {
        datasets = [{
            data: data.data,
            backgroundColor: colors.slice(0, data.data.length),
            borderWidth: 3,
            borderColor: 'rgba(255,255,255,0.95)',
            hoverBorderWidth: 4,
            hoverOffset: 10,
            cutout: '65%'
        }];
    } else if (type === 'bar') {
        var barColors = data.data.map(function(_, i) { return colors[i % colors.length]; });
        datasets = [{
            data: data.data,
            backgroundColor: barColors,
            borderRadius: 6,
            borderSkipped: false,
            maxBarThickness: 48,
            hoverBackgroundColor: barColors.map(function(c) { return c; })
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
    mQuery('#lwa-kpi-detail-body').html('<div class="lwa-panel-loading"><div class="lwa-spinner"></div><span>Carregando...</span></div>');
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
                mQuery('#lwa-kpi-detail-body').html('<div class="lwa-empty-state"><i class="ri-inbox-line"></i><p>Nenhum dado encontrado neste periodo</p></div>');
            }
        },
        error: function() {
            mQuery('#lwa-kpi-detail-body').html('<div class="lwa-empty-state"><p>Erro ao carregar dados</p></div>');
        }
    });
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
        'leads_perdidos': Mautic.lwaRenderLeadsPerdidos
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
    var porOrigem = {};
    var porStatus = {};
    leads.forEach(function(l) {
        var o = (l.origem_principal||'outros').replace(/_/g,' ');
        var s = (l.status_comercial||'').replace(/_/g,' ');
        porOrigem[o] = (porOrigem[o]||0) + 1;
        porStatus[s] = (porStatus[s]||0) + 1;
    });
    var totalValor = leads.reduce(function(s,l){return s+parseFloat(l.valor_potencial||0);},0);

    var html = '<div class="lwa-modal-metrics lwa-modal-metrics--3">';
    html += '<div class="lwa-metric-card lwa-metric-card--blue"><div class="lwa-metric-value" style="color:#3b82f6">' + total + '</div><div class="lwa-metric-label">Total Reuniões</div></div>';
    html += '<div class="lwa-metric-card lwa-metric-card--green"><div class="lwa-metric-value" style="color:#10b981">R$ ' + (totalValor/1000).toFixed(0) + 'k</div><div class="lwa-metric-label">Valor em pipeline</div></div>';
    html += '<div class="lwa-metric-card lwa-metric-card--purple"><div class="lwa-metric-value" style="color:#7c3aed">' + Object.keys(porOrigem).length + '</div><div class="lwa-metric-label">Origens ativas</div></div>';
    html += '</div>';
    html += '<div class="lwa-modal-grid-2">';
    html += '<div><div class="lwa-modal-section-title">Reuniões por Origem</div>';
    html += '<table class="lwa-table lwa-table--compact" style="margin:0"><tbody>';
    Object.keys(porOrigem).sort(function(a,b){return porOrigem[b]-porOrigem[a];}).forEach(function(o) {
        var pct = Math.round((porOrigem[o]/total)*100);
        html += '<tr><td><strong>'+o+'</strong></td><td>'+porOrigem[o]+'</td><td><div class="lwa-bar"><div class="lwa-bar-fill lwa-bar-fill--blue" style="width:'+pct+'%"></div></div></td><td>'+pct+'%</td></tr>';
    });
    html += '</tbody></table></div>';
    html += '<div><div class="lwa-modal-section-title">Por Etapa</div>';
    html += '<table class="lwa-table lwa-table--compact" style="margin:0"><tbody>';
    Object.keys(porStatus).sort(function(a,b){return porStatus[b]-porStatus[a];}).forEach(function(s) {
        html += '<tr><td>'+s+'</td><td><strong>'+porStatus[s]+'</strong></td></tr>';
    });
    html += '</tbody></table></div></div>';
    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderTempoProposta = function(leads) {
    var totalValor = leads.reduce(function(s,l){return s+parseFloat(l.valor_potencial||0);},0);
    var porModulo = {};
    var porStatus = {};
    leads.forEach(function(l) {
        var m = l.modulo_interesse || 'Não definido';
        var s = (l.status_comercial||'').replace(/_/g,' ');
        porModulo[m] = (porModulo[m]||0) + 1;
        porStatus[s] = (porStatus[s]||0) + 1;
    });

    var html = '<div class="lwa-modal-metrics lwa-modal-metrics--2">';
    html += '<div class="lwa-metric-card lwa-metric-card--purple"><div class="lwa-metric-value" style="color:#8b5cf6">' + leads.length + '</div><div class="lwa-metric-label">Propostas ativas</div></div>';
    html += '<div class="lwa-metric-card lwa-metric-card--green"><div class="lwa-metric-value" style="color:#10b981">R$ ' + (totalValor/1000).toFixed(0) + 'k</div><div class="lwa-metric-label">Valor total em propostas</div></div>';
    html += '</div>';
    html += '<div class="lwa-modal-grid-2">';
    html += '<div><div class="lwa-modal-section-title">Por Status</div>';
    html += '<table class="lwa-table lwa-table--compact" style="margin:0"><tbody>';
    Object.keys(porStatus).forEach(function(s){ html += '<tr><td>'+s+'</td><td><strong>'+porStatus[s]+'</strong></td></tr>'; });
    html += '</tbody></table></div>';
    html += '<div><div class="lwa-modal-section-title">Por Módulo de Interesse</div>';
    html += '<table class="lwa-table lwa-table--compact" style="margin:0"><tbody>';
    Object.keys(porModulo).sort(function(a,b){return porModulo[b]-porModulo[a];}).forEach(function(m){ html += '<tr><td>'+m+'</td><td><strong>'+porModulo[m]+'</strong></td></tr>'; });
    html += '</tbody></table></div></div>';
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
    html += '<div><div class="lwa-modal-section-title">Top Deals</div>';
    html += '<table class="lwa-table lwa-table--compact" style="margin:0"><tbody>';
    leads.slice(0,5).forEach(function(l){ var name=((l.firstname||'')+' '+(l.lastname||'')).trim()||l.company; html += '<tr class="lwa-table-row-interactive" onclick="Mautic.lwaOpenLeadModal('+l.id+')"><td><strong>'+name+'</strong></td><td style="color:#10b981;font-weight:700">R$ '+(parseFloat(l.valor_potencial||0)/1000).toFixed(0)+'k</td></tr>'; });
    html += '</tbody></table></div>';
    html += '<div><div class="lwa-modal-section-title">Valor por Segmento</div>';
    html += '<table class="lwa-table lwa-table--compact" style="margin:0"><tbody>';
    Object.keys(porSegmento).sort(function(a,b){return porSegmento[b]-porSegmento[a];}).forEach(function(s){ html += '<tr><td>'+s+'</td><td style="font-weight:700;color:#10b981">R$ '+(porSegmento[s]/1000).toFixed(0)+'k</td></tr>'; });
    html += '</tbody></table></div></div>';
    mQuery('#lwa-kpi-detail-body').html(html);
    mQuery('#lwa-kpi-pagination').html('');
};

Mautic.lwaRenderConvertidos = function(leads) {
    var totalValor = leads.reduce(function(s,l){return s+parseFloat(l.valor_potencial||0);},0);
    var porModulo = {};
    leads.forEach(function(l){ var m=l.modulo_interesse||'Não definido'; porModulo[m]=(porModulo[m]||0)+1; });

    var html = '<div style="padding:8px 0">';
    html += '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px">';
    html += '<div style="text-align:center;padding:16px;background:#f0fdf4;border-radius:10px"><div style="font-size:28px;font-weight:700;color:#10b981">' + leads.length + '</div><div style="font-size:11px;color:var(--lwa-gray-500)">Clientes ganhos</div></div>';
    html += '<div style="text-align:center;padding:16px;background:var(--lwa-gray-50);border-radius:10px"><div style="font-size:28px;font-weight:700;color:#10b981">R$ ' + (totalValor/1000).toFixed(0) + 'k</div><div style="font-size:11px;color:var(--lwa-gray-500)">Receita total</div></div>';
    html += '<div style="text-align:center;padding:16px;background:var(--lwa-gray-50);border-radius:10px"><div style="font-size:28px;font-weight:700;color:#3b82f6">R$ ' + (leads.length>0?(totalValor/leads.length/1000).toFixed(1):'0') + 'k</div><div style="font-size:11px;color:var(--lwa-gray-500)">Ticket médio</div></div>';
    html += '</div>';
    html += '<div style="font-size:12px;font-weight:600;color:var(--lwa-gray-400);margin-bottom:8px">CLIENTES POR MÓDULO</div>';
    html += '<table class="lwa-table lwa-table--compact" style="margin:0"><tbody>';
    Object.keys(porModulo).sort(function(a,b){return porModulo[b]-porModulo[a];}).forEach(function(m){
        html += '<tr class="lwa-table-row-interactive"><td><strong>'+m+'</strong></td><td>'+porModulo[m]+' clientes</td><td>R$ '+(leads.filter(function(l){return (l.modulo_interesse||'Não definido')===m;}).reduce(function(s,l){return s+parseFloat(l.valor_potencial||0);},0)/1000).toFixed(0)+'k</td></tr>';
    });
    html += '</tbody></table></div>';
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
    var html = '<div class="lwa-kd-header-bar"><span class="lwa-kd-count"><i class="ri-close-circle-line"></i> <strong>' + leads.length + '</strong> leads perdidos</span></div>';
    html += '<table class="lwa-table" style="margin:0"><thead><tr><th>Lead</th><th>Empresa</th><th>Motivo</th><th>Origem</th><th>Valor perdido</th></tr></thead><tbody>';
    leads.forEach(function(l) {
        var name = ((l.firstname||'') + ' ' + (l.lastname||'')).trim() || 'Sem nome';
        var valor = l.valor_potencial ? 'R$ ' + parseFloat(l.valor_potencial).toLocaleString('pt-BR') : '-';
        html += '<tr class="lwa-table-row-interactive" onclick="Mautic.lwaOpenLeadModal(' + l.id + ')">';
        html += '<td><strong>' + name + '</strong></td><td>' + (l.company||'-') + '</td>';
        html += '<td style="color:#dc2626">' + (l.motivo_perda||'Não informado') + '</td>';
        html += '<td>' + (l.origem_principal||'').replace(/_/g,' ') + '</td>';
        html += '<td>' + valor + '</td></tr>';
    });
    html += '</tbody></table>';
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
    var ctx = Mautic.lwaKpiContext || {};

    var html = '<div class="lwa-kd-header-bar">';
    html += '  <span class="lwa-kd-count"><strong>' + leads.length + '</strong> leads encontrados</span>';
    html += '  <span class="lwa-kd-page">Página ' + page + '/' + totalPages + '</span>';
    html += '</div>';
    if (ctx.subtitle) {
        html += '<div style="font-size:12px;color:var(--lwa-gray-400);margin-bottom:12px;display:flex;align-items:center;gap:6px"><i class="' + (ctx.icon || 'ri-information-line') + '"></i> ' + ctx.subtitle + '</div>';
    }
    html += '<div class="lwa-kd-grid">';

    pageLeads.forEach(function(lead, idx) {
        var name = ((lead.firstname || '') + ' ' + (lead.lastname || '')).trim() || lead.company || 'Sem nome';
        var initial = name.charAt(0).toUpperCase();
        var company = lead.company || '';
        var cargo = lead.cargo || '';
        var temp = lead.temperatura || '';
        var score = lead.interesse_score || 0;
        var origin = (lead.origem_principal || '').replace(/_/g, ' ');
        var status = (lead.status_comercial || '').replace(/_/g, ' ');
        var avatarColors = {'frio':'#3b82f6','morno':'#f59e0b','quente':'#dc2626','prioritario':'#9333ea'};
        var avatarBg = avatarColors[temp] || '#dc2626';

        var statusIcons = {
            'reuniao agendada': {icon: 'ri-calendar-todo-line', color: '#3b82f6', label: 'Agendada'},
            'reuniao realizada': {icon: 'ri-calendar-check-line', color: '#10b981', label: 'Realizada'},
            'reuniao fechamento': {icon: 'ri-handshake-line', color: '#059669', label: 'Fechamento'},
            'proposta enviada': {icon: 'ri-file-text-line', color: '#8b5cf6', label: 'Proposta'},
            'negociacao': {icon: 'ri-exchange-line', color: '#7c3aed', label: 'Negociação'},
            'prospeccao': {icon: 'ri-user-search-line', color: '#94a3b8', label: 'Prospecção'},
            'engajado': {icon: 'ri-chat-check-line', color: '#f59e0b', label: 'Engajado'},
            'ganho': {icon: 'ri-trophy-line', color: '#10b981', label: 'Ganho'},
            'perdido': {icon: 'ri-close-circle-line', color: '#dc2626', label: 'Perdido'}
        };
        var statusInfo = statusIcons[status] || {icon: 'ri-flag-line', color: '#94a3b8', label: status};
        var lastDate = lead.data_ultima_interacao ? new Date(lead.data_ultima_interacao).toLocaleDateString('pt-BR') : '';

        html += '<div class="lwa-kd-card" onclick="Mautic.lwaOpenLeadModal(' + lead.id + ')" style="animation-delay:' + (idx * 0.04) + 's">';
        html += '  <div class="lwa-kd-card-top">';
        html += '    <div class="lwa-kd-avatar" style="background:' + avatarBg + '">' + initial + '</div>';
        html += '    <div class="lwa-kd-info">';
        html += '      <div class="lwa-kd-name">' + name + '</div>';
        html += '      <div class="lwa-kd-role">' + (company || cargo) + '</div>';
        html += '    </div>';
        if (status) html += '    <span class="lwa-kd-status-pill" style="background:' + statusInfo.color + '12;color:' + statusInfo.color + ';border:1px solid ' + statusInfo.color + '25"><i class="' + statusInfo.icon + '"></i> ' + statusInfo.label + '</span>';
        html += '  </div>';
        html += '  <div class="lwa-kd-card-bottom">';
        // Contextual highlight field
        if (ctx.highlight === 'motivo_perda' && lead.motivo_perda) {
            html += '    <span class="lwa-kd-tag" style="background:#fef2f2;color:#dc2626"><i class="ri-alert-line"></i> ' + lead.motivo_perda + '</span>';
        } else if (ctx.highlight === 'valor_potencial' && lead.valor_potencial) {
            html += '    <span class="lwa-kd-tag" style="background:#f0fdf4;color:#10b981"><i class="ri-money-dollar-circle-line"></i> R$ ' + parseFloat(lead.valor_potencial).toLocaleString('pt-BR') + '</span>';
        } else if (ctx.highlight === 'origem_principal' && lead.origem_principal) {
            html += '    <span class="lwa-kd-tag" style="background:#eff6ff;color:#3b82f6"><i class="ri-global-line"></i> ' + (lead.origem_principal || '').replace(/_/g, ' ') + '</span>';
        }
        if (lastDate) html += '    <span class="lwa-kd-tag"><i class="ri-calendar-line"></i> ' + lastDate + '</span>';
        if (company && cargo) html += '    <span class="lwa-kd-tag"><i class="ri-user-line"></i> ' + cargo + '</span>';
        html += '    <span class="lwa-kd-tag"><i class="ri-bar-chart-fill"></i> ' + score + '</span>';
        html += '  </div>';
        html += '</div>';
    });

    html += '</div>';
    mQuery('#lwa-kpi-detail-body').html(html);

    // Pagination
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
                var icon = icons[i.tipo] || 'ri-information-line';
                var color = colors[i.tipo] || '#64748b';
                html += '<div class="lwa-insight-card" data-tipo="' + (i.tipo || '') + '">';
                html += '  <div class="lwa-insight-icon" style="color:' + color + '"><i class="' + icon + '"></i></div>';
                html += '  <div class="lwa-insight-content">';
                html += '    <div class="lwa-insight-title">' + (i.titulo || '') + '</div>';
                html += '    <div class="lwa-insight-desc">' + (i.descricao || '') + '</div>';
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

            var tempClass = l.temperatura ? ' lwa-lead-avatar--' + l.temperatura : ' lwa-lead-avatar--quente';
            var html = '<div class="lwa-modal-lead">';
            html += '<div class="lwa-modal-lead-header">';
            html += '  <div class="lwa-lead-avatar lwa-lead-avatar--lg' + tempClass + '">' + name.charAt(0).toUpperCase() + '</div>';
            html += '  <div class="lwa-modal-lead-info">';
            html += '    <div class="lwa-modal-lead-name">' + name + '</div>';
            html += '    <div class="lwa-modal-lead-role">' + (l.cargo || '') + ' · ' + (l.company || '') + '</div>';
            if (l.email) html += '    <div class="lwa-modal-lead-contact"><i class="ri-mail-line"></i> ' + l.email + '</div>';
            if (l.phone) html += '    <div class="lwa-modal-lead-contact"><i class="ri-phone-line"></i> ' + l.phone + '</div>';
            html += '  </div>';
            if (l.temperatura) html += '  <span class="lwa-badge lwa-badge--' + l.temperatura + '">' + l.temperatura.charAt(0).toUpperCase() + l.temperatura.slice(1) + '</span>';
            html += '</div>';

            // Info grid
            html += '<div class="lwa-modal-grid">';
            html += Mautic.lwaModalField('Origem', l.origem_principal);
            html += Mautic.lwaModalField('Segmento', l.segmento);
            html += Mautic.lwaModalField('Módulo', l.modulo_interesse);
            html += Mautic.lwaModalField('Score', l.interesse_score);
            html += Mautic.lwaModalField('Status', (l.status_comercial || '').replace(/_/g, ' '));
            html += Mautic.lwaModalField('Valor', l.valor_potencial ? 'R$ ' + parseFloat(l.valor_potencial).toLocaleString('pt-BR') : '-');
            html += '</div>';

            if (l.dor_principal) {
                html += '<div class="lwa-modal-section"><label>Dor Principal</label><p>' + l.dor_principal + '</p></div>';
            }

            // Contact
            if (l.email || l.phone) {
                html += '<div class="lwa-modal-section"><label>Contato</label></div>';
                html += '<div class="lwa-modal-contact-actions">';
                if (l.email) html += '<a href="mailto:' + l.email + '" class="lwa-modal-contact-btn"><i class="ri-mail-line"></i> ' + l.email + '</a>';
                if (l.phone) html += '<a href="tel:' + l.phone + '" class="lwa-modal-contact-btn"><i class="ri-phone-line"></i> ' + l.phone + '</a>';
                if (l.phone) html += '<a href="https://wa.me/55' + (l.phone||'').replace(/\\D/g,'') + '" target="_blank" class="lwa-modal-contact-btn"><i class="ri-whatsapp-line"></i> WhatsApp</a>';
                html += '</div>';
            }

            // Status update
            html += '<div class="lwa-modal-section"><label>Etapa do Pipeline</label></div>';
            html += '<div class="lwa-modal-status-bar">';
            var statuses = [
                {v:'prospeccao',l:'Prospecção'},{v:'engajado',l:'Engajado'},{v:'reuniao_agendada',l:'R. Agendada'},
                {v:'reuniao_realizada',l:'R. Realizada'},{v:'reuniao_fechamento',l:'R. Fechamento'},
                {v:'proposta_enviada',l:'Proposta'},{v:'negociacao',l:'Negociação'},{v:'ganho',l:'Ganho'},{v:'perdido',l:'Perdido'}
            ];
            statuses.forEach(function(s) {
                var active = (l.status_comercial === s.v) ? ' lwa-status-btn--active' : '';
                html += '<button class="lwa-status-btn' + active + '" onclick="Mautic.lwaUpdateLead(' + l.id + ',\'status_comercial\',\'' + s.v + '\')">' + s.l + '</button>';
            });
            html += '</div>';

            // Actions
            html += '<div class="lwa-modal-section"><label>Ações</label></div>';
            html += '<div class="lwa-modal-actions">';
            html += '  <button class="lwa-modal-action-btn lwa-modal-action-btn--highlight" onclick="Mautic.lwaEmailLead(' + l.id + ',\'' + (l.email||'').replace(/'/g,"\\'") + '\',\'' + name.replace(/'/g,"\\'") + '\')"><i class="ri-mail-send-line"></i> Enviar E-mail</button>';
            html += '  <button class="lwa-modal-action-btn lwa-modal-action-btn--highlight" onclick="Mautic.lwaScheduleFollowup(' + l.id + ',\'' + name.replace(/'/g,"\\'") + '\')"><i class="ri-calendar-todo-line"></i> Agendar Follow-up</button>';
            html += '  <a href="/s/contacts/view/' + l.id + '" class="lwa-modal-action-btn"><i class="ri-user-line"></i> Ficha Completa</a>';
            html += '  <button class="lwa-modal-action-btn lwa-modal-action-btn--ai" onclick="Mautic.lwaAiSuggest(' + l.id + ')"><i class="ri-sparkling-line"></i> Sugestão IA</button>';
            html += '</div>';

            // Playbook actions
            html += '<div class="lwa-modal-section"><label>Playbooks</label></div>';
            html += '<div class="lwa-modal-actions">';
            html += '  <button class="lwa-modal-action-btn" onclick="Mautic.lwaGeneratePlaybook(' + l.id + ',\'inicial_linkedin\')"><i class="ri-linkedin-box-line"></i> LinkedIn Inicial</button>';
            html += '  <button class="lwa-modal-action-btn" onclick="Mautic.lwaGeneratePlaybook(' + l.id + ',\'checklist\')"><i class="ri-checkbox-line"></i> Checklist</button>';
            html += '  <button class="lwa-modal-action-btn" onclick="Mautic.lwaGeneratePlaybook(' + l.id + ',\'diagnostico\')"><i class="ri-stethoscope-line"></i> Diagnóstico</button>';
            html += '  <button class="lwa-modal-action-btn" onclick="Mautic.lwaGeneratePlaybook(' + l.id + ',\'reativacao\')"><i class="ri-refresh-line"></i> Reativação</button>';
            html += '</div>';

            // Actions extras
            html += '<div class="lwa-modal-actions" style="margin-top:8px">';
            html += '  <button class="lwa-modal-action-btn lwa-modal-action-btn--ai" onclick="Mautic.lwaLeadSummary(' + l.id + ')"><i class="ri-file-list-3-line"></i> Resumo Completo IA</button>';
            html += '  <button class="lwa-modal-action-btn" onclick="Mautic.lwaShowTimeline(' + l.id + ')"><i class="ri-time-line"></i> Timeline</button>';
            html += '</div>';

            // AI suggestion placeholder
            html += '<div id="lwa-ai-result"></div>';

            // Timeline placeholder
            html += '<div id="lwa-timeline-result"></div>';

            html += '</div>';
            mQuery('#lwa-modal-body').html(html);
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
                Mautic.lwaCloseModal();
            } else {
                Mautic.lwaToast(res.error || 'Erro ao enviar');
            }
        },
        error: function() { Mautic.lwaToast('Erro de conexão'); }
    });
};

Mautic.lwaAiEmailSuggest = function(leadId) {
    mQuery('#lwa-email-body').val('Gerando sugestão...');
    mQuery.ajax({
        url: '/s/analytics/ai/suggest/' + leadId,
        dataType: 'json',
        success: function(res) {
            var s = res.suggestion;
            if (s.mensagem_followup) {
                mQuery('#lwa-email-body').val(s.mensagem_followup);
                if (!mQuery('#lwa-email-subject').val()) {
                    mQuery('#lwa-email-subject').val(s.proxima_acao || 'Follow-up');
                }
                Mautic.lwaToast('Mensagem gerada pela IA');
            }
        },
        error: function() { mQuery('#lwa-email-body').val(''); Mautic.lwaToast('Erro ao consultar IA'); }
    });
};

Mautic.lwaScheduleFollowup = function(leadId, name) {
    var tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 2);
    var defaultDate = tomorrow.toISOString().split('T')[0];

    mQuery('#lwa-modal-title').text('Agendar Follow-up');
    var html = '<div class="lwa-form-section">';
    html += '<div class="lwa-form-header"><i class="ri-calendar-todo-line"></i> ' + name + '</div>';
    html += '<div class="lwa-form-group"><label>Data do Follow-up</label><input type="date" id="lwa-followup-date" class="lwa-input" value="' + defaultDate + '"></div>';
    html += '<div class="lwa-form-group"><label>Observação (opcional)</label><textarea id="lwa-followup-note" class="lwa-textarea" rows="3" placeholder="Ex: Enviar proposta revisada, ligar para confirmar reunião..."></textarea></div>';
    html += '<div class="lwa-form-info"><i class="ri-notification-3-line"></i> Você receberá uma notificação 1 dia antes.</div>';
    html += '<div class="lwa-form-actions">';
    html += '  <button class="lwa-btn lwa-btn--primary" onclick="Mautic.lwaConfirmFollowup(' + leadId + ')"><i class="ri-check-line"></i> Agendar</button>';
    html += '  <button class="lwa-btn lwa-btn--ghost" onclick="Mautic.lwaOpenLeadModal(' + leadId + ')">Voltar</button>';
    html += '</div></div>';

    mQuery('#lwa-modal-title').text('Agendar Follow-up');
    mQuery('#lwa-modal-body').html(html);
    mQuery('#lwa-lead-modal').show();
};

Mautic.lwaConfirmFollowup = function(leadId) {
    var date = mQuery('#lwa-followup-date').val();
    var note = mQuery('#lwa-followup-note').val().trim();
    if (!date) { Mautic.lwaToast('Selecione uma data'); return; }

    mQuery.ajax({
        url: '/s/analytics/action/followup',
        method: 'POST',
        data: { lead_id: leadId, date: date, note: note },
        headers: {'X-CSRF-Token': mauticAjaxCsrf},
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                Mautic.lwaToast(res.message);
                Mautic.lwaCloseModal();
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
    $result.html('<div class="lwa-ai-loading"><i class="ri-sparkling-line ri-spin"></i> Consultando IA...</div>');

    mQuery.ajax({
        url: '/s/analytics/ai/suggest/' + leadId,
        dataType: 'json',
        success: function(res) {
            var s = res.suggestion;
            if (s.error) { $result.html('<div class="lwa-ai-error">' + s.error + '</div>'); return; }

            var html = '<div class="lwa-ai-card">';
            html += '<div class="lwa-ai-header"><i class="ri-sparkling-line"></i> Sugestão da IA</div>';
            html += '<div class="lwa-ai-row"><strong>Próxima Ação:</strong> ' + (s.proxima_acao || '-') + '</div>';
            html += '<div class="lwa-ai-row"><strong>Intenção:</strong> ' + (s.intencao || '-') + '</div>';
            html += '<div class="lwa-ai-row"><strong>Probabilidade:</strong> ' + (s.probabilidade_conversao || '-') + '</div>';
            html += '<div class="lwa-ai-row"><strong>Dica:</strong> ' + (s.dica_comercial || '-') + '</div>';
            html += '<div class="lwa-ai-message"><strong>Mensagem sugerida:</strong><p>' + (s.mensagem_followup || '-') + '</p></div>';
            html += '<button class="lwa-modal-action-btn" onclick="Mautic.lwaCopyText(this)" data-text="' + (s.mensagem_followup || '').replace(/"/g, '&quot;') + '"><i class="ri-file-copy-line"></i> Copiar Mensagem</button>';
            html += '</div>';
            $result.html(html);
        },
        error: function() {
            $result.html('<div class="lwa-ai-error">Erro ao consultar IA</div>');
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

Mautic.lwaToast = function(message) {
    var $toast = mQuery('<div class="lwa-toast">' + message + '</div>');
    mQuery('body').append($toast);
    setTimeout(function() { $toast.addClass('lwa-toast--visible'); }, 10);
    setTimeout(function() { $toast.removeClass('lwa-toast--visible'); setTimeout(function() { $toast.remove(); }, 300); }, 3000);
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
    $result.html('<div class="lwa-ai-loading"><i class="ri-file-text-line ri-spin"></i> Gerando mensagem...</div>');

    mQuery.ajax({
        url: '/s/analytics/ai/tab/playbook_' + tipo + '_' + leadId,
        dataType: 'json',
        success: function(res) {
            if (res.error) { $result.html('<div class="lwa-ai-error">' + res.error + '</div>'); return; }

            var html = '<div class="lwa-ai-card">';
            html += '<div class="lwa-ai-header"><i class="ri-file-text-line"></i> Playbook: ' + res.tipo + '</div>';
            html += '<div class="lwa-ai-message"><p style="white-space:pre-line">' + res.message + '</p></div>';
            html += '<div style="display:flex;gap:6px;margin-top:10px">';
            html += '  <button class="lwa-modal-action-btn" onclick="Mautic.lwaCopyText(this)" data-text="' + res.message.replace(/"/g, '&quot;') + '"><i class="ri-file-copy-line"></i> Copiar</button>';
            html += '</div>';
            html += '</div>';
            $result.html(html);
        },
        error: function() { $result.html('<div class="lwa-ai-error">Erro ao gerar playbook.</div>'); }
    });
};

/**
 * Timeline do Lead
 */
Mautic.lwaShowTimeline = function(leadId) {
    var $container = mQuery('#lwa-timeline-result');
    $container.html('<div class="lwa-ai-loading"><i class="ri-time-line ri-spin"></i> Carregando timeline...</div>');

    mQuery.ajax({
        url: '/s/analytics/lead/' + leadId + '?include=timeline',
        dataType: 'json',
        success: function(res) {
            var items = res.timeline || [];
            if (!items.length) { $container.html('<p style="text-align:center;color:var(--lwa-gray-400);padding:12px">Sem histórico.</p>'); return; }

            var icons = { note: 'ri-sticky-note-line', email: 'ri-mail-line', page: 'ri-globe-line', form: 'ri-file-edit-line', download: 'ri-download-2-line' };
            var colors = { note: '#64748b', email: '#3b82f6', page: '#10b981', form: '#7c3aed', download: '#f59e0b' };

            var html = '<div class="lwa-timeline">';
            html += '<div class="lwa-ai-header" style="margin-top:16px"><i class="ri-time-line"></i> Timeline</div>';
            items.forEach(function(item) {
                var icon = icons[item.type] || 'ri-record-circle-line';
                var color = colors[item.type] || '#94a3b8';
                var date = item.date ? new Date(item.date).toLocaleDateString('pt-BR') + ' ' + new Date(item.date).toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'}) : '';
                html += '<div class="lwa-timeline-item">';
                html += '  <div class="lwa-timeline-icon" style="color:' + color + '"><i class="' + icon + '"></i></div>';
                html += '  <div class="lwa-timeline-content">';
                html += '    <div class="lwa-timeline-text">' + (item.text || '') + '</div>';
                html += '    <div class="lwa-timeline-meta">' + date + (item.user ? ' · ' + item.user : '') + '</div>';
                html += '  </div>';
                html += '</div>';
            });
            html += '</div>';
            $container.html(html);
        },
        error: function() { $container.html('<p style="color:#dc2626;padding:12px">Erro ao carregar timeline.</p>'); }
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
        success: function(res) {
            var s = res.summary || {};
            if (s.error) { $result.html('<div class="lwa-ai-error">' + s.error + '</div>'); return; }

            var html = '<div class="lwa-ai-card">';
            html += '<div class="lwa-ai-header"><i class="ri-file-list-3-line"></i> Resumo Consolidado</div>';
            if (s.resumo) html += '<div class="lwa-ai-row" style="margin-bottom:12px">' + s.resumo + '</div>';

            if (s.dores_identificadas && s.dores_identificadas.length) {
                html += '<div class="lwa-ai-row"><strong>Dores:</strong> ' + s.dores_identificadas.join(', ') + '</div>';
            }
            if (s.objecoes && s.objecoes.length) {
                html += '<div class="lwa-ai-row"><strong>Objeções:</strong> ' + s.objecoes.join(', ') + '</div>';
            }
            if (s.proxima_acao) html += '<div class="lwa-ai-row"><strong>Próxima Ação:</strong> ' + s.proxima_acao + '</div>';
            if (s.justificativa_acao) html += '<div class="lwa-ai-row"><strong>Justificativa:</strong> ' + s.justificativa_acao + '</div>';
            if (s.probabilidade) html += '<div class="lwa-ai-row"><strong>Probabilidade:</strong> ' + s.probabilidade + '</div>';

            if (s.mensagem_sugerida) {
                html += '<div class="lwa-ai-message"><strong>Mensagem sugerida:</strong><p>' + s.mensagem_sugerida + '</p></div>';
                html += '<button class="lwa-modal-action-btn" onclick="Mautic.lwaCopyText(this)" data-text="' + (s.mensagem_sugerida || '').replace(/"/g, '&quot;') + '"><i class="ri-file-copy-line"></i> Copiar</button>';
            }

            html += '</div>';
            $result.html(html);
        },
        error: function() { $result.html('<div class="lwa-ai-error">Erro ao gerar resumo.</div>'); }
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
 * Utility
 */
Mautic.lwaHexToRgba = function(hex, alpha) {
    var r = parseInt(hex.slice(1, 3), 16);
    var g = parseInt(hex.slice(3, 5), 16);
    var b = parseInt(hex.slice(5, 7), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
};
