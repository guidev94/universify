# PRD: CRM Centralizado no Mautic

## 1. Visao do Produto

Centralizar a gestao de leads no Mautic, consolidando contatos vindos do LinkedIn/Roboze, leads capturados ou importados no Mautic e eventos comerciais do CRM LetsWork.

O objetivo e criar uma base unica para nutricao, qualificacao, scoring, automacao e priorizacao comercial, mantendo o LetsWork CRM como sistema operacional de reunioes, propostas e fechamento.

## 2. Objetivos

- Centralizar leads em um unico local operacional de marketing: Mautic.
- Unificar contatos vindos do Roboze, Mautic e LetsWork CRM.
- Identificar leads frios, mornos, quentes e prioritarios.
- Criar automacoes para transformar leads frios em oportunidades comerciais.
- Permitir que marketing e comercial atuem juntos no follow-up.
- Usar IA para classificar intencao, sugerir mensagens e recomendar proximas acoes.
- Medir quais origens, segmentos e campanhas geram reunioes, propostas e clientes.

## 3. Contexto Atual

Hoje os leads estao distribuidos em tres pontos principais:

- Roboze / `../auto-pilot-dashboard`: contatos e conversas do LinkedIn.
- Mautic / `../mautic7/`: leads do site, importacoes Apollo, listas externas e automacoes.
- CRM LetsWork: controle de reunioes, oportunidades, propostas e fechamento.

Essa separacao dificulta:

- Ver o historico completo do lead.
- Saber se um contato do LinkedIn ja virou oportunidade.
- Evitar duplicidade.
- Priorizar leads com maior chance de compra.
- Reativar leads frios com campanhas adequadas.
- Medir conversao real por origem.

## 4. Arquitetura Proposta

```text
LinkedIn / Roboze
        |
      Mautic
        |
Nutricao, score, campanhas, IA
        |
Lead qualificado
        |
LetsWork CRM
        |
Reuniao, proposta, ganho/perdido
        |
Eventos retornam ao Mautic
```

### 4.1 Mautic

O Mautic sera o centro de marketing e qualificacao de leads.

Responsabilidades:

- Base central de leads.
- Automacao de marketing.
- Scoring de interesse.
- Segmentacao.
- Campanhas de nutricao.
- Tarefas para marketing.
- Gatilhos para comercial.
- IA para sugestao de mensagens e classificacao de intencao.

### 4.2 LetsWork CRM

O LetsWork CRM continua sendo a ferramenta principal do comercial.

Responsabilidades:

- Reunioes.
- Oportunidades.
- Propostas.
- Negociacao.
- Fechamento.
- Motivo de perda.
- Historico comercial.

### 4.3 Roboze / auto-pilot-dashboard

O Roboze sera responsavel por alimentar o Mautic com contatos e interacoes do LinkedIn.

Responsabilidades:

- Captura de contatos do LinkedIn.
- Status da conexao.
- Mensagens enviadas.
- Respostas recebidas.
- Exportacao/API para Mautic.
- Classificacao inicial de interacao.

### 4.4 Extensao Chrome Para Reunioes

Uma extensao Chrome deve apoiar o comercial durante reunioes no Google Meet, capturando audio do navegador e microfone, gerando transcricao, resumo e atualizando automaticamente o CRM LetsWork.

Responsabilidades:

- Identificar reunioes abertas no navegador.
- Vincular a reuniao a um lead/oportunidade do CRM LetsWork.
- Capturar audio do browser como fala do lead/participantes.
- Capturar audio do microfone como fala do vendedor.
- Transcrever a conversa quando houver permissao.
- Enviar transcricao para IA gerar resumo comercial.
- Identificar dores, objecoes, participantes, proximos passos e sinais de compra.
- Atualizar automaticamente a oportunidade no LetsWork CRM.
- Enviar eventos relevantes de volta ao Mautic.
- Remover o audio bruto depois da transcricao.
- Manter a transcricao no cadastro do lead/oportunidade.

## 5. Publicos E Responsaveis

### Marketing

- Gerencia campanhas e segmentos no Mautic.
- Nutre leads frios e mornos.
- Revisa leads de alto fit com baixo engajamento.
- Ajusta campanhas por segmento e dor.

### Comercial

- Atua sobre leads quentes e prioritarios.
- Registra reunioes, propostas e status no LetsWork CRM.
- Informa motivos de perda.
- Executa follow-ups de oportunidades.

### Administrador / Operacao

- Mantem campos, integracoes e regras de scoring.
- Monitora duplicidades e qualidade dos dados.
- Ajusta fluxos entre Roboze, Mautic e LetsWork CRM.

## 6. ICP

### Segmentos Prioritarios

- Servicos.
- Engenharia.
- Comercio.
- Alimentacao.
- Saude.

### Cargos Prioritarios

- RH
- DP
- Inovação

### Dores Provaveis Por Modulo

#### Ponto Eletronico

- Fechamento de ponto demorado e muito manual.
- Dificuldade para controlar batidas de entrada, saida e pausas.
- Falta de confianca nos registros de ponto.
- Erros no calculo de horas trabalhadas, atrasos e faltas.
- Dificuldade para tratar justificativas, abonos, atestados e licencas.
- Falta de espelho de ponto e relatorios confiaveis para auditoria.
- Risco de nao conformidade com regras legais e Portaria 671.
- Dificuldade para controlar ponto em app, desktop, relogio, WhatsApp ou equipes externas.
- Visualização de Banco de Horas pelo colaborador

#### Banco De Horas E Horas Extras

- Banco de horas controlado em planilhas ou conferido manualmente.
- Saldo positivo e negativo sem visibilidade para RH, gestor e funcionario.
- Dificuldade para separar horas extras de banco de horas.
- Erros em percentuais, faixas e regras por departamento ou turno.
- Atrasos e faltas nao refletindo corretamente no saldo.
- Retrabalho para conferir saldo antes do fechamento da folha.

#### Turnos, Escalas E Jornadas

- Escalas 5x2, 6x1, 12x36 ou rotativas dificeis de administrar.
- Horarios rigidos e flexiveis controlados manualmente.
- Mudancas de turno sem historico claro.
- Gestores sem visibilidade de quem deveria estar trabalhando.
- Dificuldade para controlar equipes por unidade, departamento ou obra.

#### Ferias

- Controle de ferias vencidas e a vencer em planilhas.
- Dificuldade para calcular saldo por periodo aquisitivo/concessivo.
- Risco de aprovar ferias acima do saldo disponivel.
- Processo lento de solicitacao e aprovacao pelo funcionario/gestor.
- Falta de planejamento visual para evitar ausencia simultanea de pessoas-chave.
- Pouca rastreabilidade sobre aprovacoes, reprovacoes e justificativas.

#### Documentos, Holerites E Assinaturas

- Distribuicao manual de holerites e documentos.
- Falta de comprovante de entrega, leitura ou assinatura.
- Documentos obrigatorios dispersos por e-mail, pastas ou papel.
- Dificuldade para coletar assinaturas de funcionarios.
- RH sem visibilidade de pendencias documentais por funcionario.

#### Recrutamento E Selecao / ATS

- Candidatos espalhados em e-mail, planilhas, LinkedIn e portais.
- Processo seletivo sem funil claro por etapa.
- Triagem de curriculos lenta e pouco padronizada.
- Dificuldade para comparar candidatos de forma objetiva.
- Agendamento de entrevistas manual e sujeito a falhas.
- Falta de historico do candidato ate a contratacao.

#### Avaliacoes De Desempenho

- Avaliacoes feitas em formularios soltos ou planilhas.
- Falta de rotina para autoavaliacao, gestor, equipe, cliente e 360 graus.
- Dificuldade para acompanhar prazos de resposta.
- Baixa visibilidade sobre desempenho, feedbacks e pontos de desenvolvimento.
- Falta de historico estruturado para apoiar decisoes de RH e liderancas.

#### OKRs E Metas

- Objetivos da empresa, departamentos e funcionarios desconectados.
- Falta de visibilidade de progresso e confianca nas metas.
- KRs atualizados manualmente e sem rotina de check-in.
- Dificuldade para cascatear objetivos entre empresa, area e pessoa.
- Gestores sem dados para acompanhar execucao estrategica.

#### Timesheet, Projetos E Rentabilidade

- Horas de projetos apontadas em planilhas ou depois do ocorrido.
- Falta de visibilidade de horas por cliente, projeto e equipe.
- Dificuldade para medir rentabilidade por projeto ou cliente.
- Faturamento sem conexao clara com horas trabalhadas.
- Gestores sem previsibilidade de capacidade, alocacao e produtividade.

#### Planejamento E Tarefas

- Tarefas dispersas em WhatsApp, e-mail e planilhas.
- Falta de padrao nos processos recorrentes.
- Dificuldade para acompanhar etapas, responsaveis, prazos e checklists.
- Pouca automacao para avisos, follow-ups e mudancas de status.
- Baixa visibilidade da execucao por equipe.

#### Comunicacao Interna E Notificacoes

- Avisos importantes perdidos entre e-mail, WhatsApp e comunicados manuais.
- Falta de rastreabilidade sobre mensagens enviadas e lidas.
- Dificuldade para notificar funcionarios sobre ponto, ferias, documentos e pendencias.

### Dores Prioritarias Para Campanhas Comerciais

- Fechamento de ponto e folha com muito retrabalho.
- Banco de horas e horas extras sem confianca.
- Controle de equipes externas, obras, lojas, turnos ou plantao.
- Ferias vencidas, saldo incorreto e aprovacao manual.
- Holerites, documentos e assinaturas sem rastreabilidade.
- Recrutamento com candidatos espalhados e triagem manual.
- Falta de indicadores de desempenho, metas e produtividade.
- Projetos sem controle de horas, rentabilidade e faturamento.

## 7. Campos Customizados No Mautic

### Campos Obrigatorios Para Entrada De Lead

- `email`
- `phone`

Leads sem e-mail ou telefone nao devem ser descartados. Eles devem entrar em uma fila de enriquecimento e revisao manual, com origem preservada e identificadores alternativos como LinkedIn, nome, empresa e cargo.

### Campos De Identificacao

- `firstname`
- `lastname`
- `company`
- `cargo`
- `segmento`
- `linkedin_url`
- `lead_incompleto`
- `motivo_lead_incompleto`

### Campos De Origem

- `origem_principal`
- `origens`
- `campanha_origem`
- `data_primeira_origem`
- `data_ultima_origem`

### Campos De LinkedIn

- `linkedin_status`
- `linkedin_ultima_interacao`
- `linkedin_respondeu`
- `linkedin_ultima_mensagem`
- `linkedin_ultima_resposta`
- `linkedin_intencao`

### Campos De Scoring E Qualificacao

- `icp_score`
- `interesse_score`
- `temperatura`
- `modulo_interesse`
- `dor_principal`
- `dor_identificada`
- `objecao_identificada`
- `proxima_acao`

### Campos Comerciais

- `status_comercial`
- `responsavel_marketing`
- `responsavel_comercial`
- `responsavel_recebimento_lead`
- `data_ultima_interacao`
- `data_proximo_followup`
- `motivo_perda`
- `valor_potencial`
- `ultima_reuniao_resumo`
- `ultima_reuniao_dores`
- `ultima_reuniao_objecoes`
- `ultima_reuniao_proximos_passos`
- `ultima_reuniao_transcricao_url`

## 8. Deduplicacao

Prioridade de identificacao:

1. E-mail.
2. Telefone.
3. URL do LinkedIn.
4. Nome + empresa.
5. Dominio da empresa.

Regras:

- Atualizar contato existente quando e-mail ou telefone ja existir.
- Manter todas as origens vinculadas ao contato.
- Nao sobrescrever campos preenchidos com valores vazios.
- Priorizar dados mais recentes quando houver conflito.
- Registrar a origem de cada atualizacao relevante.

## 9. Fluxos De Integracao

### 9.1 Roboze Para Mautic

Eventos enviados pelo Roboze:

- Contato encontrado.
- Conexao enviada.
- Conexao aceita.
- Mensagem enviada.
- Resposta recebida.
- Resposta positiva.
- Resposta negativa.
- Lead pediu informacao.
- Lead aceitou reuniao.

Regras:

- Criar lead completo quando houver e-mail e telefone.
- Criar lead incompleto quando faltar e-mail ou telefone, sem enviar automaticamente para campanhas comerciais.
- Marcar leads incompletos com `lead_incompleto = sim` e `motivo_lead_incompleto`.
- Direcionar leads incompletos para fila de enriquecimento, revisao manual ou nova tentativa de captura de contato.
- Usar `linkedin_url` como identificador complementar.
- Atualizar lead existente se e-mail ou telefone ja existir.
- Marcar origem como `linkedin_roboze`.
- Enviar conteudo da resposta para classificacao por IA quando houver resposta.

### 9.2 Mautic Para LetsWork CRM

Gatilhos para envio ao LetsWork CRM:

- Temperatura quente.
- Score acima de 60.
- Pediu informacao.
- Aceitou reuniao.
- Visitou pagina de preco.
- Respondeu positivamente no LinkedIn.
- Preencheu formulario de interesse.

Dados enviados:

- Lead.
- Empresa.
- Origem.
- Segmento.
- Score.
- Temperatura.
- Historico resumido.
- Dor identificada.
- Proxima acao sugerida.
- Responsavel sugerido.

Regras de distribuicao:

- O envio para o LetsWork CRM deve permitir selecionar quem vai receber o lead.
- A selecao pode ser manual no MVP.
- A regra automatica futura pode considerar segmento, origem, carteira, disponibilidade e responsavel comercial padrao.
- Toda transferencia deve registrar quem enviou, quem recebeu e data/hora.

### 9.3 LetsWork CRM Para Mautic

Eventos retornados:

- Reuniao agendada.
- Reuniao realizada.
- Reuniao transcrita.
- Dor identificada em reuniao.
- Objecao identificada em reuniao.
- Proximo passo definido em reuniao.
- Proposta enviada.
- Negociacao iniciada.
- Ganho.
- Perdido.
- Motivo de perda.
- Sem resposta.
- Follow-up futuro.

Uso no Mautic:

- Ajustar score.
- Atualizar temperatura.
- Remover de campanhas inadequadas.
- Iniciar campanha de reativacao.
- Medir conversao por origem.
- Registrar aprendizado de perda.

### 9.4 Extensao Chrome Para LetsWork CRM

Fluxo esperado:

1. Comercial entra em uma reuniao online.
2. Extensao identifica a URL da reuniao e permite selecionar o lead/oportunidade do LetsWork CRM.
3. Usuario inicia captura de audio com consentimento dos participantes.
4. Extensao captura audio do browser como fala do lead/participantes e audio do microfone como fala do vendedor.
5. Ao final da reuniao, a extensao envia o audio para o backend do LetsWork.
6. Backend transcreve, separa falas quando possivel e remove o audio bruto apos a transcricao.
7. Backend usa IA para gerar resumo estruturado.
8. CRM LetsWork salva a transcricao no cadastro do lead/oportunidade.
9. CRM LetsWork atualiza a oportunidade automaticamente quando a alteracao nao for critica.
10. Eventos comerciais relevantes retornam ao Mautic.

Dados enviados pela extensao:

- `lead_id` ou `oportunidade_id`.
- URL da reuniao.
- Plataforma da reuniao.
- Participantes identificados.
- Data e duracao.
- Audio do browser.
- Audio do microfone.
- Idioma.

Dados gerados pela IA:

- Resumo executivo da reuniao.
- Dores citadas pelo lead.
- Objecoes citadas.
- Modulos de interesse.
- Nivel de interesse percebido.
- Proximos passos combinados.
- Data sugerida de follow-up.
- Probabilidade de avanco.
- Sugestao de mensagem de follow-up.

Atualizacoes no LetsWork CRM:

- Registrar atividade de reuniao realizada.
- Salvar transcricao no cadastro do lead/oportunidade.
- Salvar resumo da reuniao.
- Atualizar `dor_principal` e `modulo_interesse` quando identificados.
- Atualizar etapa da oportunidade quando houver sinal claro de avanco.
- Criar tarefa de follow-up para o responsavel comercial.
- Sugerir proposta quando houver intencao de compra.

Atualizacoes no Mautic:

- Aumentar score por reuniao realizada.
- Aumentar score quando houver dor clara ou proximo passo.
- Atualizar temperatura para quente/prioritario quando apropriado.
- Remover lead de campanhas frias.
- Adicionar tags de modulo de interesse.

Retencao:

- Audio bruto deve ser usado apenas para transcricao.
- Audio bruto deve ser removido apos transcricao bem-sucedida.
- Em caso de falha na transcricao, o audio deve ficar disponivel apenas temporariamente para nova tentativa.
- Transcricao e resumo devem permanecer no cadastro do lead/oportunidade, com controle de acesso.

## 10. Scoring Proposto No Mautic

O Mautic deve manter um `Score De Interesse` para medir o engajamento do lead com base em eventos do LinkedIn/Roboze, campanhas do Mautic e andamento comercial no LetsWork CRM.

### 10.1 Score De Interesse - Eventos LinkedIn


| Evento                          | Pontos |
| ------------------------------- | ------ |
| Contato importado do Roboze     | +2     |
| Aceitou conexao                 | +5     |
| Respondeu mensagem              | +15    |
| Resposta com dor identificada   | +30    |
| Pediu mais informacoes          | +40    |
| Aceitou diagnostico/reuniao     | +60    |
| Nao respondeu apos 3 abordagens | -10    |
| Disse que nao tem interesse     | -40    |


### 10.2 Eventos Mautic


| Evento                    | Pontos |
| ------------------------- | ------ |
| Abriu e-mail              | +2     |
| Clicou em e-mail          | +5     |
| Visitou pagina do produto | +8     |
| Visitou pagina de precos  | +20    |
| Preencheu formulario      | +30    |
| Baixou material           | +15    |
| Respondeu campanha        | +25    |
| Sem interacao por 30 dias | -10    |
| Sem interacao por 60 dias | -20    |
| Descadastrou de e-mail    | -40    |


### 10.3 Score De Interesse - Eventos Comerciais


| Evento                                   | Pontos |
| ---------------------------------------- | ------ |
| Reuniao agendada                         | +50    |
| Reuniao realizada                        | +70    |
| Reuniao transcrita com resumo            | +10    |
| Dor clara identificada em reuniao        | +30    |
| Objecao tratavel identificada em reuniao | +15    |
| Proximo passo com data definida          | +40    |
| Proposta enviada                         | +90    |
| Negociacao ativa                         | +100   |
| Cliente ganho                            | +200   |
| Perdido                                  | -100   |


## 11. Temperatura Do Lead


| Score    | Temperatura | Acao                            |
| -------- | ----------- | ------------------------------- |
| 1 a 20   | Frio        | Nutricao leve                   |
| 21 a 60  | Morno       | Conteudo + abordagem consultiva |
| 61 a 100 | Quente      | Comercial deve agir             |
| 100+     | Prioritario | Follow-up imediato              |


Regras:

- A temperatura deve ser recalculada a cada evento relevante.
- Leads sem interacao devem esfriar com o tempo.
- Comercial pode ajustar temperatura manualmente quando necessario.
- Leads ganhos devem sair de campanhas de prospeccao.
- Leads perdidos devem entrar em reativacao futura, se fizer sentido.

## 12. Segmentos No Mautic

Segmentos recomendados:

- `Prospecção - Nunca houve interação`
- `ICP alto`
- `ICP medio`
- `LinkedIn - novos contatos`
- `LinkedIn - responderam`
- `LinkedIn - frios com bom ICP`
- `Leads frios`
- `Leads mornos`
- `Leads quentes`
- `Pronto para comercial`
- `Reativacao 15 dias`
- `Reativacao 30 dias`
- `Reativacao 60 dias`
- `Perdidos sem motivo informado`
- `Comercio`
- `Engenharia`
- `Alimentacao`
- `Saude`
- `Servicos`
- `RH / DP`
- `Interesse - Ponto eletronico`
- `Interesse - Banco de horas`
- `Interesse - Ferias`
- `Interesse - Documentos e holerites`
- `Interesse - ATS vagas`
- `Interesse - Avaliacoes e OKRs`
- `Interesse - Timesheet e projetos`

## 13. Campanhas De Nutricao

### 13.1 Lead Frio

Objetivo: gerar consciencia e descobrir dor.

Sequencia sugerida:

1. Conteudo educativo.
2. Checklist pratico.
3. Case por segmento.
4. Pergunta consultiva.
5. Oferta de diagnostico.
6. Tentativa leve de reuniao.
7. Reativacao futura se nao engajar.

Temas:

- Como reduzir retrabalho no fechamento do ponto.
- Checklist para evitar inconsistencias na jornada e no espelho de ponto.
- Como empresas com equipe externa, obras, lojas ou plantoes controlam jornada.
- Banco de horas e horas extras sem planilha.
- Como evitar ferias vencidas e erros de saldo.
- Como distribuir holerites e documentos com comprovante e assinatura.
- Como organizar recrutamento sem perder candidatos no e-mail ou planilha.
- Como acompanhar desempenho, metas e produtividade sem formularios soltos.
- Como medir horas, rentabilidade e faturamento por projeto.

### 13.2 Lead Morno

Objetivo: transformar interesse em conversa comercial.

Acoes:

- Enviar case por segmento.
- Enviar checklist avancado.
- Convidar para diagnostico.
- Criar alerta para marketing acompanhar.
- Fazer abordagem consultiva no LinkedIn ou e-mail.

### 13.3 Lead Quente

Objetivo: acelerar contato comercial.

Acoes:

- Criar tarefa comercial.
- Enviar para LetsWork CRM como oportunidade.
- Parar campanhas frias.
- Sugerir mensagem personalizada.
- Definir follow-up curto.

## 14. Playbooks De Abordagem

### 14.1 Mensagem Inicial LinkedIn

```text
Oi, {nome}. Vi que voce atua com {area} na {empresa}.
Tenho conversado com empresas de {segmento} que estao tentando reduzir retrabalho em {dor_principal}.
Isso tambem e um desafio por ai?
```

Exemplos de `dor_principal` por interesse:


| Modulo           | Dor para abordagem                                       |
| ---------------- | -------------------------------------------------------- |
| Ponto eletronico | fechamento de ponto e controle de jornada                |
| Banco de horas   | banco de horas, horas extras e atrasos                   |
| Escalas          | escalas, turnos e equipes externas                       |
| Ferias           | controle de ferias vencidas e saldos                     |
| Documentos       | holerites, documentos e assinaturas                      |
| ATS              | triagem de candidatos e organizacao do processo seletivo |
| Avaliacoes       | avaliacoes de desempenho e feedbacks                     |
| OKRs             | acompanhamento de metas e indicadores                    |
| Timesheet        | controle de horas, projetos e rentabilidade              |


### 14.2 Oferta De Checklist

```text
Posso te mandar um checklist rapido para avaliar se o processo atual de {modulo_interesse} esta gerando retrabalho, risco ou perda de visibilidade?
```

### 14.3 Convite Para Diagnostico

```text
Se fizer sentido, consigo olhar contigo em 15 minutos e apontar onde normalmente aparecem os maiores gargalos.
```

### 14.4 Reativacao

```text
Oi, {nome}. Retomando nosso contato: voces chegaram a resolver a parte de {dor_principal} ai na empresa?
Pergunto porque temos ajudado empresas a reduzir bastante o retrabalho do RH nessa rotina.
```

## 15. IA No Processo

### 15.1 Classificacao De Respostas

A IA deve classificar respostas em:

- Sem interesse.
- Curioso.
- Dor identificada.
- Objecao.
- Pediu informacao.
- Pronto para reuniao.
- Lead desqualificado.

### 15.2 Sugestao De Mensagens

A IA deve sugerir mensagens considerando:

- Cargo.
- Segmento.
- Historico.
- Ultima resposta.
- Temperatura.
- Modulo de interesse.
- Dor identificada.
- Objecao.

### 15.3 Resumo Do Lead

O resumo deve conter:

- Origem.
- Principais interacoes.
- Dores.
- Modulo de interesse.
- Objecoes.
- Score.
- Temperatura.
- Proxima acao recomendada.

### 15.4 Proxima Acao Recomendada

Possiveis recomendacoes:

- Nutrir.
- Enviar case.
- Pedir reuniao.
- Passar para comercial.
- Reativar depois.
- Desqualificar.

### 15.5 Analise De Reunioes

A IA deve analisar transcricoes de reunioes e retornar um resumo estruturado para o CRM LetsWork.

Saida esperada:

- Resumo curto para timeline do CRM.
- Principais dores do lead.
- Modulos LetsWork relacionados as dores.
- Objecoes comerciais.
- Concorrentes citados.
- Nivel de urgencia.
- Pessoas envolvidas na decisao.
- Proximos passos combinados.
- Tarefas sugeridas para comercial.
- Mensagem de follow-up sugerida.

Regra importante:

- A IA pode sugerir atualizacoes, mas no MVP alteracoes criticas como ganho, perdido, valor de proposta e etapa final devem exigir confirmacao humana.

## 16. MVP

Escopo minimo:

1. Campos customizados no Mautic.
2. Segmentos basicos.
3. Score inicial.
4. Integracao Roboze para Mautic.
5. Campanhas para leads frios.
6. Classificacao de temperatura.
7. Envio manual ou semi-automatico para LetsWork CRM.
8. Registro de eventos comerciais de volta no Mautic.
9. IA para classificar respostas do LinkedIn.
10. IA para sugerir proxima mensagem.
11. Prototipo de extensao Chrome para vincular reuniao ao CRM LetsWork.
12. Transcricao/resumo de reuniao com atualizacao semi-automatica da oportunidade.

## 17. Pos-MVP

- Deduplicacao avancada.
- Enriquecimento automatico de empresa.
- Dashboard completo de origem/conversao.
- Previsao de fechamento.
- Playbooks por segmento.
- Automacao bidirecional completa.
- Analise automatica de motivo de perda.
- Integracao profunda com paginas visitadas no site.
- Assistente comercial com historico completo.
- Captura automatica avancada de audio quando legendas/transcricao nativa nao estiverem disponiveis.
- Suporte a multiplas plataformas de reuniao alem do Google Meet.

## 18. Metricas De Sucesso

- Quantidade de leads centralizados.
- Duplicidades reduzidas.
- Leads frios convertidos em mornos.
- Leads mornos convertidos em reuniao.
- Reunioes geradas por LinkedIn.
- Reunioes geradas por Mautic.
- Taxa de conversao por segmento.
- Tempo medio ate reuniao.
- Tempo medio ate proposta.
- Taxa de ganho por origem.
- Motivos de perda mais comuns.
- Leads quentes sem follow-up.

## 19. Criterios De Aceite

### 19.1 Base Mautic

- Deve existir um conjunto padrao de campos para origem, LinkedIn, score, temperatura e status comercial.
- Deve ser possivel identificar a origem principal e origens secundarias de um lead.
- Deve ser possivel segmentar leads por temperatura, segmento, cargo e origem.

### 19.2 Integracao Roboze

- O Roboze deve conseguir criar ou atualizar leads no Mautic.
- Leads sem e-mail ou telefone devem ser identificados como incompletos e enviados para fila de enriquecimento/revisao.
- Eventos do LinkedIn devem atualizar score e historico do lead.
- Respostas do LinkedIn devem ser enviadas para classificacao por IA.

### 19.3 Scoring

- O score deve ser alterado automaticamente por eventos de marketing, LinkedIn e comercial.
- A temperatura deve refletir a faixa atual do score.
- Leads sem interacao devem perder pontos com o tempo.

### 19.4 Integracao LetsWork CRM

- Leads quentes devem poder ser enviados ao LetsWork CRM.
- O envio de leads deve permitir selecionar a pessoa que recebera o lead.
- Eventos comerciais devem retornar ao Mautic.
- Leads ganhos devem sair de campanhas de prospeccao.
- Leads perdidos devem registrar motivo quando informado.

### 19.5 IA

- A IA deve classificar respostas recebidas do LinkedIn.
- A IA deve sugerir uma proxima mensagem editavel.
- A IA deve sugerir proxima acao com base no historico do lead.

### 19.6 Extensao Chrome De Reunioes

- A extensao deve permitir vincular uma reuniao a um lead ou oportunidade do LetsWork CRM.
- A captura de transcricao deve exigir acao explicita do usuario.
- O usuario deve confirmar que a reuniao pode ser transcrita.
- A primeira versao deve suportar apenas Google Meet.
- A extensao deve capturar audio do browser e do microfone separadamente quando possivel.
- Ao finalizar a reuniao, o CRM deve receber transcricao, resumo, dores, objecoes e proximos passos.
- O audio bruto deve ser removido depois da transcricao.
- A transcricao deve ficar salva no cadastro do lead/oportunidade.
- O CRM deve criar uma atividade de reuniao realizada na timeline da oportunidade.
- O CRM deve criar tarefa de follow-up quando a IA identificar proximo passo.
- Alteracoes criticas sugeridas pela IA devem ser revisadas antes de aplicar automaticamente.

## 20. Backlog Priorizado

### P0 - Fundacao

- Criar todos os campos customizados no Mautic, pois nao ha campos customizados existentes.
- Criar segmentos basicos.
- Definir tags padronizadas.
- Definir regras de score inicial.
- Mapear eventos do Roboze.
- Mapear eventos do LetsWork CRM.
- Definir lista de usuarios que podem receber leads no LetsWork CRM.
- Definir fila de leads incompletos sem e-mail ou telefone.

### P1 - Integracoes MVP

- Criar endpoint/exportacao do Roboze para o Mautic.
- Criar rotina de criacao/atualizacao de lead no Mautic.
- Criar gatilho de envio de lead quente para LetsWork CRM.
- Criar retorno de eventos comerciais do LetsWork CRM para Mautic.
- Implementar deduplicacao por e-mail e telefone.
- Criar API no LetsWork CRM para receber transcricao e resumo de reuniao.
- Criar prototipo da extensao Chrome para Google Meet.
- Criar endpoints basicos no LetsWork CRM para criar/atualizar leads e oportunidades.
- Usar a API mais simples possivel para integrar com o Mautic no MVP.

### P2 - Nutricao E Operacao

- Criar campanhas para leads frios.
- Criar campanhas para leads mornos.
- Criar fluxo de reativacao 30/60 dias.
- Criar tarefas para marketing e comercial.
- Criar playbooks por segmento.

### P3 - IA

- Classificar respostas do LinkedIn.
- Sugerir mensagens.
- Resumir historico do lead.
- Sugerir proxima acao.
- Identificar objecoes e dores.
- Resumir transcricoes de reuniao.
- Extrair dores, objecoes, modulos de interesse e proximos passos de reunioes.
- Gerar mensagem de follow-up pos-reuniao.

### P4 - Otimizacao

- Criar dashboard de conversao por origem.
- Criar relatorio de segmentos que mais convertem.
- Criar analise de motivos de perda.
- Criar deduplicacao avancada.
- Criar enriquecimento automatico de empresas.
- Avaliar futuramente suporte a Zoom, Teams e outras plataformas.

