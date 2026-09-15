
import sqlite3
import os
import uuid
from database import get_connection
from models import Campaign
from scheduler import scheduler
from pipeline import LeadPipeline
from blueprints.events import event_bus
import time

def create_campaign():
    name = "SP_Foco_Tech_Ad_Sites"
    keyword = "Indústria|Logística|Clínica|Transportadora|Distribuidor"
    location = "São Paulo, SP"
    goal = 500
    
    conn = get_connection()
    try:
        # Check if exists
        existing = conn.execute("SELECT id FROM campaigns WHERE name = ?", (name,)).fetchone()
        if not existing:
            conn.execute(
                "INSERT INTO campaigns (name, keyword, location, goal, status, expand_auto) VALUES (?, ?, ?, ?, ?, ?)",
                (name, keyword, location, goal, "active", 1)
            )
            conn.commit()
            print(f"✅ Campanha '{name}' criada com sucesso.")
        else:
            print(f"ℹ️ Campanha '{name}' já existe.")
    finally:
        conn.close()
    return name

def run_crawl(campaign_name):
    print(f"🚀 Iniciando extração para a campanha: {campaign_name}")
    
    # Setup pipeline
    pipeline = LeadPipeline(event_bus=event_bus)
    pipeline.start()
    
    # Setup scheduler
    scheduler.pipeline = pipeline
    scheduler.event_bus = event_bus
    
    # Trigger campaign extraction
    # This will run multiple threads for different sources
    try:
        scheduler._crawl_campaigns()
        print("✅ Ciclo de extração de campanhas concluído.")
    except Exception as e:
        print(f"❌ Erro na extração: {e}")

    print("⏳ Aguardando processamento do pipeline (pode levar alguns minutos)...")
    
    # Let it run for a while to verify sites
    # In a real scenario, this would run as a service, but here we'll wait 60s for demonstration
    # and to ensure some leads are processed.
    start_time = time.time()
    while time.time() - start_time < 60:
        status = pipeline.status
        print(f"📊 Status: Verified={status['verified_total']}, Enrich={status['enriched_total']}, Pending={status['verify_pending']}")
        if status['verified_total'] > 0 and status['verify_pending'] == 0:
            break
        time.sleep(10)

    pipeline.stop()
    print("🏁 Processamento finalizado.")

def report_results(campaign_name):
    conn = get_connection()
    try:
        # Leads qualified by the user's criteria:
        # 1. Site quality poor OR copyright_year old OR not responsive
        # 2. Has ads
        # 3. Location São Paulo
        
        query = """
            SELECT name, website, phone, score, tier, pitch_hook, has_ads, site_quality, copyright_year
            FROM leads
            WHERE campaign = ?
            AND (site_quality = 'poor' OR copyright_year < 2022 OR is_responsive = 0)
            AND has_ads = 1
            ORDER BY score DESC
        """
        rows = conn.execute(query, (campaign_name,)).fetchall()
        
        print(f"\n🎯 Leads Qualificados Encontrados ({len(rows)}):")
        for i, row in enumerate(rows[:10]):
            print(f"{i+1}. {row['name']} | Score: {row['score']} | Ads: {row['has_ads']} | Quality: {row['site_quality']} | Web: {row['website']}")
            print(f"   Hook: {row['pitch_hook']}")
        
        if len(rows) < 100:
            print(f"\n⚠️ Encontrados {len(rows)} leads qualificados até agora. O processo continua em background se o serviço estiver ativo.")
        else:
            print(f"\n✅ Meta atingida! {len(rows)} leads qualificados encontrados.")
            
    finally:
        conn.close()

if __name__ == "__main__":
    camp_name = create_campaign()
    run_crawl(camp_name)
    report_results(camp_name)
