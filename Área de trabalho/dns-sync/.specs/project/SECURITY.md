# Análise de Segurança: DNS Sync

## 1. Escopo de Segurança
O DNS Sync manipula configurações críticas de rede no nível do sistema operacional (Android/iOS).

## 2. Riscos Identificados e Mitigações

### R-1: Manipulação de Tráfego via DNS/Proxy
- **Risco:** Servidores DNS ou Proxies maliciosos podem interceptar tráfego, realizar ataques de Man-in-the-Middle (MitM) ou redirecionar o usuário para sites de phishing.
- **Mitigação:** 
    - O app fornece provedores DNS confiáveis (Google, Cloudflare) por padrão.
    - **Aviso Obrigatório:** Implementar um aviso de isenção de responsabilidade (disclaimer) ao ativar proxies gratuitos.
    - **Validação:** Verificar a integridade dos IPs de proxy antes da conexão.

### R-2: Permissões de VPN (VpnService)
- **Risco:** O uso de `VpnService` para alterar o DNS dá ao app visibilidade sobre todo o tráfego do dispositivo.
- **Mitigação:** 
    - Política de Privacidade clara declarando "Zero Logs".
    - O túnel VPN deve ser configurado para interceptar apenas consultas DNS (Porta 53), não o tráfego de dados completo, sempre que possível.

### R-3: Armazenamento de Dados Sensíveis
- **Risco:** Logs de conexão ou IPs de proxy podem ser vazados.
- **Mitigação:** 
    - Não armazenar logs persistentes no dispositivo.
    - Usar `flutter_secure_storage` para quaisquer chaves ou configurações sensíveis.

## 3. Próximos Passos de Segurança
- [ ] Implementar tela de consentimento e aviso sobre proxies.
- [ ] Integrar checagem de "DNS Leak" dentro do app.
- [ ] Realizar auditoria de pacotes externos.
