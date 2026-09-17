# Auditoria de Segurança: Sync DNS (Play Store Ready)

## 1. Ofuscação de Código (Obfuscation)
Para subir na Play Store, o app será compilado com `--obfuscate`.
- **Risco:** Engenharia reversa para descobrir lógica de proxy.
- **Mitigação:** Dart code obfuscation e ProGuard no Android.

## 2. Permissões Críticas
O app solicita `BIND_VPN_SERVICE`.
- **Regra Google Play:** Apps que usam VpnService devem ser explícitos.
- **Ação:** Criar uma "Página de Consentimento" obrigatória no primeiro uso explicando que o app cria um túnel local para proteção DNS.

## 3. Integridade de Dados
- **SSL Pinning:** Sugerido para chamadas à API de Proxies em versões futuras.
- **Certificados:** O app deve usar apenas conexões HTTPS (já implementado).

## 4. Segurança de Terceiros (Proxies)
- **Risco:** O usuário pode se conectar a um proxy malicioso.
- **Ação:** Adicionar o campo "Rating" de confiabilidade e avisos de "Sandbox" para proxies não verificados.
