/**
 * WV Todoz admin — ACF tab focus, icon picker, confirms, editor banner.
 */
(function () {
	'use strict';

	var cfg = window.wvAdmin || {};

	function onReady(fn) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fn);
		} else {
			fn();
		}
	}

	function findTabButton(fieldKey) {
		if (!fieldKey) return null;
		return (
			document.querySelector('.acf-tab-button[data-key="' + fieldKey + '"]') ||
			document.querySelector('a.acf-tab-button[data-key="' + fieldKey + '"]') ||
			document.querySelector('.acf-tab-group a[data-key="' + fieldKey + '"]')
		);
	}

	function clickAcfTab() {
		var slug = cfg.tab;
		if (!slug || !cfg.tabKeys) return false;
		var key = cfg.tabKeys[slug];
		if (!key) return false;
		var btn = findTabButton(key);
		if (!btn) return false;
		btn.click();
		btn.scrollIntoView({ behavior: 'smooth', block: 'center' });
		return true;
	}

	function injectBanner() {
		if (!cfg.notice) return;
		if (document.querySelector('.wv-admin-banner')) return;

		var banner = document.createElement('div');
		banner.className = 'wv-admin-banner';
		banner.setAttribute('role', 'status');

		var p = document.createElement('p');
		p.textContent = cfg.notice;
		banner.appendChild(p);

		if (cfg.panelUrl) {
			var a = document.createElement('a');
			a.className = 'wv-admin-banner__link';
			a.href = cfg.panelUrl;
			a.textContent = (cfg.i18n && cfg.i18n.back) || 'Painel WV Todoz';
			banner.appendChild(a);
		}

		var wrap = document.querySelector('.wrap');
		if (wrap && wrap.parentNode) {
			wrap.parentNode.insertBefore(banner, wrap);
		}
	}

	function bindConfirms() {
		document.querySelectorAll('[data-wv-confirm]').forEach(function (el) {
			el.addEventListener('click', function (e) {
				var msg = el.getAttribute('data-wv-confirm');
				if (msg && !window.confirm(msg)) {
					e.preventDefault();
				}
			});
		});
	}

	function focusTabWithRetry() {
		if (!cfg.tab) return;
		var attempts = 0;
		var max = 40;
		var timer = window.setInterval(function () {
			attempts += 1;
			if (clickAcfTab() || attempts >= max) {
				window.clearInterval(timer);
			}
		}, 150);

		if (window.acf && typeof window.acf.addAction === 'function') {
			window.acf.addAction('ready', function () {
				clickAcfTab();
			});
		}
	}

	/* ——— Icon picker ——— */

	var pickerState = {
		targetInput: null,
		previewEl: null,
		overlay: null,
	};

	function normalizeIconClass(value) {
		var v = (value || '').trim();
		if (!v) return 'ph-robot';
		if (v.indexOf('ph-') === 0) return v;
		return 'ph-' + v.replace(/^ph\s+/, '');
	}

	function ensurePickerDom() {
		if (pickerState.overlay) return pickerState.overlay;

		var overlay = document.createElement('div');
		overlay.className = 'wv-icon-picker';
		overlay.setAttribute('role', 'dialog');
		overlay.setAttribute('aria-modal', 'true');
		overlay.setAttribute('aria-label', (cfg.i18n && cfg.i18n.pickIcon) || 'Escolher ícone');
		overlay.hidden = true;

		overlay.innerHTML =
			'<div class="wv-icon-picker__panel">' +
			'  <header class="wv-icon-picker__head">' +
			'    <div>' +
			'      <p class="wv-icon-picker__eyebrow">WV_TODOZ</p>' +
			'      <h2 class="wv-icon-picker__title"></h2>' +
			'    </div>' +
			'    <button type="button" class="wv-icon-picker__close" aria-label="' +
			((cfg.i18n && cfg.i18n.close) || 'Fechar') +
			'">×</button>' +
			'  </header>' +
			'  <div class="wv-icon-picker__toolbar">' +
			'    <input type="search" class="wv-icon-picker__search" placeholder="' +
			((cfg.i18n && cfg.i18n.searchIcon) || 'Buscar ícone…') +
			'" />' +
			'    <div class="wv-icon-picker__current">' +
			'      <i class="ph ph-robot" data-wv-current-icon aria-hidden="true"></i>' +
			'      <code data-wv-current-class>ph-robot</code>' +
			'    </div>' +
			'  </div>' +
			'  <div class="wv-icon-picker__grid" data-wv-icon-grid></div>' +
			'  <p class="wv-icon-picker__empty" hidden></p>' +
			'</div>';

		overlay.querySelector('.wv-icon-picker__title').textContent =
			(cfg.i18n && cfg.i18n.pickIcon) || 'Escolher ícone';
		overlay.querySelector('.wv-icon-picker__empty').textContent =
			(cfg.i18n && cfg.i18n.noIcons) || 'Nenhum ícone encontrado.';

		document.body.appendChild(overlay);
		pickerState.overlay = overlay;

		overlay.addEventListener('click', function (e) {
			if (e.target === overlay) closePicker();
		});
		overlay.querySelector('.wv-icon-picker__close').addEventListener('click', closePicker);
		overlay.querySelector('.wv-icon-picker__search').addEventListener('input', function (e) {
			renderIconGrid(e.target.value || '');
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && pickerState.overlay && !pickerState.overlay.hidden) {
				closePicker();
			}
		});

		return overlay;
	}

	function renderIconGrid(query) {
		var overlay = ensurePickerDom();
		var grid = overlay.querySelector('[data-wv-icon-grid]');
		var empty = overlay.querySelector('.wv-icon-picker__empty');
		var q = (query || '').toLowerCase().trim();
		var icons = cfg.icons || [];
		grid.innerHTML = '';

		var shown = 0;
		icons.forEach(function (icon) {
			var hay = (icon.class + ' ' + icon.label + ' ' + (icon.tags || '')).toLowerCase();
			if (q && hay.indexOf(q) === -1) return;
			shown += 1;
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'wv-icon-picker__item';
			btn.setAttribute('data-icon', icon.class);
			btn.title = icon.label + ' (' + icon.class + ')';
			btn.innerHTML =
				'<i class="ph ' +
				icon.class +
				'" aria-hidden="true"></i>' +
				'<span>' +
				icon.label +
				'</span>';
			btn.addEventListener('click', function () {
				applyIcon(icon.class);
				closePicker();
			});
			grid.appendChild(btn);
		});

		empty.hidden = shown > 0;
	}

	function openPicker(input, previewEl) {
		pickerState.targetInput = input;
		pickerState.previewEl = previewEl || null;
		var overlay = ensurePickerDom();
		var current = normalizeIconClass(input.value);
		var curIcon = overlay.querySelector('[data-wv-current-icon]');
		var curClass = overlay.querySelector('[data-wv-current-class]');
		curIcon.className = 'ph ' + current;
		curClass.textContent = current;
		overlay.querySelector('.wv-icon-picker__search').value = '';
		renderIconGrid('');
		overlay.hidden = false;
		document.body.classList.add('wv-icon-picker-open');
		window.setTimeout(function () {
			overlay.querySelector('.wv-icon-picker__search').focus();
		}, 30);
	}

	function closePicker() {
		if (!pickerState.overlay) return;
		pickerState.overlay.hidden = true;
		document.body.classList.remove('wv-icon-picker-open');
		pickerState.targetInput = null;
	}

	function applyIcon(iconClass) {
		var value = normalizeIconClass(iconClass);
		if (pickerState.targetInput) {
			pickerState.targetInput.value = value;
			pickerState.targetInput.dispatchEvent(new Event('input', { bubbles: true }));
			pickerState.targetInput.dispatchEvent(new Event('change', { bubbles: true }));
			if (window.jQuery) {
				window.jQuery(pickerState.targetInput).trigger('change');
			}
		}
		if (pickerState.previewEl) {
			pickerState.previewEl.className = 'ph ' + value + ' wv-icon-field__preview-icon';
		}
		document.querySelectorAll('[data-wv-icon-sync]').forEach(function (el) {
			if (el.getAttribute('data-wv-icon-sync') === 'preview') {
				el.className = 'ph ' + value;
			}
		});
	}

	function enhanceIconInput(input) {
		if (!input || input.dataset.wvIconReady === '1') return;
		input.dataset.wvIconReady = '1';

		var wrap = document.createElement('div');
		wrap.className = 'wv-icon-field';
		input.parentNode.insertBefore(wrap, input);
		wrap.appendChild(input);

		var preview = document.createElement('span');
		preview.className = 'wv-icon-field__preview';
		preview.innerHTML = '<i class="ph ' + normalizeIconClass(input.value) + ' wv-icon-field__preview-icon" aria-hidden="true"></i>';

		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'wv-admin__btn wv-admin__btn--primary wv-icon-field__btn';
		btn.textContent = (cfg.i18n && cfg.i18n.pickIcon) || 'Escolher ícone';

		wrap.appendChild(preview);
		wrap.appendChild(btn);

		input.addEventListener('input', function () {
			var icon = preview.querySelector('i');
			if (icon) icon.className = 'ph ' + normalizeIconClass(input.value) + ' wv-icon-field__preview-icon';
		});

		btn.addEventListener('click', function (e) {
			e.preventDefault();
			openPicker(input, preview.querySelector('i'));
		});
	}

	function wireIconFields() {
		var selectors = [
			'.acf-field[data-name$="_icon"] input[type="text"]',
			'.acf-field[data-name*="_icon"] input[type="text"]',
			'input[name="acf[field_wv_servico_icon]"]',
			'.wv-acf-icon-field input[type="text"]',
			'[data-wv-icon-input]',
		];
		selectors.forEach(function (sel) {
			document.querySelectorAll(sel).forEach(enhanceIconInput);
		});
	}

	function wireSectionIconButtons() {
		document.querySelectorAll('[data-wv-open-icon-picker]').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				var sel = btn.getAttribute('data-wv-open-icon-picker');
				var input = sel ? document.querySelector(sel) : null;
				if (input) openPicker(input, null);
			});
		});
	}

	onReady(function () {
		bindConfirms();
		wireIconFields();
		wireSectionIconButtons();
		if (cfg.tab) {
			injectBanner();
			focusTabWithRetry();
		}
		if (window.acf && typeof window.acf.addAction === 'function') {
			window.acf.addAction('ready', wireIconFields);
			window.acf.addAction('append', wireIconFields);
		}
		// Late ACF paint
		window.setTimeout(wireIconFields, 400);
		window.setTimeout(wireIconFields, 1200);
	});
})();
