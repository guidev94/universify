import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/proxy_model.dart';

class ProxyService {
  // Usando a API do ProxyScrape (Exemplo gratuito)
  static const String _apiUrl = "https://api.proxyscrape.com/v2/?request=displayproxies&protocol=http&timeout=10000&country=all&ssl=all&anonymity=all";

  Future<List<ProxyModel>> buscarProxies() async {
    try {
      final response = await http.get(Uri.parse(_apiUrl));

      if (response.statusCode == 200) {
        // A API ProxyScrape no modo displayproxies retorna uma lista de IP:PORT separados por newline
        final listaRaw = response.body.split('\n');
        return listaRaw
            .where((linha) => linha.contains(':'))
            .map((linha) {
              final partes = linha.trim().split(':');
              return ProxyModel(
                ip: partes[0],
                porta: partes[1],
                pais: "Desconhecido",
                protocolo: "http",
                latencia: 0,
                anonimato: "Desconhecido",
              );
            })
            .toList();
      } else {
        throw Exception("Erro ao buscar proxies: ${response.statusCode}");
      }
    } catch (e) {
      // Em produção, usar um logger adequado
      rethrow;
    }
  }
}
