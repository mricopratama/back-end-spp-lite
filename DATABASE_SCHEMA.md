# SPP Lite - Database Schema Documentation

## Overview
Aplikasi Laravel untuk manajemen pembayaran SPP (Sumbangan Pembinaan Pendidikan) dengan sistem invoicing terintegrasi.

## Database Structure

### Migration Files (Urutan Eksekusi)
1. `0001_01_01_000000_create_users_table.php` - Password reset & sessions
2. `0001_01_01_000001_create_cache_table.php` - Cache table
3. `0001_01_01_000002_create_jobs_table.php` - Queue jobs
4. **`2025_01_01_000001_create_roles_table.php`** - Roles table
5. **`2025_01_01_000002_create_academic_years_table.php`** - Academic years
6. **`2025_01_01_000003_create_classes_table.php`** - Classes
7. **`2025_01_01_000004_create_fee_categories_table.php`** - Fee categories
8. **`2025_01_01_000005_create_students_table.php`** - Students
9. **`2025_01_01_000006_create_users_table.php`** - Users (custom)
10. **`2025_01_01_000007_create_student_class_history_table.php`** - Student-Class-Year pivot
11. **`2025_01_01_000008_create_invoices_table.php`** - Invoices
12. **`2025_01_01_000009_create_invoice_items_table.php`** - Invoice items
13. **`2025_01_01_000010_create_payments_table.php`** - Payments

## Module Structure

### MODULE: AUTH & USERS

#### Table: `roles`
**Model:** `App\Models\Role`

| Column | Type | Attributes |
|--------|------|------------|
| id | bigint unsigned | PK, Auto Increment |
| name | varchar(255) | Unique |
| description | text | Nullable |
| created_at | timestamp | Nullable |
| updated_at | timestamp | Nullable |

**Relationships:**
- `hasMany(User)` - A role can have many users

---

#### Table: `users`
**Model:** `App\Models\User`

| Column | Type | Attributes |
|--------|------|------------|
| id | bigint unsigned | PK, Auto Increment |
| username | varchar(255) | Unique |
| password_hash | varchar(255) | - |
| role_id | bigint unsigned | FK -> roles.id |
| student_id | bigint unsigned | FK -> students.id, Nullable |
| created_at | timestamp | Nullable |
| updated_at | timestamp | Nullable |

**Relationships:**
- `belongsTo(Role)` - User belongs to a role
- `belongsTo(Student)` - User can be associated with a student
- `hasMany(Payment, 'processed_by')` - User can process many payments

**Foreign Keys:**
- `role_id` → `roles.id` (CASCADE DELETE)
- `student_id` → `students.id` (CASCADE DELETE)

---

### MODULE: ACADEMIC

#### Table: `academic_years`
**Model:** `App\Models\AcademicYear`

| Column | Type | Attributes |
|--------|------|------------|
| id | bigint unsigned | PK, Auto Increment |
| name | varchar(255) | Unique |
| is_active | boolean | Default: false |
| created_at | timestamp | Nullable |
| updated_at | timestamp | Nullable |

**Relationships:**
- `hasMany(StudentClassHistory)` - Academic year has many class histories
- `hasMany(Invoice)` - Academic year has many invoices

---

#### Table: `classes`
**Model:** `App\Models\Classes`

| Column | Type | Attributes |
|--------|------|------------|
| id | bigint unsigned | PK, Auto Increment |
| name | varchar(255) | - |
| level | varchar(255) | - |
| created_at | timestamp | Nullable |
| updated_at | timestamp | Nullable |

**Relationships:**
- `hasMany(StudentClassHistory, 'class_id')` - Class has many student histories
- `belongsToMany(Student)` through `student_class_history` - Many-to-many with students

---

#### Table: `students`
**Model:** `App\Models\Student`

| Column | Type | Attributes |
|--------|------|------------|
| id | bigint unsigned | PK, Auto Increment |
| nis | varchar(255) | Unique (Nomor Induk Siswa) |
| full_name | varchar(255) | - |
| address | text | Nullable |
| status | enum | Values: 'active', 'inactive', 'graduated', 'dropped' |
| spp_base_fee | decimal(12,2) | Default: 0 |
| created_at | timestamp | Nullable |
| updated_at | timestamp | Nullable |

**Relationships:**
- `hasOne(User)` - Student can have one user account
- `hasMany(StudentClassHistory)` - Student has many class histories
- `hasMany(Invoice)` - Student has many invoices
- `belongsToMany(Classes)` through `student_class_history` - Many-to-many with classes

---

#### Table: `student_class_history`
**Model:** `App\Models\StudentClassHistory`

| Column | Type | Attributes |
|--------|------|------------|
| id | bigint unsigned | PK, Auto Increment |
| student_id | bigint unsigned | FK -> students.id |
| class_id | bigint unsigned | FK -> classes.id |
| academic_year_id | bigint unsigned | FK -> academic_years.id |
| created_at | timestamp | Nullable |
| updated_at | timestamp | Nullable |

**Unique Constraint:** `student_class_academic_unique` (student_id, class_id, academic_year_id)

**Relationships:**
- `belongsTo(Student)` - History belongs to a student
- `belongsTo(Classes, 'class_id')` - History belongs to a class
- `belongsTo(AcademicYear)` - History belongs to an academic year

**Foreign Keys:**
- `student_id` → `students.id` (CASCADE DELETE)
- `class_id` → `classes.id` (CASCADE DELETE)
- `academic_year_id` → `academic_years.id` (CASCADE DELETE)

---

### MODULE: FINANCE

#### Table: `fee_categories`
**Model:** `App\Models\FeeCategory`

| Column | Type | Attributes |
|--------|------|------------|
| id | bigint unsigned | PK, Auto Increment |
| name | varchar(255) | Unique |
| default_amount | decimal(12,2) | Default: 0 |
| created_at | timestamp | Nullable |
| updated_at | timestamp | Nullable |

**Relationships:**
- `hasMany(InvoiceItem)` - Fee category has many invoice items

---

#### Table: `invoices`
**Model:** `App\Models\Invoice`

| Column | Type | Attributes |
|--------|------|------------|
| id | bigint unsigned | PK, Auto Increment |
| invoice_number | varchar(255) | Unique |
| title | varchar(255) | - |
| total_amount | decimal(12,2) | Default: 0 |
| paid_amount | decimal(12,2) | Default: 0 |
| status | enum | Values: 'unpaid', 'partial', 'paid' |
| due_date | date | - |
| student_id | bigint unsigned | FK -> students.id |
| academic_year_id | bigint unsigned | FK -> academic_years.id |
| created_at | timestamp | Nullable |
| updated_at | timestamp | Nullable |

**Relationships:**
- `belongsTo(Student)` - Invoice belongs to a student
- `belongsTo(AcademicYear)` - Invoice belongs to an academic year
- `hasMany(InvoiceItem)` - Invoice has many invoice items
- `hasMany(Payment)` - Invoice has many payments

**Foreign Keys:**
- `student_id` → `students.id` (CASCADE DELETE)
- `academic_year_id` → `academic_years.id` (CASCADE DELETE)

---

#### Table: `invoice_items`
**Model:** `App\Models\InvoiceItem`

| Column | Type | Attributes |
|--------|------|------------|
| id | bigint unsigned | PK, Auto Increment |
| description | varchar(255) | - |
| amount | decimal(12,2) | Default: 0 |
| invoice_id | bigint unsigned | FK -> invoices.id |
| fee_category_id | bigint unsigned | FK -> fee_categories.id |
| created_at | timestamp | Nullable |
| updated_at | timestamp | Nullable |

**Relationships:**
- `belongsTo(Invoice)` - Item belongs to an invoice
- `belongsTo(FeeCategory)` - Item belongs to a fee category

**Foreign Keys:**
- `invoice_id` → `invoices.id` (CASCADE DELETE)
- `fee_category_id` → `fee_categories.id` (CASCADE DELETE)

---

#### Table: `payments`
**Model:** `App\Models\Payment`

| Column | Type | Attributes |
|--------|------|------------|
| id | bigint unsigned | PK, Auto Increment |
| amount | decimal(12,2) | Default: 0 |
| payment_date | date | - |
| payment_method | varchar(255) | - |
| notes | text | Nullable |
| invoice_id | bigint unsigned | FK -> invoices.id |
| processed_by | bigint unsigned | FK -> users.id |
| created_at | timestamp | Nullable |
| updated_at | timestamp | Nullable |

**Relationships:**
- `belongsTo(Invoice)` - Payment belongs to an invoice
- `belongsTo(User, 'processed_by')` - Payment processed by a user

**Foreign Keys:**
- `invoice_id` → `invoices.id` (CASCADE DELETE)
- `processed_by` → `users.id` (CASCADE DELETE)

---

## Laravel Commands

### Migration Commands
```bash
# Run all migrations
php artisan migrate

# Rollback last migration batch
php artisan migrate:rollback

# Reset and re-run all migrations
php artisan migrate:fresh

# Run migrations with seeding
php artisan migrate:fresh --seed
```

### Seeding Commands
```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=RoleSeeder
```

### Model Usage Examples

```php
// Create a new role
$role = Role::create([
    'name' => 'Admin',
    'description' => 'Administrator'
]);

// Create a student
$student = Student::create([
    'nis' => '2024001',
    'full_name' => 'John Doe',
    'address' => 'Jl. Example No. 123',
    'status' => 'active',
    'spp_base_fee' => 500000
]);

// Create an invoice for a student
$invoice = Invoice::create([
    'invoice_number' => 'INV-2025-001',
    'title' => 'SPP Januari 2025',
    'total_amount' => 500000,
    'paid_amount' => 0,
    'status' => 'unpaid',
    'due_date' => '2025-01-31',
    'student_id' => $student->id,
    'academic_year_id' => 1
]);

// Get student with their invoices
$student = Student::with('invoices')->find(1);

// Get invoice with items and payments
$invoice = Invoice::with(['invoiceItems', 'payments'])->find(1);
```

## Best Practices

1. **Cascade Deletes:** Semua foreign keys menggunakan `cascadeOnDelete()` untuk menjaga integritas data
2. **Unique Constraints:** Digunakan pada kolom-kolom seperti username, nis, invoice_number
3. **Decimal Precision:** Semua field amount menggunakan decimal(12,2) untuk presisi finansial
4. **Enum Types:** Digunakan untuk field dengan nilai terbatas (status, payment status)
5. **Timestamps:** Semua tabel memiliki created_at dan updated_at untuk audit trail

## Database Conventions

- Primary Key: `id` (bigint unsigned auto increment)
- Foreign Key: `{table_singular}_id` (e.g., student_id, role_id)
- Pivot Tables: `{table1}_{table2}_history` format
- Timestamps: `created_at`, `updated_at` (automatically managed)
- Soft Deletes: Not implemented (using hard deletes with cascade)

---

**Generated:** November 27, 2025
**Laravel Version:** 11.x
**Database:** MySQL 8.0+
