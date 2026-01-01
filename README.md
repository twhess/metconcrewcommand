# MetCon Crew Command - Monorepo

A full-stack monorepo with Quasar (Vue 3) frontend and Laravel backend with MySQL database.

## Stack

- **Frontend:** Quasar Framework (Vue 3) + Vite + Pinia + Vue Router
- **Backend:** Laravel 11 (API-only) + Sanctum Authentication
- **Database:** MySQL 8.0 (Docker)

## Quick Start

### 1. Start Database
```bash
docker compose up -d
```

### 2. Start Backend
```bash
cd backend
composer install
php artisan migrate
php artisan db:seed
php artisan serve
```

### 3. Start Frontend
```bash
cd frontend
npm install
npm run dev
```

### 4. Access Application
- Frontend: http://localhost:9000
- Backend: http://localhost:8000
- phpMyAdmin: http://localhost:8080

### 5. Login
```
Email: admin@example.com
Password: password123
```

## Project Structure

```
.
├── frontend/          # Quasar Vue 3 application
│   ├── src/
│   │   ├── api/      # API client (Axios)
│   │   ├── stores/   # Pinia stores
│   │   ├── pages/    # Login, Dashboard
│   │   ├── layouts/  # Main layout
│   │   └── router/   # Vue Router with guards
│   └── .env          # VITE_API_BASE_URL
│
├── backend/          # Laravel API
│   ├── app/
│   │   └── Http/
│   │       └── Controllers/
│   │           └── Api/
│   │               └── AuthController.php
│   ├── routes/
│   │   └── api.php   # API routes
│   └── .env          # Database, Sanctum config
│
├── docs/             # Documentation
│   ├── SETUP.md
│   ├── TROUBLESHOOTING.md
│   ├── API.md
│   └── SMOKE_TEST.md
│
└── docker-compose.yml # MySQL + phpMyAdmin
```

## API Endpoints

### Public
- `GET /api/health` - Health check
- `POST /api/auth/login` - Login (returns token)

### Protected (requires Bearer token)
- `GET /api/me` - Get current user
- `POST /api/auth/logout` - Logout

## Features

### Frontend
- ✅ Login page with form validation
- ✅ Dashboard showing authenticated user info
- ✅ Axios client with automatic token attachment
- ✅ 401 interceptor redirects to login
- ✅ Route guards (auth/guest)
- ✅ Pinia state management
- ✅ Token persistence in localStorage

### Backend
- ✅ Laravel Sanctum token authentication
- ✅ CORS configured for localhost:9000
- ✅ API-only routes
- ✅ Validation and error handling
- ✅ Database seeder with default user

## Documentation

- [Setup Guide](docs/SETUP.md) - Step-by-step installation
- [Troubleshooting](docs/TROUBLESHOOTING.md) - Common issues and solutions
- [API Documentation](docs/API.md) - Endpoint reference
- [Smoke Test Checklist](docs/SMOKE_TEST.md) - Verification tests

## Development

### Frontend Development
```bash
cd frontend
npm run dev       # Start dev server (port 9000)
npm run build     # Build for production
```

### Backend Development
```bash
cd backend
php artisan serve              # Start dev server (port 8000)
php artisan migrate:fresh --seed   # Reset database
php artisan tinker             # Laravel REPL
```

### Database Management
```bash
docker compose up -d           # Start MySQL
docker compose down            # Stop MySQL
docker compose down -v         # Stop and remove volumes
```

Access phpMyAdmin at http://localhost:8080
- Username: `root`
- Password: `rootpassword`

## Environment Variables

### Backend (.env)
```env
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=metcon_db
DB_USERNAME=metcon_user
DB_PASSWORD=metcon_password
SANCTUM_STATEFUL_DOMAINS=localhost:9000
```

### Frontend (.env)
```env
VITE_API_BASE_URL=http://localhost:8000
```

## Testing the Setup

Run through the [Smoke Test Checklist](docs/SMOKE_TEST.md) to verify everything works.

Quick test:
```bash
# 1. Check health endpoint
curl http://localhost:8000/api/health

# 2. Open browser to http://localhost:9000
# 3. Login with admin@example.com / password123
# 4. Verify dashboard shows user info
```

## Troubleshooting

See [TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) for common issues:
- CORS errors
- Database connection issues
- Port conflicts
- Authentication token problems

## License

MIT
