<?php

declare(strict_types=1);

/**
 * Scoring event catalog for interesse_score (M-004).
 *
 * repeatable=false: unique per (lead_id, event_key) — or per source_id when applicable.
 * repeatable=true:  allows multiple applications (e.g. per email/page hit via source_id).
 */
return [
    // LinkedIn / Roboze
    'linkedin.import' => [
        'key'        => 'linkedin.import',
        'label'      => 'Contato importado do Roboze',
        'delta'      => 2,
        'category'   => 'linkedin',
        'repeatable' => false,
    ],
    'linkedin.connection_accepted' => [
        'key'        => 'linkedin.connection_accepted',
        'label'      => 'Aceitou conexão',
        'delta'      => 5,
        'category'   => 'linkedin',
        'repeatable' => false,
    ],
    'linkedin.message_replied' => [
        'key'        => 'linkedin.message_replied',
        'label'      => 'Respondeu mensagem',
        'delta'      => 15,
        'category'   => 'linkedin',
        'repeatable' => false,
    ],
    'linkedin.pain_identified' => [
        'key'        => 'linkedin.pain_identified',
        'label'      => 'Resposta com dor identificada',
        'delta'      => 30,
        'category'   => 'linkedin',
        'repeatable' => false,
    ],
    'linkedin.more_info_requested' => [
        'key'        => 'linkedin.more_info_requested',
        'label'      => 'Pediu mais informações',
        'delta'      => 40,
        'category'   => 'linkedin',
        'repeatable' => false,
    ],
    'linkedin.meeting_accepted' => [
        'key'        => 'linkedin.meeting_accepted',
        'label'      => 'Aceitou diagnóstico/reunião',
        'delta'      => 60,
        'category'   => 'linkedin',
        'repeatable' => false,
    ],
    'linkedin.no_reply_3_attempts' => [
        'key'        => 'linkedin.no_reply_3_attempts',
        'label'      => 'Não respondeu após 3 abordagens',
        'delta'      => -10,
        'category'   => 'linkedin',
        'repeatable' => false,
    ],
    'linkedin.not_interested' => [
        'key'        => 'linkedin.not_interested',
        'label'      => 'Disse que não tem interesse',
        'delta'      => -40,
        'category'   => 'linkedin',
        'repeatable' => false,
    ],

    // Mautic behavior
    'mautic.email.open' => [
        'key'        => 'mautic.email.open',
        'label'      => 'Abriu e-mail',
        'delta'      => 2,
        'category'   => 'mautic',
        'repeatable' => true,
    ],
    'mautic.email.click' => [
        'key'        => 'mautic.email.click',
        'label'      => 'Clicou em e-mail',
        'delta'      => 5,
        'category'   => 'mautic',
        'repeatable' => true,
    ],
    'mautic.page.product' => [
        'key'        => 'mautic.page.product',
        'label'      => 'Visitou página do produto',
        'delta'      => 8,
        'category'   => 'mautic',
        'repeatable' => true,
    ],
    'mautic.page.pricing' => [
        'key'        => 'mautic.page.pricing',
        'label'      => 'Visitou página de preços',
        'delta'      => 20,
        'category'   => 'mautic',
        'repeatable' => true,
    ],
    'mautic.form.submit' => [
        'key'        => 'mautic.form.submit',
        'label'      => 'Preencheu formulário',
        'delta'      => 30,
        'category'   => 'mautic',
        'repeatable' => true,
    ],
    'mautic.asset.download' => [
        'key'        => 'mautic.asset.download',
        'label'      => 'Baixou material',
        'delta'      => 15,
        'category'   => 'mautic',
        'repeatable' => true,
    ],
    'mautic.campaign.reply' => [
        'key'        => 'mautic.campaign.reply',
        'label'      => 'Respondeu campanha',
        'delta'      => 25,
        'category'   => 'mautic',
        'repeatable' => true,
    ],
    'mautic.inactivity.30d' => [
        'key'        => 'mautic.inactivity.30d',
        'label'      => 'Sem interação por 30 dias',
        'delta'      => -10,
        'category'   => 'mautic',
        'repeatable' => false,
    ],
    'mautic.inactivity.60d' => [
        'key'        => 'mautic.inactivity.60d',
        'label'      => 'Sem interação por 60 dias',
        'delta'      => -20,
        'category'   => 'mautic',
        'repeatable' => false,
    ],
    'mautic.email.unsubscribe' => [
        'key'        => 'mautic.email.unsubscribe',
        'label'      => 'Descadastrou de e-mail',
        'delta'      => -40,
        'category'   => 'mautic',
        'repeatable' => false,
    ],

    // LetsWork commercial events (webhook M-009)
    'letswork.meeting.scheduled' => [
        'key'        => 'letswork.meeting.scheduled',
        'label'      => 'Reunião agendada',
        'delta'      => 50,
        'category'   => 'letswork',
        'repeatable' => false,
    ],
    'letswork.meeting.completed' => [
        'key'        => 'letswork.meeting.completed',
        'label'      => 'Reunião realizada',
        'delta'      => 70,
        'category'   => 'letswork',
        'repeatable' => false,
    ],
    'letswork.meeting.transcribed' => [
        'key'        => 'letswork.meeting.transcribed',
        'label'      => 'Reunião transcrita com resumo',
        'delta'      => 10,
        'category'   => 'letswork',
        'repeatable' => false,
    ],
    'letswork.meeting.pain_identified' => [
        'key'        => 'letswork.meeting.pain_identified',
        'label'      => 'Dor clara identificada em reunião',
        'delta'      => 30,
        'category'   => 'letswork',
        'repeatable' => false,
    ],
    'letswork.meeting.objection_identified' => [
        'key'        => 'letswork.meeting.objection_identified',
        'label'      => 'Objeção tratável identificada em reunião',
        'delta'      => 15,
        'category'   => 'letswork',
        'repeatable' => false,
    ],
    'letswork.meeting.next_step_defined' => [
        'key'        => 'letswork.meeting.next_step_defined',
        'label'      => 'Próximo passo com data definida',
        'delta'      => 40,
        'category'   => 'letswork',
        'repeatable' => false,
    ],
    'letswork.proposal.sent' => [
        'key'        => 'letswork.proposal.sent',
        'label'      => 'Proposta enviada',
        'delta'      => 90,
        'category'   => 'letswork',
        'repeatable' => false,
    ],
    'letswork.negotiation.active' => [
        'key'        => 'letswork.negotiation.active',
        'label'      => 'Negociação ativa',
        'delta'      => 100,
        'category'   => 'letswork',
        'repeatable' => false,
    ],
    'letswork.deal.won' => [
        'key'        => 'letswork.deal.won',
        'label'      => 'Cliente ganho',
        'delta'      => 200,
        'category'   => 'letswork',
        'repeatable' => false,
    ],
    'letswork.deal.lost' => [
        'key'        => 'letswork.deal.lost',
        'label'      => 'Perdido',
        'delta'      => -100,
        'category'   => 'letswork',
        'repeatable' => false,
    ],
];
