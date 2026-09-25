package com.dnssync.app.dns_sync

import android.appwidget.AppWidgetManager
import android.content.Context
import android.content.SharedPreferences
import android.widget.RemoteViews
import es.antonborri.home_widget.HomeWidgetLaunchIntent
import es.antonborri.home_widget.HomeWidgetProvider

class SyncDnsMediumWidgetProvider : HomeWidgetProvider() {
    override fun onUpdate(
        context: Context,
        appWidgetManager: AppWidgetManager,
        appWidgetIds: IntArray,
        widgetData: SharedPreferences
    ) {
        appWidgetIds.forEach { widgetId ->
            val views = RemoteViews(context.packageName, R.layout.sync_dns_medium_widget).apply {
                val isActive = widgetData.getBoolean("is_active", false)
                val provider = widgetData.getString("provider", "Nenhum") ?: "Nenhum"
                val speed = widgetData.getString("speed", "-- Mbps") ?: "-- Mbps"

                setTextViewText(R.id.widget_medium_speed, speed)
                setTextViewText(R.id.widget_medium_provider, if (isActive) "DNS: $provider" else "Desprotegido")
                setTextViewText(R.id.widget_medium_badge, if (isActive) "PROTEGIDO" else "DESATIVADO")
                setTextColor(R.id.widget_medium_badge, if (isActive) 0xFF00FF66.toInt() else 0xFF888888.toInt())

                val pendingIntent = HomeWidgetLaunchIntent.getActivity(
                    context,
                    MainActivity::class.java
                )
                setOnClickPendingIntent(R.id.widget_medium_root, pendingIntent)
            }
            appWidgetManager.updateAppWidget(widgetId, views)
        }
    }
}
