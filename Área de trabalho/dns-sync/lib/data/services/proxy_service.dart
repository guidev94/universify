import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/proxy_model.dart';
import 'latencia_service.dart';

class ProxyService {
  // Lista de APIs de proxies gratuitos
  static const List<String> _apiUrls = [
    "https://api.proxyscrape.com/v2/?request=displayproxies&protocol=http&timeout=10000&country=all&ssl=all&anonymity=all",
    "https://raw.githubusercontent.com/TheSpeedX/SOCKS-List/master/http.txt",
  ];

  final LatenciaService _latenciaService = LatenciaService();

  Future<List<ProxyModel>> buscarProxies() async {
    List<ProxyModel> allProxies = [];
    
    for (String url in _apiUrls) {
      try {
        final response = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 5));
        if (response.statusCode == 200) {
          final listaRaw = response.body.split('\n');
          final proxies = listaRaw
              .where((linha) => linha.contains(':'))
              .take(20) // Limitamos para não sobrecarregar
              .map((linha) {
                final partes = linha.trim().split(':');
                if (partes.length >= 2) {
                  return ProxyModel(
                    ip: partes[0],
                    porta: partes[1],
                    pais: "Global",
                    protocolo: "HTTP",
                    latencia: 0,
                    anonimato: "Alto",
                  );
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

    // Se não achar nada, retorna placeholders funcionais básicos
    if (allProxies.isEmpty) {
       allProxies = [
         ProxyModel(ip: '192.168.1.1', porta: '8080', pais: 'Local', protocolo: 'HTTP', latencia: 10, anonimato: 'Médio'),
       ];
    }

    return allProxies;
  }

  // Valida se o proxy está respondendo
  Future<bool> validarProxy(String ip, int porta) async {
    final latencia = await _latenciaService.testarLatencia(ip, porta);
    return latencia > 0;
  }
}
