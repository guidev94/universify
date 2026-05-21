/**
 * Letswork Analytics Dashboard — Premium interactions
 */

Mautic.letsworkAnalyticsOnLoad = function(container) {
    Mautic.lwaRenderCharts();
    Mautic.lwaAnimateKpis();

    // Close panel on Escape
    mQuery(document).on('keydown.lwa', function(e) {
        if (e.key === 'Escape') Mautic.lwaCloseKpiDetail();
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
    var colors = data.colors || ['#dc2626', '#10b981', '#f59e0b', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];

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
    mQuery('#lwa-kpi-detail-body').html('<div class="lwa-panel-loading"><div class="lwa-spinner"></div><span>Carregando leads...</span></div>');
    mQuery('#lwa-kpi-pagination').html('');
    $panel.show();

    var dateFrom = mQuery('input[name="dateFrom"]').val() || '';
    var dateTo = mQuery('input[name="dateTo"]').val() || '';

    mQuery.ajax({
        url: '/s/analytics/kpi-detail/' + kpiId,
        data: { dateFrom: dateFrom, dateTo: dateTo },
        dataType: 'json',
        success: function(response) {
            if (response.leads && response.leads.length > 0) {
                Mautic.lwaKpiLeads = response.leads;
                Mautic.lwaRenderKpiPage();
            } else {
                mQuery('#lwa-kpi-detail-body').html('<div class="lwa-empty-state"><i class="ri-inbox-line"></i><p>Nenhum lead encontrado neste período</p></div>');
            }
        },
        error: function() {
            mQuery('#lwa-kpi-detail-body').html('<div class="lwa-empty-state"><p>Erro ao carregar dados</p></div>');
        }
    });
};

Mautic.lwaRenderKpiPage = function() {
    var leads = Mautic.lwaKpiLeads;
    var page = Mautic.lwaKpiPage;
    var perPage = Mautic.lwaKpiPerPage;
    var totalPages = Math.ceil(leads.length / perPage);
    var start = (page - 1) * perPage;
    var pageLeads = leads.slice(start, start + perPage);

    var html = '<div class="lwa-kd-header-bar">';
    html += '  <span class="lwa-kd-count"><strong>' + leads.length + '</strong> leads encontrados</span>';
    html += '  <span class="lwa-kd-page">Página ' + page + '/' + totalPages + '</span>';
    html += '</div>';
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

        html += '<div class="lwa-kd-card" onclick="Mautic.lwaOpenLeadModal(' + lead.id + ')" style="animation-delay:' + (idx * 0.04) + 's">';
        html += '  <div class="lwa-kd-card-top">';
        html += '    <div class="lwa-kd-avatar" style="background:' + avatarBg + '">' + initial + '</div>';
        html += '    <div class="lwa-kd-info">';
        html += '      <div class="lwa-kd-name">' + name + '</div>';
        html += '      <div class="lwa-kd-role">' + cargo + (cargo && company ? ' · ' : '') + company + '</div>';
        html += '    </div>';
        html += '    <span class="lwa-badge lwa-badge--' + temp + '">' + (temp ? temp.charAt(0).toUpperCase() + temp.slice(1) : '-') + '</span>';
        html += '  </div>';
        html += '  <div class="lwa-kd-card-bottom">';
        html += '    <span class="lwa-kd-tag"><i class="ri-bar-chart-fill"></i> ' + score + '</span>';
        if (origin) html += '    <span class="lwa-kd-tag"><i class="ri-global-line"></i> ' + origin + '</span>';
        if (status) html += '    <span class="lwa-kd-tag"><i class="ri-flag-line"></i> ' + status + '</span>';
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

Mautic.lwaCloseKpiDetail = function() {
    mQuery('#lwa-kpi-detail').hide();
};

/**
 * Lead Modal — Full detail + Actions
 */
Mautic.lwaOpenLeadModal = function(leadId) {
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

            // Contact actions
            html += '<div class="lwa-modal-section"><label>Contato Direto</label></div>';
            html += '<div class="lwa-modal-contact-actions">';
            if (l.email) html += '  <a href="mailto:' + l.email + '" class="lwa-modal-contact-btn lwa-modal-contact-btn--email"><i class="ri-mail-line"></i> ' + l.email + '</a>';
            if (l.phone) html += '  <a href="tel:' + l.phone + '" class="lwa-modal-contact-btn lwa-modal-contact-btn--phone"><i class="ri-phone-line"></i> ' + l.phone + '</a>';
            if (l.phone) html += '  <a href="https://wa.me/55' + (l.phone||'').replace(/\D/g,'') + '" target="_blank" class="lwa-modal-contact-btn lwa-modal-contact-btn--whatsapp"><i class="ri-whatsapp-line"></i> WhatsApp</a>';
            html += '</div>';

            // Main Actions
            html += '<div class="lwa-modal-section"><label>Ações</label></div>';
            html += '<div class="lwa-modal-actions">';
            html += '  <button class="lwa-modal-action-btn lwa-modal-action-btn--highlight" onclick="Mautic.lwaEmailLead(' + l.id + ', \'' + (l.email||'').replace(/'/g,"\\'") + '\', \'' + name.replace(/'/g,"\\'") + '\')"><i class="ri-mail-send-line"></i> Enviar E-mail</button>';
            html += '  <button class="lwa-modal-action-btn lwa-modal-action-btn--highlight" onclick="Mautic.lwaScheduleFollowup(' + l.id + ', \'' + name.replace(/'/g,"\\'") + '\')"><i class="ri-calendar-todo-line"></i> Agendar Follow-up</button>';
            html += '  <a href="/s/contacts/view/' + l.id + '" class="lwa-modal-action-btn"><i class="ri-user-line"></i> Ver Ficha</a>';
            html += '  <a href="/s/contacts/edit/' + l.id + '" class="lwa-modal-action-btn"><i class="ri-edit-line"></i> Editar</a>';
            html += '  <button class="lwa-modal-action-btn" onclick="Mautic.lwaToast(\'' + name.replace(/'/g,"\\'") + ' enviado para CRM LetsWork\')"><i class="ri-send-plane-line"></i> Enviar para CRM</button>';
            html += '  <button class="lwa-modal-action-btn lwa-modal-action-btn--ai" onclick="Mautic.lwaAiSuggest(' + l.id + ')"><i class="ri-sparkling-line"></i> Sugestão IA</button>';
            html += '</div>';

            // AI suggestion placeholder
            html += '<div id="lwa-ai-result"></div>';

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

    var html = '<div class="lwa-form-section">';
    html += '<div class="lwa-form-header"><i class="ri-mail-send-line"></i> Enviar E-mail para ' + name + '</div>';
    html += '<div class="lwa-form-meta"><i class="ri-mail-line"></i> ' + email + '</div>';
    html += '<div class="lwa-form-group"><label>Assunto</label><input type="text" id="lwa-email-subject" class="lwa-input" placeholder="Assunto do e-mail"></div>';
    html += '<div class="lwa-form-group"><label>Mensagem</label><textarea id="lwa-email-body" class="lwa-textarea" rows="6" placeholder="Escreva sua mensagem..."></textarea></div>';
    html += '<div class="lwa-form-actions">';
    html += '  <button class="lwa-btn lwa-btn--primary" onclick="Mautic.lwaSendEmail(' + leadId + ')"><i class="ri-send-plane-line"></i> Enviar</button>';
    html += '  <button class="lwa-btn lwa-btn--ai" onclick="Mautic.lwaAiEmailSuggest(' + leadId + ')"><i class="ri-sparkling-line"></i> Gerar com IA</button>';
    html += '  <button class="lwa-btn lwa-btn--ghost" onclick="Mautic.lwaCloseModal()">Cancelar</button>';
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

    var html = '<div class="lwa-form-section">';
    html += '<div class="lwa-form-header"><i class="ri-calendar-todo-line"></i> Agendar Follow-up — ' + name + '</div>';
    html += '<div class="lwa-form-group"><label>Data do Follow-up</label><input type="date" id="lwa-followup-date" class="lwa-input" value="' + defaultDate + '"></div>';
    html += '<div class="lwa-form-group"><label>Observação (opcional)</label><textarea id="lwa-followup-note" class="lwa-textarea" rows="3" placeholder="Ex: Enviar proposta revisada, ligar para confirmar reunião..."></textarea></div>';
    html += '<div class="lwa-form-info"><i class="ri-notification-3-line"></i> Você receberá uma notificação 1 dia antes do follow-up.</div>';
    html += '<div class="lwa-form-actions">';
    html += '  <button class="lwa-btn lwa-btn--primary" onclick="Mautic.lwaConfirmFollowup(' + leadId + ')"><i class="ri-check-line"></i> Agendar</button>';
    html += '  <button class="lwa-btn lwa-btn--ghost" onclick="Mautic.lwaCloseModal()">Cancelar</button>';
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
 * Utility
 */
Mautic.lwaHexToRgba = function(hex, alpha) {
    var r = parseInt(hex.slice(1, 3), 16);
    var g = parseInt(hex.slice(3, 5), 16);
    var b = parseInt(hex.slice(5, 7), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
};
