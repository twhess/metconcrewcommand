# Command Reference

All commands to create and run the monorepo from scratch.

## Initial Setup Commands

### 1. Create Directory Structure
```bash
mkdir -p frontend backend docs
```

### 2. Create Docker Compose for MySQL
Already created at `docker-compose.yml` in root.

### 3. Create Laravel Backend
```bash
cd backend
composer create-project laravel/laravel . "^11.0"
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 4. Create Quasar Frontend
```bash
cd frontend
npm init -y
npm install quasar @quasar/extras
npm install -D @quasar/vite-plugin vite vue vue-router pinia axios @vitejs/plugin-vue
```

## Daily Development Commands

### Start All Services

**Terminal 1 - Database:**
```bash
docker compose up -d
```

**Terminal 2 - Backend:**
```bash
cd backend
php artisan serve
```

**Terminal 3 - Frontend:**
```bash
cd frontend
npm run dev
```

### Stop All Services

**Frontend & Backend:**
Press `Ctrl+C` in each terminal

**Database:**
```bash
docker compose down
```

## Backend Commands (Laravel)

### Database
```bash
# Run migrations
php artisan migrate

# Fresh migration (destroys data)
php artisan migrate:fresh

# Run seeders
php artisan db:seed

# Fresh migration + seed
php artisan migrate:fresh --seed

# Rollback last migration
php artisan migrate:rollback
```

### Cache
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Development
```bash
# Start dev server
php artisan serve

# Start on different port
php artisan serve --port=8001

# Interactive shell
php artisan tinker

# Create controller
php artisan make:controller Api/UserController

# Create migration
php artisan make:migration create_posts_table

# Create model
php artisan make:model Post -m

# List all routes
php artisan route:list
```

### Sanctum
```bash
# Create a personal access token (in tinker)
php artisan tinker
>>> $user = User::find(1);
>>> $token = $user->createToken('token-name');
>>> echo $token->plainTextToken;
```

## Frontend Commands (Quasar/Vite)

### Development
```bash
# Start dev server
npm run dev

# Start on different port
npm run dev -- --port=9001

# Build for production
npm run build

# Preview production build
npm run preview
```

### Dependencies
```bash
# Install dependencies
npm install

# Add new dependency
npm install package-name

# Add dev dependency
npm install -D package-name

# Update dependencies
npm update

# Clean install
rm -rf node_modules package-lock.json
npm install
```

## Docker Commands

### Container Management
```bash
# Start containers
docker compose up -d

# Stop containers
docker compose down

# Stop and remove volumes (destroys data)
docker compose down -v

# View running containers
docker compose ps

# View logs
docker compose logs

# Follow logs
docker compose logs -f

# Restart containers
docker compose restart
```

### MySQL Access
```bash
# Access MySQL CLI
docker compose exec mysql mysql -u metcon_user -pmetcon_password metcon_db

# Import SQL file
docker compose exec -T mysql mysql -u metcon_user -pmetcon_password metcon_db < backup.sql

# Export database
docker compose exec mysql mysqldump -u metcon_user -pmetcon_password metcon_db > backup.sql
```

## Database Direct Access

### Via Command Line
```bash
# Using Docker
docker compose exec mysql mysql -u metcon_user -pmetcon_password metcon_db

# Using local MySQL client (if installed)
mysql -h 127.0.0.1 -P 3306 -u metcon_user -pmetcon_password metcon_db
```

### Via phpMyAdmin
Open http://localhost:8080
- Server: `mysql`
- Username: `root`
- Password: `rootpassword`

## Useful One-Liners

### Reset Everything
```bash
# Backend
cd backend && php artisan migrate:fresh --seed

# Frontend
cd frontend && rm -rf node_modules package-lock.json && npm install

# Database
docker compose down -v && docker compose up -d
```

### Check Service Status
```bash
# Check if ports are in use
lsof -i :3306  # MySQL
lsof -i :8000  # Laravel
lsof -i :9000  # Quasar

# Test API endpoints
curl http://localhost:8000/api/health
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password123"}'
```

### Create New User
```bash
# Via tinker
php artisan tinker
>>> \App\Models\User::create([
...   'name' => 'New User',
...   'email' => 'user@example.com',
...   'password' => bcrypt('password123')
... ]);
```

### View Laravel Logs
```bash
# Real-time log viewing
tail -f backend/storage/logs/laravel.log

# View last 50 lines
tail -n 50 backend/storage/logs/laravel.log

# Clear logs
echo "" > backend/storage/logs/laravel.log
```

## Git Commands (Optional)

```bash
# Initialize repo
git init

# Add all files
git add .

# Commit
git commit -m "Initial commit: Quasar + Laravel + MySQL monorepo"

# Add remote
git remote add origin <your-repo-url>

# Push
git push -u origin main
```

## Production Build Commands

### Frontend
```bash
cd frontend
npm run build
# Output in frontend/dist
```

### Backend
```bash
cd backend
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
