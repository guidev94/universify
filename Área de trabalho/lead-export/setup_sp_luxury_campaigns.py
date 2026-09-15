import sqlite3
import os

DB_PATH = os.path.join(os.path.dirname(__file__), "data", "leads.db")

def setup_sp_luxury_campaigns():
    print("Configurando Campanhas High-End SP (Paulista, Faria Lima, etc.)...")
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()

    # Foco em regiões de altíssimo ticket em São Paulo
    campaigns = [
        ("Paulista — Coworking e Services", "Escritório Compartilhado OR Coworking OR Consultoria", "Avenida Paulista, São Paulo", 100),
        ("Higienópolis — Saúde e Bem-Estar", "Clínica Odontológica Luxo OR Pilates OR Spa Estético", "Higienópolis, São Paulo", 100),
        ("Morumbi — Educação e Kids", "Berçário Bilíngue OR Escola Internacional OR Buffet Infantil", "Morumbi, São Paulo", 100),
        ("Berrini — Business e TI", "Desenvolvimento Software OR Data Center OR Outsourcing TI", "Berrini, São Paulo", 100),
        ("Faria Lima — Corporate Finance", "Asset Management OR Investment Banking OR M&A", "Faria Lima, São Paulo", 100)
    ]

    for name, kw, loc, goal in campaigns:
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
    setup_sp_luxury_campaigns()
