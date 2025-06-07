# Clairvoyance KPI Tracking System - Project Status

## 1. Overview

This document summarizes the current development status of the Clairvoyance KPI Visualization and Performance Tracking System. The backend API and frontend Svelte data stores are largely in place, providing a robust foundation for UI development.

**Primary Goal**: To monitor, analyze, and report on key performance indicators through customizable dashboards, flexible data import, interactive visualizations, and role-based access.

## 2. Tech Stack

-   **Frontend**: SvelteKit + TailwindCSS
-   **Backend**: Vanilla PHP (REST API)
-   **Database**: MySQL (via XAMPP phpMyAdmin)
-   **Data Visualization**: JavaScript charting libraries (to be integrated into UI)

## 3. Core Features & Functionality Status

### 3.1. Backend API (PHP)

-   **Authentication**: JWT-based authentication. Login endpoint (`POST /auth/login`) validates credentials and returns a token and user details (including role).
-   **Role-Based Access Control (RBAC)**: Three roles are implemented:
    -   `admin`: Full access.
    -   `editor`: Can create/edit content (KPIs, dashboards, widgets, categories), import data.
    -   `viewer`: Read-only access to dashboards, KPIs, reports; can export data.
    Middleware enforces permissions at the API endpoint level.
-   **User Management**: CRUD operations for users (`GET /users`, `POST /users`, `PUT /users/{id}`, `DELETE /users/{id}`), admin-only.
-   **Category Management**: CRUD operations for KPI categories (`GET /categories`, etc.).
-   **KPI Management**: CRUD operations for KPIs. Endpoint for adding measurements to KPIs (`POST /kpis/{kpi_id}/measurements`).
-   **Dashboard Management**: CRUD operations for dashboards (`GET /dashboards`, etc.). Endpoint to retrieve all widgets for a specific dashboard (`GET /dashboards/{id}/widgets`).
-   **Widget Management**: CRUD operations for widgets (`GET /widgets`, `POST /widgets`, etc.). Widgets are associated with dashboards and KPIs, support various types (`line`, `bar`, `pie`, `donut`, `card`), and have configurable settings, positions, and sizes.
-   **Data Import**: Generic endpoint (`POST /import`) supporting file uploads (CSV, Excel, JSON) for different data types (e.g., `kpi_measurements`, `dashboard_structure`).
-   **Data Export**: Generic endpoint (`GET /export`) to export various entities (KPIs, dashboard data, measurements) in multiple formats (CSV, XLSX, JSON, PDF). Also, a specific endpoint `GET /export/dashboard/{id}`.
-   **Report Generation**: Backend logic for generating reports (details depend on `ReportController` specifics, but export functionality is available).
-   **API Documentation**: Detailed endpoint specifications are available in `api/README.md`.

### 3.2. Frontend (SvelteKit)

-   **API Service (`web/src/lib/services/api.ts`)**: A generic service handles all HTTP requests (GET, POST, PUT, DELETE) to the backend API. It manages base URL, headers (including Authorization with JWT token)

-   **Svelte Stores (`web/src/lib/stores/`)**: These stores manage application state and provide functions to interact with the backend API. All major entities have corresponding stores:
    -   `auth.ts`: Manages user authentication state (token, user object, isAuthenticated), login/logout, session verification.
    -   `user.ts`: Handles fetching and managing user data (for admin UI).
    -   `category.ts`: Manages KPI categories.
    -   `kpi.ts`: Manages KPIs, including fetching and adding KPI measurements.
    -   `dashboard.ts`: Manages dashboards (CRUD), including fetching all widgets for a specific dashboard (`fetchWidgetsForDashboard`).
    -   `widget.ts`: Manages widgets (CRUD), fetching individual widgets, and fetching all widgets accessible by the user. Defines `WidgetCreateInput` and `WidgetUpdateInput`.
    -   `report.ts`: Intended for report-specific logic (details to be confirmed during UI build for report generation features beyond basic export).
    -   `data.ts`: Handles generic data import (`importData`) and export (`exportData`) functionalities, interfacing with the backend's `/import` and `/export` endpoints.

-   **Type Definitions**: TypeScript interfaces are defined for key data structures (e.g., `User`, `Dashboard`, `Widget`, `Kpi`, `Category`) within the stores, aligned with API responses.

## 4. Key Design Decisions & Patterns

-   **Separation of Concerns**: Backend API handles business logic and data persistence. Frontend SvelteKit application handles presentation and user interaction, using stores as a bridge to the API.
-   **State Management**: Svelte stores are the primary mechanism for managing client-side state and reactivity.
-   **RESTful API**: The backend exposes a RESTful API, which the frontend consumes.
-   **Token-based Authentication**: JWTs are used for securing API endpoints.

## 5. Next Steps: UI Implementation Plan

The immediate focus is to build the SvelteKit UI components and pages, leveraging the existing stores. The suggested implementation order is:

1.  **Core Authentication & App Shell** (Login, Layout, Auth Guarding, Logout).
2.  **Dashboard Listing & Viewing** (Read-only first).
3.  **Widget Data Visualization** (Integrate charting library, create widget components).
4.  **Dashboard Management (CRUD)**.
5.  **Widget Management (CRUD within a Dashboard)**.
6.  **KPI Management & Measurement Addition**.
7.  **Data Import UI**.
8.  **Report Export UI**.
9.  **User Management UI (Admin)**.
10. **Category Management UI (if needed)**.
11. **Role-Based UI Polish & Refinements**.

This README provides the necessary context to begin UI development with a clear understanding of the available backend services and frontend data management capabilities.
