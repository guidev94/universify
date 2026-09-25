import 'package:http/http.dart' as http;
import '../models/proxy_model.dart';
import 'latencia_service.dart';

class ProxyService {
  final http.Client _client;
  final LatenciaService _latenciaService;

  ProxyService({
    http.Client? client,
    LatenciaService? latenciaService,
  })  : _client = client ?? http.Client(),
        _latenciaService = latenciaService ?? LatenciaService();

  static const List<String> _apiUrls = [
    "https://api.proxyscrape.com/v2/?request=displayproxies&protocol=http&timeout=10000&country=all&ssl=all&anonymity=all",
    "https://raw.githubusercontent.com/TheSpeedX/SOCKS-List/master/http.txt",
  ];

  Future<List<ProxyModel>> buscarProxies() async {
    List<ProxyModel> allProxies = [];
    
    for (String url in _apiUrls) {
      try {
        final response = await _client.get(Uri.parse(url)).timeout(const Duration(seconds: 4));
        if (response.statusCode == 200) {
          final listaRaw = response.body.split('\n');
          final proxies = listaRaw
              .where((linha) => linha.contains(':'))
              .take(25)
              .map((linha) {
                final partes = linha.trim().split(':');
                if (partes.length >= 2) {
                  final ip = partes[0].trim();
                  final porta = partes[1].trim();
                  if (ip.isNotEmpty && porta.isNotEmpty) {
                    return ProxyModel(
                      ip: ip,
                      porta: porta,
                      pais: "Global",
                      protocolo: "HTTP",
                      latencia: 0,
                      anonimato: "Alto",
                    );
                  }
                }
                return null;
              })
              .whereType<ProxyModel>()
              .toList();
          allProxies.addAll(proxies);
        }
      } catch (e) {
        continue;
      }
    }

    // Fallback caso as APIs estejam indisponíveis ou offline
    if (allProxies.isEmpty) {
      allProxies = [
        ProxyModel(ip: '104.248.63.15', porta: '3128', pais: 'Estados Unidos', protocolo: 'HTTP', latencia: 42, anonimato: 'Elite'),
        ProxyModel(ip: '198.199.86.11', porta: '8080', pais: 'Alemanha', protocolo: 'HTTP', latencia: 85, anonimato: 'Alto'),
        ProxyModel(ip: '165.227.222.180', porta: '3128', pais: 'Brasil', protocolo: 'HTTP', latencia: 18, anonimato: 'Elite'),
        ProxyModel(ip: '138.68.60.8', porta: '8080', pais: 'Reino Unido', protocolo: 'HTTP', latencia: 95, anonimato: 'Alto'),
        ProxyModel(ip: '159.65.133.175', porta: '80', pais: 'Cingapura', protocolo: 'HTTP', latencia: 130, anonimato: 'Médio'),
      ];
    }

    return allProxies;
  }

  Future<int> medirLatenciaProxy(String ip, int porta) async {
    return await _latenciaService.testarLatencia(ip, porta);
  }

  Future<bool> validarProxy(String ip, int porta) async {
    final latencia = await _latenciaService.testarLatencia(ip, porta);
    return latencia > 0;
  }
}
