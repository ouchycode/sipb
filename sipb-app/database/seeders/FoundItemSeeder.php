<?php

namespace Database\Seeders;

use App\Models\FoundItem;
use App\Models\StatusAudit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FoundItemSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::where('email', 'admin@sipb.test')->first();

        if (! $admin) {
            $this->command->warn('Admin user not found. Run DatabaseSeeder first.');

            return;
        }

        $items = [
            [
                'name' => 'Dompet hitam',
                'category' => 'Aksesoris',
                'description' => 'Dompet lipat warna hitam berbahan kulit sintetis, ditemukan di meja kantin. Terdapat beberapa kartu di dalamnya.',
                'location' => 'Kantin',
                'found_at' => now()->subDays(1)->setTime(12, 20),
                'photo_url' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Ahmad Fauzi',
                'finder_nim' => '231060001',
                'storage_location' => 'Resepsionis Gedung A',
            ],
            [
                'name' => 'Kartu mahasiswa a/n Rina',
                'category' => 'Kartu',
                'description' => 'Kartu tanda mahasiswa atas nama Rina Amalia, ditemukan di lantai 2 perpustakaan.',
                'location' => 'Perpustakaan',
                'found_at' => now()->subDays(2)->setTime(15, 45),
                'photo_url' => 'https://images.unsplash.com/photo-1585079542156-2755d9c8a094?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Siti Nurhaliza',
                'finder_nim' => '231060015',
                'storage_location' => 'Meja Informasi Perpustakaan',
            ],
            [
                'name' => 'Mouse wireless',
                'category' => 'Elektronik',
                'description' => 'Mouse wireless merek Logitech warna abu-abu, masih ada receiver USB-nya.',
                'location' => 'Lab Komputer',
                'found_at' => now()->subDays(3)->setTime(9, 10),
                'photo_url' => 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Budi Santoso',
                'finder_nim' => '231060022',
                'storage_location' => 'Lab Komputer 2',
            ],
            [
                'name' => 'Buku catatan merah',
                'category' => 'Buku/ATK',
                'description' => 'Buku catatan spiral warna merah dengan stiker karakter动漫, berisi catatan kuliah semester ini.',
                'location' => 'Ruang Kelas 203',
                'found_at' => now()->subDays(4)->setTime(11, 30),
                'photo_url' => 'https://images.unsplash.com/photo-1531346878377-a5be20888e57?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Dewi Lestari',
                'finder_nim' => '231060035',
                'storage_location' => 'Resepsionis Gedung B',
            ],
            [
                'name' => 'Jaket hoodie abu-abu',
                'category' => 'Pakaian',
                'description' => 'Jaket hoodie warna abu-abu merk Erigo, ukuran L, ditemukan di bangku belakang masjid.',
                'location' => 'Masjid Kampus',
                'found_at' => now()->subDays(5)->setTime(13, 15),
                'photo_url' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Rizky Pratama',
                'finder_nim' => '231060048',
                'storage_location' => 'Gudang Barang Hilang Masjid',
            ],
            [
                'name' => 'Flashdisk 32GB',
                'category' => 'Elektronik',
                'description' => 'Flashdisk Sandisk 32GB warna hitam dengan gantungan kunci biru, berisi tugas semester.',
                'location' => 'Lab Komputer',
                'found_at' => now()->subDays(6)->setTime(16, 0),
                'photo_url' => 'https://images.unsplash.com/photo-1601471160059-9e2c32e68c9e?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Andi Wijaya',
                'finder_nim' => '231060056',
                'storage_location' => 'Lab Komputer 1',
            ],
            [
                'name' => 'Tas ransel hitam',
                'category' => 'Tas',
                'description' => 'Tas ransel hitam merk Axioo, kompartemen laptop, ditemukan di parkiran motor dekat pos satpam.',
                'location' => 'Parkiran Motor',
                'found_at' => now()->subDays(7)->setTime(7, 45),
                'photo_url' => 'https://images.unsplash.com/photo-1622560480605-d83c853bc5c3?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Satpam Rohmat',
                'finder_nim' => null,
                'storage_location' => 'Pos Satpam Depan',
            ],
            [
                'name' => 'Jam tangan silver',
                'category' => 'Aksesoris',
                'description' => 'Jam tangan analog warna silver with tali kulit coklat, merek Alexandre Christie.',
                'location' => 'Lapangan',
                'found_at' => now()->subDays(8)->setTime(10, 30),
                'photo_url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Fajar Nugroho',
                'finder_nim' => '231060067',
                'storage_location' => 'Resepsionis Gedung A',
            ],
            [
                'name' => 'Botol minum tumbler',
                'category' => 'Botol/Tempat Minum',
                'description' => 'Tumbler stainless warna putih 500ml, masih ada stiker SIPB di badannya.',
                'location' => 'Aula',
                'found_at' => now()->subDays(9)->setTime(14, 20),
                'photo_url' => 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Nabila Putri',
                'finder_nim' => '231060078',
                'storage_location' => 'Resepsionis Gedung A',
            ],
            [
                'name' => 'Kacamata hitam',
                'category' => 'Aksesoris',
                'description' => 'Kacamata hitam frame kotak merek Ray-Ban, ditemukan di kursi koridor Gedung A lantai 3.',
                'location' => 'Koridor Gd A',
                'found_at' => now()->subDays(10)->setTime(8, 50),
                'photo_url' => 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Citra Dewanti',
                'finder_nim' => '231060089',
                'storage_location' => 'Resepsionis Gedung A',
            ],
            [
                'name' => 'Charger laptop',
                'category' => 'Elektronik',
                'description' => 'Charger laptop Lenovo 65w dengan kabel masih utuh, ditemukan di colokan dekat jendela perpustakaan.',
                'location' => 'Perpustakaan',
                'found_at' => now()->subDays(11)->setTime(17, 30),
                'photo_url' => 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Dian Permata',
                'finder_nim' => '231060093',
                'storage_location' => 'Meja Informasi Perpustakaan',
            ],
            [
                'name' => 'Dompet coklat',
                'category' => 'Aksesoris',
                'description' => 'Dompet panjang warna coklat muda berbahan kanvas, ditemukan di bawah kursi auditorium.',
                'location' => 'Auditorium',
                'found_at' => now()->subDays(12)->setTime(19, 15),
                'photo_url' => 'https://images.unsplash.com/photo-1606503825008-909a67e63c3d?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Hendra Gunawan',
                'finder_nim' => '231060102',
                'storage_location' => 'Resepsionis Gedung A',
            ],
            [
                'name' => 'Kalkulator ilmiah',
                'category' => 'Alat Tulis',
                'description' => 'Kalkulator Casio fx-991ID PLUS, ditemukan di laci meja Ruang Kelas 305.',
                'location' => 'Ruang Kelas 305',
                'found_at' => now()->subDays(13)->setTime(10, 0),
                'photo_url' => 'https://images.unsplash.com/photo-1587145820266-a5951ee6f620?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Mega Sari',
                'finder_nim' => '231060115',
                'storage_location' => 'Resepsionis Gedung B',
            ],
            [
                'name' => 'Payung lipat',
                'category' => 'Lainnya',
                'description' => 'Payung lipat otomatis warna biru dongker, ditemukan di rak sepatu depan kantin.',
                'location' => 'Kantin',
                'found_at' => now()->subDays(14)->setTime(12, 45),
                'photo_url' => 'https://images.unsplash.com/photo-1605289982774-9a6fef564df8?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Rendi Kurniawan',
                'finder_nim' => '231060128',
                'storage_location' => 'Resepsionis Gedung A',
            ],
            [
                'name' => 'Hardisk eksternal',
                'category' => 'Elektronik',
                'description' => 'Hardisk eksternal Toshiba 1TB warna hitam, ditemukan di rak Lab Komputer 3.',
                'location' => 'Lab Komputer',
                'found_at' => now()->subDays(15)->setTime(15, 20),
                'photo_url' => 'https://images.unsplash.com/photo-1531492746078-200b9c9324b7?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Tiara Anindita',
                'finder_nim' => '231060134',
                'storage_location' => 'Lab Komputer 3',
            ],
            [
                'name' => 'Topi baseball navy',
                'category' => 'Pakaian',
                'description' => 'Topi baseball warna navy dengan logo New York Yankees, ditemukan di tribun lapangan.',
                'location' => 'Lapangan',
                'found_at' => now()->subDays(16)->setTime(16, 30),
                'photo_url' => 'https://images.unsplash.com/photo-1575428652377-a2d80e2277fc?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Dimas Ardiansyah',
                'finder_nim' => '231060147',
                'storage_location' => 'Resepsionis Gedung A',
            ],
            [
                'name' => 'Buku agenda 2026',
                'category' => 'Buku/ATK',
                'description' => 'Buku agenda tahun 2026 warna hijau, berisi jadwal kuliah dan catatan penting.',
                'location' => 'Aula',
                'found_at' => now()->subDays(17)->setTime(13, 0),
                'photo_url' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Sarah Mutiara',
                'finder_nim' => '231060153',
                'storage_location' => 'Resepsionis Gedung A',
            ],
            [
                'name' => 'Earphone wireless',
                'category' => 'Elektronik',
                'description' => 'Earphone TWS merek Xiaomi warna putih, ditemukan di meja baca perpustakaan lantai 3.',
                'location' => 'Perpustakaan',
                'found_at' => now()->subDays(18)->setTime(14, 10),
                'photo_url' => 'https://images.unsplash.com/photo-1590658268037-6bf12f032f55?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Gilang Ramadhan',
                'finder_nim' => '231060166',
                'storage_location' => 'Meja Informasi Perpustakaan',
            ],
            [
                'name' => 'Dompet panjang',
                'category' => 'Aksesoris',
                'description' => 'Dompet panjang warna hitam merek Pierre Cardin, ditemukan di dekat pintu keluar parkiran mobil.',
                'location' => 'Parkiran Mobil',
                'found_at' => now()->subDays(19)->setTime(17, 45),
                'photo_url' => 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Wulan Safitri',
                'finder_nim' => '231060172',
                'storage_location' => 'Pos Satpam Depan',
            ],
            [
                'name' => 'Gelang silver',
                'category' => 'Perhiasan',
                'description' => 'Gelang perak dengan ukiran motif bunga, ditemukan di tempat wudhu masjid kampus.',
                'location' => 'Masjid Kampus',
                'found_at' => now()->subDays(20)->setTime(5, 30),
                'photo_url' => 'https://images.unsplash.com/photo-1591955506260-fa62f7c0a3c8?auto=format&fit=crop&w=900&q=80',
                'finder_name' => 'Umar Faruq',
                'finder_nim' => '231060188',
                'storage_location' => 'Gudang Barang Hilang Masjid',
            ],
        ];

        foreach ($items as $i => $data) {
            $daysAgo = $i + 1;

            $item = FoundItem::updateOrCreate(
                ['name' => $data['name'], 'found_at' => $data['found_at']],
                array_merge($data, [
                    'status' => 'tersedia',
                    'published_at' => now()->subDays($daysAgo),
                    'managed_by' => $admin->id,
                    'photo_data' => null,
                    'claimant_name' => null,
                    'claimant_nim' => null,
                    'claimed_at' => null,
                    'rejected_at' => null,
                    'expired_at' => null,
                    'admin_notes' => null,
                    'validation_notes' => null,
                    'pickup_checklist' => null,
                ]),
            );

            StatusAudit::firstOrCreate(
                [
                    'found_item_id' => $item->id,
                    'action' => 'seeder:20-items',
                ],
                [
                    'user_id' => $admin->id,
                    'from_status' => null,
                    'to_status' => 'tersedia',
                ],
            );
        }

        $this->command->info('20 barang hilang (tersedia) berhasil di-seed.');
    }
}
