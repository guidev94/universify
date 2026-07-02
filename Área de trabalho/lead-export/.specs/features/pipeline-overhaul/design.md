# Design: Pipeline Overhaul

## Architecture
- **Kanban Engine:** Sortable.js for drag-and-drop.
- **Data Synchronization:** REST API for status updates, SSE for real-time lead updates.
- **Intelligence Layer:** `SiteVerifier` processes technical debt heuristics; `LeadScorer` calculates sales priority.

## UI Components

### Kanban Column
- Glassmorphic header with count badge and "Glow" indicator for active extraction.
- Context menu for bulk actions (Clear, Move All).

### Kanban Card (The "Premium" Card)
- **Top Bar:** Tier Badge (left), Score Circle (right).
- **Body:** Company Name (bold), Snippet of latest activity or contact info.
- **Badges:** 
  - `SITE ANTIGO`: Orange/Red glow.
  - `ADS SPENDER`: Amber/Gold glow.
  - `SSL INVALID`: Red warning icon.
- **Footer:** Contact action buttons (Quick WhatsApp, Quick Email) and source icon.

## Data Schema Updates (Implicit)
- Already have `site_quality`, `tech_score`, `has_ads` in `leads` table.
- Need to ensure `site_quality` is accurately populated.

## Sales Heuristics
- **Old Site:** `tech_score < 50` OR `site_quality == 'poor'`.
- **Qualified:** `score > 80` OR (`has_ads == 1` AND `has_whatsapp == 1`).
