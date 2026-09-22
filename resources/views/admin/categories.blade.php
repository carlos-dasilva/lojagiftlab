@extends('layouts.admin')
@section('title','Categorias')
@section('heading','Categorias')
@section('content')
<div class="admin-two-cols"><section class="admin-card"><h2>{{ $editing->exists ? 'Editar categoria' : 'Nova categoria' }}</h2><p>Cada produto pode receber várias categorias.</p>
<form method="post" enctype="multipart/form-data" action="{{ $editing->exists ? route('admin.categories.update',$editing) : route('admin.categories.store') }}">@csrf @if($editing->exists) @method('put') @endif
<label>Nome *<input name="name" value="{{ old('name',$editing->name) }}" required></label>
<label>Endereço amigável<input name="slug" value="{{ old('slug',$editing->slug) }}" placeholder="Gerado pelo nome"></label>
<label>Ícone ou símbolo<input name="icon" maxlength="10" value="{{ old('icon',$editing->icon) }}"></label><label>Imagem<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label><label>Descrição<textarea name="description">{{ old('description',$editing->description) }}</textarea></label>
<label>Categoria superior<select name="parent_id"><option value="">Nenhuma</option>@foreach($categories as $option)@if($option->id !== $editing->id)<option value="{{ $option->id }}" @selected(old('parent_id',$editing->parent_id)==$option->id)>{{ $option->name }}</option>@endif
@endforeach</select></label>
<label>Ordem<input type="number" name="order" min="0" value="{{ old('order',$editing->order) }}"></label>
<input type="hidden" name="active" value="0"><label class="check"><input type="checkbox" name="active" value="1" @checked(old('active',$editing->active))> Ativa</label>
<button class="btn primary">Salvar categoria</button></form></section>
<section class="admin-card"><h2>Categorias cadastradas</h2>@foreach($categories as $category)<div class="category-row"><div><a href="{{ route('admin.categories.edit',$category) }}"><strong>#{{ $category->name }}</strong></a><small>{{ $category->parent?->name }} · {{ $category->products_count }} produtos · {{ $category->active ? 'Ativa' : 'Inativa' }}</small></div><form method="post" action="{{ route('admin.categories.destroy',$category) }}" data-confirm data-confirm-message="Excluir a categoria sem excluir os produtos?">@csrf @method('delete')<button>Excluir</button></form></div>@endforeach</section></div>
@endsection
