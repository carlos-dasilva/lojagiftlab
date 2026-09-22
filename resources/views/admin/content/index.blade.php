@extends('layouts.admin')
@section('title', $title)
@section('heading', $title)
@section('content')
@include('admin.content.nav')
<section class="admin-card">
    <div class="card-head"><h2>{{ $title }}</h2>@if($section !== 'paginas')<a class="btn primary" href="{{ route('admin.content.create', $section) }}">Novo registro</a>@endif</div>
    <div class="content-records">
    @forelse($records as $record)
        <article><div><a href="{{ route('admin.content.edit', [$section, $record->id]) }}"><strong>{{ $record->title ?? $record->question ?? $record->name }}</strong></a>@if(isset($record->active))<small>{{ $record->active ? 'Ativo' : 'Inativo' }}</small>@endif</div><a class="btn ghost" href="{{ route('admin.content.edit', [$section, $record->id]) }}">Editar</a></article>
    @empty<p>Nenhum registro cadastrado.</p>@endforelse
    </div>{{ $records->links() }}
</section>
@endsection
