# 🗓️ Booking Management System

A **Laravel 11** application for managing client bookings, complete with validation rules, overlap prevention, weekly reporting, and a minimal, clean front-end.

This project was designed to meet the given technical specification with an emphasis on **SOLID principles**, **clean architecture**, **data integrity**, and **user experience**.

---

## 🚀 Features

### ✅ Core Functionality
- Create new bookings with:
  - Title & optional description  
  - Start & end datetime  
  - Associated **User** and **Client**
- Each booking is serialized via an API resource for consistent structure.

### 🧠 Business Logic
- Prevent overlapping bookings for the same user (even **partially overlapping** ones).
- Allow back-to-back (adjacent) bookings that **touch but do not overlap**.
- Reject bookings with past start times.
- Validate that end time > start time.
- Require valid existing `user_id` and `client_id`.

### ⚡ Overlap Logic — Real-World Precision
The system correctly handles **edge cases where a booking starts or ends exactly at the boundary** of another booking or week.  
It uses this overlap rule:

```php
where('start_time', '<', $end)
where('end_time', '>', $start)
```

This ensures:
- Bookings that **partially overlap** a week (e.g. start before Monday but end after Monday) are included.  
- Bookings that start **exactly when another ends** are allowed (no false positives).  
- Bookings that start **right after the week ends** are excluded properly.

Example scenarios:

| Case | Start | End | Included? |
|------|--------|------|-----------|
| Spans into week | 2025-08-03 23:59:59 → 2025-08-04 00:59:59 | ✅ |
| Fully inside week | 2025-08-04 00:00:00 → 2025-08-04 01:00:00 | ✅ |
| Ends exactly at week end | 2025-08-10 23:00:00 → 2025-08-10 23:59:59 | ✅ |
| Starts after week ends | 2025-08-10 23:59:59 → 2025-08-11 00:59:59 | ❌ |

This logic was verified with automated tests and ensures accurate week boundary inclusion.

---

### 📅 Weekly Bookings Endpoint
- Retrieve all bookings within a specific calendar week using `?week=YYYY-MM-DD`.
- Returns structured `data` and `meta` (week start, week end, total count).
- Handles invalid or missing week parameters gracefully.
- Includes **any booking that overlaps the week**, not just those fully contained.

---

### 🧱 Data Model
| Entity | Description |
|---------|--------------|
| **User** | Booking owner |
| **Client** | The client associated with the booking |
| **Booking** | Contains title, description, start/end times, and associations |

### 🧩 Validation Rules
- All input validated via **Form Requests**
- Trimmed and sanitized inputs
- Custom **NoOverlapRule** to detect time conflicts per user

---

### 🧰 API Endpoints
| Method | Endpoint | Description |
|---------|-----------|-------------|
| `GET /api/users` | Fetch all users |
| `GET /api/clients` | Fetch all clients |
| `POST /api/bookings` | Create a new booking |
| `GET /api/bookings?week=YYYY-MM-DD` | List bookings for a specific week |

---

## 💻 Front-End (Blade + Vanilla JS)

The UI uses simple, reactive logic without any frontend framework dependencies.

### 🧩 Key Features
- Dynamically loads users and clients from `/api/users` and `/api/clients`
- Displays “No users/clients found” if the database is empty
- Disables form submission when data is missing
- Validates start/end time before submission
- Displays real-time feedback messages (success/error)

### 🪶 UI Components
| Component | Description |
|------------|--------------|
| `<x-input>` | Reusable input field |
| `<x-textarea>` | Reusable textarea |
| `<x-select>` | Dropdown for users/clients |
| `<x-button>` | Submit button |
| `<div id="messageBox">` | Dynamic status message area |

---

## 🧪 Automated Tests

The project includes comprehensive **Feature Tests** covering all business logic.

### ✅ Test Files
| File | Purpose |
|------|----------|
| `CreateBookingTest.php` | CRUD and validation tests |
| `PreventOverlapTest.php` | Overlapping/adjacent logic |
| `WeeklyBookingsTest.php` | Weekly API correctness and boundary tests |
| `BookingEndToEndTest.php` | Full system integration test |

### 🧠 Scenarios Covered
- Booking creation and validation
- Optional description handling
- Overlap prevention (including **boundary and partial overlap cases**)
- Weekly report with boundary inclusions
- Handling of empty database (no users/clients)
- Input trimming and sanitization
- Past date rejection
- Concurrency and transaction safety

To run all tests:

```bash
php artisan test
```

Or run the end-to-end suite only:

```bash
php artisan test --filter=BookingEndToEndTest
```

---

## ⚙️ Installation & Setup

### 🪟 Requirements
- PHP 8.3+
- Composer
- Node.js & npm
- SQLite / MySQL
- Laravel 11.x

### 🧰 Setup Steps

```bash
# 1. Clone the repository
git clone https://github.com/your-username/booking-system.git
cd booking-system

# 2. Install dependencies
composer install
npm install

# 3. Environment setup
cp .env.example .env
php artisan key:generate

# 4. Configure your DB in .env (SQLite or MySQL)

# 5. Run migrations and seeders
php artisan migrate --seed

# 6. Build frontend assets
npm run build

# 7. Serve the app
php artisan serve
```

---

## 🧪 Quick Manual Test Plan

| Scenario | Expected Outcome |
|-----------|------------------|
| No users/clients exist | Form disabled + “No users/clients found” message |
| Valid booking submission | Success message + DB entry created |
| Overlapping booking | Red error “Overlaps another booking” |
| Adjacent booking | Allowed successfully |
| Past start time | Validation error |
| Weekly endpoint (API) | Returns all bookings that **overlap** the specified week |
| Missing week param | Returns `400` with error message |

---

## 🧾 Design Highlights

- **SOLID structure** — each class has a clear boundary and purpose.
- **Form Requests** encapsulate all input validation.
- **Custom Rule (NoOverlap)** enforces critical business logic.
- **Overlap handling includes partial and boundary scenarios.**
- **API Resource** standardizes JSON output.
- **DRY & Clean** — no duplicate logic across controllers or validation.
- **UX-aware front-end** that adapts to empty states and validation errors.
- **Comprehensive test coverage** ensures no regressions.

---

