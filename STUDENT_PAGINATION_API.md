# Student Pagination API Documentation

## Endpoint: GET `/api/students/paginate`

Endpoint khusus untuk mendapatkan daftar siswa dengan pagination dan filter yang dapat dikustomisasi.

### URL
```
GET /api/students/paginate
```

### Authentication
Required: Yes (Bearer Token)

### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Nomor halaman yang ingin ditampilkan |
| `per_page` | integer | No | 15 | Jumlah data per halaman (max: 100) |
| `academic_year_id` | integer | No | - | Filter berdasarkan ID tahun ajaran (exact match) |
| `year` | string | No | - | Filter berdasarkan tahun (contoh: `2024`, `2025`, atau `2024-2025`) |
| `class_id` | integer | No | - | Filter berdasarkan kelas |
| `status` | string | No | - | Filter berdasarkan status siswa |
| `search` | string | No | - | Pencarian berdasarkan nama, NIS, alamat, atau nomor telepon |
| `sort_by` | string | No | full_name | Kolom untuk sorting |
| `sort_order` | string | No | asc | Urutan sorting (asc/desc) |

### Parameter Notes

**Academic Year Filtering - Dua Cara:**
1. **Menggunakan `academic_year_id`** (Recommended jika sudah tahu ID)
   - Exact match berdasarkan ID
   - Lebih presisi
   - Contoh: `academic_year_id=1`

2. **Menggunakan `year`** (Lebih simpel dan intuitif)
   - Partial match berdasarkan tahun awal
   - Otomatis mencocokkan dengan nama academic year
   - Format yang didukung:
     - `year=2024` → akan match dengan `2024/2025` (tahun ajaran yang dimulai dengan 2024)
     - `year=2025` → akan match dengan `2025/2026` (tahun ajaran yang dimulai dengan 2025)
     - `year=2024-2025` → akan match dengan `2024/2025` (exact match) ⭐ **RECOMMENDED**
   - Format `2024-2025` akan otomatis dikonversi ke `2024/2025` untuk matching

⚠️ **Catatan:** Jika kedua parameter diberikan, `academic_year_id` akan diprioritaskan.

### Status Values
- `active` - Siswa aktif
- `inactive` - Siswa tidak aktif
- `graduated` - Siswa lulus
- `dropped` - Siswa keluar/DO

### Sort By Values
- `full_name` - Nama lengkap
- `nis` - Nomor Induk Siswa
- `status` - Status siswa
- `created_at` - Tanggal dibuat

### Example Requests

#### 1. Basic Pagination (Default)
```bash
GET /api/students/paginate
```

Response:
```json
{
    "success": true,
    "message": "Students retrieved successfully",
    "data": {
        "data": [
            {
                "id": 1,
                "nis": "2024001",
                "full_name": "Ahmad Rizki",
                "address": "Jl. Merdeka No. 10",
                "phone_number": "081234567890",
                "status": "active",
                "created_at": "2025-01-01T00:00:00.000000Z",
                "updated_at": "2025-01-01T00:00:00.000000Z",
                "current_class": {
                    "id": 1,
                    "name": "X-A",
                    "academic_year": {
                        "id": 1,
                        "name": "2024/2025",
                        "is_active": true
                    }
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 100,
            "last_page": 7,
            "from": 1,
            "to": 15,
            "filters": {
                "academic_year_id": null,
                "class_id": null,
                "status": null,
                "search": null
            }
        }
    }
}
```

#### 2. Pagination dengan Per Page Custom
```bash
GET /api/students/paginate?page=2&per_page=25
```

#### 3. Filter berdasarkan Academic Year (menggunakan ID)
```bash
GET /api/students/paginate?academic_year_id=1&page=1&per_page=20
```

#### 4. Filter berdasarkan Tahun (lebih simpel)
```bash
# Menggunakan single year
GET /api/students/paginate?year=2024&per_page=20

# Menggunakan format tahun ajaran (RECOMMENDED)
GET /api/students/paginate?year=2024-2025&per_page=20
```

#### 5. Filter berdasarkan Academic Year dan Class
```bash
GET /api/students/paginate?academic_year_id=1&class_id=5&per_page=30
```

#### 6. Filter berdasarkan Status
```bash
GET /api/students/paginate?status=active&per_page=50
```

#### 6. Search dengan Pagination
```bash
GET /api/students/paginate?search=Ahmad&page=1&per_page=20
# Dengan single year
GET /api/students/paginate?year=2024&status=active&sort_by=nis&sort_order=desc&per_page=25

# Dengan format tahun ajaran (lebih presisi)
GET /api/students/paginate?year=2024-2025&status=active&sort_by=nis&sort_order=desc&per_page=25

#### 7. Kombinasi Filter dan Sort
```bash
GET /api/students/paginate?year=2024&status=active&sort_by=nis&sort_order=desc&per_page=25
```-2025

#### 9. Full Parameters Example (dengan year)
```bash
GET /api/students/paginate?page=2&per_page=20&year=2024&class_id=3&status=active&search=Budi&sort_by=full_name&sort_order=asc
```

#### 10. Full Parameters Example (dengan academic_year_id)
```bash
GET /api/students/paginate?page=2&per_page=20&academic_year_id=1&class_id=3&status=active&search=Budi&sort_by=full_name&sort_order=asc
```

### Response Structure

```json
{
    "success": true,
    "message": "Students retrieved successfully",
    "data": {
        "data": [...],  // Array of student objects
        "pagination": {
            "current_page": 1,      // Current page number
            "per_page": 15,         // Items per page
            "total": 100,           // Total number of records
            "lastyear": null,
                "_page": 7,         // Last page number
            "from": 1,              // First item number on current page
            "to": 15,               // Last item number on current page
            "filters": {            // Applied filters
                "academic_year_id": 1,
                "class_id": 3,
                "status": "active",
                "search": "Ahmad"
            }
        }
    }
}
```

### Error Responses

#### 400 Bad Request - Invalid Parameters
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "per_page": ["The per page field must not be greater than 100."]
    }
}
```

#### 401 Unauthorized - Missing Token
```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

#### 500 Internal Server Error
```json
{
    "success": false,
    "message": "Failed to fetch students: [error message]"
}
```

### Notes

1. **Maximum Per Page**: Maksimal 100 item per halaman untuk menjaga performa
2. **Default Sorting**: Jika tidak ada parameter sort, data akan diurutkan berdasarkan `full_name` secara ascending
3. **Search Functionality**: Pencarian akan mencari di kolom: `full_name`, `nis`, `address`, dan `phone_number`
4. **Role-based Access**: Jika user adalah student, hanya akan menampilkan data 
6. **Academic Year Filter**: Bisa menggunakan `academic_year_id` (exact match) atau `year` (partial match)
   - `academic_year_id=1` → Cari siswa di academic year dengan ID 1
   - `year=2024` → Cari siswa di academic year `2024/2025` (tahun ajaran yang dimulai 2024)
   - `year=2025` → Cari siswa di academic year `2025/2026` (tahun ajaran yang dimulai 2025)
   - `year=2024-2025` → Cari siswa di academic year `2024/2025` (exact match) ⭐
7. **Priority**: Jika `academic_year_id` dan `year` diberikan bersamaan, `academic_year_id` yang akan digunakansiswa tersebut saja
5. **Relationships**: Data akan include relasi `currentClass` dan `academicYear`

### Comparison with `/api/students`

| Feature | `/api/students` | `/api/students/paginate` |
|---------|----------------|--------------------------|
| Pagination | ✅ Yes | ✅ Yes |
| Custom per_page | ✅ Yes | ✅ Yes (max 100) |
| Academic Year Filter | ✅ Yes | ✅ Yes |
| Class Filter | ✅ Yes | ✅ Yes |
| Status Filter | ✅ Yes | ✅ Yes |
| Search | ✅ Yes | ✅ Yes |
| Sorting | ✅ Yes | ✅ Yes |
| Validation | ⚠️ Basic | ✅ Comprehensive |
| Response Format | Standard Laravel | Custom with metadata |
| Parameter Validation | ❌ No | ✅ Yes |

### Use Cases

1. **Admin Dashboard**: Menampilkan daftar siswa dengan pagination untuk manajem
6. **Year-based Filtering**: Filter siswa berdasarkan tahun ajaran dengan cara yang lebih sederhana menggunakan `year`

### Comparison: `academic_year_id` vs `year`

| Aspect | `academic_year_id` | `year` |
|--------|-------------------|--------|(atau exact dengan format `2024-2025`) |
| Simplicity | ❌ Perlu tahu ID dulu | ✅ Langsung pakai tahun |
| Use Case | Saat sudah punya dropdown academic year | Saat ingin filter cepat berdasarkan tahun |
| Example | `academic_year_id=1` | `year=2024` atau `year=2024-2025` |
| Matches | Hanya academic year ID 1 | `2024` = semua yang mengandung "2024"<br>`2024-2025` = "2024/2025" |

**Rekomendasi:**
- Gunakan `academic_year_id` jika aplikasi sudah menampilkan list academic year
- Gunakan `year=2024-2025` untuk filter yang lebih presisi dan user-friendly ⭐
- Gunakan `year=2024` untuk filter yang lebih luas (semua yang mengandung 2024)ear
- Gunakan `year` untuk filter cepat atau public API yang lebih user-friendlyen data
2. **Class Management**: Filter siswa berdasarkan kelas dan tahun ajaran
3. **Student Search**: Cari siswa dengan keyword tertentu
4. **Report Generation**: Ambil data siswa dengan filter spesifik untuk laporan
5. **Mobile App**: Implementasi infinite scroll dengan per_page yang disesuaikan

### Testing with cURL

```bash
# Basic request
curl -X GET "http://localhost:8000/api/students/paginate" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"

# With parameters
curl -X GET "http://localhost:8000/api/students/paginate?page=1&per_page=20&academic_year_id=1&status=active" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Testing with Postman

1. Method: `GET`
2. URL: `{{base_url}}/api/students/paginate`
3. Headers:
   - `Authorization`: `Bearer {{token}}`
   - `Accept`: `application/json`
4. Params:
   - `page`: 1
   - `per_page`: 20
   - `academic_year_id`: 1
   - `status`: active
