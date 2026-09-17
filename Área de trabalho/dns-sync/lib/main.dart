import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:firebase_core/firebase_core.dart';
import 'core/theme/app_styles.dart';
import 'presentation/screens/home_screen.dart';
import 'presentation/providers/proxy_provider.dart';
import 'data/services/notificacao_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Inicialização de serviços (opcional)
  try {
    // await NotificacaoService().inicializar();
  } catch (e) {
    debugPrint("Erro ao inicializar serviços: $e");
  }

  // Inicialização do app com ProviderScope para o Riverpod
  runApp(
    const ProviderScope(
      child: DnsSyncApp(),
    ),
  );
}

class DnsSyncApp extends ConsumerWidget {
  const DnsSyncApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final modoTema = ref.watch(temaProvider);

    return MaterialApp(
      title: 'DNS Sync',
      debugShowCheckedModeBanner: false,
      theme: AppEstilos.temaClaro,
      darkTheme: AppEstilos.temaEscuro,
      themeMode: modoTema,
      home: const HomeScreen(),
    );
  }
}
