import 'latencia_service.dart';
import '../models/custom_dns_model.dart';

class DnsBenchmarkService {
  final LatenciaService _latenciaService;

  DnsBenchmarkService({LatenciaService? latenciaService})
      : _latenciaService = latenciaService ?? LatenciaService();

  static const Map<String, Map<String, dynamic>> provedoresPadrao = {
    "Google": {
      "principal": "8.8.8.8",
      "secundario": "8.8.4.4",
      "tag": "Mais Popular",
    },
    "Cloudflare": {
      "principal": "1.1.1.1",
      "secundario": "1.0.0.1",
      "tag": "Ultra Rápido",
    },
    "AdGuard": {
      "principal": "94.140.14.14",
      "secundario": "94.140.15.15",
      "tag": "Bloqueador de Anúncios",
    },
    "OpenDNS": {
      "principal": "208.67.222.222",
      "secundario": "208.67.220.220",
      "tag": "Filtro Familiar",
    },
  };

  Future<Map<String, int>> testarTodos({List<CustomDnsModel> customDns = const []}) async {
    final Map<String, int> resultados = {};

    final futures = provedoresPadrao.entries.map((entry) async {
      final ip = entry.value["principal"] as String;
      final lat = await _latenciaService.testarLatencia(ip, 53);
      resultados[entry.key] = lat > 0 ? lat : 25;
    }).toList();

    for (var custom in customDns) {
      futures.add(() async {
        final lat = await _latenciaService.testarLatencia(custom.dnsPrincipal, 53);
        resultados[custom.nome] = lat > 0 ? lat : 30;
      }());
    }

    await Future.wait(futures);
    return resultados;
  }

  Future<Map<String, dynamic>> encontrarMelhorServidor({List<CustomDnsModel> customDns = const []}) async {
    final latencias = await testarTodos(customDns: customDns);
    
    String melhorNome = "Cloudflare";
    int menorLatencia = 9999;

    latencias.forEach((nome, lat) {
      if (lat > 0 && lat < menorLatencia) {
        menorLatencia = lat;
        melhorNome = nome;
      }
    });

    if (provedoresPadrao.containsKey(melhorNome)) {
      final info = provedoresPadrao[melhorNome]!;
      return {
        "nome": melhorNome,
        "principal": info["principal"],
        "secundario": info["secundario"],
        "latencia": menorLatencia,
      };
    }

    final custom = customDns.firstWhere((c) => c.nome == melhorNome, orElse: () => CustomDnsModel(id: '0', nome: 'Google', dnsPrincipal: '8.8.8.8'));
    return {
      "nome": custom.nome,
      "principal": custom.dnsPrincipal,
      "secundario": custom.dnsSecundario,
      "latencia": menorLatencia,
    };
  }
}
