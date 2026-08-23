<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Gift Lab</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="theme-color" content="#0B163D">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
    <aside class="admin-sidebar" data-admin-sidebar>
        <div class="admin-brand-row">
            <a href="{{ route('admin.dashboard') }}"><img src="{{ asset('images/gift-lab-logo.png') }}" alt="Gift Lab"><span>Administração</span></a>
            <button class="admin-menu-button" type="button" data-admin-menu-toggle aria-expanded="false" aria-controls="admin-navigation" aria-label="Abrir menu administrativo"><span></span><span></span><span></span></button>
        </div>
        <div class="admin-mobile-menu" id="admin-navigation" data-admin-mobile-menu>
        <nav>
            <a href="{{ route('admin.dashboard') }}">⌂ Dashboard</a>
            <a href="{{ route('admin.products.index') }}">◈ Produtos</a>
            <a href="{{ route('admin.categories.index') }}">⌁ Categorias</a>
            <a href="{{ route('admin.settings.edit') }}">⚙ Configurações</a>
            <a href="{{ route('home') }}">↗ Ver site</a>
        </nav>
        <form method="post" action="{{ route('admin.logout') }}">@csrf<button>← Sair</button></form>
        </div>
    </aside>
    <main class="admin-main">
        <header><div><small>Painel Gift Lab</small><h1>@yield('heading')</h1></div><span>{{ auth()->user()->name }}</span></header>
        @if (session('success'))
            <div data-modal-feedback data-type="success" data-title="Tudo certo!" data-message="{{ session('success') }}" data-items="[]" hidden></div>
        @endif
        @if ($errors->any())
            <div data-modal-feedback data-type="error" data-title="Não foi possível salvar" data-message="Revise os campos destacados e tente novamente." data-items='@json($errors->all())' hidden></div>
        @endif
        @yield('content')
    </main>
</body>
</html>
