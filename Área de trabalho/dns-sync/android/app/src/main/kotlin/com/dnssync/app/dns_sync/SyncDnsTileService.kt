package com.dnssync.app.dns_sync

import android.content.Intent
import android.os.Build
import android.service.quicksettings.Tile
import android.service.quicksettings.TileService
import androidx.annotation.RequiresApi

@RequiresApi(Build.VERSION_CODES.N)
class SyncDnsTileService : TileService() {

    override fun onStartListening() {
        super.onStartListening()
        updateTileState()
    }

    override fun onClick() {
        super.onClick()
        if (MyVpnService.isRunning) {
            val intent = Intent(this, MyVpnService::class.java).apply { action = "STOP" }
            startService(intent)
        } else {
            val intent = Intent(this, MyVpnService::class.java).apply {
                action = "START"
                putExtra("dns1", "1.1.1.1")
                putExtra("dns2", "1.0.0.1")
                putExtra("provider", "Cloudflare")
                putExtra("enableAdBlock", true)
            }
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                startForegroundService(intent)
            } else {
                startService(intent)
            }
        }
        updateTileState()
    }

    private fun updateTileState() {
        val tile = qsTile ?: return
        val isRunning = MyVpnService.isRunning
        tile.state = if (isRunning) Tile.STATE_ACTIVE else Tile.STATE_INACTIVE
        tile.label = if (isRunning) "Sync DNS (Ativo)" else "Sync DNS"
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            tile.subtitle = if (isRunning) MyVpnService.currentProvider else "Toque para ligar"
        }
        tile.updateTile()
    }
}
