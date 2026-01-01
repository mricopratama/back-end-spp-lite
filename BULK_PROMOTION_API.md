# Bulk Promotion API Documentation

## Endpoints

### 1. Preview Bulk Promotion (Auto 1:1 Mapping)

**Endpoint:**
```
GET /api/students/bulk-promote/preview
```

**Query Parameters:**
- `from_academic_year_id` (required): ID tahun ajaran asal
- `to_academic_year_id` (required): ID tahun ajaran tujuan

**Example Request:**
```bash
GET /api/students/bulk-promote/preview?from_academic_year_id=7&to_academic_year_id=8
Authorization: Bearer {token}
```

**Example Response:**
```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Preview bulk promotion generated successfully"
  },
  "data": {
    "summary": {
      "total_active_students": 150,
      "will_promote": 125,
      "will_graduate": 25,
      "will_skip_inactive": 5
    },
    "class_mapping": [
      {
        "from_class_id": 1,
        "from_class_name": "Kelas 1.1",
        "from_class_level": 1,
        "to_class_id": 7,
        "to_class_name": "Kelas 2.1",
        "to_class_level": 2,
        "student_count": 25,
        "action": "promote"
      },
      {
        "from_class_id": 2,
        "from_class_name": "Kelas 1.2",
        "from_class_level": 1,
        "to_class_id": 8,
        "to_class_name": "Kelas 2.2",
        "to_class_level": 2,
        "student_count": 23,
        "action": "promote"
      },
      {
        "from_class_id": 12,
        "from_class_name": "Kelas 6.1",
        "from_class_level": 6,
        "to_class_id": null,
        "to_class_name": null,
        "to_class_level": null,
        "student_count": 15,
        "action": "graduate"
      }
    ],
    "warnings": [
      "Kelas tujuan untuk 'Kelas 3.3' tidak ditemukan (level 4)"
    ]
  }
}
```

---

### 2. Execute Bulk Promotion (Auto 1:1 Mapping)

**Endpoint:**
```
POST /api/students/bulk-promote/auto
```

**Request Body:**
```json
{
  "from_academic_year_id": 7,
  "to_academic_year_id": 8
}
```

**Example Request:**
```bash
POST /api/students/bulk-promote/auto
Authorization: Bearer {token}
Content-Type: application/json

{
  "from_academic_year_id": 7,
  "to_academic_year_id": 8
}
```

**Example Response:**
```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Bulk promotion completed successfully"
  },
  "data": {
    "success": true,
    "promoted_count": 125,
    "graduated_count": 25,
    "skipped_count": 2,
    "failed_count": 0,
    "details": {
      "promoted": [
        {
          "student_id": 186,
          "student_name": "Adzkia Naura Hasna Annida",
          "student_nis": "2021001",
          "from_class": "Kelas 1.1",
          "to_class": "Kelas 2.1"
        },
        {
          "student_id": 187,
          "student_name": "Annisa Dina Raffia",
          "student_nis": "2021002",
          "from_class": "Kelas 1.1",
          "to_class": "Kelas 2.1"
        }
      ],
      "graduated": [
        {
          "student_id": 350,
          "student_name": "Ahmad Fauzi",
          "student_nis": "2019001",
          "from_class": "Kelas 6.1",
          "status": "graduated"
        }
      ],
      "skipped": [
        {
          "student_id": 200,
          "student_name": "Budi Santoso",
          "student_nis": "2021050",
          "reason": "Already promoted to target academic year"
        }
      ],
      "failed": []
    }
  }
}
```

---

## Auto-Mapping Logic (1:1)

**Rule:**
- Level naik 1 tingkat
- Class suffix tetap sama

**Examples:**
- `Kelas 1.1` → `Kelas 2.1` (level 1→2, suffix .1 tetap)
- `Kelas 2.2` → `Kelas 3.2` (level 2→3, suffix .2 tetap)
- `Kelas 5.1` → `Kelas 6.1` (level 5→6, suffix .1 tetap)
- `Kelas 6.x` → **GRADUATED** (level 6 = maksimal)

**Fallback:**
Jika kelas dengan suffix yang sama tidak ditemukan, sistem akan:
1. Mengambil kelas pertama di level target
2. Atau menampilkan warning jika tidak ada kelas di level target

---

## Graduation Logic

- Siswa di kelas level 6 akan otomatis di-graduate
- Status siswa berubah dari `active` → `graduated`
- Tidak ada student_class_history yang dibuat untuk siswa yang lulus

---

## Validation & Safety

**Before Execution:**
1. ✅ Cek tahun ajaran asal dan tujuan valid
2. ✅ Tahun ajaran tujuan harus berbeda dengan asal
3. ✅ Hanya siswa dengan status ACTIVE yang di-promote
4. ✅ Skip siswa yang sudah ada di tahun ajaran tujuan
5. ✅ Database transaction (auto-rollback jika error)

**Recommended Flow:**
1. Call **Preview** endpoint terlebih dahulu
2. Tampilkan hasil preview ke user
3. User konfirmasi
4. Call **Execute** endpoint

---

## Error Handling

**Common Errors:**

**400 Bad Request:**
```json
{
  "meta": {
    "code": 400,
    "status": "error",
    "message": "Tahun ajaran tujuan harus berbeda dengan tahun ajaran asal"
  }
}
```

**404 Not Found:**
```json
{
  "meta": {
    "code": 404,
    "status": "error",
    "message": "Tahun ajaran asal tidak valid"
  }
}
```

**500 Server Error:**
```json
{
  "meta": {
    "code": 500,
    "status": "error",
    "message": "Failed to execute bulk promotion: {error details}"
  }
}
```

---

## Testing Checklist

- [ ] Preview dengan tahun ajaran yang valid
- [ ] Preview dengan tahun ajaran yang sama (error expected)
- [ ] Execute promotion
- [ ] Verify student_class_history dibuat
- [ ] Verify siswa kelas 6 status berubah menjadi graduated
- [ ] Execute 2x (skipped expected)
- [ ] Check warnings jika ada kelas yang tidak ditemukan
