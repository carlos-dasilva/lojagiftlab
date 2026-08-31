@extends('layouts.admin')
@section('title', 'Fiados')
@section('heading', 'Vendas fiadas')
@section('content')
@include('admin.finance.nav')

<div class="credit-summary"><div><span>Total ainda não recebido</span><strong>R$ {{ number_format($pendingTotal, 2, ',', '.') }}</strong></div><p>Vendas fiadas só entram no Extrato depois que você confirmar o recebimento.</p></div>

<div class="finance-layout">
    <section class="admin-card finance-form credit-form">
        <span class="kicker">Nova conta a receber</span><h2>Registrar fiado</h2>
        <form method="post" action="{{ route('admin.finance.credits.store') }}">
            @csrf
            <label>Cliente <b class="required-mark">*</b><input name="customer_name" value="{{ old('customer_name') }}" placeholder="Nome de quem comprou" required></label>
            <label>Contato<input name="customer_contact" value="{{ old('customer_contact') }}" placeholder="Telefone, WhatsApp ou Instagram"></label>
            <label>Produto <b class="required-mark">*</b><select name="product_id" required><option value="">Selecione</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }}{{ $product->is_bundle ? ' (Conjunto)' : '' }}</option>@endforeach</select></label>
            <div class="credit-numbers"><label>Quantidade <b class="required-mark">*</b><input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" required></label><label>Valor unitário (R$) <b class="required-mark">*</b><input type="number" name="unit_price" min="0.01" step="0.01" value="{{ old('unit_price') }}" required></label></div>
            <div class="credit-numbers"><label>Frete a receber (R$)<input type="number" name="shipping_income" min="0" step="0.01" value="{{ old('shipping_income', 0) }}"></label><label>Taxas (R$)<input type="number" name="fee" min="0" step="0.01" value="{{ old('fee', 0) }}"></label></div>
            <label>Canal de venda<select name="sales_channel_id"><option value="">Venda direta</option>@foreach($channels as $channel)<option value="{{ $channel->id }}" @selected(old('sales_channel_id') == $channel->id)>{{ $channel->name }}</option>@endforeach</select></label>
            <div class="credit-dates"><label>Data da venda <b class="required-mark">*</b><input type="date" name="sold_at" value="{{ old('sold_at', now()->toDateString()) }}" required></label><label>Previsão de pagamento<input type="date" name="due_date" value="{{ old('due_date') }}"></label></div>
            <label class="check"><input type="checkbox" name="delivered" value="1" @checked(old('delivered'))> Produto já foi entregue</label>
            <label>Observações<textarea name="notes" rows="3">{{ old('notes') }}</textarea></label>
            <button class="btn primary">Registrar fiado</button>
        </form>
    </section>

    <section class="admin-card">
        <div class="card-head"><div><span class="kicker">Contas a receber</span><h2>Fiados registrados</h2></div></div>
        <div class="credit-list">
            @forelse($credits as $credit)
                <article @class(['is-received' => $credit->is_received, 'is-overdue' => !$credit->is_received && $credit->due_date?->isPast()])>
                    <div class="credit-main"><div><strong>{{ $credit->customer_name }}</strong><span>{{ $credit->quantity }}× {{ $credit->product_name }}</span></div><strong class="credit-value">R$ {{ number_format($credit->net_total, 2, ',', '.') }}</strong></div>
                    <div class="credit-meta"><span>Venda: {{ $credit->sold_at->format('d/m/Y') }}</span>@if($credit->due_date)<span>Previsão: {{ $credit->due_date->format('d/m/Y') }}</span>@endif @if($credit->customer_contact)<span>{{ $credit->customer_contact }}</span>@endif</div>
                    <div class="credit-badges"><span @class(['done' => $credit->is_received])>{{ $credit->is_received ? '✓ Recebido em '.$credit->received_at->format('d/m/Y') : 'Aguardando pagamento' }}</span><span @class(['done' => $credit->is_delivered])>{{ $credit->is_delivered ? '✓ Entregue' : 'Não entregue' }}</span></div>
                    <div class="credit-actions"><form method="post" action="{{ route('admin.finance.credits.received', $credit) }}">@csrf @method('patch')<button>{{ $credit->is_received ? 'Reabrir pagamento' : 'Marcar como recebido' }}</button></form><form method="post" action="{{ route('admin.finance.credits.delivered', $credit) }}">@csrf @method('patch')<button>{{ $credit->is_delivered ? 'Marcar não entregue' : 'Marcar como entregue' }}</button></form><form method="post" action="{{ route('admin.finance.credits.destroy', $credit) }}" data-confirm data-confirm-title="Excluir venda fiada?" data-confirm-message="O registro e sua possível entrada no extrato serão removidos." data-confirm-label="Excluir">@csrf @method('delete')<button class="text-danger">Excluir</button></form></div>
                </article>
            @empty
                <div class="finance-empty">Nenhuma venda fiada registrada.</div>
            @endforelse
        </div>
        {{ $credits->links() }}
    </section>
</div>
@endsection
