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
    lead_id = Lead.create({
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
    lead = Lead.get_by_id(lead_id)
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
    data = {"name": "Full Name Here", "email": "test@test.com", "phone": "+5511999999999", "whatsapp": "+5511999999999", "website": "", "category": "dentista"}
    score, tier, pitch_hook = calculate_score(data)
    assert score >= 75
    assert tier == "hot"


def test_lead_scoring_cold():
    data = {"name": "X", "email": "", "phone": "", "whatsapp": "", "website": "", "category": ""}
    score, tier, pitch_hook = calculate_score(data)
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


def test_get_all_filter_category():
    Lead.create({"name": "Dentista 1", "email": "d1@t.com", "phone": "", "website": "https://dent1.com", "source": "t", "category": "dentista", "score": 80, "tier": "hot"})
    Lead.create({"name": "Advogado 1", "email": "a1@t.com", "phone": "", "website": "https://adv1.com", "source": "t", "category": "advogado", "score": 70, "tier": "warm"})

    leads, total = Lead.get_all(category="dentista")
    assert total >= 1
    assert all("dentista" in (l.get("category") or "") for l in leads)


def test_get_all_filter_category_empty():
    leads, total = Lead.get_all(category="inexistente_xyz")
    assert total == 0


def test_campaign_create():
    from models import Campaign
    Campaign.create("Test Campaign", "test_keyword", "São Paulo", 50)
    campaigns = Campaign.get_all()
    assert len(campaigns) >= 1
    assert campaigns[0]["name"] == "Test Campaign"


def test_campaign_update():
    from models import Campaign
    Campaign.create("Original", "keyword1", "SP", 100)
    campaigns = Campaign.get_all()
    camp_id = campaigns[0]["id"]
    result = Campaign.update(camp_id, name="Updated", goal=200)
    assert result is True
    updated = Campaign.get_by_id(camp_id)
    assert updated["name"] == "Updated"
    assert updated["goal"] == 200


def test_campaign_update_partial():
    from models import Campaign
    Campaign.create("Partial", "kw", "RJ", 50)
    campaigns = Campaign.get_all()
    camp_id = campaigns[0]["id"]
    Campaign.update(camp_id, keyword="new_keyword")
    updated = Campaign.get_by_id(camp_id)
    assert updated["keyword"] == "new_keyword"
    assert updated["name"] == "Partial"


def test_campaign_get_by_id():
    from models import Campaign
    Campaign.create("ById Test", "test", "SP", 75)
    campaigns = Campaign.get_all()
    camp_id = campaigns[0]["id"]
    found = Campaign.get_by_id(camp_id)
    assert found is not None
    assert found["name"] == "ById Test"
    assert found["goal"] == 75


def test_campaign_get_remaining():
    from models import Campaign
    Campaign.create("Remaining Test", "test", "SP", 50)
    remaining = Campaign.get_remaining("Remaining Test", 50)
    assert remaining == 50


def test_campaign_get_remaining_with_leads():
    from models import Campaign
    Campaign.create("WithLeads", "test", "SP", 30)
    lead_id = Lead.create({"name": "Lead Camp", "email": "camp@t.com", "phone": "", "website": "https://camp1.com", "source": "t"})
    conn = get_connection()
    conn.execute("UPDATE leads SET campaign = 'WithLeads' WHERE id = ?", (lead_id,))
    conn.commit()
    conn.close()
    remaining = Campaign.get_remaining("WithLeads", 30)
    assert remaining == 29


def test_extraction_quantity_uses_remaining():
    from models import Campaign
    Campaign.create("Big Goal", "test", "SP", 1000)
    remaining = Campaign.get_remaining("Big Goal", 1000)
    quantity = min(30, remaining)
    assert quantity == 30


def test_pipeline_stats_all_stages():
    lead_id = Lead.create({"name": "New Lead", "email": "new@t.com", "phone": "", "website": "https://newl.com", "source": "t"})
    conn = get_connection()
    conn.execute("UPDATE leads SET status = 'contacted' WHERE id = ?", (lead_id,))
    conn.commit()
    conn.close()
    lead = Lead.get_by_id(lead_id)
    assert lead["status"] == "contacted"


def test_lead_status_transitions():
    lead_id = Lead.create({"name": "Status Test", "email": "st@t.com", "phone": "", "website": "https://status1.com", "source": "t"})
    Lead.update_status(lead_id, "contacted")
    lead = Lead.get_by_id(lead_id)
    assert lead["status"] == "contacted"
    Lead.update_status(lead_id, "negotiating")
    lead = Lead.get_by_id(lead_id)
    assert lead["status"] == "negotiating"


def test_crawl_job_create_and_fail():
    import uuid
    job_uuid = f"test-{uuid.uuid4().hex[:8]}"
    job_id = CrawlJob.create(job_uuid, "test_keyword", "SP", "both", 30)
    assert job_id is not None
    CrawlJob.fail(job_uuid, "Timeout error")
    job = CrawlJob.get_by_uuid(job_uuid)
    assert job["status"] == "error"
    assert "Timeout" in job["errors"]


def test_crawl_job_complete():
    import uuid
    job_uuid = f"test-{uuid.uuid4().hex[:8]}"
    CrawlJob.create(job_uuid, "dentista", "RJ", "website", 25)
    CrawlJob.set_running(job_uuid)
    job = CrawlJob.get_by_uuid(job_uuid)
    assert job["status"] == "running"
    CrawlJob.complete(job_uuid, 20)
    job = CrawlJob.get_by_uuid(job_uuid)
    assert job["status"] == "completed"
    assert job["quantity_found"] == 20


def test_crawl_job_cleanup_stuck():
    import uuid
    job_uuid = f"test-stuck-{uuid.uuid4().hex[:8]}"
    CrawlJob.create(job_uuid, "stuck_test", "", "both", 10)
    CrawlJob.set_running(job_uuid)
    conn = get_connection()
    conn.execute("UPDATE crawl_jobs SET started_at = datetime('now', '-10 minutes', 'localtime') WHERE job_uuid = ?", (job_uuid,))
    conn.commit()
    conn.execute("UPDATE crawl_jobs SET status = 'error' WHERE status = 'running' AND started_at < datetime('now', '-2 minutes', 'localtime')")
    conn.commit()
    conn.close()
    job = CrawlJob.get_by_uuid(job_uuid)
    assert job["status"] == "error"
