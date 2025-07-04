# Performance Tracking System for General KPIs

## Overview

This project is a **KPI Visualization and Performance Tracking System** designed to help businesses and teams monitor, analyze, and report on key performance indicators. The system features customizable dashboards, flexible data import options, interactive visualizations, and user-role-based access management.

---

## Tech Stack

- **Frontend**: SvelteKit + TailwindCSS
- **Backend**: Vanilla PHP (REST API)
- **Database**: MySQL (via XAMPP phpMyAdmin)
- **Deployment**: Local XAMPP environment
- **Data Visualization**: JavaScript charting libraries (e.g., Chart.js, ApexCharts, or similar)

---

## Project Goals & Features

### 1. Dashboard with KPI Widgets
- Users can **add**, **remove**, and **customize** widgets.
- Each widget corresponds to a specific KPI.
- Support for different visualization types per widget (chart, number card, etc.)

### 2. Data Import Feature
- Upload CSV, Excel, or JSON files to populate data.
- Optional: Connect and fetch data from external APIs.
- Backend validation and preprocessing for imported data.

### 3. Interactive Charts and Graphs
- Dynamic visual representation of KPIs.
- Chart types: **Line**, **Bar**, **Pie**, and **Donut**.
- Tooltips, labels, and filtering options for usability.

### 4. Real-Time Update or Refresh
- Manual **refresh button** for users.
- Optional: Real-time data sync using polling or WebSocket.
- Live updates to charts and widgets without full reload.

### 5. Role-Based Access Control
- **Admin Role**:
    - Complete system management and configuration.
    - User management and access control.
    - Critical data governance.
    - Can create, edit, delete dashboards, KPIs, users, categories.
    - Can import/export data and manage data retention.
    - Has audit capabilities.
- **Editor Role**:
    - Content creation and curation.
    - Team-specific KPI management.
    - Report generation and analysis.
    - Can create and edit dashboards, KPIs, widgets.
    - Can import data and generate reports.
    - Cannot delete critical data or manage users.
- **Viewer Role**:
    - Monitor performance metrics.
    - Access assigned dashboards.
    - Generate basic reports.
    - Can view dashboards and create personal dashboards.
    - Can export data and generate standard reports.
    - Cannot create or modify shared content.
- Middleware logic in PHP API to manage permissions.
- SvelteKit layouts/components respond based on role.

### 6. Downloadable Reports
- Generate reports in **PDF** and **Excel (XLSX)** format.
- Export selected KPIs and visual charts.
- Includes metadata (timestamp, user, filters applied).

---

## Development Notes

- Ensure consistent JSON response structure from the PHP API.
- Handle frontend errors gracefully with SvelteKit.
- Use Tailwind utility classes for responsive layouts.
- Prefer secure storage for user credentials (password_hash in PHP).
- Integrate file handling (CSV, Excel) using `PhpSpreadsheet`.

---

## Notes

- Maintain awareness of:
  - Role-specific views and permissions
  - KPI widget configurations per user
  - Dynamic rendering of charts based on API data
  - File upload and parsing logic
  - Export logic for reports
- Track real-time state sync and manual refresh mechanisms.
- Optimize dashboard layout using responsive design and TailwindCSS grids.
