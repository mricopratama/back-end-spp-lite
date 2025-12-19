# API Documentation - New Features

## 🎉 FITUR BARU YANG SUDAH DIBUAT

### 1. 💰 PAYMENT MANAGEMENT (Catat Pembayaran)

#### **Record Payment (Admin Only)**
```http
POST /api/payments
Authorization: Bearer {token}
Content-Type: application/json

{
  "invoice_id": 1,
  "amount": 500000,
  "payment_date": "2025-12-19",
  "payment_method": "CASH",  // CASH atau TRANSFER
  "notes": "Pembayaran SPP Desember"
}

Response (201):
{
  "success": true,
  "message": "Payment recorded successfully",
  "data": {
    "payment": {
      "id": 1,
      "receipt_number": "RCP/2025/12/0001",
      "amount": 500000,
      "payment_method": "CASH",
      "payment_date": "2025-12-19"
    },
    "invoice": {
      "invoice_number": "INV/2025/12/0001",
      "student_name": "Ahmad Fauzan",
      "total_amount": 1000000,
      "paid_amount": 500000,
      "remaining_amount": 500000,
      "status": "PARTIAL"  // UNPAID, PARTIAL, atau PAID
    }
  }
}
```

**Features:**
- ✅ Auto-generate receipt number (RCP/YYYY/MM/XXXX)
- ✅ Auto-update invoice status (UNPAID → PARTIAL → PAID)
- ✅ Support partial payment (cicilan)
- ✅ Support overpayment (dibatasi sampai total_amount)
- ✅ Create notification otomatis untuk wali murid
- ✅ Validation: payment method harus CASH atau TRANSFER

---

#### **List All Payments**
```http
GET /api/payments?payment_method=CASH&date_from=2025-12-01&date_to=2025-12-31&search=Ahmad&per_page=15
Authorization: Bearer {token}

Response (200):
{
  "success": true,
  "message": "List of payments",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "receipt_number": "RCP/2025/12/0001",
        "amount": 500000,
        "payment_date": "2025-12-19",
        "payment_method": "CASH",
        "notes": "Pembayaran SPP",
        "invoice": {
          "invoice_number": "INV/2025/12/0001",
          "student": {
            "full_name": "Ahmad Fauzan",
            "nis": "2025001"
          }
        },
        "processed_by": {
          "full_name": "Admin Sekolah"
        }
      }
    ],
    "total": 1,
    "per_page": 15
  }
}
```

**Query Parameters:**
- `payment_method` - Filter by CASH or TRANSFER
- `date_from` - Filter dari tanggal
- `date_to` - Filter sampai tanggal
- `student_id` - Filter by student ID
- `search` - Cari by receipt number atau nama siswa
- `per_page` - Items per page (default: 15)

---

#### **Get Payment Detail**
```http
GET /api/payments/{payment_id}
Authorization: Bearer {token}

Response (200):
{
  "success": true,
  "message": "Payment detail fetched",
  "data": {
    "id": 1,
    "receipt_number": "RCP/2025/12/0001",
    "amount": 500000,
    "payment_date": "2025-12-19",
    "payment_method": "CASH",
    "notes": "Pembayaran SPP",
    "invoice": {
      "invoice_number": "INV/2025/12/0001",
      "total_amount": 1000000,
      "paid_amount": 500000,
      "status": "PARTIAL",
      "student": {
        "nis": "2025001",
        "full_name": "Ahmad Fauzan"
      },
      "items": [
        {
          "fee_category": {
            "name": "SPP"
          },
          "description": "SPP Desember 2025",
          "amount": 500000
        }
      ]
    },
    "processed_by": {
      "full_name": "Admin Sekolah"
    }
  }
}
```

---

#### **Get Payment History by Student**
```http
GET /api/payments/student/{student_id}?date_from=2025-01-01&academic_year_id=1
Authorization: Bearer {token}

Response (200):
{
  "success": true,
  "message": "Payment history fetched",
  "data": {
    "summary": {
      "total_paid": 3500000,
      "payment_count": 7
    },
    "payments": {
      "data": [...]
    }
  }
}
```

---

#### **My Payment History (Wali Murid)**
```http
GET /api/payments/my
Authorization: Bearer {token}  // Student/Wali Murid token

Response (200):
{
  "success": true,
  "message": "Payment history fetched",
  "data": {
    "summary": {
      "total_paid": 3500000,
      "payment_count": 7
    },
    "payments": [...]
  }
}
```

---

### 2. 🎴 KARTU SPP DIGITAL

#### **Get SPP Card for Student (Admin)**
```http
GET /api/students/{student_id}/spp-card?academic_year_id=1
Authorization: Bearer {token}

Response (200):
{
  "success": true,
  "message": "SPP Card fetched successfully",
  "data": {
    "student": {
      "id": 1,
      "nis": "2025001",
      "full_name": "Ahmad Fauzan",
      "class": "5.A"
    },
    "academic_year": {
      "id": 1,
      "name": "2025/2026"
    },
    "monthly_status": [
      {
        "month": 7,
        "month_name": "Juli",
        "year": 2025,
        "invoice_id": 1,
        "invoice_number": "INV/2025/07/0001",
        "amount": 500000,
        "paid_amount": 500000,
        "remaining_amount": 0,
        "status": "PAID",
        "status_color": "green",  // green, yellow, red
        "progress_percentage": 100,
        "due_date": "2025-07-10",
        "payment_date": "2025-07-05",
        "items": [
          {
            "category": "SPP",
            "description": "SPP Juli 2025",
            "amount": 500000
          }
        ]
      },
      {
        "month": 8,
        "month_name": "Agustus",
        "year": 2025,
        "invoice_id": 2,
        "invoice_number": "INV/2025/08/0001",
        "amount": 500000,
        "paid_amount": 250000,
        "remaining_amount": 250000,
        "status": "PARTIAL",
        "status_color": "yellow",
        "progress_percentage": 50,
        "due_date": "2025-08-10",
        "payment_date": "2025-08-05",
        "items": [...]
      },
      {
        "month": 9,
        "month_name": "September",
        "year": 2025,
        "invoice_id": 3,
        "invoice_number": "INV/2025/09/0001",
        "amount": 500000,
        "paid_amount": 0,
        "remaining_amount": 500000,
        "status": "UNPAID",
        "status_color": "red",
        "progress_percentage": 0,
        "due_date": "2025-09-10",
        "payment_date": null,
        "items": [...]
      }
    ],
    "summary": {
      "total_months": 12,
      "paid_months": 7,
      "partial_months": 2,
      "unpaid_months": 3,
      "total_amount": 6000000,
      "paid_amount": 4250000,
      "remaining": 1750000,
      "percentage": 70.83
    }
  }
}
```

**Status Colors:**
- 🟢 `green` - PAID (Lunas)
- 🟡 `yellow` - PARTIAL (Cicilan)
- 🔴 `red` - UNPAID (Belum Bayar)

---

#### **My SPP Card (Wali Murid)**
```http
GET /api/students/my/spp-card?academic_year_id=1
Authorization: Bearer {token}  // Student/Wali Murid token

Response: Same as above
```

---

### 3. 🔔 NOTIFICATION SYSTEM (Basic Version)

#### **Get All Notifications**
```http
GET /api/notifications?type=PAYMENT_SUCCESS&is_read=false&per_page=15
Authorization: Bearer {token}

Response (200):
{
  "success": true,
  "message": "Notifications fetched successfully",
  "data": {
    "notifications": {
      "data": [
        {
          "id": 1,
          "title": "Pembayaran Berhasil",
          "message": "Pembayaran sebesar Rp 500.000 untuk invoice INV/2025/12/0001 telah berhasil dicatat.",
          "type": "PAYMENT_SUCCESS",
          "is_read": false,
          "created_at": "2025-12-19T10:30:00.000000Z"
        },
        {
          "id": 2,
          "title": "Invoice Baru",
          "message": "Invoice INV/2025/12/0002 telah dibuat untuk bulan Desember 2025.",
          "type": "INVOICE_CREATED",
          "is_read": true,
          "created_at": "2025-12-18T15:20:00.000000Z"
        }
      ]
    },
    "unread_count": 5
  }
}
```

**Notification Types:**
- `PAYMENT_SUCCESS` - Notif pembayaran sukses
- `PAYMENT_REMINDER` - Reminder tagihan
- `INVOICE_CREATED` - Invoice baru dibuat
- `GENERAL` - Notifikasi umum

---

#### **Mark Notification as Read**
```http
PUT /api/notifications/{notification_id}/read
Authorization: Bearer {token}

Response (200):
{
  "success": true,
  "message": "Notification marked as read",
  "data": {
    "id": 1,
    "is_read": true
  }
}
```

---

#### **Mark All Notifications as Read**
```http
PUT /api/notifications/read-all
Authorization: Bearer {token}

Response (200):
{
  "success": true,
  "message": "All notifications marked as read",
  "data": {
    "updated_count": 5
  }
}
```

---

#### **Get Unread Count**
```http
GET /api/notifications/unread-count
Authorization: Bearer {token}

Response (200):
{
  "success": true,
  "message": "Unread count fetched",
  "data": {
    "unread_count": 5
  }
}
```

---

#### **Delete Notification**
```http
DELETE /api/notifications/{notification_id}
Authorization: Bearer {token}

Response (200):
{
  "success": true,
  "message": "Notification deleted successfully",
  "data": null
}
```

---

## 📊 DATABASE CHANGES

### **payments table** (Updated)
```sql
- receipt_number (VARCHAR, UNIQUE) - Auto-generated RCP/YYYY/MM/XXXX
- payment_method (ENUM: 'CASH', 'TRANSFER')
```

### **notifications table** (New)
```sql
- id (BIGINT, PK)
- user_id (BIGINT, FK to users)
- title (VARCHAR)
- message (TEXT)
- type (ENUM: 'PAYMENT_SUCCESS', 'PAYMENT_REMINDER', 'INVOICE_CREATED', 'GENERAL')
- is_read (BOOLEAN, default: false)
- created_at, updated_at (TIMESTAMP)
```

---

## 🎯 USE CASE EXAMPLES

### Use Case 1: Admin Catat Pembayaran Tunai
```
1. Admin pilih invoice siswa
2. POST /api/payments
   {
     "invoice_id": 123,
     "amount": 500000,
     "payment_method": "CASH",
     "payment_date": "2025-12-19",
     "notes": "Bayar SPP Desember"
   }
3. System:
   - Generate receipt: RCP/2025/12/0001
   - Update invoice paid_amount
   - Update invoice status
   - Create notification untuk wali murid
4. Return payment & invoice details
```

### Use Case 2: Wali Murid Cek Kartu SPP
```
1. Wali murid login
2. GET /api/students/my/spp-card
3. System return:
   - Status 12 bulan dengan warna
   - Progress bar percentage
   - Summary total bayar/sisa
4. UI display kartu SPP dengan indikator warna
```

### Use Case 3: Wali Murid Cek Notifikasi
```
1. GET /api/notifications/unread-count
   Response: { "unread_count": 3 }
2. GET /api/notifications?is_read=false
   Response: List notifikasi belum dibaca
3. PUT /api/notifications/{id}/read
   Mark notifikasi sebagai dibaca
```

---

## ✅ CHECKLIST IMPLEMENTASI

### CRITICAL Features (DONE ✅)
- [x] PaymentController - Record payment
- [x] Auto-update invoice status
- [x] Auto-generate receipt number
- [x] Support partial payment (cicilan)
- [x] Support overpayment (cap at total_amount)
- [x] Payment method dropdown (CASH, TRANSFER)
- [x] Kartu SPP Digital endpoint
- [x] Color indicator (green/yellow/red)
- [x] Progress bar percentage
- [x] Notification system (basic)
- [x] Auto-create notification on payment

### Database Changes (DONE ✅)
- [x] Add receipt_number to payments table
- [x] Change payment_method to ENUM
- [x] Create notifications table
- [x] Update Payment model
- [x] Create Notification model
- [x] Update User model with notifications relationship

### API Routes (DONE ✅)
- [x] POST /api/payments - Record payment
- [x] GET /api/payments - List payments
- [x] GET /api/payments/{id} - Payment detail
- [x] GET /api/payments/my - My payments (wali murid)
- [x] GET /api/students/{id}/spp-card - SPP Card
- [x] GET /api/students/my/spp-card - My SPP Card
- [x] GET /api/notifications - List notifications
- [x] PUT /api/notifications/{id}/read - Mark as read
- [x] PUT /api/notifications/read-all - Mark all as read
- [x] GET /api/notifications/unread-count - Unread count
- [x] DELETE /api/notifications/{id} - Delete notification

---

## 🚀 NEXT STEPS (Future Development)

### PHASE 2 (Optional)
- [ ] Export Payment Receipt to PDF
- [ ] Export Reports to Excel
- [ ] Soft delete for payments & invoices
- [ ] Payment reversal/cancellation
- [ ] Discount/Subsidi feature

### PHASE 3 (Advanced)
- [ ] Automated payment reminders (cron job)
- [ ] Email/Push notifications
- [ ] Payment installment plan
- [ ] Audit log for financial transactions
- [ ] PWA optimization (offline support)

---

## 📝 NOTES

1. **Receipt Number Format**: RCP/YYYY/MM/XXXX (auto-increment per bulan)
2. **Invoice Status Flow**: UNPAID → PARTIAL → PAID
3. **Payment Method**: Currently CASH & TRANSFER (easily extensible to E_WALLET)
4. **Notification**: Auto-created on payment success
5. **SPP Card**: Groups invoices by month based on due_date
6. **Multiple Invoice Payment**: NOT supported (per requirement)
7. **Overpayment**: Allowed but capped at total_amount

---

## 🔐 AUTHORIZATION

- **Admin**: Can record payments, view all payments, view all SPP cards
- **Student/Wali Murid**: Can view own payments, own SPP card, own notifications
- **Role Middleware**: Applied on routes where needed

---

**Implementation Date**: December 19, 2025  
**Status**: ✅ PRODUCTION READY  
**Migration**: ✅ COMPLETED
