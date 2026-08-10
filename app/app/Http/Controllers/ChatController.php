<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\SearchHistory;
use App\Services\AiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(): RedirectResponse
    {
        $conversation = Auth::user()->conversations()->latest('updated_at')->first()
            ?? Auth::user()->conversations()->create(['title' => 'ShopMate Chat']);

        return redirect()->route('chat.show', $conversation);
    }

    public function newConversation(): RedirectResponse
    {
        $conversation = Auth::user()->conversations()->create(['title' => 'New chat']);

        return redirect()->route('chat.show', $conversation);
    }

    public function show(Conversation $conversation): View
    {
        $this->authorizeConversation($conversation);

        $conversation->load('messages');
        $conversations = Auth::user()->conversations()->latest('updated_at')->get();

        return view('chat.index', [
            'conversation' => $conversation,
            'conversations' => $conversations,
        ]);
    }

    public function send(Request $request, Conversation $conversation, AiService $ai): RedirectResponse
    {
        $this->authorizeConversation($conversation);

        $validated = $request->validate(['message' => 'required|string|max:500']);

        $conversation->messages()->create([
            'sender' => 'user',
            'body' => $validated['message'],
        ]);

        if ($conversation->title === 'New chat' || $conversation->title === 'ShopMate Chat') {
            $conversation->update(['title' => \Illuminate\Support\Str::limit($validated['message'], 40)]);
        } else {
            $conversation->touch();
        }

        try {
            $result = $ai->query($validated['message']);
        } catch (\Throwable $e) {
            $conversation->messages()->create([
                'sender' => 'assistant',
                'body' => 'The AI service is not reachable right now. Make sure it is running (see README) and try again.',
            ]);

            return redirect()->route('chat.show', $conversation);
        }

        $conversation->messages()->create([
            'sender' => 'assistant',
            'body' => $this->summaryFor($result),
            'intent' => $result['intent'] ?? null,
            'entities' => $result['entities'] ?? [],
            'results' => $result['results'] ?? [],
        ]);

        SearchHistory::create([
            'user_id' => Auth::id(),
            'query' => $validated['message'],
            'parsed_entities' => $result['entities'] ?? [],
            'result_count' => count($result['results'] ?? []),
        ]);

        return redirect()->route('chat.show', $conversation);
    }

    private function authorizeConversation(Conversation $conversation): void
    {
        abort_if($conversation->user_id !== Auth::id(), 403);
    }

    private function summaryFor(array $result): string
    {
        $count = count($result['results'] ?? []);

        if ($count === 0) {
            return "I couldn't find a matching product in the catalogue for that yet.";
        }

        return "Found {$count} matching product(s) across our stores:";
    }
}
