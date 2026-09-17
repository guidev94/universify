import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter/material.dart';
import '../../data/models/proxy_model.dart';
import '../../data/services/proxy_service.dart';
import '../../data/services/dns_leak_service.dart';
import '../../data/services/latencia_service.dart';
import 'package:shared_preferences/shared_preferences.dart';

// Providers de serviços
final proxyServiceProvider = Provider((ref) => ProxyService());
final dnsLeakServiceProvider = Provider((ref) => DnsLeakService());
final latenciaServiceProvider = Provider((ref) => LatenciaService());

// FutureProvider para buscar a lista de proxies
final listaProxyProvider = FutureProvider<List<dynamic>>((ref) async {
  try {
    final service = ref.watch(proxyServiceProvider);
    final proxiesRaw = await service.buscarProxies();
    
    if (proxiesRaw.isEmpty) {
      return [
        {'ip': '192.168.1.1', 'porta': '8080', 'pais': 'Brasil'},
        {'ip': '1.1.1.1', 'porta': '3128', 'pais': 'EUA'},
      ];
    }
    
    return proxiesRaw.map((p) => {
      'ip': p.ip,
      'porta': p.porta,
      'pais': p.pais,
    }).toList();
  } catch (e) {
    return [
      {'ip': '192.168.1.1', 'porta': '8080', 'pais': 'Brasil'},
      {'ip': '1.1.1.1', 'porta': '3128', 'pais': 'EUA'},
    ];
  }
});

// Info de Conexão (Leak Test)
final conexaoInfoProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  return await ref.watch(dnsLeakServiceProvider).obterInfoConexao();
});

// Providers de estado
final dnsStatusProvider = StateProvider<bool>((ref) => false);
final provedorSelecionadoProvider = StateProvider<String>((ref) => "Nenhum");
final temaProvider = StateProvider<ThemeMode>((ref) => ThemeMode.light);

// Favoritos
final favoritosProvider = StateNotifierProvider<FavoritosNotifier, List<String>>((ref) {
  return FavoritosNotifier();
});

class FavoritosNotifier extends StateNotifier<List<String>> {
  FavoritosNotifier() : super([]) {
    _load();
  }

  Future<void> _load() async {
    final prefs = await SharedPreferences.getInstance();
    state = prefs.getStringList('favoritos') ?? [];
  }

  Future<void> toggle(String ip) async {
    if (state.contains(ip)) {
      state = state.where((item) => item != ip).toList();
    } else {
      state = [...state, ip];
    }
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList('favoritos', state);
  }
}

// Speed Test State
final speedTestProvider = StateNotifierProvider<SpeedTestNotifier, SpeedTestState>((ref) {
  return SpeedTestNotifier(ref.watch(latenciaServiceProvider));
});

class SpeedTestState {
  final bool isRunning;
  final int latency;
  final double downloadSpeed;

  SpeedTestState({this.isRunning = false, this.latency = 0, this.downloadSpeed = 0.0});

  SpeedTestState copyWith({bool? isRunning, int? latency, double? downloadSpeed}) {
    return SpeedTestState(
      isRunning: isRunning ?? this.isRunning,
      latency: latency ?? this.latency,
      downloadSpeed: downloadSpeed ?? this.downloadSpeed,
    );
  }
}

class SpeedTestNotifier extends StateNotifier<SpeedTestState> {
  final LatenciaService _latenciaService;
  SpeedTestNotifier(this._latenciaService) : super(SpeedTestState());

  Future<void> runTest() async {
    state = state.copyWith(isRunning: true, latency: 0, downloadSpeed: 0.0);
    
    // Teste de Latência Real (Google)
    final ping = await _latenciaService.testarLatencia('8.8.8.8', 53);
    state = state.copyWith(latency: ping);
    
    // Simulação de Speed Test (UI UX)
    for (int i = 0; i < 20; i++) {
      await Future.delayed(const Duration(milliseconds: 100));
      state = state.copyWith(downloadSpeed: 10.0 + (i * 2.5));
    }
    
    state = state.copyWith(isRunning: false, downloadSpeed: 64.5);
  }
}
