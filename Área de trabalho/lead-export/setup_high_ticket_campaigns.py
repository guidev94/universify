import sqlite3
import os

DB_PATH = os.path.join(os.path.dirname(__file__), "data", "leads.db")

def setup_campaigns():
    print("Configurando Campanhas High-Ticket SP...")
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()

    campaigns = [
        ("Indústrias e Logística SP", "Indústria OR Fábrica OR Logística OR Transportadora", "São Paulo, SP", 250),
        ("Clínicas de Alto Padrão SP", "Clínica Médica OR Odontologia Estética OR Cirurgia Plástica", "São Paulo, SP", 200),
        ("Construção e Engenharia SP", "Construtora OR Incorporadora OR Engenharia Civil", "São Paulo, SP", 200),
        ("Escritórios de Advocacia SP", "Advocacia Tributária OR Direito Empresarial", "São Paulo, SP", 150),
        ("Tecnologia e Software SP", "Software House OR Automação Industrial OR Consultoria TI", "São Paulo, SP", 150)
    ]

    for name, kw, loc, goal in campaigns:
        # Check if exists
        cursor.execute("SELECT id FROM campaigns WHERE name = ?", (name,))
        if not cursor.fetchone():
            cursor.execute("""
                INSERT INTO campaigns (name, keyword, location, goal, status, expand_auto, created_at)
                VALUES (?, ?, ?, ?, 'active', 1, datetime('now', 'localtime'))
            """, (name, kw, loc, goal))
            print(f"Campanha '{name}' criada.")
        else:
            print(f"Campanha '{name}' já existe.")

    conn.commit()
    conn.close()

if __name__ == "__main__":
    setup_campaigns()
