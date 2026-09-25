class IpValidator {
  static final RegExp _ipv4Regex = RegExp(
    r'^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$',
  );

  static bool isIpv4Valido(String ip) {
    final trimmed = ip.trim();
    if (trimmed.isEmpty) return false;
    return _ipv4Regex.hasMatch(trimmed);
  }

  static bool isPortaValida(int porta) {
    return porta >= 1 && porta <= 65535;
  }

  static String? validarIp(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Informe o endereço IP';
    }
    if (!isIpv4Valido(value.trim())) {
      return 'IP inválido (Ex: 8.8.8.8)';
    }
    return null;
  }
}
