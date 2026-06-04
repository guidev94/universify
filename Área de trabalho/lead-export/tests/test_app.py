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


def test_api_export_no_leads(page: Page, app_server):
    response = page.request.post(
        f"{app_server}/api/export",
        data={"format": "csv", "tier": "hot"},
    )
    assert response.status == 404


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
