<?php

namespace App\Http\Controllers;

use App\Models\FoundItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AiAssistantController extends Controller
{
    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'messages' => ['required', 'array', 'min:1', 'max:12'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:1200'],
            'path' => ['nullable', 'string', 'max:160'],
        ]);

        $messages = collect($data['messages'])
            ->take(-8)
            ->map(fn (array $message) => [
                'role' => $message['role'],
                'content' => trim($message['content']),
            ])
            ->values()
            ->all();
        $latestUserMessage = collect($messages)->where('role', 'user')->last()['content'] ?? '';
        $fallbackAction = $this->fallbackAction($latestUserMessage);

        if (! config('services.groq.key')) {
            return response()->json([
                'reply' => 'AI belum aktif karena GROQ_API_KEY belum dipasang di server. Sementara ini, saya bisa arahkan: gunakan Cari Barang untuk melihat barang tersedia, atau Bantuan untuk panduan layanan.',
                'action' => $fallbackAction,
            ]);
        }

        try {
            $response = Http::withToken(config('services.groq.key'))
                ->acceptJson()
                ->timeout(20)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => config('services.groq.model'),
                    'temperature' => 0.25,
                    'max_tokens' => 520,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt($data['path'] ?? '/', $latestUserMessage)],
                        ...$messages,
                    ],
                ]);

            if (! $response->successful()) {
                report(new \RuntimeException('Groq API error: '.$response->status().' '.$response->body()));

                return response()->json([
                    'reply' => 'AI sedang belum bisa dihubungi. Untuk sekarang, coba gunakan halaman Cari Barang atau Bantuan.',
                    'action' => $fallbackAction,
                ], 502);
            }

            $content = $response->json('choices.0.message.content', '{}');
            $payload = json_decode($content, true) ?: [];
            $reply = trim((string) ($payload['reply'] ?? ''));
            $actionPayload = $payload['action'] ?? null;


            return response()->json([
                'reply' => $reply !== '' ? $reply : 'Saya siap membantu mencari barang atau menjelaskan alur pengambilan di SIPB UYM.',
                'action' => $this->sanitizeAction($actionPayload) ?? $fallbackAction,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'reply' => 'AI sedang tidak stabil. Kamu tetap bisa cari barang di halaman pencarian.',
                'action' => $fallbackAction,
            ], 502);
        }
    }

    private function systemPrompt(string $path, string $message): string
    {
        $categories = implode(', ', config('sipb.categories', []));
        $locations = implode(', ', config('sipb.locations', []));
        
        $query = FoundItem::query()
            ->visibleToPublic();
        $text = Str::lower($message);
        
        foreach (config('sipb.locations', []) as $location) {
            if (Str::contains($text, Str::lower($location))) {
                $query->where('location', $location);
                break;
            }
        }
        
        foreach (config('sipb.categories', []) as $category) {
            if (Str::contains($text, Str::lower($category))) {
                $query->where('category', $category);
                break;
            }
        }
        
        $items = $query->latest('published_at')
            ->limit(40)
            ->get(['id', 'name', 'category', 'location', 'description', 'found_at'])
            ->map(fn (FoundItem $item) => sprintf(
                '- %s (Ditemukan: %s) | %s | %s | /barang/%s | %s',
                $item->name,
                $item->found_at ? $item->found_at->translatedFormat('j F Y') : '-',
                $item->category,
                $item->location,
                $item->id,
                Str::limit((string) $item->description, 90)
            ));

        $itemsText = $items->isEmpty()
            ? "Belum ada barang yang cocok di database."
            : "Data barang (Temuan) di database:\n" . $items->implode("\n");

        return <<<PROMPT
Kamu adalah Asisten AI Agent SIPB UYM, layanan lost and found kampus.
Jawab selalu dalam Bahasa Indonesia yang ramah, singkat, dan proaktif.

TUGAS UTAMA:
1. Menjawab pertanyaan pengguna dengan natural, ramah, dan komunikatif. JIKA pengguna mencari barang, KAMU WAJIB menyebutkan nama-nama barang dari 'Data barang' yang relevan dalam jawabanmu. JANGAN hanya menjawab dengan 1 kalimat pendek!
2. Jika ada yang melapor menemukan barang, arahkan mereka untuk menyerahkannya ke resepsionis atau admin agar bisa dicatat.
3. Pandu pengguna bagaimana cara mencari atau mengambil barang.

Aturan:
- Jangan meminta password atau token.
- Jika pengguna ingin melihat daftar barang, sarankan membuka pencarian.

Kategori tersedia: {$categories}
Lokasi tersedia: {$locations}
Path pengguna sekarang: {$path}
{$itemsText}

Balas HANYA JSON valid dengan format:
{
  "reply": "jawaban natural, wajib sebutkan nama barang dari data jika ada",
  "action": {
      "type": "search|help|none",
      "label": "teks tombol (jika type search/help)",
      "url": "/path-internal (jika type search/help)"
  }
}
Catatan: Url hanya boleh /cari, /bantuan, atau /barang/{id}.
PROMPT;
    }

    private function fallbackAction(string $message): ?array
    {
        $text = Str::lower($message);

        if (Str::contains($text, ['bantuan', 'jam layanan', 'cara', 'ambil', 'mengambil'])) {
            return [
                'type' => 'help',
                'label' => 'Buka bantuan',
                'url' => '/bantuan',
            ];
        }

        if (Str::contains($text, ['cari', 'hilang', 'kehilangan', 'dompet', 'tas', 'buku', 'kartu', 'mouse', 'charger', 'kunci', 'barang'])) {
            $query = ['q' => Str::limit(trim($message), 80, '')];

            foreach (config('sipb.locations', []) as $location) {
                if (Str::contains($text, Str::lower($location))) {
                    $query['location'] = $location;
                    break;
                }
            }

            foreach (config('sipb.categories', []) as $category) {
                if (Str::contains($text, Str::lower($category))) {
                    $query['category'] = $category;
                    break;
                }
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
