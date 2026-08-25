@extends('layouts.admin')
@section('title', 'Contas a pagar')
@section('heading', 'Contas a pagar')
@section('content')
@include('admin.finance.nav')
<div class="finance-layout">
    <section class="admin-card finance-form">
        <span class="kicker">Nova saída</span>
        <h2>Registrar conta</h2>
        <form method="post" action="{{ route('admin.finance.payables.store') }}">
            @csrf
            <label>Descrição <b class="required-mark">*</b><input name="description" value="{{ old('description') }}" placeholder="Ex.: Filamento para impressão" required></label>
            <label>Categoria<input name="category" value="{{ old('category') }}" placeholder="Ex.: Matéria-prima"></label>
            <label>Valor total da compra (R$) <b class="required-mark">*</b><input type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" required></label>
            <div class="installment-fields">
                <label>Número de parcelas <b class="required-mark">*</b><input type="number" name="installments" min="1" max="120" value="{{ old('installments', 1) }}" required></label>
                <label>Primeiro vencimento <b class="required-mark">*</b><input type="date" name="due_date" value="{{ old('due_date', now()->toDateString()) }}" required></label>
            </div>
            <small class="form-help">Exemplo: informe 3 parcelas para criar 3 contas mensais. O valor total será dividido automaticamente.</small>
            <label class="check"><input type="checkbox" name="paid" value="1" @checked(old('paid'))> Todas as parcelas já foram pagas</label>
            <label>Observações<textarea name="notes" rows="3">{{ old('notes') }}</textarea></label>
            <button class="btn primary">Registrar conta</button>
        </form>
    </section>
    <section class="admin-card">
        <h2>Contas registradas</h2>
        <div class="finance-records">
            @forelse($payables as $payable)
                <article @class(['paid' => $payable->is_paid, 'overdue' => !$payable->is_paid && $payable->due_date->isPast()])>
                    <div>
                        <strong>{{ $payable->description }} @if($payable->installments_total > 1)<span class="installment-badge">{{ $payable->installment_number }}/{{ $payable->installments_total }}</span>@endif</strong>
                        <small>Vence {{ $payable->due_date->format('d/m/Y') }} · {{ $payable->category ?: 'Sem categoria' }}</small>
                    </div>
                    <b>R$ {{ number_format((float) $payable->amount, 2, ',', '.') }}</b>
                    <div class="record-actions">
                        <form method="post" action="{{ route('admin.finance.payables.toggle', $payable) }}">@csrf @method('patch')<button>{{ $payable->is_paid ? 'Reabrir' : 'Marcar paga' }}</button></form>
                        <form method="post" action="{{ route('admin.finance.payables.destroy', $payable) }}" data-confirm data-confirm-title="Excluir conta?" data-confirm-message="Somente esta parcela será removida do extrato." data-confirm-label="Excluir">@csrf @method('delete')<button class="text-danger">Excluir</button></form>
                    </div>
                </article>
            @empty
                <div class="finance-empty">Nenhuma conta registrada.</div>
            @endforelse
        </div>
        {{ $payables->links() }}
    </section>
</div>
@endsection
