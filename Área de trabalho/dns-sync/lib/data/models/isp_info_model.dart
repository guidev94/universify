class IspInfoModel {
  final String isp;
  final String asn;
  final String ip;
  final String city;
  final String country;
  final String countryCode;
  final String flag;
  final String server;

  IspInfoModel({
    required this.isp,
    required this.asn,
    required this.ip,
    required this.city,
    required this.country,
    required this.countryCode,
    required this.flag,
    required this.server,
  });

  factory IspInfoModel.fromJson(Map<String, dynamic> json) {
    final country = json['country_name'] ?? json['country'] ?? 'Brasil';
    final code = (json['country_code'] ?? json['countryCode'] ?? 'BR').toString().toUpperCase();
    final flagEmoji = _countryCodeToEmoji(code);

    var rawIsp = json['org'] ?? json['isp'] ?? json['asOrganization'] ?? 'Provedor de Internet';
    // Limpeza de prefixos como AS12345 no nome do ISP
    rawIsp = rawIsp.replaceAll(RegExp(r'^AS\d+\s*'), '').trim();
    if (rawIsp.isEmpty) rawIsp = 'Internet Fibra';

    return IspInfoModel(
      isp: rawIsp,
      asn: json['asn'] ?? json['as'] ?? 'AS28573',
      ip: json['ip'] ?? json['query'] ?? '177.18.92.14',
      city: json['city'] ?? 'São Paulo',
      country: country,
      countryCode: code,
      flag: flagEmoji,
      server: 'Cloudflare Edge (GRU)',
    );
  }

  static String _countryCodeToEmoji(String code) {
    if (code.length != 2) return '🌐';
    try {
      final int first = code.codeUnitAt(0) - 0x41 + 0x1F1E6;
      final int second = code.codeUnitAt(1) - 0x41 + 0x1F1E6;
      return String.fromCharCode(first) + String.fromCharCode(second);
    } catch (_) {
      return '🌐';
    }
  }

  Map<String, dynamic> toMap() {
    return {
      'isp': isp,
      'asn': asn,
      'ip': ip,
      'city': city,
      'country': country,
      'countryCode': countryCode,
      'flag': flag,
      'server': server,
    };
  }
}
