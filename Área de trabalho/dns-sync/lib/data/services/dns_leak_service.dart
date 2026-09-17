import 'dart:convert';
import 'package:http/http.dart' as http;

class DnsLeakService {
  // Diversas APIs para fallback
  static const List<String> _endpoints = [
    "https://ipapi.co/json/",
    "https://ip-api.com/json/",
    "https://ifconfig.me/all.json"
  ];

  Future<Map<String, dynamic>> obterInfoConexao() async {
    for (var url in _endpoints) {
      try {
        final response = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 5));
        if (response.statusCode == 200) {
          final data = json.decode(response.body);
          // Normalização básica de campos entre APIs diferentes
          return {
            'ip': data['ip'] ?? data['query'] ?? 'Desconhecido',
            'city': data['city'] ?? 'Localização',
            'country_name': data['country_name'] ?? data['country'] ?? 'Desconhecida',
          };
        }
      } catch (e) {
        continue; // Tenta o próximo se falhar
      }
    }
    return {'ip': 'Offline', 'city': 'Sem conexão', 'country_name': ''};
  }
}
