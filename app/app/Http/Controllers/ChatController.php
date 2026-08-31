<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\SearchHistory;
use App\Services\AiService;
use App\Services\LiveSearchFallbackService;
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

    public function show(Request $request, Conversation $conversation): View
    {
        $this->authorizeConversation($conversation);

        $conversation->load('messages');

        $search = trim((string) $request->query('q', ''));
        $conversationsQuery = Auth::user()->conversations()->latest('updated_at');
        if ($search !== '') {
            $conversationsQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhereHas('messages', fn ($m) => $m->where('body', 'like', "%{$search}%"));
            });
        }

        return view('chat.index', [
            'conversation' => $conversation,
            'conversations' => $conversationsQuery->get(),
            'search' => $search,
        ]);
    }

    public function destroy(Conversation $conversation): RedirectResponse
    {
        $this->authorizeConversation($conversation);

        // Deleting a chat other than the one currently open (e.g. from the
        // sidebar while viewing a different conversation) should keep you
        // where you were rather than bouncing to whatever chat.index()
        // picks next. The previous page may have had a "?q=..." search
        // filter on it, which is still the deleted conversation's own page
        // and must not be redirected back to (its id is gone - that would
        // just 404) - matched by "equals, or equals plus a query string",
        // not a raw prefix check, so conversation 5's URL doesn't also
        // match conversation 50's.
        $deletedUrl = route('chat.show', $conversation);
        $conversation->delete();

        $previous = url()->previous();
        $wasViewingDeleted = $previous === $deletedUrl || str_starts_with($previous, $deletedUrl.'?');

        return $wasViewingDeleted
            ? redirect()->route('chat.index')
            : redirect($previous);
    }

    public function send(Request $request, Conversation $conversation, AiService $ai, LiveSearchFallbackService $fallback): RedirectResponse
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

        $includeInternational = Auth::user()->include_international_stores;

        try {
            $result = $ai->query($validated['message'], $includeInternational);
        } catch (\Throwable $e) {
            $conversation->messages()->create([
                'sender' => 'assistant',
                'body' => 'The AI service is not reachable right now. Make sure it is running (see README) and try again.',
            ]);

            return redirect()->route('chat.show', $conversation);
        }

        // The pre-imported catalogue only ever covers SEED_QUERIES' fixed
        // list - a genuinely empty local result doesn't necessarily mean
        // the store doesn't carry it, just that nothing was pre-imported
        // for it. One live, on-demand Othoba search for the exact query
        // (see LiveSearchFallbackService) catches that before settling for
        // "not available" - re-querying the AI service afterward picks up
        // whatever it just ingested. Never blocks longer than that: any
        // failure in the fallback itself just leaves $result as it was.
        if (empty($result['results']) && $fallback->tryFor($validated['message'])) {
            try {
                // Reuse the parse from the call above rather than paying
                // for a second LLM round trip for the same message text -
                // only the catalog changed, not the query.
                $result = $ai->query($validated['message'], $includeInternational, $result['intent'] ?? null, $result['entities'] ?? null);
            } catch (\Throwable $e) {
                // Fallback ingestion may still have helped a future search;
                // the AI service being unreachable now just means this
                // particular reply falls through to the normal "not
                // available" handling below with the original $result.
            }
        }

        // '_query' rides along in the same JSON column as the parsed
        // entities purely so the "not available" panel in chat/index can
        // pre-fill a pre-order request with the exact text the user typed,
        // without a schema change or fragile message-order guessing in the
        // view. It is excluded wherever entities are displayed to the user.
        $entities = $result['entities'] ?? [];
        $entities['_query'] = $validated['message'];

        $conversation->messages()->create([
            'sender' => 'assistant',
            'body' => $this->summaryFor($result),
            'intent' => $result['intent'] ?? null,
            'entities' => $entities,
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
            return "That's not available in our catalogue at the moment.";
        }

        return "Found {$count} matching product(s) across our stores:";
    }
}
