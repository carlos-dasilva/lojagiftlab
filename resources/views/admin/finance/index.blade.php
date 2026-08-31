@extends('layouts.admin')
@section('title', 'Financeiro')
@section('heading', 'Financeiro')
@section('content')
@include('admin.finance.nav')
<div class="finance-stats">
    <article><span>Entradas no mês</span><strong class="money-in">R$ {{ number_format($income, 2, ',', '.') }}</strong></article>
    <article><span>Saídas no mês</span><strong class="money-out">R$ {{ number_format($expenses, 2, ',', '.') }}</strong></article>
    <article><span>Saldo do mês</span><strong>R$ {{ number_format($income - $expenses, 2, ',', '.') }}</strong></article>
    <article><span>Contas pendentes</span><strong>R$ {{ number_format($pending, 2, ',', '.') }}</strong></article>
    <article><span>Fiados a receber</span><strong>R$ {{ number_format($receivables, 2, ',', '.') }}</strong></article>
    <article><span>Itens vendidos</span><strong>{{ $salesCount }}</strong></article>
</div>
<section class="admin-card"><div class="card-head"><h2>Movimentações recentes</h2><a href="{{ route('admin.finance.statement') }}">Ver extrato →</a></div>@include('admin.finance.statement-list', ['entries' => $entries])</section>
@endsection
