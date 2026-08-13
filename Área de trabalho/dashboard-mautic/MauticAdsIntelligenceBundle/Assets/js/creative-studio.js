(function () {
    'use strict';

    function parseJsonScript(id, fallback) {
        var node = document.getElementById(id);
        if (!node) {
            return fallback;
        }
        try {
            return JSON.parse(node.textContent || node.innerText || '{}');
        } catch (e) {
            return fallback;
        }
    }

    function initCreativeStudio() {
        var root = document.getElementById('ads-creative-studio');
        if (!root || root.getAttribute('data-acs-ready') === '1') {
            return;
        }
        root.setAttribute('data-acs-ready', '1');

        var csrf = root.getAttribute('data-csrf') || '';
        var configured = root.getAttribute('data-configured') === '1';
        var defaultModelsByMode = {
            text: [
                { id: 'gemini-3.6-flash', label: 'Gemini 3.6 Flash' },
                { id: 'gemini-3.5-flash-lite', label: 'Gemini 3.5 Flash-Lite' },
                { id: 'gemini-3.1-pro-preview', label: 'Gemini 3.1 Pro (Preview)' },
                { id: 'gemini-3.5-flash', label: 'Gemini 3.5 Flash' },
                { id: 'gemini-2.5-flash', label: 'Gemini 2.5 Flash' },
                { id: 'gemini-2.5-pro', label: 'Gemini 2.5 Pro' }
            ],
            image: [
                { id: 'gemini-3.1-flash-image-preview', label: 'Gemini 3.1 Flash Image (Preview)' },
                { id: 'gemini-2.5-flash-image', label: 'Gemini 2.5 Flash Image' }
            ],
            video: [
                { id: 'veo-3.1-generate-preview', label: 'Veo 3.1' },
                { id: 'veo-3.1-fast-generate-preview', label: 'Veo 3.1 Fast' }
            ]
        };
        var parsedModels = parseJsonScript('acs-models-json', {});
        var modelsByMode = {
            text: (parsedModels.text && parsedModels.text.length) ? parsedModels.text : defaultModelsByMode.text,
            image: (parsedModels.image && parsedModels.image.length) ? parsedModels.image : defaultModelsByMode.image,
            video: (parsedModels.video && parsedModels.video.length) ? parsedModels.video : defaultModelsByMode.video
        };
        var endpoints = parseJsonScript('acs-endpoints-json', {});
        var i18n = parseJsonScript('acs-i18n-json', {});
        var supportedSseEvents = {
            started: true,
            progress: true,
            completed: true,
            failed: true,
            error: true
        };

        var state = {
            conversations: [],
            activeId: null,
            messages: [],
            canExtend: false,
            sending: false,
            streamController: null,
            referenceFile: null
        };

        var els = {
            list: document.getElementById('acs-conversation-list'),
            messages: document.getElementById('acs-messages'),
            welcome: document.getElementById('acs-welcome'),
            title: document.getElementById('acs-chat-title'),
            mode: document.getElementById('acs-mode'),
            model: document.getElementById('acs-model'),
            aspect: document.getElementById('acs-aspect'),
            extend: document.getElementById('acs-extend'),
            reference: document.getElementById('acs-reference'),
            referencePreview: document.getElementById('acs-reference-preview'),
            referenceThumb: document.getElementById('acs-reference-thumb'),
            referenceClear: document.getElementById('acs-reference-clear'),
            prompt: document.getElementById('acs-prompt'),
            send: document.getElementById('acs-send'),
            status: document.getElementById('acs-status'),
            newChat: document.getElementById('acs-new-chat')
        };

        function url(template, id) {
            return String(template || '').replace('__ID__', String(id));
        }

        function setStatus(text, isError) {
            if (!text) {
                els.status.hidden = true;
                els.status.textContent = '';
                els.status.classList.remove('is-error');
                return;
            }
            els.status.hidden = false;
            els.status.textContent = text;
            els.status.classList.toggle('is-error', !!isError);
        }

        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function request(method, path, options) {
            options = options || {};
            var headers = options.headers || {};
            if (method !== 'GET') {
                headers['X-CSRF-Token'] = csrf;
            }
            if (!(options.body instanceof FormData) && options.json) {
                headers['Content-Type'] = 'application/json';
            }

            return fetch(path, {
                method: method,
                headers: headers,
                body: options.body instanceof FormData
                    ? options.body
                    : (options.json ? JSON.stringify(options.json) : options.body),
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json().catch(function () {
                    return { success: false, error: i18n.error || 'Error' };
                }).then(function (data) {
                    if (!response.ok || data.success === false) {
                        throw new Error(data.error || i18n.error || 'Error');
                    }
                    return data;
                });
            });
        }

        function parseSseBlock(block) {
            var type = 'message';
            var dataLines = [];

            block.replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n').forEach(function (line) {
                if (!line || line.charAt(0) === ':') {
                    return;
                }

                var separator = line.indexOf(':');
                var field = separator === -1 ? line : line.slice(0, separator);
                var value = separator === -1 ? '' : line.slice(separator + 1).replace(/^ /, '');

                if (field === 'event') {
                    type = value || 'message';
                } else if (field === 'data') {
                    dataLines.push(value);
                }
            });

            // Symfony's Web Profiler and reverse proxies may add their own SSE
            // events. Only decode events owned by the Creative Studio protocol.
            if (!supportedSseEvents[type] || !dataLines.length) {
                return null;
            }

            return {
                type: type,
                data: JSON.parse(dataLines.join('\n'))
            };
        }

        function consumeSseResponse(response, onEvent) {
            if (!response.body || typeof response.body.getReader !== 'function') {
                throw new Error(i18n.error || 'Streaming is not supported by this browser.');
            }

            var reader = response.body.getReader();
            var decoder = new TextDecoder('utf-8');
            var buffer = '';
            var lastData = null;

            function findBoundary(value) {
                var candidates = [
                    { index: value.indexOf('\r\n\r\n'), length: 4 },
                    { index: value.indexOf('\n\n'), length: 2 },
                    { index: value.indexOf('\r\r'), length: 2 }
                ].filter(function (candidate) {
                    return candidate.index !== -1;
                }).sort(function (a, b) {
                    return a.index - b.index;
                });

                return candidates.length ? candidates[0] : null;
            }

            function dispatchBlocks(finalChunk) {
                var boundary = findBoundary(buffer);

                while (boundary) {
                    var block = buffer.slice(0, boundary.index);
                    buffer = buffer.slice(boundary.index + boundary.length);
                    if (block) {
                        var parsed = parseSseBlock(block);
                        if (parsed) {
                            lastData = parsed.data;
                            onEvent(parsed.type, parsed.data);
                        }
                    }
                    boundary = findBoundary(buffer);
                }

                if (finalChunk && buffer.trim()) {
                    var parsedFinal = parseSseBlock(buffer);
                    if (parsedFinal) {
                        lastData = parsedFinal.data;
                        onEvent(parsedFinal.type, parsedFinal.data);
                    }
                    buffer = '';
                }
            }

            function read() {
                return reader.read().then(function (chunk) {
                    if (chunk.done) {
                        buffer += decoder.decode();
                        dispatchBlocks(true);
                        return lastData;
                    }

                    buffer += decoder.decode(chunk.value, { stream: true });
                    dispatchBlocks(false);
                    return read();
                });
            }

            return read();
        }

        function streamRequest(method, path, options, onEvent) {
            options = options || {};
            var headers = Object.assign({}, options.headers || {});
            headers.Accept = 'text/event-stream';
            headers['X-CSRF-Token'] = csrf;

            return fetch(path, {
                method: method,
                headers: headers,
                body: options.body,
                credentials: 'same-origin',
                cache: 'no-store',
                signal: options.signal
            }).then(function (response) {
                if (!response.ok) {
                    return response.json().catch(function () {
                        return { error: i18n.error || 'Error' };
                    }).then(function (data) {
                        throw new Error(data.error || i18n.error || 'Error');
                    });
                }

                var contentType = response.headers.get('Content-Type') || '';
                if (contentType.indexOf('text/event-stream') === -1) {
                    throw new Error(i18n.error || 'Invalid streaming response.');
                }

                return consumeSseResponse(response, onEvent);
            });
        }

        function refreshModels() {
            if (!els.mode || !els.model) {
                return;
            }

            var mode = els.mode.value || 'text';
            var models = modelsByMode[mode] || defaultModelsByMode[mode] || [];

            while (els.model.firstChild) {
                els.model.removeChild(els.model.firstChild);
            }
            models.forEach(function (item, index) {
                var option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.label;
                option.setAttribute('data-mode', mode);
                if (index === 0) {
                    option.selected = true;
                }
                els.model.appendChild(option);
            });

            root.classList.toggle('is-video-mode', mode === 'video');
            root.querySelectorAll('.acs-mode-pill').forEach(function (pill) {
                pill.classList.toggle('is-active', pill.getAttribute('data-mode') === mode);
            });
            updateExtendAvailability();

            if (typeof mQuery !== 'undefined') {
                mQuery(els.model).trigger('chosen:updated');
                mQuery(els.mode).trigger('chosen:updated');
            }
        }

        function updateExtendAvailability() {
            var mode = els.mode.value;
            var model = els.model.value;
            var supported = mode === 'video' && state.canExtend && model.indexOf('veo-3.1') !== -1 && model.indexOf('fast') === -1;
            els.extend.disabled = !supported;
            if (!supported) {
                els.extend.checked = false;
            }
        }

        function renderConversations() {
            if (!state.conversations.length) {
                els.list.innerHTML = '<div class="ads-creative-studio__empty-list">' + escapeHtml(i18n.noConversations || '') + '</div>';
                return;
            }

            els.list.innerHTML = state.conversations.map(function (conv) {
                var active = String(conv.id) === String(state.activeId) ? ' is-active' : '';
                return (
                    '<div class="ads-creative-studio__conv-item' + active + '" data-id="' + conv.id + '">' +
                        '<span title="' + escapeHtml(conv.title) + '">' + escapeHtml(conv.title) + '</span>' +
                        '<button type="button" class="acs-delete" data-delete="' + conv.id + '" title="' + escapeHtml(i18n.delete || 'Delete') + '">&times;</button>' +
                    '</div>'
                );
            }).join('');
        }

        function mediaHtml(message) {
            if (!message.hasMedia || !message.previewUrl) {
                return '';
            }
            if (message.mediaType === 'image') {
                return '<div class="ads-creative-studio__media"><img src="' + escapeHtml(message.previewUrl) + '" alt=""></div>';
            }
            if (message.mediaType === 'video') {
                return '<div class="ads-creative-studio__media"><video src="' + escapeHtml(message.previewUrl) + '" controls playsinline></video></div>';
            }
            return '';
        }

        function actionsHtml(message) {
            var html = '<div class="ads-creative-studio__actions">';
            var hasActions = false;

            if (message.role === 'user' && message.content) {
                html += '<button type="button" class="btn btn-xs btn-default acs-replay" data-id="' + message.id + '" title="' + escapeHtml(i18n.replay || 'Replay') + '">' +
                    '<span aria-hidden="true">↻</span> ' + escapeHtml(i18n.replay || 'Replay') +
                    '</button>';
                hasActions = true;
            }

            if (message.role === 'assistant' && message.status === 'failed') {
                html += '<button type="button" class="btn btn-xs btn-default acs-replay" data-id="' + message.id + '" data-from-assistant="1" title="' + escapeHtml(i18n.replay || 'Replay') + '">' +
                    '<span aria-hidden="true">↻</span> ' + escapeHtml(i18n.replay || 'Replay') +
                    '</button>';
                hasActions = true;
            }

            if (message.hasMedia) {
                if (message.downloadUrl) {
                    html += '<a class="btn btn-xs btn-default" href="' + escapeHtml(message.downloadUrl) + '">' + escapeHtml(i18n.download || 'Download') + '</a>';
                    hasActions = true;
                }
                if (message.saveAssetUrl) {
                    html += '<button type="button" class="btn btn-xs btn-primary acs-save-asset" data-id="' + message.id + '">' + escapeHtml(i18n.saveAsset || 'Save') + '</button>';
                    hasActions = true;
                }
            }

            html += '</div>';

            if (message.assetUrl) {
                html += '<div class="ads-creative-studio__asset-url"><strong>' + escapeHtml(i18n.assetUrl || 'URL') + ':</strong> ' +
                    '<a href="' + escapeHtml(message.assetUrl) + '" target="_blank" rel="noopener">' + escapeHtml(message.assetUrl) + '</a> ' +
                    '<button type="button" class="btn btn-xs btn-default acs-copy-url" data-url="' + escapeHtml(message.assetUrl) + '">' + escapeHtml(i18n.copyUrl || 'Copy') + '</button></div>';
                hasActions = true;
            }

            return hasActions ? html : '';
        }

        function applyGenerationEvent(type, data) {
            if (type === 'started') {
                setStatus(i18n.generating || 'Generating creative…');
                return;
            }

            if (type === 'error' || data.success === false) {
                throw new Error(data.error || i18n.error || 'Error');
            }

            if (data.conversation) {
                upsertConversation(data.conversation);
                els.title.textContent = data.conversation.title || els.title.textContent;
            }
            if (data.userMessage) {
                replaceMessage(data.userMessage);
            }
            if (data.message) {
                replaceMessage(data.message);
            }

            state.canExtend = !!data.canExtend;
            updateExtendAvailability();

            if (type === 'progress') {
                setStatus((data.message && data.message.content) || i18n.generating || 'Generating creative…');
            } else if (type === 'failed') {
                setStatus((data.message && data.message.content) || i18n.error || 'Error', true);
            } else if (type === 'completed') {
                setStatus('');
            }
        }

        function startStream() {
            stopStreaming();
            var controller = new AbortController();
            state.streamController = controller;
            return controller;
        }

        function finishStream(controller) {
            if (controller && state.streamController !== controller) {
                return;
            }
            state.streamController = null;
            state.sending = false;
            els.send.disabled = !configured;
        }

        function replayMessage(messageId) {
            if (state.sending || !configured) {
                return;
            }
            if (!endpoints.replayStream) {
                setStatus(i18n.error || 'Error', true);
                return;
            }

            state.sending = true;
            els.send.disabled = true;
            setStatus('');

            var controller = startStream();
            streamRequest('POST', url(endpoints.replayStream, messageId), {
                body: new FormData(),
                signal: controller.signal
            }, applyGenerationEvent).then(function () {
                finishStream(controller);
            }).catch(function (error) {
                if (error.name === 'AbortError') {
                    return;
                }
                setStatus(error.message || i18n.error, true);
                finishStream(controller);
            });
        }

        function renderMessages() {
            if (!state.messages.length) {
                els.messages.innerHTML = '';
                if (els.welcome) {
                    els.messages.appendChild(els.welcome);
                    els.welcome.hidden = false;
                }
                return;
            }

            if (els.welcome) {
                els.welcome.hidden = true;
            }

            els.messages.innerHTML = state.messages.map(function (message) {
                var roleClass = message.role === 'user' ? 'ads-creative-studio__msg--user' : 'ads-creative-studio__msg--assistant';
                var bubbleClass = 'ads-creative-studio__bubble';
                if (message.status === 'failed') {
                    bubbleClass += ' is-failed';
                }
                if (message.status === 'pending') {
                    bubbleClass += ' is-pending';
                }
                return (
                    '<div class="ads-creative-studio__msg ' + roleClass + '">' +
                        '<div class="' + bubbleClass + '">' +
                            '<div>' + escapeHtml(message.content || '') + '</div>' +
                            mediaHtml(message) +
                            actionsHtml(message) +
                        '</div>' +
                    '</div>'
                );
            }).join('');

            els.messages.scrollTop = els.messages.scrollHeight;
        }

        function upsertConversation(conversation) {
            var found = false;
            state.conversations = state.conversations.map(function (item) {
                if (String(item.id) === String(conversation.id)) {
                    found = true;
                    return conversation;
                }
                return item;
            });
            if (!found) {
                state.conversations.unshift(conversation);
            }
            state.conversations.sort(function (a, b) {
                return String(b.dateModified || b.dateAdded).localeCompare(String(a.dateModified || a.dateAdded));
            });
            renderConversations();
        }

        function loadConversations() {
            return request('GET', endpoints.conversations).then(function (data) {
                state.conversations = data.conversations || [];
                renderConversations();
                if (!state.activeId && state.conversations.length) {
                    return openConversation(state.conversations[0].id);
                }
            });
        }

        function openConversation(id) {
            stopStreaming();
            state.sending = false;
            els.send.disabled = !configured;
            return request('GET', url(endpoints.conversation, id)).then(function (data) {
                state.activeId = id;
                state.messages = data.messages || [];
                state.canExtend = !!data.canExtend;
                if (data.conversation) {
                    els.title.textContent = data.conversation.title || i18n.newChat || 'Chat';
                    if (data.conversation.mode) {
                        els.mode.value = data.conversation.mode;
                        refreshModels();
                    }
                    if (data.conversation.model) {
                        els.model.value = data.conversation.model;
                    }
                    upsertConversation(data.conversation);
                }
                renderConversations();
                renderMessages();
                updateExtendAvailability();
                maybeStreamPending();
            });
        }

        function createConversation(abortActiveStream) {
            if (abortActiveStream) {
                stopStreaming();
                state.sending = false;
                els.send.disabled = !configured;
            }

            return request('POST', endpoints.create, {
                json: {
                    mode: els.mode.value,
                    model: els.model.value
                }
            }).then(function (data) {
                state.activeId = data.conversation.id;
                state.messages = [];
                state.canExtend = false;
                upsertConversation(data.conversation);
                els.title.textContent = data.conversation.title || i18n.newChat || 'Chat';
                renderMessages();
                updateExtendAvailability();
                els.prompt.focus();
            });
        }

        function ensureConversation() {
            if (state.activeId) {
                return Promise.resolve(state.activeId);
            }
            return createConversation().then(function () {
                return state.activeId;
            });
        }

        function stopStreaming() {
            if (state.streamController) {
                state.streamController.abort();
                state.streamController = null;
            }
        }

        function maybeStreamPending() {
            var pending = state.messages.find(function (message) {
                return message.role === 'assistant' && message.status === 'pending' && message.streamUrl;
            });
            if (!pending) {
                return;
            }
            streamPendingMessage(pending);
        }

        function streamPendingMessage(message) {
            state.sending = true;
            els.send.disabled = true;
            setStatus(i18n.generatingVideo || 'Generating video…');
            var controller = startStream();

            streamRequest('GET', message.streamUrl, {
                signal: controller.signal
            }, applyGenerationEvent).then(function () {
                finishStream(controller);
            }).catch(function (error) {
                if (error.name === 'AbortError') {
                    return;
                }
                setStatus(error.message || i18n.error, true);
                finishStream(controller);
            });
        }

        function replaceMessage(message) {
            if (!message || !message.id) {
                return;
            }
            var replaced = false;
            state.messages = state.messages.map(function (item) {
                if (String(item.id) === String(message.id)) {
                    replaced = true;
                    return message;
                }
                return item;
            });
            if (!replaced) {
                state.messages.push(message);
            }
            renderMessages();
        }

        function sendPrompt() {
            if (state.sending || !configured) {
                return;
            }
            var prompt = (els.prompt.value || '').trim();
            if (!prompt) {
                return;
            }

            state.sending = true;
            els.send.disabled = true;
            setStatus('');

            var controller;
            var composerCleared = false;

            ensureConversation().then(function (conversationId) {
                var form = new FormData();
                form.append('prompt', prompt);
                form.append('mode', els.mode.value);
                form.append('model', els.model.value);
                form.append('aspect_ratio', els.aspect.value);
                form.append('extend_video', els.extend.checked ? '1' : '0');
                form.append('_token', csrf);
                if (state.referenceFile && els.mode.value === 'video' && !els.extend.checked) {
                    form.append('reference_image', state.referenceFile);
                }

                controller = startStream();
                return streamRequest('POST', url(endpoints.messagesStream, conversationId), {
                    body: form,
                    signal: controller.signal
                }, function (type, data) {
                    applyGenerationEvent(type, data);
                    if (!composerCleared && data.userMessage) {
                        composerCleared = true;
                        els.prompt.value = '';
                        clearReference();
                    }
                });
            }).then(function () {
                finishStream(controller);
            }).catch(function (error) {
                if (error.name === 'AbortError') {
                    return;
                }
                setStatus(error.message || i18n.error, true);
                finishStream(controller);
            });
        }

        function clearReference() {
            state.referenceFile = null;
            if (els.reference) {
                els.reference.value = '';
            }
            if (els.referencePreview) {
                els.referencePreview.hidden = true;
            }
            if (els.referenceThumb) {
                els.referenceThumb.removeAttribute('src');
            }
            var nameEl = document.getElementById('acs-reference-name');
            if (nameEl) {
                nameEl.textContent = '';
            }
        }

        function saveAsset(messageId) {
            var message = state.messages.find(function (item) {
                return String(item.id) === String(messageId);
            });
            if (!message || !message.saveAssetUrl) {
                return;
            }

            setStatus('');
            request('POST', message.saveAssetUrl, { json: {} }).then(function (data) {
                replaceMessage(data.message);
                setStatus((i18n.assetUrl || 'URL') + ': ' + (data.assetUrl || ''));
            }).catch(function (error) {
                setStatus(error.message || i18n.error, true);
            });
        }

        els.newChat.addEventListener('click', function () {
            createConversation(true).catch(function (error) {
                setStatus(error.message || i18n.error, true);
            });
        });

        els.list.addEventListener('click', function (event) {
            var deleteBtn = event.target.closest('[data-delete]');
            if (deleteBtn) {
                event.stopPropagation();
                var deleteId = deleteBtn.getAttribute('data-delete');
                request('DELETE', url(endpoints.delete, deleteId), { json: {} }).then(function () {
                    state.conversations = state.conversations.filter(function (item) {
                        return String(item.id) !== String(deleteId);
                    });
                    if (String(state.activeId) === String(deleteId)) {
                        stopStreaming();
                        state.sending = false;
                        els.send.disabled = !configured;
                        state.activeId = null;
                        state.messages = [];
                        state.canExtend = false;
                        els.title.textContent = i18n.newChat || 'Chat';
                        renderMessages();
                    }
                    renderConversations();
                    if (!state.activeId && state.conversations.length) {
                        openConversation(state.conversations[0].id);
                    }
                }).catch(function (error) {
                    setStatus(error.message || i18n.error, true);
                });
                return;
            }

            var item = event.target.closest('[data-id]');
            if (!item) {
                return;
            }
            openConversation(item.getAttribute('data-id')).catch(function (error) {
                setStatus(error.message || i18n.error, true);
            });
        });

        els.messages.addEventListener('click', function (event) {
            var replayBtn = event.target.closest('.acs-replay');
            if (replayBtn) {
                replayMessage(replayBtn.getAttribute('data-id'));
                return;
            }
            var saveBtn = event.target.closest('.acs-save-asset');
            if (saveBtn) {
                saveAsset(saveBtn.getAttribute('data-id'));
                return;
            }
            var copyBtn = event.target.closest('.acs-copy-url');
            if (copyBtn && navigator.clipboard) {
                navigator.clipboard.writeText(copyBtn.getAttribute('data-url') || '');
            }
        });

        if (els.mode) {
            els.mode.addEventListener('change', refreshModels);
            if (typeof mQuery !== 'undefined') {
                mQuery(els.mode).off('change.adsCreativeStudio').on('change.adsCreativeStudio', refreshModels);
            }
        }

        root.querySelectorAll('.acs-mode-pill').forEach(function (pill) {
            pill.addEventListener('click', function () {
                if (!els.mode) {
                    return;
                }
                els.mode.value = pill.getAttribute('data-mode');
                refreshModels();
            });
        });

        var attachBtn = document.getElementById('acs-attach-row');
        if (attachBtn && els.reference) {
            attachBtn.addEventListener('click', function (event) {
                event.preventDefault();
                els.reference.click();
            });
        }

        if (els.model) {
            els.model.addEventListener('change', updateExtendAvailability);
        }
        els.send.addEventListener('click', sendPrompt);
        els.prompt.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendPrompt();
            }
        });
        els.prompt.addEventListener('input', function () {
            els.prompt.style.height = 'auto';
            els.prompt.style.height = Math.min(els.prompt.scrollHeight, 160) + 'px';
        });

        els.reference.addEventListener('change', function () {
            var file = els.reference.files && els.reference.files[0] ? els.reference.files[0] : null;
            state.referenceFile = file;
            if (!file) {
                clearReference();
                return;
            }
            var reader = new FileReader();
            reader.onload = function () {
                els.referenceThumb.src = reader.result;
                els.referencePreview.hidden = false;
                var nameEl = document.getElementById('acs-reference-name');
                if (nameEl) {
                    nameEl.textContent = file.name;
                }
            };
            reader.readAsDataURL(file);
        });

        if (els.referenceClear) {
            els.referenceClear.addEventListener('click', clearReference);
        }

        refreshModels();
        loadConversations().catch(function (error) {
            setStatus(error.message || i18n.error, true);
        });
    }

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    ready(initCreativeStudio);

    if (typeof mQuery !== 'undefined') {
        mQuery(document).off('mauticContentLoaded.adsCreativeStudio').on('mauticContentLoaded.adsCreativeStudio', function () {
            initCreativeStudio();
        });
    }

    window.AdsCreativeStudio = {
        init: initCreativeStudio
    };
})();
