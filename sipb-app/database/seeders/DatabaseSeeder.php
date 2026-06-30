<?php

namespace Database\Seeders;

use App\Models\FoundItem;
use App\Models\StatusAudit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminPassword = env('SEEDER_ADMIN_PASSWORD', 'password');
        $admin = User::updateOrCreate(
            ['email' => env('SEEDER_ADMIN_EMAIL', 'admin@sipb.test')],
            [
                'name' => 'Admin Resepsionis',
                'role' => User::ROLE_SUPER_ADMIN,
                'password' => Hash::make($adminPassword),
            ],
        );

        $items = [
            [
                'name' => 'Dompet hitam',
                'category' => 'Aksesoris',
                'description' => 'Dompet lipat warna hitam, ditemukan dalam kondisi tertutup.',
                'location' => 'Kantin',
                'found_at' => now()->subDays(1)->setTime(12, 20),
                'photo_url' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?auto=format&fit=crop&w=900&q=80',
                'status' => 'tersedia',
                'published_at' => now()->subDay(),
                'finder_name' => 'Mahasiswa Pelapor',
                'finder_nim' => '231060001',
                'storage_location' => 'Resepsionis gedung A',
            ],
            [
                'name' => 'Kartu mahasiswa',
                'category' => 'Kartu',
                'description' => 'Kartu identitas mahasiswa dengan sebagian detail disamarkan.',
                'location' => 'Perpustakaan',
                'found_at' => now()->subDays(2)->setTime(15, 45),
                'photo_url' => 'https://images.unsplash.com/photo-1585079542156-2755d9c8a094?auto=format&fit=crop&w=900&q=80',
                'status' => 'tersedia',
                'published_at' => now()->subDays(2),
                'finder_name' => 'Staf Perpustakaan',
                'storage_location' => 'Meja informasi perpustakaan',
            ],
            [
                'name' => 'Mouse wireless',
                'category' => 'Elektronik',
                'description' => 'Mouse wireless abu-abu dengan receiver USB.',
                'location' => 'Laboratorium komputer',
                'found_at' => now()->subDays(4)->setTime(9, 10),
                'photo_url' => 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=900&q=80',
                'status' => 'sudah_diambil',
                'published_at' => now()->subDays(4),
                'claimed_at' => now()->subDay(),
                'finder_name' => 'Asisten Lab',
                'storage_location' => 'Lab komputer 2',
            ],
            [
                'name' => 'Buku catatan merah',
                'category' => 'Buku',
                'description' => 'Buku catatan spiral warna merah dengan beberapa stiker.',
                'location' => 'Ruang kelas',
                'found_at' => now()->subHours(5),
                'photo_url' => 'https://images.unsplash.com/photo-1531346878377-a5be20888e57?auto=format&fit=crop&w=900&q=80',
                'status' => 'draft',
                'finder_name' => 'Nabila',
                'finder_nim' => '231060071',
                'storage_location' => 'Resepsionis gedung B',
            ],
        ];

        foreach ($items as $data) {
            $item = FoundItem::updateOrCreate(
                ['name' => $data['name'], 'found_at' => $data['found_at']],
                [...$data, 'managed_by' => $admin->id],
            );

            StatusAudit::firstOrCreate(
                [
                    'found_item_id' => $item->id,
                    'action' => 'seed',
                ],
                [
                    'user_id' => $admin->id,
                    'from_status' => null,
                    'to_status' => $item->status,
                ],
            );
        }

        $this->call(FoundItemSeeder::class);
    }
}
