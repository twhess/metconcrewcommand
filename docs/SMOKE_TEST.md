# Smoke Test Checklist

Use this checklist to verify the entire stack is working correctly.

## Pre-Test Setup

- [x] MySQL container is running: `docker compose ps` shows mysql as "Up"
- [x] Laravel server is running on http://localhost:8000
- [X] Quasar dev server is running on http://localhost:9000
- [ ] Database is migrated and seeded

## Backend Tests

### 1. Health Check
```bash
curl http://localhost:8000/api/health
```
**Expected:** `{"status":"ok"}`

### 2. Login Endpoint
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password123"}'
```
**Expected:** JSON with `token` and `user` fields

### 3. Protected Endpoint (Get User)
```bash
# First, get a token from login
TOKEN="your_token_here"

curl http://localhost:8000/api/me \
  -H "Authorization: Bearer $TOKEN"
```
**Expected:** JSON with user data

### 4. Protected Endpoint (Unauthorized)
```bash
curl http://localhost:8000/api/me
```
**Expected:** 401 Unauthenticated error

## Frontend Tests

### 1. Initial Load
- [ ] Navigate to http://localhost:9000
- [ ] Should redirect to `/login`
- [ ] Login page displays correctly with email/password fields

### 2. Login Flow
- [ ] Enter email: `admin@example.com`
- [ ] Enter password: `password123`
- [ ] Click "Login" button
- [ ] Should see "Login successful!" notification
- [ ] Should redirect to `/dashboard`

### 3. Dashboard Display
- [ ] Dashboard shows "Authenticated" heading
- [ ] User ID is displayed
- [ ] Name shows "Admin User"
- [ ] Email shows "admin@example.com"
- [ ] Header shows "Logout" button

### 4. Protected Route Access
- [ ] While logged in, try to access `/login`
- [ ] Should redirect to `/dashboard`

### 5. Logout Flow
- [ ] Click "Logout" button in header
- [ ] Should see "Logged out successfully" notification
- [ ] Should redirect to `/login`
- [ ] Try to access `/dashboard` directly
- [ ] Should redirect to `/login`

### 6. Invalid Login
- [ ] Enter wrong email or password
- [ ] Click "Login"
- [ ] Should see error notification
- [ ] Should remain on login page

### 7. Token Persistence
- [ ] Login successfully
- [ ] Refresh the page (F5)
- [ ] Should remain logged in on `/dashboard`
- [ ] User data should still display

### 8. 401 Handling
- [ ] While logged in, open browser console
- [ ] Run: `localStorage.setItem('auth_token', 'invalid')`
- [ ] Refresh the page
- [ ] Navigate to `/dashboard`
- [ ] Should redirect to `/login` after API call fails

## Browser Console Checks

Open DevTools (F12) and check:

### 1. No Console Errors
- [ ] No red errors in console
- [ ] No CORS errors
- [ ] No 404 errors for assets

### 2. Network Tab (while logging in)
- [ ] POST to `/api/auth/login` returns 200
- [ ] Response contains `token` and `user`
- [ ] Subsequent requests include `Authorization: Bearer {token}` header

### 3. Application Tab (after login)
- [ ] localStorage contains `auth_token`
- [ ] localStorage contains `user` (JSON string)

## Database Checks

### 1. phpMyAdmin
- [ ] Access http://localhost:8080
- [ ] Login with root/rootpassword
- [ ] Database `metcon_db` exists
- [ ] Table `users` exists
- [ ] Table `personal_access_tokens` exists
- [ ] Users table has 1 row (admin@example.com)

### 2. Token Creation (after login)
- [ ] `personal_access_tokens` table has a new row
- [ ] `tokenable_id` matches the user ID
- [ ] `name` is "auth_token"

## Edge Cases

### 1. Direct Dashboard Access (Not Logged In)
- [ ] Clear localStorage: `localStorage.clear()`
- [ ] Navigate to http://localhost:9000/dashboard
- [ ] Should redirect to `/login`

### 2. Multiple Logins
- [ ] Login successfully
- [ ] Check `personal_access_tokens` table
- [ ] Login again from another tab
- [ ] Both tokens should be valid
- [ ] Logout from one tab
- [ ] That token should be deleted from database

### 3. Expired/Invalid Token
- [ ] Login successfully
- [ ] Manually edit token in localStorage to invalid value
- [ ] Try to access dashboard
- [ ] Should get 401 and redirect to login

## Success Criteria

All checkboxes should be checked. If any test fails:
1. Check the troubleshooting guide in TROUBLESHOOTING.md
2. Review Laravel logs: `backend/storage/logs/laravel.log`
3. Check browser console for frontend errors
4. Verify all environment variables are set correctly
