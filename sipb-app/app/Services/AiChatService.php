<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiChatService
{
    private const FALLBACK_LIMIT = 4;
    private const VISION_SELECT_LIMIT = 3;

    public function __construct(
        private readonly SystemPromptBuilder $promptBuilder,
    ) {}

    public function chat(array $messages, array $systemResult, ?string $imageData, ?string $originalLatest): array
    {
        $useVision = $imageData !== null;
        $fallbackAction = $this->fallbackAction($originalLatest ?? '');

        if (! config('services.groq.key')) {
            return $this->buildResponse('AI belum aktif karena GROQ_API_KEY belum dipasang di server. Sementara ini, saya bisa arahkan: gunakan Cari Barang untuk melihat barang tersedia, atau Bantuan untuk panduan layanan.', $fallbackAction, $systemResult['items']);
        }

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

            return $this->buildResponse('AI sedang belum bisa dihubungi. Untuk sekarang, coba gunakan halaman Cari Barang atau Bantuan.', $fallbackAction, $systemResult['items']);
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

        $items = $systemResult['items'];
        if (is_array($itemIds) && count($itemIds) > 0) {
            $items = collect($items)
                ->filter(fn ($item) => in_array($item['id'], $itemIds))
                ->values()
                ->take(self::VISION_SELECT_LIMIT)
                ->toArray();
        } else {
            $items = array_slice($items, 0, self::FALLBACK_LIMIT);
        }

        return $this->buildResponse(
            $reply !== '' ? $reply : 'Saya siap membantu mencari barang atau menjelaskan alur pengambilan di SIPB UYM.',
            $this->sanitizeAction($actionPayload) ?? $fallbackAction,
            $items,
        );
    }

    public function fallbackAction(string $message): ?array
    {
        [$cleanMessage] = $this->promptBuilder->stripTopicPrefix($message);
        $text = Str::lower($cleanMessage);

        if (Str::contains($text, ['bantuan', 'jam layanan', 'cara', 'ambil', 'mengambil'])) {
            return [
                'type' => 'help',
                'label' => 'Buka bantuan',
                'url' => '/bantuan',
            ];
        }

        if (Str::contains($text, ['cari', 'hilang', 'kehilangan', 'dompet', 'tas', 'buku', 'kartu', 'mouse', 'charger', 'kunci', 'barang'])) {
            return [
                'type' => 'search',
                'label' => 'Cari barang',
                'url' => '/cari',
            ];
        }

        return null;
    }

    public function sanitizeAction(mixed $action): ?array
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

    private function buildResponse(string $reply, ?array $action, array $items): array
    {
        return [
            'reply' => $reply,
            'action' => $action,
            'items' => $items,
        ];
    }
}
