import 'dart:convert';
import 'package:http/http.dart' as http;

class DnsLeakService {
  static const String _ipUrl = "https://ipapi.co/json/";

  Future<Map<String, dynamic>> obterInfoConexao() async {
    try {
      final response = await http.get(Uri.parse(_ipUrl));
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        throw Exception("Falha ao obter info de conexão");
      }
    } catch (e) {
      return {'error': e.toString()};
    }
  }
}
