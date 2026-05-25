<?php

namespace MauticPlugin\LetsworkAnalyticsBundle\Model;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class AnalyticsModel
{
    private Connection $connection;

    private const STATUS_REUNIOES = ['reuniao_agendada', 'reuniao_realizada', 'reuniao_fechamento'];
    private const STATUS_PROPOSTAS = ['proposta_enviada', 'negociacao'];
    private const STATUS_EXCLUIDOS = ['ganho', 'perdido'];
    private const TEMP_QUENTES = ['quente', 'prioritario'];

    public function __construct(EntityManagerInterface $em)
    {
        $this->connection = $em->getConnection();
    }

    public function getOverviewData(array $dateRange): array
    {
        return [
            'kpis' => [
                'prospeccao'          => $this->getProspeccao(),
                'frio_to_morno'       => $this->getFrioToMorno($dateRange),
                'morno_to_reuniao'    => $this->getMornoToReuniao($dateRange),
                'reunioes_agendadas'  => $this->getCountByStatusAll('reuniao_agendada'),
                'reunioes_realizadas' => $this->getCountByStatusAll('reuniao_realizada'),
                'reunioes_fechamento' => $this->getCountByStatusAll('reuniao_fechamento'),
                'reunioes_linkedin'   => $this->getReunioesLinkedin($dateRange),
                'propostas'           => $this->getCountByStatusAll('proposta_enviada') + $this->getCountByStatusAll('negociacao'),
                'ganhos'              => $this->getCountByStatus($dateRange, 'ganho'),
                'hot_no_followup'     => $this->getHotWithoutFollowup(),
                'leads_incompletos'   => $this->getLeadsIncompletos(),
                'campanhas'           => $this->getCampaignCount(),
                'disparos'            => $this->getTotalDisparos(),
                'emails_abertos'      => $this->getEmailsOpened($dateRange),
                'taxa_abertura'       => $this->getEmailOpenRate($dateRange),
            ],
            'temperatura_counts' => $this->getTemperatureCounts(),
            'top_dores' => $this->getTopDores(),
            'top_modulos' => $this->getTopModulos(),
            'funnel' => $this->getConversionFunnel($dateRange),
            'page_hits' => $this->getPageHitsData($dateRange),
            'charts' => [
                'temperature' => $this->getTemperatureDistribution(),
                'leads_time'  => $this->getLeadsOverTime($dateRange),
                'emails_time' => $this->getEmailsOverTime($dateRange),
            ],
            'tables' => [
                'hot_leads' => $this->getHotLeadsTable(),
            ],
            'engagement' => [
                'form_fills'   => $this->getFormFillsCount($dateRange),
                'downloads'    => $this->getDownloadsCount($dateRange),
                'in_campaigns' => $this->getLeadsInCampaigns(),
                'duplicates'   => $this->getDuplicatesCount(),
            ],
        ];
    }

    public function getConversaoData(array $dateRange): array
    {
        return [
            'funnel'    => $this->getConversionFunnel($dateRange),
            'by_origin' => $this->getConversionByOrigin($dateRange),
            'win_rate'  => $this->getWinRateByOrigin($dateRange),
            'stages'    => $this->getStagesList(),
        ];
    }

    public function getSegmentosData(array $dateRange): array
    {
        return [
            'performance'     => $this->getSegmentPerformance($dateRange),
            'ranking'         => $this->getSegmentRanking($dateRange),
            'mautic_segments' => $this->getMauticSegments($dateRange),
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

        // Conversão KPIs
        if ($kpi === 'conv_total_leads') {
            $where = "date_added BETWEEN :dateFrom AND :dateTo";
        } elseif ($kpi === 'conv_ganhos' || $kpi === 'conv_taxa' || $kpi === 'taxa_geral') {
            $where = "LOWER(status_comercial) = 'ganho' AND date_added BETWEEN :dateFrom AND :dateTo";
        } elseif ($kpi === 'tempo_reuniao') {
            $where = "LOWER(status_comercial) LIKE '%reuni%' AND date_modified BETWEEN :dateFrom AND :dateTo";
        } elseif ($kpi === 'tempo_proposta' || $kpi === 'valor_negociacao') {
            $where = "LOWER(status_comercial) IN ('proposta_enviada','proposta','negociacao','negociação') AND date_modified BETWEEN :dateFrom AND :dateTo";
        } elseif ($kpi === 'site_visitas' || $kpi === 'site_visitantes' || $kpi === 'site_precos') {
            return $this->getPageHitsLeads($kpi, $dateRange);
        } elseif ($kpi === 'leads_perdidos') {
            $where = "LOWER(status_comercial) = 'perdido' AND date_modified BETWEEN :dateFrom AND :dateTo";
        } elseif ($kpi === 'motivos_perda') {
            $where = "LOWER(status_comercial) = 'perdido' AND motivo_perda IS NOT NULL AND motivo_perda != '' AND date_modified BETWEEN :dateFrom AND :dateTo";
        } elseif ($kpi === 'origens_afetadas') {
            $where = "LOWER(status_comercial) = 'perdido' AND origem_principal IS NOT NULL AND origem_principal != '' AND date_modified BETWEEN :dateFrom AND :dateTo";
        // Dynamic cases via if/elseif
        } elseif (strpos($kpi, 'temp_') === 0) {
            $tempValue = substr($kpi, 5);
            $where = "LOWER(temperatura) = LOWER(:temp) AND (status_comercial IS NULL OR LOWER(status_comercial) NOT IN ('ganho','perdido'))";
            $params = ['temp' => $tempValue];
        } elseif (preg_match('/^conv_stage_(\d+)$/', $kpi, $m)) {
            return $this->getLeadsByStage((int)$m[1], $dateRange);
        } elseif (preg_match('/^segmento_nativo_(\d+)$/', $kpi, $m)) {
            return $this->getLeadsByNativeSegment((int)$m[1], $dateRange);
        } elseif (strpos($kpi, 'segmento_') === 0) {
            $segName = urldecode(substr($kpi, 9));
            return $this->getLeadsBySegmentName($segName, $dateRange);
        } else {
            switch ($kpi) {
                case 'prospeccao':
                    $where = "LOWER(status_comercial) IN ('prospeccao','novo') AND date_added BETWEEN :dateFrom AND :dateTo";
                    break;
                case 'engajados':
                    $where = "LOWER(status_comercial) IN ('engajado','em contato') AND date_modified BETWEEN :dateFrom AND :dateTo";
                    break;
                case 'reunioes':
                    $where = "LOWER(status_comercial) LIKE '%reuni%' AND date_modified BETWEEN :dateFrom AND :dateTo";
                    break;
                case 'morno_to_reuniao':
                    $where = "LOWER(temperatura) IN ('morno') AND LOWER(status_comercial) LIKE '%reuni%' AND date_modified BETWEEN :dateFrom AND :dateTo";
                    break;
                case 'reunioes_linkedin':
                    $where = "LOWER(origem_principal) LIKE :origem AND LOWER(status_comercial) LIKE '%reuni%' AND date_modified BETWEEN :dateFrom AND :dateTo";
                    $params['origem'] = '%linkedin%';
                    break;
                case 'propostas':
                    $where = "LOWER(status_comercial) IN ('proposta_enviada','proposta','negociacao','negociação') AND date_modified BETWEEN :dateFrom AND :dateTo";
                    break;
                case 'ganhos':
                    $where = "LOWER(status_comercial) IN ('ganho') AND date_modified BETWEEN :dateFrom AND :dateTo";
                    break;
                case 'hot_no_followup':
                    $where = "LOWER(temperatura) IN ('quente','prioritario','prioritário') AND (data_proximo_followup IS NULL OR data_proximo_followup < NOW()) AND (status_comercial IS NULL OR LOWER(status_comercial) NOT IN ('ganho','perdido'))";
                    $params = [];
                    break;
                case 'leads_incompletos':
                case 'incomplete':
                    $where = "lead_incompleto = 'sim' OR lead_incompleto = 1";
                    $params = [];
                    break;
                case 'modulo':
                    $modulo = $dateRange['modulo'] ?? '';
                    if (!$modulo || !$this->columnExists('leads', 'modulo_interesse')) return [];
                    return $this->connection->fetchAllAssociative("SELECT id, firstname, lastname, email, company, temperatura, interesse_score, status_comercial, origem_principal, cargo FROM leads WHERE modulo_interesse = :modulo ORDER BY interesse_score DESC LIMIT 50", ['modulo' => $modulo]);
                case 'emails_enviados':
                    return $this->getEmailCampaigns($dateRange);
                case 'emails_abertos':
                    return $this->getEmailLeads($dateRange, true);
                case 'emails_taxa':
                    return $this->getEmailCampaigns($dateRange);
                default:
                    if ($this->columnExists('leads', 'status_comercial')) {
                        $check = (int)$this->connection->fetchOne("SELECT COUNT(*) FROM leads WHERE status_comercial = :s OR LOWER(status_comercial) = LOWER(:s2)", ['s' => $kpi, 's2' => $kpi]);
                        if ($check > 0) {
                            return $this->connection->fetchAllAssociative("SELECT id, firstname, lastname, email, company, temperatura, interesse_score, status_comercial, origem_principal, data_ultima_interacao, data_proximo_followup, cargo FROM leads WHERE LOWER(status_comercial) = LOWER(:status) OR LOWER(status_comercial) = LOWER(:status2) ORDER BY interesse_score DESC LIMIT 50", ['status' => $kpi, 'status2' => $kpi]);
                        }
                    }
                    return [];
            }
        }

        if (empty($where)) return [];
        $sql = "SELECT id, firstname, lastname, email, company, temperatura, interesse_score, status_comercial, origem_principal, data_ultima_interacao, data_proximo_followup, cargo, motivo_perda, valor_potencial, modulo_interesse, dor_principal, segmento
                FROM leads WHERE $where ORDER BY interesse_score DESC LIMIT 50";
        return $this->connection->fetchAllAssociative($sql, $params);
    }

    // --- KPI Queries ---

    private function getCountByStatus(array $dateRange, string $status): int
    {
        if (!$this->columnExists('leads', 'status_comercial')) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM leads WHERE LOWER(status_comercial) = LOWER(:status) AND date_added BETWEEN :dateFrom AND :dateTo";
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
        $sql = "SELECT COUNT(*) FROM leads WHERE LOWER(temperatura) IN ('quente','prioritário','prioritario') AND (data_proximo_followup IS NULL OR data_proximo_followup < NOW()) AND (status_comercial IS NULL OR LOWER(status_comercial) NOT IN ('ganho','perdido'))";
        return (int) $this->connection->fetchOne($sql);
    }

    private function getCountByStatusAll(string $status): int
    {
        if (!$this->columnExists('leads', 'status_comercial')) return 0;
        $likePattern = '%' . str_replace('_', '%', $status) . '%';
        return (int) $this->connection->fetchOne("SELECT COUNT(*) FROM leads WHERE LOWER(status_comercial) = LOWER(:exact) OR LOWER(status_comercial) LIKE LOWER(:pattern)", ['exact' => $status, 'pattern' => $likePattern]);
    }

    private function getTotalDisparos(): int
    {
        return (int) $this->connection->fetchOne("SELECT COUNT(*) FROM email_stats WHERE email_id IS NOT NULL");
    }

    private function getProspeccao(): int
    {
        if (!$this->columnExists('leads', 'status_comercial')) return 0;
        return (int) $this->connection->fetchOne("SELECT COUNT(*) FROM leads WHERE LOWER(status_comercial) IN ('prospeccao','novo')");
    }

    private function getFrioToMorno(array $dateRange): int
    {
        if (!$this->columnExists('leads', 'temperatura')) return 0;
        return (int) $this->connection->fetchOne("SELECT COUNT(*) FROM leads WHERE LOWER(temperatura) = 'morno' AND date_modified BETWEEN :dateFrom AND :dateTo", [
            'dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59',
        ]);
    }

    private function getMornoToReuniao(array $dateRange): int
    {
        if (!$this->columnExists('leads', 'status_comercial')) return 0;
        return (int) $this->connection->fetchOne("SELECT COUNT(*) FROM leads WHERE status_comercial IN ('reuniao_agendada','reuniao_realizada') AND date_modified BETWEEN :dateFrom AND :dateTo", [
            'dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59',
        ]);
    }

    private function getReunioesLinkedin(array $dateRange): int
    {
        if (!$this->columnExists('leads', 'origem_principal') || !$this->columnExists('leads', 'status_comercial')) return 0;
        return (int) $this->connection->fetchOne("SELECT COUNT(*) FROM leads WHERE origem_principal LIKE :origem AND status_comercial IN ('reuniao_agendada','reuniao_realizada','reuniao_fechamento') AND date_modified BETWEEN :dateFrom AND :dateTo", [
            'origem' => '%linkedin%', 'dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59',
        ]);
    }

    private function getTemperatureCounts(): array
    {
        if (!$this->columnExists('leads', 'temperatura')) return [];
        $sql = "SELECT temperatura, COUNT(*) as total FROM leads WHERE temperatura IS NOT NULL AND temperatura != '' AND (status_comercial IS NULL OR LOWER(status_comercial) NOT IN ('ganho','perdido')) GROUP BY temperatura ORDER BY total DESC";
        $rows = $this->connection->fetchAllAssociative($sql);
        $result = [];
        foreach ($rows as $row) {
            $key = strtolower(trim($row['temperatura']));
            if ($key) $result[$key] = (int)$row['total'];
        }
        return $result;
    }

    private function getPageHitsLeads(string $kpi, array $dateRange): array
    {
        $urlFilter = '';
        if ($kpi === 'site_precos') $urlFilter = "AND ph.url LIKE '%planos%'";

        $sql = "SELECT DISTINCT l.id, l.firstname, l.lastname, l.email, l.company, l.temperatura, l.interesse_score, l.status_comercial, l.origem_principal, l.cargo,
                    COUNT(ph.id) as page_views, MAX(ph.date_hit) as ultima_visita
                FROM page_hits ph
                JOIN leads l ON l.id = ph.lead_id
                WHERE ph.lead_id IS NOT NULL AND ph.date_hit BETWEEN :dateFrom AND :dateTo $urlFilter
                GROUP BY l.id, l.firstname, l.lastname, l.email, l.company, l.temperatura, l.interesse_score, l.status_comercial, l.origem_principal, l.cargo
                ORDER BY page_views DESC
                LIMIT 50";
        return $this->connection->fetchAllAssociative($sql, [
            'dateFrom' => $dateRange['from'],
            'dateTo' => $dateRange['to'] . ' 23:59:59',
        ]);
    }

    private function getPageHitsData(array $dateRange): array
    {
        $total = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM page_hits WHERE date_hit BETWEEN :from AND :to", ['from' => $dateRange['from'], 'to' => $dateRange['to'] . ' 23:59:59']);
        $visitorsCount = (int) $this->connection->fetchOne("SELECT COUNT(DISTINCT lead_id) FROM page_hits WHERE lead_id IS NOT NULL AND date_hit BETWEEN :from AND :to", ['from' => $dateRange['from'], 'to' => $dateRange['to'] . ' 23:59:59']);
        $pricingVisits = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM page_hits WHERE url LIKE '%planos%' AND date_hit BETWEEN :from AND :to", ['from' => $dateRange['from'], 'to' => $dateRange['to'] . ' 23:59:59']);

        $topPages = $this->connection->fetchAllAssociative("SELECT url_title, COUNT(*) as views FROM page_hits WHERE date_hit BETWEEN :from AND :to AND url_title IS NOT NULL AND url_title != '' GROUP BY url_title ORDER BY views DESC LIMIT 5", ['from' => $dateRange['from'], 'to' => $dateRange['to'] . ' 23:59:59']);

        return [
            'total' => $total,
            'visitors' => $visitorsCount,
            'pricing_visits' => $pricingVisits,
            'top_pages' => $topPages,
        ];
    }

    private function getTopDores(): array
    {
        if (!$this->columnExists('leads', 'dor_principal')) return [];
        return $this->connection->fetchAllAssociative("SELECT dor_principal, COUNT(*) as total FROM leads WHERE dor_principal IS NOT NULL AND dor_principal != '' GROUP BY dor_principal ORDER BY total DESC LIMIT 6");
    }

    private function getTopModulos(): array
    {
        if (!$this->columnExists('leads', 'modulo_interesse')) return [];
        return $this->connection->fetchAllAssociative("SELECT modulo_interesse, COUNT(*) as total FROM leads WHERE modulo_interesse IS NOT NULL AND modulo_interesse != '' GROUP BY modulo_interesse ORDER BY total DESC LIMIT 8");
    }

    private function getLeadsIncompletos(): int
    {
        if (!$this->columnExists('leads', 'lead_incompleto')) return 0;
        return (int) $this->connection->fetchOne("SELECT COUNT(*) FROM leads WHERE lead_incompleto = 'sim'");
    }

    private function getLinkedinAtivos(): int
    {
        if (!$this->columnExists('leads', 'linkedin_status')) return 0;
        return (int) $this->connection->fetchOne("SELECT COUNT(*) FROM leads WHERE linkedin_status IS NOT NULL AND linkedin_status != ''");
    }

    private function getCampaignCount(): int
    {
        return (int) $this->connection->fetchOne("SELECT COUNT(*) FROM emails WHERE is_published = 1");
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
            return ['labels' => [], 'data' => []];
        }
        $sql = "SELECT temperatura, COUNT(*) as total FROM leads WHERE temperatura IS NOT NULL AND temperatura != '' GROUP BY temperatura ORDER BY FIELD(LOWER(temperatura), 'frio', 'morno', 'quente', 'prioritario', 'prioritário'), total DESC";
        $rows = $this->connection->fetchAllAssociative($sql);

        $labels = [];
        $data = [];
        $colorMap = ['frio' => '#3b82f6', 'morno' => '#f59e0b', 'quente' => '#dc2626', 'prioritario' => '#9333ea'];
        $colors = [];
        foreach ($rows as $row) {
            $key = strtolower(trim($row['temperatura']));
            $labels[] = ucfirst($key);
            $data[] = (int)$row['total'];
            $colors[] = $colorMap[$key] ?? '#64748b';
        }

        return [
            'labels' => $labels,
            'data'   => $data,
            'colors' => $colors,
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
        // Fonte primária: tabela stages do Mautic (dinâmico em produção)
        if ($this->tableExists('stages') && $this->tableExists('lead_stages_change_log')) {
            return $this->getConversionFunnelFromStages($dateRange);
        }

        // Fallback: campo status_comercial (dev/seed)
        if (!$this->columnExists('leads', 'status_comercial')) {
            return ['labels' => [], 'data' => []];
        }
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN LOWER(temperatura) IN ('morno','quente','prioritário','prioritario') THEN 1 ELSE 0 END) as engajados,
                    SUM(CASE WHEN LOWER(status_comercial) LIKE '%reuni%' THEN 1 ELSE 0 END) as reunioes,
                    SUM(CASE WHEN LOWER(status_comercial) IN ('proposta_enviada','proposta','negociacao','negociação') THEN 1 ELSE 0 END) as propostas,
                    SUM(CASE WHEN LOWER(status_comercial) = 'ganho' THEN 1 ELSE 0 END) as ganhos
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

    private function getConversionFunnelFromStages(array $dateRange): array
    {
        $stages = $this->connection->fetchAllAssociative(
            "SELECT s.id, s.name, s.weight, COUNT(DISTINCT lsc.lead_id) as total
             FROM stages s
             LEFT JOIN lead_stages_change_log lsc ON s.id = lsc.stage_id
                AND lsc.date_added BETWEEN :dateFrom AND :dateTo
             WHERE s.is_published = 1
             GROUP BY s.id, s.name, s.weight
             ORDER BY s.weight ASC",
            ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']
        );

        if (empty($stages)) {
            return ['labels' => [], 'data' => [], 'stage_ids' => []];
        }

        $labels = [];
        $data = [];
        $stageIds = [];
        foreach ($stages as $s) {
            $labels[] = $s['name'];
            $data[] = (int)$s['total'];
            $stageIds[] = (int)$s['id'];
        }

        return ['labels' => $labels, 'data' => $data, 'stage_ids' => $stageIds];
    }

    public function getLeadsByStage(int $stageId, array $dateRange): array
    {
        if (!$this->tableExists('lead_stages_change_log')) return [];
        $sql = "SELECT l.id, l.firstname, l.lastname, l.email, l.company, l.temperatura, l.interesse_score, l.status_comercial, l.origem_principal, l.data_ultima_interacao, l.data_proximo_followup, l.cargo
                FROM lead_stages_change_log lsc
                JOIN leads l ON l.id = lsc.lead_id
                WHERE lsc.stage_id = :stageId AND lsc.date_added BETWEEN :dateFrom AND :dateTo
                ORDER BY l.interesse_score DESC
                LIMIT 50";
        return $this->connection->fetchAllAssociative($sql, [
            'stageId' => $stageId,
            'dateFrom' => $dateRange['from'],
            'dateTo' => $dateRange['to'] . ' 23:59:59',
        ]);
    }

    public function getStagesList(): array
    {
        if (!$this->tableExists('stages')) return [];
        return $this->connection->fetchAllAssociative(
            "SELECT id, name, weight FROM stages WHERE is_published = 1 ORDER BY weight ASC"
        );
    }

    private function getLeadsBySegmentName(string $segmento, array $dateRange): array
    {
        if (!$this->columnExists('leads', 'segmento')) return [];
        $sql = "SELECT id, firstname, lastname, email, company, temperatura, interesse_score, status_comercial, origem_principal, data_ultima_interacao, cargo
                FROM leads WHERE segmento = :segmento AND date_added BETWEEN :dateFrom AND :dateTo ORDER BY interesse_score DESC LIMIT 50";
        return $this->connection->fetchAllAssociative($sql, [
            'segmento' => $segmento, 'dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59',
        ]);
    }

    public function getLeadsByNativeSegment(int $listId, array $dateRange): array
    {
        if (!$this->tableExists('lead_lists_leads')) return [];
        $sql = "SELECT l.id, l.firstname, l.lastname, l.email, l.company, l.temperatura, l.interesse_score, l.status_comercial, l.origem_principal, l.data_ultima_interacao, l.cargo
                FROM lead_lists_leads lll
                JOIN leads l ON l.id = lll.lead_id
                WHERE lll.leadlist_id = :listId AND lll.manually_removed = 0
                ORDER BY l.interesse_score DESC
                LIMIT 50";
        return $this->connection->fetchAllAssociative($sql, ['listId' => $listId]);
    }

    private function getConversionByOrigin(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'origem_principal')) return [];
        $sql = "SELECT origem_principal, COUNT(*) as total,
                    SUM(CASE WHEN LOWER(status_comercial) = 'ganho' THEN 1 ELSE 0 END) as ganhos
                FROM leads WHERE date_added BETWEEN :dateFrom AND :dateTo AND origem_principal IS NOT NULL AND origem_principal != '' GROUP BY origem_principal ORDER BY total DESC LIMIT 10";
        return $this->connection->fetchAllAssociative($sql, [
            'dateFrom' => $dateRange['from'],
            'dateTo'   => $dateRange['to'] . ' 23:59:59',
        ]);
    }

    private function getWinRateByOrigin(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'origem_principal') || !$this->columnExists('leads', 'status_comercial')) return ['labels' => [], 'data' => []];
        $sql = "SELECT origem_principal, COUNT(*) as total, SUM(CASE WHEN LOWER(status_comercial) = 'ganho' THEN 1 ELSE 0 END) as ganhos
                FROM leads WHERE status_comercial IN ('ganho','perdido') AND date_modified BETWEEN :dateFrom AND :dateTo AND origem_principal IS NOT NULL AND origem_principal != '' GROUP BY origem_principal ORDER BY total DESC LIMIT 8";
        $rows = $this->connection->fetchAllAssociative($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']);

        $labels = [];
        $data = [];
        foreach ($rows as $row) {
            $labels[] = $this->formatOrigin($row['origem_principal']);
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
                    SUM(CASE WHEN LOWER(status_comercial) = 'ganho' THEN 1 ELSE 0 END) as ganhos,
                    SUM(CASE WHEN LOWER(temperatura) IN ('quente','prioritário','prioritario') THEN 1 ELSE 0 END) as quentes
                FROM leads WHERE date_added BETWEEN :dateFrom AND :dateTo AND segmento IS NOT NULL AND segmento != '' GROUP BY segmento ORDER BY total DESC";
        return $this->connection->fetchAllAssociative($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']);
    }

    private function getSegmentRanking(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'segmento') || !$this->columnExists('leads', 'status_comercial')) return ['labels' => [], 'data' => []];
        $sql = "SELECT segmento, ROUND((SUM(CASE WHEN LOWER(status_comercial) = 'ganho' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as taxa
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
        $sql = "SELECT motivo_perda, COUNT(*) as total FROM leads WHERE LOWER(status_comercial) = 'perdido' AND motivo_perda IS NOT NULL AND motivo_perda != '' AND date_modified BETWEEN :dateFrom AND :dateTo GROUP BY motivo_perda ORDER BY total DESC LIMIT 8";
        $rows = $this->connection->fetchAllAssociative($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']);
        $labels = []; $data = [];
        foreach ($rows as $row) { $labels[] = $row['motivo_perda']; $data[] = (int)$row['total']; }
        return ['labels' => $labels, 'data' => $data];
    }

    private function getLossByOrigin(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'origem_principal') || !$this->columnExists('leads', 'status_comercial')) return [];
        $sql = "SELECT origem_principal, COUNT(*) as total FROM leads WHERE LOWER(status_comercial) = 'perdido' AND origem_principal IS NOT NULL AND origem_principal != '' AND date_modified BETWEEN :dateFrom AND :dateTo GROUP BY origem_principal ORDER BY total DESC";
        return $this->connection->fetchAllAssociative($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']);
    }

    private function getLossBySegment(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'segmento') || !$this->columnExists('leads', 'status_comercial')) return [];
        $sql = "SELECT segmento, COUNT(*) as total FROM leads WHERE LOWER(status_comercial) = 'perdido' AND segmento IS NOT NULL AND segmento != '' AND date_modified BETWEEN :dateFrom AND :dateTo GROUP BY segmento ORDER BY total DESC";
        return $this->connection->fetchAllAssociative($sql, ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']);
    }

    private function getLossTrend(array $dateRange): array
    {
        if (!$this->columnExists('leads', 'status_comercial')) return ['labels' => [], 'data' => []];
        $sql = "SELECT DATE(date_modified) as dia, COUNT(*) as total FROM leads WHERE LOWER(status_comercial) = 'perdido' AND date_modified BETWEEN :dateFrom AND :dateTo GROUP BY DATE(date_modified) ORDER BY dia ASC";
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
                FROM leads WHERE LOWER(temperatura) IN ('quente','prioritário','prioritario') AND (status_comercial IS NULL OR LOWER(status_comercial) NOT IN ('ganho','perdido'))
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

    // --- Segmento Detail ---

    public function getLeadsBySegment(string $segmento, array $dateRange): array
    {
        if (!$this->columnExists('leads', 'segmento')) return [];
        $sql = "SELECT id, firstname, lastname, email, company, temperatura, interesse_score, status_comercial, origem_principal, data_ultima_interacao, data_proximo_followup, cargo
                FROM leads WHERE segmento = :segmento AND date_added BETWEEN :dateFrom AND :dateTo ORDER BY interesse_score DESC LIMIT 50";
        return $this->connection->fetchAllAssociative($sql, [
            'segmento' => $segmento,
            'dateFrom' => $dateRange['from'],
            'dateTo'   => $dateRange['to'] . ' 23:59:59',
        ]);
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

    private function getEmailCampaigns(array $dateRange): array
    {
        $sql = "SELECT e.id, e.name, e.subject, e.date_added,
                    COUNT(es.id) as sent_count,
                    SUM(es.is_read) as read_count,
                    CASE WHEN COUNT(es.id) > 0 THEN ROUND((SUM(es.is_read) / COUNT(es.id)) * 100, 1) ELSE 0 END as taxa_abertura
                FROM emails e
                LEFT JOIN email_stats es ON es.email_id = e.id AND es.date_sent BETWEEN :dateFrom AND :dateTo
                WHERE e.is_published = 1
                GROUP BY e.id, e.name, e.subject, e.date_added
                HAVING COUNT(es.id) > 0
                ORDER BY e.date_added DESC
                LIMIT 20";
        $campaigns = $this->connection->fetchAllAssociative($sql, [
            'dateFrom' => $dateRange['from'],
            'dateTo'   => $dateRange['to'] . ' 23:59:59',
        ]);
        return ['type' => 'campaigns', 'data' => $campaigns];
    }

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

    // --- Score Dinâmico (PRD §10) ---

    private function mapScoreToTemperature(int $score): string
    {
        $configFile = __DIR__ . '/../Config/temperatura.php';
        $config = file_exists($configFile) ? require $configFile : [];
        $prioritarioMin = (int)($config['prioritario_min'] ?? 101);
        $quenteMin = (int)($config['quente_min'] ?? 61);
        $mornoMin = (int)($config['morno_min'] ?? 21);

        if ($score >= $prioritarioMin) return 'Prioritário';
        if ($score >= $quenteMin) return 'Quente';
        if ($score >= $mornoMin) return 'Morno';
        return 'Frio';
    }

    private function getScoreEventsCatalog(): array
    {
        static $catalog = null;
        if ($catalog !== null) return $catalog;

        $configFile = __DIR__ . '/../Config/score_events.php';
        if (file_exists($configFile)) {
            $raw = require $configFile;
            $catalog = [];
            foreach ($raw as $key => $def) {
                $catalog[$key] = (int)($def['delta'] ?? 0);
            }
        } else {
            $catalog = [
                'linkedin.import' => 2, 'linkedin.connection_accepted' => 5,
                'linkedin.message_replied' => 15, 'linkedin.pain_identified' => 30,
                'linkedin.more_info_requested' => 40, 'linkedin.meeting_accepted' => 60,
                'linkedin.no_reply_3_attempts' => -10, 'linkedin.not_interested' => -40,
                'mautic.email.open' => 2, 'mautic.email.click' => 5,
                'mautic.page.product' => 8, 'mautic.page.pricing' => 20,
                'mautic.form.submit' => 30, 'mautic.asset.download' => 15,
                'mautic.campaign.reply' => 25, 'mautic.inactivity.30d' => -10,
                'mautic.inactivity.60d' => -20, 'mautic.email.unsubscribe' => -40,
                'letswork.meeting.scheduled' => 50, 'letswork.meeting.completed' => 70,
                'letswork.meeting.transcribed' => 10, 'letswork.meeting.pain_identified' => 30,
                'letswork.meeting.objection_identified' => 15, 'letswork.meeting.next_step_defined' => 40,
                'letswork.proposal.sent' => 90, 'letswork.negotiation.active' => 100,
                'letswork.deal.won' => 200, 'letswork.deal.lost' => -100,
            ];
        }
        return $catalog;
    }

    public function addScoreEvent(int $leadId, string $event, ?string $description = null): array
    {
        $catalog = $this->getScoreEventsCatalog();
        if (!isset($catalog[$event])) {
            return ['error' => "Evento de score inválido: $event"];
        }

        $points = $catalog[$event];
        $lead = $this->connection->fetchAssociative("SELECT id, interesse_score, temperatura FROM leads WHERE id = :id", ['id' => $leadId]);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $currentScore = (int)($lead['interesse_score'] ?? 0);
        $newScore = max(0, $currentScore + $points);
        $now = date('Y-m-d H:i:s');

        $this->connection->executeStatement(
            "UPDATE leads SET interesse_score = :score, date_modified = :now WHERE id = :id",
            ['score' => $newScore, 'now' => $now, 'id' => $leadId]
        );

        $this->connection->insert('lead_notes', [
            'lead_id' => $leadId, 'is_published' => 1, 'date_added' => $now,
            'created_by' => 1, 'created_by_user' => 'system',
            'text' => "Score: {$event} (" . ($points >= 0 ? "+{$points}" : $points) . ") → {$newScore}" . ($description ? " | $description" : ''),
            'type' => 'general', 'date_time' => $now,
        ]);

        $newTemp = $this->recalculateTemperature($leadId, $newScore);

        return ['success' => true, 'score' => $newScore, 'temperatura' => $newTemp, 'points_added' => $points];
    }

    public function recalculateTemperature(int $leadId, ?int $score = null): string
    {
        if ($score === null) {
            $score = (int)$this->connection->fetchOne("SELECT COALESCE(interesse_score, 0) FROM leads WHERE id = :id", ['id' => $leadId]);
        }

        $temp = $this->mapScoreToTemperature($score);

        $currentTemp = $this->connection->fetchOne("SELECT temperatura FROM leads WHERE id = :id", ['id' => $leadId]);

        if ($currentTemp !== $temp) {
            $now = date('Y-m-d H:i:s');
            $this->connection->executeStatement(
                "UPDATE leads SET temperatura = :temp, date_modified = :now WHERE id = :id",
                ['temp' => $temp, 'now' => $now, 'id' => $leadId]
            );
        }

        return $temp;
    }

    public function getScoreEvents(): array
    {
        return $this->getScoreEventsCatalog();
    }

    // --- Timeline do Lead ---

    public function getLeadTimeline(int $leadId): array
    {
        $timeline = [];

        $notes = $this->connection->fetchAllAssociative(
            "SELECT text, date_added, created_by_user FROM lead_notes WHERE lead_id = :id ORDER BY date_added DESC LIMIT 20",
            ['id' => $leadId]
        );
        foreach ($notes as $n) {
            $timeline[] = ['type' => 'note', 'text' => $n['text'], 'date' => $n['date_added'], 'user' => $n['created_by_user']];
        }

        $emails = $this->connection->fetchAllAssociative(
            "SELECT date_sent, is_read, email_address FROM email_stats WHERE lead_id = :id ORDER BY date_sent DESC LIMIT 10",
            ['id' => $leadId]
        );
        foreach ($emails as $e) {
            $status = $e['is_read'] ? 'aberto' : 'enviado';
            $timeline[] = ['type' => 'email', 'text' => "E-mail {$status}: {$e['email_address']}", 'date' => $e['date_sent'], 'user' => 'system'];
        }

        $pages = $this->connection->fetchAllAssociative(
            "SELECT url_title, date_hit FROM page_hits WHERE lead_id = :id ORDER BY date_hit DESC LIMIT 10",
            ['id' => $leadId]
        );
        foreach ($pages as $p) {
            $timeline[] = ['type' => 'page', 'text' => "Visitou: " . ($p['url_title'] ?: 'Página'), 'date' => $p['date_hit'], 'user' => 'lead'];
        }

        if ($this->tableExists('form_submissions')) {
            $forms = $this->connection->fetchAllAssociative(
                "SELECT fs.date_submitted, f.name as form_name FROM form_submissions fs LEFT JOIN forms f ON fs.form_id = f.id WHERE fs.lead_id = :id ORDER BY fs.date_submitted DESC LIMIT 5",
                ['id' => $leadId]
            );
            foreach ($forms as $f) {
                $timeline[] = ['type' => 'form', 'text' => "Formulário: " . ($f['form_name'] ?: 'Form'), 'date' => $f['date_submitted'], 'user' => 'lead'];
            }
        }

        if ($this->tableExists('asset_downloads')) {
            $downloads = $this->connection->fetchAllAssociative(
                "SELECT ad.date_download, a.title FROM asset_downloads ad LEFT JOIN assets a ON ad.asset_id = a.id WHERE ad.lead_id = :id ORDER BY ad.date_download DESC LIMIT 5",
                ['id' => $leadId]
            );
            foreach ($downloads as $d) {
                $timeline[] = ['type' => 'download', 'text' => "Download: " . ($d['title'] ?: 'Material'), 'date' => $d['date_download'], 'user' => 'lead'];
            }
        }

        usort($timeline, function($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });

        return array_slice($timeline, 0, 30);
    }

    // --- Classificação LinkedIn (PRD §15.1) ---

    public function classifyLinkedinResponse(int $leadId, string $message): array
    {
        $lead = $this->connection->fetchAssociative(
            "SELECT firstname, lastname, company, cargo, segmento, modulo_interesse, dor_principal, temperatura, interesse_score, status_comercial FROM leads WHERE id = :id",
            ['id' => $leadId]
        );
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $name = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? ''));

        $prompt = "Voce e analista comercial da LetsWork (software RH). Classifique esta resposta do LinkedIn e sugira proxima acao.

LEAD:
- Nome: $name
- Empresa: {$lead['company']}
- Cargo: {$lead['cargo']}
- Segmento: {$lead['segmento']}
- Modulo interesse: {$lead['modulo_interesse']}
- Temperatura: {$lead['temperatura']}
- Score: {$lead['interesse_score']}

MENSAGEM RECEBIDA:
\"$message\"

CLASSIFICACOES POSSIVEIS: sem_interesse, curioso, dor_identificada, objecao, pediu_informacao, pronto_para_reuniao, desqualificado

Responda APENAS em JSON:
{
  \"intencao\": \"classificacao\",
  \"dor_principal\": \"dor se houver ou null\",
  \"modulo_interesse\": \"modulo se inferivel ou null\",
  \"objecao\": \"objecao se houver ou null\",
  \"score_event\": \"evento de score correspondente (respondeu_mensagem, resposta_com_dor, pediu_informacao, aceitou_reuniao, sem_interesse)\",
  \"proxima_acao\": \"acao concreta\",
  \"mensagem_sugerida\": \"proxima mensagem consultiva de 2-3 linhas\"
}";

        $result = $this->callGemini($prompt);

        $scoreCatalog = $this->getScoreEventsCatalog();
        if (isset($result['score_event']) && isset($scoreCatalog[$result['score_event']])) {
            $this->addScoreEvent($leadId, $result['score_event'], "LinkedIn: " . substr($message, 0, 80));
        }

        if (!empty($result['dor_principal']) && $this->columnExists('leads', 'dor_principal')) {
            $this->connection->executeStatement(
                "UPDATE leads SET dor_principal = :dor, date_modified = NOW() WHERE id = :id AND (dor_principal IS NULL OR dor_principal = '')",
                ['dor' => $result['dor_principal'], 'id' => $leadId]
            );
        }

        if (!empty($result['modulo_interesse']) && $this->columnExists('leads', 'modulo_interesse')) {
            $this->connection->executeStatement(
                "UPDATE leads SET modulo_interesse = :mod, date_modified = NOW() WHERE id = :id AND (modulo_interesse IS NULL OR modulo_interesse = '')",
                ['mod' => $result['modulo_interesse'], 'id' => $leadId]
            );
        }

        return $result;
    }

    // --- Resumo Consolidado do Lead (PRD §15.3) ---

    public function getLeadSummary(int $leadId): array
    {
        $lead = $this->getLeadDetail($leadId);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $timeline = $this->getLeadTimeline($leadId);
        $timelineText = implode("\n", array_map(function($t) {
            return date('d/m', strtotime($t['date'])) . " [{$t['type']}] {$t['text']}";
        }, array_slice($timeline, 0, 15)));

        $name = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? ''));
        $status = str_replace('_', ' ', $lead['status_comercial'] ?? 'sem status');

        $prompt = "Voce e analista comercial da LetsWork (software RH). Gere um resumo consolidado deste lead para o time comercial.

LEAD:
- Nome: $name | Empresa: {$lead['company']} | Cargo: {$lead['cargo']}
- Segmento: {$lead['segmento']} | Origem: {$lead['origem_principal']}
- Modulo: {$lead['modulo_interesse']} | Dor: {$lead['dor_principal']}
- Score: {$lead['interesse_score']} | Temperatura: {$lead['temperatura']} | Status: $status
- Valor potencial: R$ " . number_format((float)($lead['valor_potencial'] ?? 0), 0, ',', '.') . "

TIMELINE RECENTE:
$timelineText

OPCOES DE PROXIMA ACAO: nutrir, enviar_case, pedir_reuniao, passar_para_comercial, reativar_depois, desqualificar

Responda APENAS em JSON:
{
  \"resumo\": \"resumo de 3-4 linhas do lead\",
  \"principais_interacoes\": [\"interacao 1\", \"interacao 2\"],
  \"dores_identificadas\": [\"dor 1\"],
  \"objecoes\": [\"objecao se houver\"],
  \"proxima_acao\": \"uma das opcoes acima\",
  \"justificativa_acao\": \"porque essa acao\",
  \"probabilidade\": \"Alta|Media|Baixa\",
  \"mensagem_sugerida\": \"mensagem de abordagem ou follow-up\"
}";

        return $this->callGemini($prompt);
    }

    // --- Update Lead ---

    public function updateLeadField(int $leadId, string $field, string $value): array
    {
        $allowedFields = ['status_comercial', 'temperatura', 'valor_potencial', 'modulo_interesse', 'dor_principal', 'proxima_acao'];
        if (!in_array($field, $allowedFields)) {
            return ['error' => 'Campo não permitido'];
        }

        $lead = $this->connection->fetchAssociative("SELECT firstname, lastname, interesse_score FROM leads WHERE id = :id", ['id' => $leadId]);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $now = date('Y-m-d H:i:s');
        $this->connection->executeStatement("UPDATE leads SET $field = :value, date_modified = :now WHERE id = :id", [
            'value' => $value, 'now' => $now, 'id' => $leadId
        ]);

        $leadName = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? ''));
        $fieldLabels = ['status_comercial' => 'Status', 'temperatura' => 'Temperatura', 'valor_potencial' => 'Valor', 'modulo_interesse' => 'Módulo', 'dor_principal' => 'Dor', 'proxima_acao' => 'Próxima ação'];

        $this->connection->insert('lead_notes', [
            'lead_id' => $leadId, 'is_published' => 1, 'date_added' => $now,
            'created_by' => 1, 'created_by_user' => 'admin',
            'text' => ($fieldLabels[$field] ?? $field) . " atualizado para: $value",
            'type' => 'general', 'date_time' => $now,
        ]);

        if ($field === 'status_comercial') {
            $scoreEvent = match($value) {
                'reuniao_agendada' => 'reuniao_agendada',
                'reuniao_realizada' => 'reuniao_realizada',
                'proposta_enviada' => 'proposta_enviada',
                'negociacao' => 'negociacao_ativa',
                'ganho' => 'ganho',
                'perdido' => 'perdido',
                default => null,
            };
            if ($scoreEvent) {
                $this->addScoreEvent($leadId, $scoreEvent, "Status mudou para: $value");
            }
        }

        return ['success' => true, 'message' => ($fieldLabels[$field] ?? $field) . ' atualizado'];
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

        // Create notification (reminder 1 day before) with link to lead
        $followupDateFormatted = date('d/m/Y', strtotime($date));
        $leadLink = "/s/contacts/view/$leadId";
        $this->connection->insert('notifications', [
            'user_id' => 1,
            'type' => 'info',
            'header' => "Follow-up: $leadName",
            'message' => "Follow-up agendado para $followupDateFormatted" . ($note ? " — $note" : '') . " | <a href=\"$leadLink\" data-toggle=\"ajax\">Ver lead</a>",
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
        $fromEmail = getenv('MAUTIC_ADMIN_EMAIL') ?: ($_ENV['MAUTIC_ADMIN_EMAIL'] ?? 'noreply@localhost');
        $headers = "From: $fromEmail\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $htmlBody = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));

        $sent = mail($to, $subject, $htmlBody, $headers);

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

    // --- AI Tab Analysis ---

    public function getAiTabAnalysis(string $tab, array $dateRange): array
    {
        $prompts = [
            'conversao' => $this->buildConversaoPrompt($dateRange),
            'segmentos' => $this->buildSegmentosPrompt($dateRange),
            'pipeline' => $this->buildPipelinePrompt($dateRange),
            'perdas' => $this->buildPerdasPrompt($dateRange),
            'email' => $this->buildEmailPrompt($dateRange),
            'site' => $this->buildSitePrompt($dateRange),
        ];

        $prompt = $prompts[$tab] ?? '';
        if (!$prompt) return ['texto' => 'Tab não suportada.'];

        return $this->callGemini($prompt);
    }

    private function buildConversaoPrompt(array $dateRange): string
    {
        $funnel = $this->getConversionFunnel($dateRange);
        $winRate = $this->getWinRateByOrigin($dateRange);
        return "Voce e analista comercial da LetsWork (software RH). Analise o funil de conversao e sugira 3 acoes concretas para melhorar a taxa.

FUNIL: " . implode(' → ', array_map(function($l, $d) { return "$l: $d"; }, $funnel['labels'], $funnel['data'])) . "
WIN RATE POR ORIGEM: " . implode(', ', array_map(function($l, $d) { return "$l: {$d}%"; }, $winRate['labels'], $winRate['data'])) . "

Responda em JSON: {\"titulo\": \"titulo da analise\", \"insights\": [\"insight 1\", \"insight 2\", \"insight 3\"], \"acao_principal\": \"acao mais importante agora\", \"email_sugerido\": \"assunto de email para leads do funil\"}";
    }

    private function buildSegmentosPrompt(array $dateRange): string
    {
        $perf = $this->getSegmentPerformance($dateRange);
        $data = implode(', ', array_map(function($r) { return $r['segmento'] . ": " . $r['total'] . " leads (" . $r['ganhos'] . " ganhos)"; }, $perf));
        return "Voce e analista comercial da LetsWork (software RH). Analise a performance por segmento e identifique onde focar esforcos.

SEGMENTOS: $data

Responda em JSON: {\"titulo\": \"titulo\", \"melhor_segmento\": \"nome\", \"por_que\": \"razao\", \"insights\": [\"insight 1\", \"insight 2\", \"insight 3\"], \"campanha_sugerida\": \"ideia de campanha para o melhor segmento\"}";
    }

    private function buildPipelinePrompt(array $dateRange): string
    {
        $avgMeeting = $this->getAvgTimeToMeeting($dateRange);
        $avgProposal = $this->getAvgTimeToProposal($dateRange);
        $valor = $this->getValorPipeline();
        $hot = $this->getHotWithoutFollowup();
        return "Voce e analista comercial da LetsWork (software RH). Analise a velocidade do pipeline e sugira como acelerar fechamentos.

DADOS:
- Tempo medio ate reuniao: {$avgMeeting} dias
- Tempo medio ate proposta: {$avgProposal} dias
- Valor total em negociacao: R$ " . number_format($valor, 0, ',', '.') . "
- Leads quentes sem follow-up: $hot

Responda em JSON: {\"titulo\": \"titulo\", \"insights\": [\"insight 1\", \"insight 2\", \"insight 3\"], \"priorizacao\": \"o que fazer primeiro\", \"mensagem_followup\": \"sugestao de mensagem para leads sem follow-up\"}";
    }

    private function buildPerdasPrompt(array $dateRange): string
    {
        $reasons = $this->getLossReasons($dateRange);
        $data = implode(', ', array_map(function($l, $d) { return "$l ($d)"; }, $reasons['labels'], $reasons['data']));
        return "Voce e analista comercial da LetsWork (software RH). Analise os motivos de perda e sugira estrategias de recuperacao.

MOTIVOS DE PERDA: $data

Responda em JSON: {\"titulo\": \"titulo\", \"insights\": [\"insight 1\", \"insight 2\", \"insight 3\"], \"estrategia_reativacao\": \"como reativar leads perdidos\", \"email_reativacao\": \"sugestao de assunto de email para reativacao\"}";
    }

    private function buildEmailPrompt(array $dateRange): string
    {
        $taxa = $this->getEmailOpenRate($dateRange);
        $modulos = $this->getTopModulos();
        $modulosList = implode(', ', array_column($modulos, 'modulo_interesse'));
        return "Voce e especialista em email marketing da LetsWork (software RH para ponto eletronico, banco de horas, ferias, escalas, timesheet, ATS).

DADOS:
- Taxa de abertura atual: {$taxa}%
- Modulos mais procurados: $modulosList

Sugira 3 ideias de campanha de email marketing com assuntos que geram alta abertura.

Responda em JSON: {\"titulo\": \"titulo\", \"taxa_analise\": \"analise da taxa atual\", \"campanhas\": [{\"assunto\": \"assunto do email\", \"objetivo\": \"o que espera\", \"publico\": \"para quem\"}, ...]}";
    }

    private function buildSitePrompt(array $dateRange): string
    {
        $pageData = $this->getPageHitsData($dateRange);
        $pages = implode(', ', array_map(function($p) { return $p['url_title'] . " ({$p['views']}x)"; }, $pageData['top_pages']));
        return "Voce e analista de marketing digital da LetsWork (software RH). Analise o comportamento dos leads no site e sugira como converter mais visitantes.

DADOS:
- Total page views: {$pageData['total']}
- Visitantes identificados: {$pageData['visitors']}
- Visitaram pagina de precos: {$pageData['pricing_visits']}
- Paginas mais visitadas: $pages

Responda em JSON: {\"titulo\": \"titulo\", \"insights\": [\"insight 1\", \"insight 2\", \"insight 3\"], \"acao_principal\": \"o que fazer com leads que viram precos\", \"campanha_sugerida\": \"campanha para quem visitou o site\"}";
    }

    private function callGemini(string $prompt): array
    {
        $apiKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
        if (!$apiKey) return ['error' => 'GEMINI_API_KEY não configurada. Configure a variável de ambiente.'];
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";

        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 800],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $parsed = json_decode($matches[0], true);
            if ($parsed) return $parsed;
        }

        return ['titulo' => 'Análise', 'insights' => [$text ?: 'Sem resposta da IA.']];
    }

    // --- AI Insights (Dashboard) ---

    public function getAiInsights(array $dateRange): array
    {
        $data = [
            'prospeccao' => $this->getProspeccao(),
            'frio_to_morno' => $this->getFrioToMorno($dateRange),
            'reunioes' => $this->getReunioesCount($dateRange),
            'ganhos' => $this->getCountByStatus($dateRange, 'ganho'),
            'perdidos' => $this->getCountByStatus($dateRange, 'perdido'),
            'hot_no_followup' => $this->getHotWithoutFollowup(),
            'top_motivos_perda' => $this->getLossReasons($dateRange),
            'top_modulos' => $this->getTopModulos(),
            'taxa_email' => $this->getEmailOpenRate($dateRange),
        ];

        $apiKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
        if (!$apiKey) return ['error' => 'GEMINI_API_KEY não configurada. Configure a variável de ambiente.'];
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";

        $prompt = "Voce e o analista de dados comercial da LetsWork (software de RH). Analise estas metricas do CRM e gere 4 insights estrategicos curtos e acionaveis para o time comercial.

METRICAS:
- Leads em prospeccao (sem contato): {$data['prospeccao']}
- Leads frios convertidos em mornos: {$data['frio_to_morno']}
- Reunioes no periodo: {$data['reunioes']}
- Clientes ganhos: {$data['ganhos']}
- Leads perdidos: {$data['perdidos']}
- Leads quentes sem follow-up: {$data['hot_no_followup']}
- Taxa de abertura email: {$data['taxa_email']}%
- Top motivos de perda: " . implode(', ', array_column($data['top_motivos_perda']['labels'] ?? [], 0) ?: ['sem dados']) . "
- Modulos mais procurados: " . implode(', ', array_column($data['top_modulos'], 'modulo_interesse')) . "

REGRAS:
- Cada insight deve ter no maximo 2 linhas
- Foque em: onde estamos perdendo, onde estamos ganhando, o que pode melhorar
- Sugira acoes concretas
- Tom direto e objetivo

Responda APENAS em JSON:
[
  {\"tipo\": \"alerta|oportunidade|melhoria|sucesso\", \"titulo\": \"titulo curto\", \"descricao\": \"descricao de 1-2 linhas com acao\"},
  ...
]";

        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 600],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
        $response = curl_exec($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);
        $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (preg_match('/\[.*\]/s', $text, $matches)) {
            $parsed = json_decode($matches[0], true);
            if ($parsed) return $parsed;
        }

        return [['tipo' => 'melhoria', 'titulo' => 'Dados insuficientes', 'descricao' => 'Adicione mais leads para gerar insights automaticos.']];
    }

    // --- AI (Gemini) ---

    public function getAiSuggestion(array $lead): array
    {
        $apiKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
        if (!$apiKey) return ['error' => 'GEMINI_API_KEY não configurada. Configure a variável de ambiente.'];
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";

        $name = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? ''));
        $status = str_replace('_', ' ', $lead['status_comercial'] ?? '');

        $segmentos = $this->connection->fetchAllAssociative("SELECT DISTINCT segmento FROM leads WHERE segmento IS NOT NULL AND segmento != '' LIMIT 10");
        $segList = implode(', ', array_column($segmentos, 'segmento')) ?: 'diversos';
        $modulos = $this->getTopModulos();
        $modList = implode(', ', array_column($modulos, 'modulo_interesse')) ?: 'gestao de RH';

        $prompt = "Voce e o assistente comercial da LetsWork, plataforma de gestao de pessoas para RH e DP. Nossos modulos: $modList.

Nosso ICP sao empresas dos segmentos $segList, com cargos de RH, DP, Inovacao.

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

    // --- Mautic Native Tables ---

    private function getMauticSegments(array $dateRange): array
    {
        if (!$this->tableExists('lead_lists') || !$this->tableExists('lead_lists_leads')) return [];
        $sql = "SELECT ll.id, ll.name, ll.alias, COUNT(lll.lead_id) as total_leads
                FROM lead_lists ll
                LEFT JOIN lead_lists_leads lll ON ll.id = lll.leadlist_id AND lll.manually_removed = 0
                WHERE ll.is_published = 1
                GROUP BY ll.id, ll.name, ll.alias
                HAVING total_leads > 0
                ORDER BY total_leads DESC
                LIMIT 20";
        return $this->connection->fetchAllAssociative($sql);
    }

    public function getLeadTags(int $leadId): array
    {
        if (!$this->tableExists('lead_tags_xref') || !$this->tableExists('lead_tags')) return [];
        $sql = "SELECT t.tag FROM lead_tags t JOIN lead_tags_xref x ON t.id = x.tag_id WHERE x.lead_id = :id ORDER BY t.tag";
        return array_column($this->connection->fetchAllAssociative($sql, ['id' => $leadId]), 'tag');
    }

    private function getCampaignPerformance(array $dateRange): array
    {
        if (!$this->tableExists('campaign_leads') || !$this->tableExists('campaigns')) return [];
        $sql = "SELECT c.id, c.name, COUNT(cl.lead_id) as total_leads,
                    SUM(CASE WHEN cl.manually_removed = 0 THEN 1 ELSE 0 END) as active_leads
                FROM campaigns c
                JOIN campaign_leads cl ON c.id = cl.campaign_id
                WHERE c.is_published = 1 AND cl.date_added BETWEEN :dateFrom AND :dateTo
                GROUP BY c.id, c.name
                ORDER BY total_leads DESC
                LIMIT 10";
        return $this->connection->fetchAllAssociative($sql, [
            'dateFrom' => $dateRange['from'],
            'dateTo'   => $dateRange['to'] . ' 23:59:59',
        ]);
    }

    private function getFormFillsCount(array $dateRange): int
    {
        if (!$this->tableExists('form_submissions')) return 0;
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM form_submissions WHERE date_submitted BETWEEN :dateFrom AND :dateTo",
            ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']
        );
    }

    private function getDownloadsCount(array $dateRange): int
    {
        if (!$this->tableExists('asset_downloads')) return 0;
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM asset_downloads WHERE date_download BETWEEN :dateFrom AND :dateTo",
            ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']
        );
    }

    private function getLeadsInCampaigns(): int
    {
        if (!$this->tableExists('campaign_leads')) return 0;
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(DISTINCT lead_id) FROM campaign_leads WHERE manually_removed = 0"
        );
    }

    private function getDuplicatesCount(): int
    {
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM (SELECT email, COUNT(*) as c FROM leads WHERE email IS NOT NULL AND email != '' GROUP BY email HAVING c > 1) dups"
        );
    }

    // --- Fila de Leads Incompletos ---

    public function getIncompleteQueue(): array
    {
        if (!$this->columnExists('leads', 'lead_incompleto')) return [];
        $sql = "SELECT id, firstname, lastname, company, cargo, segmento, origem_principal, linkedin_status, motivo_lead_incompleto, date_added
                FROM leads WHERE lead_incompleto = 'sim'
                ORDER BY date_added DESC LIMIT 50";
        return $this->connection->fetchAllAssociative($sql);
    }

    public function enrichLead(int $leadId, ?string $email, ?string $phone): array
    {
        $lead = $this->connection->fetchAssociative("SELECT id, email, phone, lead_incompleto FROM leads WHERE id = :id", ['id' => $leadId]);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $now = date('Y-m-d H:i:s');
        $updates = ['date_modified' => $now];

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $updates['email'] = $email;
        }
        if ($phone) {
            $updates['phone'] = preg_replace('/[^0-9+]/', '', $phone);
        }

        $hasEmail = !empty($updates['email'] ?? $lead['email']);
        $hasPhone = !empty($updates['phone'] ?? $lead['phone']);

        if ($hasEmail && $hasPhone) {
            $updates['lead_incompleto'] = null;
            $updates['motivo_lead_incompleto'] = null;
        } elseif ($hasEmail || $hasPhone) {
            $updates['motivo_lead_incompleto'] = !$hasEmail ? 'sem_email' : 'sem_telefone';
        }

        $sets = [];
        $params = ['id' => $leadId];
        foreach ($updates as $col => $val) {
            $sets[] = "$col = :$col";
            $params[$col] = $val;
        }
        $this->connection->executeStatement("UPDATE leads SET " . implode(', ', $sets) . " WHERE id = :id", $params);

        $this->connection->insert('lead_notes', [
            'lead_id' => $leadId, 'is_published' => 1, 'date_added' => $now,
            'created_by' => 1, 'created_by_user' => 'admin',
            'text' => "Lead enriquecido" . ($email ? " | Email: $email" : '') . ($phone ? " | Tel: $phone" : ''),
            'type' => 'general', 'date_time' => $now,
        ]);

        return ['success' => true, 'message' => 'Lead enriquecido com sucesso'];
    }

    // --- Playbooks (PRD §14) ---

    public function getPlaybookMessage(int $leadId, string $tipo): array
    {
        $lead = $this->getLeadDetail($leadId);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $name = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? ''));
        $vars = [
            '{nome}' => $name ?: 'Prezado(a)',
            '{empresa}' => $lead['company'] ?? 'sua empresa',
            '{segmento}' => $lead['segmento'] ?? 'seu segmento',
            '{dor_principal}' => $lead['dor_principal'] ?? 'gestão de pessoas',
            '{modulo_interesse}' => $lead['modulo_interesse'] ?? 'gestão de RH',
            '{cargo}' => $lead['cargo'] ?? 'RH',
        ];

        $templates = [
            'inicial_linkedin' => "Oi, {nome}. Vi que você atua com {cargo} na {empresa}.\nTenho conversado com empresas de {segmento} que estão tentando reduzir retrabalho em {dor_principal}.\nIsso também é um desafio por aí?",
            'checklist' => "Posso te mandar um checklist rápido para avaliar se o processo atual de {modulo_interesse} está gerando retrabalho, risco ou perda de visibilidade?",
            'diagnostico' => "Se fizer sentido, consigo olhar contigo em 15 minutos e apontar onde normalmente aparecem os maiores gargalos em {dor_principal}.\nSem compromisso — é um diagnóstico rápido que temos feito com empresas de {segmento}.",
            'reativacao' => "Oi, {nome}. Retomando nosso contato: vocês chegaram a resolver a parte de {dor_principal} aí na {empresa}?\nPergunto porque temos ajudado empresas a reduzir bastante o retrabalho do RH nessa rotina.",
        ];

        if (!isset($templates[$tipo])) {
            return ['error' => 'Tipo de playbook inválido. Use: inicial_linkedin, checklist, diagnostico, reativacao'];
        }

        $message = str_replace(array_keys($vars), array_values($vars), $templates[$tipo]);

        return ['success' => true, 'tipo' => $tipo, 'message' => $message, 'lead_name' => $name];
    }

    // --- Webhook (Roboze / LetsWork CRM) ---

    public function processWebhookEvent(string $source, string $event, int $leadId, array $data, string $authToken = ''): array
    {
        $expectedToken = getenv('ANALYTICS_WEBHOOK_TOKEN') ?: ($_ENV['ANALYTICS_WEBHOOK_TOKEN'] ?? '');
        if ($expectedToken && $authToken !== $expectedToken) {
            return ['error' => 'Token de autenticação inválido'];
        }

        $validSources = ['roboze', 'letswork_crm', 'mautic', 'webhook'];
        if (!in_array($source, $validSources)) {
            return ['error' => 'Source inválido'];
        }

        $lead = $this->connection->fetchAssociative("SELECT id FROM leads WHERE id = :id", ['id' => $leadId]);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        if (isset($this->getScoreEventsCatalog()[$event])) {
            $scoreResult = $this->addScoreEvent($leadId, $event, "Webhook: $source");
        } else {
            $scoreResult = ['info' => 'Evento não altera score'];
        }

        $now = date('Y-m-d H:i:s');
        $updates = ['date_modified' => $now, 'data_ultima_interacao' => $now];

        if (!empty($data['status_comercial'])) {
            $updates['status_comercial'] = $data['status_comercial'];
        }
        if (!empty($data['dor_principal']) && $this->columnExists('leads', 'dor_principal')) {
            $updates['dor_principal'] = $data['dor_principal'];
        }
        if (!empty($data['modulo_interesse']) && $this->columnExists('leads', 'modulo_interesse')) {
            $updates['modulo_interesse'] = $data['modulo_interesse'];
        }
        if (!empty($data['motivo_perda']) && $this->columnExists('leads', 'motivo_perda')) {
            $updates['motivo_perda'] = $data['motivo_perda'];
        }
        if (!empty($data['linkedin_status']) && $this->columnExists('leads', 'linkedin_status')) {
            $updates['linkedin_status'] = $data['linkedin_status'];
        }

        $sets = [];
        $params = ['id' => $leadId];
        foreach ($updates as $col => $val) {
            $sets[] = "$col = :$col";
            $params[$col] = $val;
        }
        $this->connection->executeStatement("UPDATE leads SET " . implode(', ', $sets) . " WHERE id = :id", $params);

        $this->connection->insert('lead_notes', [
            'lead_id' => $leadId, 'is_published' => 1, 'date_added' => $now,
            'created_by' => 1, 'created_by_user' => 'webhook',
            'text' => "[$source] Evento: $event" . (!empty($data['message']) ? " | " . substr($data['message'], 0, 100) : ''),
            'type' => 'general', 'date_time' => $now,
        ]);

        return ['success' => true, 'event' => $event, 'source' => $source, 'score' => $scoreResult];
    }

    // --- Helpers ---

    private function tableExists(string $table): bool
    {
        static $cache = [];
        if (!isset($cache[$table])) {
            try {
                $schemaManager = $this->connection->createSchemaManager();
                $cache[$table] = $schemaManager->tablesExist([$table]);
            } catch (\Exception $e) {
                $cache[$table] = false;
            }
        }
        return $cache[$table];
    }

    private function formatOrigin(string $origin): string
    {
        return ucfirst(str_replace('_', ' ', $origin));
    }

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
