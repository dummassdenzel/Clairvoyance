Simplified KPI Analytics System Specification
build this app according to this spec:


1. Overview
The KPI Analytics System is a web-based application for tracking and visualizing Key Performance Indicators (KPIs) using dashboards. It supports two user roles: Editors, who create dashboards and input data, and Viewers, who view dashboards in read-only mode. The system runs locally on a XAMPP environment, making it accessible for a college student with no cloud access. It uses SvelteKit + TailwindCSS for the frontend, Vanilla PHP for the backend REST API, MySQL for data storage, and Chart.js for visualizations.
2. Objectives

Enable Editors to create and manage KPI dashboards with simple widgets (charts, tables).
Allow Editors to input KPI data manually or via CSV uploads.
Provide Viewers with read-only access to dashboards for monitoring KPIs.
Ensure a lightweight, secure system running on a local XAMPP server.
Support basic visualizations and exportable reports in PDF format.
Keep development simple for a student with limited resources.

3. User Roles and Permissions
3.1 Editor

Responsibilities:
Create, edit, and delete KPI dashboards.
Design dashboards with drag-and-drop widgets (line charts, bar charts, tables).
Input KPI data manually or via CSV uploads.
Set KPI targets and Red/Amber/Green (RAG) thresholds.
Share dashboards with Viewers by assigning access.
Export dashboards as PDF reports.


Permissions:
Full read-write access to dashboards, KPIs, and data.
Manage user access (assign Viewer roles).



3.2 Viewer

Responsibilities:
View assigned dashboards with interactive charts and tables.
Access exported PDF reports.


Permissions:
Read-only access to assigned dashboards.
No access to data input or dashboard editing.



4. Functional Requirements
4.1 Dashboard Management

Creation: Editors can create dashboards using a drag-and-drop interface with Chart.js-based widgets.
Customization: Support basic widget customization (e.g., chart type, colors, titles).
Templates: Provide 2-3 predefined dashboard templates (e.g., Sales, Academic Performance).
Sharing: Editors can assign dashboards to Viewers via a user management interface.
Export: Export dashboards as PDFs using a JavaScript library (e.g., jsPDF).

4.2 Data Input

Manual Entry: Editors can input KPI data via a form (e.g., KPI name, value, date).
CSV Upload: Support bulk data import via CSV files with a predefined format (e.g., columns: KPI ID, Date, Value).
Data Validation: Basic checks (e.g., numeric values, required fields) before saving.

4.3 Data Visualization

Widgets: Support line charts, bar charts, and tables using Chart.js.
RAG Indicators: Color-coded indicators (Red, Amber, Green) based on KPI thresholds.
Interactivity: Allow Viewers to hover over charts for data details (tooltips).
Real-Time Updates: Reflect data changes in dashboards without page refresh (via SvelteKit reactivity).

4.4 User Management

Authentication: Simple email/password login (stored securely in MySQL).
Roles: Assign users as Editor or Viewer during registration.
Access Control: Restrict Viewer access to read-only dashboards via PHP session checks.

5. Non-Functional Requirements

Performance: Dashboards load within 3 seconds on a local XAMPP server.
Security:
Passwords hashed with PHP’s password_hash().
Role-based access enforced in PHP API endpoints.
Sanitize user inputs to prevent SQL injection and XSS.


Usability: Simple, intuitive interface requiring minimal setup (inspired by SimpleKPI’s ease of use).
Compatibility: Works on Chrome and Firefox in a local XAMPP environment.
Storage: MySQL database with minimal schema for KPIs, users, and dashboards.

6. Technical Architecture
6.1 Frontend

Framework: SvelteKit for reactive UI and routing.
Styling: TailwindCSS for responsive, utility-first design.
Libraries:
Chart.js for data visualizations (line charts, bar charts, tables).
jsPDF for PDF report generation.
interact.js for drag-and-drop widget placement.


Features:
Dashboard builder with drag-and-drop widgets for Editors.
Read-only dashboard view for Viewers with interactive charts.
Responsive design for desktop (mobile support optional due to local constraints).



6.2 Backend

Framework: Vanilla PHP for a REST API, served via XAMPP’s Apache, in /clairvoyance-v3/api/
Database: MySQL (managed via phpMyAdmin).
API Endpoints:
POST /api/kpis.php: Create a new KPI (Editor only).
Body: { "name": "Sales", "target": 1000, "rag_red": 500, "rag_amber": 800 }
Response: 201 Created, { "id": 1, ... }


POST /api/kpi_entries.php: Add KPI data entry (Editor only).
Body: { "kpi_id": 1, "date": "2025-07-01", "value": 950 }
Response: 201 Created, { "id": 1, ... }


GET /api/dashboards.php?id={id}: Retrieve dashboard data (Editor or Viewer).
Response: 200 OK, { "id": 1, "name": "Sales Dashboard", "widgets": [...] }


POST /api/dashboards.php: Create a new dashboard (Editor only).
Body: { "name": "Sales Dashboard", "widgets": [...] }
Response: 201 Created, { "id": 1, ... }


POST /api/users.php: Register a user (Editor assigns role).
Body: { "email": "user@example.com", "password": "pass", "role": "viewer" }
Response: 201 Created, { "id": 1, ... }




Authentication: PHP sessions with role checks for API access.

6.3 Database (MySQL)

Tables:
users:
id (INT, PK, Auto-Increment)
email (VARCHAR, Unique)
password (VARCHAR, Hashed)
role (ENUM: ‘editor’, ‘viewer’)


kpis:
id (INT, PK, Auto-Increment)
name (VARCHAR)
target (DECIMAL)
rag_red (DECIMAL)
rag_amber (DECIMAL)
user_id (INT, FK to users)


kpi_entries:
id (INT, PK, Auto-Increment)
kpi_id (INT, FK to kpis)
date (DATE)
value (DECIMAL)


dashboards:
id (INT, PK, Auto-Increment)
name (VARCHAR)
layout (JSON, stores widget configurations)
user_id (INT, FK to users)


dashboard_access:
dashboard_id (INT, FK to dashboards)
user_id (INT, FK to users)




Notes: JSON for layout keeps the schema simple; CSV uploads are parsed in PHP and inserted into kpi_entries.



7. User Interface

Editor Interface:
Dashboard Builder: Drag-and-drop canvas with Chart.js widgets (line/bar charts, tables).
KPI Form: Input fields for name, target, RAG thresholds.
Data Entry Form: Fields for KPI ID, date, and value; CSV upload option.
User Management: Table to assign Viewer access to dashboards.


Viewer Interface:
Dashboard View: Read-only display of charts and tables with hover tooltips.
Report Export: Button to download dashboard as PDF.


Styling: TailwindCSS for clean, responsive design (e.g., card-based layouts, minimal color palette).



8. Assumptions and Constraints

Assumptions:
XAMPP is installed and configured correctly.
Users access the system on a single local machine (no multi-user scaling needed).


Constraints:
No cloud integrations or third-party APIs due to local setup.
Limited to CSV for bulk data input (no real-time API integrations).
Maximum 100 KPIs and 10 dashboards for simplicity.



10. References

SimpleKPI Features (for inspiration): https://www.simplekpi.com/
SvelteKit Documentation: https://kit.svelte.dev/docs
Chart.js Documentation: https://www.chartjs.org/docs/latest/
TailwindCSS Documentation: https://tailwindcss.com/docs
PHP MySQL Guide: https://www.php.net/manual/en/book.mysql.php