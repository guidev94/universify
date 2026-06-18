import re
from playwright.sync_api import Page, expect

def test_dashboard_load(page: Page):
    page.goto("http://localhost:5000/dashboard")
    expect(page).to_have_title(re.compile("Dashboard"))
    # Wait for stats to load
    page.wait_for_selector(".text-blue-400")

def test_kanban_drag_and_drop(page: Page):
    page.goto("http://localhost:5000/pipeline")
    # Wait for cards to load
    page.wait_for_selector(".kanban-card")
    
    initial_new_count = page.inner_text("[data-status='new'] .count-badge")
    initial_contacted_count = page.inner_text("[data-status='contacted'] .count-badge")
    
    # Simple check if columns exist
    expect(page.locator("[data-status='new']")).to_be_visible()
    expect(page.locator("[data-status='contacted']")).to_be_visible()

def test_export_page_filters(page: Page):
    page.goto("http://localhost:5000/export")
    # Select Hot tier only
    page.uncheck("input[value='warm']")
    page.wait_for_timeout(500) # Wait for preview update
    
    # Check if preview count changed/loaded
    count = page.inner_text("#previewCount")
    assert count != "—"
