<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\ContentPage;
use App\Models\FaqItem;
use App\Models\Product;
use App\Models\SalesChannel;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_administration_endpoints_require_an_admin(): void
    {
        $this->get(route('admin.content.index', 'faq'))->assertRedirect('/admin');
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->post(route('admin.content.store', 'faq'), ['question' => 'Não autorizado'])->assertForbidden();
        $this->get(route('admin.messages.index'))->assertForbidden();
        $this->assertDatabaseCount('faq_items', 0);
    }

    public function test_catalog_searches_full_description_and_filters_condition(): void
    {
        $p = $this->product(['description' => 'Marcador exclusivo completo', 'condition' => 'used']);
        $this->get('/produtos?q=exclusivo&condition=used')->assertOk()->assertSee(route('products.show', $p));
        $this->get('/produtos?q=exclusivo&condition=new')->assertOk()->assertDontSee(route('products.show', $p));
    }

    public function test_email_failure_does_not_lose_contact_message(): void
    {
        Setting::put('contact_email_enabled', '1');
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('SMTP indisponível'));
        $this->post('/contato', ['name' => 'Cliente', 'email' => 'cliente@example.com', 'subject' => 'Falha SMTP', 'message' => 'Esta mensagem deve permanecer no painel.'])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('contact_messages', ['subject' => 'Falha SMTP']);
    }

    public function test_legacy_images_can_be_optimized_without_deleting_original(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('original.jpg', 800, 400);
        $path = $file->store('products', 'public');
        $image = $this->product()->images()->create(['path' => $path, 'is_primary' => true]);
        $this->artisan('images:optimize')->assertExitCode(0);
        $image->refresh();
        $this->assertCount(3, $image->files());
        Storage::disk('public')->assertExists($path);
        foreach ($image->files() as $variant) {
            Storage::disk('public')->assertExists($variant);
        }
    }

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function product(array $extra = []): Product
    {
        return Product::create($extra + ['name' => 'Produto de teste', 'slug' => 'produto-'.Str::uuid(), 'cost_price' => 10, 'condition' => 'new', 'status' => 'published']);
    }

    public function test_admin_can_manage_faq_and_pages_and_public_html_is_safe(): void
    {
        $this->admin();
        $this->post(route('admin.content.store', 'faq'), ['question' => 'Entrega?', 'answer' => 'Via Correios', 'order' => 0, 'active' => 1])->assertSessionHasNoErrors();
        $faq = FaqItem::firstOrFail();
        $this->get('/faq')->assertSee('Via Correios');
        $this->put(route('admin.content.update', ['faq', $faq->id]), ['question' => 'Entrega?', 'answer' => 'Via Correios', 'order' => 0, 'active' => 0])->assertSessionHasNoErrors();
        $this->get('/faq')->assertDontSee('Via Correios');
        $this->get(route('admin.content.index', 'paginas'))->assertOk();
        $page = ContentPage::where('slug', 'quem-somos')->firstOrFail();
        $this->put(route('admin.content.update', ['paginas', $page->id]), ['title' => 'Nossa história', 'body' => '**Criatividade** <script>alert(1)</script>'])->assertSessionHasNoErrors();
        $this->get('/quem-somos')->assertOk()->assertSee('<strong>Criatividade</strong>', false)->assertDontSee('<script>alert(1)</script>', false);
        foreach (['faq', 'canais', 'banners', 'paginas'] as $section) {
            $this->get(route('admin.content.index', $section))->assertOk();
        }
        foreach (['faq', 'canais', 'banners'] as $section) {
            $this->get(route('admin.content.create', $section))->assertOk();
        }
    }

    public function test_inactive_channels_do_not_leak_into_prices_and_remain_editable(): void
    {
        $p = $this->product();
        $channel = SalesChannel::create(['name' => 'Canal desativado', 'slug' => 'desativado', 'active' => false]);
        $p->allSalesLinks()->create(['sales_channel_id' => $channel->id, 'url' => 'https://example.com/hidden', 'price' => 1, 'active' => true]);
        $this->get(route('products.show', $p))->assertOk()->assertDontSee('Canal desativado')->assertDontSee('https://example.com/hidden');
        $this->assertNull($p->fresh()->starting_price);
        $this->admin();
        $this->get(route('admin.products.edit', $p))->assertOk()->assertSee('Canal desativado');
    }

    public function test_category_cycles_collisions_and_inactive_urls_are_rejected(): void
    {
        $this->admin();
        $a = Category::create(['name' => 'Pai', 'slug' => 'pai', 'active' => true]);
        $b = Category::create(['name' => 'Filho', 'slug' => 'filho', 'parent_id' => $a->id, 'active' => true]);
        $this->put(route('admin.categories.update', $a), ['name' => 'Pai', 'parent_id' => $b->id, 'active' => 1])->assertSessionHasErrors('parent_id');
        $this->post(route('admin.categories.store'), ['name' => 'Pái'])->assertSessionHasErrors('slug');
        $a->update(['active' => false]);
        $this->get(route('categories.show', $a))->assertNotFound();
        $this->get(route('admin.categories.edit', $b))->assertOk();
    }

    public function test_hidden_bundle_components_and_duplicate_or_fake_youtube_urls_are_rejected(): void
    {
        $bundle = $this->product(['is_bundle' => true]);
        $hidden = $this->product(['name' => 'Segredo do conjunto', 'status' => 'draft']);
        $bundle->bundleItems()->attach($hidden, ['quantity' => 1]);
        $this->get(route('products.show', $bundle))->assertDontSee('Segredo do conjunto');
        $this->admin();
        $base = ['name' => 'Vídeo', 'cost_price' => 10, 'condition' => 'new', 'status' => 'draft'];
        $this->post(route('admin.products.store'), $base + ['videos' => [['url' => 'https://evil.example/youtube.com/watch?v=dQw4w9WgXcQ']]])->assertSessionHasErrors('videos.0.url');
        $this->post(route('admin.products.store'), $base + ['videos' => [['url' => 'https://youtu.be/dQw4w9WgXcQ'], ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']]])->assertSessionHasErrors('videos.1.url');
    }

    public function test_product_details_images_promotions_and_redirects_work_together(): void
    {
        Storage::fake('public');
        $this->admin();
        $data = ['name' => 'Caneca azul', 'cost_price' => 10, 'status' => 'published', 'condition' => 'new', 'details_present' => 1, 'tags_text' => 'Azul, Presente', 'attributes' => [['name' => 'Material', 'value' => 'PLA', 'unit' => '']], 'variants' => [['name' => 'Azul P', 'price_adjustment' => 5, 'active' => 1]], 'sales_links' => [['channel' => 'Loja externa', 'url' => 'https://example.com/caneca', 'price' => 20, 'original_price' => 30]], 'images' => [UploadedFile::fake()->image('photo.png', 1000, 600)]];
        $this->post(route('admin.products.store'), $data)->assertSessionHasNoErrors();
        $p = Product::where('slug', 'caneca-azul')->firstOrFail();
        $image = $p->images->first();
        foreach ($image->files() as $path) {
            Storage::disk('public')->assertExists($path);
        }
        $this->assertCount(3, $image->files());
        $this->get(route('products.show', $p))->assertOk()->assertSee('Azul P')->assertSee('PLA')->assertSee('application/ld+json', false);
        $this->get('/produtos?promotion=1&min_price=19&max_price=21')->assertSee('Caneca azul');
        $this->get('/produtos?min_price=25')->assertDontSee('Caneca azul');
        unset($data['images']);
        $data['slug'] = 'caneca-nova';
        $this->put(route('admin.products.update', $p), $data)->assertSessionHasNoErrors();
        $this->get('/produto/caneca-azul')->assertStatus(301)->assertRedirect('/produto/caneca-nova');
        $this->delete(route('admin.products.images.destroy', [$p->fresh(), $image]))->assertOk();
        foreach ($image->files() as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_contact_messages_are_private_and_managed_without_losing_the_message(): void
    {
        $this->post('/contato', ['name' => 'Cliente', 'email' => 'cliente@example.com', 'subject' => 'Dúvida', 'message' => 'Uma mensagem suficientemente longa.'])->assertSessionHasNoErrors();
        $message = ContactMessage::firstOrFail();
        $this->get(route('admin.messages.index'))->assertRedirect('/admin');
        $this->admin();
        $this->get(route('admin.messages.index'))->assertOk()->assertSee('Uma mensagem suficientemente longa.');
        $this->patch(route('admin.messages.read', $message))->assertSessionHasNoErrors();
        $this->assertNotNull($message->fresh()->read_at);
        $this->delete(route('admin.messages.destroy', $message))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_banners_settings_and_external_media_consent_are_rendered(): void
    {
        Banner::create(['title' => 'Banner ativo', 'active' => true, 'order' => 0]);
        Banner::create(['title' => 'Banner futuro', 'active' => true, 'starts_at' => now()->addDay(), 'order' => 0]);
        $this->get('/')->assertOk()->assertSee('Banner ativo')->assertDontSee('Banner futuro');
        $this->admin();
        $this->get(route('admin.settings.edit'))->assertOk()->assertSee('Página inicial');
        $this->put(route('admin.settings.update'), ['site_name' => 'Gift Lab', 'site_email' => 'loja@example.com', 'hero_title' => 'Nova chamada', 'primary_color' => '#112233', 'home_banners' => '0'])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Nova chamada')->assertSee('--navy:#112233', false)->assertDontSee('Banner ativo');
        $p = $this->product();
        $p->videos()->create(['youtube_id' => 'dQw4w9WgXcQ']);
        $this->get(route('products.show', $p))->assertOk()->assertDontSee('<iframe src=', false)->assertSee('data-external-thumbnail', false)->assertSee('Preferências de privacidade');
    }
}
