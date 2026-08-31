@extends('layouts.admin')
@section('title', 'Metas de vendas')
@section('heading', 'Metas de vendas')
@section('content')
@include('admin.finance.nav')

<section class="goals-intro">
    <div><span class="kicker">Acompanhamento comercial</span><h2>Transforme o objetivo em progresso visível</h2><p>As metas consideram o valor bruto dos produtos vendidos. Frete e taxas continuam separados no Extrato.</p></div>
    <div class="goals-rule"><strong>Semana</strong><span>Domingo a sábado</span><strong>Mês</strong><span>Dia 1 ao último dia</span></div>
</section>

<div class="goal-settings">
    @foreach(['weekly' => ['Semanal', $weeklyGoal], 'monthly' => ['Mensal', $monthlyGoal]] as $period => [$label, $goal])
        <form class="admin-card" method="post" action="{{ route('admin.finance.goals.store') }}">
            @csrf
            <input type="hidden" name="period_type" value="{{ $period }}">
            <div><span class="kicker">Meta {{ strtolower($label) }}</span><h3>{{ $goal ? 'R$ '.number_format((float) $goal->target_amount, 2, ',', '.') : 'Ainda não configurada' }}</h3><p>{{ $goal ? 'Valor vigente desde '.$goal->effective_from->format('d/m/Y') : 'Defina o primeiro objetivo para começar a acompanhar.' }}</p></div>
            <label>Novo valor (R$)<input type="number" name="target_amount" min="0.01" step="0.01" placeholder="0,00" required></label>
            <button class="btn primary">Salvar meta {{ strtolower($label) }}</button>
        </form>
    @endforeach
</div>

<div class="goal-history-heading">
    <div><span class="kicker">Histórico</span><h2>Desempenho por período</h2></div>
    <nav class="goal-type-tabs" aria-label="Tipo de meta"><a href="{{ route('admin.finance.goals', ['type' => 'weekly']) }}" @class(['active' => $type === 'weekly'])>Semanais</a><a href="{{ route('admin.finance.goals', ['type' => 'monthly']) }}" @class(['active' => $type === 'monthly'])>Mensais</a></nav>
</div>

<div class="goal-history-list">
    @foreach($history as $item)
        <article class="admin-card goal-result {{ $item['status'] }}">
            <header><div><span>{{ $item['current'] ? 'Meta atual' : ($type === 'weekly' ? 'Semana' : 'Mês') }}</span><h3>{{ $type === 'weekly' ? $item['start']->format('d/m/Y').' a '.$item['end']->format('d/m/Y') : $item['start']->format('m/Y') }}</h3></div><span class="goal-status">@switch($item['status'])@case('achieved')✓ Meta atingida @break @case('in_progress')↗ Em andamento @break @case('missed')Meta não atingida @break @default Sem meta @endswitch</span></header>
            @if($item['target'] !== null)
                <div class="goal-values"><div><span>Vendido</span><strong>R$ {{ number_format($item['actual'], 2, ',', '.') }}</strong></div><div><span>Meta</span><strong>R$ {{ number_format($item['target'], 2, ',', '.') }}</strong></div><div><span>{{ $item['difference'] >= 0 ? 'Ultrapassou' : 'Faltou' }}</span><strong @class(['money-in' => $item['difference'] >= 0, 'money-out' => $item['difference'] < 0])>R$ {{ number_format(abs($item['difference']), 2, ',', '.') }}</strong></div></div>
                <div class="goal-progress" aria-label="{{ number_format($item['percentage'], 1, ',', '.') }}% da meta"><span style="width: {{ min(100, $item['percentage']) }}%"></span></div>
                <footer><strong>{{ number_format($item['percentage'], 1, ',', '.') }}%</strong> concluído @if($item['current'] && $item['difference'] < 0)<span>Para atingir: média de <strong>R$ {{ number_format($item['needed_per_day'], 2, ',', '.') }}</strong> por dia restante.</span>@endif</footer>
            @else
                <p class="goal-without-target">Nenhuma meta estava vigente neste período.</p>
            @endif
        </article>
    @endforeach
</div>
{{ $history->links() }}
@endsection
