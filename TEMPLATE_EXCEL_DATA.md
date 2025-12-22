# Template Import Siswa - Copy ke Excel

## Copy data di bawah ini ke Excel Anda

### Format 1: Lengkap dengan SPP Base Fee

```
NIS	Nama	Alamat	No HP	Status	SPP Base Fee
2024001	Alika Azalea	Jl. Merdeka 1	08123456789	ACTIVE	180000
2024002	Budi Santoso	Jl. Sudirman 2	08123456790	ACTIVE	150000
2024003	Citra Dewi	Jl. Gatot Subroto 3	08123456791	ACTIVE	200000
2024004	Dani Rahman	Jl. Ahmad Yani 4	08123456792	ACTIVE	0
2024005	Eka Putri	Jl. Diponegoro 5	08123456793	ACTIVE	175000
```

### Format 2: Minimal (Hanya NIS dan Nama)

```
NIS	Nama
2024001	Alika Azalea
2024002	Budi Santoso
2024003	Citra Dewi
2024004	Dani Rahman
2024005	Eka Putri
```

### Format 3: Dengan SPP dalam format Rupiah

```
NIS	Nama	SPP Base Fee
2024001	Alika Azalea	Rp 180.000
2024002	Budi Santoso	Rp 150.000
2024003	Citra Dewi	Rp 200.000
```

---

## Cara Penggunaan:

1. **Buka Excel** atau Google Sheets
2. **Copy salah satu format** di atas
3. **Paste ke Excel** (mulai dari cell A1)
4. **Edit data** sesuai kebutuhan Anda
5. **Save as .xlsx** dengan nama: `import_siswa.xlsx`
6. **Upload via API** POST /api/students/import

---

## Contoh Data Real (30 Siswa)

```
NIS	Nama	Alamat	No HP	Status	SPP Base Fee
2024001	Alika Azalea	Jl. Merdeka 1	08123456789	ACTIVE	180000
2024002	Budi Santoso	Jl. Sudirman 2	08123456790	ACTIVE	150000
2024003	Citra Dewi	Jl. Gatot Subroto 3	08123456791	ACTIVE	200000
2024004	Dani Rahman	Jl. Ahmad Yani 4	08123456792	ACTIVE	180000
2024005	Eka Putri	Jl. Diponegoro 5	08123456793	ACTIVE	175000
2024006	Fajar Hidayat	Jl. Kartini 6	08123456794	ACTIVE	150000
2024007	Gina Safitri	Jl. Cut Nyak Dien 7	08123456795	ACTIVE	180000
2024008	Hadi Gunawan	Jl. RA Kartini 8	08123456796	ACTIVE	200000
2024009	Indah Permata	Jl. Dewi Sartika 9	08123456797	ACTIVE	150000
2024010	Joko Widodo	Jl. Imam Bonjol 10	08123456798	ACTIVE	180000
2024011	Kartika Sari	Jl. Teuku Umar 11	08123456799	ACTIVE	175000
2024012	Luthfi Hakim	Jl. Veteran 12	08123456800	ACTIVE	150000
2024013	Maya Anggraini	Jl. Pangeran Antasari 13	08123456801	ACTIVE	200000
2024014	Nanda Pratama	Jl. Pattimura 14	08123456802	ACTIVE	180000
2024015	Olivia Tania	Jl. Sultan Agung 15	08123456803	ACTIVE	150000
2024016	Putra Mahardika	Jl. Letjen Suprapto 16	08123456804	ACTIVE	175000
2024017	Qori Amalia	Jl. Basuki Rahmat 17	08123456805	ACTIVE	180000
2024018	Rizki Firmansyah	Jl. Hayam Wuruk 18	08123456806	ACTIVE	200000
2024019	Sinta Maharani	Jl. Gajah Mada 19	08123456807	ACTIVE	150000
2024020	Taufik Hidayat	Jl. Mojopahit 20	08123456808	ACTIVE	180000
2024021	Umar Bakri	Jl. Pahlawan 21	08123456809	ACTIVE	175000
2024022	Vina Angelia	Jl. Pemuda 22	08123456810	ACTIVE	150000
2024023	Wahyu Saputra	Jl. Proklamasi 23	08123456811	ACTIVE	200000
2024024	Xena Valentina	Jl. Kemerdekaan 24	08123456812	ACTIVE	180000
2024025	Yusuf Ibrahim	Jl. Merdeka Barat 25	08123456813	ACTIVE	150000
2024026	Zahra Fitria	Jl. Asia Afrika 26	08123456814	ACTIVE	175000
2024027	Ahmad Fauzi	Jl. Raya Timur 27	08123456815	ACTIVE	180000
2024028	Bella Anindya	Jl. Industri 28	08123456816	ACTIVE	200000
2024029	Cahya Nugraha	Jl. Raya Selatan 29	08123456817	ACTIVE	150000
2024030	Dimas Aditya	Jl. Raya Utara 30	08123456818	ACTIVE	180000
```

---

## Tips:

1. ✅ Gunakan **Tab** untuk pemisah kolom (bukan spasi)
2. ✅ Header harus di **baris pertama**
3. ✅ NIS harus **unique** (tidak boleh duplikat)
4. ✅ SPP Base Fee boleh **kosong** (akan default ke 0)
5. ✅ Status boleh kosong (default: ACTIVE)
6. ✅ Format angka SPP: **angka saja** lebih aman (tanpa Rp atau titik)

---

## Variasi Header yang Didukung:

### NIS bisa ditulis:
- NIS
- Nomor Induk
- No Induk
- Student ID

### Nama bisa ditulis:
- Nama
- Name
- Full Name
- Student Name

### SPP Base Fee bisa ditulis:
- SPP
- SPP Base Fee
- Biaya SPP
- SPP Bulanan
- Base Fee

**Sistem otomatis mendeteksi! 🎯**
