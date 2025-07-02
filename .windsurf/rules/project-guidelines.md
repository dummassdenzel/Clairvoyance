---
trigger: always_on
---

# KPI Analytics System - Development Guidelines
Project: Local XAMPP-based KPI dashboard system

## API Structure:
- Follow existing /api/ folder structure with organized subdirectories
- controllers/ for business logic, routes/ for endpoint definitions
- middleware/ for auth/validation, models/ for data access
- utils/ for helper functions, services/ for reusable logic
- Use .htaccess for URL rewriting and API routing

## Key Constraints:
- Local XAMPP environment (backend in /api/ directory)
- Two roles: Editor (full access) + Viewer (read-only)
- Simple drag-and-drop dashboard builder
- Chart.js or ApexCharts for visualizations, jsPDF for exports
- Maximum 100 KPIs, 10 dashboards for simplicity

## Code Standards:
- Organize PHP code into controllers/models/services pattern
- Use middleware for authentication and role-based access control
- PHP sessions for auth, password_hash() for security
- Sanitize all inputs (SQL injection/XSS prevention)
- RESTful API endpoints following existing structure
- Responsive design with TailwindCSS utility classes
- SvelteKit reactivity for real-time updates

Refer to PROJECT_SPEC.md for detailed requirements and API specifications.