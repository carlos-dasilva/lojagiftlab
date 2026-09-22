<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;

class MessageController extends Controller
{
    public function index()
    {
        return view('admin.content.messages', ['messages' => ContactMessage::latest()->paginate(20)]);
    }

    public function read(ContactMessage $message)
    {
        $message->update(['read_at' => $message->read_at ? null : now()]);

        return back()->with('success', 'Situação da mensagem atualizada.');
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();

        return back()->with('success', 'Mensagem excluída.');
    }
}
