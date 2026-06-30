<?php

namespace App\Services;

use App\Models\FoundItem;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SystemPromptBuilder
{
    private const VISION_MAX_ITEMS = 12;
    private const TEXT_MAX_ITEMS = 12;

    public function build(string $path, string $message, bool $hasImage = false): array
    {
        [$cleanMessage, $topic] = $this->stripTopicPrefix($message);

        $categories = implode(', ', config('sipb.categories', []));
        $locations = implode(', ', config('sipb.locations', []));
        $text = Str::lower($cleanMessage);
        $topicBasedSearch = $topic && in_array($topic, ['Cari Barang', 'Lapor Barang Hilang']);
        $shouldSearch = $hasImage || $topicBasedSearch || $this->hasSearchIntent($text);

        if ($shouldSearch) {
            $query = FoundItem::query()->visibleToPublic();
            $limit = self::TEXT_MAX_ITEMS;

            if (! $hasImage) {
                $parsedDate = $this->parseDateFromMessage($text);
                if ($parsedDate) {
                    $query->whereDate('found_at', $parsedDate);
                }

                $matchedLocation = $this->matchFilterFromMessage($text, 'locations');
                if ($matchedLocation) {
                    $query->where('location', 'like', "%{$matchedLocation}%");
                }

                $matchedCategory = $this->matchFilterFromMessage($text, 'categories');
                if ($matchedCategory) {
                    $query->where('category', 'like', "%{$matchedCategory}%");
                }
            } else {
                $limit = self::VISION_MAX_ITEMS;
            }

            $collection = $query->latest('published_at')
                ->limit($limit)
                ->get(['id', 'name', 'category', 'location', 'photo_url', 'photo_data', 'photo_path', 'description', 'found_at']);

            $items = $collection->map(fn (FoundItem $item) => sprintf(
                '- ID:%s | [%s] — %s, %s — %s. %s',
                $item->id,
                $item->name,
                $item->category,
                $item->location,
                $item->found_at ? $item->found_at->translatedFormat('j F Y') : '-',
                trim((string) $item->description)
            ));

            $itemsText = $items->isEmpty()
                ? "Tidak ada barang yang cocok dengan filter di database."
                : "Data barang (Temuan) yang cocok:\n" . $items->implode("\n");
        } else {
            $collection = collect();
            $itemsText = null;
        }

        $topicInfo = $topic ? "\nTopik dari sistem pengirim: {$topic}" : '';
        $visionInfo = $hasImage
            ? "\nUser mengirim FOTO barang. Analisis foto dan cocokkan ciri-cirinya (merk, warna, bentuk) dengan deskripsi 'Data barang' di bawah.\nTulis ID barang yang PALING MIRIP di item_ids (maks 3). Jika tidak ada yang cocok, biarkan array kosong []."
            : '';

        $prompt = $shouldSearch ? <<<PROMPT
Kamu adalah Asisten AI Agent SIPB UYM, layanan lost and found kampus.
Jawab selalu dalam Bahasa Indonesia yang ramah, singkat, dan proaktif.

TUGAS UTAMA:
1. Jawab pertanyaan HANYA berdasarkan 'Data barang' yang diberikan di bawah. JANGAN membuat jawaban sendiri. JANGAN mengarang barang yang tidak ada di data.
2. Jika data kosong, akui dengan jujur bahwa tidak ada barang yang cocok.
3. Jika ada yang melapor menemukan barang, arahkan mereka untuk menyerahkannya ke resepsionis atau admin agar bisa dicatat.
4. Pandu pengguna bagaimana cara mencari atau mengambil barang.{$visionInfo}

Aturan:
- Jika data barang tidak kosong dan relevan, WAJIB sebutkan nama barangnya dalam jawaban.
- Jangan meminta password atau token.
- Jika pengguna ingin melihat daftar lengkap, sarankan membuka halaman pencarian.
- {$topicInfo}

Kategori tersedia: {$categories}
Lokasi tersedia: {$locations}
Path pengguna sekarang: {$path}
{$itemsText}

Balas WAJIB MENGGUNAKAN JSON SAJA. DILARANG MENJAWAB DENGAN KALIMAT BIASA ATAU TEKS LAINNYA.
Gunakan format persis seperti ini:
{
  "reply": "jawaban natural Anda di sini...",
  "action": {
      "type": "search|help|none",
      "label": "teks tombol",
      "url": "/path"
  },
  "item_ids": [1, 2]
}
WAJIB: Isi "item_ids" dengan array angka ID barang yang cocok. Jika tidak ada yang cocok, isi dengan [].
Catatan: Url hanya boleh /cari, /bantuan, atau /barang/{id}.
PROMPT
        : <<<PROMPT
Kamu adalah Asisten AI Agent SIPB UYM, layanan lost and found kampus.
Jawab selalu dalam Bahasa Indonesia yang ramah, singkat, dan proaktif.

TUGAS UTAMA:
1. Bantu user dengan pertanyaan mereka tentang layanan SIPB UYM.
2. Jika user mencari barang tertentu, arahkan untuk menggunakan fitur pencarian.
3. Jika ada yang melapor menemukan barang, arahkan ke resepsionis atau admin.

Aturan:
- Jangan meminta password atau token.
- Jika user ingin mencari barang, sarankan membuka halaman pencarian.
- Jangan menyebut atau mengarang barang yang tidak ada di data.
- {$topicInfo}

Kategori tersedia: {$categories}
Lokasi tersedia: {$locations}
Path pengguna sekarang: {$path}

Balas HANYA JSON valid dengan format ketat. JANGAN sertakan kalimat pembuka/penutup. JANGAN gunakan markdown ```json.
{
  "reply": "jawaban natural",
  "action": {
      "type": "search|help|none",
      "label": "teks tombol (jika type search/help)",
      "url": "/path-internal (jika type search/help)"
  }
}
Catatan: Url hanya boleh /cari, /bantuan, atau /barang/{id}.
PROMPT;

        return [
            'prompt' => $prompt,
            'items' => $collection->map(fn (FoundItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'photo_url' => $item->photo_url,
            ])->values()->toArray(),
        ];
    }

    public function hasSearchIntent(string $text): bool
    {
        if (Str::contains($text, ['syarat', 'prosedur', 'cara ambil', 'cara mengambil'])) {
            return false;
        }

        $keywords = ['cari', 'temukan', 'hilang', 'kehilangan',
            'dompet', 'tas', 'buku', 'kartu', 'mouse', 'charger',
            'kunci', 'hp', 'laptop', 'jam', 'kacamata',
            'flashdisk', 'hardisk', 'kalkulator', 'botol', 'tumbler',
            'jaket', 'hoodie', 'topi', 'gelang', 'payung',
            'earphone', 'headset', 'ponsel', 'handphone', 'smartphone',
        ];

        foreach (config('sipb.categories', []) as $category) {
            if (Str::contains($text, Str::lower($category))) {
                return true;
            }
        }

        foreach (config('sipb.locations', []) as $location) {
            if (Str::contains($text, Str::lower($location))) {
                return true;
            }
        }

        return Str::contains($text, $keywords);
    }

    public function matchFilterFromMessage(string $text, string $configKey): ?string
    {
        foreach (config("sipb.{$configKey}", []) as $value) {
            if (Str::contains($text, Str::lower($value))) {
                return $value;
            }
        }

        return null;
    }

    public function stripTopicPrefix(string $message): array
    {
        $text = preg_replace('/\[topik:[^\]]+\]\s*/iu', '', $message);
        $topic = null;

        if (preg_match('/\[topik:\s*([^\]]+)\]/iu', $message, $m)) {
            $topic = trim($m[1]);
        }

        return [$text, $topic];
    }

    public function parseDateFromMessage(string $text): ?Carbon
    {
        $text = Str::lower($text);

        if (Str::contains($text, 'hari ini')) {
            return now()->startOfDay();
        }

        if (Str::contains($text, 'kemarin')) {
            return now()->subDay()->startOfDay();
        }

        $months = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
            'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
        ];

        $monthPattern = implode('|', array_keys($months));
        if (preg_match('/(?:tanggal\s+)?(\d{1,2})\s*(' . $monthPattern . ')\s*(\d{4})?/u', $text, $matches)) {
            $day = (int) $matches[1];
            $month = $months[$matches[2]];
            $year = ! empty($matches[3]) ? (int) $matches[3] : now()->year;

            $date = now()->setYear($year)->setMonth($month)->setDay($day)->startOfDay();

            if ($date->isFuture()) {
                $date->subYear();
            }

            return $date;
        }

        return null;
    }
}
