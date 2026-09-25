class ProxyModel {
  final String ip;
  final String porta;
  final String pais;
  final String protocolo;
  final int latencia;
  final String anonimato;
  final String? usuario;
  final String? senha;

  ProxyModel({
    required this.ip,
    required this.porta,
    required this.pais,
    required this.protocolo,
    required this.latencia,
    required this.anonimato,
    this.usuario,
    this.senha,
  });

  String get enderecoFormatado => '$ip:$porta';
  int get portaInt => int.tryParse(porta) ?? 8080;
  bool get isAutenticado => usuario != null && usuario!.isNotEmpty && senha != null && senha!.isNotEmpty;

  factory ProxyModel.fromJson(Map<String, dynamic> json) {
    return ProxyModel(
      ip: json['ip'] ?? '',
      porta: json['porta']?.toString() ?? json['port']?.toString() ?? '8080',
      pais: json['pais'] ?? json['country'] ?? 'Global',
      protocolo: (json['protocolo'] ?? json['protocol'] ?? 'HTTP').toString().toUpperCase(),
      latencia: json['latencia'] ?? json['latency'] ?? 0,
      anonimato: json['anonimato'] ?? json['anonymity'] ?? 'Alto',
      usuario: json['usuario'] as String?,
      senha: json['senha'] as String?,
    );
  }

  Map<String, dynamic> toMap() {
    return {
      'ip': ip,
      'porta': porta,
      'pais': pais,
      'protocolo': protocolo,
      'latencia': latencia,
      'anonimato': anonimato,
      'usuario': usuario,
      'senha': senha,
    };
  }

  ProxyModel copyWith({
    String? ip,
    String? porta,
    String? pais,
    String? protocolo,
    int? latencia,
    String? anonimato,
    String? usuario,
    String? senha,
  }) {
    return ProxyModel(
      ip: ip ?? this.ip,
      porta: porta ?? this.porta,
      pais: pais ?? this.pais,
      protocolo: protocolo ?? this.protocolo,
      latencia: latencia ?? this.latencia,
      anonimato: anonimato ?? this.anonimato,
      usuario: usuario ?? this.usuario,
      senha: senha ?? this.senha,
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProxyModel &&
          runtimeType == other.runtimeType &&
          ip == other.ip &&
          porta == other.porta;

  @override
  int get hashCode => ip.hashCode ^ porta.hashCode;
}
