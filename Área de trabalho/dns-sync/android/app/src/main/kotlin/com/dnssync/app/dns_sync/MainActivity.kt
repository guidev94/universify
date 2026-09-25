package com.dnssync.app.dns_sync

import android.app.Activity
import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.net.ProxyInfo
import android.net.VpnService
import android.os.Build
import android.os.ParcelFileDescriptor
import android.util.Log
import androidx.core.app.NotificationCompat
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel
import java.io.ByteArrayOutputStream
import java.io.FileInputStream
import java.io.FileOutputStream
import java.net.DatagramPacket
import java.net.DatagramSocket
import java.net.HttpURLConnection
import java.net.InetAddress
import java.net.URL
import java.nio.ByteBuffer
import java.util.concurrent.ExecutorService
import java.util.concurrent.Executors
import java.util.concurrent.atomic.AtomicBoolean
import java.util.concurrent.atomic.AtomicInteger

class MainActivity: FlutterActivity() {
    private val CHANNEL = "com.dnssync.app/dns"
    private var methodChannel: MethodChannel? = null

    private var pendingDns1: String? = null
    private var pendingDns2: String? = null
    private var pendingProvider: String? = null
    private var pendingProxyHost: String? = null
    private var pendingProxyPort: Int = 0
    private var pendingUseDoh: Boolean = true
    private var pendingDohUrl: String = ""
    private var pendingBypassPackages: ArrayList<String>? = null
    private var pendingResult: MethodChannel.Result? = null

    companion object {
        const val VPN_REQUEST_CODE = 101
    }

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        val channel = MethodChannel(flutterEngine.dartExecutor.binaryMessenger, CHANNEL)
        methodChannel = channel

        MyVpnService.onStateChangeListener = { ativo, provedor ->
            runOnUiThread {
                methodChannel?.invokeMethod("onStatusChanged", mapOf(
                    "ativo" to ativo,
                    "provedor" to provedor
                ))
            }
        }

        MyVpnService.onAdBlockedListener = { count, domain ->
            runOnUiThread {
                methodChannel?.invokeMethod("onAdBlocked", mapOf(
                    "totalBlocked" to count,
                    "domain" to domain
                ))
            }
        }

        channel.setMethodCallHandler { call, result ->
            when (call.method) {
                "aplicarDns" -> {
                    val principal = call.argument<String>("principal") ?: "1.1.1.1"
                    val secundario = call.argument<String>("secundario") ?: "1.0.0.1"
                    val provedor = call.argument<String>("provedor") ?: "Cloudflare"
                    val useDoh = call.argument<Boolean>("useDoh") ?: true
                    val dohUrl = call.argument<String>("dohUrl") ?: ""
                    val enableAdBlock = call.argument<Boolean>("enableAdBlock") ?: true
                    val bypassPackages = call.argument<ArrayList<String>>("bypassPackages") ?: arrayListOf()
                    
                    val intent = VpnService.prepare(this)
                    if (intent != null) {
                        pendingDns1 = principal
                        pendingDns2 = secundario
                        pendingProvider = provedor
                        pendingProxyHost = null
                        pendingProxyPort = 0
                        pendingUseDoh = useDoh
                        pendingDohUrl = dohUrl
                        pendingBypassPackages = bypassPackages
                        pendingResult = result
                        startActivityForResult(intent, VPN_REQUEST_CODE)
                    } else {
                        startDnsVpn(principal, secundario, provedor, useDoh, dohUrl, enableAdBlock, bypassPackages)
                        result.success(true)
                    }
                }
                "aplicarProxy" -> {
                    val host = call.argument<String>("ip") ?: ""
                    val porta = call.argument<Int>("porta") ?: 8080
                    val pais = call.argument<String>("pais") ?: "Global"

                    val intent = VpnService.prepare(this)
                    if (intent != null) {
                        pendingDns1 = "1.1.1.1"
                        pendingDns2 = "1.0.0.1"
                        pendingProvider = "Proxy ($pais)"
                        pendingProxyHost = host
                        pendingProxyPort = porta
                        pendingResult = result
                        startActivityForResult(intent, VPN_REQUEST_CODE)
                    } else {
                        startProxyVpn(host, porta, "Proxy ($pais)")
                        result.success(true)
                    }
                }
                "desconectar" -> {
                    stopDnsVpn()
                    result.success(true)
                }
                "compartilharResultado" -> {
                    val texto = call.argument<String>("texto") ?: ""
                    val titulo = call.argument<String>("titulo") ?: "Sync DNS Resultado"
                    val sendIntent = Intent().apply {
                        action = Intent.ACTION_SEND
                        putExtra(Intent.EXTRA_TEXT, texto)
                        type = "text/plain"
                    }
                    val shareIntent = Intent.createChooser(sendIntent, titulo)
                    startActivity(shareIntent)
                    result.success(true)
                }
                "obterStatus" -> {
                    val status = mapOf(
                        "ativo" to MyVpnService.isRunning,
                        "provedor" to MyVpnService.currentProvider,
                        "dns" to MyVpnService.currentDns,
                        "startTimeMs" to MyVpnService.startTimeMs,
                        "anunciosBloqueados" to MyVpnService.blockedAdsCount.get()
                    )
                    result.success(status)
                }
                else -> result.notImplemented()
            }
        }
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode == VPN_REQUEST_CODE) {
            if (resultCode == Activity.RESULT_OK) {
                if (!pendingProxyHost.isNullOrBlank() && pendingProxyPort > 0) {
                    startProxyVpn(pendingProxyHost!!, pendingProxyPort, pendingProvider ?: "Proxy")
                } else {
                    startDnsVpn(
                        pendingDns1 ?: "1.1.1.1", 
                        pendingDns2 ?: "1.0.0.1", 
                        pendingProvider ?: "Cloudflare",
                        pendingUseDoh,
                        pendingDohUrl,
                        true,
                        pendingBypassPackages ?: arrayListOf()
                    )
                }
                pendingResult?.success(true)
            } else {
                pendingResult?.success(false)
            }
            pendingResult = null
        }
    }

    private fun startDnsVpn(
        p: String?, 
        s: String?, 
        provider: String,
        useDoh: Boolean = true,
        dohUrl: String = "",
        enableAdBlock: Boolean = true,
        bypassPackages: ArrayList<String> = arrayListOf()
    ) {
        val intent = Intent(this, MyVpnService::class.java).apply {
            action = "START"
            putExtra("dns1", p)
            putExtra("dns2", s)
            putExtra("provider", provider)
            putExtra("useDoh", useDoh)
            putExtra("dohUrl", dohUrl)
            putExtra("enableAdBlock", enableAdBlock)
            putStringArrayListExtra("bypassPackages", bypassPackages)
        }
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            startForegroundService(intent)
        } else {
            startService(intent)
        }
    }

    private fun startProxyVpn(host: String, port: Int, provider: String) {
        val intent = Intent(this, MyVpnService::class.java).apply {
            action = "START"
            putExtra("dns1", "1.1.1.1")
            putExtra("dns2", "1.0.0.1")
            putExtra("provider", provider)
            putExtra("proxyHost", host)
            putExtra("proxyPort", port)
        }
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            startForegroundService(intent)
        } else {
            startService(intent)
        }
    }

    private fun stopDnsVpn() {
        val intent = Intent(this, MyVpnService::class.java).apply {
            action = "STOP"
        }
        startService(intent)
    }

    override fun onDestroy() {
        MyVpnService.onStateChangeListener = null
        MyVpnService.onAdBlockedListener = null
        super.onDestroy()
    }
}

class MyVpnService : VpnService(), Runnable {
    companion object {
        const val TAG = "SyncDnsVpn"
        const val NOTIFICATION_ID = 1001
        const val CHANNEL_ID = "sync_dns_vpn_channel"
        
        var isRunning = false
            private set
        var currentProvider = "Desconectado"
            private set
        var currentDns = ""
            private set
        var startTimeMs: Long = 0
            private set
        val blockedAdsCount = AtomicInteger(0)

        var onStateChangeListener: ((Boolean, String) -> Unit)? = null
        var onAdBlockedListener: ((Int, String) -> Unit)? = null

        val AD_BLOCKLIST = hashSetOf(
            "doubleclick.net", "googleadservices.com", "googlesyndication.com",
            "adservice.google.com", "app-measurement.com", "analytics.google.com",
            "ads.facebook.com", "graph.facebook.com", "ads.tiktok.com",
            "analytics.tiktok.com", "criteo.com", "taboola.com", "outbrain.com",
            "unityads.unity3d.com", "applovin.com", "vungle.com", "ironsrc.com",
            "adcolony.com", "adnxs.com", "flurry.com", "adjust.com", "appsflyer.com",
            "branch.io", "kochava.com", "admob.com", "scorecardresearch.com",
            "quantserve.com", "popads.net", "adcash.com", "propellerads.com",
            "trafficjunky.com", "exoclick.com", "smartadserver.com", "rubiconproject.com",
            "pubmatic.com", "openx.net", "casalemedia.com", "advertising.com"
        )
    }

    private var vpnInterface: ParcelFileDescriptor? = null
    private var vpnThread: Thread? = null
    private var executorService: ExecutorService? = null
    private val shouldRun = AtomicBoolean(false)
    private var upstreamDns1: String = "1.1.1.1"
    private var upstreamDns2: String = "1.0.0.1"
    private var isDohEnabled: Boolean = true
    private var dohEndpoint: String = ""
    private var isAdBlockEnabled: Boolean = true

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        createNotificationChannel()
        val action = intent?.action ?: "START"
        if (action == "STOP") {
            stopVpn()
            return START_NOT_STICKY
        }

        upstreamDns1 = intent?.getStringExtra("dns1") ?: "1.1.1.1"
        upstreamDns2 = intent?.getStringExtra("dns2") ?: "1.0.0.1"
        val providerName = intent?.getStringExtra("provider") ?: "Cloudflare"
        val proxyHost = intent?.getStringExtra("proxyHost")
        val proxyPort = intent?.getIntExtra("proxyPort", 0) ?: 0
        isDohEnabled = intent?.getBooleanExtra("useDoh", true) ?: true
        dohEndpoint = intent?.getStringExtra("dohUrl") ?: ""
        isAdBlockEnabled = intent?.getBooleanExtra("enableAdBlock", true) ?: true
        val bypassPackages = intent?.getStringArrayListExtra("bypassPackages") ?: arrayListOf()

        startVpn(upstreamDns1, upstreamDns2, providerName, proxyHost, proxyPort, bypassPackages)
        return START_STICKY
    }

    private fun startVpn(
        dns1: String,
        dns2: String,
        providerName: String,
        proxyHost: String?,
        proxyPort: Int,
        bypassPackages: ArrayList<String>
    ) {
        stopVpn()

        try {
            createNotificationChannel()
            val notification = buildNotification(providerName, dns1, proxyHost, proxyPort)
            startForeground(NOTIFICATION_ID, notification)

            val builder = Builder()
                .setSession("SyncDNS - $providerName")
                .setMtu(1500)
                .addAddress("10.233.233.2", 30)

            // Anti-Leak IPv6 Shield
            try {
                builder.addAddress("fd00:1::2", 120)
                builder.addRoute("fd00:1::", 64)
            } catch (e: Exception) {
                Log.w(TAG, "IPv6 anti-leak fallback: ${e.message}")
            }

            // Servidores DNS e rotas para interceptação
            val publicDns = listOf(dns1, dns2, "1.1.1.1", "1.0.0.1", "8.8.8.8", "8.8.4.4", "9.9.9.9", "94.140.14.14", "208.67.222.222")
            for (d in publicDns.distinct()) {
                if (d.isNotEmpty()) {
                    try {
                        builder.addDnsServer(d)
                        builder.addRoute(d, 32)
                    } catch (e: Exception) {}
                }
            }

            // Suporte a Proxy HTTP no TUN (Android 10+)
            if (!proxyHost.isNullOrBlank() && proxyPort > 0) {
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                    builder.setHttpProxy(ProxyInfo.buildDirectProxy(proxyHost, proxyPort))
                }
            }

            // Split Tunneling
            try {
                builder.addDisallowedApplication(packageName)
                for (pkg in bypassPackages) {
                    if (pkg.isNotBlank() && pkg != packageName) {
                        try {
                            builder.addDisallowedApplication(pkg)
                        } catch (e: Exception) {}
                    }
                }
            } catch (e: Exception) {
                Log.w(TAG, "Exceção no split tunnel: ${e.message}")
            }

            vpnInterface = builder.establish()

            if (vpnInterface != null) {
                shouldRun.set(true)
                isRunning = true
                currentProvider = providerName
                currentDns = dns1
                startTimeMs = System.currentTimeMillis()
                onStateChangeListener?.invoke(true, providerName)

                executorService = Executors.newFixedThreadPool(16)
                vpnThread = Thread(this, "SyncDnsVpnThread").apply { start() }
                Log.i(TAG, "SyncDNS VPN ativa para $providerName (DoH Ativo: $isDohEnabled)")
            } else {
                Log.e(TAG, "Falha ao estabelecer interface TUN")
                stopSelf()
            }
        } catch (e: Exception) {
            Log.e(TAG, "Erro ao iniciar VPN: ${e.message}", e)
            stopVpn()
        }
    }

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                CHANNEL_ID,
                "Sync DNS - Serviço Ativo",
                NotificationManager.IMPORTANCE_LOW
            ).apply {
                description = "Notificação de status da conexão DNS e Proxy"
                setShowBadge(false)
            }
            val manager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
            manager.createNotificationChannel(channel)
        }
    }

    private fun buildNotification(
        provider: String,
        dns: String,
        proxyHost: String?,
        proxyPort: Int
    ): Notification {
        val openAppIntent = Intent(this, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_SINGLE_TOP
        }
        val pOpenApp = PendingIntent.getActivity(
            this,
            0,
            openAppIntent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val disconnectIntent = Intent(this, MyVpnService::class.java).apply {
            action = "STOP"
        }
        val pDisconnect = PendingIntent.getService(
            this,
            1,
            disconnectIntent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val subtext = if (!proxyHost.isNullOrBlank()) {
            "Proxy Ativo: $proxyHost:$proxyPort"
        } else {
            "DNS: $dns • Bloqueios: ${blockedAdsCount.get()}"
        }

        return NotificationCompat.Builder(this, CHANNEL_ID)
            .setSmallIcon(R.mipmap.ic_launcher)
            .setContentTitle("Sync DNS: Protegido ($provider)")
            .setContentText("$subtext • Toque para gerenciar")
            .setContentIntent(pOpenApp)
            .setOngoing(true)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .addAction(android.R.drawable.ic_menu_close_clear_cancel, "Desconectar", pDisconnect)
            .build()
    }

    override fun run() {
        val pfd = vpnInterface ?: return
        val input = FileInputStream(pfd.fileDescriptor)
        val out = FileOutputStream(pfd.fileDescriptor)
        val packet = ByteBuffer.allocate(32767)

        while (shouldRun.get()) {
            try {
                val length = input.read(packet.array())
                if (length > 0) {
                    val copy = ByteArray(length)
                    System.arraycopy(packet.array(), 0, copy, 0, length)
                    
                    executorService?.execute {
                        processDnsPacket(copy, length, out)
                    }
                    packet.clear()
                } else {
                    Thread.sleep(5)
                }
            } catch (e: Exception) {
                if (!shouldRun.get()) break
            }
        }
    }

    private fun processDnsPacket(data: ByteArray, length: Int, out: FileOutputStream) {
        if (length < 28) return

        val versionAndIhl = data[0].toInt() and 0xFF
        val version = versionAndIhl shr 4
        if (version != 4) return

        val ihl = (versionAndIhl and 0x0F) * 4
        val protocol = data[9].toInt() and 0xFF
        if (protocol != 17) return // UDP

        val dstPort = ((data[ihl + 2].toInt() and 0xFF) shl 8) or (data[ihl + 3].toInt() and 0xFF)
        if (dstPort != 53) return

        val srcPort = ((data[ihl].toInt() and 0xFF) shl 8) or (data[ihl + 1].toInt() and 0xFF)
        val udpLen = ((data[ihl + 4].toInt() and 0xFF) shl 8) or (data[ihl + 5].toInt() and 0xFF)
        val dnsPayloadLen = udpLen - 8
        if (dnsPayloadLen <= 0 || (ihl + 8 + dnsPayloadLen) > length) return

        val dnsQuery = ByteArray(dnsPayloadLen)
        System.arraycopy(data, ihl + 8, dnsQuery, 0, dnsPayloadLen)

        // 1. Extração do domínio solicitado (QNAME)
        val domain = extractDomainName(dnsQuery)

        // 2. Bloqueio local de anúncios (se ativado)
        if (isAdBlockEnabled && domain.isNotEmpty() && isBlockedDomain(domain)) {
            val blockedTotal = blockedAdsCount.incrementAndGet()
            onAdBlockedListener?.invoke(blockedTotal, domain)
            
            val syntheticResponse = createBlockDnsResponse(dnsQuery)
            writePacketToTun(data, dstPort, srcPort, syntheticResponse, out)
            return
        }

        val dstIp = "${data[16].toInt() and 0xFF}.${data[17].toInt() and 0xFF}.${data[18].toInt() and 0xFF}.${data[19].toInt() and 0xFF}"
        val targetDns = if (dstIp == upstreamDns1 || dstIp == upstreamDns2) dstIp else upstreamDns1

        // 3. Encaminhamento via DoH (DNS-over-HTTPS por IP direto)
        if (isDohEnabled) {
            val dohResponse = queryDoh(dnsQuery, targetDns)
            if (dohResponse != null && dohResponse.isNotEmpty()) {
                writePacketToTun(data, dstPort, srcPort, dohResponse, out)
                return
            }
        }

        // 4. Fallback para UDP protegido se DoH falhar
        var socket: DatagramSocket? = null
        try {
            socket = DatagramSocket()
            protect(socket)
            socket.soTimeout = 2000

            val targetAddr = InetAddress.getByName(targetDns)
            val sendPacket = DatagramPacket(dnsQuery, dnsQuery.size, targetAddr, 53)
            socket.send(sendPacket)

            val recvBuffer = ByteArray(2048)
            val recvPacket = DatagramPacket(recvBuffer, recvBuffer.size)
            socket.receive(recvPacket)

            val dnsResponseLen = recvPacket.length
            val dnsResponse = ByteArray(dnsResponseLen)
            System.arraycopy(recvBuffer, 0, dnsResponse, 0, dnsResponseLen)

            writePacketToTun(data, dstPort, srcPort, dnsResponse, out)
        } catch (e: Exception) {
            // Timeout
        } finally {
            try {
                socket?.close()
            } catch (e: Exception) {}
        }
    }

    private fun writePacketToTun(
        originalIpPacket: ByteArray,
        srcPort: Int,
        dstPort: Int,
        dnsResponse: ByteArray,
        out: FileOutputStream
    ) {
        val dnsResponseLen = dnsResponse.size
        val totalRespLen = 20 + 8 + dnsResponseLen
        val respPacket = ByteArray(totalRespLen)

        respPacket[0] = 0x45.toByte()
        respPacket[1] = 0x00.toByte()
        respPacket[2] = ((totalRespLen shr 8) and 0xFF).toByte()
        respPacket[3] = (totalRespLen and 0xFF).toByte()
        respPacket[4] = 0x12.toByte()
        respPacket[5] = 0x34.toByte()
        respPacket[6] = 0x40.toByte()
        respPacket[7] = 0x00.toByte()
        respPacket[8] = 64.toByte()
        respPacket[9] = 17.toByte()

        // Swap IPs
        System.arraycopy(originalIpPacket, 16, respPacket, 12, 4)
        System.arraycopy(originalIpPacket, 12, respPacket, 16, 4)

        // Checksum IPv4
        val ipChecksum = calculateChecksum(respPacket, 0, 20)
        respPacket[10] = ((ipChecksum shr 8) and 0xFF).toByte()
        respPacket[11] = (ipChecksum and 0xFF).toByte()

        // UDP Header
        val respUdpLen = 8 + dnsResponseLen
        respPacket[20] = ((srcPort shr 8) and 0xFF).toByte()
        respPacket[21] = (srcPort and 0xFF).toByte()
        respPacket[22] = ((dstPort shr 8) and 0xFF).toByte()
        respPacket[23] = (dstPort and 0xFF).toByte()
        respPacket[24] = ((respUdpLen shr 8) and 0xFF).toByte()
        respPacket[25] = (respUdpLen and 0xFF).toByte()
        respPacket[26] = 0.toByte()
        respPacket[27] = 0.toByte()

        System.arraycopy(dnsResponse, 0, respPacket, 28, dnsResponseLen)

        synchronized(out) {
            out.write(respPacket, 0, totalRespLen)
            out.flush()
        }
    }

    private fun extractDomainName(dnsData: ByteArray): String {
        if (dnsData.size < 13) return ""
        val sb = StringBuilder()
        var pos = 12
        while (pos < dnsData.size) {
            val len = dnsData[pos].toInt() and 0xFF
            if (len == 0) break
            pos++
            if (pos + len > dnsData.size) break
            if (sb.isNotEmpty()) sb.append('.')
            sb.append(String(dnsData, pos, len))
            pos += len
        }
        return sb.toString().lowercase()
    }

    private fun isBlockedDomain(domain: String): Boolean {
        for (blocked in AD_BLOCKLIST) {
            if (domain == blocked || domain.endsWith(".$blocked")) {
                return true
            }
        }
        return false
    }

    private fun createBlockDnsResponse(query: ByteArray): ByteArray {
        val resp = ByteArray(query.size + 16)
        System.arraycopy(query, 0, resp, 0, query.size)
        resp[2] = 0x81.toByte()
        resp[3] = 0x83.toByte() // NXDOMAIN
        return resp
    }

    private fun queryDoh(query: ByteArray, targetDns: String): ByteArray? {
        val dohUrl = when (targetDns) {
            "1.1.1.1", "1.0.0.1" -> "https://1.1.1.1/dns-query"
            "8.8.8.8", "8.8.4.4" -> "https://8.8.8.8/dns-query"
            "9.9.9.9", "149.112.112.112" -> "https://9.9.9.9/dns-query"
            "94.140.14.14", "94.140.15.15" -> "https://94.140.14.14/dns-query"
            else -> if (dohEndpoint.isNotEmpty()) dohEndpoint else "https://1.1.1.1/dns-query"
        }

        var conn: HttpURLConnection? = null
        return try {
            val url = URL(dohUrl)
            conn = url.openConnection() as HttpURLConnection
            conn.requestMethod = "POST"
            conn.connectTimeout = 2000
            conn.readTimeout = 2000
            conn.doOutput = true
            conn.useCaches = false
            conn.setRequestProperty("Content-Type", "application/dns-message")
            conn.setRequestProperty("Accept", "application/dns-message")

            if (dohUrl.contains("1.1.1.1") || dohUrl.contains("1.0.0.1")) {
                conn.setRequestProperty("Host", "cloudflare-dns.com")
            } else if (dohUrl.contains("8.8.8.8") || dohUrl.contains("8.8.4.4")) {
                conn.setRequestProperty("Host", "dns.google")
            } else if (dohUrl.contains("9.9.9.9")) {
                conn.setRequestProperty("Host", "dns.quad9.net")
            }

            conn.outputStream.use { os ->
                os.write(query)
                os.flush()
            }

            if (conn.responseCode == 200) {
                val baos = ByteArrayOutputStream()
                conn.inputStream.use { input ->
                    input.copyTo(baos)
                }
                baos.toByteArray()
            } else {
                null
            }
        } catch (e: Exception) {
            null
        } finally {
            conn?.disconnect()
        }
    }

    private fun calculateChecksum(buf: ByteArray, offset: Int, length: Int): Int {
        var sum = 0
        var i = offset
        while (i < offset + length - 1) {
            val word = ((buf[i].toInt() and 0xFF) shl 8) or (buf[i + 1].toInt() and 0xFF)
            sum += word
            i += 2
        }
        if (i < offset + length) {
            sum += (buf[i].toInt() and 0xFF) shl 8
        }
        while (sum shr 16 > 0) {
            sum = (sum and 0xFFFF) + (sum shr 16)
        }
        return (sum.inv() and 0xFFFF)
    }

    private fun stopVpn() {
        shouldRun.set(false)
        try {
            vpnThread?.interrupt()
            vpnThread = null
        } catch (e: Exception) {}

        try {
            executorService?.shutdownNow()
            executorService = null
        } catch (e: Exception) {}

        try {
            vpnInterface?.close()
            vpnInterface = null
        } catch (e: Exception) {}

        isRunning = false
        currentProvider = "Desconectado"
        currentDns = ""
        startTimeMs = 0
        onStateChangeListener?.invoke(false, "Desconectado")

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
            stopForeground(STOP_FOREGROUND_REMOVE)
        } else {
            @Suppress("DEPRECATION")
            stopForeground(true)
        }
        stopSelf()
    }

    override fun onDestroy() {
        stopVpn()
        super.onDestroy()
    }
}
