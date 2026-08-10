<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiService
{
    public function query(string $message): array
    {
        $response = Http::timeout(10)
            ->post(rtrim(config('services.ai.url'), '/').'/chat/query', [
                'message' => $message,
            ]);

        $response->throw();

        return $response->json();
    }
}
