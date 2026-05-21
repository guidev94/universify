<?php

namespace MauticPlugin\LetsworkAnalyticsBundle\Model;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class AnalyticsModel
{
    private Connection $connection;

    public function __construct(EntityManagerInterface $em)
    {
        $this->connection = $em->getConnection();
    }

    public function getOverviewData(array $dateRange): array
    {
        return [
            'kpis' => [
                'prospeccao'        => $this->getCountByStatus($dateRange, 'prospeccao'),
                'engajados'         => $this->getCountByStatus($dateRange, 'engajado'),
                'reunioes'          => $this->getReunioesCount($dateRange),
                'propostas'         => $this->getCountByStatus($dateRange, 'proposta_enviada'),
                'ganhos'            => $this->getCountByStatus($dateRange, 'ganho'),
                'hot_no_followup'   => $this->getHotWithoutFollowup(),
                'emails_enviados'   => $this->getEmailsSent($dateRange),
                'emails_abertos'    => $this->getEmailsOpened($dateRange),
                'taxa_abertura'     => $this->getEmailOpenRate($dateRange),
            ],
            'charts' => [
                'temperature' => $this->getTemperatureDistribution(),
                'leads_time'  => $this->getLeadsOverTime($dateRange),
                'emails_time' => $this->getEmailsOverTime($dateRange),
            ],
            'tables' => [
                'hot_leads' => $this->getHotLeadsTable(),
            ],
        ];
    }

    public function getConversaoData(array $dateRange): array
    {
        return [
            'funnel'    => $this->getConversionFunnel($dateRange),
            'by_origin' => $this->getConversionByOrigin($dateRange),
            'win_rate'  => $this->getWinRateByOrigin($dateRange),
        ];
    }

    public function getSegmentosData(array $dateRange): array
    {
        return [
            'performance' => $this->getSegmentPerformance($dateRange),
            'ranking'     => $this->getSegmentRanking($dateRange),
        ];
    }

    public function getPipelineData(array $dateRange): array
    {
        return [
            'temperature'     => $this->getTemperatureDistribution(),
            'avg_to_meeting'  => $this->getAvgTimeToMeeting($dateRange),
            'avg_to_proposal' => $this->getAvgTimeToProposal($dateRange),
            'score_progress'  => $this->getScoreProgression($dateRange),
            'valor_pipeline'  => $this->getValorPipeline(),
            'by_module'       => $this->getLeadsByModule($dateRange),
            'by_status'       => $this->getLeadsByStatus($dateRange),
        ];
    }

    public function getPerdasData(array $dateRange): array
    {
        return [
            'reasons'    => $this->getLossReasons($dateRange),
            'by_origin'  => $this->getLossByOrigin($dateRange),
            'by_segment' => $this->getLossBySegment($dateRange),
            'trend'      => $this->getLossTrend($dateRange),
        ];
    }

    public function getLeadsByKpi(string $kpi, array $dateRange): array
    {
        $where = '';
        $params = ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59'];

        switch ($kpi) {
            case 'prospeccao':
                $where = "status_comercial = 'prospeccao' AND date_added BETWEEN :dateFrom AND :dateTo";
                break;
            case 'engajados':
                $where = "status_comercial = 'engajado' AND date_modified BETWEEN :dateFrom AND :dateTo";
                break;
            case 'reunioes':
                $where = "status_comercial IN ('reuniao_agendada','reuniao_realizada') AND date_modified BETWEEN :dateFrom AND :dateTo";
                break;
            case 'propostas':
                $where = "status_comercial = 'proposta_enviada' AND date_modified BETWEEN :dateFrom AND :dateTo";
                break;
            case 'ganhos':
                $where = "status_comercial = 'ganho' AND date_modified BETWEEN :dateFrom AND :dateTo";
                break;
            case 'hot_no_followup':
                $where = "temperatura IN ('quente','prioritario') AND (data_proximo_followup IS NULL OR data_proximo_followup < NOW()) AND (status_comercial IS NULL OR status_comercial NOT IN ('ganho','perdido'))";
                $params = [];
                break;
            case 'emails_enviados':
                return $this->getEmailLeads($dateRange, false);
            case 'emails_abertos':
                return $this->getEmailLeads($dateRange, true);
            case 'emails_taxa':
                return $this->getEmailLeads($dateRange, false);
            default:
                return [];
        }

        $sql = "SELECT id, firstname, lastname, email, company, temperatura, interesse_score, status_comercial, origem_principal, data_ultima_interacao, cargo
                FROM leads WHERE $where ORDER BY interesse_score DESC LIMIT 50";
        return $this->connection->fetchAllAssociative($sql, $params);
    }

    // --- KPI Queries ---

    private function getCountByStatus(array $dateRange, string $status): int
    {
        if (!$this->columnExists('leads', 'status_comercial')) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM leads WHERE status_comercial = :status AND date_added BETWEEN :dateFrom AND :dateTo";
        return (int) $this->connection->fetchOne($sql, [
            'status'   => $status,
            'dateFrom' => $dateRange['from'],
            'dateTo'   => $dateRange['to'] . ' 23:59:59',
        ]);
    }

    private function getReunioesCount(array $dateRange): int
    {
        if (!$this->columnExists('leads', 'status_comercial')) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM leads WHERE status_comercial IN ('reuniao_agendada','reuniao_realizada') AND date_modified BETWEEN :dateFrom AND :dateTo";
        return (int) $this->connection->fetchOne($sql, [
            'dateFrom' => $dateRange['from'],
            'dateTo'   => $dateRange['to'] . ' 23:59:59',
        ]);
    }

    private function getHotWithoutFollowup(): int
    {
        if (!$this->columnExists('leads', 'temperatura')) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM leads WHERE temperatura IN ('quente','prioritario') AND (data_proximo_followup IS NULL OR data_proximo_followup < NOW()) AND (status_comercial IS NULL OR status_comercial NOT IN ('ganho','perdido'))";
        return (int) $this->connection->fetchOne($sql);
    }

    private function getEmailsSent(array $dateRange): int
    {
        $sql = "SELECT COUNT(*) FROM email_stats WHERE date_sent BETWEEN :dateFrom AND :dateTo";
        return (int) $this->connection->fetchOne($sql, [
            'dateFrom' => $dateRange['from'],
            'dateTo'   => $dateRange['to'] . ' 23:59:59',
        ]);
    }

    private function getEmailsOpened(array $dateRange): int
    {
        $sql = "SELECT COUNT(*) FROM email_stats WHERE is_read = 1 AND date_sent BETWEEN :dateFrom AND :dateTo";
        return (int) $this->connection->fetchOne($sql, [
            'dateFrom' => $dateRange['from'],
            'dateTo'   => $dateRange['to'] . ' 23:59:59',
        ]);
    }

    private function getEmailOpenRate(array $dateRange): float
    {
        $sent = $this->getEmailsSent($dateRange);
        if ($sent === 0) return 0;
        $opened = $this->getEmailsOpened($dateRange);
        return round(($opened / $sent) * 100, 1);
    }

    // --- Chart Queries ---

    private function getTemperatureDistribution(): array
    {
        if (!$this->columnExists('leads', 'temperatura')) {
            return ['labels' => ['Frio', 'Morno', 'Quente', 'Prioritário'], 'data' => [0, 0, 0, 0]];
        }
        $sql = "SELECT temperatura, COUNT(*) as total FROM leads WHERE temperatura IS NOT NULL AND temperatura != '' GROUP BY temperatura";
        $rows = $this->connection->fetchAllAssociative($sql);

        $map = ['frio' => 0, 'morno' => 0, 'quente' => 0, 'prioritario' => 0];
        foreach ($rows as $row) {
            $key = strtolower(trim($row['temperatura']));
            if (isset($map[$key])) {
                $map[$key] = (int) $row['total'];
            }
        }

        return [
            'labels' => ['Frio', 'Morno', 'Quente', 'Prioritário'],
            'data'   => array_values($map),
            'colors' => ['#3b82f6', '#f59e0b', '#dc2626', '#9333ea'],
        ];
    }

    private function getLeadsOverTime(array $dateRange): array
    {
        $sql = "SELECT DATE(date_added) as dia, COUNT(*) as total FROM leads WHERE date_added BETWEEN :dateFrom AND :dateTo GROUP BY DATE(date_added) ORDER BY dia ASC";
        $rows = $this->connection->fetchAllAssociative($sql, [
            'dateFrom' => $dateRange['from'],
            'dateTo'   => $dateRange['to'] . ' 23:59:59',
        ]);

        $labels = [];
        $data = [];
        foreach ($rows as $row) {
            $labels[] = date('d/m', strtotime($row['dia']));
            $data[] = (int) $row['total'];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function getEmailsOverTime(array $dateRange): array
    {
        $sql = "SELECT DATE(date_sent) as dia, COUNT(*) as enviados, SUM(is_read) as abertos FROM email_stats WHERE date_sent BETWEEN :dateFrom AND :dateTo GROUP BY DATE(date_sent) ORDER BY dia ASC";
        $rows = $this->connection->fetchAllAssociative($sql, [
            'dateFrom' => $dateRange['from'],
            'dateTo'   => $dateRange['to'] . ' 23:59:59',
        ]);

        $labels = [];
        $enviados = [];
        $abertos = [];
        foreach ($rows as $row) {
            $labels[] = date('d/m', strtotime($row['dia']));
            $enviados[] = (int) $row['enviados'];
            $abertos[] = (int) $row['abertos'];
        }

        return ['labels' => $labels, 'datasets' => [
            ['label' => 'Enviados', 'data' => $enviados],
            ['label' => 'Abertos', 'data' => $abertos],
        ]];
    }

    // --- Conversão Queries ---

    private function getConversionFunnel(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'status_comercial')) {
            return ['labels' => [], 'data' => []];
        }
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN temperatura IN ('morno','quente','prioritario') THEN 1 ELSE 0 END) as engajados,
                    SUM(CASE WHEN status_comercial IN ('reuniao_agendada','reuniao_realizada') THEN 1 ELSE 0 END) as reunioes,
                    SUM(CASE WHEN status_comercial IN ('proposta_enviada','negociacao') THEN 1 ELSE 0 END) as propostas,
                    SUM(CASE WHEN status_comercial = 'ganho' THEN 1 ELSE 0 END) as ganhos
                FROM leads WHERE date_added BETWEEN :dateFrom AND :dateTo";
        $row = $this->connection->fetchAssociative($sql, [
            'dateFrom' => $dateRange['from'],
            'dateTo'   => $dateRange['to'] . ' 23:59:59',
        ]);

        return [
            'labels' => ['Leads', 'Engajados', 'Reuniões', 'Propostas', 'Ganhos'],
            'data'   => [(int)($row['total']??0), (int)($row['engajados']??0), (int)($row['reunioes']??0), (int)($row['propostas']??0), (int)($row['ganhos']??0)],
        ];
    }

    private function getConversionByOrigin(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'origem_principal')) return [];
        $sql = "SELECT origem_principal, COUNT(*) as total,
                    SUM(CASE WHEN status_comercial = 'ganho' THEN 1 ELSE 0 END) as ganhos
                FROM leads WHERE date_added BETWEEN :dateFrom AND :dateTo AND origem_principal IS NOT NULL AND origem_principal != '' GROUP BY origem_principal ORDER BY total DESC LIMIT 10";
        return $this->connection->fetchAllAssociative($sql, [
            'dateFrom' => $dateRange['from'],
            'dateTo'   => $dateRange['to'] . ' 23:59:59',
        ]);
    }

    private function getWinRateByOrigin(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'origem_principal') || !$this->columnExists('leads', 'status_comercial')) return ['labels' => [], 'data' => []];
        $sql = "SELECT origem_principal, COUNT(*) as total, SUM(CASE WHEN status_comercial = 'ganho' THEN 1 ELSE 0 END) as ganhos
                FROM leads WHERE status_comercial IN ('ganho','perdido') AND date_modified BETWEEN :dateFrom AND :dateTo AND origem_principal IS NOT NULL AND origem_principal != '' GROUP BY origem_principal ORDER BY total DESC LIMIT 8";
        $rows = $this->connection->fetchAllAssociative($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']);

        $labels = [];
        $data = [];
        foreach ($rows as $row) {
            $labels[] = $row['origem_principal'];
            $total = (int)$row['total'];
            $data[] = $total > 0 ? round(((int)$row['ganhos'] / $total) * 100, 1) : 0;
        }
        return ['labels' => $labels, 'data' => $data];
    }

    // --- Segmentos ---

    private function getSegmentPerformance(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'segmento')) return [];
        $sql = "SELECT segmento, COUNT(*) as total,
                    SUM(CASE WHEN status_comercial = 'ganho' THEN 1 ELSE 0 END) as ganhos,
                    SUM(CASE WHEN temperatura IN ('quente','prioritario') THEN 1 ELSE 0 END) as quentes
                FROM leads WHERE date_added BETWEEN :dateFrom AND :dateTo AND segmento IS NOT NULL AND segmento != '' GROUP BY segmento ORDER BY total DESC";
        return $this->connection->fetchAllAssociative($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']);
    }

    private function getSegmentRanking(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'segmento') || !$this->columnExists('leads', 'status_comercial')) return ['labels' => [], 'data' => []];
        $sql = "SELECT segmento, ROUND((SUM(CASE WHEN status_comercial = 'ganho' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as taxa
                FROM leads WHERE date_added BETWEEN :dateFrom AND :dateTo AND segmento IS NOT NULL AND segmento != '' GROUP BY segmento HAVING COUNT(*) >= 2 ORDER BY taxa DESC LIMIT 8";
        $rows = $this->connection->fetchAllAssociative($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']);
        $labels = []; $data = [];
        foreach ($rows as $row) { $labels[] = $row['segmento']; $data[] = (float)$row['taxa']; }
        return ['labels' => $labels, 'data' => $data];
    }

    // --- Pipeline ---

    private function getAvgTimeToMeeting(array $dateRange): float
    {
        if (!$this->columnExists('leads', 'status_comercial')) return 0;
        $sql = "SELECT AVG(DATEDIFF(date_modified, date_added)) as avg_days FROM leads WHERE status_comercial IN ('reuniao_agendada','reuniao_realizada') AND date_modified BETWEEN :dateFrom AND :dateTo";
        return round((float)($this->connection->fetchOne($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']) ?: 0), 1);
    }

    private function getAvgTimeToProposal(array $dateRange): float
    {
        if (!$this->columnExists('leads', 'status_comercial')) return 0;
        $sql = "SELECT AVG(DATEDIFF(date_modified, date_added)) as avg_days FROM leads WHERE status_comercial IN ('proposta_enviada','negociacao') AND date_modified BETWEEN :dateFrom AND :dateTo";
        return round((float)($this->connection->fetchOne($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']) ?: 0), 1);
    }

    private function getScoreProgression(array $dateRange): array
    {
        $sql = "SELECT DATE(date_added) as dia, ROUND(AVG(points), 0) as avg_score FROM leads WHERE date_added BETWEEN :dateFrom AND :dateTo GROUP BY DATE(date_added) ORDER BY dia ASC";
        $rows = $this->connection->fetchAllAssociative($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']);
        $labels = []; $data = [];
        foreach ($rows as $row) { $labels[] = date('d/m', strtotime($row['dia'])); $data[] = (int)$row['avg_score']; }
        return ['labels' => $labels, 'data' => $data];
    }

    private function getValorPipeline(): float
    {
        if (!$this->columnExists('leads', 'valor_potencial') || !$this->columnExists('leads', 'status_comercial')) return 0;
        $sql = "SELECT COALESCE(SUM(valor_potencial), 0) FROM leads WHERE status_comercial IN ('reuniao_agendada','reuniao_realizada','proposta_enviada','negociacao')";
        return (float)$this->connection->fetchOne($sql);
    }

    private function getLeadsByModule(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'modulo_interesse')) return ['labels' => [], 'data' => []];
        $sql = "SELECT modulo_interesse, COUNT(*) as total FROM leads WHERE modulo_interesse IS NOT NULL AND modulo_interesse != '' AND date_added BETWEEN :dateFrom AND :dateTo GROUP BY modulo_interesse ORDER BY total DESC";
        $rows = $this->connection->fetchAllAssociative($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']);
        $labels = []; $data = [];
        foreach ($rows as $row) { $labels[] = $row['modulo_interesse']; $data[] = (int)$row['total']; }
        return ['labels' => $labels, 'data' => $data];
    }

    private function getLeadsByStatus(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'status_comercial')) return [];
        $sql = "SELECT status_comercial, COUNT(*) as total, COALESCE(SUM(valor_potencial),0) as valor FROM leads WHERE status_comercial IS NOT NULL AND status_comercial != '' AND date_added BETWEEN :dateFrom AND :dateTo GROUP BY status_comercial ORDER BY total DESC";
        return $this->connection->fetchAllAssociative($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']);
    }

    // --- Perdas ---

    private function getLossReasons(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'motivo_perda')) return ['labels' => [], 'data' => []];
        $sql = "SELECT motivo_perda, COUNT(*) as total FROM leads WHERE status_comercial = 'perdido' AND motivo_perda IS NOT NULL AND motivo_perda != '' AND date_modified BETWEEN :dateFrom AND :dateTo GROUP BY motivo_perda ORDER BY total DESC LIMIT 8";
        $rows = $this->connection->fetchAllAssociative($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']);
        $labels = []; $data = [];
        foreach ($rows as $row) { $labels[] = $row['motivo_perda']; $data[] = (int)$row['total']; }
        return ['labels' => $labels, 'data' => $data];
    }

    private function getLossByOrigin(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'origem_principal') || !$this->columnExists('leads', 'status_comercial')) return [];
        $sql = "SELECT origem_principal, COUNT(*) as total FROM leads WHERE status_comercial = 'perdido' AND origem_principal IS NOT NULL AND origem_principal != '' AND date_modified BETWEEN :dateFrom AND :dateTo GROUP BY origem_principal ORDER BY total DESC";
        return $this->connection->fetchAllAssociative($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']);
    }

    private function getLossBySegment(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'segmento') || !$this->columnExists('leads', 'status_comercial')) return [];
        $sql = "SELECT segmento, COUNT(*) as total FROM leads WHERE status_comercial = 'perdido' AND segmento IS NOT NULL AND segmento != '' AND date_modified BETWEEN :dateFrom AND :dateTo GROUP BY segmento ORDER BY total DESC";
        return $this->connection->fetchAllAssociative($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']);
    }

    private function getLossTrend(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'status_comercial')) return ['labels' => [], 'data' => []];
        $sql = "SELECT DATE(date_modified) as dia, COUNT(*) as total FROM leads WHERE status_comercial = 'perdido' AND date_modified BETWEEN :dateFrom AND :dateTo GROUP BY DATE(date_modified) ORDER BY dia ASC";
        $rows = $this->connection->fetchAllAssociative($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']);
        $labels = []; $data = [];
        foreach ($rows as $row) { $labels[] = date('d/m', strtotime($row['dia'])); $data[] = (int)$row['total']; }
        return ['labels' => $labels, 'data' => $data];
    }

    // --- Table ---

    private function getHotLeadsTable(): array
    {
        if (!$this->columnExists('leads', 'temperatura')) return [];
        $sql = "SELECT id, firstname, lastname, company, temperatura, COALESCE(interesse_score, points) as score, data_ultima_interacao, cargo
                FROM leads WHERE temperatura IN ('quente','prioritario') AND (status_comercial IS NULL OR status_comercial NOT IN ('ganho','perdido'))
                ORDER BY COALESCE(interesse_score, points) DESC LIMIT 10";
        $rows = $this->connection->fetchAllAssociative($sql);
        $result = [];
        foreach ($rows as $row) {
            $name = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
            if (empty($name)) $name = $row['company'] ?? 'Sem nome';
            $temp = $row['temperatura'] ?? '';
            $badge = '<span class="lwa-badge lwa-badge--' . $temp . '">' . ucfirst($temp) . '</span>';
            $date = $row['data_ultima_interacao'] ? date('d/m/Y', strtotime($row['data_ultima_interacao'])) : '-';
            $result[] = [$name, $row['cargo'] ?? '-', $badge, (int)$row['score'], $date, '', $row['id']];
        }
        return $result;
    }

    // --- Lead Detail ---

    public function getLeadDetail(int $leadId): ?array
    {
        $sql = "SELECT id, firstname, lastname, email, phone, company, temperatura, interesse_score, icp_score, status_comercial, origem_principal, segmento, modulo_interesse, dor_principal, cargo, motivo_perda, data_ultima_interacao, data_proximo_followup, valor_potencial, points, date_added
                FROM leads WHERE id = :id";
        $lead = $this->connection->fetchAssociative($sql, ['id' => $leadId]);
        return $lead ?: null;
    }

    // --- Email Leads ---

    private function getEmailLeads(array $dateRange, bool $onlyOpened): array
    {
        $readCondition = $onlyOpened ? 'AND es.is_read = 1' : '';
        $sql = "SELECT l.id, l.firstname, l.lastname, l.email, l.company, l.temperatura, l.interesse_score, l.status_comercial, l.origem_principal, l.cargo,
                    es.date_sent, es.is_read as email_aberto, es.source as email_source
                FROM email_stats es
                JOIN leads l ON l.id = es.lead_id
                WHERE es.date_sent BETWEEN :dateFrom AND :dateTo $readCondition
                ORDER BY es.date_sent DESC
                LIMIT 50";
        return $this->connection->fetchAllAssociative($sql, [
            'dateFrom' => $dateRange['from'],
            'dateTo'   => $dateRange['to'] . ' 23:59:59',
        ]);
    }

    // --- Actions ---

    public function scheduleFollowup(int $leadId, string $date, string $note): array
    {
        $followupDate = date('Y-m-d H:i:s', strtotime($date));
        $notificationDate = date('Y-m-d H:i:s', strtotime($date . ' -1 day'));
        $now = date('Y-m-d H:i:s');

        $lead = $this->connection->fetchAssociative("SELECT firstname, lastname, company FROM leads WHERE id = :id", ['id' => $leadId]);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $leadName = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? '')) ?: ($lead['company'] ?? 'Lead');

        // Update follow-up date on lead
        if ($this->columnExists('leads', 'data_proximo_followup')) {
            $this->connection->executeStatement(
                "UPDATE leads SET data_proximo_followup = :date, date_modified = :now WHERE id = :id",
                ['date' => $followupDate, 'now' => $now, 'id' => $leadId]
            );
        }

        // Create lead note
        $noteText = "📅 Follow-up agendado para " . date('d/m/Y', strtotime($date));
        if ($note) $noteText .= "\nObservação: $note";

        $this->connection->insert('lead_notes', [
            'lead_id' => $leadId,
            'is_published' => 1,
            'date_added' => $now,
            'created_by' => 1,
            'created_by_user' => 'admin',
            'text' => $noteText,
            'type' => 'general',
            'date_time' => $followupDate,
        ]);

        // Create notification (reminder 1 day before)
        $this->connection->insert('notifications', [
            'user_id' => 1,
            'type' => 'info',
            'header' => 'Follow-up amanha',
            'message' => "Lembrete: Follow-up com $leadName agendado para " . date('d/m/Y', strtotime($date)) . ($note ? ". Nota: $note" : ''),
            'date_added' => $notificationDate,
            'icon_class' => 'ri-calendar-check-line',
            'is_read' => 0,
        ]);

        return ['success' => true, 'message' => "Follow-up agendado para " . date('d/m/Y', strtotime($date))];
    }

    public function sendEmailToLead(int $leadId, string $subject, string $body): array
    {
        $lead = $this->connection->fetchAssociative("SELECT id, email, firstname, lastname FROM leads WHERE id = :id", ['id' => $leadId]);
        if (!$lead) return ['error' => 'Lead não encontrado'];
        if (empty($lead['email'])) return ['error' => 'Lead não possui e-mail cadastrado'];

        $to = $lead['email'];
        $leadName = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? ''));
        $now = date('Y-m-d H:i:s');

        // Send email via PHP mail (Mautic mailer config)
        $headers = "From: admin@letswork.com.br\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $htmlBody = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));

        $sent = @mail($to, $subject, $htmlBody, $headers);

        // Log in email_stats
        $this->connection->insert('email_stats', [
            'lead_id' => $leadId,
            'email_address' => $to,
            'date_sent' => $now,
            'is_read' => 0,
            'is_failed' => $sent ? 0 : 1,
            'viewed_in_browser' => 0,
            'source' => 'letswork_analytics',
        ]);

        // Create note on lead
        $this->connection->insert('lead_notes', [
            'lead_id' => $leadId,
            'is_published' => 1,
            'date_added' => $now,
            'created_by' => 1,
            'created_by_user' => 'admin',
            'text' => "📧 E-mail enviado: $subject",
            'type' => 'general',
            'date_time' => $now,
        ]);

        // Update last interaction
        if ($this->columnExists('leads', 'data_ultima_interacao')) {
            $this->connection->executeStatement(
                "UPDATE leads SET data_ultima_interacao = :now, date_modified = :now WHERE id = :id",
                ['now' => $now, 'id' => $leadId]
            );
        }

        return ['success' => true, 'message' => "E-mail enviado para $to"];
    }

    // --- AI (Gemini) ---

    public function getAiSuggestion(array $lead): array
    {
        $apiKey = 'REMOVED_KEY';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";

        $name = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? ''));
        $status = str_replace('_', ' ', $lead['status_comercial'] ?? '');
        $prompt = "Voce e o assistente comercial da LetsWork, plataforma de gestao de pessoas para RH e DP. Nossos modulos: Ponto Eletronico, Banco de Horas, Escalas e Turnos, Ferias, Documentos e Holerites, Recrutamento (ATS), Avaliacoes de Desempenho, OKRs, Timesheet e Projetos.

Nosso ICP sao empresas dos segmentos Servicos, Engenharia, Comercio, Alimentacao e Saude, com cargos de RH, DP, Inovacao.

Analise este lead e gere uma recomendacao comercial estrategica:

LEAD:
- Nome: $name
- Empresa: {$lead['company']}
- Cargo: {$lead['cargo']}
- Segmento: {$lead['segmento']}
- Modulo de interesse: {$lead['modulo_interesse']}
- Dor principal: {$lead['dor_principal']}
- Temperatura: {$lead['temperatura']}
- Score de interesse: {$lead['interesse_score']}
- Status comercial: $status
- Origem: {$lead['origem_principal']}

REGRAS:
- Mensagem deve ser consultiva e profissional, nunca generica
- Citar a dor especifica do lead na abordagem
- Sugerir proximo passo concreto com prazo
- Tom corporativo mas humano, primeira pessoa do singular
- Maximo 3 linhas na mensagem

Responda APENAS em JSON valido:
{
  \"proxima_acao\": \"acao concreta com prazo (ex: Agendar diagnostico esta semana)\",
  \"mensagem_followup\": \"mensagem de 2-3 linhas consultiva citando a dor do lead\",
  \"intencao\": \"Sem interesse | Curioso | Dor identificada | Pronto para reuniao | Negociando\",
  \"probabilidade_conversao\": \"Alta | Media | Baixa\",
  \"dica_comercial\": \"insight estrategico para o vendedor sobre como abordar\"
}";

        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 500],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return ['error' => 'Falha ao consultar IA', 'raw' => $response];
        }

        $data = json_decode($response, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        $jsonStr = $text;
        if (preg_match('/\{[^{}]*\}/s', $text, $matches)) {
            $jsonStr = $matches[0];
        }

        $parsed = json_decode($jsonStr, true);
        if (!$parsed) {
            return ['proxima_acao' => 'Analisar manualmente', 'mensagem_followup' => $text, 'intencao' => '-', 'probabilidade_conversao' => '-', 'dica_comercial' => '-'];
        }

        return $parsed;
    }

    // --- Helpers ---

    private function columnExists(string $table, string $column): bool
    {
        static $cache = [];
        $key = "$table.$column";
        if (!isset($cache[$key])) {
            try {
                $schemaManager = $this->connection->createSchemaManager();
                $columns = $schemaManager->listTableColumns($table);
                $cache[$key] = isset($columns[$column]);
            } catch (\Exception $e) {
                $cache[$key] = false;
            }
        }
        return $cache[$key];
    }
}
