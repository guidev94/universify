# Feature Spec: Sales Pipeline Refactor (Focus on Redesign)

## Description
Replace the complex and broken Kanban board with a functional "Opportunity Feed" optimized for selling website redesigns. The new pipeline will prioritize leads with "old, ugly, or non-existent" sites.

## Requirements

### 1. Unified Opportunity Feed (ID: PIP-UI-NEW)
- **PIP-UI-1:** Remove the multi-column Kanban layout.
- **PIP-UI-2:** Implement a vertical "Sales Feed" sorted by conversion potential.
- **PIP-UI-3:** Group leads into simple buckets: "High Priority (Redesign Needed)", "Recent Leads", "Contacted".

### 2. Redesign Sales Intelligence (ID: PIP-TECH-NEW)
- **PIP-TECH-1:** Display specific "Redesign Triggers" in the UI:
    - 🔴 **Sem Site:** Company has no website.
    - 🟠 **Site Antigo:** Low technical score, legacy tags.
    - 🟡 **Não Responsivo:** Missing viewport meta.
    - ⚪ **Sem SSL:** Insecure site.
- **PIP-TECH-2:** Add a "Sales Pitch" helper that suggests a reason to contact (e.g., "Seu site não funciona bem no celular").

### 3. Objective Functionality (ID: PIP-FUNC)
- **PIP-MASS-1:** Quick action buttons for each lead: WhatsApp, Email, "Mark as Contacted".
- **PIP-MASS-2:** Filtering by "Industry" and "Score" directly in the feed.
- **PIP-MASS-3:** "Hide/Archive" functionality for low-quality leads.

## Technical Details
- **Frontend:** Single-column responsive layout, Tailwind CSS, Lucide Icons.
- **API:** Reuse `/api/leads` with updated sorting and filtering.
