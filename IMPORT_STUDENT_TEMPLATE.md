# 📄 Template Import Siswa

## Format Excel yang Didukung

Sistem akan **otomatis mendeteksi** kolom-kolom berikut di Excel Anda:

### Kolom Wajib (Required):
1. **NIS** / Nomor Induk / No Induk / Student ID
2. **Nama** / Name / Full Name / Student Name

### Kolom Opsional (Optional):
3. **Alamat** / Address
4. **No HP** / Phone / Telepon / Contact
5. **Status** / State (active, GRADUATED, DROPPED_OUT, TRANSFERRED)
6. **Kelas** / Class / Tingkat ⭐ **BARU!**
7. **Tahun Ajaran** / Academic Year / TA ⭐ **BARU!**

---

## 📋 Template Excel Standar

Buat file Excel dengan format berikut:

| NIS    | Nama           | Alamat        | No HP        | Status | Kelas | Tahun Ajaran |
|--------|----------------|---------------|--------------|--------|-------|--------------|
| 2024001| Alika Azalea   | Jl. Merdeka 1 | 8123456789   | active | 1.1   | 2024/2025    |
| 2024002| Budi Santoso   | Jl. Sudirman 2| 8123456790   | active | 1.2   | 2024/2025    |
| 2024003| Citra Dewi     | Jl. Gatot 3   | 8123456791   | active | 2.1   | 2024/2025    |
| 2024004| Dani Rahman    | Jl. Ahmad 4   | 8123456792   | active | 2.2   | 2024/2025    |

### 💡 Penjelasan Kolom Kelas & Tahun Ajaran:
- **Kelas**: Nama kelas sesuai dengan yang ada di database (contoh: 1.1, 1.2, 2.1, dst)
- **Tahun Ajaran**: Nama tahun ajaran sesuai database (contoh: 2024/2025)
- **Jika kosong**: 
  - Akan menggunakan `class_id` dan `academic_year_id` dari parameter API
  - Jika parameter juga kosong, siswa dibuat tanpa kelas (bisa di-assign manual nanti)
- **Prioritas**: Data di Excel > Parameter API

---

## 🎯 Variasi Header yang Didukung

Sistem **FLEKSIBEL** mendeteksi berbagai nama kolom:

### Untuk NIS:
- NIS
- Nomor Induk
- No Induk
- Student ID
- ID Siswa

### Untuk Nama:
- Nama
- Name
- Full Name
- Student Name

### Untuk Alamat:
- Alamat
- Address

### Untuk No HP:
- No HP
- Phone
- Telepon
- Contact

### Untuk Status:
- Status
- State

### Untuk Kelas:
- Kelas
- Class
- Tingkat

### Untuk Tahun Ajaran:
- Tahun Ajaran
- Academic Year
- Tahun Akademik
- TA

---

## 📊 Contoh Format Excel Lainnya

### Format 1: Minimal (Hanya Kolom Wajib)
```
| NIS     | Nama          |
|---------|---------------|
| 2024001 | Alika Azalea  |
| 2024002 | Budi Santoso  |
```
✅ Akan berhasil diimport (tanpa kelas, bisa di-assign manual nanti)

### Format 2: Dengan Kelas dan Tahun Ajaran
```
| Nomor Induk | Nama Lengkap  | Kelas | Tahun Ajaran |
|-------------|---------------|-------|--------------|
| 2024001     | Alika Azalea  | 1.1   | 2024/2025    |
| 2024002     | Budi Santoso  | 1.2   | 2024/2025    |
```
✅ Akan otomatis assign ke kelas sesuai Excel

### Format 3: Lengkap Semua Kolom
```
| NIS     | Nama          | Alamat      | Telepon     | Status | Kelas | Tahun Ajaran |
|---------|---------------|-------------|-------------|--------|-------|--------------|
| 2024001 | Alika Azalea  | Jl. Merdeka | 08123456789 | active | 1.1   | 2024/2025    |
| 2024002 | Budi Santoso  | Jl. Gatot   | 08123456790 | active | 1.2   | 2024/2025    |
```
✅ Import lengkap dengan semua data

### Format 4: Mixed (Ada yang punya kelas, ada yang tidak)
```
| NIS     | Nama          | Kelas | Tahun Ajaran |
|---------|---------------|-------|--------------|
| 2024001 | Alika Azalea  | 1.1   | 2024/2025    |
| 2024002 | Budi Santoso  |       |              |
| 2024003 | Citra Dewi    | 2.1   | 2024/2025    |
```
✅ Alika & Citra: Masuk kelas sesuai Excel
✅ Budi: Tanpa kelas (pakai parameter API atau assign manual nanti)

---

## 🚀 Cara Import di Postman

### Request:
```
POST http://localhost:8000/api/students/import
Authorization: Bearer {token}

Body: form-data
- Key: file
- Type: File
- Value: [pilih file Excel Anda]

Optional Parameters (jika tidak ada di Excel):
- Key: class_id
- Type: Text
- Value: 1

- Key: academic_year_id
- Type: Text
- Value: 1
```

### Response Sukses:
```json
{
    "success": true,
    "message": "Import completed. 4 students inserted, 0 failed.",
    "data": {
        "total_rows": 4,
        "inserted": 4,
        "failed": 0,
        "errors": [],
        "import_info": {
            "sheet_name": "Sheet1",
            "header_row": 1,
            "data_start_row": 2,
            "data_end_row": 5,
            "column_mapping": {
                "nis": "A",
                "name": "B",
                "address": "C",
                "phone": "D",
                "status": "E",
                "class": "F",
                "academic_year": "G"
            },
            "sample_data": [
                "Row 2: NIS='2024001', Name='Alika Azalea', Class='1.1', Year='2024/2025'",
                "Row 3: NIS='2024002', Name='Budi Santoso', Class='1.2', Year='2024/2025'",
                "Row 4: NIS='2024003', Name='Citra Dewi', Class='2.1', Year='2024/2025'"
            ]
        }
    }
}
```

---

## ⚠️ Catatan Penting

### 1. Deteksi Otomatis
Sistem akan **scan semua sheet** di file Excel Anda dan mencari yang paling cocok.
Tidak perlu sheet harus bernama "Sheet1" atau tertentu.

### 2. Header Fleksibel
Header bisa di row mana saja (row 1, 2, 3, dst).
Sistem akan mencari baris yang punya kolom NIS dan Nama.

### 3. Kelas & Tahun Ajaran
- **Prioritas**: Data di Excel > Parameter API > Tanpa kelas
- **Jika Excel kosong**: Gunakan `class_id` dan `academic_year_id` dari parameter
- **Jika semua kosong**: Siswa dibuat tanpa kelas (bisa di-assign manual nanti)
- **Nama harus match**: Nama kelas dan tahun ajaran di Excel harus sesuai dengan database

### 4. Format Nama Kelas & Tahun Ajaran
Sistem mencari berdasarkan nama:
- ✅ Kelas: "1.1", "1.2", "2.1", "X IPA 1", dll (sesuai database)
- ✅ Tahun Ajaran: "2024/2025", "2023/2024", dll (sesuai database)
- ⚠️ Jika tidak ditemukan di database, siswa akan dibuat tanpa kelas

### 5. Validasi Data
- NIS wajib diisi dan unique
- Nama wajib diisi
- Status default = active jika tidak diisi
- Kelas & Tahun Ajaran opsional

---

## 💡 Tips Best Practice

1. **Pastikan NIS Unique**: Jangan ada NIS duplikat di Excel
2. **Format Kelas & Tahun Ajaran**: Pastikan nama sesuai dengan data di database
3. **Status Valid**: active, GRADUATED, DROPPED_OUT, TRANSFERRED
4. **Test Import Kecil Dulu**: Import 2-3 data dulu untuk test
5. **Backup Data**: Sebelum import massal, backup database dulu
6. **Cek Master Data**: Pastikan kelas dan tahun ajaran sudah ada di database

---

## 🔍 Troubleshooting

### Error: "Tidak dapat menemukan data siswa"
➡️ Pastikan ada kolom dengan header NIS dan Nama

### Error: "NIS {xxx} sudah terdaftar"
➡️ NIS duplikat, cek data di database atau di Excel

### Kelas tidak ter-assign
➡️ Pastikan nama kelas di Excel sama persis dengan database
➡️ Atau gunakan parameter `class_id` untuk assign semua siswa

### Tahun Ajaran tidak ditemukan
➡️ Pastikan nama tahun ajaran di Excel sesuai database (contoh: 2024/2025)
➡️ Atau gunakan parameter `academic_year_id`

---

## 📥 Download Template

Buat file Excel baru dengan struktur ini:

**Sheet: Data Siswa**

| NIS     | Nama          | Alamat           | No HP        | Status | Kelas | Tahun Ajaran |
|---------|---------------|------------------|--------------|--------|-------|--------------|
| 2024001 | Alika Azalea  | Jl. Merdeka 1    | 08123456789  | active | 1.1   | 2024/2025    |
| 2024002 | Budi Santoso  | Jl. Sudirman 2   | 08123456790  | active | 1.2   | 2024/2025    |

Simpan sebagai: `template_import_siswa.xlsx`

---

## 🎉 Workflow Complete

```
1. Download/Buat template Excel
2. Isi data siswa (minimal: NIS, Nama)
3. Isi kolom Kelas dan Tahun Ajaran (opsional)
4. Import via API POST /api/students/import
   - Jika tidak ada di Excel, tambahkan class_id dan academic_year_id (opsional)
5. Cek response → berapa yang sukses/gagal
6. Cek database → data siswa masuk dengan kelas dan tahun ajar
```

---

## 🆕 FITUR TERBARU

### 1. Auto-Assign Class dari Excel
Sekarang bisa langsung assign kelas dari kolom Excel! Tidak perlu parameter API lagi.

### 2. Flexible Column Detection
Sistem mendeteksi berbagai variasi nama kolom:
- Kelas / Class / Tingkat
- Tahun Ajaran / Academic Year / TA

### 3. Priority Logic
- **Excel > Parameter API > None**
- Jika ada di Excel, gunakan dari Excel
- Jika kosong di Excel, gunakan parameter API
- Jika keduanya kosong, siswa tanpa kelas

### Cara Pakai:

#### Opsi 1: Data Kelas di Excel (RECOMMENDED)
```
POST /api/students/import
Authorization: Bearer {token}

Form-Data:
- file: students.xlsx (dengan kolom Kelas dan Tahun Ajaran)
```

#### Opsi 2: Parameter API (untuk assign semua ke kelas yang sama)
```
POST /api/students/import
Authorization: Bearer {token}

Form-Data:
- file: students.xlsx
- class_id: 3
- academic_year_id: 1
```

#### Opsi 3: Mixed (Excel + Parameter sebagai fallback)
```
POST /api/students/import
Authorization: Bearer {token}

Form-Data:
- file: students.xlsx (beberapa punya kolom Kelas, beberapa kosong)
- class_id: 3              (fallback untuk yang kosong)
- academic_year_id: 1      (fallback untuk yang kosong)
```

### Keuntungan:
✅ Konsisten dengan create student manual (class langsung ter-assign)  
✅ Hemat waktu - tidak perlu set class satu-satu setelah import  
✅ Data lebih lengkap - langsung ada di StudentClassHistory  
✅ Siap generate invoice - siswa sudah punya kelas dan tahun ajaran

### Contoh Response dengan Class Assignment:
```json
{
    "success": true,
    "message": "Import completed. 3 students inserted, 0 failed.",
    "data": {
        "total_rows": 3,
        "inserted": 3,
        "failed": 0,
        "import_info": {
            "assigned_class": "1.1",
            "academic_year": "2024/2025",
            ...
        }
    }
}
```

**Selesai! ✅**
