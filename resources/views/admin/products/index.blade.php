@extends('layouts.admin')
@section('title', 'Produtos')
@section('heading', 'Produtos')

@section('content')
<div class="admin-actions product-actions">
    <div><h2>Seu catálogo</h2><p>Selecione o nome de um produto para editar seus dados.</p></div>
    <a class="btn admin-create-button" href="{{ route('admin.products.create') }}"><span aria-hidden="true">+</span> Novo produto</a>
</div>

<form class="product-admin-search" method="get" action="{{ route('admin.products.index') }}" role="search">
    <label for="product-search">Pesquisar produtos</label>
    <div><input id="product-search" type="search" name="q" value="{{ $term }}" placeholder="Nome, descrição ou categoria..."><button class="btn ghost" type="submit">Pesquisar</button>@if ($term !== '')<a href="{{ route('admin.products.index') }}">Limpar</a>@endif</div>
</form>

<section class="product-admin-list" aria-label="Produtos cadastrados">
    @forelse ($products as $product)
        <article class="product-admin-item">
            <div class="product-admin-thumb">
                @if ($product->primaryImage)<img src="{{ Storage::url($product->primaryImage->path) }}" alt="">@else<span aria-hidden="true">✦</span>@endif
            </div>
            <div class="product-admin-main">
                <a class="product-admin-name" href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a>
                <div class="table-categories">@forelse ($product->categories as $category)<span>#{{ $category->name }}</span>@empty<span>Sem categoria</span>@endforelse</div>
            </div>
            <div class="product-admin-meta"><small>Status</small><span class="status {{ $product->status->value }}">{{ $product->status->value }}</span></div>
            <div class="product-admin-meta product-admin-price"><small>Menor valor</small><strong>{{ $product->starting_price ? 'R$ '.number_format($product->starting_price, 2, ',', '.') : 'Sem preço' }}</strong></div>
            <a class="product-admin-arrow" href="{{ route('admin.products.edit', $product) }}" aria-label="Editar {{ $product->name }}">→</a>
        </article>
    @empty
        <div class="admin-card empty-state"><span>✦</span><h2>{{ $term !== '' ? 'Nenhum produto encontrado' : 'Nenhum produto cadastrado' }}</h2><p>{{ $term !== '' ? 'Tente pesquisar com outro nome ou categoria.' : 'Crie o primeiro item do seu catálogo.' }}</p>@if ($term !== '')<a class="btn ghost" href="{{ route('admin.products.index') }}">Limpar pesquisa</a>@endif</div>
    @endforelse
</section>

<div class="product-admin-pagination">{{ $products->links() }}</div>
@endsection
