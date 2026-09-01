@extends('layouts.admin')
@section('title', 'Fiados')
@section('heading', 'Vendas fiadas')
@section('content')
@include('admin.finance.nav')

<div class="credit-summary"><div><span>Total ainda não recebido</span><strong>R$ {{ number_format($pendingTotal, 2, ',', '.') }}</strong></div><p>O fiado entra no Extrato e nas Metas somente depois da confirmação do recebimento.</p></div>

@php($formItems = old('items', $editing ? $editing->items->map(fn($item) => ['product_id' => $item->product_id, 'item_name' => $item->item_name, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price])->all() : [['product_id' => '', 'item_name' => '', 'quantity' => 1, 'unit_price' => '']]))
<div class="finance-layout">
    <section class="admin-card finance-form credit-form" data-credit-editor>
        <span class="kicker">{{ $editing ? 'Corrigir venda' : 'Nova conta a receber' }}</span><h2>{{ $editing ? 'Editar fiado' : 'Registrar fiado' }}</h2>
        <form method="post" action="{{ $editing ? route('admin.finance.credits.update', $editing) : route('admin.finance.credits.store') }}">
            @csrf @if($editing) @method('put') @endif
            <label>Cliente <b class="required-mark">*</b><input name="customer_name" value="{{ old('customer_name', $editing?->customer_name) }}" placeholder="Nome de quem comprou" required></label>
            <label>Contato<input name="customer_contact" value="{{ old('customer_contact', $editing?->customer_contact) }}" placeholder="Telefone, WhatsApp ou Instagram"></label>

            <fieldset class="credit-items-fieldset"><div class="credit-items-heading"><div><strong>Itens da venda</strong><small>Escolha um produto ou use “Item avulso” para algo fora do catálogo.</small></div><button type="button" class="btn ghost" data-add-credit-item>+ Adicionar item</button></div>
                <div class="credit-item-list" data-credit-item-list>
                    @foreach($formItems as $index => $item)
                        <div class="credit-item-row" data-credit-item-row>
                            <label>Produto ou item avulso<select name="items[{{ $index }}][product_id]" data-credit-product><option value="">Outro / item avulso</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(($item['product_id'] ?? '') == $product->id)>{{ $product->name }}{{ $product->is_bundle ? ' (Conjunto)' : '' }}</option>@endforeach</select></label>
                            <label data-credit-custom-name @if(filled($item['product_id'] ?? null)) hidden @endif>Nome do item avulso <b class="required-mark">*</b><input name="items[{{ $index }}][item_name]" value="{{ $item['item_name'] ?? '' }}" placeholder="Ex.: Serviço de personalização"></label>
                            <div class="credit-item-values"><label>Quantidade <b class="required-mark">*</b><input type="number" name="items[{{ $index }}][quantity]" min="1" value="{{ $item['quantity'] ?? 1 }}" required></label><label>Valor unitário (R$) <b class="required-mark">*</b><input type="number" name="items[{{ $index }}][unit_price]" min="0.01" step="0.01" value="{{ $item['unit_price'] ?? '' }}" required></label></div>
                            <button type="button" class="remove-credit-item" data-remove-credit-item>Remover item</button>
                        </div>
                    @endforeach
                </div>
            </fieldset>

            <label>Frete a receber (R$)<input type="number" name="shipping_income" min="0" step="0.01" value="{{ old('shipping_income', $editing?->shipping_income ?? 0) }}"></label>
            <label>Taxas (R$)<input type="number" name="fee" min="0" step="0.01" value="{{ old('fee', $editing?->fee ?? 0) }}"></label>
            <label>Canal de venda<select name="sales_channel_id"><option value="">Venda direta</option>@foreach($channels as $channel)<option value="{{ $channel->id }}" @selected(old('sales_channel_id', $editing?->sales_channel_id) == $channel->id)>{{ $channel->name }}</option>@endforeach</select></label>
            <label>Data da venda <b class="required-mark">*</b><input type="date" name="sold_at" value="{{ old('sold_at', $editing?->sold_at?->toDateString() ?? now()->toDateString()) }}" required></label>
            <label>Previsão de pagamento<input type="date" name="due_date" value="{{ old('due_date', $editing?->due_date?->toDateString()) }}"></label>
            <label class="check"><input type="checkbox" name="delivered" value="1" @checked(old('delivered', $editing?->is_delivered))> Produto já foi entregue</label>
            <label>Observações<textarea name="notes" rows="3">{{ old('notes', $editing?->notes) }}</textarea></label>
            <div class="credit-form-actions">@if($editing)<a class="btn ghost" href="{{ route('admin.finance.credits') }}">Cancelar edição</a>@endif<button class="btn primary">{{ $editing ? 'Salvar alterações' : 'Registrar fiado' }}</button></div>
        </form>
        <template data-credit-item-template><div class="credit-item-row" data-credit-item-row><label>Produto <b class="required-mark">*</b><select name="__NAME__[product_id]" data-credit-product><option value="">Outro / item avulso</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}{{ $product->is_bundle ? ' (Conjunto)' : '' }}</option>@endforeach</select></label><label data-credit-custom-name>Nome do item avulso <b class="required-mark">*</b><input name="__NAME__[item_name]" placeholder="Ex.: Serviço de personalização"></label><div class="credit-item-values"><label>Quantidade <b class="required-mark">*</b><input type="number" name="__NAME__[quantity]" min="1" value="1" required></label><label>Valor unitário (R$) <b class="required-mark">*</b><input type="number" name="__NAME__[unit_price]" min="0.01" step="0.01" required></label></div><button type="button" class="remove-credit-item" data-remove-credit-item>Remover item</button></div></template>
    </section>

    <section class="admin-card">
        <div class="card-head"><div><span class="kicker">Contas a receber</span><h2>Fiados registrados</h2></div></div>
        <div class="credit-list">
            @forelse($credits as $credit)
                <article @class(['is-received' => $credit->is_received, 'is-overdue' => !$credit->is_received && $credit->due_date?->isPast()])>
                    <div class="credit-main"><div><strong>{{ $credit->customer_name }}</strong><span>{{ $credit->items->count() }} {{ $credit->items->count() === 1 ? 'item' : 'itens' }}</span></div><strong class="credit-value">R$ {{ number_format($credit->net_total, 2, ',', '.') }}</strong></div>
                    <ul class="credit-item-summary">@foreach($credit->items as $item)<li>{{ $item->quantity }}× {{ $item->item_name }} — R$ {{ number_format($item->total, 2, ',', '.') }}</li>@endforeach</ul>
                    <div class="credit-meta"><span>Venda: {{ $credit->sold_at->format('d/m/Y') }}</span>@if($credit->due_date)<span>Previsão: {{ $credit->due_date->format('d/m/Y') }}</span>@endif @if($credit->customer_contact)<span>{{ $credit->customer_contact }}</span>@endif</div>
                    <div class="credit-badges"><span @class(['done' => $credit->is_received])>{{ $credit->is_received ? '✓ Recebido em '.$credit->received_at->format('d/m/Y') : 'Aguardando pagamento' }}</span><span @class(['done' => $credit->is_delivered])>{{ $credit->is_delivered ? '✓ Entregue' : 'Não entregue' }}</span></div>
                    <div class="credit-actions"><a href="{{ route('admin.finance.credits.edit', $credit) }}">Editar</a>@if($credit->is_received)<form method="post" action="{{ route('admin.finance.credits.received', $credit) }}">@csrf @method('patch')<button>Reabrir pagamento</button></form>@else<button type="button" data-receive-credit data-url="{{ route('admin.finance.credits.received', $credit) }}" data-customer="{{ $credit->customer_name }}" data-sale-date="{{ $credit->sold_at->toDateString() }}">Marcar como recebido</button>@endif<form method="post" action="{{ route('admin.finance.credits.delivered', $credit) }}">@csrf @method('patch')<button>{{ $credit->is_delivered ? 'Marcar não entregue' : 'Marcar como entregue' }}</button></form><form method="post" action="{{ route('admin.finance.credits.destroy', $credit) }}" data-confirm data-confirm-title="Excluir venda fiada?" data-confirm-message="O registro e sua possível entrada no extrato serão removidos." data-confirm-label="Excluir">@csrf @method('delete')<button class="text-danger">Excluir</button></form></div>
                </article>
            @empty
                <div class="finance-empty">Nenhuma venda fiada registrada.</div>
            @endforelse
        </div>
        {{ $credits->links() }}
    </section>
</div>

<div class="credit-receipt-layer" data-credit-receipt-modal hidden><section role="dialog" aria-modal="true" aria-labelledby="credit-receipt-title"><h2 id="credit-receipt-title">Confirmar recebimento</h2><p>Confirme o dia em que recebeu o pagamento de <strong data-credit-receipt-customer></strong>.</p><form method="post" data-credit-receipt-form>@csrf @method('patch')<label>Data do recebimento <b class="required-mark">*</b><input type="date" name="received_on" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required></label><div><button type="button" class="btn ghost" data-close-credit-receipt>Cancelar</button><button class="btn primary">Confirmar recebimento</button></div></form></section></div>
@endsection
