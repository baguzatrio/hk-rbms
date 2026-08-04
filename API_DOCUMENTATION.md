# 📖 HK-RBMS REST API Documentation (V1)

**Base URL**: `/api/v1`

---

## 🔐 Authentication Endpoints

### 1. `POST /api/v1/auth/login`
- **Auth**: None
- **Body**: `{ "email": "admin@hk.co.id", "password": "password" }`
- **Response**: `200 OK`
```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "user": {
      "id": 1,
      "name": "Administrator HK",
      "email": "admin@hk.co.id",
      "role": "admin"
    }
  }
}
```

### 2. `GET /api/v1/auth/me`
- **Auth**: `auth:sanctum`
- **Response**: `200 OK`

### 3. `POST /api/v1/auth/logout`
- **Auth**: `auth:sanctum`
- **Response**: `200 OK`

---

## 📊 Dashboard Endpoints

### 1. `GET /api/v1/dashboard/user`
- **Auth**: `auth:sanctum`
- **Query Params**: `room_id`, `organization_id`
- **Response**: Data for `my_bookings`, `today_schedule`, `calendar_events`, `rooms`, `organizations`.

### 2. `GET /api/v1/dashboard/stats` (Admin)
- **Auth**: `auth:sanctum`, `role:admin`
- **Response**: Summary counts (`pending_count`, `approved_today`, `occupied_rooms`, `vacant_rooms`, `total_users`) and `recent_pending`.

---

## 📅 Booking Endpoints

### 1. `GET /api/v1/bookings`
- **Auth**: `auth:sanctum`
- **Response**: Paginated list of user's bookings.

### 2. `POST /api/v1/bookings`
- **Auth**: `auth:sanctum`
- **Body**:
```json
{
  "room_id": 1,
  "title": "Rapat Divisi IT",
  "start_date": "2026-08-10",
  "end_date": "2026-08-10",
  "start_hour": "09:00",
  "finish_hour": "11:00",
  "notes": "Persiapan migrasi",
  "requester_id": 2
}
```
- **Response**: `201 Created`

### 3. `GET /api/v1/bookings/{id}`
- **Auth**: `auth:sanctum`

### 4. `PUT /api/v1/bookings/{id}`
- **Auth**: `auth:sanctum`

### 5. `DELETE /api/v1/bookings/{id}`
- **Auth**: `auth:sanctum`

---

## 🛡️ Admin Booking Management Endpoints

### 1. `POST /api/v1/admin/bookings/{id}/approve`
- **Auth**: `auth:sanctum`, `role:admin`

### 2. `POST /api/v1/admin/bookings/{id}/reject`
- **Auth**: `auth:sanctum`, `role:admin`
- **Body**: `{ "rejection_note": "Ruangan sedang digunakan untuk perbaikan AC." }`

### 3. `POST /api/v1/admin/bookings/bulk-approve`
- **Auth**: `auth:sanctum`, `role:admin`
- **Body**: `{ "ids": [1, 2, 3] }`

### 4. `POST /api/v1/admin/bookings/bulk-reject`
- **Auth**: `auth:sanctum`, `role:admin`
- **Body**: `{ "ids": [4, 5], "rejection_note": "Alasan penolakan massal" }`

---

## 🏬 Master Data Endpoints (Admin)

- `GET /api/v1/admin/rooms`
- `POST /api/v1/admin/rooms`
- `PUT /api/v1/admin/rooms/{id}`
- `DELETE /api/v1/admin/rooms/{id}`

- `GET /api/v1/admin/users`
- `POST /api/v1/admin/users`
- `PUT /api/v1/admin/users/{id}`
- `DELETE /api/v1/admin/users/{id}`
- `POST /api/v1/admin/users/{id}/reset-password`

- `GET /api/v1/admin/organizations`
- `POST /api/v1/admin/organizations`
- `PUT /api/v1/admin/organizations/{id}`
- `DELETE /api/v1/admin/organizations/{id}`

---

## 📺 Public TV Display Endpoint

### `GET /api/v1/display/rooms/{room_id}`
- **Auth**: None (Public)
- **Response**: Room info, current active meeting, next upcoming meetings.
