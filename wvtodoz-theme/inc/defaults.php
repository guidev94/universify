<?php
/**
 * Static fallbacks when ACF/CPT content is empty.
 *
 * @package WVTodoz
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

/**
 * @return array<string, mixed>
 */
function wv_defaults(): array {
	$uri = WVTODOZ_URI;
	return [
		'logo' => WVTODOZ_URI . '/assets/img/logo-wv-dark.svg',
		'logo_light' => WVTODOZ_URI . '/assets/img/logo-wv-light.svg',
		'logo_dark' => WVTODOZ_URI . '/assets/img/logo-wv-dark.svg',
		'whatsapp' => 'https://wa.me/551155311779',
		'hero' => [
			'badge' => 'Software house · IA',
			'eyebrow' => 'Agentes · Automações · Integrações',
			'title_line' => 'Feito pra',
			'title_accent' => 'operar.',
			'lead' => 'Somos a WV Todoz. Software house que cria agentes de IA, automações e integrações de sistemas para comércio, indústria, serviços, saúde e o que mais o seu setor exigir.',
			'cta_primary' => 'Falar do projeto',
			'cta_secondary' => 'Ver o que fazemos',
			'video' => $uri . '/assets/video/hero.mp4',
		],
		'clientes_title_html' => 'Marcas que <span class="text-todoz text-glow">confiam</span> na WV_Todoz.',
		'clientes' => [
			['name' => 'Coca-Cola', 'url' => 'https://www.wvtodoz.com.br/wp-content/uploads/elementor/thumbs/cocacola-o59hfslv8qxu40yqaxmr2w7jin97vldnnypm21lrk2.png'],
			['name' => 'PagSeguro', 'url' => 'https://www.wvtodoz.com.br/wp-content/uploads/elementor/thumbs/pagueseguro-o59hfuhjmf0er8vzzyg07vqgpezyazl4c80l0liz7m.png'],
			['name' => 'Syngenta', 'url' => 'https://www.wvtodoz.com.br/wp-content/uploads/elementor/thumbs/syngenta-o59hfuhjmf0er8vzzyg07vqgpezyazl4c80l0liz7m.png'],
			['name' => 'VMware', 'url' => 'https://www.wvtodoz.com.br/wp-content/uploads/elementor/thumbs/wmware-o59hfuhjmf0er8vzzyg07vqgpezyazl4c80l0liz7m.png'],
			['name' => 'Confirp', 'url' => $uri . '/assets/img/clientes/logo-confirp.png'],
		],
		'sobre' => [
			'title_line' => 'Seja Todoz',
			'title_accent' => 'com a gente',
			'lead' => 'Parceiros de quem precisa tirar peso da operação: agentes de IA, automações e sistemas que conversam entre si. Do primeiro alinhamento à solução no ar, a gente caminha junto.',
			'stat1_n' => '11+',
			'stat1_l' => 'Anos de Inovação',
			'stat2_n' => '50+',
			'stat2_l' => 'Projetos Entregues',
			'cta_primary' => 'Agendar consultoria',
			'cta_secondary' => 'Ver o que fazemos',
			'image' => $uri . '/assets/img/office.png',
		],
		'missao' => [
			'eyebrow' => 'Missão',
			'title' => 'Do gargalo à operação com IA.',
			'support' => 'A WV_Todoz é a software house que transforma rotina manual em agentes, automações e integrações que funcionam no dia a dia, em qualquer setor.',
			'product_a' => 'Facíleme',
			'product_b' => "Let's Work",
			'cta' => 'Conversar com a WV_Todoz',
		],
		'mobile_bridge' => [
			'line1' => 'O mundo',
			'line2_prefix' => 'é',
			'accent' => 'mobile,',
			'line3' => 'sua empresa',
			'line4' => 'deve ser.',
			'eyebrow' => 'Onde a operação encontra o cliente',
			'lead' => 'Cliente e time vivem no celular. Construímos apps e experiências mobile que carregam a marca e, quando faz sentido, conectam agentes e automações à rotina no bolso.',
			'cta' => 'Quero conversar',
		],
		'servicos' => [
			'eyebrow' => 'Três frentes de IA',
			'title_line' => 'O que',
			'title_accent' => 'fazemos',
			'intro' => 'Criamos agentes de IA, automações e integrações de sistemas que aliviam operação em qualquer setor. Apps e sites entram quando são o veículo certo para entregar isso no negócio.',
			'note' => 'Tecnologia pensada para o seu negócio, não para impressionar com jargão.',
			'quote_cta' => 'Solicitar orçamento',
		],
		'process' => [
			['title' => 'Entendimento', 'desc' => 'Mapeamos gargalos: retrabalho, decisão lenta e ferramentas que não se falam.'],
			['title' => 'Planejamento', 'desc' => 'Priorizamos agentes, automações ou integrações com base no impacto no negócio.'],
			['title' => 'Construção', 'desc' => 'Entregamos com ritmo e critério, sempre alinhados ao resultado que importa para você.'],
			['title' => 'Evolução', 'desc' => 'Depois do lançamento, acompanhamos, ajustamos e expandimos o que já está no ar.'],
		],
		'portfolio' => [
			'eyebrow' => 'Projetos Selecionados',
			'title_line' => 'Cases que',
			'title_accent' => 'marcam',
			'lead' => 'Deslize os cards no celular. No desktop, role a página e passe o mouse para colorir.',
			'cta' => 'Explorar Todos',
		],
		'produtos' => [
			'eyebrow' => 'Ecossistema',
			'title_line' => 'Produtos',
			'title_accent' => 'proprietários',
			'lead' => 'As mesmas plataformas do site WV_Todoz: social commerce, eventos, cursos e ponto com biometria facial.',
		],
		'aplicacoes' => [
			'eyebrow' => 'Retrabalho, atraso e ferramentas soltas',
			'title_line' => 'Pare de perder',
			'title_accent' => 'tempo',
			'lead' => 'Em comércio, indústria, serviços ou saúde, o padrão se repete: decisão lenta, operação manual e sistemas que não se falam. Colocamos agentes, automações e integrações onde a dor dói, para a rotina fluir e o time respirar.',
			'image' => $uri . '/assets/img/aplicacoes-ia.jpg',
			'chip_kicker' => 'Menos retrabalho',
			'chip_title' => 'Mais resultado',
			'b1' => 'Agentes que tiram peso de atendimento e tarefas internas',
			'b2' => 'Automações que cortam retrabalho e fila de planilha',
			'b3' => 'Integrações que unem ferramentas e aceleram a decisão',
			'cta' => 'Quero automatizar a operação',
		],
		'blog' => [
			'eyebrow' => 'Insights',
			'title_line' => 'Conteúdo &',
			'title_accent' => 'inovação',
			'cta' => 'Ver Blog Completo',
		],
		/**
		 * Featured insights from production (wvtodoz.com.br).
		 * Images: local theme assets (sideload) with remote URLs as fallback.
		 */
		'insights' => [
			[
				'title'   => 'O gargalo silencioso: por que a gestão de jornada ainda drena tanto tempo das empresas?',
				'slug'    => 'o-gargalo-silencioso-por-que-a-gestao-de-jornada-ainda-drena-tanto-tempo-das-empresas',
				'excerpt' => 'Quem trabalha com tecnologia ou lidera operações sabe que quase tudo hoje tem um fluxo automatizado — exceto, em muitas empresas, a consolidação das horas trabalhadas.',
				'tag'     => 'Tecnologia',
				'img'     => 'img/blog/insight-jornada.jpg',
				'img_url' => 'https://www.wvtodoz.com.br/wp-content/uploads/2026/07/controle-de-ponto.jpg',
				'date'    => '2026-07-14 17:11:08',
				'content' => <<<'HTML'
<p>Quem trabalha com tecnologia ou lidera operações sabe que quase tudo hoje tem um fluxo automatizado. Temos pipelines de deploy rodando sozinhos, CRMs que disparam alertas automáticos e dashboards que mostram a saúde financeira da empresa em tempo real. Mas, curiosamente, quando chega o fim do mês, um processo antigo e manual costuma travar o dia a dia de muita gente: a consolidação das horas trabalhadas.</p>
<p>Muitas empresas — mesmo as que se consideram modernas — ainda gastam horas preciosas cruzando dados de planilhas de Excel, mensagens de WhatsApp e relatórios extraídos de relógios físicos que vivem dando problema.</p>
<p>Abaixo, explico por que tratar o registro de jornada como um mero detalhe burocrático é um erro estratégico, e como a tecnologia certa resolve esse problema sem criar novas fricções para o time.</p>
<h2>O custo invisível das “ajustadas” de ponto</h2>
<p>Todo fechamento de mês costuma ter o mesmo roteiro: o departamento pessoal descobre marcações duplicadas, dias esquecidos e inconsistências em geral. Começa então uma verdadeira caçada interna para descobrir se fulano realmente trabalhou naquela terça-feira ou se apenas esqueceu de bater o ponto.</p>
<p>Esse vai e vem gera um retrabalho absurdo para o RH e tira o foco de tarefas muito mais importantes, como recrutamento e desenvolvimento de pessoas. Além disso, sistemas antigos dificultam o cálculo de horas extras ou banco de horas em tempo real, fazendo com que a liderança tome decisões sobre escalas e custos no escuro.</p>
<h2>Mobilidade é realidade, não tendência</h2>
<p>Não faz mais sentido exigir que um colaborador de campo, que trabalha prestando serviços ou em modelo híbrido, dependa de um relógio de parede fixado na sede da empresa.</p>
<p>A tecnologia atual permite que o registro de jornada acompanhe o ritmo de trabalho moderno. Seja por aplicativo no celular, pelo computador ou mesmo de forma integrada com outras ferramentas cotidianas, a marcação precisa ser simples e acessível de onde o profissional estiver. Quando você dá flexibilidade ao funcionário, a taxa de esquecimento cai drasticamente e as informações chegam prontas e organizadas para quem precisa aprovar.</p>
<h2>Segurança sem burocracia: validação por foto e localização</h2>
<p>Um dos grandes desafios de gerenciar equipes que trabalham externamente ou em turnos rotativos é garantir que as informações de presença sejam reais e auditáveis.</p>
<p>Em vez de processos analógicos lentos ou planilhas que qualquer um pode editar, as plataformas modernas utilizam o reconhecimento facial direto no smartphone ou tablet e registram a geolocalização exata do momento do clique. Isso traz duas grandes vantagens:</p>
<ul>
<li><strong>Para a empresa:</strong> a certeza de que a pessoa certa estava no local combinado, reduzindo passivos trabalhistas e fraudes.</li>
<li><strong>Para o colaborador:</strong> transparência total e a garantia de que seu esforço e suas horas extras estão devidamente computados.</li>
</ul>
<h2>Dados que viram decisão</h2>
<p>O registro de ponto não deveria servir apenas para gerar a folha de pagamento no final do mês. Quando os dados de entrada e saída estão integrados a um painel inteligente, os gestores conseguem acompanhar a produtividade e a saúde das equipes de perto.</p>
<p>Se um time está fazendo hora extra de forma recorrente, por exemplo, o sistema acende um alerta. Isso permite que a liderança intervenha antes que ocorra um burnout ou um estouro no orçamento do projeto. Da mesma forma, o próprio colaborador ganha autonomia ao conseguir visualizar seu espelho de ponto e saldo de banco de horas direto na tela, eliminando aquela clássica dúvida de “quantas horas eu tenho acumuladas?”.</p>
<h2>Simplificando o processo</h2>
<p>No fim do dia, a automação desse processo serve para que as pessoas passem menos tempo preenchendo relatórios e mais tempo produzindo. Se a sua empresa ainda perde dias inteiros corrigindo inconsistências em planilhas ou tratando pontos esquecidos, talvez seja o momento de dar um passo à frente.</p>
<p>Se você quer entender como levar essa inteligência para a sua operação, vale a pena conhecer o sistema de controle de ponto da <a href="https://www.letswork.com.br" target="_blank" rel="noopener noreferrer">Let’s Work</a>, uma ferramenta desenvolvida para centralizar, automatizar e simplificar a gestão de jornadas sem complicação técnica.</p>
HTML,
			],
			[
				'title'   => 'Na linguagem de programação, o que o futuro nos reserva?',
				'slug'    => 'linguagens-de-programacao',
				'excerpt' => 'Tendências e linguagens que se aproximam do futuro do desenvolvimento — do DIY ao TypeScript, passando por Kotlin, Erlang e Go.',
				'tag'     => 'Tecnologia',
				'img'     => 'img/blog/insight-programacao.jpg',
				'img_url' => 'https://www.wvtodoz.com.br/wp-content/uploads/2018/04/programacao.jpg',
				'date'    => '2018-11-25 18:29:14',
				'content' => <<<'HTML'
<p>Chamamos de futurologia a previsão, ou tentativa dela, que aborda e calcula eventos que podem ditar nossas vidas em um futuro próximo. Nela se busca entender o que provavelmente continuará e o que poderá plausivelmente mudar. Com a programação, a experiência de muitos desenvolvedores também aponta evoluções claras neste ramo. Veja as tendências e as linguagens que mais se aproximam desse futuro — ou que já são uma ínfima realidade.</p>
<h2>DIY da programação</h2>
<p>Uma das tendências entre os desenvolvedores é acreditar que, no futuro, muitos programadores deverão se focar no desenvolvimento de programas que ajudem o usuário final a criar seus próprios sites ou aplicativos. Um dos exemplos disso são as planilhas eletrônicas, que antes precisavam de um programador para serem realizadas e hoje uma dezena de softwares consegue entregar em simples cliques.</p>
<p>Nesta visão, o programador passa a solucionar problemas ainda mais complexos — por exemplo, criar uma boa experiência final para o usuário de um programa que desenvolve aplicativos móveis, sem se limitar à criação de um único aplicativo para cada cliente. Duas tribos podem conviver: os tradicionais e os futuristas. Em alguns casos, clientes ainda vão preferir o customizável sob medida.</p>
<h2>A linguagem universal</h2>
<p>É o sonho de todo desenvolvedor: uma linguagem que se adapte a qualquer necessidade e simplifique a vida de quem escreve e de quem usa. Como qualquer outro dialeto, as linguagens de programação são compostas por regras sintáticas e semânticas. É praticamente impossível eleger a “melhor” linguagem entre as disponíveis no mercado.</p>
<p>Essa multilinguagem pode ser benéfica — escolhas alinhadas ao projeto — mas, com o tempo, algumas stacks podem se tornar obsoletas. Tendências em direção a uma linguagem mais universal trariam benefícios amplos, desde que o ecossistema e o time estejam preparados.</p>
<h2>Estudar programação como necessidade</h2>
<p>Quem é desenvolvedor sempre defende a tese de que todos deveríamos estudar programação: além de desenvolver o raciocínio lógico, o aprendizado de uma linguagem é uma forma de se preparar para o mercado. Ainda que você não goste de números ou que sua área não esteja ligada às exatas, compreender a lógica de programação permite lidar melhor com novas tecnologias — em especial com as inúmeras facetas da IA.</p>
<p>O pensamento computacional possibilita criatividade e inteligência aplicadas. Assim, até mesmo crianças deveriam aprender a programar.</p>
<h2>O que o futuro nos espera nas linguagens de programação</h2>
<p>Dentre as novas linguagens, separamos quatro bem interessantes:</p>
<h3>Kotlin — porque o Java tem salvação</h3>
<p>O Kotlin, feito pela JetBrains, deve compilar rapidamente, coexistir com o Java e corrigir alguns dos piores problemas que ocupam o tempo dos desenvolvedores. Foi projetado para trabalhar com código Java existente, tornando-se uma boa opção para equipes que desejam melhorar gradualmente uma base de código.</p>
<h3>Erlang — programação funcional em tempo real</h3>
<p>Fãs de Erlang gostam de se gabar de executar milhões de “processos” ao mesmo tempo em um servidor. A maior parte do código é forçada a operar em si, onde não pode corromper o resto do sistema através de efeitos colaterais. Funções internamente ficam executando em pequenos processos e conversam somente entre si através de mensagens. Pode exigir um pouco mais de raciocínio, mas os erros são menos propensos a se propagar.</p>
<h3>Go — assim de simples</h3>
<p>Linguagem open source, multiplataforma e escalável, com gestão de memória própria e threads transparentes ao programador. Idealizada pelo Google, endereça desempenho, escalabilidade e facilidade de manutenção — uma boa pedida para quem precisa construir lógica de negócios no servidor.</p>
<h3>TypeScript — você vai amar</h3>
<p>TypeScript é um superconjunto de JavaScript que se relaciona bem com códigos legados. A grande razão pela qual mais desenvolvedores se interessam por ele é o ecossistema moderno (incluindo Angular e além), que facilita aproveitar a qualidade do código tipado e mesclar com JavaScript existente.</p>
<p>Quanto mais o tempo passa, maiores as incidências de que a programação vai além de um código cheio de variáveis: é uma necessidade onde a tecnologia depende dela para a evolução dos produtos e dos negócios.</p>
<p>Quer saber mais sobre como seu projeto pode se adequar a determinadas linguagens? <a href="/contato/">Entre em contato conosco</a> — podemos orientar e trazer o que há de melhor em tecnologia e custo-benefício.</p>
HTML,
			],
			[
				'title'   => 'Mas afinal o que é o AI-first?',
				'slug'    => 'ai-first',
				'excerpt' => 'Do mobile-first ao AI-first: como a inteligência artificial passa a orientar produtos, equipes e experiências digitais.',
				'tag'     => 'Inovação',
				'img'     => 'img/blog/insight-ai-first.jpg',
				'img_url' => 'https://www.wvtodoz.com.br/wp-content/uploads/2017/10/Mas-afinal-o-que-%C3%A9-o-AI-first.jpg',
				'date'    => '2017-02-17 15:24:05',
				'content' => <<<'HTML'
<p>Desde os primórdios do poderoso império Google, a sua missão sempre foi “fazer com que a informação seja universalmente acessível e útil” para resolver problemas complexos com a utilização das novas tecnologias.</p>
<p>Com as novas implementações e o crescente paradigma mobile, houve a necessidade de reinventar produtos com uma nova modelagem e interação. Saindo do princípio do mundo “mobile-first” com uma interação mais inteligente, surgiu o “AI-first”. Mas afinal, o que é o AI-first?</p>
<p>O AI-first nada mais é do que a utilização das novas tecnologias para produtos que possam ajudar de maneira mais efetiva, inteligente e fácil as pessoas. E pensando neste pressuposto, o Google e outras organizações estão dando passos firmes nesta nova era tecnológica.</p>
<blockquote><p>“Sem dúvida, AI-First é uma tendência que já está aqui. E isto é possível neste momento porque coexistem grandes volumes de dados com modelos adequados e com a potencialidade necessária para lidar com eles.” — Isaac Hernández, Country Manager de Google Cloud da Espanha.</p></blockquote>
<p>Assim, a partir de um algoritmo poderoso que passou a categorizar, organizar e disponibilizar um universo de endereços eletrônicos através de buscas simples, hoje a busca vai além: reconhecimento inteligente e segmentação de imagens, fotos e vídeos — de grande utilidade para pessoas, empresas e governos.</p>
<h2>Quais são as características do AI-First?</h2>
<ul>
<li>Estruturas de alta complexidade, constituídas por múltiplas camadas conectadas — uma mistura de centralização (centros de investigação) e descentralização (para que todas as equipes explorem as possibilidades da IA).</li>
<li>A AI-first é incorporada por vários meios: plataformas de software internas, projetos open-source e serviços finais fornecidos.</li>
<li>Nas organizações AI-first, os peritos mais técnicos não estão só responsáveis por projetos de investigação: também lideram formação interna.</li>
<li>O principal objetivo é maximizar o número de experimentações com inteligência artificial e a velocidade para escalar as que são bem-sucedidas.</li>
</ul>
<h2>O que temos até o momento?</h2>
<p>Machine Learning e Deep Learning já são realidade em melhorias de busca, filtragem de spam, organização de arquivos, traduções em tempo real e eficiência em datacenters. No ecossistema Google, exemplos incluem Assistente, Google Lens (visão computacional) e iniciativas como Google for Jobs.</p>
<p>Outros produtos relacionados ao AI-first são alto-falantes inteligentes (Amazon Echo, Apple HomePod), que identificam e classificam a voz com linguagem natural durante a conversa.</p>
<h2>Como fica o desenvolvimento do AI-first?</h2>
<p>Uma das formas de dar mais acessibilidade à IA é simplificar a criação de modelos de aprendizado — redes neurais. Abordagens como AutoML mostram que é possível desenhar redes neurais que ajudam a desenhar outras redes, reduzindo tempo e expertise necessários.</p>
<p>Frameworks como TensorFlow e APIs especializadas (Vision, Speech, Translation, Natural Language, Video Intelligence) facilitam o uso da tecnologia em máquina local, cloud ou on-premise.</p>
<h2>Desafios: o que nos resta fazer?</h2>
<p>A Inteligência Artificial engloba áreas como informática, sociologia, psicologia, filosofia, neurociência, matemática e biologia. Ainda há barreiras — e a falta de recursos e mão de obra qualificada continua sendo um ponto forte contra a disseminação em massa.</p>
<p>De que adianta ter um app que indica restaurantes se ele não sabe a sua preferência e seus hábitos? Com o uso da IA, a experiência se torna contextual. Aqui na WV_Todoz trabalhamos com esse princípio. <a href="/contato/">Venha para a WV_Todoz</a> e descubra um mundo de possibilidades.</p>
HTML,
			],
		],
		'contato' => [
			'title_before' => 'Pronto para o',
			'title_highlight' => 'Próximo Nível?',
			'phone' => '(11) 5531-1779',
			'tel' => '1155311779',
			'cta' => 'FALE CONOSCO',
		],
		'footer' => [
			'tagline' => 'Criamos agentes, automações e integrações que tiram peso da operação e geram resultado no negócio.',
			'address' => "Av. Morumbi, 8435 cj.104\nBrooklin, São Paulo - SP\nBrasil",
			'maps' => 'https://www.google.com/maps/search/?api=1&query=Av.+Morumbi,+8435+Brooklin+S%C3%A3o+Paulo+SP',
			'copyright' => '© 2026 WV_TODOZ — SOFTWARE HOUSE',
			'facebook' => 'https://www.facebook.com/wvtodoz',
			'instagram' => 'https://www.instagram.com/wvtodoz',
			'linkedin' => 'https://www.linkedin.com/company/wvtodoz',
			'youtube' => 'https://www.youtube.com/@wvtodoz',
			'privacy' => '',
			'terms' => '',
		],
		'modal' => [
			'title' => 'VAMOS',
			'title_accent' => 'CONVERSAR.',
		],
		'cases' => [
			[
				'title' => "Let's Work Facial",
				'category' => 'AI / Mobile',
				'img' => 'https://www.wvtodoz.com.br/wp-content/uploads/2019/04/Lets-work-1024x576.jpg',
				'desc' => 'Sistema de ponto eletrônico com reconhecimento facial e geolocalização. Processamento de imagem nativo em tempo real para controle de jornada seguro, preciso e auditável.',
				'meta_strong' => 'Biometria facial',
				'meta_p' => 'Registro de ponto com reconhecimento facial e geolocalização em tempo real.',
			],
			[
				'title' => 'Pullman App',
				'category' => 'Marketing Digital',
				'img' => 'https://www.wvtodoz.com.br/wp-content/uploads/2019/04/Pullman-1-1024x576.jpg',
				'desc' => 'Aplicativo interativo para a Pullman com foco em engajamento de marca e utilidade para o consumidor. Interface premium, jornada fluida e arquitetura mobile preparada para escala.',
				'meta_strong' => 'Brand experience',
				'meta_p' => 'App mobile premium que aproxima a marca do consumidor com jornada fluida.',
			],
			[
				'title' => 'Mega Senha Quiz',
				'category' => 'Social Gaming',
				'img' => 'https://www.wvtodoz.com.br/wp-content/uploads/2019/04/mega-senha-2-1024x576.jpg',
				'desc' => 'Do sucesso da TV para uma experiência digital interativa. Quiz do Mega Senha no Facebook com milhares de jogadores simultâneos, gamificação e compartilhamento social em escala.',
				'meta_strong' => 'Gamificação social',
				'meta_p' => 'Quiz interativo com milhares de jogadores simultâneos e alto engajamento.',
			],
			[
				'title' => 'Facíleme Store',
				'category' => 'Social Commerce',
				'img' => 'https://www.wvtodoz.com.br/wp-content/uploads/2019/04/facileme-social-commerce-1024x576.jpg',
				'desc' => 'Ecossistema completo de lojas virtuais para venda direta nas redes sociais. Catálogo, checkout simplificado e conversão onde o público já está conectado.',
				'meta_strong' => 'Venda nas redes',
				'meta_p' => 'Loja virtual integrada ao social, do catálogo até o checkout simplificado.',
			],
			[
				'title' => 'Syngenta Wi-Fi',
				'category' => 'Enterprise',
				'img' => 'https://www.wvtodoz.com.br/wp-content/uploads/2019/04/Sy-2-1024x576.jpg',
				'desc' => 'Gestão inteligente de Wi-Fi para hotéis e eventos de grande porte. Controle de acesso, monitoramento de performance e operação estável sob alta demanda.',
				'meta_strong' => 'Conectividade',
				'meta_p' => 'Gestão de Wi-Fi corporativo com controle de acesso e monitoramento.',
			],
		],
		'servicos_cards' => [
			[
				'variant' => 'featured',
				'icon' => 'ph-robot',
				'title' => 'Criação de <span class="text-todoz">agentes.</span>',
				'desc' => 'Agentes de IA que atendem, triam, consultam sistemas e executam tarefas com contexto do seu negócio. Menos fila, menos espera, mais resposta no ritmo certo.',
				'bullets' => "Atendimento e suporte com contexto da operação\nAssistentes internos para o time decidir mais rápido\nFluxos que consultam dados e acionam próximos passos",
				'cta' => 'Quero um agente',
			],
			[
				'variant' => 'tall',
				'icon' => 'ph-flow-arrow',
				'title' => 'Automações <span class="text-todoz">que escalam.</span>',
				'desc' => 'Processos repetitivos deixam de depender de planilha e memória. Automatizamos rotinas de vendas, operação, financeiro e atendimento para o time focar no que importa.',
				'bullets' => "Menos retrabalho em tarefas que se repetem todo dia\nAlertas e gatilhos no momento certo\nRegras claras, auditáveis e fáceis de evoluir\nEscala sem contratar gente só para apagar incêndio",
				'cta' => 'Falar de automação',
				'cta_class' => 'servicos-card-btn--accent',
			],
			[
				'variant' => 'mobile',
				'icon' => 'ph-plugs-connected',
				'title' => 'Integração de <span class="text-todoz">sistemas.</span>',
				'desc' => 'ERP, CRM, e-commerce, planilhas e ferramentas soltas passam a conversar. Dados no lugar certo, sem digitação duplicada e sem versão oficial perdida no WhatsApp.',
				'bullets' => "APIs e conectores entre as ferramentas que você já usa\nUma visão única da operação, sem silos\nMenos erro humano entre ferramentas desconectadas",
				'cta' => 'Integrar meu stack',
			],
			[
				'variant' => 'ia',
				'icon' => 'ph-app-window',
				'title' => 'Apps &amp; sites <span class="text-todoz">como veículo.</span>',
				'desc' => 'Quando a solução precisa de tela, canal ou produto digital, construímos apps e sites sob medida, prontos para carregar agentes, automações e integrações por trás.',
				'bullets' => "Web e mobile com a cara da sua marca\nArquitetura preparada para IA e integrações\nEntrega com o mesmo rigor de software house",
				'cta' => 'Quero saber mais',
			],
			[
				'variant' => 'produtos',
				'icon' => 'ph-cube',
				'title' => 'Produtos <span class="text-todoz">próprios.</span>',
				'desc' => 'Facíleme e Let\'s Work nasceram da mesma exigência que levamos aos projetos dos clientes: produto vivo, melhoria contínua e foco no resultado de quem usa.',
				'bullets' => "Soluções já testadas no mercado\nMesmo padrão de qualidade dos projetos sob medida\nEvolução constante com base no uso real",
				'cta' => 'Conhecer os produtos',
				'link' => '#produtos',
			],
		],
		'produtos_cards' => [
			[
				'featured' => true,
				'badge' => 'Destaque',
				'title' => "Let's Work Facial",
				'subtitle' => 'Ponto com Biometria Facial',
				'desc' => 'Reconhecimento facial e geolocalização para controle de jornada com precisão, segurança e conformidade jurídica.',
				'logo' => $uri . '/assets/img/produtos/logo-letswork.png',
				'logo_light' => $uri . '/assets/img/produtos/letswork-logo-light.webp',
				'logo_light_fallback' => 'https://www.letswork.com.br/wp-content/themes/letswork2022/assets/img/logo.webp',
				'highlights' => "Biometria facial\nGeolocalização\nConformidade jurídica",
				'primary_label' => 'Demonstração',
				'primary_url' => 'https://www.letswork.com.br',
				'secondary_label' => 'Especialista',
				'secondary_modal' => true,
			],
			[
				'featured' => false,
				'wide' => true,
				'title' => 'Facíleme Store',
				'subtitle' => 'Social Commerce',
				'desc' => 'Lojas virtuais para venda e revenda de produtos e serviços nas redes sociais, com checkout simplificado e alta conversão.',
				'logo' => $uri . '/assets/img/produtos/logo-facileme.png',
				'primary_label' => 'Loja Virtual',
				'primary_url' => 'https://www.facileme.com.br/',
				'secondary_label' => 'Falar conosco',
				'secondary_modal' => true,
			],
			[
				'title' => 'Facíleme Ingresso',
				'subtitle' => 'Eventos & Tickets',
				'desc' => 'Venda e divulgação de eventos com poucos cliques — controle de ingressos e presença onde o público já está conectado.',
				'logo' => $uri . '/assets/img/produtos/logo-ingresso.png',
				'primary_label' => 'Ver produto',
				'primary_url' => 'https://www.facilemeingresso.com.br/',
				'secondary_label' => 'Saiba mais',
				'secondary_url' => 'https://www.wvtodoz.com.br/facileme-ingresso/',
			],
			[
				'title' => 'Facíleme Cursos',
				'subtitle' => 'Educação Digital',
				'desc' => 'Plataforma para hospedar, divulgar e vender cursos de forma descomplicada — do catálogo à matrícula.',
				'logo' => $uri . '/assets/img/produtos/logo-cursos.png',
				'primary_label' => 'Ver produto',
				'primary_url' => 'https://www.wvtodoz.com.br/facileme-cursos/',
				'secondary_label' => 'Consultar',
				'secondary_modal' => true,
			],
		],
		'hero_logos' => [
			['alt' => 'Facíleme', 'src' => $uri . '/assets/img/produtos/logo-facileme.png', 'w' => 213, 'h' => 82],
			['alt' => 'Facíleme Ingresso', 'src' => $uri . '/assets/img/produtos/logo-ingresso.png', 'w' => 214, 'h' => 88],
			['alt' => 'Facíleme Cursos', 'src' => $uri . '/assets/img/produtos/logo-cursos.png', 'w' => 282, 'h' => 82],
			['alt' => "Let's Work", 'src' => $uri . '/assets/img/produtos/logo-letswork.png', 'w' => 234, 'h' => 106],
		],
	];
}
