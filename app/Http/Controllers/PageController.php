<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\FaqItem;
use Illuminate\Support\Facades\DB;

class PageController extends Controller
{
    public function show(string $page)
    {
        abort_unless(in_array($page, ['quem-somos', 'politica-de-privacidade', 'politica-de-cookies', 'termos-de-uso']), 404);

        return view('pages.show', compact('page'));
    }

    public function faq()
    {
        return view('pages.faq', ['items' => FaqItem::where('active', true)->orderBy('order')->get()]);
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function send(ContactRequest $r)
    {
        DB::table('contact_messages')->insert([...$r->safe()->except('website'), 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Mensagem enviada. Em breve entraremos em contato!');
    }
}
