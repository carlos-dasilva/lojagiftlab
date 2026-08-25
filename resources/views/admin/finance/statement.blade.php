@extends('layouts.admin')
@section('title', 'Extrato')
@section('heading', 'Extrato financeiro')
@section('content')
@include('admin.finance.nav')
<form class="admin-card statement-filter" method="get"><label>De<input type="date" name="from" value="{{ $from->toDateString() }}"></label><label>Até<input type="date" name="to" value="{{ $to->toDateString() }}"></label><button class="btn ghost">Filtrar</button></form>
<div class="finance-stats compact"><article><span>Entradas</span><strong class="money-in">R$ {{ number_format($income, 2, ',', '.') }}</strong></article><article><span>Saídas</span><strong class="money-out">R$ {{ number_format($expenses, 2, ',', '.') }}</strong></article><article><span>Saldo</span><strong>R$ {{ number_format($income - $expenses, 2, ',', '.') }}</strong></article></div>
<section class="admin-card"><h2>Movimentações de {{ $from->format('d/m/Y') }} a {{ $to->format('d/m/Y') }}</h2>@include('admin.finance.statement-list')</section>
@endsection
