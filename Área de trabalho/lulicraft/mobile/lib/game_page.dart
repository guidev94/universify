import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:wakelock_plus/wakelock_plus.dart';
import 'package:webview_flutter/webview_flutter.dart';

import 'local_web_server.dart';
import 'settings_controller.dart';
import 'theme.dart';
import 'ui_kit.dart';

class GamePage extends StatefulWidget {
  const GamePage({
    super.key,
    required this.settings,
    this.openMenu = false,
    this.demo = false,
  });

  final SettingsController settings;
  final bool openMenu;
  final bool demo;

  @override
  State<GamePage> createState() => _GamePageState();
}

class _GamePageState extends State<GamePage> with WidgetsBindingObserver {
  late final LocalWebServer _server;
  WebViewController? _controller;
  Uri? _gameUri;
  bool _loading = true;
  bool _ready = false;
  bool _leaving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _server = LocalWebServer(bootstrapSettings: widget.settings.value.toMap());
    _enterImmersive();
    unawaited(WakelockPlus.enable().catchError((Object _) {}));
    _controller = _createController();
    unawaited(_start());
  }

  WebViewController _createController() {
    return WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(LumiColors.background)
      ..addJavaScriptChannel(
        'LulicraftBridge',
        onMessageReceived: _onBridgeMessage,
      )
      ..setNavigationDelegate(
        NavigationDelegate(
          onNavigationRequest: (request) {
            final uri = Uri.tryParse(request.url);
            if (uri == null) return NavigationDecision.prevent;
            if (_isLocalUri(uri)) return NavigationDecision.navigate;
            if (request.isMainFrame) {
              unawaited(openExternalLink(context, request.url));
            }
            return NavigationDecision.prevent;
          },
          onWebResourceError: (error) {
            if (error.isForMainFrame != true) return;
            if (!mounted || _ready) return;
            setState(() {
              _loading = false;
              _error = 'Não foi possível carregar o jogo. '
                  'Verifique se o aparelho tem WebGL habilitado e tente novamente.';
            });
          },
        ),
      );
  }

  static bool _isLocalUri(Uri uri) {
    if (uri.host == '127.0.0.1' || uri.host == 'localhost') return true;
    return uri.scheme == 'about' || uri.scheme == 'data' || uri.scheme == 'blob';
  }

  Future<void> _start() async {
    try {
      final port = await _server.start();
      _gameUri = Uri(
        scheme: 'http',
        host: '127.0.0.1',
        port: port,
        path: '/index.html',
        queryParameters: {
          'app': '1',
          if (widget.openMenu) 'menu': '1',
          if (widget.demo) 'demo': '1',
          ...widget.settings.value.toQueryParameters(),
        },
      );
      await _controller?.loadRequest(_gameUri!);
    } catch (error) {
      debugPrint('Falha ao iniciar o servidor local: $error');
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = 'Não foi possível iniciar o jogo neste aparelho.';
      });
    }
  }

  Future<void> _retry() async {
    final uri = _gameUri;
    if (uri == null) {
      setState(() {
        _error = null;
        _loading = true;
      });
      await _start();
      return;
    }
    setState(() {
      _error = null;
      _loading = true;
      _ready = false;
    });
    await _controller?.loadRequest(uri);
  }

  void _onBridgeMessage(JavaScriptMessage message) {
    Object? decoded;
    try {
      decoded = jsonDecode(message.message);
    } catch (_) {
      return;
    }
    if (decoded is! Map) return;
    final type = decoded['type'];
    switch (type) {
      case 'ready':
        _handleReady();
        break;
      case 'settings-changed':
        final settings = decoded['settings'];
        if (settings is Map) {
          unawaited(widget.settings.mergeFromGame(settings.cast<String, dynamic>()));
        }
        break;
      case 'exit':
        _exitToHome();
        break;
      case 'view-changed':
      case 'pause':
      case 'resume':
      case 'menu':
      case 'fps':
        break;
    }
  }

  void _handleReady() {
    _ready = true;
    if (mounted) {
      setState(() {
        _loading = false;
        _error = null;
      });
    }
    unawaited(_applySettingsToGame());
  }

  Future<void> _applySettingsToGame() async {
    final controller = _controller;
    if (controller == null) return;
    final payload = jsonEncode(widget.settings.value.toMap()).replaceAll('<', r'\u003c');
    try {
      await controller.runJavaScript(
        'window.LulicraftNative && window.LulicraftNative.setSettings($payload);',
      );
    } catch (error) {
      debugPrint('Não foi possível aplicar as configurações no jogo: $error');
    }
  }

  Future<void> _handleBack() async {
    final controller = _controller;
    if (!_ready || controller == null) {
      _exitToHome();
      return;
    }
    try {
      final raw = await controller.runJavaScriptReturningResult(
        'window.LulicraftNative ? window.LulicraftNative.handleBack() : "none"',
      );
      final result = raw
          .toString()
          .replaceAll('"', '')
          .replaceAll("'", '')
          .trim();
      if (result == 'none') _exitToHome();
    } catch (error) {
      debugPrint('Falha no botão voltar: $error');
      _exitToHome();
    }
  }

  void _exitToHome() {
    if (_leaving || !mounted) return;
    _leaving = true;
    Navigator.of(context).pop();
  }

  void _enterImmersive() {
    unawaited(SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky));
    SystemChrome.setSystemUIOverlayStyle(
      const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        systemNavigationBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
        systemNavigationBarIconBrightness: Brightness.light,
      ),
    );
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    switch (state) {
      case AppLifecycleState.paused:
      case AppLifecycleState.hidden:
      case AppLifecycleState.detached:
        if (_ready) {
          unawaited(
            _controller?.runJavaScript('window.LulicraftNative?.pause();') ??
                Future<void>.value(),
          );
        }
        unawaited(WakelockPlus.disable().catchError((Object _) {}));
      case AppLifecycleState.resumed:
        unawaited(WakelockPlus.enable().catchError((Object _) {}));
        _enterImmersive();
        if (_ready) unawaited(_applySettingsToGame());
      case AppLifecycleState.inactive:
        break;
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    unawaited(WakelockPlus.disable().catchError((Object _) {}));
    unawaited(_server.stop());
    unawaited(SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge));
    SystemChrome.setSystemUIOverlayStyle(
      const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        systemNavigationBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
        systemNavigationBarIconBrightness: Brightness.light,
      ),
    );
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        unawaited(_handleBack());
      },
      child: Scaffold(
        backgroundColor: LumiColors.background,
        body: Stack(
          fit: StackFit.expand,
          children: [
            if (controller != null) WebViewWidget(controller: controller),
            if (_loading) _LoadingOverlay(onClose: _exitToHome),
            if (_error != null)
              _ErrorOverlay(
                message: _error!,
                onRetry: _retry,
                onClose: _exitToHome,
              ),
          ],
        ),
      ),
    );
  }
}

class _LoadingOverlay extends StatelessWidget {
  const _LoadingOverlay({required this.onClose});

  final VoidCallback onClose;

  @override
  Widget build(BuildContext context) {
    return Stack(
      fit: StackFit.expand,
      children: [
        const Positioned.fill(child: LumiBackdrop()),
        SafeArea(
          child: Align(
            alignment: Alignment.topRight,
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: LumiRoundIconButton(
                icon: Icons.close_rounded,
                tooltip: 'Voltar ao início',
                onPressed: onClose,
              ),
            ),
          ),
        ),
        SafeArea(
          child: Center(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 32),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(28),
                    child: Image.asset(
                      'assets/branding/icon.png',
                      width: 104,
                      height: 104,
                      semanticLabel: 'Ícone do Lulicraft',
                    ),
                  ),
                  const SizedBox(height: 24),
                  Text(
                    LumiBranding.appName,
                    style: Theme.of(context).textTheme.displayMedium,
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'Preparando seu mundo…',
                    style: TextStyle(color: LumiColors.muted, fontSize: 15),
                  ),
                  const SizedBox(height: 26),
                  const SizedBox(
                    width: 26,
                    height: 26,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.4,
                      color: LumiColors.primary,
                    ),
                  ),
                  const SizedBox(height: 26),
                  const Text(
                    'Dica: gire o celular para ver mais do mundo.',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: LumiColors.muted,
                      fontSize: 12.5,
                      height: 1.4,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _ErrorOverlay extends StatelessWidget {
  const _ErrorOverlay({
    required this.message,
    required this.onRetry,
    required this.onClose,
  });

  final String message;
  final VoidCallback onRetry;
  final VoidCallback onClose;

  @override
  Widget build(BuildContext context) {
    return Stack(
      fit: StackFit.expand,
      children: [
        const Positioned.fill(child: LumiBackdrop()),
        SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(28),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: LumiCard(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        width: 46,
                        height: 46,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(14),
                          color: LumiColors.sun.withValues(alpha: 0.14),
                        ),
                        child: const Icon(
                          Icons.sentiment_dissatisfied_rounded,
                          color: LumiColors.sun,
                        ),
                      ),
                      const SizedBox(height: 16),
                      const Text(
                        'Quase lá',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w800,
                          letterSpacing: -0.4,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        message,
                        style: const TextStyle(
                          color: LumiColors.muted,
                          fontSize: 14,
                          height: 1.5,
                        ),
                      ),
                      const SizedBox(height: 20),
                      LumiButton(
                        label: 'Tentar de novo',
                        icon: Icons.refresh_rounded,
                        onPressed: onRetry,
                      ),
                      const SizedBox(height: 10),
                      TextButton(
                        onPressed: onClose,
                        child: const Text('Voltar ao início'),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }
}
