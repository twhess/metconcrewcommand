# Troubleshooting Guide

## CORS Issues

### Symptom
Browser console shows CORS errors when making API requests.

### Solution
1. Verify Laravel is configured to accept requests from `http://localhost:9000`
2. Check `backend/.env` has: `SANCTUM_STATEFUL_DOMAINS=localhost:9000`
3. Ensure `backend/bootstrap/app.php` includes Sanctum middleware
4. Clear Laravel config cache: `php artisan config:clear`

## Database Connection Issues

### Symptom
Laravel shows "Connection refused" or similar database errors.

### Solution
1. Ensure MySQL container is running: `docker compose ps`
2. Verify database credentials in `backend/.env` match `docker-compose.yml`
3. Try connecting to MySQL manually:
   ```bash
   mysql -h 127.0.0.1 -P 3306 -u metcon_user -pmetcon_password metcon_db
   ```
4. If the database doesn't exist, recreate it:
   ```bash
   docker compose down -v
   docker compose up -d
   ```

## Port Already in Use

### Symptom
"Address already in use" errors when starting services.

### Solution

**Port 3306 (MySQL):**
```bash
# Find what's using port 3306
lsof -i :3306

# Stop the conflicting service or change the port in docker-compose.yml
```

**Port 8000 (Laravel):**
```bash
# Use a different port
php artisan serve --port=8001

# Update frontend .env to match:
# VITE_API_BASE_URL=http://localhost:8001
```

**Port 9000 (Quasar):**
```bash
# Quasar will automatically use the next available port
# Or specify one: npm run dev -- --port=9001
```

## Authentication Token Issues

### Symptom
Getting 401 Unauthorized even after logging in.

### Solution
1. Check browser console for token in localStorage: `localStorage.getItem('auth_token')`
2. Verify the token is being sent in request headers (Network tab in DevTools)
3. Clear localStorage and login again:
   ```javascript
   localStorage.clear()
   ```
4. Ensure Sanctum middleware is properly configured in `backend/bootstrap/app.php`

## 419 Page Expired (CSRF Token Mismatch)

### Symptom
Getting 419 errors on API requests.

### Solution
This usually indicates CORS misconfiguration:
1. Verify `SANCTUM_STATEFUL_DOMAINS` in backend `.env`
2. Make sure frontend is accessing backend via the exact URL specified
3. Check that you're using Bearer token authentication (not cookie-based)

## Frontend Not Loading

### Symptom
White screen or errors when accessing http://localhost:9000

### Solution
1. Check browser console for errors
2. Verify all dependencies are installed: `npm install`
3. Delete `node_modules` and reinstall:
   ```bash
   rm -rf node_modules package-lock.json
   npm install
   ```
4. Check for TypeScript or build errors in terminal

## API Returns 500 Error

### Symptom
API endpoints return 500 Internal Server Error.

### Solution
1. Check Laravel logs: `backend/storage/logs/laravel.log`
2. Enable debug mode in backend `.env`: `APP_DEBUG=true`
3. Common causes:
   - Database not migrated: `php artisan migrate`
   - Missing dependencies: `composer install`
   - Cache issues: `php artisan config:clear && php artisan cache:clear`

## Migration Errors

### Symptom
`php artisan migrate` fails with table already exists or other errors.

### Solution
```bash
# Fresh migration (WARNING: destroys all data)
php artisan migrate:fresh

# With seeder
php artisan migrate:fresh --seed
```

## Seeder Not Creating User

### Symptom
Can't login with default credentials after seeding.

### Solution
```bash
# Re-run the seeder
php artisan db:seed --force

# Or create user manually via tinker:
php artisan tinker
>>> \App\Models\User::create(['name'=>'Admin User','email'=>'admin@example.com','password'=>bcrypt('password123')]);
```

## Quasar Components Not Rendering

### Symptom
Quasar components show as plain HTML or don't render.

### Solution
1. Verify Quasar is properly imported in `src/main.js`
2. Check that `@quasar/vite-plugin` is configured in `vite.config.js`
3. Ensure Material Icons CSS is imported
4. Restart the dev server

## Network Request Failed

### Symptom
Axios requests fail immediately without reaching the server.

### Solution
1. Verify backend server is running on `http://localhost:8000`
2. Check `VITE_API_BASE_URL` in frontend `.env`
3. Test the health endpoint directly: `curl http://localhost:8000/api/health`
4. Check browser console for the actual error message
5. Verify no VPN or firewall is blocking local connections
