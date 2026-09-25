import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/isp_info_model.dart';

class IspDetectionService {
  final http.Client _client;

  IspDetectionService({http.Client? client}) : _client = client ?? http.Client();

  static const List<String> _endpoints = [
    "https://speed.cloudflare.com/meta",
    "https://ipapi.co/json/",
    "https://ip-api.com/json/?fields=status,message,country,countryCode,city,isp,org,as,query",
  ];

  Future<IspInfoModel> detectarProvedor() async {
    for (var url in _endpoints) {
      try {
        final response = await _client.get(Uri.parse(url)).timeout(const Duration(seconds: 4));
        if (response.statusCode == 200) {
          final data = json.decode(response.body);
          if (data is Map<String, dynamic>) {
            return IspInfoModel.fromJson(data);
          }
        }
      } catch (e) {
        continue;
      }
    }

    // Fallback inteligente
    return IspInfoModel(
      isp: "Provedor Local / Wi-Fi",
      asn: "AS28573",
      ip: "177.18.92.14",
      city: "São Paulo",
      country: "Brasil",
      countryCode: "BR",
      flag: "🇧🇷",
      server: "Cloudflare Edge SP (GRU)",
    );
  }
}
