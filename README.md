# 🚗 GES-CARS 2026 — Car Rental Management API

A comprehensive RESTful API for car rental fleet management, built with **Laravel 13** and **PHP 8.3**. This system provides a complete back-end solution for managing agencies, vehicles, clients, reservations, billing, insurance, maintenance, and more.

---

## 📑 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Architecture](#-architecture)
- [Prerequisites](#-prerequisites)
- [Installation](#-installation)
- [Configuration](#%EF%B8%8F-configuration)
- [Database Setup](#-database-setup)
- [Running the Application](#-running-the-application)
- [API Documentation](#-api-documentation)
- [Modules Overview](#-modules-overview)
- [Authentication & Authorization](#-authentication--authorization)
- [API Endpoints](#-api-endpoints)
- [DataTables Support](#-datatables-support)
- [Testing](#-testing)
- [Project Structure](#-project-structure)
- [Contributing](#-contributing)
- [License](#-license)

---

## ✨ Features

- **Multi-Agency Management** — Manage multiple rental agencies with dedicated managers
- **Fleet Management** — Complete vehicle lifecycle tracking (status, history, media)
- **Client Management** — Client profiles with blacklist support and document uploads
- **Reservation Workflow** — Full booking lifecycle: pending → confirmed → ongoing → completed/cancelled
- **Billing System** — 6 document types: Invoices (FA), Quotes (DV), Purchase Orders (BC), Delivery Notes (BL), Reception Notes (BR), Credit Notes (AV)
- **Insurance Tracking** — Policy management with expiration alerts
- **Maintenance Logging** — Scheduled and unscheduled maintenance records
- **Technical Inspections** — Vehicle inspection history and compliance tracking
- **Vignette Management** — Road tax sticker tracking per vehicle
- **Notifications** — Built-in notification system with logging
- **Audit Trail** — Full traceability of every data change (who, what, when)
- **File Uploads** — Media library for documents, photos, PDFs, and attachments
- **Server-Side DataTables** — Yajra DataTables integration on all modules
- **Role-Based Access Control** — 5 roles with 50+ granular permissions
- **JWT Authentication** — Stateless token-based authentication
- **Swagger/OpenAPI** — Auto-generated interactive API documentation

---

## 🛠 Tech Stack

| Technology | Version | Purpose |
|---|---|---|
| **PHP** | 8.3+ | Runtime |
| **Laravel** | 13.x | Framework |
| **MySQL** / SQLite | 8.0+ / 3 | Database |
| **tymon/jwt-auth** | 2.3 | JWT Authentication |
| **spatie/laravel-permission** | 7.2 | Roles & Permissions |
| **spatie/laravel-medialibrary** | 11.x | File/Media Management |
| **owen-it/laravel-auditing** | 14.x | Audit Trail |
| **yajra/laravel-datatables** | 13.x | Server-Side DataTables |
| **darkaonline/l5-swagger** | 11.x | Swagger/OpenAPI Docs |
| **spatie/laravel-notification-log** | 1.4 | Notification Logging |

---

## 🏗 Architecture

The project follows a **Modular Service-Repository** pattern:

```
Request → Controller → Service → Repository → Model → Database
                ↓
           Policy (Authorization)
                ↓
        Resource (JSON Transformation)
```

### Design Patterns Used

| Pattern | Description |
|---|---|
| **Repository** | Data access abstraction layer with base repository |
| **Service Layer** | Business logic encapsulation per module |
| **API Resource** | Consistent JSON response transformation |
| **Policy** | Model-level authorization |
| **Factory** | Test data generation |
| **Trait** | Reusable code (UUID, ApiResponse, MediaCollections) |
| **Modular** | Each feature is a self-contained module under `app/Modules/` |

---

## 📋 Prerequisites

- **PHP** >= 8.3 with extensions: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `gd`/`imagick`
- **Composer** >= 2.x
- **MySQL** 8.0+ (or SQLite for development)
- **Node.js** >= 18.x & **npm** (for frontend assets, optional)
- **Git**

---

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/your-org/ges-cars-2026.git
cd ges-cars-2026/car-rental-api
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node Dependencies (optional)

```bash
npm install
```

### 4. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Generate JWT Secret

```bash
php artisan jwt:secret
```

### 6. Quick Setup (alternative)

```bash
composer setup
```

This runs: `composer install` → copy `.env` → `key:generate` → `migrate` → `npm install` → `npm build`.

---

## ⚙️ Configuration

Edit your `.env` file with your environment settings:

```dotenv
# Application
APP_NAME="GES-CARS 2026"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ges_cars_2026
DB_USERNAME=root
DB_PASSWORD=

# Database (SQLite alternative)
# DB_CONNECTION=sqlite

# JWT
JWT_SECRET=your-jwt-secret-here
JWT_TTL=60

# Mail (for password reset)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@ges-cars.ma"
MAIL_FROM_NAME="${APP_NAME}"

# File Storage
FILESYSTEM_DISK=local
```

---

## 🗄 Database Setup

### Run Migrations

```bash
php artisan migrate
```

### Seed the Database

```bash
php artisan db:seed
```

### Fresh Migration + Seed (reset everything)

```bash
php artisan migrate:fresh --seed
```

### Seeders Included

| Seeder | Description |
|---|---|
| `PermissionSeeder` | Creates 50+ permissions across all modules |
| `RoleSeeder` | Creates 5 roles: `super-admin`, `admin`, `manager`, `agent`, `viewer` |
| `UserSeeder` | Creates default admin and test users |
| `AgencySeeder` | Sample rental agencies |
| `VehicleSeeder` | Sample fleet vehicles |
| `ClientSeeder` | Sample clients |
| `ReservationSeeder` | Sample reservations |

---

## ▶️ Running the Application

### Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`.

### Full Development Environment (with queue, logs, and Vite)

```bash
composer dev
```

This starts concurrently: Laravel server, queue worker, Pail log viewer, and Vite dev server.

### Generate Swagger Documentation

```bash
php artisan l5-swagger:generate
```

Access the interactive docs at: `http://localhost:8000/api/documentation`

---

## 📖 API Documentation

### Interactive Swagger UI

After generating the docs, visit:
```
http://localhost:8000/api/documentation
```

### Base URL

```
http://localhost:8000/api/v1
```

### Standard Response Format

**Success:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Paginated:**
```json
{
  "success": true,
  "message": "Success",
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

**Error:**
```json
{
  "success": false,
  "message": "Error description",
  "code": 400
}
```

**Validation Error (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  },
  "code": 422
}
```

> 📄 For full API documentation, see [`DOC/API_DOCUMENTATION.md`](DOC/API_DOCUMENTATION.md).

---

## 📦 Modules Overview

The application is organized into **12 self-contained modules**:

| # | Module | Description | Key Features |
|---|---|---|---|
| 1 | **Agency** | Rental agency management | CRUD, manager assignment, media uploads |
| 2 | **Vehicle** | Fleet vehicle management | CRUD, status tracking, history, media |
| 3 | **Client** | Client/customer management | CRUD, blacklist, document uploads |
| 4 | **Reservation** | Booking management | Full workflow, calendar view, statistics |
| 5 | **Billing** | Invoicing & billing | 6 doc types, auto-calc, payment tracking |
| 6 | **Insurance** | Insurance policy tracking | CRUD, expiration management |
| 7 | **Maintenance** | Vehicle maintenance | Scheduled/unscheduled, cost tracking |
| 8 | **TechnicalInspection** | Vehicle inspections | Compliance tracking, history |
| 9 | **Vignette** | Road tax stickers | Annual tracking per vehicle |
| 10 | **User** | User management | CRUD, role assignment, activity logs |
| 11 | **Notification** | System notifications | Email, in-app, logging |
| 12 | **Role** | Roles & permissions | RBAC management |

### Each Module Contains:

```
app/Modules/{ModuleName}/
├── Controllers/       # HTTP request handling
├── Repositories/      # Data access layer
├── Services/          # Business logic
├── Requests/          # Form validation
├── Resources/         # JSON transformation
├── Policies/          # Authorization rules (where applicable)
└── Models/            # Eloquent models (where applicable)
```

---

## 🔐 Authentication & Authorization

### JWT Authentication

All protected endpoints require a valid JWT token in the `Authorization` header:

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### Auth Endpoints

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/auth/login` | ❌ | Login and receive JWT token |
| `POST` | `/auth/register` | ❌ | Register a new user |
| `POST` | `/auth/logout` | ✅ | Invalidate token |
| `POST` | `/auth/refresh` | ✅ | Refresh expired token |
| `GET` | `/auth/me` | ✅ | Get authenticated user info |
| `POST` | `/auth/forgot-password` | ❌ | Request password reset |
| `POST` | `/auth/reset-password` | ❌ | Reset password with token |

### Roles

| Role | Description |
|---|---|
| `super-admin` | Full system access, bypasses all policies |
| `admin` | Administrative access across all modules |
| `manager` | Agency-level management with billing approval |
| `agent` | Day-to-day operations (reservations, clients) |
| `viewer` | Read-only access |

### Permissions (50+)

Permissions follow the pattern `{action}-{module}`:
- `view-agencies`, `create-agencies`, `edit-agencies`, `delete-agencies`
- `view-vehicles`, `create-vehicles`, `edit-vehicles`, `delete-vehicles`
- `view-clients`, `create-clients`, `edit-clients`, `delete-clients`
- `view-reservations`, `create-reservations`, `edit-reservations`, `delete-reservations`
- `view-billing`, `create-billing`, `edit-billing`, `delete-billing`, `approve-billing`
- `view-users`, `create-users`, `edit-users`, `delete-users`
- ... and more for each module

---

## 📡 API Endpoints

### Agencies
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/agencies` | List all agencies |
| `POST` | `/api/v1/agencies` | Create an agency |
| `GET` | `/api/v1/agencies/{id}` | Get agency details |
| `PUT` | `/api/v1/agencies/{id}` | Update an agency |
| `DELETE` | `/api/v1/agencies/{id}` | Soft delete an agency |
| `POST` | `/api/v1/agencies/{id}/restore` | Restore deleted agency |
| `GET` | `/api/v1/agencies/datatable` | DataTable server-side |

### Vehicles
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/vehicles` | List all vehicles |
| `POST` | `/api/v1/vehicles` | Create a vehicle |
| `GET` | `/api/v1/vehicles/{id}` | Get vehicle details |
| `PUT` | `/api/v1/vehicles/{id}` | Update a vehicle |
| `DELETE` | `/api/v1/vehicles/{id}` | Soft delete a vehicle |
| `GET` | `/api/v1/vehicles/datatable` | DataTable server-side |

### Clients
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/clients` | List all clients |
| `POST` | `/api/v1/clients` | Create a client |
| `GET` | `/api/v1/clients/{id}` | Get client details |
| `PUT` | `/api/v1/clients/{id}` | Update a client |
| `DELETE` | `/api/v1/clients/{id}` | Soft delete a client |
| `GET` | `/api/v1/clients/datatable` | DataTable server-side |

### Reservations
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/reservations` | List all reservations |
| `POST` | `/api/v1/reservations` | Create a reservation |
| `GET` | `/api/v1/reservations/{id}` | Get reservation details |
| `PUT` | `/api/v1/reservations/{id}` | Update a reservation |
| `DELETE` | `/api/v1/reservations/{id}` | Soft delete a reservation |
| `GET` | `/api/v1/reservations/datatable` | DataTable server-side |

### Billing
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/billing` | List billing documents |
| `POST` | `/api/v1/billing` | Create a billing document |
| `GET` | `/api/v1/billing/{id}` | Get document details |
| `PUT` | `/api/v1/billing/{id}` | Update a document |
| `DELETE` | `/api/v1/billing/{id}` | Soft delete a document |
| `GET` | `/api/v1/billing/datatable` | DataTable server-side |
| `GET` | `/api/v1/billing/statistics` | Billing statistics |
| `POST` | `/api/v1/billing/from-reservation/{id}` | Generate from reservation |
| `POST` | `/api/v1/billing/{id}/approve` | Approve a document |
| `POST` | `/api/v1/billing/{id}/mark-paid` | Mark as paid |
| `POST` | `/api/v1/billing/{id}/pdf` | Upload PDF |
| `POST` | `/api/v1/billing/{id}/attachments` | Upload attachments |
| `POST` | `/api/v1/billing/{id}/restore` | Restore deleted document |

### Users
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/users` | List all users |
| `POST` | `/api/v1/users` | Create a user |
| `GET` | `/api/v1/users/{id}` | Get user details |
| `PUT` | `/api/v1/users/{id}` | Update a user |
| `DELETE` | `/api/v1/users/{id}` | Soft delete a user |
| `PATCH` | `/api/v1/users/{id}/toggle-active` | Toggle active status |
| `POST` | `/api/v1/users/{id}/assign-role` | Assign role |
| `DELETE` | `/api/v1/users/{id}/remove-role` | Remove role |
| `GET` | `/api/v1/users/{id}/activity-logs` | View activity logs |
| `GET` | `/api/v1/users/datatable` | DataTable server-side |

### Profile
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/profile` | Get current user profile |
| `PUT` | `/api/v1/profile` | Update profile |
| `PUT` | `/api/v1/profile/password` | Change password |
| `POST` | `/api/v1/profile/avatar` | Upload avatar |

### Insurance
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/insurances` | List insurance policies |
| `POST` | `/api/v1/insurances` | Create a policy |
| `GET` | `/api/v1/insurances/{id}` | Get policy details |
| `PUT` | `/api/v1/insurances/{id}` | Update a policy |
| `DELETE` | `/api/v1/insurances/{id}` | Soft delete a policy |
| `GET` | `/api/v1/insurances/datatable` | DataTable server-side |

### Maintenance
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/maintenances` | List maintenance records |
| `POST` | `/api/v1/maintenances` | Create a record |
| `GET` | `/api/v1/maintenances/{id}` | Get record details |
| `PUT` | `/api/v1/maintenances/{id}` | Update a record |
| `DELETE` | `/api/v1/maintenances/{id}` | Soft delete a record |
| `GET` | `/api/v1/maintenances/datatable` | DataTable server-side |

### Technical Inspections
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/technical-inspections` | List inspections |
| `POST` | `/api/v1/technical-inspections` | Create an inspection |
| `GET` | `/api/v1/technical-inspections/{id}` | Get inspection details |
| `PUT` | `/api/v1/technical-inspections/{id}` | Update an inspection |
| `DELETE` | `/api/v1/technical-inspections/{id}` | Soft delete an inspection |
| `GET` | `/api/v1/technical-inspections/datatable` | DataTable server-side |

### Vignettes
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/vignettes` | List vignettes |
| `POST` | `/api/v1/vignettes` | Create a vignette |
| `GET` | `/api/v1/vignettes/{id}` | Get vignette details |
| `PUT` | `/api/v1/vignettes/{id}` | Update a vignette |
| `DELETE` | `/api/v1/vignettes/{id}` | Soft delete a vignette |
| `GET` | `/api/v1/vignettes/datatable` | DataTable server-side |

### Roles & Permissions
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/roles` | List all roles |
| `POST` | `/api/v1/roles` | Create a role |
| `GET` | `/api/v1/permissions` | List all permissions |

### Notifications
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/notifications` | List notifications |

### Audit Logs
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/logs` | List audit logs |

---

## 📊 DataTables Support

All major modules expose a `/datatable` endpoint for **server-side processing** with [Yajra DataTables](https://yajrabox.com/docs/laravel-datatables).

### Supported Parameters

| Parameter | Type | Description |
|---|---|---|
| `draw` | integer | DataTables draw counter |
| `start` | integer | Pagination offset |
| `length` | integer | Number of records per page |
| `search[value]` | string | Global search term |
| `order[0][column]` | integer | Column index to sort by |
| `order[0][dir]` | string | Sort direction (`asc` / `desc`) |
| `columns[n][search][value]` | string | Per-column search |

### Example Request

```
GET /api/v1/vehicles/datatable?draw=1&start=0&length=10&search[value]=Toyota
```

### Available DataTable Endpoints

```
GET /api/v1/agencies/datatable
GET /api/v1/vehicles/datatable
GET /api/v1/clients/datatable
GET /api/v1/reservations/datatable
GET /api/v1/users/datatable
GET /api/v1/insurances/datatable
GET /api/v1/maintenances/datatable
GET /api/v1/technical-inspections/datatable
GET /api/v1/vignettes/datatable
GET /api/v1/billing/datatable
```

---

## 🧪 Testing

### Run Tests

```bash
php artisan test
```

### Run with Coverage

```bash
php artisan test --coverage
```

### Run Specific Test Suite

```bash
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

---

## 📁 Project Structure

```
car-rental-api/
├── app/
│   ├── Core/                          # Shared infrastructure
│   │   ├── Exceptions/                # Custom exception handler
│   │   ├── Http/                      # Base controller
│   │   ├── Repositories/             # Base repository (with datatable)
│   │   └── Traits/                    # Reusable traits
│   │       ├── ApiResponse.php        # Standardized API responses
│   │       ├── HasUuid.php            # UUID primary key trait
│   │       └── HasMediaCollections.php # Media library trait
│   ├── Http/
│   │   ├── Controllers/              # Auth, Profile controllers
│   │   ├── Middleware/
│   │   │   ├── JwtMiddleware.php      # JWT token validation
│   │   │   ├── RoleMiddleware.php     # Role-based access
│   │   │   └── LogRequestMiddleware.php # Request logging
│   │   ├── Requests/                  # Shared form requests
│   │   └── Resources/                 # Shared API resources
│   ├── Models/                        # Eloquent models (11 models)
│   │   ├── Agency.php
│   │   ├── Vehicle.php
│   │   ├── Client.php
│   │   ├── Reservation.php
│   │   ├── BillingDocument.php
│   │   ├── BillingDocumentItem.php
│   │   ├── Insurance.php
│   │   ├── Maintenance.php
│   │   ├── TechnicalInspection.php
│   │   ├── Vignette.php
│   │   └── User.php
│   ├── Modules/                       # Feature modules (12 modules)
│   │   ├── Agency/
│   │   ├── Billing/
│   │   ├── Client/
│   │   ├── Insurance/
│   │   ├── Maintenance/
│   │   ├── Notification/
│   │   ├── Reservation/
│   │   ├── Role/
│   │   ├── TechnicalInspection/
│   │   ├── User/
│   │   ├── Vehicle/
│   │   └── Vignette/
│   ├── Policies/                      # Authorization policies
│   └── Providers/
│       └── AppServiceProvider.php     # Repository bindings
├── config/                            # Configuration files
├── database/
│   ├── factories/                     # Model factories (9 factories)
│   ├── migrations/                    # Database migrations (17 files)
│   └── seeders/                       # Database seeders (8 seeders)
├── DOC/                               # Project documentation
│   ├── API_DOCUMENTATION.md           # Full API reference
│   └── IMPLEMENTATION_SUMMARY.md      # Implementation details
├── routes/
│   └── api/                           # API route files (14 files)
│       ├── auth.php
│       ├── agencies.php
│       ├── vehicles.php
│       ├── clients.php
│       ├── reservations.php
│       ├── billing.php
│       ├── insurances.php
│       ├── maintenances.php
│       ├── technical-inspections.php
│       ├── vignettes.php
│       ├── users.php
│       ├── roles.php
│       ├── notifications.php
│       └── logs.php
├── storage/
│   └── api-docs/                      # Generated Swagger JSON
├── tests/
│   ├── Feature/                       # Feature tests
│   └── Unit/                          # Unit tests
├── .env.example                       # Environment template
├── composer.json                      # PHP dependencies
├── phpunit.xml                        # Test configuration
└── roadmap.md                         # Development roadmap
```

---

## 🔧 Useful Commands

| Command | Description |
|---|---|
| `php artisan serve` | Start development server |
| `php artisan migrate:fresh --seed` | Reset and seed database |
| `php artisan jwt:secret` | Generate JWT secret key |
| `php artisan l5-swagger:generate` | Generate Swagger docs |
| `php artisan route:list` | List all registered routes |
| `php artisan test` | Run test suite |
| `php artisan tinker` | Interactive REPL |
| `php artisan pint` | Run code formatter (Laravel Pint) |
| `composer dev` | Start full dev environment |

---

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) for code formatting:

```bash
./vendor/bin/pint
```

---

## 📄 License

This project is open-sourced software licensed under the [MIT License](https://opensource.org/licenses/MIT).

---

## 📞 Support

For questions or issues, please open an issue on the repository or contact the development team.

---

<p align="center">
  <strong>GES-CARS 2026</strong> — Built with ❤️ using Laravel 13
</p>

