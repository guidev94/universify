import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_lucide/flutter_lucide.dart';
import 'package:shimmer/shimmer.dart';
import '../../core/theme/app_theme.dart';
import '../../core/constants/canal_nativo.dart';
import '../../core/utils/ip_validator.dart';
import '../../data/models/proxy_model.dart';
import '../../data/services/notificacao_service.dart';
import '../../data/services/widget_service.dart';
import '../providers/proxy_provider.dart';

class ProxyListScreen extends ConsumerStatefulWidget {
  const ProxyListScreen({super.key});

  @override
  ConsumerState<ProxyListScreen> createState() => _ProxyListScreenState();
}

class _ProxyListScreenState extends ConsumerState<ProxyListScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _connectingProxyIp = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _atualizarWidget() {
    final ativo = ref.read(dnsStatusProvider);
    final provedor = ref.read(provedorSelecionadoProvider);
    final velocidade = ref.read(speedTestProvider).downloadSpeed;
    
    WidgetService.atualizarWidget(
      ativo: ativo,
      provedor: provedor,
      velocidade: velocidade,
    );
  }

  Future<void> _conectarProxy(ProxyModel proxy) async {
    setState(() => _connectingProxyIp = proxy.ip);
    HapticFeedback.mediumImpact();

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text("Testando e conectando ao proxy ${proxy.enderecoFormatado}..."),
        behavior: SnackBarBehavior.floating,
        backgroundColor: AppCores.primario,
        duration: const Duration(seconds: 2),
        margin: const EdgeInsets.only(bottom: 110, left: 20, right: 20),
      ),
    );

    final sucesso = await CanalNativo.aplicarProxy(
      ip: proxy.ip,
      porta: proxy.portaInt,
      pais: proxy.pais,
      usuario: proxy.usuario,
      senha: proxy.senha,
    );

    if (!mounted) return;
    setState(() => _connectingProxyIp = '');

    if (sucesso) {
      ref.read(dnsStatusProvider.notifier).state = true;
      ref.read(conexaoTipoProvider.notifier).state = 'proxy';
      ref.read(proxyAtivoProvider.notifier).state = proxy;
      ref.read(provedorSelecionadoProvider.notifier).state = "Proxy (${proxy.pais})";
      ref.read(tempoConexaoProvider.notifier).iniciar();

      await NotificacaoService().mostrarNotificacaoProxy(proxy.ip, proxy.portaInt, proxy.pais);
      _atualizarWidget();

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text("🌐 Proxy ${proxy.enderecoFormatado} ativado com sucesso!"),
          behavior: SnackBarBehavior.floating,
          backgroundColor: AppCores.sucesso,
          duration: const Duration(seconds: 3),
          margin: const EdgeInsets.only(bottom: 110, left: 20, right: 20),
        ),
      );
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text("Falha ao estabelecer túnel proxy. Tente outro servidor."),
          behavior: SnackBarBehavior.floating,
          backgroundColor: AppCores.alerta,
          duration: Duration(seconds: 3),
          margin: EdgeInsets.only(bottom: 110, left: 20, right: 20),
        ),
      );
    }
  }

  Future<void> _desconectarProxy() async {
    HapticFeedback.mediumImpact();
    await CanalNativo.desconectar();
    ref.read(dnsStatusProvider.notifier).state = false;
    ref.read(conexaoTipoProvider.notifier).state = 'nenhum';
    ref.read(proxyAtivoProvider.notifier).state = null;
    ref.read(provedorSelecionadoProvider.notifier).state = "Nenhum";
    ref.read(tempoConexaoProvider.notifier).parar();

    await NotificacaoService().mostrarNotificacaoDesconectado();
    _atualizarWidget();

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text("Proxy desconectado com sucesso."),
          behavior: SnackBarBehavior.floating,
          duration: Duration(seconds: 2),
          margin: EdgeInsets.only(bottom: 110, left: 20, right: 20),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final proxiesAsync = ref.watch(proxiesFiltradosProvider);
    final proxyAtivo = ref.watch(proxyAtivoProvider);
    final estaConectado = ref.watch(dnsStatusProvider);
    final tipoConexao = ref.watch(conexaoTipoProvider);

    final bool isProxyAtivo = estaConectado && tipoConexao == 'proxy' && proxyAtivo != null;

    return Scaffold(
      backgroundColor: AppCores.fundo(context),
      appBar: AppBar(
        title: const Text(
          "Servidores Proxy",
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 20),
        ),
        elevation: 0,
        backgroundColor: Colors.transparent,
        actions: [
          IconButton(
            onPressed: _abrirModalAdicionarProxyPrivado,
            icon: const Icon(Icons.add_circle_outline_rounded, color: AppCores.primario),
            tooltip: "Adicionar Proxy Privado",
          ),
          IconButton(
            onPressed: () {
              HapticFeedback.lightImpact();
              ref.invalidate(listaProxyProvider);
            },
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: Column(
        children: [
          if (isProxyAtivo) _buildActiveProxyBanner(proxyAtivo),
          _buildSearchBar(),
          _buildSecurityWarning(context),
          
          Expanded(
            child: proxiesAsync.when(
              data: (proxies) {
                if (proxies.isEmpty) {
                  return _buildEmptyState();
                }
                return ListView.builder(
                  physics: const BouncingScrollPhysics(),
                  padding: const EdgeInsets.symmetric(horizontal: AppEspacamento.md),
                  itemCount: proxies.length,
                  itemBuilder: (context, index) {
                    final proxy = proxies[index];
                    final isCurrent = isProxyAtivo && proxyAtivo.ip == proxy.ip;
                    final isConnecting = _connectingProxyIp == proxy.ip;

                    return _buildModernProxyCard(
                      context, 
                      proxy, 
                      index, 
                      isCurrent: isCurrent,
                      isConnecting: isConnecting,
                    );
                  },
                );
              },
              loading: () => _buildSkeletonLoading(context),
              error: (err, stack) => _buildErrorState(context),
            ),
          ),
          const SizedBox(height: 100),
        ],
      ),
    );
  }

  Widget _buildActiveProxyBanner(ProxyModel proxy) {
    return Container(
      margin: const EdgeInsets.fromLTRB(AppEspacamento.md, 0, AppEspacamento.md, AppEspacamento.sm),
      padding: const EdgeInsets.all(AppEspacamento.md),
      decoration: BoxDecoration(
        color: AppCores.sucesso.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(AppBordas.raioGrande),
        border: Border.all(color: AppCores.sucesso.withValues(alpha: 0.4)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: const BoxDecoration(
              color: AppCores.sucesso,
              shape: BoxShape.circle,
            ),
            child: const Icon(LucideIcons.globe, color: Colors.white, size: 18),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  "PROXY ATIVO & CRIPTOGRAFADO",
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 1,
                    color: AppCores.sucesso,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  "${proxy.ip}:${proxy.porta} • ${proxy.pais} ${proxy.isAutenticado ? '(Auth)' : ''}",
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                ),
              ],
            ),
          ),
          ElevatedButton(
            onPressed: _desconectarProxy,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppCores.primario,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              elevation: 0,
            ),
            child: const Text("Desconectar", style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    ).animate().fadeIn().slideY(begin: -0.1);
  }

  Widget _buildSearchBar() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppEspacamento.md, vertical: 4),
      child: TextField(
        controller: _searchController,
        onChanged: (val) {
          ref.read(buscaProxyProvider.notifier).state = val;
        },
        decoration: InputDecoration(
          hintText: "Buscar por IP, Porta ou País...",
          hintStyle: TextStyle(fontSize: 13, color: AppCores.textoSuave(context)),
          prefixIcon: const Icon(LucideIcons.search, size: 18),
          suffixIcon: _searchController.text.isNotEmpty
              ? IconButton(
                  icon: const Icon(Icons.clear, size: 16),
                  onPressed: () {
                    _searchController.clear();
                    ref.read(buscaProxyProvider.notifier).state = '';
                  },
                )
              : null,
          filled: true,
          fillColor: AppCores.superficie(context),
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
            borderSide: BorderSide(color: AppCores.borda(context)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
            borderSide: BorderSide(color: AppCores.borda(context)),
          ),
        ),
      ),
    );
  }

  Widget _buildSecurityWarning(BuildContext context) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.symmetric(horizontal: AppEspacamento.md, vertical: 8),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: AppCores.alerta.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(AppBordas.raioPadrao),
        border: Border.all(color: AppCores.alerta.withValues(alpha: 0.2)),
      ),
      child: Row(
        children: [
          const Icon(Icons.security_rounded, color: AppCores.alerta, size: 18),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              "Servidores com SSL e túnel criptografado. Suporta autenticação de proxy privada.",
              style: TextStyle(
                fontSize: 11, 
                color: AppCores.alerta.withValues(alpha: 0.9),
                fontWeight: FontWeight.w600
              ),
            ),
          ),
        ],
      ),
    ).animate().fadeIn();
  }

  Widget _buildEmptyState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppEspacamento.xl),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(LucideIcons.search_x, size: 48, color: Colors.grey),
            const SizedBox(height: 16),
            const Text(
              "Nenhum proxy encontrado",
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
            ),
            const SizedBox(height: 8),
            Text(
              "Tente buscar com outros termos ou recarregue a lista.",
              style: TextStyle(fontSize: 12, color: AppCores.textoSuave(context)),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSkeletonLoading(BuildContext context) {
    return ListView.separated(
      padding: const EdgeInsets.all(AppEspacamento.md),
      itemCount: 6,
      separatorBuilder: (context, index) => const SizedBox(height: AppEspacamento.sm),
      itemBuilder: (context, index) => Shimmer.fromColors(
        baseColor: AppCores.borda(context),
        highlightColor: AppCores.fundo(context),
        child: Container(
          height: 76,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(AppBordas.raioGrande),
          ),
        ),
      ),
    );
  }

  Widget _buildErrorState(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppEspacamento.xl),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, size: 56, color: AppCores.primario),
            const SizedBox(height: 16),
            const Text(
              "Erro ao carregar lista de proxies",
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            const Text(
              "Verifique sua conexão de rede e tente novamente.",
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12),
            ),
            const SizedBox(height: 20),
            ElevatedButton.icon(
              onPressed: () => ref.invalidate(listaProxyProvider),
              icon: const Icon(Icons.refresh, size: 16),
              label: const Text("Tentar Novamente"),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildModernProxyCard(
    BuildContext context, 
    ProxyModel proxy, 
    int index, {
    required bool isCurrent,
    required bool isConnecting,
  }) {
    final favoritos = ref.watch(favoritosProvider);
    final isFavorito = favoritos.contains(proxy.ip);

    return Container(
      margin: const EdgeInsets.only(bottom: AppEspacamento.sm),
      decoration: BoxDecoration(
        color: isCurrent 
            ? AppCores.primario.withValues(alpha: 0.06) 
            : AppCores.superficie(context),
        borderRadius: BorderRadius.circular(AppBordas.raioGrande),
        border: Border.all(
          color: isCurrent ? AppCores.primario : AppCores.borda(context),
          width: isCurrent ? 2 : 1,
        ),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: isConnecting ? null : () => _conectarProxy(proxy),
          borderRadius: BorderRadius.circular(AppBordas.raioGrande),
          child: Padding(
            padding: const EdgeInsets.all(AppEspacamento.md),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: isCurrent ? AppCores.primario : AppCores.primario.withValues(alpha: 0.1),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    isCurrent ? LucideIcons.check : LucideIcons.server, 
                    color: isCurrent ? Colors.white : AppCores.primario, 
                    size: 18,
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Text(
                            proxy.enderecoFormatado,
                            style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
                          ),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: AppCores.fundo(context),
                              borderRadius: BorderRadius.circular(4),
                              border: Border.all(color: AppCores.borda(context)),
                            ),
                            child: Text(
                              proxy.protocolo,
                              style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppCores.textoSuave(context)),
                            ),
                          ),
                          if (proxy.isAutenticado) ...[
                            const SizedBox(width: 6),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: AppCores.primario.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: const Text("AUTH", style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: AppCores.primario)),
                            ),
                          ],
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(LucideIcons.globe, size: 12, color: AppCores.textoSuave(context)),
                          const SizedBox(width: 4),
                          Text(
                            proxy.pais,
                            style: TextStyle(fontSize: 12, color: AppCores.textoSuave(context)),
                          ),
                          const SizedBox(width: 10),
                          Container(
                            width: 6,
                            height: 6,
                            decoration: const BoxDecoration(
                              shape: BoxShape.circle,
                              color: AppCores.sucesso,
                            ),
                          ),
                          const SizedBox(width: 4),
                          Text(
                            proxy.latencia > 0 ? "${proxy.latencia}ms" : "Online",
                            style: const TextStyle(fontSize: 11, color: AppCores.sucesso, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                if (isConnecting)
                  const SizedBox(
                    width: 24,
                    height: 24,
                    child: CircularProgressIndicator(strokeWidth: 2.5),
                  )
                else
                  IconButton(
                    icon: Icon(
                      isFavorito ? Icons.star_rounded : Icons.star_outline_rounded,
                      color: isFavorito ? Colors.amber : AppCores.textoSuave(context),
                    ),
                    onPressed: () {
                      HapticFeedback.mediumImpact();
                      ref.read(favoritosProvider.notifier).toggle(proxy.ip);
                    },
                  ),
              ],
            ),
          ),
        ),
      ),
    ).animate().fadeIn(delay: (index * 30).ms).slideX(begin: 0.05);
  }

  void _abrirModalAdicionarProxyPrivado() {
    final formKey = GlobalKey<FormState>();
    final ipController = TextEditingController();
    final portaController = TextEditingController();
    final paisController = TextEditingController(text: "Privado");
    final userController = TextEditingController();
    final passController = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
          child: Container(
            padding: EdgeInsets.fromLTRB(24, 24, 24, MediaQuery.of(ctx).viewInsets.bottom + 24),
            decoration: BoxDecoration(
              color: AppCores.superficie(context),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
              border: Border.all(color: AppCores.borda(context)),
            ),
            child: Form(
              key: formKey,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        "Conectar Proxy Privado",
                        style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () => Navigator.pop(ctx),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      Expanded(
                        flex: 3,
                        child: TextFormField(
                          controller: ipController,
                          decoration: const InputDecoration(labelText: "Endereço IP / Host"),
                          validator: IpValidator.validarIp,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        flex: 2,
                        child: TextFormField(
                          controller: portaController,
                          keyboardType: TextInputType.number,
                          decoration: const InputDecoration(labelText: "Porta"),
                          validator: (v) {
                            final p = int.tryParse(v ?? '');
                            return (p != null && IpValidator.isPortaValida(p)) ? null : "Inválida";
                          },
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: userController,
                    decoration: const InputDecoration(labelText: "Usuário (Opcional)", prefixIcon: Icon(LucideIcons.user, size: 18)),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: passController,
                    obscureText: true,
                    decoration: const InputDecoration(labelText: "Senha (Opcional)", prefixIcon: Icon(LucideIcons.key, size: 18)),
                  ),
                  const SizedBox(height: 20),
                  ElevatedButton(
                    onPressed: () {
                      if (formKey.currentState?.validate() ?? false) {
                        final proxy = ProxyModel(
                          ip: ipController.text.trim(),
                          porta: portaController.text.trim(),
                          pais: paisController.text.trim(),
                          protocolo: "HTTP",
                          latencia: 15,
                          anonimato: "Privado",
                          usuario: userController.text.trim().isNotEmpty ? userController.text.trim() : null,
                          senha: passController.text.trim().isNotEmpty ? passController.text.trim() : null,
                        );
                        Navigator.pop(ctx);
                        _conectarProxy(proxy);
                      }
                    },
                    child: const Text("Conectar ao Proxy", style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}
