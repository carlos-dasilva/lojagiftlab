<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $view->with('siteSettings', [
                'name' => Setting::value('site_name', 'Gift Lab'),
                'email' => Setting::value('site_email', 'lojagiftlab@gmail.com'),
                'hero_title' => Setting::value('hero_title', 'Presentes, criatividade e coisas incríveis ganhando forma.'),
                'hero_subtitle' => Setting::value('hero_subtitle', 'Ideias especiais, itens geek e presentes únicos escolhidos para surpreender.'),
                'instagram' => Setting::value('instagram'), 'whatsapp' => Setting::value('whatsapp'),
                'primary_color' => Setting::value('primary_color', '#0B163D'),
            ]);
            $view->with('navCategories', Category::where('active', true)->whereNull('parent_id')->orderBy('order')->take(8)->get());
        });
    }
}
