<?php

namespace App\Http\Controllers\Api\Mac;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AssistantController extends Controller
{
    public function chat(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required_without:attachments|nullable|string|max:20000',
            'session_id' => 'nullable|string|max:120',
            'source' => 'nullable|string|max:40',
            'context' => 'nullable|array',
            'attachments' => 'nullable|array|max:6',
            'attachments.*.type' => 'required|string|in:image,text',
            'attachments.*.name' => 'nullable|string|max:255',
            // base64-изображение до ~8 МБ или текст документа
            'attachments.*.content' => 'required|string|max:12000000',
            'attachments.*.mime' => 'nullable|string|max:60',
        ]);

        $token = config('services.notame_agent.internal_token');
        if (!$token) {
            return response()->json(['message' => 'Агент не настроен: NOTAME_AGENT_INTERNAL_TOKEN пустой'], 503);
        }

        $user = mac_app_user($request);
        $baseUrl = rtrim((string) config('services.notame_agent.base_url'), '/');
        $timeout = (int) config('services.notame_agent.timeout', 120);

        $response = Http::timeout($timeout)
            ->withToken($token)
            ->acceptJson()
            ->post($baseUrl . '/internal/chat', [
                'text' => $validated['text'] ?? '',
                'sessionId' => $validated['session_id'] ?? null,
                'source' => $validated['source'] ?? 'macos',
                'context' => $validated['context'] ?? [],
                'attachments' => $validated['attachments'] ?? [],
                'user' => [
                    'id' => $user?->ID,
                    'name' => $user?->display_name,
                    'email' => $user?->user_email,
                    'role' => $user?->getRole()?->name,
                ],
            ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Нейросотрудник временно недоступен',
                'agent_status' => $response->status(),
                'agent_error' => $response->json('error') ?? $response->body(),
            ], 502);
        }

        return response()->json($response->json());
    }
}
