@extends('layouts.admin')
@section('title', $title)
@section('heading', $title)
@section('content')
@include('admin.content.nav')
<form method="post" enctype="multipart/form-data" action="{{ $item->exists ? route('admin.content.update', [$section, $item->id]) : route('admin.content.store', $section) }}" class="admin-card">
@csrf @if($item->exists) @method('put') @endif
@foreach($fields as $key => [$label, $type, $rules])
<label @class(['check'=>$type==='checkbox'])><span>{{ $label }}{{ str_starts_with($rules, 'required') ? ' *' : '' }}</span>
@if($type==='textarea')<textarea name="{{ $key }}" rows="{{ $key==='body' ? 16 : 5 }}">{{ old($key, $item->{$key}) }}</textarea>
@elseif($type==='checkbox')<input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $item->{$key}))>
@elseif($type==='file')<input type="file" name="{{ $key }}" accept="image/jpeg,image/png,image/webp">@if($item->{$key})<img class="content-preview" src="{{ Storage::url($item->{$key}) }}" alt="Imagem atual">@endif
@else<input type="{{ $type }}" name="{{ $key }}" value="{{ old($key, $type==='datetime-local' ? $item->{$key}?->format('Y-m-d\TH:i') : $item->{$key}) }}" @required(str_starts_with($rules, 'required'))>
@endif
@error($key)<small class="field-error">{{ $message }}</small>@enderror</label>
@endforeach
<div class="content-actions"><button class="btn primary">Salvar</button><a class="btn ghost" href="{{ route('admin.content.index', $section) }}">Cancelar</a>@if($item->exists && $section !== 'paginas')<button class="btn ghost" type="submit" form="delete-content">Excluir</button>@endif</div>
</form>
@if($item->exists && $section !== 'paginas')<form id="delete-content" method="post" action="{{ route('admin.content.destroy', [$section, $item->id]) }}" data-confirm data-confirm-message="Excluir este registro? A exclusão de um canal também remove seus links do catálogo.">@csrf @method('delete')</form>@endif
@endsection
