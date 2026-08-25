@extends('layouts.admin')
@section('title', 'Conjuntos')
@section('heading', 'Conjuntos')
@section('content')
<div class="bundle-management-layout">
    <section class="admin-card bundle-create-card">
        <span class="kicker">Novo conjunto</span>
        <h2>Agrupar produtos</h2>
        <p>Crie o conjunto primeiro. Na próxima tela você pesquisará e adicionará os produtos.</p>
        <form method="post" action="{{ route('admin.bundles.store') }}">
            @csrf
            <label>Nome <b class="required-mark">*</b><input name="name" value="{{ old('name') }}" placeholder="Ex.: Kit Gamer" required></label>
            <label>Custo privado total <b class="required-mark">*</b><input type="number" name="cost_price" min="0" step="0.01" value="{{ old('cost_price', 0) }}" required></label>
            <label>Status <b class="required-mark">*</b><select name="status" required>@foreach(['draft' => 'Rascunho', 'published' => 'Publicado', 'unavailable' => 'Indisponível', 'archived' => 'Arquivado'] as $value => $label)<option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>@endforeach</select></label>
            <button class="btn primary">Criar e adicionar itens</button>
        </form>
    </section>
    <section class="admin-card">
        <div class="card-head"><div><span class="kicker">Produtos agrupados</span><h2>Conjuntos cadastrados</h2></div></div>
        <form class="bundle-search" method="get"><input type="search" name="q" value="{{ $term }}" placeholder="Pesquisar conjunto pelo nome"><button class="btn ghost">Pesquisar</button>@if($term)<a href="{{ route('admin.bundles.index') }}">Limpar</a>@endif</form>
        <div class="bundle-admin-list">
            @forelse($bundles as $bundle)
                <a href="{{ route('admin.bundles.edit', $bundle) }}"><div><strong>{{ $bundle->name }}</strong><span>{{ $bundle->bundle_items_count }} {{ $bundle->bundle_items_count === 1 ? 'produto' : 'produtos' }}</span></div><span>Organizar itens →</span></a>
            @empty
                <div class="finance-empty">{{ $term ? 'Nenhum conjunto encontrado.' : 'Nenhum conjunto cadastrado.' }}</div>
            @endforelse
        </div>
        {{ $bundles->links() }}
    </section>
</div>
@endsection
