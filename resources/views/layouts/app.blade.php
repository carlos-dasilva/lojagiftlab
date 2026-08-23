<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Gift Lab — presentes e criatividade')</title>
    <meta name="description" content="@yield('description', 'Presentes, itens geek e coisas incríveis ganhando forma na Gift Lab.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'Gift Lab')">
    <meta property="og:description" content="@yield('description', 'Criatividade e presentes incríveis.')">
    <meta property="og:image" content="@yield('og_image', asset('images/gift-lab-logo.png'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="theme-color" content="#0B163D">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
    <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
    <header class="site-header" data-mobile-header>
        <div class="container nav">
            <a href="{{ route('home') }}" class="brand" aria-label="Gift Lab - início"><img src="{{ asset('images/gift-lab-logo.png') }}" alt="Gift Lab"></a>
            <nav class="desktop-nav" aria-label="Principal">
                <a href="{{ route('catalog') }}">Produtos</a>
                <a href="{{ route('pages.show', 'quem-somos') }}">Quem somos</a>
                <a href="{{ route('faq') }}">FAQ</a>
                <a href="{{ route('contact') }}">Contato</a>
                @if (auth()->user()?->is_admin)
                    <a href="{{ route('admin.dashboard') }}" class="admin-nav-link">Admin</a>
                @endif
            </nav>
            <form class="header-search" action="{{ route('catalog') }}">
                <label class="sr-only" for="header-q">Pesquisar produtos</label>
                <input id="header-q" name="q" placeholder="O que você procura?" value="{{ request('q') }}">
                <button aria-label="Pesquisar">⌕</button>
            </form>
            <a class="favorites-link" data-favorites-link href="{{ route('catalog', ['favorites' => '']) }}" title="Ver meus favoritos">♡ <span data-favorites-count>0</span></a>
            <button class="menu-button" type="button" data-menu-toggle aria-expanded="false" aria-controls="mobile-navigation" aria-label="Abrir menu">
                <span class="menu-lines" aria-hidden="true"><i></i><i></i><i></i></span>
            </button>
        </div>
        <div class="mobile-nav" id="mobile-navigation" data-mobile-nav hidden>
            <a href="{{ route('catalog') }}">Produtos</a>
            <a href="{{ route('pages.show', 'quem-somos') }}">Quem somos</a>
            <a href="{{ route('faq') }}">FAQ</a>
            <a href="{{ route('contact') }}">Contato</a>
            <a data-favorites-link href="{{ route('catalog', ['favorites' => '']) }}">Favoritos (<span data-favorites-count>0</span>)</a>
            @if (auth()->user()?->is_admin)
                <a href="{{ route('admin.dashboard') }}" class="admin-nav-link">Admin</a>
            @endif
        </div>
    </header>

    @if (session('success')) <div class="toast success" role="status">{{ session('success') }}</div> @endif
    <main id="conteudo">@yield('content')</main>

    <footer>
        <div class="container footer-grid">
            <div><img class="footer-logo" src="{{ asset('images/gift-lab-logo.png') }}" alt="Gift Lab"><p>Presentes, criatividade e coisas incríveis ganhando forma.</p></div>
            <div><h2>Explore</h2><a href="{{ route('catalog') }}">Produtos</a><a href="{{ route('pages.show', 'quem-somos') }}">Quem somos</a><a href="{{ route('faq') }}">Perguntas frequentes</a></div>
            <div><h2>Informações</h2><a href="{{ route('pages.show', 'politica-de-privacidade') }}">Privacidade</a><a href="{{ route('pages.show', 'politica-de-cookies') }}">Cookies</a><a href="{{ route('pages.show', 'termos-de-uso') }}">Termos de uso</a></div>
            <div><h2>Fale com a gente</h2><a href="mailto:{{ $siteSettings['email'] }}">{{ $siteSettings['email'] }}</a>@if ($siteSettings['instagram']) <a href="{{ $siteSettings['instagram'] }}" target="_blank" rel="noopener noreferrer">Instagram</a> @endif</div>
        </div>
        <div class="container footer-bottom">© {{ date('Y') }} Gift Lab · Feito com criatividade.</div>
    </footer>

    <div class="cookie-banner" x-data="{show:!localStorage.getItem('giftlab_cookies')}" x-show="show" x-cloak>
        <p>Usamos cookies necessários para o site funcionar. Preferências opcionais só serão ativadas com sua escolha.</p>
        <div><button @click="localStorage.setItem('giftlab_cookies','essential');show=false" class="btn ghost">Só necessários</button><button @click="localStorage.setItem('giftlab_cookies','all');show=false" class="btn primary">Aceitar</button></div>
    </div>
</body>
</html>
