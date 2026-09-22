@extends('layouts.app')
@section('title','Perguntas frequentes — Gift Lab')
@section('content')
<section class="page-hero compact"><div class="container narrow"><span class="kicker">Tire suas dúvidas</span><h1>Perguntas frequentes</h1><p>Tudo o que você precisa saber para encontrar sua próxima descoberta.</p></div></section>
<section class="container narrow faq-list" x-data="{open:0}">
@forelse($items as $item)
<article><button @click="open=open=={{ $item->id }}?0:{{ $item->id }}" :aria-expanded="open=={{ $item->id }}"><span>{{ $item->question }}</span><i>+</i></button><div x-show="open=={{ $item->id }}" x-cloak><p>{{ $item->answer }}</p></div></article>
@empty
<p>Ainda não há perguntas publicadas. <a href="{{ route('contact') }}">Fale com a Gift Lab</a>.</p>
@endforelse
</section>
@endsection
