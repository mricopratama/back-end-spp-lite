<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\AcademicYear;
use App\Models\Classes;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $allStudents = [];
        // Alumni kelas 6.1 (lulus 2024, NIS 2018001 dst)
        $alumni61_2018 = [
            'Alvaro Dewa Y',
            'Annisa Nur Laily R',
            'Alifiana Nur Aisyah',
            'Akhdan Afif Athaya',
            'Aisyah Amaranggani',
            'Diva Awalia Putri',
            'Gilang Al Farizi',
            'Haidar Abdurrahman K',
            'Hasna Nur Azizah',
            'Ibrahim Al Ghifari',
            'Ibrohim',
            'Iznaeni Febri Rahma',
            'Kinanti Salsabila P',
            'Kalyanna Reia',
            'Muhammad Fahmi F',
            'Mu\'afa Daffa D',
            'Muhammad Syaffa Royhan',
            'Muhammad Dzaky A',
            'Novandito Al Rasya',
            'Nura Esta Fadila',
            'Nadiya Syafira A',
            'Nadira Miftahul J',
            'Qothrunada Wening',
            'Rafa Hidayatullah A',
            'Rayhan Archa Indra P',
            'Aufa Rafa Akfar Ramadhan',
            'Risqi Doni Fauzan',
            'Satya Fattahurrahim',
            'Savira Putri Denisha',
            'Muh Aqil Nur',
            'Muh Faiz Rizqi',
            'Andromeda Pastika',
        ];
        $nis = 2018001;
        foreach ($alumni61_2018 as $name) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $name,
                'address' => 'Alamat alumni',
                'phone_number' => '081200000000',
                'status' => 'graduated',
                'kelas' => 'Kelas 6.1',
            ];
        }

        // Alumni kelas 6.2 (lulus 2024, NIS lanjut dari alumni 6.1)
        $alumni62_2018 = [
            'Abiyan Adnan W',
            'Achmad Zaki Fahreza Harahap',
            'Afiqah Muria Purnamaningrum',
            'Agha Faeyza Falah',
            'Aljaris Kiar Dzaki Prasetyo',
            'Almira Ivanov Alqiranjani',
            'Amira Saputri',
            'Arkan Mahdiyyah Musyaffa',
            'Daiba Shafa Ayudya Mumtaz',
            'Deryl Putra Setiawan',
            'Desfylia Putri Anggraeni',
            'Dirga Pratama',
            'Elvina Rahma Amelia',
            'Esa Fariz Saputra',
            'Fatimah Nurul Azizah',
            'Khadijah',
            'M. Raynan Ilham Ariyanto',
            'Muhammad Fahri Firmansyah',
            'Nadine Zahwa Oktaviani',
            'Narayan Palupi Yuwantari',
            'Novita Yhuliana Putri',
            'Nufah Belva Fayola',
            'Ordelia Nadindra Rosalia',
            'Siti Hanan Tri Ayungsih',
            'Siti Hanan Tri Ayungtys',
            'Teuku Muhammad Rasya',
            'Wafiq Azzariayasa',
            'Zukhufr Akbar Ariyanto',
            'Bilauara Ashafa',
            'Tiara Alifia Nur Salwa',
        ];
        foreach ($alumni62_2018 as $name) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $name,
                'address' => 'Alamat alumni',
                'phone_number' => '081200000000',
                'status' => 'graduated',
                'kelas' => 'Kelas 6.2',
            ];
        }

        // Alumni kelas 6.1 (lulus 2025, NIS 2019001 dst)
        $alumni61 = [
            'Achmad Arlin Castana',
            'Adinda Saqina Nuraini',
            'Aisya Ramaniya Dzikra',
            'Alfad Syahitto',
            'Alvano Ruzva Valdes',
            'Alvino Devon Randiansyah',
            'Ara Yasifa Salsabilla',
            'Belmonioska Daffa Samudra',
            'Carissa Putri Aurelia Fadhilah',
            'Fika Fitria Ramadhani',
            'Galih Dwitama Panjalu',
            'Gibran Jalu Bramantyo',
            'Hani Lulu Arissa Distiryantoro',
            'Ibadurrahman Rama Wiryadana',
            'Iqbal Rezkyano Gemilang',
            'Isabel Barayef Lawin',
            'Masyitoh',
            'Muhammad Alfachrisky Ocza Putra',
            'Muhammad Migdham Aufar',
            'Mutiara Azzahra',
            'Nadia Khairunnisa Yumna Arifah',
            'Naufal Refi Alfarizi',
            'Naufal Rifani Alfarizi',
            'Nawa Khumaida Zukhal',
            'Prisya Nur Alfiza',
            'Raihan Putra Ramadhan',
            'Riz Khaliva Niraz Thamrin',
            'Rizqi Aditya Dwi Pratama',
            'Sabda Sanjaya',
            'Sofia Vitri Yulianto',
            'Zhafira Amelia Putri',
            'Zhifarra Nur Hafiza',
            'Hanifa Latisha A',
        ];
        $nis = 2019001;
        foreach ($alumni61 as $name) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $name,
                'address' => 'Alamat alumni',
                'phone_number' => '081200000000',
                'status' => 'graduated',
                'kelas' => 'Kelas 6.1',
            ];
        }

        // Alumni kelas 6.2 (lulus 2025, NIS lanjut dari alumni 6.1)
        $alumni62 = [
            'Amellia Dian Agus Triningsih',
            'Anindhitya Dzakira Aftani',
            'Ardian Hafiz Wahyu Nugroho',
            'Baiq Maulidya Khairunnisa',
            'Callista Naura Az Zahra',
            'Daiva Narzhifa Ainnunisa',
            'Devana Cantika Putri Kelana',
            'Dimas Febro Dwi Putra',
            'Fadli Yudari Putra Hermansyah',
            'Galih Gelar Darmawan',
            'Halimah Nur Hasanah',
            'Janestri Seccha Jagaddhita',
            'Johanna Devania Agustina',
            'Kalila Shafeeqa Soufyan',
            'Muh. Chaidar Khawarisimi',
            'Muh. Hibral Purwaka Putra',
            'Muhammad Fikhri Abdul Rosyid',
            'Muhammad Ghaits Muwaffa',
            'Muhammad Iqbal Putra Nugroho',
            'Muhammad Nibras El Fiqri',
            'Nadira Almayra Fitri Nugroho',
            'Na\'im Adrian Ozzil',
            'Nurul Cecylia Destiya A',
            'Rahma Kamila',
            'Raihan Naufal Arfandi',
            'Raisha Noor Athifa Ristiana',
            'Raissa Ariena Rosalinda',
            'Vicky Ewaldo Ramadhan',
            'Vynola Hanun Fadhilah',
            'Zhafira Aqila L',
            'Zivara Alanna F',
            'Kirana Queen Adhiyanti',
        ];
        foreach ($alumni62 as $name) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $name,
                'address' => 'Alamat alumni',
                'phone_number' => '081200000000',
                'status' => 'graduated',
                'kelas' => 'Kelas 6.2',
            ];
        }

        // Get active academic year
        $academicYear = AcademicYear::where('is_active', true)->first();
        if (!$academicYear) {
            $academicYear = AcademicYear::first();
        }

        // Get Kelas 6.1, 6.2, 5.1, 5.2, 4.1, 4.2, 3.1, 3.2, 2.1, 2.2, 1.1, 1.2
        $kelas61 = Classes::where('name', 'Kelas 6.1')->first();
        $kelas62 = Classes::where('name', 'Kelas 6.2')->first();
        $kelas51 = Classes::where('name', 'Kelas 5.1')->first();
        $kelas52 = Classes::where('name', 'Kelas 5.2')->first();
        $kelas41 = Classes::where('name', 'Kelas 4.1')->first();
        $kelas42 = Classes::where('name', 'Kelas 4.2')->first();
        $kelas31 = Classes::where('name', 'Kelas 3.1')->first();
        $kelas32 = Classes::where('name', 'Kelas 3.2')->first();
        $kelas21 = Classes::where('name', 'Kelas 2.1')->first();
        $kelas22 = Classes::where('name', 'Kelas 2.2')->first();
        $kelas11 = Classes::where('name', 'Kelas 1.1')->first();
        $kelas12 = Classes::where('name', 'Kelas 1.2')->first();

        // Data siswa kelas 6.1
        $students61 = [
            ['full_name' => 'Abi An Annafi'],
            ['full_name' => 'Aditya Rizky Prasetyo'],
            ['full_name' => 'Adnan Apriliantoro'],
            ['full_name' => 'Adwa Negara Ashaq'],
            ['full_name' => 'Afika Tona Nirmala'],
            ['full_name' => 'Ahmad Rafiq Fauzan'],
            ['full_name' => 'Aidhán Faris Mahardika'],
            ['full_name' => 'Alzena Khaira Fijratullah'],
            ['full_name' => 'Ama Dea Moza'],
            ['full_name' => 'Bahy Azka Samba Pratama'],
            ['full_name' => 'Bastian Eram Alvaro'],
            ['full_name' => 'Diego Sabiq Pratama N'],
            ['full_name' => 'Hamdan Akif Khoiruddin'],
            ['full_name' => 'Khrisna Aryasatya Putra'],
            ['full_name' => 'May Diana Luthfi Kuntari'],
            ['full_name' => 'Muhammad Clearsta Seno F'],
            ['full_name' => 'Muchammad Arthur Fabian'],
            ['full_name' => 'Muhammad Fauzan Athaya'],
            ['full_name' => 'Muhammad Rajiv Gilbran M'],
            ['full_name' => 'Najwa Aurora Montana'],
            ['full_name' => 'Nazura Khansa Izzatunnisa'],
            ['full_name' => 'Nindya Tasyamulyra R'],
            ['full_name' => 'R Bintang Pratama Sidik'],
            ['full_name' => 'Rafael Akbar Alvaronizam'],
            ['full_name' => 'Razita Nadya Salsabila Fajria'],
            ['full_name' => 'Rohmatian Bintang Pratama'],
            ['full_name' => 'Satria Putra'],
            ['full_name' => 'Yasmin Ardini'],
            ['full_name' => 'Faiqa Hasna Cahyaningrum'],
        ];

        // Data siswa kelas 6.2
        $students62 = [
            ['full_name' => 'Aisha Larasati'],
            ['full_name' => 'Al Bias Sinar Maulana'],
            ['full_name' => 'Aprillio Azkha Saputra'],
            ['full_name' => 'Aqilah Rizqiana'],
            ['full_name' => 'Aysha Sakha Salsabila'],
            ['full_name' => 'Azizah Faikhahur Rahma'],
            ['full_name' => 'Belmiro Ozil Tahara'],
            ['full_name' => 'Cheryl Fiorenza El Java'],
            ['full_name' => 'Dahayu Pramusita Ubadilah'],
            ['full_name' => 'Dyah Ayu Rana Wijaya'],
            ['full_name' => 'Elua Yumna Raihanah'],
            ['full_name' => 'Faeyza Al Ziqri'],
            ['full_name' => 'Farras Hazim Arhanu'],
            ['full_name' => 'Fauzan Afkar Ananta'],
            ['full_name' => 'Fauzi Afif Ananta'],
            ['full_name' => 'Isma Afifah Hasna Putri P'],
            ['full_name' => 'Nadina Marinka Kirana Putri'],
            ['full_name' => 'Najwa Saskia Dzakira'],
            ['full_name' => 'Nandana Azka Alfajri'],
            ['full_name' => 'Naura Gendis Az Zahra'],
            ['full_name' => 'Nur Ibrahim Putra Purnomo'],
            ['full_name' => 'Nur Kholis'],
            ['full_name' => 'Rafa Azmi Nurdiansyah'],
            ['full_name' => 'Rafif Kevin Ubaid W'],
            ['full_name' => 'Raisya Ilham Mayhelka'],
            ['full_name' => 'Shafwan Rafa Arfandi'],
            ['full_name' => 'Syeni Agustina Lavizi'],
            ['full_name' => 'Zaki Arafi'],
            ['full_name' => 'Zalfa Faa\'Izaty Marhandika'],
        ];

        $nis = 2020001;
        foreach ($students61 as $s) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $s['full_name'],
                'address' => 'Alamat siswa',
                'phone_number' => '081200000000',
                'status' => 'active',
                'kelas' => 'Kelas 6.1',
            ];
        }
        foreach ($students62 as $s) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $s['full_name'],
                'address' => 'Alamat siswa',
                'phone_number' => '081200000000',
                'status' => 'active',
                'kelas' => 'Kelas 6.2',
            ];
        }

        // Data siswa kelas 5.1
        $students51 = [
            ['full_name' => 'Adzkia Naura Hasna Annida'],
            ['full_name' => 'Annisa Dina Raffia'],
            ['full_name' => 'Bintang Yoga Nugraha'],
            ['full_name' => 'Briliangga Ahli Herpavi'],
            ['full_name' => 'Danendra Yuan Farid Athailla'],
            ['full_name' => 'Hazafha Arya Alfarizky'],
            ['full_name' => 'Ibrahim Naufal Ar Rafif'],
            ['full_name' => 'Keenar Hana Nailatul Izzah'],
            ['full_name' => 'Kiad Adhirajasa'],
            ['full_name' => 'Kirana Ranika Anjani'],
            ['full_name' => 'Lathifa Adhiya Hafizhah Shalihah'],
            ['full_name' => 'Mohamad Deni Darmawan'],
            ['full_name' => 'Muhammad Davin Ramadhan'],
            ['full_name' => 'Rasya Muhammad Athaya'],
            ['full_name' => 'Syahrayad Aisyah Lawin'],
            ['full_name' => 'Nadia Kindi Azzahra'],
            ['full_name' => 'Azka Aliagha Iskandar'],
            ['full_name' => 'Ganesha Tsukitama Setiawan'],
        ];
        $nis = 2021001;
        foreach ($students51 as $s) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $s['full_name'],
                'address' => 'Alamat siswa',
                'phone_number' => '081200000000',
                'status' => 'active',
                'kelas' => 'Kelas 5.1',
            ];
        }

        // Data siswa kelas 5.2
        $students52 = [
            ['full_name' => 'Afiqah Yasmina Maulida Ahmad'],
            ['full_name' => 'Aqila Faradisa Putri Nadhika'],
            ['full_name' => 'Aqueena Catzilla Diah Wiratmo'],
            ['full_name' => 'Arganta Oktorio Saputra'],
            ['full_name' => 'Arkana Khalil Anindito'],
            ['full_name' => 'Arkananta Khaizuran Pratama'],
            ['full_name' => 'Armando Hadiwijaya'],
            ['full_name' => 'Cerllia Alena Putri Damare'],
            ['full_name' => 'Darrell Arkhan Pratama'],
            ['full_name' => 'Destyan Perkasa Putra Ramadhan'],
            ['full_name' => 'Gheanina Dwi Azzalea Humaira'],
            ['full_name' => 'Jessia Ibaneza'],
            ['full_name' => 'Joaqeen Azka Vherellyto'],
            ['full_name' => 'Khiar Lail Ramadhan Nugroho'],
            ['full_name' => 'Qonita Nurul Faliah'],
            ['full_name' => 'Safa Ardina Nur Asri'],
            ['full_name' => 'Shaquilla Nufaisah Anugra'],
            ['full_name' => 'Uwais Akhtar Mahfudz'],
            ['full_name' => 'Al Falaah Jose Setiawan'],
            ['full_name' => 'Anindita Zahrana'],
            ['full_name' => 'Muhammad Fatih Darmawan'],
            ['full_name' => 'Naufal Alvino Pratama'],
        ];
        $nis = 2021001 + count($students51);
        foreach ($students52 as $s) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $s['full_name'],
                'address' => 'Alamat siswa',
                'phone_number' => '081200000000',
                'status' => 'active',
                'kelas' => 'Kelas 5.2',
            ];
        }

        // Data siswa kelas 4.1
        $students41 = [
            ['full_name' => 'Adra Rachel Fatima'],
            ['full_name' => 'Althaf Fatih Alhisyam'],
            ['full_name' => 'Aqala Putri Adila'],
            ['full_name' => 'Aqila Putri Adila'],
            ['full_name' => 'Arya Rakananta'],
            ['full_name' => 'Azzahra Prameswari Renata S'],
            ['full_name' => 'Azzalea Atiqah Amanina'],
            ['full_name' => 'Benandy Abimata D'],
            ['full_name' => 'Bonum Ventageano'],
            ['full_name' => 'Danish Silmi Ramadhan'],
            ['full_name' => 'Fatikha Ayatin Husna'],
            ['full_name' => 'Fatin Alesa Putri'],
            ['full_name' => 'Hafizh Rafi Rabbani'],
            ['full_name' => 'Hanania Khalila'],
            ['full_name' => 'Muhammad Agam Meshach A'],
            ['full_name' => 'Muhammad Alfaith Habiburrahman'],
            ['full_name' => 'Muhammad Arsya narendra'],
            ['full_name' => 'Ni Putu Manik Anindya Nari W'],
            ['full_name' => 'Nianda Meisya Onefill'],
            ['full_name' => 'Rayhan Dhiwa A'],
            ['full_name' => 'Asyifa Qinara'],
            ['full_name' => 'Adipati Ganta Sabrana'],
            ['full_name' => 'Cahaya Mahmuda'],
            ['full_name' => 'Claresta Agna Lovexia'],
            ['full_name' => 'Al Gibran Ivander Jatiasmara'],
        ];
        $nis = 2022001;
        foreach ($students41 as $s) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $s['full_name'],
                'address' => 'Alamat siswa',
                'phone_number' => '081200000000',
                'status' => 'active',
                'kelas' => 'Kelas 4.1',
            ];
        }

        // Data siswa kelas 4.2
        $students42 = [
            ['full_name' => 'Abinaya Runako Alexi'],
            ['full_name' => 'Adent Mas Damar Gatra'],
            ['full_name' => 'Andra Reyzha'],
            ['full_name' => 'Anindita Keisya Zahra'],
            ['full_name' => 'Archelyana Taviesha Sarasvati'],
            ['full_name' => 'Arsahka Hanan Ismail'],
            ['full_name' => 'Asifa Puspita Aryanto'],
            ['full_name' => 'Azarine Faeyza Kiar Prasetyo'],
            ['full_name' => 'Dzaky Denta Alfatih'],
            ['full_name' => 'Muhammad Alifiandra Naufal Argani'],
            ['full_name' => 'Muhammad Alby Raffa F'],
            ['full_name' => 'Muhammad Dzaky Nur Fauzan'],
            ['full_name' => 'Nadhira Ayundita'],
            ['full_name' => 'Solehah'],
            ['full_name' => 'Syakira Nur Arfanda'],
            ['full_name' => 'Taqiyya Sakha Althafunnisa'],
            ['full_name' => 'Zulvano Ar-ra\'uf Fitra Rahmatulloh'],
            ['full_name' => 'Mikayla Ar Rahma'],
            ['full_name' => 'Fadli Abizar Ramadhan'],
        ];
        $nis = 2022001 + count($students41);
        foreach ($students42 as $s) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $s['full_name'],
                'address' => 'Alamat siswa',
                'phone_number' => '081200000000',
                'status' => 'active',
                'kelas' => 'Kelas 4.2',
            ];
        }

        // Data siswa kelas 3.1
        $students31 = [
            ['full_name' => 'Abid Wahyu Narendra'],
            ['full_name' => 'Abida Fitria'],
            ['full_name' => 'Afifuddin Yusuf'],
            ['full_name' => 'Alula Keenar Beneamata'],
            ['full_name' => 'Anindya Faiha Faliah'],
            ['full_name' => 'Annisa Nurul Shafina'],
            ['full_name' => 'Aqila Khanza Mahesi'],
            ['full_name' => 'Ar-Rasyid Gemie Richardio'],
            ['full_name' => 'Aryasatya Lawana'],
            ['full_name' => 'Davina Anindya Calista Irawan'],
            ['full_name' => 'Donna Sabia Adara Nasution'],
            ['full_name' => 'Iman Taufik Rachman'],
            ['full_name' => 'Jabriel Rakanarendra'],
            ['full_name' => 'Kayla Balqis Ferdiansah'],
            ['full_name' => 'Maryam Jasmine Utami'],
            ['full_name' => 'Muhammad Ali Ramadhan Putra'],
            ['full_name' => 'Muhammad Hamengku Majid'],
            ['full_name' => 'Muhammad Naufal Tirta Abidzar'],
            ['full_name' => 'Mutiara Brenda Noviani'],
            ['full_name' => 'Naura Shava Azzahra'],
            ['full_name' => 'Raisya Almashyra'],
            ['full_name' => 'Rakabima Maulana'],
            ['full_name' => 'Zainab Hamida Al Hamid'],
            ['full_name' => 'Raditya Haikal Iskandar'],
            ['full_name' => 'Prabu Ataya Pinandita'],
            ['full_name' => 'Nabila Dian Arista'],
        ];
        $nis = 2023001;
        foreach ($students31 as $s) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $s['full_name'],
                'address' => 'Alamat siswa',
                'phone_number' => '081200000000',
                'status' => 'active',
                'kelas' => 'Kelas 3.1',
            ];
        }

        // Data siswa kelas 3.2
        $students32 = [
            ['full_name' => 'Abizar Syailendra Prasetya'],
            ['full_name' => 'Aila Shakila Yanika'],
            ['full_name' => 'Alan Razan Fahri'],
            ['full_name' => 'Albi Nur Riskina'],
            ['full_name' => 'Aldric Aruna Rayiaji'],
            ['full_name' => 'Aquina Bellvania Nasyira Bawono'],
            ['full_name' => 'Arshakan Reynand H'],
            ['full_name' => 'Attariz Dyota Davinse'],
            ['full_name' => 'Azalea Khaliqa Rumiva Bilqis Andriana'],
            ['full_name' => 'Azkadina Eka Marvella'],
            ['full_name' => 'Deviko Raihan T.S'],
            ['full_name' => 'Fenny Nur Ambarwati'],
            ['full_name' => 'Gissel Arsyana Putri'],
            ['full_name' => 'Malaeka Farzana Hamani'],
            ['full_name' => 'Muhammad Erland Abid Runako'],
            ['full_name' => 'Praveena Violin Elsa Zaren'],
            ['full_name' => 'Raisa Humaira'],
            ['full_name' => 'Rania Humaira Noor Latifa'],
            ['full_name' => 'Riyan Putra Tama'],
            ['full_name' => 'Zaen Fafa Saputra'],
            ['full_name' => 'Zhafira Kanaya Mardalasta'],
            ['full_name' => 'Zafran Mikail'],
            ['full_name' => 'Yusuf Hiroshi Darmawan'],
            ['full_name' => 'Azkadina Athaya Mahardiko'],
            ['full_name' => 'Sultan Abdullah Pinandita'],
        ];
        $nis = 2023001 + count($students31);
        foreach ($students32 as $s) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $s['full_name'],
                'address' => 'Alamat siswa',
                'phone_number' => '081200000000',
                'status' => 'active',
                'kelas' => 'Kelas 3.2',
            ];
        }


        // Data siswa kelas 2.1 (dengan kapitalisasi nama)
        $students21 = [
            ['full_name' => 'Adyatma Serkan Ramazan'],
            ['full_name' => 'Aisha Syifa El Medina'],
            ['full_name' => 'Alfarezi Ramadhan'],
            ['full_name' => 'Athaya Putri Qirani'],
            ['full_name' => 'Ayra Savina Rayadhanti'],
            ['full_name' => 'Bilal Aksani Akbar'],
            ['full_name' => 'Devania Oktavia Fiorenza'],
            ['full_name' => 'Dixie Kusnan Atmojo'],
            ['full_name' => 'Geosava Maliki Irawan'],
            ['full_name' => 'Ibrahim Ramadhan Al Ghazi'],
            ['full_name' => 'Inara Aileen'],
            ['full_name' => 'Meysha Azalea Ramadhan'],
            ['full_name' => 'Muhammad Brian Ibrahim'],
            ['full_name' => 'Raihan Cahyo Purnomo'],
            ['full_name' => 'Rasya Athayya Mahardika'],
            ['full_name' => 'Rivaliant Kahfi Fatir Ergantara'],
            ['full_name' => 'Rizky Hutama'],
            ['full_name' => 'Shakila Ayra Khairina'],
            ['full_name' => 'Umaila Sheza'],
        ];
        $nis = 2024001;
        foreach ($students21 as $s) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $s['full_name'],
                'address' => 'Alamat siswa',
                'phone_number' => '081200000000',
                'status' => 'active',
                'kelas' => 'Kelas 2.1',
            ];
        }

        // Data siswa kelas 2.2 (dengan kapitalisasi nama)
        $students22 = [
            ['full_name' => 'Adhara Khayra Mahardiko'],
            ['full_name' => 'Ahsan Fawwas Affandi'],
            ['full_name' => 'Almera Hana Farisha'],
            ['full_name' => 'Attaya Gibran Ravindra'],
            ['full_name' => 'Daymauza Arashsyal Ribowo'],
            ['full_name' => 'Falisha Khanza Adzkiya'],
            ['full_name' => 'Gaalvan Kunjasson'],
            ['full_name' => 'Gempita Manggar Ayu'],
            ['full_name' => 'Hafizh Erdogan'],
            ['full_name' => 'Hasan Maulana Yusuf'],
            ['full_name' => 'Khalya Qurota Ayun'],
            ['full_name' => 'Muhammad Arka A'],
            ['full_name' => 'Muhammad Fattah'],
            ['full_name' => 'Muhammad Furqon Raffiansyuri'],
            ['full_name' => 'Nadia Kayra Azmya'],
            ['full_name' => 'Raditya Reyhan'],
            ['full_name' => 'Reynand Il Principino Faeyza'],
            ['full_name' => 'Sufi Azka Rizqulloh'],
        ];
        $nis = 2024001 + count($students21);
        foreach ($students22 as $s) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $s['full_name'],
                'address' => 'Alamat siswa',
                'phone_number' => '081200000000',
                'status' => 'active',
                'kelas' => 'Kelas 2.2',
            ];
        }

        // Data siswa kelas 1.1
        $students11 = [
            ['full_name' => 'Alika Azalea Qinara'],
            ['full_name' => 'Arjuna Arrasyad'],
            ['full_name' => 'Arjuna Rachmadi Dhananjaya'],
            ['full_name' => 'Arshaka Tsabit Adnan'],
            ['full_name' => 'Ashadea Safa Kharisma'],
            ['full_name' => 'Athalla Raffasya Alfarizi Aryanto'],
            ['full_name' => 'Atharva Bimasena R'],
            ['full_name' => 'Atthar Virendra Adyatama'],
            ['full_name' => 'Davine Alvaro Septiano'],
            ['full_name' => 'Dzulfikri Hafidz Abdurrahman'],
            ['full_name' => 'Fatimatuzzahra'],
            ['full_name' => 'Ibrahim Alfarizy L'],
            ['full_name' => 'Kiana Rinjani T'],
            ['full_name' => 'Labeeba Balqish Himawan'],
            ['full_name' => 'Mahika Nadhira S'],
            ['full_name' => 'Maulana Adnan Suwandi'],
            ['full_name' => 'Maulana Ersya Mahendra'],
            ['full_name' => 'Muhammad Faizal Mahardika'],
            ['full_name' => 'Muhammad Irfan Rido Maulidi'],
            ['full_name' => 'Nadiya Shayna Azarine'],
            ['full_name' => 'Putri Nurmagfirah'],
            ['full_name' => 'Rayyanka Arcello Prasetyo'],
            ['full_name' => 'Reyhan Attala Jefarullah Biyansah'],
            ['full_name' => 'Rona Aulia Azka'],
            ['full_name' => 'Tisha Aina Callia'],
        ];
        $nis = 2025001;
        foreach ($students11 as $s) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $s['full_name'],
                'address' => 'Alamat siswa',
                'phone_number' => '081200000000',
                'status' => 'active',
                'kelas' => 'Kelas 1.1',
            ];
        }

        // Data siswa kelas 1.2
        $students12 = [
            ['full_name' => 'Adyatma Alby Davie'],
            ['full_name' => 'Adzana Syifa Mayhelka'],
            ['full_name' => 'Alfath Khalifatulllah Chandra'],
            ['full_name' => 'Alfayra Zahira Queesha'],
            ['full_name' => 'Ammar Hafidz Y'],
            ['full_name' => 'Anishatun Nur Afidah'],
            ['full_name' => 'Arsyila Salma Z'],
            ['full_name' => 'Azka Alvaro Rofii'],
            ['full_name' => 'Cellonika Raqeesha'],
            ['full_name' => 'Chairul Rizqi Ardana'],
            ['full_name' => 'Diajeng Anisa R.K'],
            ['full_name' => 'Dinantia Ghina Nur Slavina'],
            ['full_name' => 'Evano Kaivan Radhika'],
            ['full_name' => 'Hero Axel Wijaya'],
            ['full_name' => 'Lazuardi Anggara P'],
            ['full_name' => 'Lintang Artanabil Alfatih'],
            ['full_name' => 'Mikayla Najwa Hafizah'],
            ['full_name' => 'Muhammad Aqmal Maulana'],
            ['full_name' => 'Nadzan Adrian Ozza'],
            ['full_name' => 'Putri Aisyah Kirana'],
            ['full_name' => 'Satriyo Alkahfi Pinandita'],
            ['full_name' => 'Shaumi Zoya Almeira'],
            ['full_name' => 'Sulthan Al Gifari Arshaq Permana'],
            ['full_name' => 'Syauqi Muhammad Abimanyu'],
            ['full_name' => 'Wulan Rahmawati'],
            ['full_name' => 'Zakhyra Queena Dewi Nugraha'],
        ];
        $nis = 2025001 + count($students11);
        foreach ($students12 as $s) {
            $allStudents[] = [
                'nis' => (string)$nis++,
                'full_name' => $s['full_name'],
                'address' => 'Alamat siswa',
                'phone_number' => '081200000000',
                'status' => 'active',
                'kelas' => 'Kelas 1.2',
            ];
        }

        foreach ($allStudents as $studentData) {
            $student = Student::create([
                'nis' => $studentData['nis'],
                'full_name' => $studentData['full_name'],
                'address' => $studentData['address'] ?? null,
                'phone_number' => $studentData['phone_number'] ?? null,
                'status' => $studentData['status'],
            ]);

            // Deteksi tahun masuk dan kelas terakhir
            $nis = (int)$studentData['nis'];
            $kelasAkhir = $studentData['kelas'];
            $kelasAkhirLevel = 1;
            $kelasSuffix = '1';
            if (preg_match('/Kelas (\d)\.(\d)/', $kelasAkhir, $m)) {
                $kelasAkhirLevel = (int)$m[1];
                $kelasSuffix = $m[2];
            }
            // Batasi level maksimal 6
            if ($kelasAkhirLevel > 6) $kelasAkhirLevel = 6;
            // Alumni 2018: tahun masuk 2018/2019, alumni 2019: 2019/2020, dst
            if ($nis >= 2018001 && $nis < 2019000) {
                $tahunMasuk = 2018;
            } elseif ($nis >= 2019001 && $nis < 2020000) {
                $tahunMasuk = 2019;
            } elseif ($nis >= 2020001 && $nis < 2021000) {
                $tahunMasuk = 2020;
            } elseif ($nis >= 2021001 && $nis < 2022000) {
                $tahunMasuk = 2021;
            } elseif ($nis >= 2022001 && $nis < 2023000) {
                $tahunMasuk = 2022;
            } elseif ($nis >= 2023001 && $nis < 2024000) {
                $tahunMasuk = 2023;
            } elseif ($nis >= 2024001 && $nis < 2025000) {
                $tahunMasuk = 2024;
            } else {
                // Siswa aktif: fallback ke tahun ajar aktif
                $tahunAjarAktif = 2025;
                $tahunMasuk = $tahunAjarAktif - ($kelasAkhirLevel - 1);
            }
            // Buat class history kelas 1 s/d kelas akhir
            for ($i = 1; $i <= $kelasAkhirLevel; $i++) {
                $tahun = $tahunMasuk + ($i - 1);
                $academicYearObj = AcademicYear::where('name', $tahun . '/' . ($tahun + 1))->first();
                $kelasNama = 'Kelas ' . $i . '.' . $kelasSuffix;
                $kelasObj = Classes::where('name', $kelasNama)->first();
                if ($kelasObj && $academicYearObj) {
                    StudentClassHistory::create([
                        'student_id' => $student->id,
                        'class_id' => $kelasObj->id,
                        'academic_year_id' => $academicYearObj->id,
                    ]);
                }
            }
        }

        $this->command->info('Created ' . count($allStudents) . ' students for Kelas 6.1, 6.2, 5.1, 5.2, 4.1, 4.2, 3.1, 3.2, 2.1, 2.2, 1.1 & 1.2 with class assignments');
    }
}
