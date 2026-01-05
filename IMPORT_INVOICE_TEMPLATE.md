# 📄 Template Import Invoice

## Format Excel yang Didukung

Sistem akan **otomatis mendeteksi** kolom-kolom berikut di Excel Anda:

### Kolom Wajib (Required):
1. **NIS** / Nomor Induk / Student ID
2. **Nama Siswa** / Nama / Name
3. **Fee Category** / Kategori / Jenis Biaya
4. **Jumlah** / Amount / Total / Nominal / Biaya
5. **Bulan** / Month / Periode / Period

### Kolom Opsional (Optional):
6. **Kelas** / Class
7. **Tahun Ajaran** / Academic Year / TA

---

## 📋 Template Excel Standar

Buat file Excel dengan format berikut:

| NIS     | Nama Siswa       | Kelas | Fee Category | Jumlah  | Bulan     | Tahun Ajaran |
|---------|------------------|-------|--------------|---------|-----------|--------------|
| 2024001 | Alika Azalea     | 1.1   | SPP Bulanan  | 180000  | Januari   | 2024/2025    |
| 2024001 | Alika Azalea     | 1.1   | SPP Bulanan  | 180000  | Februari  | 2024/2025    |
| 2024002 | Budi Santoso     | 1.2   | SPP Bulanan  | 150000  | Januari   | 2024/2025    |
| 2024003 | Citra Dewi       | 2.1   | Uang Seragam | 200000  | Juli      | 2024/2025    |

### 💡 Penjelasan Kolom:
- **NIS**: Nomor Induk Siswa (harus sudah terdaftar di database)
- **Fee Category**: Nama kategori biaya (harus sesuai dengan database, sistem akan mencari dengan fuzzy match)
- **Jumlah**: Nominal tagihan dalam angka (tanpa Rp atau titik)
- **Bulan**: Nama bulan dalam Bahasa Indonesia atau Inggris, atau angka 1-12
- **Tahun Ajaran**: Nama tahun ajaran (jika tidak ada, gunakan parameter API)

---

## 🎯 Variasi Header yang Didukung

Sistem **FLEKSIBEL** mendeteksi berbagai nama kolom:

### Untuk NIS:
- NIS
- Nomor Induk
- Student ID

### Untuk Nama Siswa:
- Nama Siswa
- Nama
- Name
- Student Name

### Untuk Fee Category:
- Fee Category
- Kategori
- Category
- Jenis Biaya

### Untuk Jumlah:
- Jumlah
- Amount
- Total
- Nominal
- Biaya

### Untuk Bulan:
- Bulan
- Month
- Periode
- Period

### Untuk Tahun Ajaran:
- Tahun Ajaran
- Academic Year
- TA

---

## 📊 Contoh Format Bulan yang Diterima

### Format 1: Nama Bulan (Bahasa Indonesia)
```
Januari, Februari, Maret, April, Mei, Juni
Juli, Agustus, September, Oktober, November, Desember
```
✅ Sistem otomatis convert ke angka 1-12

### Format 2: Nama Bulan (English)
```
January, February, March, April, May, June
July, August, September, October, November, December
```
✅ Sistem otomatis convert ke angka 1-12

### Format 3: Angka Bulan
```
1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12
```
✅ Langsung digunakan sebagai period_month

---

## 🚀 Cara Import di Postman

### Request:
```
POST http://localhost:8000/api/invoices/import
Authorization: Bearer {token}

Body: form-data
- Key: file
- Type: File
- Value: [pilih file Excel Anda]

Optional Parameters (jika tidak ada di Excel):
- Key: academic_year_id
- Type: Text
- Value: 1
```

### Response Sukses:
```json
{
    "meta": {
        "code": 200,
        "status": "success",
        "message": "Import completed. 4 invoices created, 0 failed."
    },
    "data": {
        "invoices_created": 4,
        "failed": 0,
        "errors": [],
        "import_info": {
            "sheet_name": "Invoice Data",
            "header_row": 1,
            "data_rows": "2 to 5",
            "academic_year_id": 1,
            "column_mapping": {
                "nis": "A",
                "name": "B",
                "class": "C",
                "fee_category": "D",
                "amount": "E",
                "month": "F",
                "academic_year": "G"
            }
        }
    }
}
```

---

## ⚠️ Catatan Penting

### 1. Deteksi Otomatis
- Sistem akan **scan semua sheet** di file Excel Anda dan mencari yang paling cocok
- Tidak perlu sheet harus bernama "Sheet1" atau tertentu

### 2. Header Fleksibel
- Header bisa di row mana saja (row 1, 2, 3, dst)
- Sistem akan mencari baris yang punya kolom NIS, Fee Category, dan Jumlah

### 3. Grouping Invoice
- Invoice items akan digabung berdasarkan **Student ID + Bulan**
- Semua item untuk siswa yang sama di bulan yang sama akan punya **invoice_number** yang sama
- Contoh: Alika di bulan Januari punya SPP + Uang Seragam → 1 invoice dengan 2 items

### 4. Tahun Ajaran
- **Prioritas**: Data di Excel > Parameter API
- Jika tidak ada keduanya, akan muncul error

### 5. Validasi Data
- NIS harus terdaftar di database
- Fee Category harus ada (sistem akan mencari dengan fuzzy match)
- Jumlah harus angka
- Bulan harus valid (1-12 atau nama bulan)

---

## 💡 Tips Best Practice

1. **Pastikan NIS Valid**: Semua NIS harus sudah terdaftar di database siswa
2. **Fee Category Konsisten**: Gunakan nama kategori yang sama dengan database
3. **Format Angka**: Gunakan angka saja tanpa simbol (180000, bukan Rp 180.000)
4. **Bulan Konsisten**: Pilih satu format (nama bulan atau angka) untuk semudah tracking
5. **Test Import Kecil Dulu**: Import 2-3 data dulu untuk test
6. **Backup Data**: Sebelum import massal, backup database dulu

---

## 🔍 Troubleshooting

### Error: "Tidak dapat menemukan data invoice"
➡️ Pastikan ada kolom NIS, Fee Category, Jumlah, dan Bulan

### Error: "Siswa dengan NIS {xxx} tidak ditemukan"
➡️ NIS belum terdaftar, tambahkan siswa dulu via API atau import siswa

### Error: "Fee category '{xxx}' tidak ditemukan"
➡️ Nama kategori tidak sesuai database, cek master fee categories

### Error: "Bulan tidak valid"
➡️ Gunakan nama bulan (Januari/January) atau angka 1-12

### Error: "Tidak dapat mendeteksi tahun ajaran"
➡️ Tambahkan kolom Tahun Ajaran di Excel atau gunakan parameter `academic_year_id`

---

## 📥 Download Template

Buat file Excel baru dengan struktur ini:

**Sheet: Data Invoice**

| NIS     | Nama Siswa       | Kelas | Fee Category | Jumlah  | Bulan    | Tahun Ajaran |
|---------|------------------|-------|--------------|---------|----------|--------------|
| 2024001 | Alika Azalea     | 1.1   | SPP Bulanan  | 180000  | Januari  | 2024/2025    |
| 2024001 | Alika Azalea     | 1.1   | SPP Bulanan  | 180000  | Februari | 2024/2025    |

Simpan sebagai: `template_import_invoice.xlsx`

---

## 🎉 Workflow Complete

```
1. Download/Buat template Excel
2. Isi data invoice (minimal: NIS, Fee Category, Jumlah, Bulan)
3. Pastikan siswa dan fee category sudah ada di database
4. Import via API POST /api/invoices/import
   - Tambahkan academic_year_id jika tidak ada di Excel
5. Cek response → berapa yang sukses/gagal
6. Cek database → data invoice items masuk dengan invoice_number tergenerate
7. Siswa bisa bayar invoice via payment endpoint
```

---

## 🆕 FITUR UTAMA

### 1. Auto-Generate Invoice Number
Sistem otomatis generate invoice number unik untuk setiap grup (student + bulan)

### 2. Flexible Month Format
Terima nama bulan dalam 2 bahasa (Indonesia & English) atau angka 1-12

### 3. Fuzzy Match Fee Category
Sistem akan mencari fee category dengan LIKE query untuk fleksibilitas

### 4. Grouping by Student + Month
Invoice items dengan student dan bulan yang sama akan mendapat invoice_number yang sama

### 5. Multiple Items per Invoice
Satu invoice bisa punya banyak items (SPP + Uang Seragam + dll)
