<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ShippingController;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/produtos', [CatalogController::class, 'index'])->name('catalog');
Route::get('/produto/{product}', [CatalogController::class, 'show'])
    ->missing(fn () => response()->view('errors.product-not-found', [], 404))
    ->name('products.show');
Route::post('/produto/{product}/frete', ShippingController::class)->middleware('throttle:10,1')->name('products.shipping');
Route::get('/categoria/{category}', [CatalogController::class, 'category'])->name('categories.show');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/contato', [PageController::class, 'contact'])->name('contact');
Route::post('/contato', [PageController::class, 'send'])->middleware('throttle:5,10')->name('contact.send');
Route::get('/{page}', [PageController::class, 'show'])->where('page', 'quem-somos|politica-de-privacidade|politica-de-cookies|termos-de-uso')->name('pages.show');
Route::get('/sitemap.xml', fn () => response()->view('sitemap', ['products' => Product::published()->get(), 'categories' => Category::where('active', true)->get()])->header('Content-Type', 'application/xml'))->name('sitemap');

Route::get('/admin', [AuthController::class, 'create'])->name('admin.login');
Route::middleware('guest')->group(function () {
    Route::post('/admin/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('admin.login.store');
});
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('products', ProductController::class)->except('show');
    Route::get('/products/{product}', fn (Product $product) => redirect()->route('admin.products.edit', $product))->name('products.show');
    Route::patch('/products/{product}/images/{image}/primary', [ProductController::class, 'primaryImage'])->name('products.images.primary');
    Route::delete('/products/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
    Route::get('/categorias', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categorias', [CategoryController::class, 'store'])->name('categories.store');
    Route::delete('/categorias/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::get('/configuracoes', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/configuracoes', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
