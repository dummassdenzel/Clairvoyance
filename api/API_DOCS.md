# Clairvoyance API Documentation

This document provides instructions on how to test the Clairvoyance API endpoints using a tool like Postman.

## Authentication (Session-based)

The API now uses session-based authentication. This means you must first log in to create a session. Postman will automatically handle the session cookie (`PHPSESSID`) for subsequent requests.

### 1. Login

-   **Method:** `POST`
-   **URL:** `http://localhost/clairvoyance-v3/api/auth/login`
-   **Body (raw, JSON):**
    ```json
    {
        "email": "your_email@example.com",
        "password": "your_password"
    }
    ```
-   **Response:**
    -   On success, you will receive a `200 OK` status and user details. Postman will automatically save the `PHPSESSID` cookie.
    -   On failure, you will receive a `401 Unauthorized` error.

### 2. Logout

-   **Method:** `POST`
-   **URL:** `http://localhost/clairvoyance-v3/api/auth/logout`
-   **Description:** This will destroy the current session.

---

## Endpoints

**Note:** All subsequent requests will automatically be authenticated by Postman as long as the `PHPSESSID` cookie is present.

### Users

-   **Controller:** `UserController.php`
-   **Route File:** `api/routes/users.php`
-   **Permissions:** All endpoints require **Admin** role.

| Method | Endpoint                    | Description              | Body (JSON) Example                               |
| :----- | :-------------------------- | :----------------------- | :------------------------------------------------ |
| `GET`  | `/api/users`                | List all users           | N/A                                               |
| `GET`  | `/api/users/{id}`           | Get a specific user      | N/A                                               |
| `POST` | `/api/users`                | Create a new user        | `{"email": "new.viewer@example.com", "password": "secure_password", "role": "viewer"}` |
| `PUT`  | `/api/users/{id}`           | Update a user's details  | `{"email": "updated.viewer@example.com", "role": "editor"}` |
| `DELETE`| `/api/users/{id}`           | Delete a user            | N/A                                               |

---

### Dashboards

-   **Controller:** `DashboardController.php`
-   **Route File:** `api/routes/dashboards.php`

| Method | Endpoint                    | Required Role | Description              | Body (JSON) Example                               |
| :----- | :-------------------------- | :------------ | :----------------------- | :------------------------------------------------ |
| `GET`  | `/api/dashboards`           | Viewer+       | List all accessible dashboards | N/A                                               |
| `GET`  | `/api/dashboards/{id}`      | Viewer+       | Get a specific dashboard | N/A                                               |
| `POST` | `/api/dashboards`           | Editor+       | Create a new dashboard   | ```json
{
    "name": "Q3 Sales Performance",
    "layout": [
        {
            "type": "bar",
            "title: "Sales",
            "kpi_id": 1,
            "position": 0
        }
    ]
}

UPDATE `dashboards` SET `layout`='[{"type":"bar", "title": "Total Sales", "kpi_id":1,"position":0}]' WHERE 1



```   |
| `PUT`  | `/api/dashboards/{id}`      | Editor+       | Update a dashboard (Not Implemented) | `{"name": "Q3 2025 Sales Performance"}`                        |
| `DELETE`| `/api/dashboards/{id}`      | Editor+       | Delete a dashboard (Not Implemented) | N/A                                               |

#### Dashboard Viewers (Sub-resource)

| Method | Endpoint                            | Required Role | Description              | Body (JSON) Example                               |
| :----- | :---------------------------------- | :------------ | :----------------------- | :------------------------------------------------ |
| `POST` | `/api/dashboards/{id}/viewers`      | Editor+       | Assign a viewer to a dashboard | `{"user_id": 3}`                                  |
| `DELETE`| `/api/dashboards/{id}/viewers/{userId}` | Editor+       | Remove a viewer from a dashboard | N/A                                               |

---

### KPIs

-   **Controller:** `KpiController.php`
-   **Route File:** `api/routes/kpis.php`

| Method | Endpoint                    | Required Role | Description              | Body (JSON) Example                               |
| :----- | :-------------------------- | :------------ | :----------------------- | :------------------------------------------------ |
| `GET`  | `/api/kpis`                 | Viewer+       | List all KPIs            | N/A                                               |
| `GET`  | `/api/kpis/{id}`            | Viewer+       | Get a specific KPI (Not Implemented) | N/A                                               |
| `POST` | `/api/kpis`                 | Editor+       | Create a new KPI         | `{"name": "Avg. Response Time", "target": 120, "rag_red": 180, "rag_amber": 150}` |
| `PUT`  | `/api/kpis/{id}`            | Editor+       | Update a KPI (Not Implemented) | `{"target": 110}`                    |
| `DELETE`| `/api/kpis/{id}`            | Admin         | Delete a KPI (Not Implemented) | N/A                                               |

---

### KPI Entries

-   **Controller:** `KpiEntryController.php`
-   **Route File:** `api/routes/kpi_entries.php`

| Method | Endpoint                    | Required Role | Description              | Body (JSON) Example                               |
| :----- | :-------------------------- | :------------ | :----------------------- | :------------------------------------------------ |
| `GET`  | `/api/kpis/{kpi_id}/entries`      | Viewer+       | List all entries for a specific KPI | N/A                                               |
| `POST` | `/api/kpi_entries`          | Editor+       | Add a new entry for a KPI| `{"kpi_id": 1, "date": "2025-07-03", "value": 115}` |
| `POST` | `/api/kpi_entries`          | Editor+       | Bulk upload entries via CSV | `multipart/form-data` with a `file` field containing the CSV. The CSV must have a header: `kpi_id,date,value` |
| `PUT`  | `/api/kpi_entries/{id}`     | Editor+       | Update an entry (Not Implemented) | `{"value": 118}`                                   |
| `DELETE`| `/api/kpi_entries/{id}`     | Editor+       | Delete an entry (Not Implemented) | N/A                                               |


