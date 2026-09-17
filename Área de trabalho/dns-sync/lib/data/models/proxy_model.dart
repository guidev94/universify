class ProxyModel {
  final String ip;
  final String porta;
  final String pais;
  final String protocolo;
  final int latencia;
  final String anonimato;

  ProxyModel({
    required this.ip,
    required this.porta,
    required this.pais,
    required this.protocolo,
    required this.latencia,
    required this.anonimato,
  });

  factory ProxyModel.fromJson(Map<String, dynamic> json) {
    return ProxyModel(
      ip: json['ip'] ?? '',
      porta: json['port']?.toString() ?? '',
      pais: json['country'] ?? 'Desconhecido',
      protocolo: json['protocol'] ?? 'http',
      latencia: json['latency'] ?? 0,
      anonimato: json['anonymity'] ?? 'baixa',
    );
  }
}
