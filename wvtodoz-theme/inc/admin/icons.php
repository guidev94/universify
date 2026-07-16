<?php
/**
 * Phosphor icon catalog for the admin picker.
 *
 * @package WVTodoz
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Icons used on the site + a curated popular set.
 * Values are class suffixes stored in ACF (e.g. ph-robot).
 *
 * @return array<int, array{class:string,label:string,tags:string}>
 */
function wv_admin_icon_catalog(): array {
	$icons = [
		// Site / defaults
		['class' => 'ph-robot', 'label' => 'Robot', 'tags' => 'ia agente ai'],
		['class' => 'ph-flow-arrow', 'label' => 'Flow arrow', 'tags' => 'automacao fluxo'],
		['class' => 'ph-plugs-connected', 'label' => 'Plugs connected', 'tags' => 'integracao api'],
		['class' => 'ph-app-window', 'label' => 'App window', 'tags' => 'app software'],
		['class' => 'ph-cube', 'label' => 'Cube', 'tags' => 'produto 3d'],
		['class' => 'ph-arrow-right', 'label' => 'Arrow right', 'tags' => 'cta seta'],
		['class' => 'ph-arrow-up-right', 'label' => 'Arrow up right', 'tags' => 'link externo'],
		['class' => 'ph-check-circle', 'label' => 'Check circle', 'tags' => 'ok check'],
		['class' => 'ph-whatsapp-logo', 'label' => 'WhatsApp', 'tags' => 'contato social'],
		['class' => 'ph-user', 'label' => 'User', 'tags' => 'pessoa'],
		['class' => 'ph-calendar-blank', 'label' => 'Calendar', 'tags' => 'data agenda'],
		['class' => 'ph-x-circle', 'label' => 'X circle', 'tags' => 'fechar'],
		['class' => 'ph-moon', 'label' => 'Moon', 'tags' => 'tema'],
		['class' => 'ph-sun', 'label' => 'Sun', 'tags' => 'tema'],
		['class' => 'ph-facebook-logo', 'label' => 'Facebook', 'tags' => 'social'],
		['class' => 'ph-instagram-logo', 'label' => 'Instagram', 'tags' => 'social'],
		['class' => 'ph-linkedin-logo', 'label' => 'LinkedIn', 'tags' => 'social'],
		['class' => 'ph-youtube-logo', 'label' => 'YouTube', 'tags' => 'social'],
		// Popular business / tech
		['class' => 'ph-lightning', 'label' => 'Lightning', 'tags' => 'rapido energia'],
		['class' => 'ph-gear', 'label' => 'Gear', 'tags' => 'config automacao'],
		['class' => 'ph-gear-six', 'label' => 'Gear six', 'tags' => 'config'],
		['class' => 'ph-brain', 'label' => 'Brain', 'tags' => 'ia inteligencia'],
		['class' => 'ph-cpu', 'label' => 'CPU', 'tags' => 'tech hardware'],
		['class' => 'ph-cloud', 'label' => 'Cloud', 'tags' => 'nuvem'],
		['class' => 'ph-database', 'label' => 'Database', 'tags' => 'dados'],
		['class' => 'ph-chart-line-up', 'label' => 'Chart up', 'tags' => 'crescimento metricas'],
		['class' => 'ph-chart-bar', 'label' => 'Chart bar', 'tags' => 'analytics'],
		['class' => 'ph-rocket-launch', 'label' => 'Rocket', 'tags' => 'lancamento'],
		['class' => 'ph-target', 'label' => 'Target', 'tags' => 'meta objetivo'],
		['class' => 'ph-handshake', 'label' => 'Handshake', 'tags' => 'parceria'],
		['class' => 'ph-buildings', 'label' => 'Buildings', 'tags' => 'empresa'],
		['class' => 'ph-users-three', 'label' => 'Users', 'tags' => 'equipe'],
		['class' => 'ph-headset', 'label' => 'Headset', 'tags' => 'suporte'],
		['class' => 'ph-chat-circle', 'label' => 'Chat', 'tags' => 'mensagem'],
		['class' => 'ph-envelope-simple', 'label' => 'Envelope', 'tags' => 'email'],
		['class' => 'ph-phone', 'label' => 'Phone', 'tags' => 'telefone'],
		['class' => 'ph-map-pin', 'label' => 'Map pin', 'tags' => 'local'],
		['class' => 'ph-globe', 'label' => 'Globe', 'tags' => 'web global'],
		['class' => 'ph-lock-key', 'label' => 'Lock', 'tags' => 'seguranca'],
		['class' => 'ph-shield-check', 'label' => 'Shield check', 'tags' => 'seguranca'],
		['class' => 'ph-magic-wand', 'label' => 'Magic wand', 'tags' => 'automacao magia'],
		['class' => 'ph-sparkle', 'label' => 'Sparkle', 'tags' => 'destaque'],
		['class' => 'ph-star', 'label' => 'Star', 'tags' => 'favorito'],
		['class' => 'ph-heart', 'label' => 'Heart', 'tags' => 'favorito'],
		['class' => 'ph-lightbulb', 'label' => 'Lightbulb', 'tags' => 'ideia'],
		['class' => 'ph-code', 'label' => 'Code', 'tags' => 'dev'],
		['class' => 'ph-terminal', 'label' => 'Terminal', 'tags' => 'dev'],
		['class' => 'ph-git-branch', 'label' => 'Git branch', 'tags' => 'dev'],
		['class' => 'ph-device-mobile', 'label' => 'Mobile', 'tags' => 'celular'],
		['class' => 'ph-desktop', 'label' => 'Desktop', 'tags' => 'computador'],
		['class' => 'ph-browser', 'label' => 'Browser', 'tags' => 'web'],
		['class' => 'ph-shopping-cart', 'label' => 'Cart', 'tags' => 'ecommerce'],
		['class' => 'ph-credit-card', 'label' => 'Credit card', 'tags' => 'pagamento'],
		['class' => 'ph-wallet', 'label' => 'Wallet', 'tags' => 'financeiro'],
		['class' => 'ph-clock', 'label' => 'Clock', 'tags' => 'tempo'],
		['class' => 'ph-timer', 'label' => 'Timer', 'tags' => 'prazo'],
		['class' => 'ph-path', 'label' => 'Path', 'tags' => 'jornada'],
		['class' => 'ph-tree-structure', 'label' => 'Tree structure', 'tags' => 'fluxo'],
		['class' => 'ph-circles-three-plus', 'label' => 'Circles plus', 'tags' => 'modulos'],
		['class' => 'ph-stack', 'label' => 'Stack', 'tags' => 'camadas'],
		['class' => 'ph-package', 'label' => 'Package', 'tags' => 'produto'],
		['class' => 'ph-briefcase', 'label' => 'Briefcase', 'tags' => 'negocio'],
		['class' => 'ph-megaphone', 'label' => 'Megaphone', 'tags' => 'marketing'],
		['class' => 'ph-presentation-chart', 'label' => 'Presentation', 'tags' => 'pitch'],
		['class' => 'ph-file-text', 'label' => 'File text', 'tags' => 'documento'],
		['class' => 'ph-notebook', 'label' => 'Notebook', 'tags' => 'conteudo'],
		['class' => 'ph-newspaper', 'label' => 'Newspaper', 'tags' => 'blog'],
		['class' => 'ph-play-circle', 'label' => 'Play', 'tags' => 'video'],
		['class' => 'ph-image', 'label' => 'Image', 'tags' => 'midia'],
		['class' => 'ph-camera', 'label' => 'Camera', 'tags' => 'foto'],
		['class' => 'ph-wifi-high', 'label' => 'Wifi', 'tags' => 'conexao'],
		['class' => 'ph-broadcast', 'label' => 'Broadcast', 'tags' => 'sinal'],
		['class' => 'ph-fingerprint', 'label' => 'Fingerprint', 'tags' => 'identidade'],
		['class' => 'ph-key', 'label' => 'Key', 'tags' => 'acesso'],
		['class' => 'ph-puzzle-piece', 'label' => 'Puzzle', 'tags' => 'integracao'],
		['class' => 'ph-arrows-left-right', 'label' => 'Arrows LR', 'tags' => 'sincronizacao'],
		['class' => 'ph-arrows-clockwise', 'label' => 'Arrows sync', 'tags' => 'ciclo'],
		['class' => 'ph-plus-circle', 'label' => 'Plus circle', 'tags' => 'adicionar'],
		['class' => 'ph-minus-circle', 'label' => 'Minus circle', 'tags' => 'remover'],
		['class' => 'ph-info', 'label' => 'Info', 'tags' => 'ajuda'],
		['class' => 'ph-question', 'label' => 'Question', 'tags' => 'faq'],
		['class' => 'ph-warning', 'label' => 'Warning', 'tags' => 'alerta'],
		['class' => 'ph-thumbs-up', 'label' => 'Thumbs up', 'tags' => 'aprovado'],
		['class' => 'ph-smiley', 'label' => 'Smiley', 'tags' => 'experiencia'],
		['class' => 'ph-compass', 'label' => 'Compass', 'tags' => 'direcao'],
		['class' => 'ph-flag', 'label' => 'Flag', 'tags' => 'marco'],
		['class' => 'ph-trophy', 'label' => 'Trophy', 'tags' => 'conquista'],
		['class' => 'ph-medal', 'label' => 'Medal', 'tags' => 'premio'],
		['class' => 'ph-fire', 'label' => 'Fire', 'tags' => 'destaque'],
		['class' => 'ph-drop', 'label' => 'Drop', 'tags' => 'fluido'],
		['class' => 'ph-leaf', 'label' => 'Leaf', 'tags' => 'sustentavel'],
	];

	/**
	 * Filter icon catalog for extensions.
	 *
	 * @param array<int, array{class:string,label:string,tags:string}> $icons
	 */
	return apply_filters('wv_admin_icon_catalog', $icons);
}
