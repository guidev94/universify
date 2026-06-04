import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

import pytest
from models import Lead, CrawlJob, Keyword
from crawler.lead_scorer import calculate_score
from crawler.validator import validate_lead
from database import init_db, get_connection


@pytest.fixture(autouse=True)
def setup_db(tmp_path, monkeypatch):
    db_path = str(tmp_path / "test.db")
    monkeypatch.setattr("config.Config.DATABASE_PATH", db_path)
    monkeypatch.setattr("database.Config.DATABASE_PATH", db_path)
    init_db()
    yield


def test_lead_create_returns_id():
    lead_id = Lead.create({
        "name": "Teste Lead",
        "email": "teste@example.com",
        "phone": "+5511999999999",
        "website": "https://teste.com.br",
        "source": "test",
    })
    assert lead_id is not None
    assert isinstance(lead_id, int)


def test_lead_dedup_same_domain():
    id1 = Lead.create({
        "name": "Lead 1",
        "email": "a@teste.com",
        "phone": "",
        "website": "https://www.teste.com.br/page1",
        "source": "test",
    })
    id2 = Lead.create({
        "name": "Lead 2",
        "email": "b@teste.com",
        "phone": "",
        "website": "https://teste.com.br/page2",
        "source": "test",
    })
    assert id1 == id2


def test_lead_dedup_merges_emails():
    Lead.create({
        "name": "Empresa X",
        "email": "contato@empresa.com",
        "phone": "+5511111111111",
        "website": "https://empresa.com.br",
        "source": "test",
    })
    Lead.create({
        "name": "Empresa X",
        "email": "vendas@empresa.com",
        "phone": "+5522222222222",
        "website": "https://empresa.com.br/contato",
        "source": "test",
    })
    lead = Lead.get_by_id(1)
    assert "contato@empresa.com" in lead["email"]
    assert "vendas@empresa.com" in lead["email"]


def test_stats_total_equals_sum():
    Lead.create({"name": "Hot", "email": "h@t.com", "phone": "+5511999999999", "whatsapp": "+5511999999999", "website": "https://hot1.com", "source": "t", "score": 90, "tier": "hot"})
    Lead.create({"name": "Warm", "email": "", "phone": "+5511888888888", "website": "https://warm1.com", "source": "t", "score": 60, "tier": "warm"})
    Lead.create({"name": "Cold", "email": "", "phone": "", "website": "https://cold1.com", "source": "t", "score": 20, "tier": "cold"})

    stats = Lead.get_stats()
    assert stats["total"] == stats["hot"] + stats["warm"] + stats["cold"]


def test_chart_data_has_7_days():
    chart = Lead.get_chart_data()
    assert len(chart["labels"]) >= 7
    assert len(chart["values"]) >= 7


def test_lead_scoring_hot():
    data = {"name": "Full Name Here", "email": "test@test.com", "phone": "+5511999999999", "whatsapp": "+5511999999999", "website": "https://site.com", "category": "dentista"}
    score, tier = calculate_score(data)
    assert score >= 80
    assert tier == "hot"


def test_lead_scoring_cold():
    data = {"name": "X", "email": "", "phone": "", "whatsapp": "", "website": "", "category": ""}
    score, tier = calculate_score(data)
    assert score < 50
    assert tier == "cold"


def test_validator_requires_name():
    result = validate_lead({"name": "", "email": "a@b.com", "phone": ""})
    assert result is None


def test_validator_requires_contact():
    result = validate_lead({"name": "Test", "email": "", "phone": ""})
    assert result is None


def test_validator_accepts_valid():
    result = validate_lead({"name": "Test Lead", "email": "valid@email.com", "phone": ""})
    assert result is not None
    assert result["name"] == "Test Lead"


def test_lead_hash_by_domain():
    hash1 = Lead._generate_hash({"email": "a@x.com", "phone": "", "website": "https://www.example.com/page1", "name": "A"})
    hash2 = Lead._generate_hash({"email": "b@y.com", "phone": "", "website": "https://example.com/page2", "name": "B"})
    assert hash1 == hash2


def test_lead_hash_different_domains():
    hash1 = Lead._generate_hash({"email": "", "phone": "", "website": "https://site-a.com", "name": "A"})
    hash2 = Lead._generate_hash({"email": "", "phone": "", "website": "https://site-b.com", "name": "B"})
    assert hash1 != hash2
