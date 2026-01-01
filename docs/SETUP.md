# MetCon Crew Command - Setup Guide

## Prerequisites

- Node.js (v18 or higher)
- PHP 8.2 or higher
- Composer
- Docker & Docker Compose
- npm

## Quick Start

Follow these steps to get the entire stack running:

### 1. Start MySQL Database

```bash
# From the root directory
docker compose up -d
```

This will start:
- MySQL on port 3306
- phpMyAdmin on port 8080 (http://localhost:8080)

### 2. Set Up Backend (Laravel)

```bash
cd backend

# Install dependencies
composer install

# The .env file should already be configured, but verify these settings:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=metcon_db
# DB_USERNAME=metcon_user
# DB_PASSWORD=metcon_password

# Run migrations
php artisan migrate

# Seed the database with admin user
php artisan db:seed

# Start the Laravel development server
php artisan serve
```

Backend will run on: **http://localhost:8000**

### 3. Set Up Frontend (Quasar)

Open a new terminal:

```bash
cd frontend

# Install dependencies
npm install

# Verify .env file exists with:
# VITE_API_BASE_URL=http://localhost:8000

# Start the Quasar development server
npm run dev
```

Frontend will run on: **http://localhost:9000**

## Default Credentials

```
Email: admin@example.com
Password: password123
```

## Testing the Setup

1. Open http://localhost:9000 in your browser
2. You should be redirected to the login page
3. Enter the credentials above
4. After successful login, you'll see the dashboard with your user information

## Environment Files

### Backend (.env)

Key configurations:
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

## Stopping Services

```bash
# Stop Laravel (Ctrl+C in the terminal)

# Stop Quasar (Ctrl+C in the terminal)

# Stop MySQL
docker compose down
```

## Resetting the Database

```bash
cd backend
php artisan migrate:fresh --seed
```
