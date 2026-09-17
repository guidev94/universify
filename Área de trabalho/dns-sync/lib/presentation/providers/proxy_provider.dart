import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter/material.dart';
import '../../data/models/proxy_model.dart';
import '../../data/services/proxy_service.dart';
import '../../data/services/dns_leak_service.dart';
import '../../data/services/latencia_service.dart';

// Providers de serviços
final proxyServiceProvider = Provider((ref) => ProxyService());
final dnsLeakServiceProvider = Provider((ref) => DnsLeakService());
final latenciaServiceProvider = Provider((ref) => LatenciaService());

// FutureProvider para buscar a lista de proxies
final listaProxyProvider = FutureProvider<List<dynamic>>((ref) async {
  // Tentamos buscar da API, se falhar ou retornar vazio, usamos o placeholder
  try {
    final service = ref.watch(proxyServiceProvider);
    final proxiesRaw = await service.buscarProxies();
    
    if (proxiesRaw.isEmpty) {
      return [
        {'ip': '192.168.1.1', 'porta': '8080', 'pais': 'Brasil'},
        {'ip': '1.1.1.1', 'porta': '3128', 'pais': 'EUA'},
        {'ip': '45.77.12.34', 'porta': '80', 'pais': 'Japão'},
      ];
    }
    
    // Converte ProxyModel para Map para manter compatibilidade com a UI atual
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
