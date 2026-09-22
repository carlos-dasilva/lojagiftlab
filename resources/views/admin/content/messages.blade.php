@extends('layouts.admin')
@section('title','Mensagens')
@section('heading','Mensagens de contato')
@section('content')
@include('admin.content.nav')
@forelse($messages as $message)<article class="admin-card"><h2>{{ $message->subject }}</h2><p>{{ $message->name }} · <a href="mailto:{{ $message->email }}">{{ $message->email }}</a> · {{ $message->created_at->format('d/m/Y H:i') }}</p><p class="message-body">{{ $message->message }}</p><div class="content-actions"><form method="post" action="{{ route('admin.messages.read',$message) }}">@csrf @method('patch')<button class="btn ghost">{{ $message->read_at ? 'Marcar como não lida' : 'Marcar como lida' }}</button></form><form method="post" action="{{ route('admin.messages.destroy',$message) }}" data-confirm data-confirm-message="Excluir esta mensagem?">@csrf @method('delete')<button class="btn ghost">Excluir</button></form></div></article>@empty<div class="admin-card">Nenhuma mensagem recebida.</div>@endforelse
{{ $messages->links() }}
@endsection
