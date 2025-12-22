# 📝 API Testing Guide - Sistem SPP Bulanan

## Setup Awal

Jalankan migration dan seeder:
```bash
php artisan migrate:fresh --seed
```

## Authentication

Semua endpoint (kecuali login) memerlukan token. Tambahkan header:
```
Authorization: Bearer {token}
```

---

## 🔐 1. Login & Get Token

### Login sebagai Admin
```
POST http://localhost:8000/api/auth/login

Body (JSON):
{
    "username": "admin",
    "password": "password123"
}

Response:
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "username": "admin",
            "role": "admin"
        },
        "token": "1|xxxxxxxxxxxxx"
    }
}
```

**Salin token dari response, gunakan untuk request selanjutnya!**

---

## 📚 2. Setup Fee Category SPP

### Buat Fee Category "SPP Bulanan" (jika belum ada)
```
POST http://localhost:8000/api/fee-categories
Authorization: Bearer {token}

Body (JSON):
{
    "name": "SPP Bulanan",
    "default_amount": 150000,
    "description": "Biaya SPP per bulan"
}

Response:
{
    "success": true,
    "message": "Fee category created successfully",
    "data": {
        "id": 1,
        "name": "SPP Bulanan",
        "default_amount": "150000.00"
    }
}
```

---

## 👨‍🎓 3. Update SPP Base Fee Per Siswa

### A. Update SPP Base Fee Manual (per siswa)
```
PUT http://localhost:8000/api/students/{student_id}/spp-base-fee
Authorization: Bearer {token}

Body (JSON):
{
    "spp_base_fee": 180000
}

Contoh:
PUT http://localhost:8000/api/students/1/spp-base-fee
{
    "spp_base_fee": 180000
}

Response:
{
    "success": true,
    "message": "SPP base fee updated successfully",
    "data": {
        "student_id": 1,
        "nis": "12345",
        "full_name": "Ahmad Rizki",
        "old_spp_base_fee": "0.00",
        "new_spp_base_fee": "180000.00",
        "note": "Perubahan ini hanya berlaku untuk invoice yang dibuat setelah update ini"
    }
}
```

### B. Import SPP Base Fee dari Excel ⭐ **BARU!**
```
POST http://localhost:8000/api/students/import
Authorization: Bearer {token}

Body: form-data
- Key: file
- Type: File
- Value: [Upload file Excel]

Format Excel yang Didukung:
| NIS     | Nama          | Alamat        | No HP       | Status | SPP Base Fee |
|---------|---------------|---------------|-------------|--------|--------------|
| 2024001 | Alika Azalea  | Jl. Merdeka 1 | 8123456789  | ACTIVE | 180000       |
| 2024002 | Budi Santoso  | Jl. Sudirman 2| 8123456790  | ACTIVE | 150000       |
| 2024003 | Citra Dewi    | Jl. Gatot 3   | 8123456791  | ACTIVE | 0            |

Nama Kolom yang Dikenali:
- SPP / SPP Base Fee / Biaya SPP / SPP Bulanan / Base Fee

Format Angka yang Diterima:
- 180000 (angka biasa)
- 180.000 (dengan titik)
- Rp 180.000 (dengan prefix, otomatis dibersihkan)
- Kosong atau 0 (akan pakai default dari fee category)

Response:
{
    "success": true,
    "message": "Import completed. 3 students inserted, 0 failed.",
    "data": {
        "total_rows": 3,
        "inserted": 3,
        "failed": 0,
        "errors": [],
        "import_info": {
            "sheet_name": "Sheet1",
            "header_row": 1,
            "data_start_row": 2,
            "data_end_row": 4,
            "column_mapping": {
                "nis": "A",
                "name": "B",
                "address": "C",
                "phone": "D",
                "status": "E",
                "spp_base_fee": "F"
            },
            "sample_data": [
                "Row 2: NIS='2024001', Name='Alika Azalea', SPP='180000'",
                "Row 3: NIS='2024002', Name='Budi Santoso', SPP='150000'",
                "Row 4: NIS='2024003', Name='Citra Dewi', SPP='0'"
            ]
        }
    }
}
```

**📄 Lihat detail template di file:** `IMPORT_STUDENT_TEMPLATE.md`

**Catatan:** 
- Siswa dengan `spp_base_fee` > 0 akan pakai nilai ini
- Siswa dengan `spp_base_fee` = 0 akan pakai `default_amount` dari fee_category

---

## 📄 4. Generate Invoice SPP Bulanan

### A. Generate untuk 1 Kelas (Bulk)
```
POST http://localhost:8000/api/invoices/generate-monthly-spp
Authorization: Bearer {token}

Body (JSON):
{
    "academic_year_id": 1,
    "class_id": 1,
    "period_month": 1,
    "period_year": 2025,
    "due_date": "2025-01-10"
}

Response:
{
    "success": true,
    "message": "Successfully generated 25 monthly SPP invoices",
    "data": {
        "created": 25,
        "skipped": 0,
        "total_students": 25,
        "total_amount": "4200000.00",
        "period": "Januari 2025",
        "details": [
            {
                "student_id": 1,
                "student_name": "Ahmad Rizki",
                "student_nis": "12345",
                "invoice_number": "INV/2025/01/00001",
                "amount": "180000.00",
                "period": "Januari 2025",
                "due_date": "2025-01-10"
            },
            {
                "student_id": 2,
                "student_name": "Budi Santoso",
                "student_nis": "12346",
                "invoice_number": "INV/2025/01/00002",
                "amount": "150000.00",
                "period": "Januari 2025",
                "due_date": "2025-01-10"
            }
        ]
    }
}
```

### B. Generate untuk Siswa Tertentu (Multiple Students, 1 Bulan)
```
POST http://localhost:8000/api/invoices/generate-monthly-spp
Authorization: Bearer {token}

Body (JSON):
{
    "academic_year_id": 1,
    "student_ids": [1, 2, 3],
    "period_month": 2,
    "period_year": 2025,
    "due_date": "2025-02-10"
}
```

### C. Generate untuk 1 Siswa Saja (1 Bulan) ⭐
```
POST http://localhost:8000/api/invoices/generate-monthly-spp
Authorization: Bearer {token}

Body (JSON):
{
    "academic_year_id": 1,
    "student_ids": [1],
    "period_month": 1,
    "period_year": 2025,
    "due_date": "2025-01-10"
}

⚠️ PENTING:
- student_ids harus ARRAY walaupun cuma 1 siswa: [1] bukan 1
- period_month harus INTEGER: 1 bukan "1"
- period_year harus INTEGER: 2025 bukan "2025"

Response:
{
    "success": true,
    "message": "Successfully generated 1 monthly SPP invoices",
    "data": {
        "created": 1,
        "skipped": 0,
        "total_students": 1,
        "total_amount": "180000.00",
        "period": "Januari 2025",
        "details": [
            {
                "student_id": 1,
                "student_name": "Ahmad Rizki",
                "student_nis": "12345",
                "invoice_number": "INV/2025/01/00001",
                "amount": "180000.00",
                "period": "Januari 2025",
                "due_date": "2025-01-10"
            }
        ]
    }
}
```

### D. Generate Multiple Bulan untuk 1 Siswa ⭐⭐ **RECOMMENDED**
Gunakan endpoint khusus untuk ini:
```
POST http://localhost:8000/api/invoices/generate-missing-months
Authorization: Bearer {token}

Body (JSON):
{
    "academic_year_id": 1,
    "student_id": 1,
    "months": [
        {
            "month": 1,
            "year": 2025,
            "due_date": "2025-01-10"
        },
        {
            "month": 2,
            "year": 2025,
            "due_date": "2025-02-10"
        },
        {
            "month": 3,
            "year": 2025,
            "due_date": "2025-03-10"
        }
    ]
}

Response:
{
    "success": true,
    "message": "Successfully generated 3 missing invoices",
    "data": {
        "created": 3,
        "details": [
            {
                "invoice_number": "INV/2025/01/00001",
                "period": "Januari 2025",
                "amount": "180000.00"
            },
            {
                "invoice_number": "INV/2025/01/00002",
                "period": "Februari 2025",
                "amount": "180000.00"
            },
            {
                "invoice_number": "INV/2025/01/00003",
                "period": "Maret 2025",
                "amount": "180000.00"
            }
        ]
    }
}
```

### E. Generate Multiple Bulan untuk Seluruh Kelas (Loop)
Jika ingin generate banyak bulan untuk 1 kelas:
```
Loop 1 - Januari:
POST /api/invoices/generate-monthly-spp
{"academic_year_id": 1, "class_id": 1, "period_month": 1, "period_year": 2025, "due_date": "2025-01-10"}

Loop 2 - Februari:
POST /api/invoices/generate-monthly-spp
{"academic_year_id": 1, "class_id": 1, "period_month": 2, "period_year": 2025, "due_date": "2025-02-10"}

... dst sampai bulan 12
```

---

## 📊 5. Cek Status Pembayaran Bulanan Siswa

### Get Monthly Payment Status
```
GET http://localhost:8000/api/invoices/monthly-status/{student_id}?academic_year_id={id}
Authorization: Bearer {token}

Contoh:
GET http://localhost:8000/api/invoices/monthly-status/1?academic_year_id=1

Response:
{
    "success": true,
    "message": "Monthly payment status fetched successfully",
    "data": {
        "student": {
            "id": 1,
            "nis": "12345",
            "full_name": "Ahmad Rizki",
            "spp_base_fee": "180000.00"
        },
        "academic_year": {
            "id": 1,
            "name": "2024/2025"
        },
        "months": [
            {
                "month": 7,
                "month_name": "Juli",
                "year": 2024,
                "period": "Juli 2024",
                "invoice_id": 1,
                "invoice_number": "INV/2024/07/00001",
                "total_amount": "180000.00",
                "paid_amount": "180000.00",
                "remaining_amount": "0.00",
                "status": "paid",
                "due_date": "2024-07-10",
                "payment_date": "2024-07-05"
            },
            {
                "month": 8,
                "month_name": "Agustus",
                "year": 2024,
                "period": "Agustus 2024",
                "invoice_id": 2,
                "invoice_number": "INV/2024/08/00002",
                "total_amount": "180000.00",
                "paid_amount": "100000.00",
                "remaining_amount": "80000.00",
                "status": "partial",
                "due_date": "2024-08-10",
                "overdue": true,
                "overdue_days": 134
            },
            {
                "month": 9,
                "month_name": "September",
                "year": 2024,
                "period": "September 2024",
                "invoice_id": null,
                "status": "not_generated",
                "message": "Invoice belum dibuat"
            }
        ],
        "summary": {
            "total_months": 12,
            "paid": 1,
            "partial": 1,
            "unpaid": 0,
            "not_generated": 10,
            "total_amount": "360000.00",
            "total_paid": "280000.00",
            "total_unpaid": "80000.00",
            "total_outstanding": "80000.00"
        }
    }
}
```

---

## 💰 6. Record Pembayaran (Payment)

### A. Bayar Lunas 1 Bulan
```
POST http://localhost:8000/api/payments
Authorization: Bearer {token}

Body (JSON):
{
    "invoice_id": 1,
    "amount": 180000,
    "payment_method": "CASH",
    "payment_date": "2025-01-05",
    "notes": "Pembayaran SPP Januari 2025"
}

Response:
{
    "success": true,
    "message": "Payment recorded successfully",
    "data": {
        "payment": {
            "id": 1,
            "receipt_number": "RCP/2025/01/00001",
            "amount": "180000.00",
            "payment_method": "CASH",
            "payment_date": "2025-01-05"
        },
        "invoice": {
            "invoice_number": "INV/2025/01/00001",
            "student_name": "Ahmad Rizki",
            "total_amount": "180000.00",
            "paid_amount": "180000.00",
            "remaining_amount": "0.00",
            "status": "paid"
        }
    }
}
```

### B. Bayar Cicilan (Partial Payment)
```
POST http://localhost:8000/api/payments
Authorization: Bearer {token}

Cicilan 1:
{
    "invoice_id": 2,
    "amount": 100000,
    "payment_method": "TRANSFER",
    "payment_date": "2025-02-01",
    "notes": "Cicilan 1 SPP Februari"
}

Response: status = "partial"

Cicilan 2 (untuk melunasi):
{
    "invoice_id": 2,
    "amount": 80000,
    "payment_method": "CASH",
    "payment_date": "2025-02-10",
    "notes": "Pelunasan SPP Februari"
}

Response: status = "paid"
```

---

## 🔧 7. Generate Bulan yang Belum Dibayar (Missing Months)

### Use Case: Generate SPP untuk 1 siswa, bulan-bulan yang belum dibayar

Endpoint ini **PALING COCOK** untuk generate multiple bulan sekaligus untuk 1 siswa:

```
POST http://localhost:8000/api/invoices/generate-missing-months
Authorization: Bearer {token}

Body (JSON):
{
    "academic_year_id": 1,
    "student_id": 1,
    "months": [
        {
            "month": 9,
            "year": 2024,
            "due_date": "2024-09-10"
        },
        {
            "month": 10,
            "year": 2024,
            "due_date": "2024-10-10"
        },
        {
            "month": 11,
            "year": 2024,
            "due_date": "2024-11-10"
        }
    ]
}

Response:
{
    "success": true,
    "message": "Successfully generated 3 missing invoices",
    "data": {
        "created": 3,
        "details": [
            {
                "invoice_number": "INV/2024/12/00045",
                "period": "September 2024",
                "amount": "180000.00"
            },
            {
                "invoice_number": "INV/2024/12/00046",
                "period": "Oktober 2024",
                "amount": "180000.00"
            },
            {
                "invoice_number": "INV/2024/12/00047",
                "period": "November 2024",
                "amount": "180000.00"
            }
        ]
    }
}
```

### Contoh Real: Generate SPP Jan-Jun 2025 untuk siswa ID 1
```
POST http://localhost:8000/api/invoices/generate-missing-months
Authorization: Bearer {token}

Body (JSON):
{
    "academic_year_id": 1,
    "student_id": 1,
    "months": [
        {"month": 1, "year": 2025, "due_date": "2025-01-10"},
        {"month": 2, "year": 2025, "due_date": "2025-02-10"},
        {"month": 3, "year": 2025, "due_date": "2025-03-10"},
        {"month": 4, "year": 2025, "due_date": "2025-04-10"},
        {"month": 5, "year": 2025, "due_date": "2025-05-10"},
        {"month": 6, "year": 2025, "due_date": "2025-06-10"}
    ]
}
```

✅ **Keuntungan endpoint ini:**
- Sekali request untuk banyak bulan
- Otomatis skip bulan yang sudah punya invoice
- Lebih efisien daripada loop manual

---

## 📋 8. List Invoices dengan Filter

### Filter Invoice SPP Bulanan
```
GET http://localhost:8000/api/invoices?academic_year_id=1&student_id=1
Authorization: Bearer {token}

Response: List semua invoice untuk siswa tersebut
```

---

## 🧪 SKENARIO TESTING LENGKAP

### Skenario 1: Setup Awal
1. Login sebagai admin → dapat token
2. Buat fee category "SPP Bulanan" (Rp 150.000)
3. Set spp_base_fee siswa ID 1 = Rp 180.000
4. Set spp_base_fee siswa ID 2 = 0 (akan pakai default)

### Skenario 2: Generate Invoice Januari
1. Generate SPP Januari untuk kelas 1
2. Cek: siswa ID 1 dapat invoice Rp 180.000
3. Cek: siswa ID 2 dapat invoice Rp 150.000

### Skenario 3: Pembayaran Normal
1. Siswa 1 bayar lunas Januari (Rp 180.000)
2. Cek status → invoice status = "paid"

### Skenario 4: Pembayaran Cicilan
1. Siswa 2 bayar cicilan 1 Januari (Rp 100.000)
2. Cek status → invoice status = "partial"
3. Siswa 2 bayar cicilan 2 Januari (Rp 50.000)
4. Cek status → invoice status = "paid"

### Skenario 5: Cek Monthly Status
1. GET monthly-status untuk siswa 1
2. Lihat array 12 bulan (Juli - Juni)
3. Cek summary: paid, unpaid, not_generated

### Skenario 6: Generate Missing Months
1. Generate bulan September-Desember yang terlewat
2. Cek monthly-status lagi
3. Bulan yang tadi "not_generated" sekarang "unpaid"

---

## 📝 CATATAN PENTING

### Period Month Mapping:
- 1 = Januari
- 2 = Februari
- 3 = Maret
- 4 = April
- 5 = Mei
- 6 = Juni
- 7 = Juli
- 8 = Agustus
- 9 = September
- 10 = Oktober
- 11 = November
- 12 = Desember

### Tahun Ajaran 2024/2025:
- Juli 2024 → Juni 2025
- period_month: 7, period_year: 2024 (untuk Juli)
- period_month: 1, period_year: 2025 (untuk Januari)

### Payment Method Options:
- CASH
- TRANSFER

### Invoice Status:
- unpaid: Belum ada pembayaran
- partial: Sudah ada pembayaran tapi belum lunas
- paid: Sudah lunas

### Invoice Type:
- spp_monthly: SPP bulanan
- spp_yearly: SPP tahunan (jika ada)
- other_fee: Biaya lainnya
- other: Lainnya

---

## 🎯 QUICK TEST COMMANDS (Postman Collection Format)

Buat collection di Postman dengan requests berikut:

1. **Auth → Login Admin**
2. **Setup → Create Fee Category SPP**
3. **Setup → Update Student SPP Base Fee**
4. **Invoice → Generate Monthly SPP (Class)**
5. **Invoice → Generate Monthly SPP (Specific Students)**
6. **Invoice → Get Monthly Status**
7. **Invoice → Generate Missing Months**
8. **Payment → Record Payment (Full)**
9. **Payment → Record Payment (Partial)**
10. **Reports → List Invoices**

---

## 🔍 TROUBLESHOOTING

### Error: "The student ids field must be an array"
➡️ **Solusi:** Gunakan array walaupun cuma 1 siswa
```json
❌ "student_ids": 1
✅ "student_ids": [1]
```

### Error: "The period month field must be an integer"
➡️ **Solusi:** Jangan pakai string, pakai integer
```json
❌ "period_month": "1"
✅ "period_month": 1
```

### Error: "SPP fee category not found"
→ Belum buat fee category. Jalankan request "Create Fee Category SPP" dulu.

### Error: "No students found"
→ Class ID atau Student IDs tidak valid. Cek data di database.

### Error: "Payment amount exceeds remaining amount"
→ Amount pembayaran lebih besar dari sisa tagihan. Cek remaining_amount di invoice.

### Invoice sudah ada (skipped)
→ Sudah pernah generate untuk period yang sama. Ini normal (prevent duplicate).

### Mau generate banyak bulan untuk 1 siswa?
→ **Gunakan endpoint** `/api/invoices/generate-missing-months`
→ Lihat contoh di Section 7

---

**Happy Testing! 🚀**
