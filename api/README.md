
## 🚀 Quick Start

### **Prerequisites**
- XAMPP (Apache + MySQL + PHP 8.0+)
- Composer (for dependency management)

### **Installation**

1. **Clone and Setup**
   ```bash
   cd C:\xampp\htdocs\clairvoyance-v3\api
   composer install
   ```

2. **Database Setup**
   ```bash
   # Create database
   mysql -u root -p
   CREATE DATABASE clairvoyance_v3;
   exit
   
   # Run migrations
   php scripts/migrate.php --fresh
   ```

3. **Environment Configuration**
   Create `api/.env` file:
   ```env
   DB_HOST=localhost
   DB_NAME=clairvoyance_v3
   DB_USER=root
   DB_PASS=
   ```

4. **Test the API**
   ```bash
   # Test routes
   php tests/test_routes.php
   
   # Test models
   php tests/ModelIntegrationTest.php
   ```

## 🔐 Authentication & Authorization

### **User Roles**
- **Admin**: Full system access, user management
- **Editor**: Create/edit dashboards and KPIs, manage viewers
- **Viewer**: Read-only access to assigned dashboards

### **Authentication Flow**
1. **Register**: `POST /api/auth/register`
2. **Login**: `POST /api/auth/login` (creates session)
3. **Access Protected Routes**: Session-based authentication
4. **Logout**: `POST /api/auth/logout`

### **Session Management**
- PHP sessions with secure cookies
- Automatic session validation
- Role-based route protection

## �� API Endpoints

### **Authentication (`/api/auth/`)**
| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/register` | Register new user | No |
| POST | `/login` | User login | No |
| POST | `/logout` | User logout | Yes |
| GET | `/me` | Get current user | Yes |

### **User Management (`/api/users/`)**
| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/` | List all users | Admin |
| GET | `/{id}` | Get user by ID | Admin |
| PUT | `/{id}` | Update user role | Admin |
| DELETE | `/{id}` | Delete user | Admin |

### **Admin Operations (`/api/admin/`)**
| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/users` | List all users | Admin |
| POST | `/users` | Create user | Admin |
| PUT | `/users/{id}` | Update user role | Admin |
| DELETE | `/users/{id}` | Delete user | Admin |

### **Dashboards (`/api/dashboards/`)**
| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/` | List user's dashboards | Yes |
| GET | `/{id}` | Get dashboard details | Yes |
| POST | `/` | Create dashboard | Editor |
| PUT | `/{id}` | Update dashboard | Editor |
| DELETE | `/{id}` | Delete dashboard | Editor |
| POST | `/{id}/viewers` | Assign viewer | Editor |
| DELETE | `/{id}/viewers/{userId}` | Remove viewer | Editor |
| POST | `/{id}/share` | Generate share token | Editor |
| POST | `/share/{token}` | Redeem share token | Yes |
| GET | `/{id}/report` | Get report data | Yes |

### **KPIs (`/api/kpis/`)**
| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/` | List user's KPIs | Yes |
| GET | `/{id}` | Get KPI details | Yes |
| POST | `/` | Create KPI | Editor |
| PUT | `/{id}` | Update KPI | Editor |
| DELETE | `/{id}` | Delete KPI | Editor |
| GET | `/{id}/entries` | List KPI entries | Yes |
| GET | `/{id}/aggregate` | Get aggregated data | Yes |

### **KPI Entries (`/api/kpi_entries/`)**
| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/` | Create KPI entry | Editor |
| POST | `/` | Upload CSV (multipart) | Editor |

## ��️ Database Schema

### **Users Table**
```sql
CREATE TABLE users (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor', 'viewer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### **Dashboards Table**
```sql
CREATE TABLE dashboards (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    layout LONGTEXT NOT NULL, -- JSON layout configuration
    user_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### **KPIs Table**
```sql
CREATE TABLE kpis (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    direction ENUM('higher_is_better', 'lower_is_better') NOT NULL,
    target DECIMAL(10,2) NOT NULL,
    rag_red DECIMAL(10,2) NOT NULL,
    rag_amber DECIMAL(10,2) NOT NULL,
    format_prefix VARCHAR(10),
    format_suffix VARCHAR(10),
    user_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### **KPI Entries Table**
```sql
CREATE TABLE kpi_entries (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    kpi_id INT(11) NOT NULL,
    date DATE NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kpi_id) REFERENCES kpis(id) ON DELETE CASCADE,
    UNIQUE KEY unique_kpi_date (kpi_id, date)
);
```

## �� Testing

### **Run All Tests**
```bash
# Model integration tests
php tests/ModelIntegrationTest.php

# Individual model tests
php tests/UserModelTest.php
php tests/DashboardModelTest.php
php tests/KpiModelTest.php
php tests/KpiEntryModelTest.php
php tests/ShareTokenModelTest.php

# Route tests
php tests/test_routes.php
```

### **Test Coverage**
- ✅ **Model Layer**: CRUD operations, validation, relationships
- ✅ **Service Layer**: Business logic, authorization, error handling
- ✅ **Database**: Migrations, constraints, indexes
- ✅ **API Routes**: Endpoint availability, error responses

## 🔧 Development

### **Database Migrations**
```bash
# Run all pending migrations
php scripts/migrate.php

# Run migrations with fresh database
php scripts/migrate.php --fresh

# Rollback last migration
php scripts/rollback.php
```

### **Code Standards**
- **PSR-4 Autoloading** for all classes
- **Type Hints** for all method parameters and return types
- **Dependency Injection** for all dependencies
- **Single Responsibility** principle for all classes
- **Comprehensive Error Handling** with try/catch blocks
- **Consistent JSON Responses** across all endpoints

### **Architecture Patterns**
- **Service Layer**: Business logic separated from controllers
- **Repository Pattern**: Data access abstracted in models
- **Dependency Injection**: Loose coupling between components
- **MVC Pattern**: Clear separation of concerns

## 📝 API Response Format

### **Success Response**
```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {
        // Response data here
    }
}
```

### **Error Response**
```json
{
    "success": false,
    "error": "Error message describing what went wrong"
}
```

### **HTTP Status Codes**
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `405` - Method Not Allowed
- `500` - Internal Server Error

## 🚀 Production Deployment

### **Security Considerations**
- ✅ **Password Hashing** with `password_hash()`
- ✅ **SQL Injection Prevention** with PDO prepared statements
- ✅ **XSS Protection** with input sanitization
- ✅ **Session Security** with secure cookies
- ✅ **Role-Based Access Control**

### **Performance Optimizations**
- ✅ **Database Indexes** on frequently queried columns
- ✅ **Prepared Statements** for query optimization
- ✅ **Connection Pooling** via PDO
- ✅ **Efficient Queries** with proper JOINs

### **Monitoring & Logging**
- Error logging enabled in `index.php`
- Request logging for debugging
- Database query logging (development only)

## 🤝 Contributing

### **Development Workflow**
1. Create feature branch
2. Implement changes with tests
3. Run test suite
4. Submit pull request

### **Code Review Checklist**
- [ ] All tests pass
- [ ] Code follows PSR-4 standards
- [ ] Type hints are present
- [ ] Error handling is comprehensive
- [ ] Documentation is updated

## 📞 Support

For issues or questions:
1. Check the test suite for expected behavior
2. Review the API documentation
3. Check database migrations are up to date
4. Verify environment configuration

---

**Built with ❤️ using modern PHP practices and clean architecture principles.**