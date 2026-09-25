import 'dart:convert';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter/material.dart';
import '../../data/models/proxy_model.dart';
import '../../data/models/custom_dns_model.dart';
import '../../data/models/isp_info_model.dart';
import '../../data/services/proxy_service.dart';
import '../../data/services/dns_leak_service.dart';
import '../../data/services/latencia_service.dart';
import '../../data/services/dns_benchmark_service.dart';
import '../../data/services/notificacao_service.dart';
import '../../data/services/real_speed_test_service.dart';
import '../../data/services/isp_detection_service.dart';
import '../../data/services/video_streaming_test_service.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:async';

// Providers de serviços
final proxyServiceProvider = Provider((ref) => ProxyService());
final dnsLeakServiceProvider = Provider((ref) => DnsLeakService());
final latenciaServiceProvider = Provider((ref) => LatenciaService());
final realSpeedTestServiceProvider = Provider((ref) => RealSpeedTestService());
final ispDetectionServiceProvider = Provider((ref) => IspDetectionService());
final videoStreamingTestServiceProvider = Provider((ref) => VideoStreamingTestService());
final dnsBenchmarkServiceProvider = Provider((ref) => DnsBenchmarkService(
      latenciaService: ref.watch(latenciaServiceProvider),
    ));
final notificacaoServiceProvider = Provider((ref) => NotificacaoService());

// Identificação Inteligente do Provedor Real (ISP, ASN, Cidade, Bandeira)
final ispInfoProvider = FutureProvider<IspInfoModel>((ref) async {
  final service = ref.watch(ispDetectionServiceProvider);
  return await service.detectarProvedor();
});

// Lista de proxies disponíveis
final listaProxyProvider = FutureProvider<List<ProxyModel>>((ref) async {
  final service = ref.watch(proxyServiceProvider);
  return await service.buscarProxies();
});

// Benchmark de Latências em Tempo Real para todos os servidores
final benchmarkLatenciasProvider = FutureProvider<Map<String, int>>((ref) async {
  final service = ref.watch(dnsBenchmarkServiceProvider);
  final customList = ref.watch(customDnsListProvider);
  return await service.testarTodos(customDns: customList);
});

// Info de Conexão (DNS Leak Test)
final conexaoInfoProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  return await ref.watch(dnsLeakServiceProvider).obterInfoConexao();
});

// Filtros de busca de Proxy
final buscaProxyProvider = StateProvider<String>((ref) => '');
final paisFiltroProvider = StateProvider<String>((ref) => 'Todos');

// Proxies filtrados
final proxiesFiltradosProvider = Provider<AsyncValue<List<ProxyModel>>>((ref) {
  final proxiesAsync = ref.watch(listaProxyProvider);
  final query = ref.watch(buscaProxyProvider).toLowerCase().trim();
  final pais = ref.watch(paisFiltroProvider);

  return proxiesAsync.whenData((lista) {
    return lista.where((proxy) {
      final matchQuery = query.isEmpty ||
          proxy.ip.contains(query) ||
          proxy.porta.contains(query) ||
          proxy.pais.toLowerCase().contains(query);
      final matchPais = pais == 'Todos' || proxy.pais == pais;
      return matchQuery && matchPais;
    }).toList();
  });
});

// Providers de estado de conexão
final dnsStatusProvider = StateProvider<bool>((ref) => false);
final provedorSelecionadoProvider = StateProvider<String>((ref) => "Nenhum");
final conexaoTipoProvider = StateProvider<String>((ref) => "nenhum"); // 'dns', 'proxy', 'nenhum'
final proxyAtivoProvider = StateProvider<ProxyModel?>((ref) => null);
final latenciaAtualProvider = StateProvider<int>((ref) => 18);
final temaProvider = StateProvider<ThemeMode>((ref) => ThemeMode.light);

// Contadores de Segurança e Filtros Ativos
final anunciosBloqueadosProvider = StateProvider<int>((ref) => 0);
final dohAtivadoProvider = StateProvider<bool>((ref) => false);
final adBlockAtivadoProvider = StateProvider<bool>((ref) => true);

// Split Tunneling Notifier (Aplicativos em bypass)
final splitTunnelProvider =
    StateNotifierProvider<SplitTunnelNotifier, List<String>>((ref) {
  return SplitTunnelNotifier();
});

class SplitTunnelNotifier extends StateNotifier<List<String>> {
  SplitTunnelNotifier() : super([]) {
    _load();
  }

  Future<void> _load() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      state = prefs.getStringList('split_tunnel_packages') ?? [
        'com.android.vending',
        'com.google.android.youtube',
        'com.netflix.mediaclient',
      ];
    } catch (_) {}
  }

  Future<void> toggleApp(String packageName) async {
    if (state.contains(packageName)) {
      state = state.where((p) => p != packageName).toList();
    } else {
      state = [...state, packageName];
    }
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setStringList('split_tunnel_packages', state);
    } catch (_) {}
  }
}

// Configurações do App (Preferências persistidas)
final configuracoesProvider =
    StateNotifierProvider<ConfiguracoesNotifier, Map<String, bool>>((ref) {
  return ConfiguracoesNotifier();
});

class ConfiguracoesNotifier extends StateNotifier<Map<String, bool>> {
  ConfiguracoesNotifier()
      : super({
          'autoConnect': false,
          'smartDns': true,
          'ipv6Shield': true,
          'notificacoes': true,
          'useDoh': false,
          'enableAdBlock': true,
        }) {
    _load();
  }

  Future<void> _load() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      state = {
        'autoConnect': prefs.getBool('cfg_autoConnect') ?? false,
        'smartDns': prefs.getBool('cfg_smartDns') ?? true,
        'ipv6Shield': prefs.getBool('cfg_ipv6Shield') ?? true,
        'notificacoes': prefs.getBool('cfg_notificacoes') ?? true,
        'useDoh': prefs.getBool('cfg_useDoh') ?? false,
        'enableAdBlock': prefs.getBool('cfg_enableAdBlock') ?? true,
      };
    } catch (_) {}
  }

  Future<void> toggle(String chave) async {
    final novoValor = !(state[chave] ?? false);
    state = {...state, chave: novoValor};
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setBool('cfg_$chave', novoValor);
    } catch (_) {}
  }
}

// Custom DNS List Notifier (CRUD Completo)
final customDnsListProvider =
    StateNotifierProvider<CustomDnsNotifier, List<CustomDnsModel>>((ref) {
  return CustomDnsNotifier();
});

class CustomDnsNotifier extends StateNotifier<List<CustomDnsModel>> {
  CustomDnsNotifier() : super([]) {
    _load();
  }

  Future<void> _load() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final listRaw = prefs.getStringList('custom_dns_list');
      if (listRaw != null && listRaw.isNotEmpty) {
        state = listRaw
            .map((item) => CustomDnsModel.fromJson(json.decode(item)))
            .toList();
      } else {
        state = [
          CustomDnsModel(
            id: 'quad9',
            nome: 'Quad9 Security',
            dnsPrincipal: '9.9.9.9',
            dnsSecundario: '149.112.112.112',
          ),
          CustomDnsModel(
            id: 'nextdns',
            nome: 'NextDNS',
            dnsPrincipal: '45.90.28.0',
            dnsSecundario: '45.90.30.0',
          ),
        ];
        await _save();
      }
    } catch (_) {}
  }

  Future<void> _save() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final listRaw = state.map((c) => json.encode(c.toJson())).toList();
      await prefs.setStringList('custom_dns_list', listRaw);
    } catch (_) {}
  }

  Future<void> adicionar(String nome, String dns1, String dns2) async {
    final novo = CustomDnsModel(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      nome: nome.trim(),
      dnsPrincipal: dns1.trim(),
      dnsSecundario: dns2.trim(),
    );
    state = [...state, novo];
    await _save();
  }

  Future<void> remover(String id) async {
    state = state.where((item) => item.id != id).toList();
    await _save();
  }
}

// Timer de Conexão Ativa
final tempoConexaoProvider = StateNotifierProvider<TempoConexaoNotifier, int>((ref) {
  return TempoConexaoNotifier();
});

class TempoConexaoNotifier extends StateNotifier<int> {
  Timer? _timer;

  TempoConexaoNotifier() : super(0);

  void iniciar() {
    _timer?.cancel();
    state = 0;
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      state = state + 1;
    });
  }

  void parar() {
    _timer?.cancel();
    _timer = null;
    state = 0;
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }
}

// Favoritos
final favoritosProvider = StateNotifierProvider<FavoritosNotifier, List<String>>((ref) {
  return FavoritosNotifier();
});

class FavoritosNotifier extends StateNotifier<List<String>> {
  FavoritosNotifier() : super([]) {
    _load();
  }

  Future<void> _load() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      state = prefs.getStringList('favoritos') ?? [];
    } catch (_) {}
  }

  Future<void> toggle(String ip) async {
    if (state.contains(ip)) {
      state = state.where((item) => item != ip).toList();
    } else {
      state = [...state, ip];
    }
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setStringList('favoritos', state);
    } catch (_) {}
  }
}

// Speed Test State com Métricas Reais
final speedTestProvider =
    StateNotifierProvider<SpeedTestNotifier, SpeedTestMetrics>((ref) {
  return SpeedTestNotifier(ref.watch(realSpeedTestServiceProvider));
});

class SpeedTestNotifier extends StateNotifier<SpeedTestMetrics> {
  final RealSpeedTestService _service;

  SpeedTestNotifier(this._service) : super(SpeedTestMetrics());

  Future<void> runTest() async {
    state = SpeedTestMetrics(
      stage: 'ping',
      progress: 0.1,
      speedHistory: [],
    );

    final pingResult = await _service.testarPingEJitter();
    final ping = pingResult['ping'] ?? 16;
    final jitter = pingResult['jitter'] ?? 2;
    final loss = pingResult['loss'] ?? 0;
    final grade = jitter <= 3 ? 'A+' : (jitter <= 8 ? 'A' : 'B');

    state = state.copyWith(
      ping: ping,
      jitter: jitter,
      packetLoss: loss,
      bufferbloatGrade: grade,
      stage: 'download',
      progress: 0.25,
    );

    final downloadResult = await _service.testarDownloadReal(
      onProgress: (instantMbps, progressRatio, history) {
        state = state.copyWith(
          currentSpeed: instantMbps,
          downloadSpeed: instantMbps,
          progress: 0.25 + (progressRatio * 0.5),
          speedHistory: history,
        );
      },
    );

    state = state.copyWith(
      downloadSpeed: downloadResult,
      currentSpeed: downloadResult,
      stage: 'upload',
      progress: 0.75,
    );

    final uploadResult = await _service.testarUploadReal(
      onProgress: (instantMbps, progressRatio) {
        state = state.copyWith(
          currentSpeed: instantMbps,
          uploadSpeed: instantMbps,
          progress: 0.75 + (progressRatio * 0.25),
        );
      },
    );

    state = state.copyWith(
      uploadSpeed: uploadResult,
      currentSpeed: downloadResult,
      progress: 1.0,
      stage: 'complete',
    );
  }
}

// Video Streaming Test State
final videoTestProvider =
    StateNotifierProvider<VideoTestNotifier, VideoTestMetrics>((ref) {
  return VideoTestNotifier(ref.watch(videoStreamingTestServiceProvider));
});

class VideoTestNotifier extends StateNotifier<VideoTestMetrics> {
  final VideoStreamingTestService _service;

  VideoTestNotifier(this._service) : super(VideoTestMetrics());

  Future<void> runVideoTest() async {
    state = VideoTestMetrics(stage: 'initiating', progress: 0.1);

    await _service.executarTesteVideo(
      onProgress: (current) {
        state = current;
      },
    );
  }
}
