<nav class="finance-nav" aria-label="Navegação financeira">
    <a href="{{ route('admin.finance.index') }}" @class(['active' => request()->routeIs('admin.finance.index')])>Visão geral</a>
    <a href="{{ route('admin.finance.sales') }}" @class(['active' => request()->routeIs('admin.finance.sales*')])>Vendas</a>
    <a href="{{ route('admin.finance.payables') }}" @class(['active' => request()->routeIs('admin.finance.payables*')])>Contas a pagar</a>
    <a href="{{ route('admin.finance.statement') }}" @class(['active' => request()->routeIs('admin.finance.statement')])>Extrato</a>
    <a href="{{ route('admin.finance.goals') }}" @class(['active' => request()->routeIs('admin.finance.goals*')])>Metas</a>
</nav>
