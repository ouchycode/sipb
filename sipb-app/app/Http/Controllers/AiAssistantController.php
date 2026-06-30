<?php

namespace App\Http\Controllers;

use App\Models\FoundItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AiAssistantController extends Controller
{
    private const VISION_MAX_ITEMS = 12;
    private const TEXT_MAX_ITEMS = 12;
    private const VISION_SELECT_LIMIT = 3;
    private const FALLBACK_LIMIT = 4;
    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'messages' => ['required', 'array', 'min:1', 'max:12'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:1200'],
            'path' => ['nullable', 'string', 'max:160'],
            'image_data' => ['nullable', 'string', 'max:6000000'],
        ]);

        $recentMessages = collect($data['messages'])->take(-8);
        $messages = $recentMessages
            ->map(fn (array $message) => [
                'role' => $message['role'],
                'content' => trim(preg_replace('/\[topik:[^\]]+\]\s*/iu', '', $message['content'])),
            ])
            ->values()
            ->all();
        $originalLatest = $recentMessages->where('role', 'user')->last()['content'] ?? '';
        $fallbackAction = $this->fallbackAction($originalLatest);
        $imageData = $data['image_data'] ?? null;
        $useVision = $imageData !== null;
        $systemResult = $this->systemPrompt($data['path'] ?? '/', $originalLatest, $useVision);

        if (! config('services.groq.key')) {
            return response()->json([
                'reply' => 'AI belum aktif karena GROQ_API_KEY belum dipasang di server. Sementara ini, saya bisa arahkan: gunakan Cari Barang untuk melihat barang tersedia, atau Bantuan untuk panduan layanan.',
                'action' => $fallbackAction,
                'items' => $systemResult['items'],
            ]);
        }

        try {
            $apiMessages = [
                ['role' => 'system', 'content' => $systemResult['prompt']],
                ...$messages,
            ];

            if ($useVision) {
                for ($i = count($apiMessages) - 1; $i >= 0; $i--) {
                    if ($apiMessages[$i]['role'] === 'user') {
                        $apiMessages[$i] = [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => $apiMessages[$i]['content']],
                                ['type' => 'image_url', 'image_url' => ['url' => $imageData, 'detail' => 'high']],
                            ],
                        ];
                        break;
                    }
                }
            }

            $response = Http::withToken(config('services.groq.key'))
                ->acceptJson()
                ->timeout(60)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $useVision ? config('services.groq.vision_model') : config('services.groq.model'),
                    'temperature' => 0.25,
                    'max_tokens' => 2048,
                    'messages' => $apiMessages,
                ]);

            if (! $response->successful()) {
                report(new \RuntimeException('Groq API error: '.$response->status().' '.$response->body()));

                return response()->json([
                    'reply' => 'AI sedang belum bisa dihubungi. Untuk sekarang, coba gunakan halaman Cari Barang atau Bantuan.',
                    'action' => $fallbackAction,
                    'items' => $systemResult['items'],
                ], 502);
            }

            $content = $response->json('choices.0.message.content', '');
            \Illuminate\Support\Facades\Log::debug('Groq Output: ' . $content);

            $cleanContent = preg_replace('/<think>.*?(?:<\/think>|$)/is', '', $content);
            $cleanContent = preg_replace('/```(?:json)?/i', '', $cleanContent);
            $cleanContent = trim($cleanContent);

            $payload = json_decode($cleanContent, true);

            if (! $payload && preg_match('/\{.*\}/s', $cleanContent, $matches)) {
                $payload = json_decode($matches[0], true);
            }

            if (! $payload) {
                $payload = [
                    'reply' => $cleanContent,
                    'action' => ['type' => 'none'],
                    'item_ids' => []
                ];
            }
            $reply = trim((string) ($payload['reply'] ?? ''));
            $actionPayload = $payload['action'] ?? null;
            $itemIds = $payload['item_ids'] ?? null;

            if (is_array($itemIds) && count($itemIds) > 0) {
                $allItems = $systemResult['items'];
                $systemResult['items'] = collect($allItems)
                    ->filter(fn ($item) => in_array($item['id'], $itemIds))
                    ->values()
                    ->take(self::VISION_SELECT_LIMIT)
                    ->toArray();
            } else {
                $systemResult['items'] = array_slice($systemResult['items'], 0, self::FALLBACK_LIMIT);
            }

            return response()->json([
                'reply' => $reply !== '' ? $reply : 'Saya siap membantu mencari barang atau menjelaskan alur pengambilan di SIPB UYM.',
                'action' => $this->sanitizeAction($actionPayload) ?? $fallbackAction,
                'items' => $systemResult['items'],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'reply' => 'AI sedang tidak stabil. Kamu tetap bisa cari barang di halaman pencarian.',
                'action' => $fallbackAction,
                'items' => $systemResult['items'],
            ], 502);
        }
    }

    private function parseDateFromMessage(string $text): ?Carbon
    {
        $text = Str::lower($text);

        // "hari ini"
        if (Str::contains($text, 'hari ini')) {
            return now()->startOfDay();
        }

        // "kemarin"
        if (Str::contains($text, 'kemarin')) {
            return now()->subDay()->startOfDay();
        }

        $months = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
            'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
        ];

        // "5 Juni", "5 Juni 2026", "tanggal 5 Juni"
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

    private function stripTopicPrefix(string $message): array
    {
        $text = preg_replace('/\[topik:[^\]]+\]\s*/iu', '', $message);
        $topic = null;

        if (preg_match('/\[topik:\s*([^\]]+)\]/iu', $message, $m)) {
            $topic = trim($m[1]);
        }

        return [$text, $topic];
    }

    private function matchFilterFromMessage(string $text, string $configKey): ?string
    {
        foreach (config("sipb.{$configKey}", []) as $value) {
            if (Str::contains($text, Str::lower($value))) {
                return $value;
            }
        }

        return null;
    }

    private function hasSearchIntent(string $text): bool
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

    private function systemPrompt(string $path, string $message, bool $hasImage = false): array
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
                'photo_url' => $item->photo_path ? \Illuminate\Support\Facades\Storage::url($item->photo_path) : ($item->photo_data ?: $item->photo_url),
            ])->values()->toArray(),
        ];
    }

    private function fallbackAction(string $message): ?array
    {
        [$cleanMessage] = $this->stripTopicPrefix($message);
        $text = Str::lower($cleanMessage);

        if (Str::contains($text, ['bantuan', 'jam layanan', 'cara', 'ambil', 'mengambil'])) {
            return [
                'type' => 'help',
                'label' => 'Buka bantuan',
                'url' => '/bantuan',
            ];
        }

        if (Str::contains($text, ['cari', 'hilang', 'kehilangan', 'dompet', 'tas', 'buku', 'kartu', 'mouse', 'charger', 'kunci', 'barang'])) {
            $query = ['q' => Str::limit(trim($cleanMessage), 80, '')];

            $parsedDate = $this->parseDateFromMessage($text);
            if ($parsedDate) {
                $query['date'] = $parsedDate->format('Y-m-d');
            }

            $matchedLocation = $this->matchFilterFromMessage($text, 'locations');
            if ($matchedLocation) {
                $query['location'] = $matchedLocation;
            }

            $matchedCategory = $this->matchFilterFromMessage($text, 'categories');
            if ($matchedCategory) {
                $query['category'] = $matchedCategory;
            }

            return [
                'type' => 'search',
                'label' => 'Cari barang',
                'url' => '/cari?'.http_build_query($query),
            ];
        }

        return null;
    }

    private function sanitizeAction(mixed $action): ?array
    {
        if (! is_array($action) || ($action['type'] ?? 'none') === 'none') {
            return null;
        }

        $url = (string) ($action['url'] ?? '');
        $label = trim((string) ($action['label'] ?? 'Buka'));
        $type = (string) ($action['type'] ?? 'none');
        $allowedPrefixes = ['/cari', '/bantuan', '/barang/'];

        if ($label === '' || Str::startsWith($url, '//') || ! Str::startsWith($url, $allowedPrefixes)) {
            return null;
        }

        return [
            'type' => in_array($type, ['search', 'help'], true) ? $type : 'help',
            'label' => Str::limit($label, 32, ''),
            'url' => $url,
        ];
    }
}
