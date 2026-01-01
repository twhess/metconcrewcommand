# API Documentation

Base URL: `http://localhost:8000/api`

## Public Endpoints

### Health Check
```http
GET /health
```

**Response:**
```json
{
  "status": "ok"
}
```

### Login
```http
POST /auth/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password123"
}
```

**Success Response (200):**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com"
  }
}
```

**Error Response (422):**
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

## Protected Endpoints

All protected endpoints require the `Authorization` header:
```http
Authorization: Bearer {token}
```

### Get Current User
```http
GET /me
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com"
  }
}
```

**Error Response (401):**
```json
{
  "message": "Unauthenticated."
}
```

### Logout
```http
POST /auth/logout
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

## Error Codes

- `200` - Success
- `401` - Unauthorized (invalid or missing token)
- `422` - Validation Error (invalid input)
- `500` - Server Error

## CORS Configuration

The API accepts requests from:
- `http://localhost:9000` (Quasar dev server)

Allowed methods: `GET, POST, PUT, DELETE, OPTIONS`

## Rate Limiting

Default Laravel rate limiting applies:
- 60 requests per minute for API endpoints
