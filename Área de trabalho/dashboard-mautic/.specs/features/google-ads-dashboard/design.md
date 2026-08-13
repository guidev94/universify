# Design: Google Ads Dashboard Architecture

## 1. System Overview
The plugin will act as a bridge between the Google Ads API and the WordPress Admin UI.

## 2. Backend Components (PHP)
- **Plugin Core:** Entry point, hooks for menu creation (`add_menu_page`).
- **API Client:** Wrapper for Google Ads PHP SDK. Handles OAuth2 tokens and request building.
- **REST API Controller:** Exposes endpoints for the frontend to fetch data asynchronously.

## 3. Frontend Components (React)
- **Dashboard Hub:** Main container managing state (filters, date range).
- **Filter Bar:** Row of dropdowns for selecting account context.
- **Visualizer Engine:** uses Chart.js / Recharts for rendering the graphs.
- **Data Tables:** Custom data grid component with sorting and basic pagination.

## 4. Data Flow
1. User opens Dashboard in WP Admin.
2. React app loads and requests initial data from WP REST API.
3. WP REST API validates session, fetches from Google Ads API (or cache).
4. Data is returned in JSON format.
5. React components render the charts and tables.

## 5. UI/UX Style
- **Theme:** Google Ads Light Theme (Clean, white/light gray background).
- **Typography:** Google Sans or similar (Roboto fallback).
- **Shadows:** Soft, elevation-based hierarchy.
