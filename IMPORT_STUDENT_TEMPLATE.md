# 📄 Template Import Siswa dengan SPP Base Fee

## Format Excel yang Didukung

Sistem akan **otomatis mendeteksi** kolom-kolom berikut di Excel Anda:

### Kolom Wajib (Required):
1. **NIS** / Nomor Induk / No Induk / Student ID
2. **Nama** / Name / Full Name / Student Name

### Kolom Opsional (Optional):
3. **Alamat** / Address
4. **No HP** / Phone / Telepon / Contact
5. **Status** / State (ACTIVE, GRADUATED, DROPPED_OUT, TRANSFERRED)
6. **SPP** / SPP Base Fee / Biaya SPP / SPP Bulanan ⭐ **BARU!**

---

## 📋 Template Excel Standar

Buat file Excel dengan format berikut:

| NIS    | Nama           | Alamat        | No HP        | Status | SPP Base Fee |
|--------|----------------|---------------|--------------|--------|--------------|
| 2024001| Alika Azalea   | Jl. Merdeka 1 | 8123456789   | ACTIVE | 180000       |
| 2024002| Budi Santoso   | Jl. Sudirman 2| 8123456790   | ACTIVE | 150000       |
| 2024003| Citra Dewi     | Jl. Gatot 3   | 8123456791   | ACTIVE | 0            |
| 2024004| Dani Rahman    | Jl. Ahmad 4   | 8123456792   | ACTIVE | 200000       |

### Penjelasan Kolom SPP Base Fee:
- **Angka > 0**: Siswa akan pakai nilai SPP khusus ini saat generate invoice
- **0 atau kosong**: Siswa akan pakai nilai default dari Fee Category "SPP Bulanan"
- **Format yang diterima**:
  - `180000` (angka biasa)
  - `180.000` (dengan titik pemisah ribuan)
  - `Rp 180.000` (dengan prefix Rp)
  - `Rp. 180,000` (format lain, otomatis dibersihkan)

**Sistem otomatis membersihkan** simbol currency (Rp, Rp., dll) dan pemisah ribuan (. , -).

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

### Untuk SPP Base Fee:
- SPP
- SPP Base Fee
- Biaya SPP
- SPP Bulanan
- SPP Bulan
- Base Fee

---

## 📊 Contoh Format Excel Lainnya

### Format 1: Minimal (Hanya Kolom Wajib)
```
| NIS     | Nama          |
|---------|---------------|
| 2024001 | Alika Azalea  |
| 2024002 | Budi Santoso  |
```
✅ Akan berhasil diimport (spp_base_fee = 0, pakai default)

### Format 2: Dengan SPP Custom
```
| Nomor Induk | Nama Lengkap  | Biaya SPP |
|-------------|---------------|-----------|
| 2024001     | Alika Azalea  | 180000    |
| 2024002     | Budi Santoso  | 150000    |
```
✅ Akan berhasil diimport dengan SPP custom

### Format 3: Lengkap dengan Currency
```
| NIS     | Nama          | Alamat      | Telepon     | SPP Base Fee  |
|---------|---------------|-------------|-------------|---------------|
| 2024001 | Alika Azalea  | Jl. Merdeka | 08123456789 | Rp 180.000    |
| 2024002 | Budi Santoso  | Jl. Gatot   | 08123456790 | Rp 150.000    |
```
✅ Sistem otomatis bersihkan "Rp" dan titik

### Format 4: Mixed (Ada yang punya SPP custom, ada yang tidak)
```
| NIS     | Nama          | SPP      |
|---------|---------------|----------|
| 2024001 | Alika Azalea  | 180000   |
| 2024002 | Budi Santoso  | 0        |
| 2024003 | Citra Dewi    |          |
```
✅ Alika: SPP = 180.000
✅ Budi & Citra: SPP = 0 (pakai default dari fee category)

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

---

## ⚠️ Catatan Penting

### 1. Deteksi Otomatis
Sistem akan **scan semua sheet** di file Excel Anda dan mencari yang paling cocok.
Tidak perlu sheet harus bernama "Sheet1" atau tertentu.

### 2. Header Fleksibel
Header bisa di row mana saja (row 1, 2, 3, dst).
Sistem akan mencari baris yang punya kolom NIS dan Nama.

### 3. SPP Base Fee Opsional
Jika kolom SPP tidak ada atau kosong, tidak masalah.
Siswa akan pakai nilai default dari Fee Category saat generate invoice.

### 4. Format Angka SPP
Sistem otomatis bersihkan:
- ✅ `180000` → 180000
- ✅ `180.000` → 180000
- ✅ `Rp 180.000` → 180000
- ✅ `Rp. 180,000` → 180000
- ✅ Kosong → 0

### 5. Validasi Data
- NIS wajib diisi dan unique
- Nama wajib diisi
- Status default = ACTIVE jika tidak diisi
- SPP Base Fee default = 0 jika tidak diisi

---

## 💡 Tips Best Practice

1. **Pastikan NIS Unique**: Jangan ada NIS duplikat di Excel
2. **Format Angka**: Untuk SPP, gunakan angka saja tanpa simbol (lebih aman)
3. **Status Valid**: ACTIVE, GRADUATED, DROPPED_OUT, TRANSFERRED
4. **Test Import Kecil Dulu**: Import 2-3 data dulu untuk test
5. **Backup Data**: Sebelum import massal, backup database dulu

---

## 🔍 Troubleshooting

### Error: "Tidak dapat menemukan data siswa"
➡️ Pastikan ada kolom dengan header NIS dan Nama

### Error: "NIS {xxx} sudah terdaftar"
➡️ NIS duplikat, cek data di database atau di Excel

### SPP Base Fee tidak kedeteksi
➡️ Coba ganti nama header jadi "SPP" atau "Biaya SPP"

### Import berhasil tapi SPP = 0
➡️ Normal jika kolom SPP kosong atau tidak ada. Generate invoice akan pakai default.

---

## 📥 Download Template

Buat file Excel baru dengan struktur ini:

**Sheet: Data Siswa**

| NIS     | Nama          | Alamat           | No HP        | Status | SPP Base Fee |
|---------|---------------|------------------|--------------|--------|--------------|
| 2024001 | Alika Azalea  | Jl. Merdeka 1    | 08123456789  | ACTIVE | 180000       |
| 2024002 | Budi Santoso  | Jl. Sudirman 2   | 08123456790  | ACTIVE | 150000       |

Simpan sebagai: `template_import_siswa.xlsx`

---

## 🎉 Workflow Complete

```
1. Download/Buat template Excel
2. Isi data siswa (minimal: NIS, Nama)
3. Isi kolom SPP Base Fee (opsional)
4. Import via API POST /api/students/import
5. Cek response → berapa yang sukses/gagal
6. Cek database → data siswa masuk dengan spp_base_fee nya
7. Generate invoice → SPP akan pakai nilai dari spp_base_fee siswa
```

**Selesai! ✅**
