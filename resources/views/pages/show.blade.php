@extends('layouts.app')
@section('title', $content['title'].' — Gift Lab')
@section('description', $content['meta_description'] ?? $content['subtitle'] ?? '')
@section('content')
<section class="page-hero"><div class="container narrow"><span class="kicker">Gift Lab</span><h1>{{ $content['title'] }}</h1><p>{{ $content['subtitle'] }}</p></div></section>
<article class="container narrow legal-content markdown-content">{!! Str::markdown($content['body'], ['html_input'=>'strip','allow_unsafe_links'=>false]) !!}
@if($page !== 'quem-somos')<p>Para dúvidas ou solicitações, escreva para <a href="mailto:{{ $siteSettings['email'] }}">{{ $siteSettings['email'] }}</a>.</p>@endif
@if(!empty($content['updated_at']))<small>Atualizado em {{ \Carbon\Carbon::parse($content['updated_at'])->format('d/m/Y') }}</small>@endif
</article>
@endsection
