import re
import pytest
from playwright.sync_api import Page, expect

def test_campaign_lifecycle(page: Page):
    # 1. Create a campaign
    page.goto("http://localhost:5000/campaigns")
    
    camp_name = "Test Campaign E2E"
    page.fill("#campName", camp_name)
    page.fill("#campKeyword", "restaurante")
    page.fill("#campLocation", "São Paulo")
    page.fill("#campGoal", "10")
    
    page.click("button:has-text('Criar Campanha')")
    
    # Wait for success toast or campaign to appear
    page.wait_for_selector(f"text={camp_name}")
    
    # 2. Check if export button exists
    export_btn = page.locator(".glass-card").filter(has_text=camp_name).locator("button[title='Exportar XLSX']")
    expect(export_btn.first).to_be_visible()

def test_campaign_export_api(page: Page):
    # Test the export API directly
    response = page.request.post("http://localhost:5000/api/export", data={
        "campaign": "Test Campaign E2E",
        "format": "xlsx"
    })
    # If no leads, should be 404, but API is reachable
    assert response.status in (200, 404)
