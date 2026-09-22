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
        View::composer(['layouts.app', 'layouts.admin', 'admin.login', 'home', 'pages.*', 'catalog.*', 'errors.*'], function ($view) {
            $values = request()->attributes->get('giftlab.settings');
            if ($values === null) {
                $values = Setting::allValues();
                $values['name'] = $values['site_name'];
                $values['email'] = $values['site_email'];
                request()->attributes->set('giftlab.settings', $values);
            }
            $view->with('siteSettings', $values);
            if ($view->name() === 'layouts.app') {
                $view->with('navCategories', Category::where('active', true)->whereNull('parent_id')->orderBy('order')->take(8)->get());
            }
        });
    }
}
