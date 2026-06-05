import pytest
from playwright.sync_api import Page, expect


def test_dashboard_loads(page: Page, app_server):
    page.goto(f"{app_server}/dashboard")
    expect(page).to_have_title("Dashboard — LeadHunter")
    expect(page.locator("#statTotal")).to_be_visible()
    expect(page.locator("#leadsChart")).to_be_visible()
    expect(page.locator("#tierChart")).to_be_visible()


def test_navigation_all_pages(page: Page, app_server):
    pages = ["/dashboard", "/leads", "/crawl", "/export"]
    for path in pages:
        page.goto(f"{app_server}{path}")
        assert page.locator("aside").is_visible()


def test_leads_page_empty_state(page: Page, app_server):
    page.goto(f"{app_server}/leads")
    expect(page.locator("#leadsTableBody")).to_be_visible()


def test_leads_filters_exist(page: Page, app_server):
    page.goto(f"{app_server}/leads")
    expect(page.locator("#searchInput")).to_be_visible()
    expect(page.locator("#filterTier")).to_be_visible()
    expect(page.locator("#filterStatus")).to_be_visible()
    expect(page.locator("#filterSource")).to_be_visible()


def test_crawl_form_exists(page: Page, app_server):
    page.goto(f"{app_server}/crawl")
    expect(page.locator("#crawlForm")).to_be_visible()
    expect(page.locator("#query")).to_be_visible()
    expect(page.locator("#quantity")).to_be_visible()
    expect(page.locator("#submitBtn")).to_be_visible()


def test_crawl_quantity_slider(page: Page, app_server):
    page.goto(f"{app_server}/crawl")
    slider = page.locator("#quantity")
    slider.fill("50")
    slider.dispatch_event("input")
    expect(page.locator("#quantityValue")).to_have_text("50")


def test_export_page_elements(page: Page, app_server):
    page.goto(f"{app_server}/export")
    expect(page.locator("#exportBtn")).to_be_visible()
    expect(page.locator("#exportStatus")).to_be_visible()
    expect(page.locator("#previewCount")).to_be_visible()


def test_api_dashboard_stats(page: Page, app_server):
    response = page.request.get(f"{app_server}/api/dashboard/stats")
    assert response.ok
    data = response.json()
    assert "total" in data
    assert "hot" in data
    assert "warm" in data
    assert "cold" in data


def test_api_leads_paginated(page: Page, app_server):
    response = page.request.get(f"{app_server}/api/leads?page=1&per_page=10")
    assert response.ok
    data = response.json()
    assert "leads" in data
    assert "total" in data
    assert "pages" in data


def test_api_crawl_start(page: Page, app_server):
    response = page.request.post(
        f"{app_server}/api/crawl",
        data={"source": "website", "query": "test", "quantity": 5, "location": ""},
    )
    assert response.ok
    data = response.json()
    assert "job_id" in data


def test_api_delete_lead(page: Page, app_server):
    response = page.request.delete(f"{app_server}/api/leads/999")
    assert response.ok


def test_api_export_endpoint(page: Page, app_server):
    response = page.request.post(
        f"{app_server}/api/export",
        data={"format": "csv", "tier": "all"},
    )
    assert response.status in (200, 404)


def test_sidebar_mobile_toggle(page: Page, app_server):
    page.set_viewport_size({"width": 375, "height": 812})
    page.goto(f"{app_server}/dashboard")
    sidebar = page.locator("#sidebar")
    assert "-translate-x-full" in sidebar.get_attribute("class")
    page.locator("#menuToggle").click()
    page.wait_for_timeout(500)
    assert "-translate-x-full" not in sidebar.get_attribute("class")


def test_no_yellow_in_ui(page: Page, app_server):
    page.goto(f"{app_server}/dashboard")
    html = page.content()
    assert "yellow-400" not in html or "badge-hot" in html
    assert "#eab308" not in html
    assert "#fde047" not in html


def test_pipeline_page_loads(page: Page, app_server):
    page.goto(f"{app_server}/pipeline")
    expect(page).to_have_title("Pipeline — LeadHunter")
    expect(page.locator("#pipelineCards")).to_be_visible()
    expect(page.locator("#pipeFilter")).to_be_visible()


def test_pipeline_filter_select(page: Page, app_server):
    page.goto(f"{app_server}/pipeline")
    page.locator("#pipeFilter").select_option("new")
    page.wait_for_timeout(500)
    assert page.locator("#pipeFilter").input_value() == "new"


def test_campaigns_page_loads(page: Page, app_server):
    page.goto(f"{app_server}/campaigns")
    expect(page).to_have_title("Campanhas — LeadHunter")
    expect(page.locator("#campName")).to_be_visible()
    expect(page.locator("#campKeyword")).to_be_visible()
    expect(page.locator("#campGoal")).to_be_visible()


def test_campaign_create_flow(page: Page, app_server):
    page.goto(f"{app_server}/campaigns")
    page.locator("#campName").fill("E2E Test Campaign")
    page.locator("#campKeyword").fill("padaria")
    page.locator("#campLocation").fill("São Paulo")
    page.locator("#campGoal").fill("50")
    page.locator("button", has_text="Criar Campanha").click()
    page.wait_for_timeout(2000)
    content = page.content()
    assert "E2E Test Campaign" in content


def test_api_campaign_update(page: Page, app_server):
    page.request.post(
        f"{app_server}/api/campaigns",
        data={"name": "ToUpdate", "keyword": "test", "location": "SP", "goal": "100"},
    )
    data = page.request.get(f"{app_server}/api/campaigns").json()
    camp = next((c for c in data["campaigns"] if c["name"] == "ToUpdate"), None)
    assert camp is not None
    response = page.request.patch(
        f"{app_server}/api/campaigns/{camp['id']}",
        data={"name": "Updated Name", "goal": "200"},
    )
    assert response.ok
    assert response.json()["ok"] is True


def test_api_campaign_extract_goal_check(page: Page, app_server):
    response = page.request.get(f"{app_server}/api/leads/pipeline")
    assert response.ok
    data = response.json()
    assert "pipeline" in data
    assert "new" in data["pipeline"]
    assert "contacted" in data["pipeline"]
    assert "negotiating" in data["pipeline"]
    assert "closed_won" in data["pipeline"]


def test_logs_page_loads(page: Page, app_server):
    page.goto(f"{app_server}/logs")
    expect(page).to_have_title("Logs — LeadHunter")


def test_seo_meta_tags(page: Page, app_server):
    page.goto(f"{app_server}/dashboard")
    html = page.content()
    assert 'name="description"' in html
    assert 'property="og:title"' in html
    assert 'role="navigation"' in html
    assert 'role="main"' in html


def test_accessibility_h1_exists(page: Page, app_server):
    page.goto(f"{app_server}/dashboard")
    h1 = page.locator("h1")
    assert h1.count() >= 1


def test_api_crawl_cleanup(page: Page, app_server):
    response = page.request.post(f"{app_server}/api/crawl/cleanup")
    assert response.ok
    data = response.json()
    assert "cleaned" in data


def test_api_crawl_abort_unknown(page: Page, app_server):
    response = page.request.post(f"{app_server}/api/crawl/abort/nonexistent-uuid")
    assert response.ok


def test_api_crawl_retry_invalid(page: Page, app_server):
    response = page.request.post(f"{app_server}/api/crawl/retry/99999")
    assert response.status == 404


def test_logs_page_has_actions(page: Page, app_server):
    page.goto(f"{app_server}/logs")
    expect(page.locator("#logStatusFilter")).to_be_visible()
    expect(page.locator("text=Limpar Stuck")).to_be_visible()
