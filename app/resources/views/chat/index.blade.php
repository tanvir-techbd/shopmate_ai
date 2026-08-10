@extends('layouts.base')

@section('title', 'Chat — ShopMate AI')

@section('extra_style')
<style>
    .chat-layout { display: flex; gap: 20px; align-items: flex-start; }
    .conv-sidebar { width: 190px; flex-shrink: 0; }
    .conv-sidebar a { display: block; padding: 8px 10px; border-radius: 8px; font-size: 0.82rem; color: var(--text); text-decoration: none; margin-bottom: 4px; }
    .conv-sidebar a.active { background: #E6F2F3; color: var(--brand-dark); font-weight: 600; }
    .conv-sidebar a:hover { background: #EEF2F2; }
    .chat-main { flex: 1; min-width: 0; }
    .msg { margin-bottom: 18px; display: flex; }
    .msg.user { justify-content: flex-end; }
    .bubble { max-width: 92%; padding: 10px 14px; border-radius: 12px; line-height: 1.4; }
    .msg.user .bubble { background: var(--brand); color: #fff; border-bottom-right-radius: 2px; }
    .msg.assistant .bubble { background: var(--card); border: 1px solid #E5E7EB; border-bottom-left-radius: 2px; width: 100%; }
    .meta { font-size: 0.72rem; color: var(--muted); margin-top: 6px; }
    .products { display: grid; gap: 10px; margin-top: 10px; }
    .product { border: 1px solid #E5E7EB; border-radius: 10px; padding: 10px 12px; background: #FAFBFB; }
    .product-top { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
    .product-title { font-weight: 600; font-size: 0.95rem; }
    .reason { font-size: 0.7rem; background: var(--brand); color: #fff; border-radius: 999px; padding: 2px 8px; white-space: nowrap; }
    .offer-line { font-size: 0.85rem; margin-top: 4px; }
    .offer-line b { color: var(--brand-dark); }
    .other-offers { font-size: 0.78rem; color: var(--muted); margin-top: 6px; }
    .product-actions { margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap; }
    .product-actions form { display: inline; }
    form.composer { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid #E5E7EB; padding: 12px 16px; display: flex; gap: 8px; }
    form.composer .inner { max-width: 760px; margin: 0 auto; display: flex; gap: 8px; width: 100%; }
    form.composer input[type=text] { flex: 1; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 0.95rem; }
    .empty { color: var(--muted); text-align: center; margin-top: 60px; }
    .new-chat-btn { display: block; width: 100%; margin-bottom: 12px; text-align: center; }
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
        @foreach ($conversations as $conv)
            <a href="{{ route('chat.show', $conv) }}" class="{{ $conv->id === $conversation->id ? 'active' : '' }}">
                {{ $conv->title }}
            </a>
        @endforeach
    </aside>

    <div class="chat-main">
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
                            @php $entities = collect($message->entities ?? [])->filter(fn($v) => $v !== null && $v !== false); @endphp
                            @if ($entities->isNotEmpty())
                                &middot; Understood: {{ $entities->map(fn($v, $k) => "$k=$v")->implode(', ') }}
                            @endif
                        </div>
                    @endif

                    @if (!empty($message->results))
                        <div class="products">
                            @foreach ($message->results as $product)
                                <div class="product">
                                    <div class="product-top">
                                        <span class="product-title">{{ $product['canonical_title'] }}</span>
                                        <span class="reason">{{ $product['reason'] }}</span>
                                    </div>
                                    <div class="offer-line">
                                        <b>{{ $product['best_offer']['store_name'] }}</b>
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
                                                    {{ $offer['store_name'] }} ৳{{ number_format($offer['total_cost']) }}
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
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<form class="composer" method="POST" action="{{ route('chat.send', $conversation) }}">
    <div class="inner">
        @csrf
        <input type="text" name="message" placeholder="Ask ShopMate AI for a product..." required autofocus>
        <button type="submit" class="btn">Send</button>
    </div>
</form>
@endsection
