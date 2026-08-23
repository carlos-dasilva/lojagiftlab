@extends('layouts.app')

@section('title', 'Produto não encontrado — Gift Lab')
@section('description', 'Este produto não está mais disponível neste endereço. Explore o catálogo da Gift Lab para encontrar outras ideias incríveis.')

@section('content')
    <section class="product-not-found" aria-labelledby="not-found-title">
        <div class="container product-not-found__card">
            <div class="product-not-found__content">
                <span class="kicker"><i></i> Produto não encontrado</span>
                <p class="product-not-found__code" aria-hidden="true">Erro 404</p>
                <h1 id="not-found-title">Este presente saiu da bancada.</h1>
                <p>O produto pode ter sido removido, estar temporariamente indisponível ou ter ganhado um novo endereço. Que tal procurar outra criação?</p>

                <form class="product-not-found__search" action="{{ route('catalog') }}" method="GET">
                    <label class="sr-only" for="missing-product-search">Pesquisar no catálogo</label>
                    <input id="missing-product-search" name="q" type="search" placeholder="O que você está procurando?" autocomplete="off">
                    <button type="submit" aria-label="Pesquisar">⌕</button>
                </form>

                <div class="product-not-found__actions">
                    <a class="btn primary" href="{{ route('catalog') }}">Explorar produtos <span aria-hidden="true">→</span></a>
                    <a class="btn ghost" href="{{ route('home') }}">Voltar ao início</a>
                </div>
            </div>

            <div class="product-not-found__art" aria-hidden="true">
                <span class="product-not-found__number">404</span>
                <div class="product-not-found__gift">
                    <span class="product-not-found__bow">◇</span>
                    <img src="{{ asset('favicon.svg') }}" alt="">
                </div>
                <span class="product-not-found__spark product-not-found__spark--one">✦</span>
                <span class="product-not-found__spark product-not-found__spark--two">✦</span>
                <span class="product-not-found__spark product-not-found__spark--three">●</span>
            </div>
        </div>
    </section>
@endsection
