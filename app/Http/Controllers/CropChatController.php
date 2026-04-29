<?php

namespace App\Http\Controllers;

use App\Models\CropChatSession;
use App\Models\CropChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class CropChatController extends Controller
{
    private const CROP_WEBHOOK_URL = 'https://valtmar.app.n8n.cloud/webhook/crop-chat';

    public function sessions()
    {
        $sessions = CropChatSession::where('user_id', Auth::id())
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'created_at', 'updated_at']);

        return response()->json($sessions);
    }

    public function sessionMessages(int $id)
    {
        $session = CropChatSession::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $messages = CropChatMessage::where('crop_chat_session_id', $id)
            ->orderBy('id')
            ->get()
            ->map(fn($m) => [
                'id'      => $m->id,
                'role'    => $m->role,
                'text'    => $m->message,
                'preview' => $m->image_base64
                    ? "data:{$m->image_media_type};base64,{$m->image_base64}"
                    : null,
            ]);

        return response()->json(['session' => $session, 'messages' => $messages]);
    }

    public function deleteSession(int $id)
    {
        CropChatSession::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail()
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function chat(Request $request)
    {
        $user      = Auth::user();
        $sessionId = $request->input('sessionId');
        $message   = $request->input('message', '');
        $image     = $request->input('image');
        $mediaType = $request->input('mediaType', 'image/jpeg');

        // Get or create session
        if ($sessionId) {
            $session = CropChatSession::where('id', $sessionId)
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            $title = $message
                ? mb_substr($message, 0, 60)
                : 'Фото-анализ ' . now()->format('d.m H:i');

            $session = CropChatSession::create([
                'user_id' => $user->id,
                'title'   => $title,
            ]);
        }

        // Save user message
        CropChatMessage::create([
            'crop_chat_session_id' => $session->id,
            'role'                 => 'user',
            'message'              => $message,
            'image_base64'         => $image,
            'image_media_type'     => $image ? $mediaType : null,
        ]);

        // Build history from DB (all saved messages before the current one)
        $historyForN8n = CropChatMessage::where('crop_chat_session_id', $session->id)
            ->orderBy('id')
            ->get()
            ->slice(0, -1)
            ->map(fn($m) => [
                'role' => $m->role === 'ai' ? 'assistant' : 'user',
                'text' => $m->message,
            ])
            ->values()
            ->toArray();

        // Call n8n webhook
        $url     = env('N8N_CROP_WEBHOOK_URL', self::CROP_WEBHOOK_URL);
        $n8nResp = Http::timeout(60)->withoutVerifying()->post($url, [
            'message'   => $message,
            'image'     => $image,
            'mediaType' => $mediaType,
            'history'   => $historyForN8n,
            'sessionId' => (string) $session->id,
        ]);

        $n8nData = $n8nResp->json();
        $aiText  = is_string($n8nData)
            ? $n8nData
            : ($n8nData['response'] ?? $n8nData['output'] ?? $n8nData['text']
                ?? json_encode($n8nData, JSON_UNESCAPED_UNICODE));

        // Save AI response
        CropChatMessage::create([
            'crop_chat_session_id' => $session->id,
            'role'                 => 'ai',
            'message'              => $aiText,
        ]);

        $session->touch();

        return response()->json([
            'response'     => $aiText,
            'sessionId'    => $session->id,
            'sessionTitle' => $session->title,
        ]);
    }
}
