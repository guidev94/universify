import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter/foundation.dart';

class NotificacaoService {
  static final NotificacaoService _instance = NotificacaoService._internal();
  factory NotificacaoService() => _instance;
  NotificacaoService._internal();

  final FlutterLocalNotificationsPlugin _notificationsPlugin =
      FlutterLocalNotificationsPlugin();

  bool _initialized = false;

  Future<void> inicializar() async {
    if (_initialized) return;

    const AndroidInitializationSettings androidSettings =
        AndroidInitializationSettings('@mipmap/ic_launcher');

    const DarwinInitializationSettings iosSettings =
        DarwinInitializationSettings(
      requestAlertPermission: true,
      requestBadgePermission: true,
      requestSoundPermission: true,
    );

    const InitializationSettings initSettings = InitializationSettings(
      android: androidSettings,
      iOS: iosSettings,
    );

    try {
      await _notificationsPlugin.initialize(
        settings: initSettings,
        onDidReceiveNotificationResponse: (NotificationResponse response) {
          debugPrint('Notificação clicada: ${response.payload}');
        },
      );

      // Solicitar permissão no Android 13+
      await _notificationsPlugin
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>()
          ?.requestNotificationsPermission();

      _initialized = true;
    } catch (e) {
      debugPrint('Erro ao inicializar notificações: $e');
    }
  }

  Future<void> mostrarNotificacaoConectado(String provedor, String dns) async {
    const AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
      'sync_dns_status_channel',
      'Status de Conexão DNS',
      channelDescription: 'Alertas visuais de conexão do Sync DNS',
      importance: Importance.high,
      priority: Priority.high,
      icon: '@mipmap/ic_launcher',
      showWhen: true,
    );

    const NotificationDetails details = NotificationDetails(
      android: androidDetails,
      iOS: DarwinNotificationDetails(),
    );

    try {
      await _notificationsPlugin.show(
        id: 1,
        title: 'Sync DNS: Proteção Ativa 🛡️',
        body: 'Conectado a $provedor ($dns) com sucesso.',
        notificationDetails: details,
        payload: 'dns_connected',
      );
    } catch (e) {
      debugPrint('Erro ao exibir notificação: $e');
    }
  }

  Future<void> mostrarNotificacaoProxy(String ip, int porta, String pais) async {
    const AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
      'sync_dns_proxy_channel',
      'Status de Proxy',
      channelDescription: 'Alertas de conexão com servidores proxy',
      importance: Importance.high,
      priority: Priority.high,
      icon: '@mipmap/ic_launcher',
      showWhen: true,
    );

    const NotificationDetails details = NotificationDetails(
      android: androidDetails,
      iOS: DarwinNotificationDetails(),
    );

    try {
      await _notificationsPlugin.show(
        id: 2,
        title: 'Proxy Conectado: $pais 🌐',
        body: 'Roteando tráfego via $ip:$porta com sucesso.',
        notificationDetails: details,
        payload: 'proxy_connected',
      );
    } catch (e) {
      debugPrint('Erro ao exibir notificação de proxy: $e');
    }
  }

  Future<void> mostrarNotificacaoDesconectado() async {
    const AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
      'sync_dns_status_channel',
      'Status de Conexão DNS',
      channelDescription: 'Alertas visuais de conexão do Sync DNS',
      importance: Importance.defaultImportance,
      priority: Priority.defaultPriority,
      icon: '@mipmap/ic_launcher',
      showWhen: true,
    );

    const NotificationDetails details = NotificationDetails(
      android: androidDetails,
      iOS: DarwinNotificationDetails(),
    );

    try {
      await _notificationsPlugin.show(
        id: 3,
        title: 'Sync DNS: Desconectado',
        body: 'Sua conexão retornou às configurações padrão do dispositivo.',
        notificationDetails: details,
        payload: 'disconnected',
      );
    } catch (e) {
      debugPrint('Erro ao exibir notificação de desconexão: $e');
    }
  }

  Future<void> cancelarTodas() async {
    try {
      await _notificationsPlugin.cancelAll();
    } catch (e) {
      debugPrint('Erro ao cancelar notificações: $e');
    }
  }
}
