# 📋 MIGRATION CHECKLIST: HK-RBMS REST API & Vue 3 SPA

## 📌 Architecture Decisions
- **Authentication**: Sanctum Stateful SPA Authentication (Cookie-based, `EnsureFrontendRequestsAreStateful`, `/sanctum/csrf-cookie`).
- **Route Structure**: All API endpoints under `routes/api.php` prefixed with `/api/v1/...`.
- **Response Format**: Standardized JSON envelope:
  - Success: `{ "success": true, "message": "...", "data": {...}, "meta": {...} }`
  - Error: `{ "success": false, "message": "...", "errors": {...} }`

---

## 🛠️ Phase Breakdown & Status

### Phase 0: Audit & Preparation
- [x] Scan codebase for Inertia usage (`Inertia::render`, `HandleInertiaRequests`, `@inertiajs/vue3`).
- [x] Create `MIGRATION_CHECKLIST.md`.

### Phase 1: Backend Foundation (Remove Inertia & Setup API Framework)
- [ ] Remove Inertia packages (`inertiajs/inertia-laravel`, `@inertiajs/vue3`).
- [ ] Remove `HandleInertiaRequests` from `bootstrap/app.php`.
- [ ] Configure `config/sanctum.php` & `config/cors.php`.
- [ ] Create `ApiResponse` trait (`app/Http/Traits/ApiResponse.php`).
- [ ] Create Form Requests (`StoreBookRequest`, `UpdateBookRequest`, `StoreRoomRequest`, `UpdateRoomRequest`, `StoreUserRequest`, `UpdateUserRequest`, `StoreOrganizationRequest`, `UpdateOrganizationRequest`).
- [ ] Create API Resources (`UserResource`, `BookResource`, `RoomResource`, `OrganizationResource`, `ActivityLogResource`).
- [ ] Configure JSON-only Exception Handling in `bootstrap/app.php`.

### Phase 2: Auth Endpoints (`/api/v1/auth/*`)
- [ ] `POST /api/v1/auth/login`
- [ ] `POST /api/v1/auth/logout`
- [ ] `GET /api/v1/auth/me`
- [ ] `POST /api/v1/auth/register` (if enabled)
- [ ] `POST /api/v1/auth/forgot-password` & `POST /api/v1/auth/reset-password`
- [ ] 2FA endpoints conversion (`enable`, `confirm`, `challenge`, `recovery-codes`).

### Phase 3: Module Controllers Conversion (`/api/v1/*`)
- [ ] **Dashboard**: `GET /api/v1/dashboard/user`, `GET /api/v1/dashboard/admin`
- [ ] **Bookings (User)**: `GET /api/v1/bookings`, `POST /api/v1/bookings`, `GET /api/v1/bookings/{id}`, `PUT /api/v1/bookings/{id}`, `DELETE /api/v1/bookings/{id}`
- [ ] **Bookings (Admin)**: `GET /api/v1/admin/bookings`, `POST /api/v1/admin/bookings/{id}/approve`, `POST /api/v1/admin/bookings/{id}/reject`, `POST /api/v1/admin/bookings/bulk-approve`, `POST /api/v1/admin/bookings/bulk-reject`
- [ ] **Rooms (Admin)**: `GET /api/v1/admin/rooms`, `POST /api/v1/admin/rooms`, `GET /api/v1/admin/rooms/{id}`, `PUT /api/v1/admin/rooms/{id}`, `DELETE /api/v1/admin/rooms/{id}`
- [ ] **Users (Admin)**: `GET /api/v1/admin/users`, `POST /api/v1/admin/users`, `PUT /api/v1/admin/users/{id}`, `DELETE /api/v1/admin/users/{id}`, `POST /api/v1/admin/users/{id}/reset-password`
- [ ] **Organizations (Admin)**: `GET /api/v1/admin/organizations`, `POST /api/v1/admin/organizations`, `PUT /api/v1/admin/organizations/{id}`, `DELETE /api/v1/admin/organizations/{id}`
- [ ] **Activity Logs (Admin)**: `GET /api/v1/admin/activity-logs`, `GET /api/v1/admin/activity-logs/export`
- [ ] **Public Display**: `GET /api/v1/display/rooms/{room}`

### Phase 4: Server-Side Authorization & Enforcement
- [ ] Create Policies (`BookPolicy`, `RoomPolicy`, `UserPolicy`, `OrganizationPolicy`).
- [ ] Server-side schedule conflict check enforcement (`Book::hasConflict()`).
- [ ] Server-side weekend restriction rule.
- [ ] Admin self-delete protection enforcement on API endpoint.
- [ ] User auto-lock requester_id & organization_id override on server-side.
- [ ] Automated tests (Pest / PHPUnit) for authorization & business rules.

### Phase 5: Frontend SPA Conversion
- [ ] Setup Axios instance (`resources/js/lib/api.ts`) with `withCredentials: true` and interceptors.
- [ ] Update `authStore` to fetch state from `GET /api/v1/auth/me`.
- [ ] Update `resources/js/app.ts` to mount Vue app + Vue Router (without `createInertiaApp`).
- [ ] Update `resources/views/app.blade.php` to clean SPA entry point.
- [ ] Migrate Vue Pages to Axios calls (remove all `@inertiajs/vue3` imports).
- [ ] Setup Vue Router navigation guards for UX protection.

### Phase 6: Cleanup & Documentation
- [ ] Clean unused dependencies.
- [ ] Update `public/PROJECT_ANALYSIS.md` & `API_DOCUMENTATION.md`.
- [ ] Update `.env.example` (CORS/Sanctum configuration).
- [ ] Run full automated test suite.
