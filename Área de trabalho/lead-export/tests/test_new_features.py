import pytest
from app import create_app
from database import init_db
from models import Lead

@pytest.fixture
def app():
    app = create_app()
    app.config.update({
        "TESTING": True,
        "DATABASE_PATH": ":memory:" # Use in-memory for unit tests
    })
    
    with app.app_context():
        init_db()
        yield app

@pytest.fixture
def client(app):
    return app.test_client()

def test_lead_scoring_logic():
    from crawler.lead_scorer import calculate_score
    
    # Test hot lead BR
    lead_br = {
        "name": "Empresa Teste",
        "email": "contato@empresa.com.br",
        "phone": "11999998888",
        "whatsapp": "5511999998888",
        "website": "https://empresa.com.br"
    }
    score, tier, _ = calculate_score(lead_br)
    assert score >= 80
    assert tier == "hot"

    # Test gringo lead penalty
    lead_us = {
        "name": "US Company",
        "email": "info@company.us",
        "website": "https://company.us"
    }
    score_us, tier_us, _ = calculate_score(lead_us)
    assert score_us < 50
    assert tier_us == "cold"

def test_lead_validation_br():
    from crawler.validator import validate_lead
    
    valid_lead = {
        "name": "Joao Silva",
        "email": "joao@gmail.com",
        "phone": "11988887777",
        "website": "www.joaosilva.com.br"
    }
    result = validate_lead(valid_lead)
    assert result is not None
    assert result["name"] == "Joao Silva"
    
    # Test blocked gov domain
    gov_lead = {
        "name": "Prefeitura Municipal",
        "website": "http://sp.gov.br"
    }
    assert validate_lead(gov_lead) is None

def test_api_list_leads(client):
    response = client.get('/api/leads')
    assert response.status_code == 200
    data = response.get_json()
    assert "leads" in data
    assert "total" in data
