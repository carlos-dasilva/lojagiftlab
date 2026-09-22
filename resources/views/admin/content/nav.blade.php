<nav class="finance-nav" aria-label="Conteúdo da loja">
@foreach(['paginas'=>'Páginas', 'faq'=>'FAQ', 'banners'=>'Banners', 'canais'=>'Canais de venda'] as $key=>$label)<a href="{{ route('admin.content.index', $key) }}" @class(['active'=>($section ?? '')===$key])>{{ $label }}</a>@endforeach
<a href="{{ route('admin.messages.index') }}">Mensagens</a>
</nav>
