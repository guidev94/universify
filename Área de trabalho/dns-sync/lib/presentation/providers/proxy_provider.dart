import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter/material.dart';
import '../../data/models/proxy_model.dart';
import '../../data/services/proxy_service.dart';
import '../../data/services/dns_leak_service.dart';
import '../../data/services/latencia_service.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:async';
import 'dart:math';

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

// Speed Test State Avançado
class SpeedTestState {
  final bool isRunning;
  final int latency;
  final int jitter;
  final double downloadSpeed;
  final double uploadSpeed;
  final double progress; // 0.0 a 1.0
  final String stage; // 'ping', 'download', 'upload', 'idle'

  SpeedTestState({
    this.isRunning = false, 
    this.latency = 0, 
    this.jitter = 0,
    this.downloadSpeed = 0.0, 
    this.uploadSpeed = 0.0,
    this.progress = 0.0,
    this.stage = 'idle',
  });

  SpeedTestState copyWith({
    bool? isRunning, 
    int? latency, 
    int? jitter,
    double? downloadSpeed, 
    double? uploadSpeed,
    double? progress,
    String? stage,
  }) {
    return SpeedTestState(
      isRunning: isRunning ?? this.isRunning,
      latency: latency ?? this.latency,
      jitter: jitter ?? this.jitter,
      downloadSpeed: downloadSpeed ?? this.downloadSpeed,
      uploadSpeed: uploadSpeed ?? this.uploadSpeed,
      progress: progress ?? this.progress,
      stage: stage ?? this.stage,
    );
  }
}

final speedTestProvider = StateNotifierProvider<SpeedTestNotifier, SpeedTestState>((ref) {
  return SpeedTestNotifier(ref.watch(latenciaServiceProvider));
});

class SpeedTestNotifier extends StateNotifier<SpeedTestState> {
  final LatenciaService _latenciaService;
  SpeedTestNotifier(this._latenciaService) : super(SpeedTestState());

  Future<void> runTest() async {
    state = SpeedTestState(isRunning: true, stage: 'ping');
    final random = Random();

    // 1. Teste de Latência e Jitter
    int totalPing = 0;
    List<int> pings = [];
    for (int i = 0; i < 5; i++) {
      final p = await _latenciaService.testarLatencia('8.8.8.8', 53);
      if (p > 0) {
        pings.add(p);
        totalPing += p;
      }
      state = state.copyWith(progress: (i + 1) / 30);
      await Future.delayed(const Duration(milliseconds: 100));
    }
    
    int avgPing = pings.isEmpty ? 0 : (totalPing / pings.length).round();
    int jitter = pings.length < 2 ? 0 : (pings.last - pings.first).abs();
    state = state.copyWith(latency: avgPing, jitter: jitter, stage: 'download');

    // 2. Simulação de Download
    double finalDownload = 40.0 + random.nextDouble() * 60.0;
    for (int i = 0; i < 15; i++) {
      double current = (finalDownload / 15) * (i + 1) + (random.nextDouble() * 5);
      state = state.copyWith(
        downloadSpeed: current,
        progress: (5 + i + 1) / 30,
      );
      await Future.delayed(const Duration(milliseconds: 150));
    }
    state = state.copyWith(downloadSpeed: finalDownload, stage: 'upload');

    // 3. Simulação de Upload
    double finalUpload = 15.0 + random.nextDouble() * 25.0;
    for (int i = 0; i < 10; i++) {
      double current = (finalUpload / 10) * (i + 1) + (random.nextDouble() * 3);
      state = state.copyWith(
        uploadSpeed: current,
        progress: (20 + i + 1) / 30,
      );
      await Future.delayed(const Duration(milliseconds: 200));
    }

    state = state.copyWith(
      isRunning: false, 
      uploadSpeed: finalUpload, 
      progress: 1.0, 
      stage: 'idle'
    );
  }
}
