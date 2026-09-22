@extends('layouts.admin')
@section('title','Configurações')
@section('heading','Configurações')
@section('content')
<nav class="settings-tabs" aria-label="Seções das configurações">@foreach($groups as $group=>[$label,$fields])<a href="#settings-{{ $group }}">{{ $label }}</a>@endforeach</nav>
<form method="post" enctype="multipart/form-data" action="{{ route('admin.settings.update') }}">@csrf @method('put')
@foreach($groups as $group=>[$label,$fields])
<section id="settings-{{ $group }}" class="admin-card settings-section"><h2>{{ $label }}</h2><div class="form-grid">
@foreach($fields as $key=>[$fieldLabel,$type,$default])
<label @class(['span-2'=>$type==='textarea','check'=>$type==='checkbox'])><span>{{ $fieldLabel }}</span>
@if($type==='textarea')<textarea name="{{ $key }}" rows="3">{{ old($key,$values[$key]) }}</textarea>
@elseif($type==='checkbox')<input type="hidden" name="{{ $key }}" value="0"><input type="checkbox" name="{{ $key }}" value="1" @checked(old($key,$values[$key])==='1')>
@elseif($type==='file')<input type="file" name="{{ $key }}" accept="image/png,image/jpeg,image/webp">@if($values[$key])<img class="content-preview" src="{{ Storage::url($values[$key]) }}" alt="Imagem atual">@endif
@else<input type="{{ $type }}" name="{{ $key }}" value="{{ old($key,$values[$key]) }}" @required(in_array($key,['site_name','site_email','hero_title']))>
@endif @error($key)<small class="field-error">{{ $message }}</small>@enderror</label>
@endforeach</div></section>
@endforeach
<div class="sticky-actions"><button class="btn primary">Salvar configurações</button></div>
</form>
@endsection
