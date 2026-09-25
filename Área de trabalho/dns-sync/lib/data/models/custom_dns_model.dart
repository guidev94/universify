class CustomDnsModel {
  final String id;
  final String nome;
  final String dnsPrincipal;
  final String dnsSecundario;
  final int latencia;

  CustomDnsModel({
    required this.id,
    required this.nome,
    required this.dnsPrincipal,
    this.dnsSecundario = '',
    this.latencia = 0,
  });

  factory CustomDnsModel.fromJson(Map<String, dynamic> json) {
    return CustomDnsModel(
      id: json['id'] ?? DateTime.now().millisecondsSinceEpoch.toString(),
      nome: json['nome'] ?? 'Custom DNS',
      dnsPrincipal: json['dnsPrincipal'] ?? '8.8.8.8',
      dnsSecundario: json['dnsSecundario'] ?? '',
      latencia: json['latencia'] ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'nome': nome,
      'dnsPrincipal': dnsPrincipal,
      'dnsSecundario': dnsSecundario,
      'latencia': latencia,
    };
  }

  CustomDnsModel copyWith({
    String? id,
    String? nome,
    String? dnsPrincipal,
    String? dnsSecundario,
    int? latencia,
  }) {
    return CustomDnsModel(
      id: id ?? this.id,
      nome: nome ?? this.nome,
      dnsPrincipal: dnsPrincipal ?? this.dnsPrincipal,
      dnsSecundario: dnsSecundario ?? this.dnsSecundario,
      latencia: latencia ?? this.latencia,
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CustomDnsModel &&
          runtimeType == other.runtimeType &&
          id == other.id;

  @override
  int get hashCode => id.hashCode;
}
