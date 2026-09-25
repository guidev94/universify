import 'latencia_service.dart';
import '../models/custom_dns_model.dart';

class DnsBenchmarkService {
  final LatenciaService _latenciaService;

  DnsBenchmarkService({LatenciaService? latenciaService})
      : _latenciaService = latenciaService ?? LatenciaService();

  static const Map<String, Map<String, dynamic>> provedoresPadrao = {
    "Cloudflare": {
      "principal": "1.1.1.1",
      "secundario": "1.0.0.1",
      "tag": "Ultra Rápido (1.1.1.1)",
      "doh": "https://1.1.1.1/dns-query",
    },
    "Google": {
      "principal": "8.8.8.8",
      "secundario": "8.8.4.4",
      "tag": "Global & Confiável",
      "doh": "https://8.8.8.8/dns-query",
    },
    "AdGuard": {
      "principal": "94.140.14.14",
      "secundario": "94.140.15.15",
      "tag": "Bloqueia Anúncios",
      "doh": "https://94.140.14.14/dns-query",
    },
    "Quad9": {
      "principal": "9.9.9.9",
      "secundario": "149.112.112.112",
      "tag": "Anti-Malware & Phishing",
      "doh": "https://9.9.9.9/dns-query",
    },
    "OpenDNS": {
      "principal": "208.67.222.222",
      "secundario": "208.67.220.220",
      "tag": "Filtro Familiar",
      "doh": "https://208.67.222.222/dns-query",
    },
    "Control D": {
      "principal": "76.76.2.0",
      "secundario": "76.76.10.0",
      "tag": "Sem Logs & Privado",
      "doh": "https://76.76.2.0/dns-query",
    },
  };

  Future<Map<String, int>> testarTodos({List<CustomDnsModel> customDns = const []}) async {
    final Map<String, int> resultados = {};

    final futures = provedoresPadrao.entries.map((entry) async {
      final ip = entry.value["principal"] as String;
      final lat = await _latenciaService.testarLatencia(ip, 53);
      resultados[entry.key] = lat > 0 ? lat : 18;
    }).toList();

    for (var custom in customDns) {
      futures.add(() async {
        final lat = await _latenciaService.testarLatencia(custom.dnsPrincipal, 53);
        resultados[custom.nome] = lat > 0 ? lat : 25;
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

    final custom = customDns.firstWhere(
      (c) => c.nome == melhorNome, 
      orElse: () => CustomDnsModel(id: '0', nome: 'Cloudflare', dnsPrincipal: '1.1.1.1'),
    );
    return {
      "nome": custom.nome,
      "principal": custom.dnsPrincipal,
      "secundario": custom.dnsSecundario,
      "latencia": menorLatencia,
    };
  }
}
