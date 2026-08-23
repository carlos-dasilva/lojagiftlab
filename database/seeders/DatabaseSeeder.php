<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\FaqItem;
use App\Models\Product;
use App\Models\SalesChannel;
use App\Models\Setting;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $categories = collect(['Impressão 3D', 'Action Figures', 'Geek', 'Games Retrô', 'Presentes', 'Casa', 'Cozinha', 'Plantas', 'Decoração', 'Utilidades', 'Moda íntima', 'Outros'])->mapWithKeys(fn ($name, $i) => [$name => Category::updateOrCreate(['slug' => str($name)->slug()], ['name' => $name, 'order' => $i, 'active' => true])]);
        $samples = [
            ['Action Figure Mario 3D', 'Action Figures', 189.90, 10, 'Uma peça demonstrativa cheia de personalidade para fãs do universo dos games.'],
            ['Vaso Groot', 'Plantas', 79.90, 0, 'Um vaso demonstrativo divertido para deixar seu cantinho mais verde.'],
            ['Suporte para Controle', 'Games Retrô', 59.90, 0, 'Organização e estilo para o setup gamer.'],
            ['Organizador de Cozinha', 'Cozinha', 49.90, 5, 'Praticidade com um toque criativo para o dia a dia.'],
            ['Presente Personalizado', 'Presentes', 99.90, 0, 'Uma ideia demonstrativa feita para ganhar a sua personalidade.'],
            ['Produto Geek', 'Geek', 69.90, 0, 'Um item demonstrativo para quem gosta de cultura pop.'],
            ['Item Retrô', 'Games Retrô', 129.90, 15, 'Nostalgia e personalidade em uma peça demonstrativa.'],
        ];
        foreach ($samples as $i => [$name,$category,$price,$discount,$description]) {
            $product = Product::updateOrCreate(['slug' => str($name)->slug()], ['name' => $name, 'short_description' => $description, 'description' => $description."\n\nProduto fictício criado para demonstração do catálogo.", 'cost_price' => round($price * .45, 2), 'sale_price' => $price, 'discount_percentage' => $discount, 'stock' => 10, 'condition' => 'new', 'featured' => $i < 4, 'is_new' => $i > 3, 'customizable' => $name === 'Presente Personalizado', 'made_to_order' => $name === 'Presente Personalizado', 'status' => 'published', 'order' => $i]);
            $product->categories()->syncWithoutDetaching([$categories[$category]->id]);
        }
        foreach ([['Instagram', '#FF3D5F'], ['WhatsApp', '#19a974'], ['Mercado Livre', '#3483FA'], ['Shopee', '#EE4D2D']] as $i => [$name,$color]) {
            SalesChannel::updateOrCreate(['slug' => str($name)->slug()], ['name' => $name, 'color' => $color, 'active' => true, 'order' => $i]);
        }
        foreach (['site_name' => 'Gift Lab', 'site_email' => 'lojagiftlab@gmail.com', 'hero_title' => 'Presentes, criatividade e coisas incríveis ganhando forma.', 'hero_subtitle' => 'Ideias especiais, itens geek e presentes únicos escolhidos para surpreender.', 'primary_color' => '#0B163D'] as $key => $value) {
            Setting::put($key, $value);
        }
        foreach ([['Como faço para comprar?', 'Escolha um canal disponível na página do produto. A compra acontece no ambiente externo selecionado.'], ['Vocês fazem produtos personalizados?', 'Quando um produto aceitar personalização, essa informação aparecerá em destaque. Entre em contato para conversar sobre sua ideia.'], ['Os produtos do seed são reais?', 'Sim, estas são as versões finais após vários testes até obtermos a melhor versão de cada produto.']] as $i => [$q,$a]) {
            FaqItem::updateOrCreate(['question' => $q], ['answer' => $a, 'order' => $i, 'active' => true]);
        }
    }
}
