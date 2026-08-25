@extends('layouts.admin')
@section('title', 'Itens de '.$bundle->name)
@section('heading', 'Organizar conjunto')
@section('content')
<div class="bundle-editor-heading">
    <div><span class="kicker">Conjunto</span><h2>{{ $bundle->name }}</h2><p>Pesquise pelo nome e adicione somente os produtos que fazem parte deste conjunto.</p></div>
    <a class="btn ghost" href="{{ route('admin.products.edit', $bundle) }}">Editar detalhes, imagens e preços</a>
</div>

<section class="admin-card bundle-product-search">
    <form method="get"><label for="bundle-product-query">Encontrar produto</label><div><input id="bundle-product-query" type="search" name="q" value="{{ $term }}" placeholder="Digite parte do nome do produto" autofocus><button class="btn primary">Pesquisar</button></div></form>
    @if($term)<p class="search-summary">Resultados para “{{ $term }}” — no máximo 20 produtos por pesquisa.</p>@endif
</section>

<form method="post" action="{{ route('admin.bundles.update', $bundle) }}">
    @csrf @method('put')
    <section class="admin-card">
        <div class="card-head"><div><span class="kicker">Composição</span><h2>Produtos do conjunto</h2><p>Desmarque um item para removê-lo ou ajuste sua quantidade.</p></div></div>
        <div class="bundle-selection-list">
            @foreach($selected as $item)
                <article class="is-selected"><label class="check"><input type="checkbox" name="items[{{ $item->id }}][selected]" value="1" checked><span>{{ $item->name }}</span></label><label>Quantidade<input type="number" name="items[{{ $item->id }}][quantity]" min="1" max="9999" value="{{ old('items.'.$item->id.'.quantity', $item->pivot->quantity) }}"></label></article>
            @endforeach
            @foreach($results as $item)
                <article><label class="check"><input type="checkbox" name="items[{{ $item->id }}][selected]" value="1"><span>{{ $item->name }}</span></label><label>Quantidade<input type="number" name="items[{{ $item->id }}][quantity]" min="1" max="9999" value="1"></label></article>
            @endforeach
            @if($selected->isEmpty() && $results->isEmpty())<div class="finance-empty">{{ $term ? 'Nenhum produto encontrado com esse nome.' : 'Pesquise um produto acima para começar.' }}</div>@endif
        </div>
        <div class="bundle-editor-actions"><a class="btn ghost" href="{{ route('admin.bundles.index') }}">Voltar</a><button class="btn primary">Salvar composição</button></div>
    </section>
</form>
@endsection
