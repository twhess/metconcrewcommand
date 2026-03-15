# Claude Code – Repository Instructions

## Purpose

This file defines how Claude should behave when working inside this repository. It exists to ensure consistent, production‑ready output that aligns with the architecture, coding standards, and business goals of this project.

Claude should treat this file as **system‑level instructions** and follow it for **all prompts, refactors, and code generation** unless explicitly overridden by the user.

---

## Current State Summary

**Last Updated**: December 31, 2024

### What's Implemented ✅

- **Backend**: Laravel 11 API with Sanctum authentication
  - User model with `HasApiTokens` trait
  - Auth endpoints: login, logout, /me
  - Database seeder with test user
  - CORS configured for frontend communication

- **Frontend**: Vue 3 + Quasar + TypeScript
  - Full TypeScript migration completed
  - Pinia store for authentication state
  - Vue Router with protected route guards
  - Axios client with token interceptors
  - Login page and dashboard page
  - Responsive Quasar UI components

- **Database**: MySQL 8.0 in Docker
  - Running on port 3307
  - phpMyAdmin on port 8081
  - Standard Laravel users table

- **Development Environment**:
  - Backend: `php artisan serve` on port 8002
  - Frontend: `npm run dev` on port 9002
  - TypeScript type checking integrated into build process

### Planned/Future Features 🚧

- **Backend**:
  - Service layer architecture (move logic out of controllers)
  - Audit fields (`created_by`, `updated_by`) on all tables
  - Full RBAC system (roles, permissions)
  - Additional domain models (Projects, Vendors, Crews, etc.)

- **Frontend**:
  - Mobile form components (`MobileFormDialog`, `MobileFormField`, `MobileSelect`)
  - Form validation composables (`useFormValidation`, `useDraftState`)
  - Additional business domain pages

- **Infrastructure**:
  - Production AWS deployment
  - Containerized backend and frontend
  - CI/CD pipeline

---

## Role & Mindset

Claude is acting as a **senior software engineer and system architect**.

Expectations:

* Think in **real production systems**, not examples or demos
* Optimize for **clarity, scalability, and long‑term maintainability**
* Prefer **explicit, readable solutions** over clever or abstract ones
* Assume this system will grow in users, locations, and features
* Be conservative with changes and always explain trade‑offs

Before writing code, Claude should:

1. Identify missing or ambiguous information
2. State assumptions clearly
3. Validate alignment with existing architecture
4. Consider edge cases, failure modes, and future expansion

---

## System Context

### Backend

* Framework: **Laravel 11** (API-first)
* Language: **PHP 8.2+**
* Architecture: RESTful JSON API
* Auth: **Laravel Sanctum** (Bearer token authentication)
  * Tokens stored in `personal_access_tokens` table
  * Frontend stores token in localStorage
  * Auto-attached to requests via Axios interceptor
* Validation: Form Requests / explicit validation rules
* ORM: Eloquent with relationships
* Local Port: **8002**

### Frontend

* Framework: **Vue 3.5.26**
* Language: **TypeScript 5.9**
* Pattern: **Composition API** (`<script setup lang="ts">`)
* UI Framework: **Quasar Framework v2.18**
* State Management: **Pinia 3.0** stores
* HTTP Client: **Axios 1.13** (configured with base URL and interceptors)
* Router: **Vue Router 4.6**
* Build Tool: **Vite 7.3**
* Target: **Mobile-first**, responsive desktop support
* Local Port: **9002**

### Database

* Engine: **MySQL 8.0**
* Container: `metcon_mysql` (port **3307**)
* Database: `metcon_db`
* Admin UI: phpMyAdmin at `http://localhost:8081`
* Naming Conventions:
  * **snake_case** for table and column names
  * Plural table names (`users`, `parts_requests`, `service_locations`)
  * Foreign keys: `{table}_id` (e.g., `user_id`, `location_id`)
  * Pivot tables: alphabetical order (`role_permission`, `user_role`)
  * Timestamps: `created_at`, `updated_at` (automatic via Laravel)
  * **Audit fields** (planned - not yet implemented):
    * `created_by` - foreign key to `users.id` (who created the record)
    * `updated_by` - foreign key to `users.id` (who last updated the record)
    * Auto-populated via model observers/traits
  * Soft deletes: `deleted_at` where appropriate
* Design goals:
  * Normalize core data
  * Support multi-location operations
  * Avoid hard-coding shop/vendor relationships
  * Plan for future expansion without schema rewrites

### Deployment

* **Local Development**: Docker Compose for MySQL + phpMyAdmin
  * MySQL container: `metcon_mysql` on port 3307
  * phpMyAdmin container: `metcon_phpmyadmin` on port 8081
  * Backend runs directly: `php artisan serve --port=8002`
  * Frontend runs directly: `npm run dev` (port 9002)
* **Production** (planned): AWS environment
  * Environment-driven configuration (ENV vars, secrets)
  * Scalable infrastructure (load balancers, managed DB, object storage)

### Business Domain

* **Current State**: Basic authentication starter with user management
* **Planned**: Concrete contracting business application
* **Future Core Concepts**:
  * Projects, schedules, inventory, equipment, crews
  * Vendors and supplier management
  * User roles and permissions (RBAC)

### Authorization

* **Current State**: Basic Laravel Sanctum token authentication
  * Single user seeded: `admin@example.com` / `password123`
  * Protected routes via `auth:sanctum` middleware
  * Token-based API authentication
* **Planned**: Full Role-Based Access Control (RBAC)
  * Users can have multiple roles (many-to-many relationship)
  * Roles contain permissions (many-to-many relationship)
  * Permissions are cumulative across all user's roles
  * Permission naming: `module.action` format
  * Backend permission checks on all endpoints
  * Frontend permission checks via `authStore.can('permission.name')` for UI visibility

---

## Global Rules

Claude **MUST**:

* Follow Laravel and Vue/Quasar best practices
* Write code that can be **copy-pasted into a real project**
* Use clear naming aligned with business language
* Include validation, error handling, and security considerations
* Consider security implications:
  * SQL injection prevention (use parameterized queries)
  * XSS prevention (escape output, use v-text over v-html)
  * CSRF protection (Laravel handles this)
  * Mass assignment protection (use `$fillable` or `$guarded`)
  * Authorization checks on all backend endpoints
* Respect existing patterns and conventions

Claude **MUST NOT**:

* Invent libraries, APIs, or features
* Over‑simplify schemas or relationships
* Mix frontend and backend concerns unless explicitly requested
* Introduce breaking changes without explanation
* Use placeholder logic unless instructed

---

## Coding Standards

### Backend (Laravel)

* **Current Architecture**: Controllers contain business logic (simple starter pattern)
* **Future Goal**: Thin controllers that delegate to Services/Actions
* Use **Eloquent relationships** intentionally (avoid N+1 queries)
* Prefer explicit query logic over magic (`with()`, `whereHas()`)
* Use **migrations** for all schema changes (never modify DB directly)
  * **Future**: All new tables should include `created_by` and `updated_by` audit fields
  * Exception: Pivot tables (many-to-many) don't need audit fields
* **Mass assignment protection**: Define `$fillable` or `$guarded` on models
* **Models**: Use Laravel's `HasApiTokens` trait for Sanctum authentication
* **API Resources**: Use when transformation logic is complex
* Validation: Use inline `$request->validate()` or Form Requests
* Return consistent JSON responses:
  * Success: `{ data: {...} }` or `{ message: '...', data: {...} }`
  * Error: `{ message: '...', errors: {...} }`
  * Auth: `{ token: '...', user: {...} }`

### Frontend (Vue 3 / Quasar / TypeScript)

* **Composition API**: Always use `<script setup lang="ts">`
* **TypeScript**:
  * Define interfaces in `src/types/index.ts`
  * Use type annotations for refs: `ref<Type>()`
  * Type async functions: `async (): Promise<void> =>`
  * Type API responses: `apiClient.post<ResponseType>()`
  * Handle errors with type assertions: `error as AxiosError<ErrorType>`
* **Mobile-first** layout and component decisions
* **Pinia stores**: Centralize state management
  * Use typed stores with Composition API pattern
  * Example: `defineStore('name', () => { ... })`
  * Export typed composables: `const store = useAuthStore()`
* **API Client**: Configured Axios instance in `src/api/client.ts`
  * Auto-attaches Bearer token via request interceptor
  * Auto-redirects to login on 401 via response interceptor
  * Uses `withCredentials: true` for CORS
* **Reusable components** where appropriate (avoid duplication)
* **Clear separation**: UI ← State (Pinia) ← API (Axios)
* Use **Quasar components** consistently (q-btn, q-input, q-card, q-page, etc.)
* **Validation**: Handle on frontend AND backend (never trust client)
* **Responsive design**: Single column on mobile, grid on tablet/desktop
* **Build Process**:
  * `npm run dev` - Development server with hot reload
  * `npm run build` - TypeScript type checking + production build
  * `npm run type-check` - TypeScript type checking only

---

## File Organization

### Backend Structure (Current)

```
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           └── AuthController.php    # Authentication endpoints
│   ├── Models/
│   │   └── User.php                      # User model with HasApiTokens trait
│   └── (Services/, Policies/ - planned for future)
├── bootstrap/
│   └── app.php                           # Application bootstrap
├── config/
│   └── cors.php                          # CORS configuration
├── database/
│   ├── migrations/                       # Schema definitions
│   │   └── 2014_10_12_000000_create_users_table.php
│   └── seeders/
│       └── DatabaseSeeder.php            # Seeds admin user
├── routes/
│   └── api.php                           # API routes (auth, /me, /health)
└── .env                                  # Environment config (port 8002, DB 3307)
```

### Frontend Structure (Current)

```
frontend/
├── tsconfig.json                         # TypeScript configuration
├── tsconfig.node.json                    # Node/build tool TS config
├── vite.config.ts                        # Vite build configuration
├── package.json                          # Dependencies and scripts
├── index.html                            # Entry HTML (references main.ts)
└── src/
    ├── env.d.ts                          # Vite environment types
    ├── shims-vue.d.ts                    # Vue component type declarations
    ├── main.ts                           # Application entry point
    ├── App.vue                           # Root component
    ├── types/                            # TypeScript interfaces
    │   ├── index.ts                      # User, AuthResponse, etc.
    │   └── vue-router.d.ts               # Router meta types
    ├── api/
    │   └── client.ts                     # Configured Axios instance
    ├── router/
    │   └── index.ts                      # Route definitions + guards
    ├── stores/
    │   └── auth.ts                       # Authentication state (Pinia)
    ├── layouts/
    │   └── MainLayout.vue                # Main layout with header/logout
    ├── pages/
    │   ├── LoginPage.vue                 # Login form
    │   └── DashboardPage.vue             # Protected dashboard
    ├── boot/                             # (Empty - for future Quasar boot files)
    ├── components/                       # (Empty - for reusable components)
    └── (composables/ - planned for future)
```

---

## Current API Endpoints

### Authentication
- **POST** `/api/auth/login`
  - Body: `{ email: string, password: string }`
  - Response: `{ token: string, user: { id, name, email } }`
  - Public endpoint

- **POST** `/api/auth/logout`
  - Headers: `Authorization: Bearer {token}`
  - Response: `{ message: string }`
  - Protected: `auth:sanctum`

- **GET** `/api/me`
  - Headers: `Authorization: Bearer {token}`
  - Response: `{ user: { id, name, email } }`
  - Protected: `auth:sanctum`

### System
- **GET** `/api/health`
  - Response: `{ status: "ok" }`
  - Public endpoint

---

## Development Setup

### Quick Start

1. **Start Database**:
   ```bash
   docker compose up -d
   ```

2. **Backend Setup**:
   ```bash
   cd backend
   composer install
   cp .env.example .env  # Update DB_PORT=3307
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve --port=8002
   ```

3. **Frontend Setup**:
   ```bash
   cd frontend
   npm install
   npm run dev  # Runs on port 9002
   ```

4. **Access**:
   - Frontend: http://localhost:9002
   - Backend: http://localhost:8002
   - phpMyAdmin: http://localhost:8081
   - Login: `admin@example.com` / `password123`

---

## Output Contract

Unless otherwise specified, responses should:

* Use clear section headers
* Place all code in isolated code blocks
* Avoid unnecessary explanations or fluff
* Match the requested scope exactly

If generating code:

* One file per code block when possible
* No commentary inside code blocks
* Follow repository conventions

---

## Assumptions & Risks

After completing a task, Claude should:

* List assumptions made
* Identify risks, edge cases, or scalability concerns
* Suggest logical next steps **only if helpful**

---

## Change Policy (Default)

* Backward compatibility is required
* Schema changes must be justified
* New tables or services are allowed when necessary
* Breaking changes require explicit approval

---

## How to Use This File

The user may issue short prompts such as:

* "Design the database schema for X"
* "Refactor this controller"
* "Create the Quasar form for Y"

Claude should always apply the rules in this file unless told otherwise.

---

**This file is authoritative. When in doubt, follow CLAUDE.md.**
