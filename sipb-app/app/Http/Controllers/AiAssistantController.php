<?php

namespace App\Http\Controllers;

use App\Services\AiChatService;
use App\Services\SystemPromptBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function __construct(
        private readonly AiChatService $chatService,
        private readonly SystemPromptBuilder $promptBuilder,
    ) {}

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
        $imageData = $data['image_data'] ?? null;

        $systemResult = $this->promptBuilder->build(
            $data['path'] ?? '/',
            $originalLatest,
            $imageData !== null,
        );

        $result = $this->chatService->chat(
            $messages,
            $systemResult,
            $imageData,
            $originalLatest,
        );

        return response()->json($result);
    }
}
