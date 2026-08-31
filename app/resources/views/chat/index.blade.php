@extends('layouts.base')

@section('title', 'Chat — ShopMate AI')

@section('extra_style')
<style>
    .chat-layout { display: flex; gap: 24px; align-items: flex-start; }
    .conv-sidebar { width: 208px; flex-shrink: 0; }
    .conv-search { display: flex; gap: 6px; margin-bottom: 12px; }
    .conv-search input { flex: 1; min-width: 0; padding: 7px 10px; border: 1px solid var(--border-strong); border-radius: var(--radius-sm); font-size: 0.8rem; font-family: inherit; }
    .conv-search input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-light); }
    .conv-search-clear { display: block; font-size: 0.75rem; margin: -8px 0 12px; }
    .conv-row { display: flex; align-items: center; gap: 4px; margin-bottom: 3px; }
    .conv-row a { flex: 1; min-width: 0; display: block; padding: 8px 10px; border-radius: var(--radius-sm); font-size: 0.83rem; color: var(--text); text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .conv-row a.active { background: var(--brand-light); color: var(--brand-dark); font-weight: 600; }
    .conv-row a:hover { background: var(--surface-sunken); }
    .conv-row form { flex-shrink: 0; }
    .conv-delete { background: none; border: none; color: var(--text-faint); font-size: 0.9rem; line-height: 1; cursor: pointer; padding: 6px; border-radius: 6px; }
    .conv-delete:hover { color: var(--danger); background: var(--danger-bg); }
    .conv-empty { font-size: 0.8rem; color: var(--text-muted); padding: 8px 10px; }
    .chat-main { flex: 1; min-width: 0; }
    .msg { margin-bottom: 18px; display: flex; }
    .msg.user { justify-content: flex-end; }
    .bubble { max-width: 92%; padding: 11px 15px; border-radius: 14px; line-height: 1.45; }
    .msg.user .bubble { background: var(--brand); color: #fff; border-bottom-right-radius: 3px; }
    .msg.assistant .bubble { background: var(--surface); border: 1px solid var(--border); border-bottom-left-radius: 3px; width: 100%; box-shadow: var(--shadow-sm); }
    .meta { font-size: 0.72rem; color: var(--text-muted); margin-top: 7px; }
    .products { display: grid; gap: 10px; margin-top: 10px; }
    .product { border: 1px solid var(--border); border-radius: var(--radius); padding: 12px 14px; background: var(--surface-sunken); display: flex; gap: 12px; }
    .product-photo { flex-shrink: 0; width: 72px; height: 72px; border-radius: var(--radius-sm); overflow: hidden; background: #fff; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; }
    .product-photo img { width: 100%; height: 100%; object-fit: contain; }
    .photo-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: var(--brand-tint); }
    .photo-placeholder svg { width: 26px; height: 26px; color: var(--brand); opacity: 0.55; }
    .product-body { flex: 1; min-width: 0; }
    .product-top { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
    .product-title { font-weight: 700; font-size: 0.95rem; }
    .reason { font-size: 0.68rem; font-weight: 600; background: var(--brand); color: #fff; border-radius: 999px; padding: 3px 9px; white-space: nowrap; }
    .offer-line { font-size: 0.85rem; margin-top: 5px; }
    .offer-line b { color: var(--brand-dark); }
    .abroad-badge { font-size: 0.68rem; background: #EEF2FF; color: #4338CA; border-radius: 999px; padding: 1px 7px; margin-left: 4px; white-space: nowrap; }
    .other-offers { font-size: 0.78rem; color: var(--text-muted); margin-top: 6px; }
    .product-actions { margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap; }
    .product-actions form { display: inline; }
    form.composer { position: fixed; bottom: 0; left: var(--sidebar-w); right: 0; background: var(--surface); border-top: 1px solid var(--border); padding: 14px 16px; display: flex; gap: 8px; box-shadow: 0 -4px 16px rgba(20, 36, 32, 0.05); }
    form.composer .inner { max-width: 1160px; margin: 0 auto; display: flex; gap: 10px; width: 100%; }
    form.composer input[type=text] { flex: 1; padding: 11px 14px; border: 1px solid var(--border-strong); border-radius: var(--radius-sm); font-size: 0.95rem; font-family: inherit; }
    form.composer input[type=text]:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-light); }
    .empty { color: var(--muted); text-align: center; margin-top: 60px; }
    .new-chat-btn { display: block; width: 100%; margin-bottom: 12px; text-align: center; }
    .no-results { margin-top: 10px; border: 1px dashed var(--border-strong); border-radius: var(--radius); padding: 12px 14px; background: var(--surface-sunken); }
    .no-results p { margin: 0 0 8px; }
    .no-results form { display: flex; gap: 8px; align-items: center; }
    .bubble.thinking { display: flex; align-items: center; gap: 8px; color: var(--muted); }
    .thinking-dots { display: inline-flex; gap: 3px; }
    .thinking-dots span { width: 6px; height: 6px; border-radius: 50%; background: var(--brand); animation: thinking-bounce 1.1s infinite ease-in-out; }
    .thinking-dots span:nth-child(2) { animation-delay: 0.15s; }
    .thinking-dots span:nth-child(3) { animation-delay: 0.3s; }
    @keyframes thinking-bounce { 0%, 60%, 100% { transform: translateY(0); opacity: 0.5; } 30% { transform: translateY(-4px); opacity: 1; } }
    form.composer button[disabled] { opacity: 0.7; cursor: default; }
</style>
@endsection

@section('main_class', 'wide')

@section('content')
<div class="chat-layout">
    <aside class="conv-sidebar">
        <form method="POST" action="{{ route('chat.new') }}">
            @csrf
            <button type="submit" class="btn small new-chat-btn">+ New chat</button>
        </form>

        <form method="GET" action="{{ route('chat.show', $conversation) }}" class="conv-search">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search chats...">
            <button type="submit" class="btn small secondary">Go</button>
        </form>
        @if ($search !== '')
            <a href="{{ route('chat.show', $conversation) }}" class="conv-search-clear">Clear search</a>
        @endif

        @forelse ($conversations as $conv)
            <div class="conv-row">
                <a href="{{ route('chat.show', $conv) }}" class="{{ $conv->id === $conversation->id ? 'active' : '' }}">
                    {{ $conv->title }}
                </a>
                <form method="POST" action="{{ route('chat.destroy', $conv) }}" onsubmit="return confirm('Delete this chat? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="conv-delete" title="Delete chat" aria-label="Delete chat">&times;</button>
                </form>
            </div>
        @empty
            <p class="conv-empty">No chats match "{{ $search }}".</p>
        @endforelse
    </aside>

    <div class="chat-main" id="chat-main">
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($conversation->messages->isEmpty())
            <p class="empty">Try: "black backpack under 3000 taka with a laptop compartment"</p>
        @endif

        @foreach ($conversation->messages as $message)
            <div class="msg {{ $message->sender }}">
                <div class="bubble">
                    {{ $message->body }}

                    @if ($message->sender === 'assistant' && $message->intent)
                        <div class="meta">
                            Intent: {{ $message->intent }}
                            @php $entities = collect($message->entities ?? [])->except('_query')->filter(fn($v) => $v !== null && $v !== false); @endphp
                            @if ($entities->isNotEmpty())
                                &middot; Understood: {{ $entities->map(fn($v, $k) => "$k=$v")->implode(', ') }}
                            @endif
                        </div>
                    @endif

                    @if (!empty($message->results))
                        <div class="products">
                            @foreach ($message->results as $product)
                                <div class="product">
                                    @php
                                        // Older messages' cached results JSON predates these fields
                                        // (image_url, store_origin) - fall back gracefully instead of
                                        // throwing on a key that simply didn't exist when this
                                        // particular message was saved.
                                        $bestImageUrl = $product['best_offer']['image_url'] ?? null;
                                        $bestOrigin = $product['best_offer']['store_origin'] ?? 'domestic';
                                    @endphp
                                    <div class="product-photo">
                                        @if ($bestImageUrl)
                                            <img src="{{ $bestImageUrl }}" alt="{{ $product['canonical_title'] }}" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="photo-placeholder" style="display:none;">@include('chat._photo-icon')</div>
                                        @else
                                            <div class="photo-placeholder">@include('chat._photo-icon')</div>
                                        @endif
                                    </div>
                                    <div class="product-body">
                                    <div class="product-top">
                                        <span class="product-title">{{ $product['canonical_title'] }}</span>
                                        <span class="reason">{{ $product['reason'] }}</span>
                                    </div>
                                    <div class="offer-line">
                                        <b>{{ $product['best_offer']['store_name'] }}</b>
                                        @if ($bestOrigin === 'international')
                                            <span class="abroad-badge" title="Ships from abroad">🌍 Ships from abroad</span>
                                        @endif
                                        &mdash; ৳{{ number_format($product['best_offer']['price']) }}
                                        @if ($product['best_offer']['delivery_charge'] > 0)
                                            + ৳{{ number_format($product['best_offer']['delivery_charge']) }} delivery
                                        @else
                                            (free delivery)
                                        @endif
                                        = <b>৳{{ number_format($product['best_offer']['total_cost']) }}</b>
                                        @if ($product['best_offer']['rating'])
                                            &middot; {{ $product['best_offer']['rating'] }}★ ({{ $product['best_offer']['review_count'] }})
                                        @endif
                                    </div>
                                    @if (count($product['all_offers']) > 1)
                                        <div class="other-offers">
                                            Also at:
                                            @foreach ($product['all_offers'] as $offer)
                                                @if ($offer['store_name'] !== $product['best_offer']['store_name'])
                                                    {{ $offer['store_name'] }}{{ ($offer['store_origin'] ?? 'domestic') === 'international' ? ' 🌍' : '' }} ৳{{ number_format($offer['total_cost']) }}
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="product-actions">
                                        <form method="POST" action="{{ route('shopping-list.items.store') }}">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product['id'] }}">
                                            <button type="submit" class="btn small secondary">+ Add to shopping list</button>
                                        </form>
                                        <form method="POST" action="{{ route('alerts.store') }}">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product['id'] }}">
                                            @if ($product['best_offer']['in_stock'])
                                                <input type="hidden" name="type" value="price_drop">
                                                <button type="submit" class="btn small secondary">Notify me on price drop</button>
                                            @else
                                                <input type="hidden" name="type" value="restock">
                                                <button type="submit" class="btn small secondary">Notify when back in stock</button>
                                            @endif
                                        </form>
                                        @if ($product['best_offer']['in_stock'])
                                            <a href="{{ route('orders.confirm', ['product_price_id' => $product['best_offer']['id']]) }}" class="btn small">Buy from {{ $product['best_offer']['store_name'] }}</a>
                                        @endif
                                    </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif ($message->sender === 'assistant' && in_array($message->intent, ['SEARCH_PRODUCT', 'RECOMMEND_PRODUCT', 'COMPARE_PRODUCT', 'BUY_PRODUCT']))
                        <div class="no-results">
                            <p class="muted">We don't have this in our catalogue right now.</p>
                            <form method="POST" action="{{ route('pre-orders.store') }}">
                                @csrf
                                <input type="hidden" name="query" value="{{ $message->entities['_query'] ?? '' }}">
                                <input type="hidden" name="category" value="{{ $message->entities['category'] ?? '' }}">
                                <input type="hidden" name="brand" value="{{ $message->entities['brand'] ?? '' }}">
                                <button type="submit" class="btn small secondary">Notify me if this becomes available</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<form class="composer" id="composer" method="POST" action="{{ route('chat.send', $conversation) }}">
    <div class="inner">
        @csrf
        <input type="text" name="message" placeholder="Ask ShopMate AI for a product..." required autofocus>
        <button type="submit" class="btn">Send</button>
    </div>
</form>

@section('scripts')
<script>
    // Land on the newest message instead of the top of a long
    // conversation - this page always renders top-to-bottom in full, with
    // no pagination, so the reader otherwise has to scroll down manually
    // every single time the page loads (including right after sending a
    // message, since this is a plain POST with a full page reload).
    var lastMsg = document.querySelector('.chat-main .msg:last-child');
    if (lastMsg) {
        lastMsg.scrollIntoView({block: 'end'});
    }

    // The AI's reply can take a few seconds (it's an LLM call, not an
    // instant lookup) - without this, the page just sits still on submit
    // with no indication anything is happening, which reads as broken.
    // This form is a plain POST (full page reload on completion), so
    // there is no follow-up JS needed to ever hide this state.
    document.getElementById('composer').addEventListener('submit', function () {
        var button = this.querySelector('button[type=submit]');
        // Deliberately NOT disabling the message <input> here: a disabled
        // form field is excluded from the browser's submitted data, so
        // doing that in this same handler would submit an empty message.
        // readOnly keeps it visually inert without that risk.
        this.querySelector('input[name=message]').readOnly = true;
        button.disabled = true;
        button.textContent = 'Sending...';

        var chatMain = document.getElementById('chat-main');
        var thinking = document.createElement('div');
        thinking.className = 'msg assistant';
        thinking.innerHTML = '<div class="bubble thinking">ShopMate AI is thinking'
            + '<span class="thinking-dots"><span></span><span></span><span></span></span></div>';
        chatMain.appendChild(thinking);
        thinking.scrollIntoView({behavior: 'smooth', block: 'end'});
    });
</script>
@endsection
@endsection
