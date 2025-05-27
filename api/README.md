The Clairvoyance API is a RESTful service that powers a KPI (Key Performance Indicator) visualization and tracking system. It provides endpoints for managing users, dashboards, KPIs, widgets, and reports with role-based access control.

## Base URL
`http://localhost/clairvoyance/api`

## Authentication
All endpoints (except login) require a valid JWT token in the Authorization header:

```
Authorization: Bearer <token>
```

## Response Format
All API responses follow a standard format:

**Success Response:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

**Error Response:**
```json
{
  "success": false,
  "error": { 
    "code": 400,
    "message": "Error description"
  }
}
```

## Endpoints

### AUTHENTICATION: 
#### `POST /auth/login`
Login with username and password

**Request:**
```json
{
  "username": "lee",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": 1,
      "username": "testuser",
      "email": "user@example.com",
      "role": "viewer"
    }
  },
  "message": "Login successful"
}
```

### USERS: 
#### `GET /users`
Get all users (Admin only)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com",
      "role": "admin",
      "created_at": "2025-04-11 15:34:53"
    },
    {
      "id": 2,
      "username": "viewer",
      "email": "viewer@example.com",
      "role": "viewer",
      "created_at": "2025-05-16 18:23:27"
    }
  ],
  "message": "Users retrieved successfully"
}
```

#### `GET /users/{id}`
Get a specific user by ID (Admin only)

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "admin",
    "email": "admin@example.com",
    "role": "admin",
    "created_at": "2025-04-11 15:34:53"
  },
  "message": "User retrieved successfully"
}
```

#### `POST /users`
Create a new user (Admin only)

**Request:**
```json
{
  "username": "newuser",
  "email": "newuser@example.com",
  "password": "securepassword",
  "role": "viewer"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "username": "newuser",
    "email": "newuser@example.com",
    "role": "viewer",
    "created_at": "2025-05-25 18:00:00"
  },
  "message": "User created successfully"
}
```

#### `PUT /users/{id}`
Update a specific user by ID (Admin only)

**Request:**
```json
{
  "email": "updated@example.com",
  "role": "admin"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "username": "newuser",
    "email": "updated@example.com",
    "role": "admin",
    "created_at": "2025-05-25 18:00:00"
  },
  "message": "User updated successfully"
}
```

#### `DELETE /users/{id}`
Delete a specific user by ID (Admin only)

**Response:**
```json
{
  "success": true,
  "data": null,
  "message": "User deleted successfully"
}
```

### CATEGORIES:
#### `GET /categories`
Get all categories

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Software",
      "user_id": null,
      "username": null
    },
    {
      "id": 2,
      "name": "Business",
      "user_id": null,
      "username": null
    },
    {
      "id": 3,
      "name": "Marketing",
      "user_id": 2,
      "username": "lee"
    }
  ],
  "message": "Categories retrieved successfully"
}
```

#### `GET /categories/{id}`
Get a specific category by ID

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Software",
    "user_id": null,
    "username": null
  },
  "message": "Category retrieved successfully"
}
```

#### `GET /categories/{id}/kpis`
Get all KPIs for a specific category

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "category_id": 1,
      "name": "Monthly Active Users",
      "unit": "users",
      "target": 1000,
      "created_at": "2025-05-01 10:00:00",
      "username": "lee"
    },
    {
      "id": 3,
      "user_id": 1,
      "category_id": 1,
      "name": "Code Coverage",
      "unit": "%",
      "target": 80,
      "created_at": "2025-05-10 14:20:00",
      "username": "lee"
    }
  ],
  "message": "KPIs retrieved successfully"
}
```

#### `POST /categories`
Create a new category (Admin only)

**Request:**
```json
{
  "name": "Sales",
  "is_system": false
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 4,
    "name": "Sales",
    "user_id": 2,
    "username": "lee"
  },
  "message": "Category created successfully"
}
```

#### `PUT /categories/{id}`
Update a specific category by ID (Admin only)

**Request:**
```json
{
  "name": "Sales & Marketing"
}
```

**Response:**
```json
{
  "success": true,
  "data": null,
  "message": "Category updated successfully"
}
```

#### `DELETE /categories/{id}`
Delete a specific category by ID (Admin only)

**Response:**
```json
{
  "success": true,
  "data": null,
  "message": "Category deleted successfully"
}
```

### KPIs: 
###  `GET /kpis`
Get all KPIs

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "category_id": 1,
      "name": "Monthly Active Users",
      "unit": "users",
      "target": 1000,
      "created_at": "2025-05-01 10:00:00",
      "category": {
        "id": 1,
        "name": "Software"
      }
    },
    {
      "id": 2,
      "user_id": 1,
      "category_id": 2,
      "name": "Revenue Growth",
      "unit": "%",
      "target": 15,
      "created_at": "2025-05-02 11:30:00",
      "category": {
        "id": 2,
        "name": "Business"
      }
    }
  ],
  "message": "KPIs retrieved successfully"
}
```

#### `GET /kpis/{id}`
Get a specific KPI by ID

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "category_id": 1,
    "name": "Monthly Active Users",
    "unit": "users",
    "target": 1000,
    "created_at": "2025-05-01 10:00:00",
    "category": {
      "id": 1,
      "name": "Software"
    },
    "measurements": [
      {
        "id": 1,
        "value": 850,
        "timestamp": "2025-05-01 00:00:00"
      },
      {
        "id": 2,
        "value": 920,
        "timestamp": "2025-05-15 00:00:00"
      }
    ]
  },
  "message": "KPI retrieved successfully"
}
```

#### `POST /kpis`
Create a new KPI (Admin only)

**Request:**
```json
{
  "name": "Customer Satisfaction",
  "category_id": 2,
  "unit": "score",
  "target": 4.5
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "user_id": 1,
    "category_id": 2,
    "name": "Customer Satisfaction",
    "unit": "score",
    "target": 4.5,
    "created_at": "2025-05-25 18:05:00"
  },
  "message": "KPI created successfully"
}
```

#### `PUT /kpis/{id}`
Update a specific KPI by ID (Admin only)

**Request:**
```json
{
  "name": "Customer Satisfaction Score",
  "target": 4.8
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "user_id": 1,
    "category_id": 2,
    "name": "Customer Satisfaction Score",
    "unit": "score",
    "target": 4.8,
    "created_at": "2025-05-25 18:05:00"
  },
  "message": "KPI updated successfully"
}
```

#### `DELETE /kpis/{id}`
Delete a specific KPI by ID (Admin only)

**Response:**
```json
{
  "success": true,
  "data": null,
  "message": "KPI deleted successfully"
}
```

### DASHBOARDS: 
#### `GET /dashboards`
Get all dashboards for the current user

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "name": "Marketing Overview",
      "description": "Marketing team KPIs",
      "is_default": true,
      "created_at": "2025-05-10 09:00:00"
    },
    {
      "id": 2,
      "user_id": 1,
      "name": "Development Metrics",
      "description": "Software development metrics",
      "is_default": false,
      "created_at": "2025-05-12 14:30:00"
    }
  ],
  "message": "Dashboards retrieved successfully"
}
```

#### `GET /dashboards/{id}`
Get a specific dashboard by ID with its widgets

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "name": "Marketing Overview",
    "description": "Marketing team KPIs",
    "is_default": true,
    "created_at": "2025-05-10 09:00:00",
    "widgets": [
      {
        "id": 1,
        "dashboard_id": 1,
        "kpi_id": 2,
        "title": "Revenue Growth",
        "widget_type": "line",
        "position_x": 0,
        "position_y": 0,
        "width": 2,
        "height": 2,
        "settings": {
          "timeRange": "month",
          "showTarget": true
        },
        "created_at": "2025-05-10 09:15:00",
        "kpi": {
          "id": 2,
          "name": "Revenue Growth",
          "unit": "%"
        }
      }
    ]
  },
  "message": "Dashboard retrieved successfully"
}
```

#### `POST /dashboards`
Create a new dashboard

**Request:**
```json
{
  "name": "Sales Dashboard",
  "description": "Sales team performance metrics",
  "is_default": false
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "user_id": 1,
    "name": "Sales Dashboard",
    "description": "Sales team performance metrics",
    "is_default": false,
    "created_at": "2025-05-25 18:10:00"
  },
  "message": "Dashboard created successfully"
}
```

#### `PUT /dashboards/{id}`
Update a specific dashboard by ID

**Request:**
```json
{
  "name": "Sales Performance",
  "is_default": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "user_id": 1,
    "name": "Sales Performance",
    "description": "Sales team performance metrics",
    "is_default": true,
    "created_at": "2025-05-25 18:10:00"
  },
  "message": "Dashboard updated successfully"
}
```

#### `DELETE /dashboards/{id}`
Delete a specific dashboard by ID

**Response:**
```json
{
  "success": true,
  "data": null,
  "message": "Dashboard deleted successfully"
}
```

### WIDGETS: 
#### `GET /widgets`
Get all widgets

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "dashboard_id": 1,
      "kpi_id": 2,
      "title": "Revenue Growth",
      "widget_type": "line",
      "position_x": 0,
      "position_y": 0,
      "width": 2,
      "height": 2,
      "settings": {
        "timeRange": "month",
        "showTarget": true
      },
      "created_at": "2025-05-10 09:15:00",
      "kpi": {
        "id": 2,
        "name": "Revenue Growth",
        "unit": "%"
      }
    },
    {
      "id": 2,
      "dashboard_id": 2,
      "kpi_id": 1,
      "title": "Active Users",
      "widget_type": "bar",
      "position_x": 0,
      "position_y": 0,
      "width": 2,
      "height": 1,
      "settings": {
        "timeRange": "week",
        "showTarget": true
      },
      "created_at": "2025-05-12 14:45:00",
      "kpi": {
        "id": 1,
        "name": "Monthly Active Users",
        "unit": "users"
      }
    }
  ],
  "message": "Widgets retrieved successfully"
}
```

#### `GET /widgets/{id}`
Get a specific widget by ID

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "dashboard_id": 1,
    "kpi_id": 2,
    "title": "Revenue Growth",
    "widget_type": "line",
    "position_x": 0,
    "position_y": 0,
    "width": 2,
    "height": 2,
    "settings": {
      "timeRange": "month",
      "showTarget": true
    },
    "created_at": "2025-05-10 09:15:00",
    "kpi": {
      "id": 2,
      "name": "Revenue Growth",
      "unit": "%",
      "target": 15
    },
    "data": [
      {
        "timestamp": "2025-04-01",
        "value": 12.3
      },
      {
        "timestamp": "2025-05-01",
        "value": 14.7
      }
    ]
  },
  "message": "Widget retrieved successfully"
}
```

#### `POST /widgets`
Create a new widget

**Request:**
```json
{
  "dashboard_id": 3,
  "kpi_id": 3,
  "title": "Customer Satisfaction Trend",
  "widget_type": "line",
  "position_x": 0,
  "position_y": 0,
  "width": 2,
  "height": 2,
  "settings": {
    "timeRange": "quarter",
    "showTarget": true,
    "colors": ["#4CAF50", "#F44336"]
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "dashboard_id": 3,
    "kpi_id": 3,
    "title": "Customer Satisfaction Trend",
    "widget_type": "line",
    "position_x": 0,
    "position_y": 0,
    "width": 2,
    "height": 2,
    "settings": {
      "timeRange": "quarter",
      "showTarget": true,
      "colors": ["#4CAF50", "#F44336"]
    },
    "created_at": "2025-05-25 18:15:00"
  },
  "message": "Widget created successfully"
}
```

#### `PUT /widgets/{id}`
Update a specific widget by ID

**Request:**
```json
{
  "title": "Customer Satisfaction Score",
  "widget_type": "bar",
  "width": 3,
  "settings": {
    "timeRange": "year",
    "showTarget": false
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "dashboard_id": 3,
    "kpi_id": 3,
    "title": "Customer Satisfaction Score",
    "widget_type": "bar",
    "position_x": 0,
    "position_y": 0,
    "width": 3,
    "height": 2,
    "settings": {
      "timeRange": "year",
      "showTarget": false,
      "colors": ["#4CAF50", "#F44336"]
    },
    "created_at": "2025-05-25 18:15:00"
  },
  "message": "Widget updated successfully"
}
```

#### `DELETE /widgets/{id}`
Delete a specific widget by ID

**Response:**
```json
{
  "success": true,
  "data": null,
  "message": "Widget deleted successfully"
}
```

### REPORTS: 
#### `GET /reports`
Get all reports

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "dashboard_id": 1,
      "name": "Marketing Report - May 2025",
      "format": "pdf",
      "created_at": "2025-05-20 10:30:00",
      "download_url": "/api/reports/1/download"
    },
    {
      "id": 2,
      "user_id": 1,
      "dashboard_id": 2,
      "name": "Development Metrics - Q2 2025",
      "format": "xlsx",
      "created_at": "2025-05-22 14:15:00",
      "download_url": "/api/reports/2/download"
    }
  ],
  "message": "Reports retrieved successfully"
}
```

#### `GET /reports/{id}`
Get a specific report by ID

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "dashboard_id": 1,
    "name": "Marketing Report - May 2025",
    "format": "pdf",
    "created_at": "2025-05-20 10:30:00",
    "download_url": "/api/reports/1/download",
    "dashboard": {
      "id": 1,
      "name": "Marketing Overview"
    },
    "metadata": {
      "generated_by": "admin",
      "time_range": {
        "start": "2025-05-01",
        "end": "2025-05-31"
      },
      "kpi_count": 3
    }
  },
  "message": "Report retrieved successfully"
}
```

#### `POST /reports`
Create a new report

**Request:**
```json
{
  "dashboard_id": 3,
  "name": "Sales Performance - May 2025",
  "format": "pdf",
  "time_range": {
    "start": "2025-05-01",
    "end": "2025-05-31"
  },
  "include_widgets": [3, 4, 5]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "user_id": 1,
    "dashboard_id": 3,
    "name": "Sales Performance - May 2025",
    "format": "pdf",
    "created_at": "2025-05-25 18:20:00",
    "download_url": "/api/reports/3/download",
    "metadata": {
      "generated_by": "admin",
      "time_range": {
        "start": "2025-05-01",
        "end": "2025-05-31"
      },
      "kpi_count": 3
    }
  },
  "message": "Report created successfully"
}
```

#### `GET /reports/{id}/download`
Download a specific report

**Response:**
Binary file stream with appropriate Content-Type header (application/pdf or application/vnd.openxmlformats-officedocument.spreadsheetml.sheet)

#### `PUT /reports/{id}`
Update a specific report by ID

**Request:**
```json
{
  "name": "Sales Performance Report - May 2025",
  "format": "xlsx"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "user_id": 1,
    "dashboard_id": 3,
    "name": "Sales Performance Report - May 2025",
    "format": "xlsx",
    "created_at": "2025-05-25 18:20:00",
    "download_url": "/api/reports/3/download"
  },
  "message": "Report updated successfully"
}
```

#### `DELETE /reports/{id}`
Delete a specific report by ID

**Response:**
```json
{
  "success": true,
  "data": null,
  "message": "Report deleted successfully"
}
```

## Data Import API

### `POST /import/data`
Import KPI data from a file (CSV, Excel, or JSON)

**Request:**
Multipart form data with:
- `file`: The file to upload
- `kpi_id`: The KPI ID to associate with the data
- `mapping`: JSON string defining column mappings

**Example mapping:**
```json
{
  "timestamp_column": "Date",
  "value_column": "Value",
  "timestamp_format": "Y-m-d"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "imported_rows": 24,
    "skipped_rows": 2,
    "errors": []
  },
  "message": "Data imported successfully"
}
```

## Data Import/Export API

### Import Endpoints

#### `POST /import/data`
Import KPI data from a file (CSV, Excel, or JSON)

**Request:**
Multipart form data with:
- `file`: The file to upload
- `kpi_id`: The KPI ID to associate with the data
- `mapping`: JSON string defining column mappings

**Example CSV/Excel mapping:**
```json
{
  "timestamp_column": "Date",
  "value_column": "Value",
  "timestamp_format": "Y-m-d"
}
```

**Example JSON mapping:**
```json
{
  "timestamp_field": "date",
  "value_field": "value",
  "timestamp_format": "Y-m-d"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "imported_rows": 24,
    "skipped_rows": 2,
    "errors": [],
    "kpi": {
      "id": 3,
      "name": "Customer Satisfaction",
      "unit": "score",
      "target": 4.5
    }
  },
  "message": "Data imported successfully"
}
```

#### `POST /import/validate`
Validate import data before actual import

**Request:**
Multipart form data with:
- `file`: The file to upload
- `kpi_id`: The KPI ID to associate with the data
- `mapping`: JSON string defining column mappings

**Response:**
```json
{
  "success": true,
  "data": {
    "file_name": "satisfaction_data.csv",
    "file_size": 1024,
    "file_type": "csv",
    "kpi": {
      "id": 3,
      "name": "Customer Satisfaction",
      "unit": "score",
      "target": 4.5
    },
    "mapping": {
      "timestamp_column": "Date",
      "value_column": "Value",
      "timestamp_format": "Y-m-d"
    }
  },
  "message": "File and mapping validated successfully"
}
```

#### `GET /import/templates`
Get import templates

**Response:**
```json
{
  "success": true,
  "data": {
    "csv": {
      "description": "CSV template for KPI data import",
      "format": "CSV (Comma Separated Values)",
      "example": "Date,Value\n2025-01-01,100\n2025-01-02,105\n2025-01-03,110",
      "mapping_example": {
        "timestamp_column": "Date",
        "value_column": "Value",
        "timestamp_format": "Y-m-d"
      }
    },
    "json": {
      "description": "JSON template for KPI data import",
      "format": "JSON (JavaScript Object Notation)",
      "example": "[{\"date\":\"2025-01-01\",\"value\":100},{\"date\":\"2025-01-02\",\"value\":105},{\"date\":\"2025-01-03\",\"value\":110}]",
      "mapping_example": {
        "timestamp_field": "date",
        "value_field": "value",
        "timestamp_format": "Y-m-d"
      }
    }
  },
  "message": "Import templates retrieved successfully"
}
```

### Export Endpoints

#### `GET /export/kpi/{id}`
Export KPI data to CSV, Excel, PDF or JSON

**Query Parameters:**
- `format`: Export format (csv, xlsx, pdf, or json, default: csv)
- `time_range[start]`: Start date for filtering data (optional)
- `time_range[end]`: End date for filtering data (optional)

**Response:**
File download with appropriate Content-Type header

#### `GET /export/dashboard/{id}`
Export dashboard data to CSV, Excel, PDF or JSON

**Query Parameters:**
- `format`: Export format (csv, xlsx, pdf, or json, default: csv)
- `time_range[start]`: Start date for filtering data (optional)
- `time_range[end]`: End date for filtering data (optional)

**Response:**
File download with appropriate Content-Type header

#### `POST /reports/generate`
Generate a report for a dashboard

**Request:**
```json
{
  "dashboard_id": 3,
  "name": "Sales Performance - May 2025",
  "format": "xlsx",
  "time_range": {
    "start": "2025-05-01",
    "end": "2025-05-31"
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "name": "Sales Performance - May 2025",
    "format": "csv",
    "download_url": "/api/reports/5/download"
  },
  "message": "Report generated successfully"
}
```

#### `GET /reports/{id}/download`
Download a generated report

**Response:**
File download with appropriate Content-Type header