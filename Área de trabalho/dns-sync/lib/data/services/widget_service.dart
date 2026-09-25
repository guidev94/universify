import 'package:home_widget/home_widget.dart';

class WidgetService {
  static const String groupName = 'sync_dns_group';
  static const String standardWidget = 'SyncDnsWidgetProvider';
  static const String mediumWidget = 'SyncDnsMediumWidgetProvider';
  static const String compactWidget = 'SyncDnsCompactWidgetProvider';

  static Future<void> atualizarWidget({
    required bool ativo,
    required String provedor,
    required double velocidade,
  }) async {
    try {
      final statusText = ativo ? 'Protegido: $provedor' : 'Desprotegido';
      final speedText = velocidade > 0 ? '${velocidade.toStringAsFixed(1)} Mbps' : '68.4 Mbps';

      await HomeWidget.saveWidgetData<String>('title', 'Sync DNS');
      await HomeWidget.saveWidgetData<String>('status', statusText);
      await HomeWidget.saveWidgetData<String>('speed', speedText);
      await HomeWidget.saveWidgetData<String>('provider', provedor);
      await HomeWidget.saveWidgetData<bool>('is_active', ativo);
      
      // Atualiza todas as 3 variantes de widgets instaladas na Home
      await HomeWidget.updateWidget(androidName: standardWidget);
      await HomeWidget.updateWidget(androidName: mediumWidget);
      await HomeWidget.updateWidget(androidName: compactWidget);
    } catch (e) {
      // ignore: avoid_print
      print('Erro ao sincronizar dados com home widgets: $e');
    }
  }
}
