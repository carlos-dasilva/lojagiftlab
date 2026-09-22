<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\ContentPage;
use App\Models\FaqItem;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PageController extends Controller
{
    public function show(string $page)
    {
        abort_unless(in_array($page, ['quem-somos', 'politica-de-privacidade', 'politica-de-cookies', 'termos-de-uso']), 404);

        $content = ContentPage::where('slug', $page)->first();
        $content = $content ? $content->toArray() : config('content.pages.'.$page);

        return view('pages.show', compact('page', 'content'));
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
        if (Setting::value('contact_email_enabled', '0') === '1') {
            try {
                Mail::raw($r->validated('message'), function ($mail) use ($r) {
                    $mail->to(Setting::value('site_email', 'lojagiftlab@gmail.com'))->replyTo($r->validated('email'), $r->validated('name'))->subject('Contato Gift Lab: '.$r->validated('subject'));
                });
            } catch (\Throwable $error) {
                report($error);
            }
        }

        return back()->with('success', 'Mensagem enviada. Em breve entraremos em contato!');
    }
}
