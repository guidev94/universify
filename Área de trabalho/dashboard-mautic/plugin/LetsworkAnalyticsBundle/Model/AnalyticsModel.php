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
                'perdidos'            => $this->getCountByStatus($dateRange, 'perdido'),
                'hot_no_followup'     => $this->getHotWithoutFollowup(),
                'leads_incompletos'   => $this->getLeadsIncompletos(),
                'campanhas'           => $this->getCampaignCount(),
                'disparos'            => $this->getTotalDisparos(),
                'emails_abertos'      => $this->getEmailsOpened($dateRange),
                'taxa_abertura'       => $this->getEmailOpenRate($dateRange),
                'valor_pipeline'      => $this->getValorPipeline(),
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
                'decay_risk'   => $this->getDecayRiskCount(),
            ],
        ];
    }

    public function getConversaoData(array $dateRange): array
    {
        $dateFilter = ['from' => $dateRange['from'], 'to' => $dateRange['to'] . ' 23:59:59'];
        $validLead = "((email IS NOT NULL AND email != '') OR (firstname IS NOT NULL AND firstname != ''))";
        $totalLeads = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM leads WHERE date_added BETWEEN :from AND :to AND $validLead", $dateFilter);
        $totalGanhos = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM leads WHERE LOWER(status_comercial) = 'ganho' AND date_added BETWEEN :from AND :to AND $validLead", $dateFilter);

        return [
            'funnel'       => $this->getConversionFunnel($dateRange),
            'by_origin'    => $this->getConversionByOrigin($dateRange),
            'win_rate'     => $this->getWinRateByOrigin($dateRange),
            'stages'       => $this->getStagesList(),
            'total_leads'  => $totalLeads,
            'total_ganhos' => $totalGanhos,
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

    public function getUtmData(array $dateRange): array
    {
        if (!$this->tableExists('lead_utmtags')) {
            return ['total' => 0, 'by_source' => [], 'by_medium' => [], 'by_campaign' => [], 'by_content' => [], 'by_term' => [], 'recent' => []];
        }
        $params = ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59'];

        $total = (int) $this->connection->fetchOne("SELECT COUNT(DISTINCT lead_id) FROM lead_utmtags WHERE date_added BETWEEN :dateFrom AND :dateTo", $params);

        $bySource = $this->connection->fetchAllAssociative("SELECT utm_source, COUNT(DISTINCT lead_id) as leads, COUNT(*) as hits FROM lead_utmtags WHERE date_added BETWEEN :dateFrom AND :dateTo AND utm_source IS NOT NULL AND utm_source != '' GROUP BY utm_source ORDER BY leads DESC LIMIT 10", $params);

        $byMedium = $this->connection->fetchAllAssociative("SELECT utm_medium, COUNT(DISTINCT lead_id) as leads FROM lead_utmtags WHERE date_added BETWEEN :dateFrom AND :dateTo AND utm_medium IS NOT NULL AND utm_medium != '' GROUP BY utm_medium ORDER BY leads DESC LIMIT 10", $params);

        $byCampaign = $this->connection->fetchAllAssociative("SELECT utm_campaign, COUNT(DISTINCT lead_id) as leads, COUNT(*) as hits FROM lead_utmtags WHERE date_added BETWEEN :dateFrom AND :dateTo AND utm_campaign IS NOT NULL AND utm_campaign != '' GROUP BY utm_campaign ORDER BY leads DESC LIMIT 15", $params);

        $byContent = $this->connection->fetchAllAssociative("SELECT utm_content, COUNT(DISTINCT lead_id) as leads FROM lead_utmtags WHERE date_added BETWEEN :dateFrom AND :dateTo AND utm_content IS NOT NULL AND utm_content != '' GROUP BY utm_content ORDER BY leads DESC LIMIT 10", $params);

        $byTerm = $this->connection->fetchAllAssociative("SELECT utm_term, COUNT(DISTINCT lead_id) as leads FROM lead_utmtags WHERE date_added BETWEEN :dateFrom AND :dateTo AND utm_term IS NOT NULL AND utm_term != '' GROUP BY utm_term ORDER BY leads DESC LIMIT 10", $params);

        $recent = $this->connection->fetchAllAssociative("SELECT u.lead_id, l.firstname, l.lastname, l.email, l.company, GROUP_CONCAT(DISTINCT u.utm_source SEPARATOR ', ') as utm_source, GROUP_CONCAT(DISTINCT u.utm_medium SEPARATOR ', ') as utm_medium, GROUP_CONCAT(DISTINCT u.utm_campaign SEPARATOR ', ') as utm_campaign, MAX(u.date_added) as date_added FROM lead_utmtags u JOIN leads l ON l.id = u.lead_id WHERE u.date_added BETWEEN :dateFrom AND :dateTo AND ((l.email IS NOT NULL AND l.email != '') OR (l.firstname IS NOT NULL AND l.firstname != '')) GROUP BY u.lead_id, l.firstname, l.lastname, l.email, l.company ORDER BY date_added DESC LIMIT 20", $params);

        $pipeline = $this->connection->fetchAllAssociative("SELECT u.utm_source, l.temperatura, COUNT(DISTINCT u.lead_id) as leads, SUM(CASE WHEN LOWER(l.status_comercial) = 'perdido' THEN 1 ELSE 0 END) as perdidos FROM lead_utmtags u JOIN leads l ON l.id = u.lead_id WHERE u.date_added BETWEEN :dateFrom AND :dateTo AND l.temperatura IS NOT NULL AND l.temperatura != '' AND ((l.email IS NOT NULL AND l.email != '') OR (l.firstname IS NOT NULL AND l.firstname != '')) GROUP BY u.utm_source, l.temperatura ORDER BY u.utm_source, leads DESC", $params);

        $campaignPipeline = $this->connection->fetchAllAssociative("SELECT u.utm_campaign, l.temperatura, COUNT(DISTINCT u.lead_id) as leads, SUM(CASE WHEN LOWER(l.status_comercial) = 'perdido' THEN 1 ELSE 0 END) as perdidos FROM lead_utmtags u JOIN leads l ON l.id = u.lead_id WHERE u.date_added BETWEEN :dateFrom AND :dateTo AND l.temperatura IS NOT NULL AND l.temperatura != '' AND u.utm_campaign IS NOT NULL AND u.utm_campaign != '' AND ((l.email IS NOT NULL AND l.email != '') OR (l.firstname IS NOT NULL AND l.firstname != '')) GROUP BY u.utm_campaign, l.temperatura ORDER BY u.utm_campaign, leads DESC", $params);

        // Pipeline KPIs (leads UTM por temperatura)
        $utmPipeline = $this->connection->fetchAssociative(
            "SELECT
                COUNT(DISTINCT CASE WHEN (l.status_comercial IS NULL OR l.status_comercial = '' OR LOWER(l.status_comercial) IN ('prospeccao','novo')) THEN u.lead_id END) as prospeccao,
                COUNT(DISTINCT CASE WHEN LOWER(l.temperatura) = 'frio' THEN u.lead_id END) as frio,
                COUNT(DISTINCT CASE WHEN LOWER(l.temperatura) = 'morno' THEN u.lead_id END) as morno,
                COUNT(DISTINCT CASE WHEN LOWER(l.temperatura) IN ('quente') THEN u.lead_id END) as quente,
                COUNT(DISTINCT CASE WHEN LOWER(l.temperatura) IN ('prioritario','prioritário') THEN u.lead_id END) as prioritario,
                COUNT(DISTINCT CASE WHEN LOWER(l.status_comercial) LIKE '%reuni%' THEN u.lead_id END) as reunioes,
                COUNT(DISTINCT CASE WHEN LOWER(l.status_comercial) IN ('proposta_enviada','proposta','negociacao','negociação') THEN u.lead_id END) as propostas,
                COUNT(DISTINCT CASE WHEN LOWER(l.status_comercial) = 'ganho' THEN u.lead_id END) as ganhos,
                COUNT(DISTINCT CASE WHEN LOWER(l.status_comercial) = 'perdido' THEN u.lead_id END) as perdidos
            FROM lead_utmtags u
            JOIN leads l ON l.id = u.lead_id
            WHERE u.date_added BETWEEN :dateFrom AND :dateTo
            AND ((l.email IS NOT NULL AND l.email != '') OR (l.firstname IS NOT NULL AND l.firstname != ''))",
            $params
        );

        return [
            'total' => $total,
            'by_source' => $bySource,
            'by_medium' => $byMedium,
            'by_campaign' => $byCampaign,
            'by_content' => $byContent,
            'by_term' => $byTerm,
            'recent' => $recent,
            'pipeline' => $pipeline,
            'campaign_pipeline' => $campaignPipeline,
            'utm_pipeline' => $utmPipeline ?: [],
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
        } elseif ($kpi === 'engaj_forms' || $kpi === 'engaj_downloads' || $kpi === 'engaj_campanhas') {
            return [];
        } elseif (strpos($kpi, 'utm_') === 0) {
            return $this->getUtmLeads($kpi, $dateRange);
        } elseif ($kpi === 'leads_perdidos') {
            $where = "LOWER(status_comercial) = 'perdido' AND date_modified BETWEEN :dateFrom AND :dateTo";
        } elseif ($kpi === 'motivos_perda') {
            $where = "LOWER(status_comercial) = 'perdido' AND motivo_perda IS NOT NULL AND motivo_perda != '' AND date_modified BETWEEN :dateFrom AND :dateTo";
        } elseif ($kpi === 'origens_afetadas') {
            $where = "LOWER(status_comercial) = 'perdido' AND origem_principal IS NOT NULL AND origem_principal != '' AND date_modified BETWEEN :dateFrom AND :dateTo";
        // Dynamic cases via if/elseif
        } elseif (strpos($kpi, 'temp_') === 0) {
            $tempValue = substr($kpi, 5);
            $where = "LOWER(REPLACE(REPLACE(temperatura,'á','a'),'ó','o')) = LOWER(:temp) AND (status_comercial IS NULL OR LOWER(status_comercial) NOT IN ('ganho','perdido'))";
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
                    $where = "(status_comercial IS NULL OR status_comercial = '' OR LOWER(status_comercial) IN ('prospeccao','novo')) AND date_added BETWEEN :dateFrom AND :dateTo";
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
                    $where = "(email IS NULL OR email = '') OR (firstname IS NULL OR firstname = '')";
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
                FROM leads WHERE $where AND ((email IS NOT NULL AND email != '') OR (firstname IS NOT NULL AND firstname != '')) ORDER BY interesse_score DESC LIMIT 50";
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
        $sql = "SELECT COUNT(*) FROM leads WHERE LOWER(temperatura) IN ('quente','prioritário','prioritario') AND (data_proximo_followup IS NULL OR data_proximo_followup < NOW()) AND (status_comercial IS NULL OR LOWER(status_comercial) NOT IN ('ganho','perdido')) AND (email IS NOT NULL AND email != '' AND email NOT LIKE '%@anonymous%') AND (firstname IS NOT NULL AND CHAR_LENGTH(firstname) >= 2)";
        return (int) $this->connection->fetchOne($sql);
    }

    public function getPendingFollowups(): array
    {
        if (!$this->columnExists('leads', 'data_proximo_followup')) return ['count' => 0, 'leads' => []];

        $sql = "SELECT id, firstname, lastname, company, temperatura, data_proximo_followup, status_comercial
                FROM leads
                WHERE data_proximo_followup IS NOT NULL
                  AND data_proximo_followup <= DATE_ADD(NOW(), INTERVAL 1 DAY)
                  AND (status_comercial IS NULL OR LOWER(status_comercial) NOT IN ('ganho','perdido'))
                ORDER BY data_proximo_followup ASC
                LIMIT 15";
        $leads = $this->connection->fetchAllAssociative($sql);

        $hotCount = $this->getHotWithoutFollowup();

        return ['count' => count($leads) + $hotCount, 'followups' => $leads, 'hot_no_followup' => $hotCount];
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
        return (int) $this->connection->fetchOne("SELECT COUNT(*) FROM leads WHERE (status_comercial IS NULL OR status_comercial = '' OR LOWER(status_comercial) IN ('prospeccao','novo')) AND ((email IS NOT NULL AND email != '') OR (firstname IS NOT NULL AND firstname != ''))");
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
        $sql = "SELECT temperatura, COUNT(*) as total FROM leads WHERE temperatura IS NOT NULL AND temperatura != '' AND (status_comercial IS NULL OR LOWER(status_comercial) NOT IN ('ganho','perdido')) AND ((email IS NOT NULL AND email != '') OR (firstname IS NOT NULL AND firstname != '')) GROUP BY temperatura ORDER BY FIELD(LOWER(temperatura), 'frio', 'morno', 'quente', 'prioritario', 'prioritário')";
        $rows = $this->connection->fetchAllAssociative($sql);
        $result = [];
        foreach ($rows as $row) {
            $key = strtolower(trim($row['temperatura']));
            $key = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $key);
            if ($key) $result[$key] = ($result[$key] ?? 0) + (int)$row['total'];
        }
        return $result;
    }

    private function getUtmLeads(string $kpi, array $dateRange): array
    {
        if (!$this->tableExists('lead_utmtags')) return [];
        $params = ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59'];
        $sql = "SELECT l.id, l.firstname, l.lastname, l.email, l.company, l.temperatura, l.interesse_score, l.status_comercial, l.origem_principal, l.cargo,
                    GROUP_CONCAT(DISTINCT u.utm_source SEPARATOR ', ') as utm_source,
                    GROUP_CONCAT(DISTINCT u.utm_medium SEPARATOR ', ') as utm_medium,
                    GROUP_CONCAT(DISTINCT u.utm_campaign SEPARATOR ', ') as utm_campaign
                FROM lead_utmtags u
                JOIN leads l ON l.id = u.lead_id
                WHERE u.date_added BETWEEN :dateFrom AND :dateTo
                AND ((l.email IS NOT NULL AND l.email != '') OR (l.firstname IS NOT NULL AND l.firstname != ''))
                GROUP BY l.id, l.firstname, l.lastname, l.email, l.company, l.temperatura, l.interesse_score, l.status_comercial, l.origem_principal, l.cargo
                ORDER BY l.interesse_score DESC LIMIT 50";
        $leads = $this->connection->fetchAllAssociative($sql, $params);
        return ['type' => 'utm_data', 'leads' => $leads];
    }

    private function getPageHitsLeads(string $kpi, array $dateRange): array
    {
        $urlFilter = '';
        $urlFilterSimple = '';
        if ($kpi === 'site_precos') {
            $urlFilter = "AND ph.url LIKE '%planos%'";
            $urlFilterSimple = "AND url LIKE '%planos%'";
        }

        $sql = "SELECT l.id, l.firstname, l.lastname, l.email, l.company, l.temperatura, l.interesse_score, l.status_comercial, l.origem_principal, l.cargo,
                    COUNT(ph.id) as page_views, MAX(ph.date_hit) as ultima_visita,
                    GROUP_CONCAT(DISTINCT ph.url_title SEPARATOR ', ') as paginas_visitadas
                FROM page_hits ph
                JOIN leads l ON l.id = ph.lead_id
                WHERE ph.lead_id IS NOT NULL AND ph.date_hit BETWEEN :dateFrom AND :dateTo $urlFilter
                GROUP BY l.id, l.firstname, l.lastname, l.email, l.company, l.temperatura, l.interesse_score, l.status_comercial, l.origem_principal, l.cargo
                ORDER BY page_views DESC
                LIMIT 50";
        $leads = $this->connection->fetchAllAssociative($sql, [
            'dateFrom' => $dateRange['from'],
            'dateTo' => $dateRange['to'] . ' 23:59:59',
        ]);

        $topPages = $this->connection->fetchAllAssociative(
            "SELECT url_title, url, COUNT(*) as views FROM page_hits WHERE date_hit BETWEEN :dateFrom AND :dateTo AND url_title IS NOT NULL AND url_title != '' $urlFilterSimple GROUP BY url_title, url ORDER BY views DESC LIMIT 10",
            ['dateFrom' => $dateRange['from'], 'dateTo' => $dateRange['to'] . ' 23:59:59']
        );
        foreach ($topPages as &$p) { $p['url_title'] = html_entity_decode($p['url_title'] ?? '', ENT_QUOTES, 'UTF-8'); }

        return ['type' => 'site_hits', 'leads' => $leads, 'top_pages' => $topPages];
    }

    private function getPageHitsData(array $dateRange): array
    {
        $total = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM page_hits WHERE date_hit BETWEEN :from AND :to", ['from' => $dateRange['from'], 'to' => $dateRange['to'] . ' 23:59:59']);
        $visitorsCount = (int) $this->connection->fetchOne("SELECT COUNT(DISTINCT lead_id) FROM page_hits WHERE lead_id IS NOT NULL AND date_hit BETWEEN :from AND :to", ['from' => $dateRange['from'], 'to' => $dateRange['to'] . ' 23:59:59']);
        $pricingVisits = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM page_hits WHERE url LIKE '%planos%' AND date_hit BETWEEN :from AND :to", ['from' => $dateRange['from'], 'to' => $dateRange['to'] . ' 23:59:59']);

        $topPages = $this->connection->fetchAllAssociative("SELECT url_title, COUNT(*) as views FROM page_hits WHERE date_hit BETWEEN :from AND :to AND url_title IS NOT NULL AND url_title != '' GROUP BY url_title ORDER BY views DESC LIMIT 5", ['from' => $dateRange['from'], 'to' => $dateRange['to'] . ' 23:59:59']);
        foreach ($topPages as &$p) { $p['url_title'] = html_entity_decode($p['url_title'], ENT_QUOTES, 'UTF-8'); }

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
        return (int) $this->connection->fetchOne("SELECT COUNT(*) FROM leads WHERE (email IS NULL OR email = '') OR (firstname IS NULL OR firstname = '')");
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
                AND ((email IS NOT NULL AND email != '') OR (firstname IS NOT NULL AND firstname != ''))
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

        // Score event log (CRM Bundle table)
        if ($this->tableExists('letswork_score_event_log')) {
            $scoreEvents = $this->connection->fetchAllAssociative(
                "SELECT event_key, delta, created_at FROM letswork_score_event_log WHERE lead_id = :id ORDER BY created_at DESC LIMIT 10",
                ['id' => $leadId]
            );
            foreach ($scoreEvents as $se) {
                $sign = (int)$se['delta'] >= 0 ? '+' : '';
                $timeline[] = ['type' => 'score', 'text' => $se['event_key'] . " ({$sign}{$se['delta']} pts)", 'date' => $se['created_at'], 'user' => 'system'];
            }
        }

        // Push log (CRM Bundle table)
        if ($this->tableExists('letswork_push_log')) {
            $pushes = $this->connection->fetchAllAssociative(
                "SELECT status, created_at FROM letswork_push_log WHERE lead_id = :id ORDER BY created_at DESC LIMIT 5",
                ['id' => $leadId]
            );
            foreach ($pushes as $p) {
                $timeline[] = ['type' => 'push', 'text' => "Enviado para CRM ({$p['status']})", 'date' => $p['created_at'], 'user' => 'system'];
            }
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

        $daysSince = 'desconhecido';
        if (!empty($lead['data_ultima_interacao'])) {
            $days = (int)((time() - strtotime($lead['data_ultima_interacao'])) / 86400);
            $daysSince = $days . ' dias';
        }

        $emailStats = $this->connection->fetchAssociative(
            "SELECT COUNT(*) as total, SUM(is_read) as opened FROM email_stats WHERE lead_id = :id",
            ['id' => $leadId]
        );
        $emailInfo = "Emails enviados: " . ($emailStats['total'] ?? 0) . " | Abertos: " . ($emailStats['opened'] ?? 0);

        $prompt = "Voce e o analista comercial senior da LetsWork (plataforma de gestao de pessoas para RH/DP).
Gere um RESUMO CONSOLIDADO executivo deste lead para briefing do time comercial.

LEAD:
- Nome: $name | Empresa: {$lead['company']} | Cargo: {$lead['cargo']}
- Segmento: {$lead['segmento']} | Origem: {$lead['origem_principal']}
- Modulo de interesse: {$lead['modulo_interesse']} | Dor principal: {$lead['dor_principal']}
- Score: {$lead['interesse_score']} | Temperatura: {$lead['temperatura']} | Status: $status
- Valor potencial: R$ " . number_format((float)($lead['valor_potencial'] ?? 0), 0, ',', '.') . "
- Dias desde ultima interacao: $daysSince
- $emailInfo

TIMELINE COMPLETA:
$timelineText

ANALISE NECESSARIA:
1. Resumo: Quem e esse lead? O que sabemos? Qual a situacao atual? (3-4 linhas)
2. Interacoes-chave: Os momentos mais importantes na timeline
3. Dores: O que podemos inferir das interacoes + dados
4. Objecoes: Sinais de resistencia ou blockers identificados
5. Acao recomendada: Baseada no STATUS ATUAL e no historico
6. Mensagem: Pronta para copiar e enviar pelo canal mais adequado

REGRAS:
- Se nao ha dados suficientes, diga claramente e sugira qualificacao
- Probabilidade deve considerar: engagement (emails abertos), tempo sem interacao, temperatura
- Mensagem deve ser ESPECIFICA para esse lead, citando empresa/dor/modulo
- Lead sem interacao em >14 dias = risco de esfriar
- Lead com emails abertos mas sem resposta = dor existe, mudar abordagem

Responda APENAS em JSON valido:
{
  \"resumo\": \"resumo executivo de 3-4 linhas\",
  \"principais_interacoes\": [\"interacao 1\", \"interacao 2\", \"interacao 3\"],
  \"dores_identificadas\": [\"dor 1\", \"dor 2\"],
  \"objecoes\": [\"objecao ou blocker\"],
  \"proxima_acao\": \"acao concreta com canal e prazo\",
  \"justificativa_acao\": \"racional estrategico\",
  \"probabilidade\": \"Alta|Media|Baixa\",
  \"mensagem_sugerida\": \"mensagem pronta para enviar (3-4 linhas)\"
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
        $lead = $this->connection->fetchAssociative("SELECT id, email, firstname, lastname, company FROM leads WHERE id = :id", ['id' => $leadId]);
        if (!$lead) return ['error' => 'Lead não encontrado'];
        if (empty($lead['email'])) return ['error' => 'Lead não possui e-mail cadastrado'];

        $to = $lead['email'];
        $leadName = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? ''));
        $now = date('Y-m-d H:i:s');

        $smtpConfig = $this->getMauticSmtpConfig();
        if (isset($smtpConfig['error'])) return $smtpConfig;

        $fromEmail = $smtpConfig['from_email'];
        $fromName = $smtpConfig['from_name'];
        $htmlBody = $this->buildEmailHtml($body, $leadName, $fromName);

        $sent = $this->sendViaSmtp($smtpConfig, $fromEmail, $fromName, $to, $leadName, $subject, $htmlBody);

        $this->connection->insert('email_stats', [
            'lead_id' => $leadId,
            'email_address' => $to,
            'date_sent' => $now,
            'is_read' => 0,
            'is_failed' => $sent ? 0 : 1,
            'viewed_in_browser' => 0,
            'source' => 'letswork_analytics',
        ]);

        $this->connection->insert('lead_notes', [
            'lead_id' => $leadId,
            'is_published' => 1,
            'date_added' => $now,
            'created_by' => 1,
            'created_by_user' => 'admin',
            'text' => "📧 E-mail enviado: $subject" . ($sent ? '' : ' [FALHA]'),
            'type' => 'general',
            'date_time' => $now,
        ]);

        if ($this->columnExists('leads', 'data_ultima_interacao')) {
            $this->connection->executeStatement(
                "UPDATE leads SET data_ultima_interacao = :now, date_modified = :now WHERE id = :id",
                ['now' => $now, 'id' => $leadId]
            );
        }

        if (!$sent) return ['error' => 'Falha ao enviar email via SMTP. Verifique configuração do Mautic.'];
        return ['success' => true, 'message' => "E-mail enviado para $to"];
    }

    private function getMauticSmtpConfig(): array
    {
        $configPaths = [
            '/var/www/html/config/local.php',
            '/var/www/html/app/config/local.php',
        ];

        $config = [];
        foreach ($configPaths as $path) {
            if (file_exists($path)) {
                $config = include $path;
                break;
            }
        }

        $host = $config['mailer_host'] ?? ($config['parameters']['mailer_host'] ?? '');
        $port = (int)($config['mailer_port'] ?? ($config['parameters']['mailer_port'] ?? 587));
        $user = $config['mailer_user'] ?? ($config['parameters']['mailer_user'] ?? '');
        $password = $config['mailer_password'] ?? ($config['parameters']['mailer_password'] ?? '');
        $encryption = $config['mailer_encryption'] ?? ($config['parameters']['mailer_encryption'] ?? 'tls');
        $fromEmail = $config['mailer_from_email'] ?? ($config['parameters']['mailer_from_email'] ?? '');
        $fromName = $config['mailer_from_name'] ?? ($config['parameters']['mailer_from_name'] ?? 'LetsWork');

        if (!$host && isset($config['mailer_dsn'])) {
            $dsn = $config['mailer_dsn'];
            if (preg_match('#smtp://([^:]*):([^@]*)@([^:]+):?(\d+)?#', $dsn, $m)) {
                $user = urldecode($m[1]);
                $password = urldecode($m[2]);
                $host = $m[3];
                $port = isset($m[4]) ? (int)$m[4] : 587;
            }
        }
        if (!$host && isset($config['parameters']['mailer_dsn'])) {
            $dsn = $config['parameters']['mailer_dsn'];
            if (preg_match('#smtp://([^:]*):([^@]*)@([^:]+):?(\d+)?#', $dsn, $m)) {
                $user = urldecode($m[1]);
                $password = urldecode($m[2]);
                $host = $m[3];
                $port = isset($m[4]) ? (int)$m[4] : 587;
            }
        }

        if (!$host) {
            $host = getenv('SMTP_HOST') ?: ($_ENV['SMTP_HOST'] ?? '');
            $port = (int)(getenv('SMTP_PORT') ?: ($_ENV['SMTP_PORT'] ?? 587));
            $user = getenv('SMTP_USER') ?: ($_ENV['SMTP_USER'] ?? '');
            $password = getenv('SMTP_PASSWORD') ?: ($_ENV['SMTP_PASSWORD'] ?? '');
        }

        if (!$fromEmail) {
            $fromEmail = getenv('MAUTIC_ADMIN_EMAIL') ?: ($_ENV['MAUTIC_ADMIN_EMAIL'] ?? $user);
        }

        if (!$host) return ['error' => 'SMTP não configurado. Configure email nas Configurações do Mautic.'];

        return [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'encryption' => $encryption,
            'from_email' => $fromEmail,
            'from_name' => $fromName,
        ];
    }

    private function sendViaSmtp(array $config, string $fromEmail, string $fromName, string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $host = $config['host'];
        $port = $config['port'];
        $user = $config['user'];
        $password = $config['password'];
        $encryption = $config['encryption'];

        $contextOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
        $context = stream_context_create($contextOptions);

        $prefix = ($encryption === 'ssl' || $port === 465) ? 'ssl://' : 'tcp://';
        $socket = @stream_socket_client($prefix . $host . ':' . $port, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) return false;

        $response = fgets($socket, 512);
        if (strpos($response, '220') !== 0) { fclose($socket); return false; }

        fwrite($socket, "EHLO localhost\r\n");
        $this->smtpReadAll($socket);

        if ($encryption === 'tls' && $port !== 465) {
            fwrite($socket, "STARTTLS\r\n");
            $response = fgets($socket, 512);
            if (strpos($response, '220') !== 0) { fclose($socket); return false; }
            $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
            if (!$crypto) { fclose($socket); return false; }
            fwrite($socket, "EHLO localhost\r\n");
            $this->smtpReadAll($socket);
        }

        if ($user && $password) {
            fwrite($socket, "AUTH LOGIN\r\n");
            $this->smtpReadAll($socket);
            fwrite($socket, base64_encode($user) . "\r\n");
            $this->smtpReadAll($socket);
            fwrite($socket, base64_encode($password) . "\r\n");
            $response = $this->smtpReadAll($socket);
            if (strpos($response, '235') === false) { fclose($socket); return false; }
        }

        fwrite($socket, "MAIL FROM:<$fromEmail>\r\n");
        $this->smtpReadAll($socket);
        fwrite($socket, "RCPT TO:<$toEmail>\r\n");
        $response = $this->smtpReadAll($socket);
        if (strpos($response, '250') === false) { fclose($socket); return false; }

        fwrite($socket, "DATA\r\n");
        $this->smtpReadAll($socket);

        $boundary = md5(uniqid());
        $headers = "From: $fromName <$fromEmail>\r\n";
        $headers .= "To: $toName <$toEmail>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Message-ID: <" . uniqid('lwa_') . "@letswork>\r\n";
        $headers .= "\r\n";
        $headers .= chunk_split(base64_encode($htmlBody));
        $headers .= "\r\n.\r\n";

        fwrite($socket, $headers);
        $response = $this->smtpReadAll($socket);
        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return strpos($response, '250') !== false;
    }

    private function smtpReadAll($socket): string
    {
        $response = '';
        stream_set_timeout($socket, 5);
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            $meta = stream_get_meta_data($socket);
            if ($meta['timed_out']) break;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $response;
    }

    private function buildEmailHtml(string $body, string $leadName, string $senderName): string
    {
        $bodyHtml = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:32px 16px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06)">
<tr><td style="padding:32px 40px 24px;border-bottom:1px solid #e4e4e7">
<div style="font-size:20px;font-weight:700;color:#18181b">LetsWork</div>
</td></tr>
<tr><td style="padding:32px 40px">
<div style="font-size:15px;line-height:1.7;color:#3f3f46">' . $bodyHtml . '</div>
</td></tr>
<tr><td style="padding:24px 40px 32px;border-top:1px solid #e4e4e7">
<div style="font-size:13px;color:#71717a;line-height:1.5">
<strong style="color:#3f3f46">' . htmlspecialchars($senderName) . '</strong><br>
LetsWork — Gestão de Pessoas
</div>
</td></tr>
</table>
</td></tr></table>
</body></html>';
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
            'utm' => $this->buildUtmPrompt($dateRange),
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

    private function buildUtmPrompt(array $dateRange): string
    {
        $utmData = $this->getUtmData($dateRange);
        $sources = implode(', ', array_map(function($s) { return $s['utm_source'] . " ({$s['leads']} leads)"; }, $utmData['by_source']));
        $campaigns = implode(', ', array_map(function($c) { return $c['utm_campaign'] . " ({$c['leads']} leads)"; }, $utmData['by_campaign']));
        return "Voce e analista de marketing digital da LetsWork (software RH). Analise a performance das campanhas UTM e sugira otimizacoes.

DADOS UTM:
- Total leads rastreados: {$utmData['total']}
- Sources: $sources
- Campanhas: $campaigns

Responda em JSON: {\"titulo\": \"titulo\", \"insights\": [\"insight 1\", \"insight 2\", \"insight 3\"], \"melhor_source\": \"source com mais conversao\", \"acao_principal\": \"o que fazer para melhorar ROI das campanhas\", \"campanha_sugerida\": \"ideia de nova campanha baseada nos dados\"}";
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

    private function getGeminiApiKey(): string
    {
        static $cached = null;
        if ($cached !== null) return $cached;

        // 1. Environment variables (Docker container, Apache SetEnv, .htaccess)
        $apiKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? ($_SERVER['GEMINI_API_KEY'] ?? ($_SERVER['REDIRECT_GEMINI_API_KEY'] ?? '')));

        // 2. Plugin .env files (relative to Model/ dir — works both local and Docker mount)
        if (!$apiKey) {
            $envFiles = [
                __DIR__ . '/../.env',
                __DIR__ . '/../.env.example',
                '/var/www/html/plugins/LetsworkAnalyticsBundle/.env',
                '/var/www/html/plugins/LetsworkAnalyticsBundle/.env.example',
                '/var/www/html/.env',
                '/var/www/html/.env.local',
            ];
            foreach ($envFiles as $envFile) {
                if (!file_exists($envFile)) continue;
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos($line, '#') === 0) continue;
                    if (strpos($line, 'GEMINI_API_KEY=') === 0) {
                        $apiKey = trim(substr($line, 15));
                        if ($apiKey) break 2;
                    }
                }
            }
        }

        // 3. Mautic local.php config (custom parameter)
        if (!$apiKey) {
            $configPaths = ['/var/www/html/config/local.php', '/var/www/html/app/config/local.php'];
            foreach ($configPaths as $path) {
                if (!file_exists($path)) continue;
                $config = include $path;
                $params = $config['parameters'] ?? $config;
                if (!empty($params['gemini_api_key'])) { $apiKey = $params['gemini_api_key']; break; }
            }
        }

        $cached = $apiKey ?: '';
        return $cached;
    }

    private function callGemini(string $prompt): array
    {
        $apiKey = $this->getGeminiApiKey();
        if (!$apiKey) return ['error' => 'GEMINI_API_KEY não configurada. Adicione no .env do Mautic ou nas variáveis de ambiente do container.'];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$apiKey";

        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 1000],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) return ['error' => 'Erro de conexão com Gemini: ' . $curlError];
        if ($httpCode === 429) return ['error' => 'Limite de requisições Gemini atingido. Aguarde 1 minuto.'];
        if ($httpCode === 403) return ['error' => 'API key Gemini inválida ou sem permissão.'];
        if ($httpCode !== 200) return ['error' => 'Gemini retornou HTTP ' . $httpCode];

        $data = json_decode($response, true);
        if (!is_array($data)) return ['error' => 'Resposta inválida da IA (não é JSON).'];
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (!$text) {
            $blockReason = $data['promptFeedback']['blockReason'] ?? '';
            if ($blockReason) return ['error' => 'Prompt bloqueado pela IA: ' . $blockReason];
            return ['error' => 'IA retornou resposta vazia.'];
        }

        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        $text = trim($text);

        // Try array first (most prompts expect array response)
        if (preg_match('/\[[\s\S]*\]/s', $text, $matches)) {
            $parsed = json_decode($matches[0], true);
            if ($parsed && is_array($parsed)) return $parsed;
        }
        // Then try object
        if (preg_match('/\{[\s\S]*\}/s', $text, $matches)) {
            $parsed = json_decode($matches[0], true);
            if ($parsed && is_array($parsed)) return $parsed;
        }

        return ['titulo' => 'Análise', 'insights' => [$text]];
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

        $prompt = "Voce e o analista de dados comercial da LetsWork (software de RH/DP). Analise estas metricas do CRM e gere 4 insights estrategicos curtos e acionaveis para o time comercial.

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
- Foque em: onde estamos perdendo, onde estamos ganhando, o que pode melhorar AGORA
- Sugira acoes concretas com responsavel e prazo
- Tom direto e objetivo, como um VP de vendas falaria no standup
- Se leads quentes sem follow-up > 0: SEMPRE alertar (dinheiro na mesa)
- Se taxa email < 20%: alertar sobre qualidade do assunto/segmentacao

Responda APENAS em JSON:
[
  {\"tipo\": \"alerta|oportunidade|melhoria|sucesso\", \"titulo\": \"titulo curto (max 6 palavras)\", \"descricao\": \"descricao de 1-2 linhas com acao concreta\"},
  {\"tipo\": \"...\", \"titulo\": \"...\", \"descricao\": \"...\"},
  {\"tipo\": \"...\", \"titulo\": \"...\", \"descricao\": \"...\"},
  {\"tipo\": \"...\", \"titulo\": \"...\", \"descricao\": \"...\"}
]";

        $result = $this->callGemini($prompt);

        if (isset($result['error'])) return [['tipo' => 'alerta', 'titulo' => 'Erro IA', 'descricao' => $result['error']]];

        // Gemini returned array of insights directly
        if (isset($result[0]) && is_array($result[0]) && isset($result[0]['tipo'])) return $result;

        // Gemini returned single object with array inside
        if (isset($result['insights']) && is_array($result['insights'])) {
            $formatted = [];
            foreach ($result['insights'] as $i) {
                if (is_array($i) && isset($i['tipo'])) {
                    $formatted[] = $i;
                } elseif (is_string($i)) {
                    $formatted[] = ['tipo' => 'melhoria', 'titulo' => 'Insight', 'descricao' => $i];
                }
            }
            return $formatted ?: [['tipo' => 'melhoria', 'titulo' => $result['titulo'] ?? 'Análise', 'descricao' => $result['insights'][0] ?? 'Sem dados']];
        }

        // Gemini returned single insight object
        if (isset($result['tipo']) && isset($result['descricao'])) return [$result];

        // Fallback: raw text came back
        if (isset($result['titulo'])) {
            return [['tipo' => 'melhoria', 'titulo' => $result['titulo'], 'descricao' => is_array($result['insights'] ?? null) ? implode(' ', $result['insights']) : ($result['descricao'] ?? 'Sem dados')]];
        }

        return [['tipo' => 'melhoria', 'titulo' => 'Dados insuficientes', 'descricao' => 'Adicione mais leads para gerar insights automáticos.']];
    }

    // --- AI (Gemini) ---

    public function getAiSuggestion(array $lead): array
    {
        $leadId = (int)($lead['id'] ?? 0);
        $name = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? ''));
        $status = str_replace('_', ' ', $lead['status_comercial'] ?? '');

        $timeline = $leadId ? $this->getLeadTimeline($leadId) : [];
        $timelineText = implode("\n", array_map(function($t) {
            return date('d/m', strtotime($t['date'])) . " [{$t['type']}] {$t['text']}";
        }, array_slice($timeline, 0, 10)));

        $daysSinceLastInteraction = 'desconhecido';
        if (!empty($lead['data_ultima_interacao'])) {
            $days = (int)((time() - strtotime($lead['data_ultima_interacao'])) / 86400);
            $daysSinceLastInteraction = $days . ' dias';
        }

        $emailsOpened = 0;
        if ($leadId) {
            $emailsOpened = (int)$this->connection->fetchOne(
                "SELECT COUNT(*) FROM email_stats WHERE lead_id = :id AND is_read = 1",
                ['id' => $leadId]
            );
        }

        $modulos = $this->getTopModulos();
        $modList = implode(', ', array_column($modulos, 'modulo_interesse')) ?: 'gestao de RH';

        $prompt = "Voce e o estrategista comercial senior da LetsWork (plataforma de gestao de pessoas para RH/DP).
Modulos: $modList.

Analise este lead em PROFUNDIDADE e recomende a proxima acao mais efetiva:

LEAD:
- Nome: $name | Empresa: {$lead['company']} | Cargo: {$lead['cargo']}
- Segmento: {$lead['segmento']} | Modulo: {$lead['modulo_interesse']}
- Dor: {$lead['dor_principal']}
- Temperatura: {$lead['temperatura']} | Score: {$lead['interesse_score']} | Status: $status
- Origem: {$lead['origem_principal']}
- Dias desde ultima interacao: $daysSinceLastInteraction
- Emails abertos: $emailsOpened

TIMELINE RECENTE:
$timelineText

CONTEXTO ESTRATEGICO:
- Se lead NAO interagiu em >7 dias → precisa de nova abordagem/canal
- Se lead abriu emails mas nao respondeu → dor existe, abordagem errada
- Se score alto + sem reuniao → bloqueio no decisor, escalar
- Se temperatura fria + score baixo → desqualificar ou nutrir com conteudo
- Se lead perdido → analisar motivo_perda e reativar se fizer sentido

REGRAS:
- Proxima acao DEVE ser especifica (canal + acao + prazo)
- Mensagem de follow-up: 2-3 linhas, consultiva, citar dor especifica
- Se nao ha dados suficientes, sugira qualificacao antes de acao comercial
- Tom: direto, estrategico, sem enrolacao

Responda APENAS em JSON valido:
{
  \"proxima_acao\": \"acao especifica com canal e prazo\",
  \"mensagem_followup\": \"mensagem pronta para enviar (2-3 linhas)\",
  \"intencao\": \"Sem interesse | Curioso | Dor identificada | Pronto para reuniao | Negociando\",
  \"probabilidade_conversao\": \"Alta | Media | Baixa\",
  \"dica_comercial\": \"insight estrategico unico para esse lead\"
}";

        $result = $this->callGemini($prompt);

        if (isset($result['error'])) return $result;
        if (isset($result['proxima_acao'])) return $result;

        return ['proxima_acao' => 'Analisar manualmente', 'mensagem_followup' => $result['insights'][0] ?? '-', 'intencao' => '-', 'probabilidade_conversao' => '-', 'dica_comercial' => '-'];
    }

    public function generateEmailForLead(int $leadId): array
    {
        $lead = $this->getLeadDetail($leadId);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $timeline = $this->getLeadTimeline($leadId);
        $recentEvents = array_slice($timeline, 0, 10);
        $timelineText = implode("\n", array_map(function($t) {
            return date('d/m', strtotime($t['date'])) . " [{$t['type']}] {$t['text']}";
        }, $recentEvents));

        $name = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? ''));
        $firstName = trim($lead['firstname'] ?? '') ?: $name;
        $status = str_replace('_', ' ', $lead['status_comercial'] ?? 'prospeccao');
        $temp = strtolower($lead['temperatura'] ?? 'frio');

        $cenario = 'primeiro_contato';
        if (in_array($status, ['perdido', 'Perdido'])) {
            $cenario = 'reativacao';
        } elseif (in_array($status, ['negociacao', 'proposta enviada', 'proposta_enviada'])) {
            $cenario = 'negociacao';
        } elseif (in_array($status, ['reuniao agendada', 'reuniao_agendada'])) {
            $cenario = 'pos_reuniao';
        } elseif (in_array($status, ['engajado', 'Engajado']) || $temp === 'quente') {
            $cenario = 'followup_quente';
        } elseif ($temp === 'morno') {
            $cenario = 'nutricao';
        }

        $cenarioInstructions = [
            'primeiro_contato' => 'Email de primeiro contato. Deve ser curto (3-4 paragrafos), gerar curiosidade sobre como a LetsWork resolve a dor do lead, e terminar com CTA para agendar conversa de 15 min. Tom: consultivo, leve, sem pressao.',
            'followup_quente' => 'Follow-up para lead quente que ja demonstrou interesse. Referenciar interacoes anteriores, reforcar valor especifico para a empresa, e propor proximo passo concreto (demo, reuniao, proposta). Tom: direto e confiante.',
            'nutricao' => 'Email de nutricao para lead morno. Compartilhar insight relevante para o segmento/cargo do lead, posicionar LetsWork como especialista, sem vender diretamente. Terminar com pergunta aberta. Tom: educativo e generoso.',
            'negociacao' => 'Email para lead em negociacao. Reforcar diferenciais vs concorrencia, tratar possiveis objecoes do segmento, criar senso de urgencia sutil (ex: vagas no onboarding). Tom: profissional e assertivo.',
            'pos_reuniao' => 'Follow-up pos-reuniao. Recapitular pontos discutidos (inferir da timeline), confirmar proximos passos, e enviar material relevante. Tom: organizado e proativo.',
            'reativacao' => 'Reativacao de lead perdido. Reconhecer que nao deu certo antes, apresentar novidade ou melhoria relevante, oferecer valor sem compromisso (ex: diagnostico gratuito). Tom: humilde e renovado.',
        ];

        $instrucao = $cenarioInstructions[$cenario];

        $prompt = "Voce e o copywriter comercial da LetsWork, plataforma de gestao de pessoas (RH/DP).
Gere um email comercial COMPLETO para este lead.

CONTEXTO DO LEAD:
- Nome: $name (primeiro nome: $firstName)
- Empresa: {$lead['company']} | Cargo: {$lead['cargo']}
- Segmento: {$lead['segmento']} | Modulo de interesse: {$lead['modulo_interesse']}
- Dor principal: {$lead['dor_principal']}
- Temperatura: {$lead['temperatura']} | Score: {$lead['interesse_score']}
- Status comercial: $status
- Origem: {$lead['origem_principal']}

TIMELINE RECENTE:
$timelineText

CENARIO: $cenario
INSTRUCAO: $instrucao

REGRAS DE COPYWRITING:
- Assunto: maximo 50 caracteres, gerar curiosidade, sem ALL CAPS ou pontuacao excessiva
- Corpo: usar primeiro nome do lead, ser especifico sobre a empresa/cargo/dor
- Paragrafos curtos (2-3 linhas max cada)
- CTA claro no final (1 so, nao multiplos)
- Assinatura: nao incluir (sera adicionada automaticamente)
- Tom: humano, consultivo, como se fosse de um especialista que realmente entende o problema
- NAO usar: 'Prezado', 'Venho por meio desta', jargoes corporativos genericos
- PODE usar: nome da empresa do lead, dados especificos do segmento, tendencias do mercado
- Escrever em portugues brasileiro natural

Responda APENAS em JSON valido:
{
  \"assunto\": \"assunto do email (max 50 chars)\",
  \"corpo\": \"corpo completo do email com quebras de linha usando \\n\",
  \"cenario_detectado\": \"$cenario\"
}";

        $result = $this->callGemini($prompt);

        if (isset($result['error'])) return $result;
        if (isset($result['assunto']) && isset($result['corpo'])) return $result;

        return ['assunto' => 'Follow-up LetsWork', 'corpo' => $result['mensagem_followup'] ?? $result['insights'][0] ?? 'Erro ao gerar email', 'cenario_detectado' => $cenario];
    }

    // =============================================
    // CADÊNCIA AUTOMATIZADA (Sequences)
    // =============================================

    public function getCadencias(): array
    {
        if (!$this->tableExists('letswork_cadencias')) return [];
        return $this->connection->fetchAllAssociative(
            "SELECT c.*, (SELECT COUNT(*) FROM letswork_cadencia_leads cl WHERE cl.cadencia_id = c.id AND cl.status = 'ativa') as leads_ativos
             FROM letswork_cadencias c WHERE c.is_active = 1 ORDER BY c.created_at DESC"
        );
    }

    public function createCadencia(string $nome, array $steps, string $descricao = ''): array
    {
        if (!$this->tableExists('letswork_cadencias')) return ['error' => 'Tabela letswork_cadencias não existe. Execute install.sql.'];
        $this->connection->insert('letswork_cadencias', [
            'nome' => $nome,
            'descricao' => $descricao,
            'steps' => json_encode($steps),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'id' => (int)$this->connection->lastInsertId(), 'message' => "Cadência '$nome' criada"];
    }

    public function addLeadToCadencia(int $leadId, int $cadenciaId): array
    {
        if (!$this->tableExists('letswork_cadencia_leads')) return ['error' => 'Tabela não existe'];
        $lead = $this->connection->fetchAssociative("SELECT id FROM leads WHERE id = :id", ['id' => $leadId]);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $exists = $this->connection->fetchOne("SELECT COUNT(*) FROM letswork_cadencia_leads WHERE lead_id = :lead AND cadencia_id = :cad AND status = 'ativa'", ['lead' => $leadId, 'cad' => $cadenciaId]);
        if ($exists) return ['error' => 'Lead já está nesta cadência'];

        $cadencia = $this->connection->fetchAssociative("SELECT steps FROM letswork_cadencias WHERE id = :id", ['id' => $cadenciaId]);
        if (!$cadencia) return ['error' => 'Cadência não encontrada'];

        $steps = json_decode($cadencia['steps'], true);
        $firstStepDay = $steps[0]['day'] ?? 0;
        $nextAction = date('Y-m-d H:i:s', strtotime("+{$firstStepDay} days"));

        $this->connection->insert('letswork_cadencia_leads', [
            'cadencia_id' => $cadenciaId,
            'lead_id' => $leadId,
            'current_step' => 0,
            'status' => 'ativa',
            'started_at' => date('Y-m-d H:i:s'),
            'next_action_at' => $nextAction,
        ]);

        $this->connection->insert('lead_notes', [
            'lead_id' => $leadId, 'is_published' => 1, 'date_added' => date('Y-m-d H:i:s'),
            'created_by' => 1, 'created_by_user' => 'admin',
            'text' => "🔄 Adicionado à cadência (step 1/" . count($steps) . ")",
            'type' => 'general', 'date_time' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'message' => 'Lead adicionado à cadência', 'next_action' => $nextAction];
    }

    public function getLeadCadenciaStatus(int $leadId): ?array
    {
        if (!$this->tableExists('letswork_cadencia_leads')) return null;
        $active = $this->connection->fetchAssociative(
            "SELECT cl.*, c.nome, c.steps FROM letswork_cadencia_leads cl
             JOIN letswork_cadencias c ON c.id = cl.cadencia_id
             WHERE cl.lead_id = :id AND cl.status = 'ativa' ORDER BY cl.started_at DESC LIMIT 1",
            ['id' => $leadId]
        );
        if (!$active) return null;
        $active['steps_parsed'] = json_decode($active['steps'], true);
        $active['total_steps'] = count($active['steps_parsed']);
        return $active;
    }

    // =============================================
    // COMPARATIVO PERÍODO
    // =============================================

    public function getComparativo(array $dateRange): array
    {
        $from = $dateRange['from'];
        $to = $dateRange['to'];
        $days = max(1, (strtotime($to) - strtotime($from)) / 86400);
        $prevFrom = date('Y-m-d', strtotime($from . " -{$days} days"));
        $prevTo = date('Y-m-d', strtotime($from . ' -1 day'));
        $prevRange = ['from' => $prevFrom, 'to' => $prevTo];

        $current = [
            'ganhos' => $this->getCountByStatus($dateRange, 'ganho'),
            'perdidos' => $this->getCountByStatus($dateRange, 'perdido'),
            'reunioes' => $this->getReunioesCount($dateRange),
            'frio_to_morno' => $this->getFrioToMorno($dateRange),
        ];
        $previous = [
            'ganhos' => $this->getCountByStatus($prevRange, 'ganho'),
            'perdidos' => $this->getCountByStatus($prevRange, 'perdido'),
            'reunioes' => $this->getReunioesCount($prevRange),
            'frio_to_morno' => $this->getFrioToMorno($prevRange),
        ];

        $comparativo = [];
        foreach ($current as $key => $val) {
            $prev = $previous[$key] ?? 0;
            $diff = $prev > 0 ? round((($val - $prev) / $prev) * 100) : ($val > 0 ? 100 : 0);
            $comparativo[$key] = ['current' => $val, 'previous' => $prev, 'diff' => $diff];
        }

        return $comparativo;
    }

    // =============================================
    // RELATÓRIO SEMANAL IA
    // =============================================

    public function generateWeeklyReport(): array
    {
        $dateRange = ['from' => date('Y-m-d', strtotime('-7 days')), 'to' => date('Y-m-d')];

        $data = [
            'novos_leads' => (int)$this->connection->fetchOne("SELECT COUNT(*) FROM leads WHERE date_added >= :from AND ((email IS NOT NULL AND email != '') OR (firstname IS NOT NULL AND firstname != ''))", ['from' => $dateRange['from']]),
            'ganhos' => $this->getCountByStatus($dateRange, 'ganho'),
            'perdidos' => $this->getCountByStatus($dateRange, 'perdido'),
            'reunioes' => $this->getReunioesCount($dateRange),
            'hot_no_followup' => $this->getHotWithoutFollowup(),
            'valor_pipeline' => $this->getValorPipeline(),
            'taxa_conversao' => 0,
        ];

        $totalActive = (int)$this->connection->fetchOne("SELECT COUNT(*) FROM leads WHERE status_comercial IS NOT NULL AND LOWER(status_comercial) NOT IN ('','ganho','perdido')");
        if ($totalActive > 0) $data['taxa_conversao'] = round(($data['ganhos'] / max(1, $totalActive)) * 100, 1);

        $topOportunidades = $this->connection->fetchAllAssociative(
            "SELECT firstname, lastname, company, valor_potencial, temperatura, interesse_score
             FROM leads WHERE LOWER(status_comercial) IN ('proposta_enviada','negociacao','reuniao_agendada')
             AND valor_potencial > 0 ORDER BY valor_potencial DESC LIMIT 5"
        );

        $prompt = "Gere um resumo executivo semanal para o gestor comercial da LetsWork (software RH/DP).

MÉTRICAS DA SEMANA:
- Novos leads: {$data['novos_leads']}
- Reuniões: {$data['reunioes']}
- Ganhos: {$data['ganhos']} | Perdidos: {$data['perdidos']}
- Pipeline ativo: R$ " . number_format($data['valor_pipeline'], 0, ',', '.') . "
- Leads quentes sem ação: {$data['hot_no_followup']}
- Taxa de conversão: {$data['taxa_conversao']}%

TOP OPORTUNIDADES:
" . implode("\n", array_map(function($l) {
            return "- " . trim(($l['firstname']??'').' '.($l['lastname']??'')) . " ({$l['company']}) — R$ " . number_format((float)$l['valor_potencial'],0,',','.') . " [{$l['temperatura']}]";
        }, $topOportunidades)) . "

Gere em JSON:
{
  \"titulo\": \"Resumo Semanal (data)\",
  \"destaque\": \"frase principal da semana (1 linha)\",
  \"pontos_positivos\": [\"ponto 1\", \"ponto 2\"],
  \"pontos_atencao\": [\"atenção 1\", \"atenção 2\"],
  \"acoes_prioritarias\": [\"ação 1 com prazo\", \"ação 2\"],
  \"previsao_proxima_semana\": \"previsão em 1 linha\"
}";

        $aiReport = $this->callGemini($prompt);

        return ['data' => $data, 'top_oportunidades' => $topOportunidades, 'ai_report' => $aiReport];
    }

    // =============================================
    // IA COACH — Feedback pós-interação
    // =============================================

    public function getIaCoachFeedback(int $leadId, string $action, string $content = ''): array
    {
        $lead = $this->getLeadDetail($leadId);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $name = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? ''));
        $timeline = $this->getLeadTimeline($leadId);
        $recentText = implode("\n", array_map(function($t) {
            return date('d/m', strtotime($t['date'])) . " [{$t['type']}] {$t['text']}";
        }, array_slice($timeline, 0, 5)));

        $prompt = "Voce e o coach de vendas da LetsWork. O vendedor acabou de executar uma acao com um lead.
Dê feedback CONSTRUTIVO e ESPECIFICO (não genérico).

AÇÃO EXECUTADA: $action
CONTEÚDO: " . mb_substr($content, 0, 200) . "

LEAD:
- Nome: $name | Empresa: {$lead['company']} | Cargo: {$lead['cargo']}
- Segmento: {$lead['segmento']} | Temperatura: {$lead['temperatura']}
- Status: {$lead['status_comercial']}

HISTÓRICO RECENTE:
$recentText

REGRAS DO COACH:
- Se email: avaliar comprimento, tom, CTA, personalização
- Se WhatsApp: avaliar se é curto demais ou longo demais
- Se follow-up: avaliar timing (muito cedo? muito tarde?)
- Sempre sugerir o PRÓXIMO passo ideal com timing
- Tom: direto, construtivo, como mentor experiente
- Max 3 linhas de feedback + 1 sugestão concreta

Responda em JSON:
{
  \"feedback\": \"feedback em 2-3 linhas\",
  \"nota\": 1-10,
  \"sugestao\": \"próximo passo sugerido\",
  \"timing\": \"quando fazer (ex: amanhã 10h)\"
}";

        return $this->callGemini($prompt);
    }

    // =============================================
    // KANBAN DATA
    // =============================================

    public function getKanbanData(array $dateRange): array
    {
        $columns = [
            ['key' => 'prospeccao', 'label' => 'Prospecção', 'color' => '#94a3b8'],
            ['key' => 'engajado', 'label' => 'Engajado', 'color' => '#3b82f6'],
            ['key' => 'reuniao_agendada', 'label' => 'Reunião', 'color' => '#8b5cf6'],
            ['key' => 'proposta_enviada', 'label' => 'Proposta', 'color' => '#f59e0b'],
            ['key' => 'negociacao', 'label' => 'Negociação', 'color' => '#ec4899'],
            ['key' => 'ganho', 'label' => 'Ganho', 'color' => '#10b981'],
            ['key' => 'perdido', 'label' => 'Perdido', 'color' => '#dc2626'],
        ];

        $leads = $this->connection->fetchAllAssociative(
            "SELECT id, firstname, lastname, company, temperatura, interesse_score, status_comercial, valor_potencial, data_ultima_interacao, responsavel_comercial, segmento
             FROM leads
             WHERE (status_comercial IS NULL OR LOWER(status_comercial) NOT IN (''))
               AND ((email IS NOT NULL AND email != '') OR (firstname IS NOT NULL AND firstname != ''))
             ORDER BY interesse_score DESC
             LIMIT 200"
        );

        $board = [];
        foreach ($columns as $col) {
            $board[$col['key']] = ['label' => $col['label'], 'color' => $col['color'], 'leads' => [], 'total_valor' => 0];
        }

        foreach ($leads as $l) {
            $status = strtolower(trim($l['status_comercial'] ?? 'prospeccao')) ?: 'prospeccao';
            $status = str_replace(['ã', 'ç'], ['a', 'c'], $status);
            if (!isset($board[$status])) $status = 'prospeccao';
            $board[$status]['leads'][] = $l;
            $board[$status]['total_valor'] += (float)($l['valor_potencial'] ?? 0);
        }

        return ['columns' => $columns, 'board' => $board];
    }

    // =============================================
    // INBOX DATA
    // =============================================

    public function getInboxData(array $dateRange): array
    {
        $items = [];

        // Follow-ups vencidos
        if ($this->columnExists('leads', 'data_proximo_followup')) {
            $overdue = $this->connection->fetchAllAssociative(
                "SELECT id, firstname, lastname, company, temperatura, data_proximo_followup, status_comercial, responsavel_comercial
                 FROM leads WHERE data_proximo_followup < NOW() AND data_proximo_followup IS NOT NULL
                 AND (status_comercial IS NULL OR LOWER(status_comercial) NOT IN ('ganho','perdido'))
                 ORDER BY data_proximo_followup ASC LIMIT 20"
            );
            foreach ($overdue as $l) {
                $items[] = ['type' => 'overdue', 'lead' => $l, 'priority' => 'high',
                    'title' => 'Follow-up vencido', 'date' => $l['data_proximo_followup']];
            }
        }

        // Leads novos sem contato (últimos 7 dias)
        $newLeads = $this->connection->fetchAllAssociative(
            "SELECT id, firstname, lastname, company, temperatura, date_added, origem_principal
             FROM leads WHERE date_added >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             AND (data_ultima_interacao IS NULL OR data_ultima_interacao = date_added)
             AND (status_comercial IS NULL OR LOWER(status_comercial) IN ('','prospeccao','novo'))
             AND ((email IS NOT NULL AND email != '') OR (firstname IS NOT NULL AND firstname != ''))
             ORDER BY date_added DESC LIMIT 15"
        );
        foreach ($newLeads as $l) {
            $items[] = ['type' => 'new_lead', 'lead' => $l, 'priority' => 'medium',
                'title' => 'Novo lead sem contato', 'date' => $l['date_added']];
        }

        // Emails abertos sem follow-up
        if ($this->tableExists('email_stats')) {
            $opened = $this->connection->fetchAllAssociative(
                "SELECT l.id, l.firstname, l.lastname, l.company, l.temperatura, MAX(es.date_sent) as last_email
                 FROM email_stats es JOIN leads l ON l.id = es.lead_id
                 WHERE es.is_read = 1 AND es.date_sent >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 AND (l.data_proximo_followup IS NULL OR l.data_proximo_followup < NOW())
                 AND (l.status_comercial IS NULL OR LOWER(l.status_comercial) NOT IN ('ganho','perdido'))
                 GROUP BY l.id, l.firstname, l.lastname, l.company, l.temperatura
                 HAVING last_email >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 ORDER BY last_email DESC LIMIT 10"
            );
            foreach ($opened as $l) {
                $items[] = ['type' => 'email_opened', 'lead' => $l, 'priority' => 'medium',
                    'title' => 'Email aberto, sem follow-up', 'date' => $l['last_email']];
            }
        }

        // Leads quentes sem ação
        if ($this->columnExists('leads', 'temperatura')) {
            $hot = $this->connection->fetchAllAssociative(
                "SELECT id, firstname, lastname, company, temperatura, interesse_score, data_ultima_interacao
                 FROM leads WHERE LOWER(temperatura) IN ('quente','prioritario','prioritário')
                 AND (data_proximo_followup IS NULL OR data_proximo_followup < NOW())
                 AND (status_comercial IS NULL OR LOWER(status_comercial) NOT IN ('ganho','perdido'))
                 ORDER BY interesse_score DESC LIMIT 10"
            );
            foreach ($hot as $l) {
                $items[] = ['type' => 'hot_idle', 'lead' => $l, 'priority' => 'critical',
                    'title' => 'Lead quente sem ação', 'date' => $l['data_ultima_interacao']];
            }
        }

        usort($items, function($a, $b) {
            $order = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            return ($order[$a['priority']] ?? 9) - ($order[$b['priority']] ?? 9);
        });

        return ['items' => $items, 'count' => count($items)];
    }

    // =============================================
    // SCORE PREDITIVO IA
    // =============================================

    public function getPredictiveScore(int $leadId): array
    {
        $lead = $this->getLeadDetail($leadId);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $wonLeads = $this->connection->fetchAllAssociative(
            "SELECT segmento, origem_principal, modulo_interesse, interesse_score, temperatura,
                    DATEDIFF(date_modified, date_added) as days_in_funnel
             FROM leads WHERE LOWER(status_comercial) = 'ganho' LIMIT 50"
        );

        $lostLeads = $this->connection->fetchAllAssociative(
            "SELECT segmento, origem_principal, modulo_interesse, interesse_score,
                    DATEDIFF(date_modified, date_added) as days_in_funnel
             FROM leads WHERE LOWER(status_comercial) = 'perdido' LIMIT 50"
        );

        $wonCount = count($wonLeads);
        $lostCount = count($lostLeads);
        if ($wonCount + $lostCount < 5) {
            return ['probability' => null, 'reason' => 'Dados insuficientes (mínimo 5 leads fechados)', 'factors' => []];
        }

        $factors = [];
        $score = 50;

        // Factor: Segmento match
        $wonSegmentos = array_count_values(array_filter(array_column($wonLeads, 'segmento')));
        if (!empty($lead['segmento']) && isset($wonSegmentos[$lead['segmento']])) {
            $pct = ($wonSegmentos[$lead['segmento']] / $wonCount) * 100;
            $boost = min(15, round($pct / 5));
            $score += $boost;
            $factors[] = ['label' => 'Segmento (' . $lead['segmento'] . ')', 'impact' => '+' . $boost, 'positive' => true];
        }

        // Factor: Origem match
        $wonOrigens = array_count_values(array_filter(array_column($wonLeads, 'origem_principal')));
        if (!empty($lead['origem_principal']) && isset($wonOrigens[$lead['origem_principal']])) {
            $pct = ($wonOrigens[$lead['origem_principal']] / $wonCount) * 100;
            $boost = min(10, round($pct / 6));
            $score += $boost;
            $factors[] = ['label' => 'Origem (' . $lead['origem_principal'] . ')', 'impact' => '+' . $boost, 'positive' => true];
        }

        // Factor: Score de interesse
        $avgWonScore = $wonCount > 0 ? array_sum(array_column($wonLeads, 'interesse_score')) / $wonCount : 50;
        $leadScore = (int)($lead['interesse_score'] ?? 0);
        if ($leadScore >= $avgWonScore) {
            $boost = min(15, round(($leadScore - $avgWonScore) / 5));
            $score += $boost;
            $factors[] = ['label' => 'Score acima da média dos ganhos', 'impact' => '+' . $boost, 'positive' => true];
        } else {
            $penalty = min(10, round(($avgWonScore - $leadScore) / 8));
            $score -= $penalty;
            $factors[] = ['label' => 'Score abaixo da média', 'impact' => '-' . $penalty, 'positive' => false];
        }

        // Factor: Temperatura
        $temp = strtolower(str_replace(['á','ó'], ['a','o'], $lead['temperatura'] ?? ''));
        $tempBoost = ['prioritario' => 12, 'quente' => 8, 'morno' => 0, 'frio' => -10];
        $tb = $tempBoost[$temp] ?? -5;
        $score += $tb;
        $factors[] = ['label' => 'Temperatura: ' . ($lead['temperatura'] ?? '-'), 'impact' => ($tb >= 0 ? '+' : '') . $tb, 'positive' => $tb >= 0];

        // Factor: Dias no funil
        $avgDays = $wonCount > 0 ? array_sum(array_column($wonLeads, 'days_in_funnel')) / $wonCount : 30;
        $leadDays = !empty($lead['date_added']) ? (int)((time() - strtotime($lead['date_added'])) / 86400) : 0;
        if ($leadDays > $avgDays * 1.5) {
            $penalty = min(10, round(($leadDays - $avgDays) / 10));
            $score -= $penalty;
            $factors[] = ['label' => 'No funil há ' . $leadDays . ' dias (média: ' . round($avgDays) . ')', 'impact' => '-' . $penalty, 'positive' => false];
        }

        // Factor: Valor potencial
        if (!empty($lead['valor_potencial']) && (float)$lead['valor_potencial'] > 0) {
            $score += 5;
            $factors[] = ['label' => 'Valor definido: R$ ' . number_format((float)$lead['valor_potencial'], 0, ',', '.'), 'impact' => '+5', 'positive' => true];
        }

        $probability = max(5, min(95, $score));

        return ['probability' => $probability, 'factors' => $factors, 'data_quality' => $wonCount + $lostCount . ' leads históricos'];
    }

    // =============================================
    // AUTO-CLASSIFICATION IA
    // =============================================

    public function autoClassifyLead(int $leadId): array
    {
        $lead = $this->getLeadDetail($leadId);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $name = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? ''));

        $prompt = "Voce e o classificador automatico de leads da LetsWork (plataforma RH/DP).
Classifique este lead com base nos dados disponiveis:

LEAD:
- Nome: $name | Empresa: {$lead['company']} | Cargo: {$lead['cargo']}
- Segmento atual: " . ($lead['segmento'] ?: 'NÃO DEFINIDO') . "
- Módulo interesse: " . ($lead['modulo_interesse'] ?: 'NÃO DEFINIDO') . "
- Origem: {$lead['origem_principal']} | Score: {$lead['interesse_score']}

MÓDULOS LETSWORK: Ponto eletrônico, Gestão de férias, Gestão de escalas, Holerite digital, Admissão digital, Treinamentos, Avaliação de desempenho

Responda APENAS em JSON:
{
  \"segmento\": \"segmento inferido (Tecnologia/Saúde/Educação/Indústria/Varejo/Serviços/Financeiro/Construção/Agro/Logística/Outros)\",
  \"modulo_provavel\": \"módulo mais provável baseado no cargo/empresa\",
  \"icp_score\": 0-100,
  \"icp_justificativa\": \"por que esse ICP score\",
  \"temperatura_sugerida\": \"Frio|Morno|Quente\",
  \"perfil\": \"resumo de 1 linha do perfil do lead\"
}";

        $result = $this->callGemini($prompt);
        if (isset($result['error'])) return $result;

        $now = date('Y-m-d H:i:s');
        $updates = ['date_modified' => $now];

        if (!empty($result['segmento']) && $this->columnExists('leads', 'segmento') && empty($lead['segmento'])) {
            $updates['segmento'] = $result['segmento'];
        }
        if (!empty($result['modulo_provavel']) && $this->columnExists('leads', 'modulo_interesse') && empty($lead['modulo_interesse'])) {
            $updates['modulo_interesse'] = $result['modulo_provavel'];
        }
        if (!empty($result['icp_score']) && $this->columnExists('leads', 'icp_score')) {
            $updates['icp_score'] = (int)$result['icp_score'];
        }

        $sets = [];
        $params = ['id' => $leadId];
        foreach ($updates as $col => $val) {
            if ($this->columnExists('leads', $col)) { $sets[] = "$col = :$col"; $params[$col] = $val; }
        }
        if ($sets) {
            $this->connection->executeStatement("UPDATE leads SET " . implode(', ', $sets) . " WHERE id = :id", $params);
        }

        return ['success' => true, 'classification' => $result, 'updated_fields' => array_keys($updates)];
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

    private function getDecayRiskCount(): int
    {
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM leads WHERE date_modified < DATE_SUB(NOW(), INTERVAL 30 DAY) AND ((email IS NOT NULL AND email != '') OR (firstname IS NOT NULL AND firstname != '')) AND (status_comercial IS NULL OR LOWER(status_comercial) NOT IN ('ganho','perdido')) AND interesse_score > 0"
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
        $sql = "SELECT id, firstname, lastname, email, company, cargo, segmento, origem_principal, date_added,
                    CASE WHEN (email IS NULL OR email = '') AND (firstname IS NULL OR firstname = '') THEN 'Sem nome e sem e-mail'
                         WHEN (email IS NULL OR email = '') THEN 'Sem e-mail'
                         WHEN (firstname IS NULL OR firstname = '') THEN 'Sem nome'
                    END as motivo_lead_incompleto
                FROM leads WHERE (email IS NULL OR email = '') OR (firstname IS NULL OR firstname = '')
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
        $firstName = trim($lead['firstname'] ?? '') ?: $name;

        $timeline = $this->getLeadTimeline($leadId);
        $recentText = implode("\n", array_map(function($t) {
            return date('d/m', strtotime($t['date'])) . " [{$t['type']}] {$t['text']}";
        }, array_slice($timeline, 0, 8)));

        $tipoInstructions = [
            'inicial_linkedin' => "Gere uma mensagem de PRIMEIRO CONTATO para LinkedIn.
REGRAS LINKEDIN:
- MAXIMO 280 caracteres (limite do LinkedIn para InMail/conexao)
- 1 paragrafo apenas, sem quebra de linha
- Comece com o primeiro nome da pessoa
- Cite algo especifico (cargo, empresa ou segmento) para mostrar que nao e generico
- Termine com pergunta aberta curta que gere curiosidade
- NAO use: 'Prezado', 'Espero que esteja bem', links, emojis excessivos
- Tom: leve, coloquial, peer-to-peer (como se fosse de um colega de industria)
- PODE usar 1 emoji maximo se fizer sentido",

            'diagnostico' => "Gere uma mensagem de FOLLOW-UP oferecendo diagnostico gratuito.
REGRAS:
- 3-4 linhas no maximo
- Referenciar a dor/modulo de interesse do lead
- Oferecer diagnostico de 15 min sem compromisso
- Posicionar como algo que ja fazemos com empresas do segmento
- Terminar com CTA claro (dia/horario sugerido)
- Tom: consultivo, expert, sem pressao",

            'reativacao' => "Gere uma mensagem de REATIVACAO para lead perdido.
REGRAS:
- 3-4 linhas no maximo
- Reconhecer que houve contato anterior (sem ser submisso)
- Apresentar novidade ou melhoria relevante
- Perguntar se a dor ainda existe ou se resolveram
- Tom: curioso, renovado, sem desculpas
- NAO mencionar que 'perdemos' o contato — apenas retomar naturalmente",

            'checklist' => "Gere uma mensagem oferecendo CONTEUDO de valor (checklist/material).
REGRAS:
- 2-3 linhas no maximo
- Oferecer material especifico para o segmento/cargo
- Gerar curiosidade sobre o conteudo sem entregar tudo
- Tom: generoso, educativo",
        ];

        if (!isset($tipoInstructions[$tipo])) {
            return ['error' => 'Tipo de playbook inválido. Use: inicial_linkedin, diagnostico, reativacao, checklist'];
        }

        $prompt = "Voce e o SDR senior da LetsWork (plataforma de gestao de pessoas para RH/DP).
Sua especialidade e outbound consultivo B2B.

LEAD:
- Nome: $name (primeiro nome: $firstName)
- Empresa: {$lead['company']} | Cargo: {$lead['cargo']}
- Segmento: {$lead['segmento']} | Modulo de interesse: {$lead['modulo_interesse']}
- Dor principal: {$lead['dor_principal']}
- Temperatura: {$lead['temperatura']} | Status: {$lead['status_comercial']}
- Motivo perda (se houver): " . ($lead['motivo_perda'] ?? 'N/A') . "

TIMELINE RECENTE:
$recentText

{$tipoInstructions[$tipo]}

Responda APENAS em JSON valido:
{
  \"message\": \"a mensagem pronta para copiar e enviar\",
  \"contexto\": \"por que essa abordagem faz sentido para esse lead (1 linha)\"
}";

        $result = $this->callGemini($prompt);

        if (isset($result['error'])) return $result;

        return [
            'success' => true,
            'tipo' => $tipo,
            'message' => $result['message'] ?? $result['insights'][0] ?? 'Erro ao gerar mensagem',
            'contexto' => $result['contexto'] ?? '',
            'lead_name' => $name,
        ];
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

    // --- Push to CRM ---

    public function pushLeadToCrm(int $leadId, string $responsavel): array
    {
        $lead = $this->getLeadDetail($leadId);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $now = date('Y-m-d H:i:s');
        if ($this->columnExists('leads', 'responsavel_comercial')) {
            $this->connection->executeStatement("UPDATE leads SET responsavel_comercial = :resp, date_modified = :now WHERE id = :id", ['resp' => $responsavel, 'now' => $now, 'id' => $leadId]);
        }

        $this->connection->insert('lead_notes', [
            'lead_id' => $leadId, 'is_published' => 1, 'date_added' => $now,
            'created_by' => 1, 'created_by_user' => 'admin',
            'text' => "Lead enviado para CRM — Responsável: $responsavel",
            'type' => 'general', 'date_time' => $now,
        ]);

        return ['success' => true, 'message' => "Lead enviado para $responsavel"];
    }

    // --- Forecast IA ---

    public function getForecast(array $dateRange): array
    {
        $pipeline = $this->connection->fetchAllAssociative(
            "SELECT id, firstname, lastname, company, valor_potencial, status_comercial, temperatura, date_added, date_modified
             FROM leads WHERE LOWER(status_comercial) IN ('proposta_enviada','proposta','negociacao','negociação','reuniao_agendada','reuniao_realizada')
             AND ((email IS NOT NULL AND email != '') OR (firstname IS NOT NULL AND firstname != ''))
             ORDER BY valor_potencial DESC LIMIT 20"
        );

        $totalValor = array_sum(array_column($pipeline, 'valor_potencial'));
        $avgCycle = 0;
        if (count($pipeline) > 0) {
            $days = array_map(function($l) { return max(1, (strtotime($l['date_modified']) - strtotime($l['date_added'])) / 86400); }, $pipeline);
            $avgCycle = round(array_sum($days) / count($days));
        }

        return ['pipeline' => $pipeline, 'total_valor' => $totalValor, 'avg_cycle_days' => $avgCycle, 'deals_count' => count($pipeline)];
    }

    // --- Deduplicação ---

    public function getDuplicateLeads(): array
    {
        $sql = "SELECT email, COUNT(*) as qty, GROUP_CONCAT(id SEPARATOR ',') as ids, GROUP_CONCAT(DISTINCT CONCAT(COALESCE(firstname,''),' ',COALESCE(lastname,'')) SEPARATOR ' | ') as names
                FROM leads WHERE email IS NOT NULL AND email != ''
                GROUP BY email HAVING qty > 1 ORDER BY qty DESC LIMIT 20";
        return $this->connection->fetchAllAssociative($sql);
    }

    public function mergeLeads(int $keepId, int $removeId): array
    {
        $keep = $this->connection->fetchAssociative("SELECT id FROM leads WHERE id = :id", ['id' => $keepId]);
        if (!$keep) return ['error' => 'Lead principal não encontrado'];

        $this->connection->executeStatement("UPDATE lead_notes SET lead_id = :keep WHERE lead_id = :remove", ['keep' => $keepId, 'remove' => $removeId]);
        $this->connection->executeStatement("UPDATE email_stats SET lead_id = :keep WHERE lead_id = :remove", ['keep' => $keepId, 'remove' => $removeId]);
        $this->connection->executeStatement("DELETE FROM leads WHERE id = :id", ['id' => $removeId]);

        return ['success' => true, 'message' => "Lead #$removeId mergeado em #$keepId"];
    }

    // --- Campanhas do Lead ---

    public function getLeadCampaigns(int $leadId): array
    {
        if (!$this->tableExists('campaign_leads') || !$this->tableExists('campaigns')) return [];
        return $this->connection->fetchAllAssociative(
            "SELECT c.name, cl.date_added, cl.manually_removed FROM campaign_leads cl JOIN campaigns c ON c.id = cl.campaign_id WHERE cl.lead_id = :id ORDER BY cl.date_added DESC",
            ['id' => $leadId]
        );
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

    // =============================================
    // 7. SMART ALERTS — Intelligent Notifications
    // =============================================

    public function getSmartAlerts(): array
    {
        $alerts = [];
        $now = date('Y-m-d H:i:s');

        // Alert: Hot leads that visited pricing/high-intent pages in last 24h
        if ($this->tableExists('page_hits')) {
            $hotVisitors = $this->connection->fetchAllAssociative(
                "SELECT DISTINCT l.id, l.firstname, l.lastname, l.company, l.temperatura, ph.url_title, MAX(ph.date_hit) as date_hit
                 FROM page_hits ph
                 JOIN leads l ON l.id = ph.lead_id
                 WHERE ph.date_hit >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                   AND LOWER(l.temperatura) IN ('quente','prioritario','prioritário')
                   AND (l.email IS NOT NULL AND l.email != '' AND l.email NOT LIKE '%@anonymous%')
                   AND (l.firstname IS NOT NULL AND CHAR_LENGTH(l.firstname) >= 2)
                   AND (LOWER(ph.url_title) LIKE '%preco%' OR LOWER(ph.url_title) LIKE '%pricing%' OR LOWER(ph.url_title) LIKE '%plano%' OR LOWER(ph.url_title) LIKE '%demo%' OR LOWER(ph.url_title) LIKE '%contato%')
                 GROUP BY l.id, l.firstname, l.lastname, l.company, l.temperatura, ph.url_title
                 ORDER BY date_hit DESC LIMIT 5"
            );
            foreach ($hotVisitors as $v) {
                $name = trim(($v['firstname'] ?? '') . ' ' . ($v['lastname'] ?? '')) ?: $v['company'];
                $alerts[] = [
                    'type' => 'hot_visit',
                    'severity' => 'critical',
                    'icon' => 'ri-fire-line',
                    'title' => $name . ' visitou página de conversão',
                    'description' => 'Página: ' . ($v['url_title'] ?: 'pricing/contato') . ' — ' . date('H:i', strtotime($v['date_hit'])),
                    'lead_id' => $v['id'],
                    'action' => 'Ligar AGORA — sinal forte de compra',
                ];
            }
        }

        // Alert: Emails opened 3+ times without reply (no interaction after)
        if ($this->tableExists('email_stats')) {
            $multiOpeners = $this->connection->fetchAllAssociative(
                "SELECT l.id, l.firstname, l.lastname, l.company, l.temperatura, COUNT(es.id) as opens, MAX(es.date_sent) as last_email
                 FROM email_stats es
                 JOIN leads l ON l.id = es.lead_id
                 WHERE es.is_read = 1
                   AND es.date_sent >= DATE_SUB(NOW(), INTERVAL 14 DAY)
                   AND (l.status_comercial IS NULL OR LOWER(l.status_comercial) NOT IN ('ganho','perdido'))
                 GROUP BY l.id, l.firstname, l.lastname, l.company, l.temperatura
                 HAVING opens >= 3
                 ORDER BY opens DESC LIMIT 10"
            );
            foreach ($multiOpeners as $m) {
                $name = trim(($m['firstname'] ?? '') . ' ' . ($m['lastname'] ?? '')) ?: $m['company'];
                $alerts[] = [
                    'type' => 'multi_open',
                    'severity' => 'high',
                    'icon' => 'ri-mail-open-line',
                    'title' => $name . ' abriu ' . $m['opens'] . 'x sem responder',
                    'description' => 'Interesse confirmado mas algo bloqueia. Mudar canal (ligar/WhatsApp).',
                    'lead_id' => $m['id'],
                    'action' => 'Abordar por outro canal — dor existe',
                ];
            }
        }

        // Alert: Score jumped 20+ points recently
        if ($this->tableExists('letswork_score_event_log')) {
            $scoreJumps = $this->connection->fetchAllAssociative(
                "SELECT l.id, l.firstname, l.lastname, l.company, l.temperatura, l.interesse_score,
                        SUM(sel.delta) as score_gain
                 FROM letswork_score_event_log sel
                 JOIN leads l ON l.id = sel.lead_id
                 WHERE sel.created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                   AND sel.delta > 0
                   AND (l.status_comercial IS NULL OR LOWER(l.status_comercial) NOT IN ('ganho','perdido'))
                 GROUP BY l.id, l.firstname, l.lastname, l.company, l.temperatura, l.interesse_score
                 HAVING score_gain >= 20
                 ORDER BY score_gain DESC LIMIT 10"
            );
            foreach ($scoreJumps as $s) {
                $name = trim(($s['firstname'] ?? '') . ' ' . ($s['lastname'] ?? '')) ?: $s['company'];
                $alerts[] = [
                    'type' => 'score_jump',
                    'severity' => 'medium',
                    'icon' => 'ri-arrow-up-circle-line',
                    'title' => $name . ' — score +' . $s['score_gain'] . ' pts (48h)',
                    'description' => 'Score atual: ' . $s['interesse_score'] . '. Lead esquentando rápido.',
                    'lead_id' => $s['id'],
                    'action' => 'Priorizar contato — momentum alto',
                ];
            }
        }

        // Alert: Leads going cold (no interaction 10+ days, was warm/hot)
        if ($this->columnExists('leads', 'data_ultima_interacao')) {
            $goingCold = $this->connection->fetchAllAssociative(
                "SELECT id, firstname, lastname, company, temperatura, data_ultima_interacao, interesse_score
                 FROM leads
                 WHERE LOWER(temperatura) IN ('morno','quente','prioritario','prioritário')
                   AND data_ultima_interacao < DATE_SUB(NOW(), INTERVAL 10 DAY)
                   AND (status_comercial IS NULL OR LOWER(status_comercial) NOT IN ('ganho','perdido'))
                   AND ((email IS NOT NULL AND email != '') OR (firstname IS NOT NULL AND firstname != ''))
                 ORDER BY interesse_score DESC LIMIT 5"
            );
            foreach ($goingCold as $c) {
                $name = trim(($c['firstname'] ?? '') . ' ' . ($c['lastname'] ?? '')) ?: $c['company'];
                $days = (int)((time() - strtotime($c['data_ultima_interacao'])) / 86400);
                $alerts[] = [
                    'type' => 'going_cold',
                    'severity' => 'medium',
                    'icon' => 'ri-temp-cold-line',
                    'title' => $name . ' esfriando (' . $days . ' dias)',
                    'description' => $c['temperatura'] . ' com score ' . $c['interesse_score'] . ' mas sem contato.',
                    'lead_id' => $c['id'],
                    'action' => 'Reengajar antes de perder',
                ];
            }
        }

        usort($alerts, function($a, $b) {
            $order = ['critical' => 0, 'high' => 1, 'medium' => 2];
            return ($order[$a['severity']] ?? 3) - ($order[$b['severity']] ?? 3);
        });

        return ['alerts' => $alerts, 'count' => count($alerts)];
    }

    // =============================================
    // 9. WEBHOOK BIDIRECIONAL — Mautic ↔ CRM
    // =============================================

    public function processCampaignWebhook(string $event, int $leadId, array $data): array
    {
        $lead = $this->connection->fetchAssociative("SELECT id, temperatura, interesse_score, status_comercial FROM leads WHERE id = :id", ['id' => $leadId]);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $now = date('Y-m-d H:i:s');
        $updates = ['date_modified' => $now];
        $scoreEvent = null;

        switch ($event) {
            case 'mautic.lead.campaign.add':
                $campaignName = $data['campaign_name'] ?? '';
                $scoreEvent = 'campaign_join';
                if (stripos($campaignName, 'nurtur') !== false || stripos($campaignName, 'nutri') !== false) {
                    if (empty($lead['status_comercial']) || $lead['status_comercial'] === 'prospeccao') {
                        $updates['status_comercial'] = 'engajado';
                    }
                }
                break;

            case 'mautic.email.open':
                $scoreEvent = 'email_aberto';
                break;

            case 'mautic.email.click':
                $scoreEvent = 'link_clicado';
                if (in_array($lead['status_comercial'], ['', 'prospeccao', null])) {
                    $updates['status_comercial'] = 'engajado';
                }
                break;

            case 'mautic.page.hit':
                $url = $data['url'] ?? '';
                if (preg_match('/preco|pricing|plano|demo|contato/i', $url)) {
                    $scoreEvent = 'visita_pricing';
                } else {
                    $scoreEvent = 'visita_pagina';
                }
                break;

            case 'mautic.form.submit':
                $scoreEvent = 'formulario_preenchido';
                if (in_array($lead['status_comercial'], ['', 'prospeccao', 'engajado', null])) {
                    $updates['status_comercial'] = 'engajado';
                }
                break;

            case 'mautic.lead.stage.change':
                $stageName = strtolower($data['stage_name'] ?? '');
                if (strpos($stageName, 'reuni') !== false) $updates['status_comercial'] = 'reuniao_agendada';
                elseif (strpos($stageName, 'propos') !== false) $updates['status_comercial'] = 'proposta_enviada';
                elseif (strpos($stageName, 'negoci') !== false) $updates['status_comercial'] = 'negociacao';
                elseif (strpos($stageName, 'ganh') !== false || strpos($stageName, 'won') !== false) $updates['status_comercial'] = 'ganho';
                elseif (strpos($stageName, 'perd') !== false || strpos($stageName, 'lost') !== false) $updates['status_comercial'] = 'perdido';
                break;

            default:
                $scoreEvent = $event;
        }

        if ($this->columnExists('leads', 'data_ultima_interacao')) {
            $updates['data_ultima_interacao'] = $now;
        }

        $sets = [];
        $params = ['id' => $leadId];
        foreach ($updates as $col => $val) {
            if ($this->columnExists('leads', $col)) {
                $sets[] = "$col = :$col";
                $params[$col] = $val;
            }
        }
        if ($sets) {
            $this->connection->executeStatement("UPDATE leads SET " . implode(', ', $sets) . " WHERE id = :id", $params);
        }

        $scoreResult = null;
        if ($scoreEvent && isset($this->getScoreEventsCatalog()[$scoreEvent])) {
            $scoreResult = $this->addScoreEvent($leadId, $scoreEvent, "Webhook: $event");
        }

        $this->connection->insert('lead_notes', [
            'lead_id' => $leadId, 'is_published' => 1, 'date_added' => $now,
            'created_by' => 1, 'created_by_user' => 'mautic_webhook',
            'text' => "[Mautic] $event" . (!empty($data['campaign_name']) ? " — {$data['campaign_name']}" : ''),
            'type' => 'general', 'date_time' => $now,
        ]);

        return ['success' => true, 'event' => $event, 'updates' => $updates, 'score' => $scoreResult];
    }

    public function triggerMauticCampaignAction(int $leadId, string $action): array
    {
        $lead = $this->getLeadDetail($leadId);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $campaignActions = [
            'nurture_cold' => ['campaign' => 'Nutrição Frio', 'segment' => 'letswork_frio'],
            'nurture_warm' => ['campaign' => 'Nutrição Morno', 'segment' => 'letswork_morno'],
            'reactivate' => ['campaign' => 'Reativação', 'segment' => 'letswork_reativacao'],
            'onboarding' => ['campaign' => 'Onboarding Cliente', 'segment' => 'letswork_ganho'],
        ];

        if (!isset($campaignActions[$action])) {
            return ['error' => 'Ação inválida. Use: ' . implode(', ', array_keys($campaignActions))];
        }

        $config = $campaignActions[$action];
        $now = date('Y-m-d H:i:s');

        $segment = $this->connection->fetchAssociative(
            "SELECT id FROM lead_lists WHERE alias = :alias",
            ['alias' => $config['segment']]
        );

        if ($segment) {
            $exists = $this->connection->fetchOne(
                "SELECT COUNT(*) FROM lead_lists_leads WHERE leadlist_id = :list AND lead_id = :lead",
                ['list' => $segment['id'], 'lead' => $leadId]
            );
            if (!$exists) {
                $this->connection->insert('lead_lists_leads', [
                    'leadlist_id' => $segment['id'],
                    'lead_id' => $leadId,
                    'date_added' => $now,
                    'manually_added' => 1,
                    'manually_removed' => 0,
                ]);
            }
        }

        $this->connection->insert('lead_notes', [
            'lead_id' => $leadId, 'is_published' => 1, 'date_added' => $now,
            'created_by' => 1, 'created_by_user' => 'admin',
            'text' => "🔄 Adicionado à campanha: {$config['campaign']}",
            'type' => 'general', 'date_time' => $now,
        ]);

        return ['success' => true, 'message' => "Lead adicionado à campanha {$config['campaign']}", 'campaign' => $config['campaign']];
    }

    // =============================================
    // 10. WHATSAPP API — Send via Twilio/360dialog
    // =============================================

    public function sendWhatsApp(int $leadId, string $message, string $templateName = ''): array
    {
        $lead = $this->connection->fetchAssociative("SELECT id, phone, firstname, lastname, company FROM leads WHERE id = :id", ['id' => $leadId]);
        if (!$lead) return ['error' => 'Lead não encontrado'];
        if (empty($lead['phone'])) return ['error' => 'Lead sem telefone cadastrado'];

        $phone = preg_replace('/[^0-9]/', '', $lead['phone']);
        if (strlen($phone) === 11 || strlen($phone) === 10) $phone = '55' . $phone;
        if (strlen($phone) < 12) return ['error' => 'Telefone inválido: ' . $lead['phone']];

        $config = $this->getWhatsAppConfig();
        if (isset($config['error'])) return $config;

        $now = date('Y-m-d H:i:s');
        $leadName = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? ''));
        $sent = false;

        if ($config['provider'] === 'twilio') {
            $sent = $this->sendWhatsAppTwilio($config, $phone, $message, $templateName);
        } elseif ($config['provider'] === '360dialog') {
            $sent = $this->sendWhatsApp360($config, $phone, $message, $templateName, $leadName);
        } elseif ($config['provider'] === 'evolution') {
            $sent = $this->sendWhatsAppEvolution($config, $phone, $message);
        }

        $this->connection->insert('lead_notes', [
            'lead_id' => $leadId, 'is_published' => 1, 'date_added' => $now,
            'created_by' => 1, 'created_by_user' => 'admin',
            'text' => "📱 WhatsApp " . ($sent ? 'enviado' : 'FALHOU') . ": " . mb_substr($message, 0, 80),
            'type' => 'general', 'date_time' => $now,
        ]);

        if ($this->columnExists('leads', 'data_ultima_interacao')) {
            $this->connection->executeStatement("UPDATE leads SET data_ultima_interacao = :now, date_modified = :now WHERE id = :id", ['now' => $now, 'id' => $leadId]);
        }

        if ($sent) {
            $this->addScoreEvent($leadId, 'whatsapp_enviado', 'WhatsApp: ' . mb_substr($message, 0, 50));
        }

        if (!$sent) return ['error' => 'Falha ao enviar WhatsApp. Verifique configuração da API.'];
        return ['success' => true, 'message' => 'WhatsApp enviado para +' . $phone];
    }

    private function getWhatsAppConfig(): array
    {
        $provider = getenv('WHATSAPP_PROVIDER') ?: ($_ENV['WHATSAPP_PROVIDER'] ?? '');
        $apiKey = getenv('WHATSAPP_API_KEY') ?: ($_ENV['WHATSAPP_API_KEY'] ?? '');
        $fromNumber = getenv('WHATSAPP_FROM') ?: ($_ENV['WHATSAPP_FROM'] ?? '');

        if (!$provider || !$apiKey) {
            $envFiles = [__DIR__ . '/../.env', '/var/www/html/plugins/LetsworkAnalyticsBundle/.env'];
            foreach ($envFiles as $f) {
                if (!file_exists($f)) continue;
                $lines = file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos($line, '#') === 0) continue;
                    if (strpos($line, 'WHATSAPP_PROVIDER=') === 0) $provider = trim(substr($line, 18));
                    if (strpos($line, 'WHATSAPP_API_KEY=') === 0) $apiKey = trim(substr($line, 17));
                    if (strpos($line, 'WHATSAPP_FROM=') === 0) $fromNumber = trim(substr($line, 14));
                    if (strpos($line, 'WHATSAPP_INSTANCE=') === 0) $fromNumber = trim(substr($line, 18));
                }
            }
        }

        if (!$provider) $provider = 'evolution';
        if (!$apiKey) return ['error' => 'WhatsApp API não configurada. Adicione WHATSAPP_PROVIDER e WHATSAPP_API_KEY no .env do plugin.'];

        return ['provider' => $provider, 'api_key' => $apiKey, 'from' => $fromNumber];
    }

    private function sendWhatsAppTwilio(array $config, string $phone, string $message, string $template): bool
    {
        $sid = $config['from'];
        $token = $config['api_key'];
        $from = 'whatsapp:+' . preg_replace('/[^0-9]/', '', $config['from']);
        $to = 'whatsapp:+' . $phone;

        $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
        $data = ['From' => $from, 'To' => $to, 'Body' => $message];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_USERPWD => "$sid:$token", CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    private function sendWhatsApp360(array $config, string $phone, string $message, string $template, string $contactName): bool
    {
        $url = 'https://waba.360dialog.io/v1/messages';
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => ['body' => $message],
        ];

        if ($template) {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => ['name' => $template, 'language' => ['code' => 'pt_BR']],
            ];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'D360-API-KEY: ' . $config['api_key']],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    private function sendWhatsAppEvolution(array $config, string $phone, string $message): bool
    {
        $baseUrl = getenv('WHATSAPP_BASE_URL') ?: ($_ENV['WHATSAPP_BASE_URL'] ?? 'http://localhost:8080');
        $instance = $config['from'] ?: 'default';
        $url = rtrim($baseUrl, '/') . "/message/sendText/$instance";

        $payload = ['number' => $phone, 'text' => $message];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'apikey: ' . $config['api_key']],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    // =============================================
    // GOOGLE CALENDAR — Create meeting event
    // =============================================

    public function createCalendarEvent(int $leadId, array $eventData): array
    {
        $lead = $this->getLeadDetail($leadId);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $name = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? '')) ?: $lead['company'];
        $title = $eventData['title'] ?? "Reunião — $name ({$lead['company']})";
        $date = $eventData['date'] ?? '';
        $time = $eventData['time'] ?? '10:00';
        $duration = (int)($eventData['duration'] ?? 30);
        $description = $eventData['description'] ?? '';

        if (!$date) return ['error' => 'Data obrigatória'];

        $startDt = date('Ymd\THis', strtotime("$date $time"));
        $endDt = date('Ymd\THis', strtotime("$date $time +{$duration} minutes"));
        $now = date('Y-m-d H:i:s');

        $autoDesc = "Lead: $name\nEmpresa: {$lead['company']}\nCargo: {$lead['cargo']}\n";
        $autoDesc .= "Segmento: {$lead['segmento']}\nMódulo: {$lead['modulo_interesse']}\n";
        $autoDesc .= "Dor: {$lead['dor_principal']}\nScore: {$lead['interesse_score']} ({$lead['temperatura']})\n";
        if ($lead['email']) $autoDesc .= "Email: {$lead['email']}\n";
        if ($lead['phone']) $autoDesc .= "Telefone: {$lead['phone']}\n";
        if ($description) $autoDesc .= "\nNotas: $description";

        $calendarUrl = $this->buildGoogleCalendarUrl($title, $startDt, $endDt, $autoDesc, $lead['email'] ?? '');

        if ($this->columnExists('leads', 'status_comercial')) {
            $currentStatus = strtolower($lead['status_comercial'] ?? '');
            if (!in_array($currentStatus, ['reuniao_agendada', 'reuniao_realizada', 'proposta_enviada', 'negociacao', 'ganho'])) {
                $this->connection->executeStatement(
                    "UPDATE leads SET status_comercial = 'reuniao_agendada', date_modified = :now WHERE id = :id",
                    ['now' => $now, 'id' => $leadId]
                );
            }
        }

        if ($this->columnExists('leads', 'data_proximo_followup')) {
            $this->connection->executeStatement(
                "UPDATE leads SET data_proximo_followup = :date, data_ultima_interacao = :now, date_modified = :now WHERE id = :id",
                ['date' => "$date $time", 'now' => $now, 'id' => $leadId]
            );
        }

        $this->connection->insert('lead_notes', [
            'lead_id' => $leadId, 'is_published' => 1, 'date_added' => $now,
            'created_by' => 1, 'created_by_user' => 'admin',
            'text' => "📅 Reunião agendada: " . date('d/m/Y H:i', strtotime("$date $time")) . " ($duration min)\n$title",
            'type' => 'general', 'date_time' => "$date $time",
        ]);

        $this->addScoreEvent($leadId, 'reuniao_agendada', "Reunião: $title");

        return [
            'success' => true,
            'message' => 'Reunião criada! Abrindo Google Calendar...',
            'calendar_url' => $calendarUrl,
            'event' => ['title' => $title, 'date' => $date, 'time' => $time, 'duration' => $duration],
        ];
    }

    private function buildGoogleCalendarUrl(string $title, string $startDt, string $endDt, string $description, string $guestEmail): string
    {
        $params = [
            'action' => 'TEMPLATE',
            'text' => $title,
            'dates' => $startDt . '/' . $endDt,
            'details' => $description,
            'sf' => 'true',
        ];
        if ($guestEmail) $params['add'] = $guestEmail;

        return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
    }

    // =============================================
    // ENRIQUECIMENTO CNPJ — ReceitaWS / BrasilAPI
    // =============================================

    public function enrichLeadByCnpj(int $leadId, string $cnpj): array
    {
        $lead = $this->connection->fetchAssociative("SELECT id, company FROM leads WHERE id = :id", ['id' => $leadId]);
        if (!$lead) return ['error' => 'Lead não encontrado'];

        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) !== 14) return ['error' => 'CNPJ inválido — deve ter 14 dígitos'];

        $data = $this->fetchCnpjData($cnpj);
        if (isset($data['error'])) return $data;

        $now = date('Y-m-d H:i:s');
        $updates = ['date_modified' => $now];

        if (!empty($data['razao_social']) && $this->columnExists('leads', 'company')) {
            $updates['company'] = $data['razao_social'];
        }
        if (!empty($data['segmento']) && $this->columnExists('leads', 'segmento')) {
            $updates['segmento'] = $data['segmento'];
        }

        $sets = [];
        $params = ['id' => $leadId];
        foreach ($updates as $col => $val) {
            if ($this->columnExists('leads', $col)) {
                $sets[] = "$col = :$col";
                $params[$col] = $val;
            }
        }
        if ($sets) {
            $this->connection->executeStatement("UPDATE leads SET " . implode(', ', $sets) . " WHERE id = :id", $params);
        }

        $noteText = "🏢 CNPJ Enriquecido: " . substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
        $noteText .= "\n• Razão Social: " . ($data['razao_social'] ?? '-');
        $noteText .= "\n• Porte: " . ($data['porte'] ?? '-');
        $noteText .= "\n• Atividade: " . ($data['atividade_principal'] ?? '-');
        $noteText .= "\n• UF: " . ($data['uf'] ?? '-') . " | Município: " . ($data['municipio'] ?? '-');
        if (!empty($data['capital_social'])) $noteText .= "\n• Capital Social: R$ " . number_format((float)$data['capital_social'], 0, ',', '.');
        if (!empty($data['socios'])) $noteText .= "\n• Sócios: " . implode(', ', array_slice($data['socios'], 0, 3));

        $this->connection->insert('lead_notes', [
            'lead_id' => $leadId, 'is_published' => 1, 'date_added' => $now,
            'created_by' => 1, 'created_by_user' => 'admin',
            'text' => $noteText, 'type' => 'general', 'date_time' => $now,
        ]);

        return ['success' => true, 'data' => $data, 'message' => 'Lead enriquecido com dados do CNPJ'];
    }

    private function fetchCnpjData(string $cnpj): array
    {
        $urls = [
            "https://brasilapi.com.br/api/cnpj/v1/$cnpj",
            "https://receitaws.com.br/v1/cnpj/$cnpj",
        ];

        foreach ($urls as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: LetsWork-CRM/1.0'],
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $raw = json_decode($response, true);
                if ($raw && !isset($raw['error']) && !isset($raw['message'])) {
                    return $this->normalizeCnpjResponse($raw);
                }
            }
        }

        return ['error' => 'Não foi possível consultar CNPJ. Tente novamente em alguns segundos.'];
    }

    private function normalizeCnpjResponse(array $raw): array
    {
        // BrasilAPI format
        if (isset($raw['razao_social'])) {
            $socios = [];
            if (!empty($raw['qsa'])) {
                foreach ($raw['qsa'] as $s) {
                    $socios[] = ($s['nome_socio'] ?? $s['nome'] ?? '') . ' (' . ($s['qual_socio'] ?? $s['qualificacao_socio'] ?? '') . ')';
                }
            }

            $atividadePrincipal = '';
            if (!empty($raw['cnae_fiscal_descricao'])) {
                $atividadePrincipal = $raw['cnae_fiscal_descricao'];
            } elseif (!empty($raw['atividade_principal'][0]['text'])) {
                $atividadePrincipal = $raw['atividade_principal'][0]['text'];
            }

            $segmento = $this->inferSegmento($atividadePrincipal, $raw['razao_social'] ?? '');

            return [
                'razao_social' => $raw['razao_social'] ?? '',
                'nome_fantasia' => $raw['nome_fantasia'] ?? '',
                'cnpj' => $raw['cnpj'] ?? '',
                'porte' => $raw['porte'] ?? ($raw['descricao_porte'] ?? ''),
                'atividade_principal' => $atividadePrincipal,
                'capital_social' => $raw['capital_social'] ?? 0,
                'uf' => $raw['uf'] ?? ($raw['descricao_tipo_de_logradouro'] ?? ''),
                'municipio' => $raw['municipio'] ?? ($raw['descricao_municipio'] ?? ''),
                'abertura' => $raw['data_inicio_atividade'] ?? ($raw['abertura'] ?? ''),
                'situacao' => $raw['descricao_situacao_cadastral'] ?? ($raw['situacao'] ?? ''),
                'socios' => $socios,
                'segmento' => $segmento,
                'telefone' => $raw['ddd_telefone_1'] ?? ($raw['telefone'] ?? ''),
                'email_empresa' => $raw['email'] ?? '',
            ];
        }

        // ReceitaWS format
        $socios = [];
        if (!empty($raw['qsa'])) {
            foreach ($raw['qsa'] as $s) {
                $socios[] = ($s['nome'] ?? '') . ' (' . ($s['qual'] ?? '') . ')';
            }
        }

        $atividadePrincipal = !empty($raw['atividade_principal'][0]['text']) ? $raw['atividade_principal'][0]['text'] : '';
        $segmento = $this->inferSegmento($atividadePrincipal, $raw['nome'] ?? '');

        return [
            'razao_social' => $raw['nome'] ?? '',
            'nome_fantasia' => $raw['fantasia'] ?? '',
            'cnpj' => $raw['cnpj'] ?? '',
            'porte' => $raw['porte'] ?? '',
            'atividade_principal' => $atividadePrincipal,
            'capital_social' => $raw['capital_social'] ?? 0,
            'uf' => $raw['uf'] ?? '',
            'municipio' => $raw['municipio'] ?? '',
            'abertura' => $raw['abertura'] ?? '',
            'situacao' => $raw['situacao'] ?? '',
            'socios' => $socios,
            'segmento' => $segmento,
            'telefone' => $raw['telefone'] ?? '',
            'email_empresa' => $raw['email'] ?? '',
        ];
    }

    private function inferSegmento(string $atividade, string $razaoSocial): string
    {
        $text = strtolower($atividade . ' ' . $razaoSocial);
        $map = [
            'Tecnologia' => ['software', 'tecnologia', 'informática', 'sistemas', 'digital', 'dados', 'ti ', 'saas'],
            'Saúde' => ['saúde', 'saude', 'médic', 'clinic', 'hospital', 'farma', 'odonto'],
            'Educação' => ['educa', 'ensino', 'escola', 'universid', 'curso', 'treinamento'],
            'Indústria' => ['indústri', 'industri', 'fabricação', 'manufat', 'metalurg', 'química'],
            'Varejo' => ['varejo', 'comércio', 'comercio', 'loja', 'magazine', 'atacad'],
            'Serviços' => ['consultori', 'consultoria', 'advocacia', 'escritório', 'serviço'],
            'Financeiro' => ['banco', 'financ', 'seguro', 'crédito', 'invest', 'contábil', 'contabil'],
            'Construção' => ['construção', 'construcao', 'imobili', 'engenharia', 'arquitet'],
            'Agro' => ['agro', 'agrícola', 'agricola', 'pecuária', 'fazenda', 'rural'],
            'Logística' => ['logística', 'logistica', 'transporte', 'frete', 'entrega'],
        ];

        foreach ($map as $segmento => $keywords) {
            foreach ($keywords as $kw) {
                if (strpos($text, $kw) !== false) return $segmento;
            }
        }
        return 'Outros';
    }
}
