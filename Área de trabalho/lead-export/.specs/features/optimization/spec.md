# Feature Spec: Optimization & Advanced Capture

## Goal
Improve the efficiency of lead capture and the depth of qualification to ensure "Hot Leads" are truly ready for conversion.

## Requirements
### 1. Advanced Brazilian Enrichment (REQ-001)
- **CNPJ Lookup:** Integrate a public API (e.g., BrasilAPI or ReceitaWS) to find CNPJ and partners when a business name is found.
- **Deep Social Linker:** Search specifically for Instagram/LinkedIn handles if not found on the main page.

### 2. High-Quantity Capture (REQ-002)
- **Keyword Variation Engine:** A utility to expand a single keyword into neighborhood-specific searches.
- **Source Rotation Optimization:** Prioritize `directory_crawler` and `maps_crawler` for high-intent local businesses.

### 3. Smart Filtering 2.0 (REQ-003)
- **Language Detection:** (Optional/Future) Reject sites with non-Portuguese content even if on a .com domain.
- **Bounce Protection:** Verify email syntax more strictly before marking as "Hot".

### 4. Export Optimization (REQ-004)
- **Bulk WhatsApp Formatter:** Ensure phone numbers are exported in a format ready for tools like "WhatsApp Web" or automation.
- **Filtered Export by 'Category':** Allow segmentation by business type for targeted campaigns.
