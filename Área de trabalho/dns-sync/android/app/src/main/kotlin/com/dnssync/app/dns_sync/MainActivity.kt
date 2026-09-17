package com.dnssync.app.dns_sync

import android.content.Intent
import android.net.VpnService
import android.os.ParcelFileDescriptor
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity: FlutterActivity() {
    private val CHANNEL = "com.dnssync.app/dns"

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, CHANNEL).setMethodCallHandler { call, result ->
            when (call.method) {
                "aplicarDns" -> {
                    val principal = call.argument<String>("principal")
                    val secundario = call.argument<String>("secundario")
                    
                    val intent = VpnService.prepare(this)
                    if (intent != null) {
                        startActivityForResult(intent, 0)
                        result.success(false) // Precisa de permissão
                    } else {
                        startDnsVpn(principal, secundario)
                        result.success(true)
                    }
                }
                "desconectar" -> {
                    stopDnsVpn()
                    result.success(true)
                }
                else -> result.notImplemented()
            }
        }
    }

    private fun startDnsVpn(p: String?, s: String?) {
        val intent = Intent(this, MyVpnService::class.java)
        intent.action = "START"
        intent.putExtra("dns1", p)
        intent.putExtra("dns2", s)
        startService(intent)
    }

    private fun stopDnsVpn() {
        val intent = Intent(this, MyVpnService::class.java)
        intent.action = "STOP"
        startService(intent)
    }
}

class MyVpnService : VpnService() {
    private var vpnInterface: ParcelFileDescriptor? = null

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        if (intent?.action == "STOP") {
            stopVpn()
            return START_NOT_STICKY
        }
        
        val dns1 = intent?.getStringExtra("dns1") ?: "8.8.8.8"
        val dns2 = intent?.getStringExtra("dns2") ?: "1.1.1.1"
        
        startVpn(dns1, dns2)
        return START_STICKY
    }

    private fun startVpn(dns1: String, dns2: String) {
        try {
            vpnInterface = Builder()
                .setSession("SyncDNS")
                .addAddress("10.0.0.2", 32)
                .addRoute("0.0.0.0", 0)
                .addDnsServer(dns1)
                .addDnsServer(dns2)
                .establish()
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    private fun stopVpn() {
        vpnInterface?.close()
        vpnInterface = null
        stopSelf()
    }

    override fun onDestroy() {
        stopVpn()
        super.onDestroy()
    }
}
