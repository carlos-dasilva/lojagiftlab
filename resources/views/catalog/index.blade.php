@extends('layouts.app')
@section('title', $favoritesMode ? 'Meus favoritos — Gift Lab' : (isset($currentCategory) ? $currentCategory->name.' — Gift Lab' : 'Produtos — Gift Lab'))
@section('description', $currentCategory?->description ?: $siteSettings['seo_description'])
@section('content')
<section class="page-hero compact">
    <div class="container">
        <span class="kicker">{{ $favoritesMode ? 'Sua seleção' : 'Catálogo Gift Lab' }}</span>
        <h1>{{ $favoritesMode ? 'Meus favoritos.' : 'Encontre algo incrível.' }}</h1>
        <p>{{ $favoritesMode ? 'Os produtos que você marcou para encontrar facilmente depois.' : 'Presentes criativos, itens geek e descobertas escolhidas para surpreender.' }}</p>
    </div>
</section>
<div class="container catalog-layout">
    <aside class="filters">
        <div class="filter-title"><strong>Filtros</strong><a href="{{ route('catalog') }}">Limpar</a></div>
        <form method="get">
            @if ($favoritesMode)<input type="hidden" name="favorites" value="{{ request('favorites') }}">@endif
            <label>Buscar<input name="q" value="{{ request('q') }}" placeholder="Nome, categoria ou tag"></label>
            <label>Categoria<select name="category"><option value="">Todas</option>@foreach ($categories as $category)<option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>@endforeach</select></label>
            <label>Preço mínimo<input type="number" name="min_price" min="0" step="0.01" value="{{ request('min_price') }}"></label>
            <label>Preço máximo<input type="number" name="max_price" min="0" step="0.01" value="{{ request('max_price') }}"></label>
            <label>Condição<select name="condition"><option value="">Todas</option>@foreach(['new'=>'Novo','used'=>'Usado','like_new'=>'Seminovo','custom'=>'Personalizado'] as $value=>$label)<option value="{{ $value }}" @selected(request('condition')===$value)>{{ $label }}</option>@endforeach</select></label>
            <label>Ordenar<select name="sort"><option value="recent">Mais recentes</option><option value="price_asc" @selected(request('sort') === 'price_asc')>Menor preço</option><option value="price_desc" @selected(request('sort') === 'price_desc')>Maior preço</option><option value="views" @selected(request('sort') === 'views')>Mais visualizados</option></select></label>
            <label class="check"><input type="checkbox" name="promotion" value="1" @checked(request('promotion'))> Em promoção</label>
            <label class="check"><input type="checkbox" name="available" value="1" @checked(request('available'))> Disponíveis</label>
            <button class="btn primary full">Aplicar filtros</button>
        </form>
    </aside>
    <section class="catalog-results">
        <div class="results-head"><p><strong>{{ $products->total() }}</strong> {{ $favoritesMode ? 'favoritos' : 'produtos encontrados' }}</p><button class="mobile-filter" onclick="document.querySelector('.filters').classList.toggle('open')">Filtros</button></div>
        <div class="products-grid">
            @forelse ($products as $product)
                <x-product-card :product="$product"/>
            @empty
                <div class="empty-state"><span>♡</span><h2>{{ $favoritesMode ? 'Você ainda não escolheu nenhum favorito.' : 'Ainda não encontramos esse item no laboratório.' }}</h2><p>{{ $favoritesMode ? 'Toque no coração de um produto para guardá-lo aqui.' : 'Tente outros termos ou explore todos os produtos.' }}</p><a class="btn primary" href="{{ route('catalog') }}">Explorar produtos</a></div>
            @endforelse
        </div>
        {{ $products->links() }}
    </section>
</div>
@endsection
