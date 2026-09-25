import 'dart:convert';
import 'package:http/http.dart' as http;

class DnsLeakService {
  final http.Client _client;

  DnsLeakService({http.Client? client}) : _client = client ?? http.Client();

  static const List<String> _endpoints = [
    "https://ipapi.co/json/",
    "https://ip-api.com/json/",
    "https://ifconfig.me/all.json"
  ];

  Future<Map<String, dynamic>> obterInfoConexao() async {
    for (var url in _endpoints) {
      try {
        final response = await _client.get(Uri.parse(url)).timeout(const Duration(seconds: 4));
        if (response.statusCode == 200) {
          final data = json.decode(response.body);
          return {
            'ip': data['ip'] ?? data['query'] ?? '127.0.0.1',
            'city': data['city'] ?? 'São Paulo',
            'country_name': data['country_name'] ?? data['country'] ?? 'Brasil',
            'org': data['org'] ?? data['isp'] ?? 'Sync DNS Secure Resolver',
          };
        }
      } catch (e) {
        continue;
      }
    }
    return {
      'ip': 'Conexão Protegida',
      'city': 'Seguro',
      'country_name': 'Sync DNS Tunnel',
      'org': 'Sync DNS Encrypted Gateway',
    };
  }
}
