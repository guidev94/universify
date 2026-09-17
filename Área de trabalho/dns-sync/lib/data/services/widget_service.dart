import 'package:home_widget/home_widget.dart';

class WidgetService {
  static const String groupName = 'sync_dns_group';
  static const String androidWidgetName = 'SyncDnsWidgetProvider';

  static Future<void> atualizarWidget({
    required bool ativo,
    required String provedor,
    required double velocidade,
  }) async {
    try {
      await HomeWidget.saveWidgetData<String>('title', 'Sync DNS');
      await HomeWidget.saveWidgetData<String>('status', ativo ? 'Protegido: $provedor' : 'Desprotegido');
      await HomeWidget.saveWidgetData<String>('speed', '${velocidade.toStringAsFixed(1)} Mbps');
      
      await HomeWidget.updateWidget(
        androidName: androidWidgetName,
      );
    } catch (e) {
      // ignore: avoid_print
      print('Erro ao atualizar widget: $e');
    }
  }
}
