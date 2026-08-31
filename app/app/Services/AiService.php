<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiService
{
    /**
     * $intent/$entities let a caller that already has a fresh parse for
     * this exact message (ChatController's post-fallback-ingest retry)
     * skip the AI service re-running understand() - which, with an LLM
     * backend configured, is itself a multi-second network round trip
     * that would otherwise happen twice for one chat message.
     */
    public function query(string $message, bool $includeInternational = false, ?string $intent = null, ?array $entities = null): array
    {
        $payload = [
            'message' => $message,
            'include_international' => $includeInternational,
        ];

        if ($intent !== null && $entities !== null) {
            $payload['intent'] = $intent;
            $payload['entities'] = $entities;
        }

        $response = Http::timeout(10)->post(rtrim(config('services.ai.url'), '/').'/chat/query', $payload);

        $response->throw();

        return $response->json();
    }
}
