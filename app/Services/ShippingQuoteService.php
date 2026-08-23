<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ShippingQuoteService
{
    public function quote(Product $product, string $postalCode): array
    {
        $config = config('services.melhor_envio');
        if (blank($config['token']) || blank($config['from_postal_code'])) {
            throw new RuntimeException('O cálculo de frete ainda não foi configurado pela loja.');
        }
        foreach (['weight_kg', 'width_cm', 'height_cm', 'length_cm'] as $field) {
            if (! $product->{$field}) {
                throw new RuntimeException('As informações de embalagem deste produto ainda não foram cadastradas.');
            }
        }

        $response = Http::acceptJson()->withToken($config['token'])->withHeaders(['User-Agent' => $config['user_agent']])->timeout(15)->post(rtrim($config['base_url'], '/').'/me/shipment/calculate', [
            'from' => ['postal_code' => preg_replace('/\D/', '', $config['from_postal_code'])],
            'to' => ['postal_code' => preg_replace('/\D/', '', $postalCode)],
            'products' => [['id' => (string) $product->id, 'width' => (float) $product->width_cm, 'height' => (float) $product->height_cm, 'length' => (float) $product->length_cm, 'weight' => (float) $product->weight_kg, 'insurance_value' => (float) ($product->starting_price ?? 1), 'quantity' => 1]],
        ]);
        if ($response->failed()) {
            throw new RuntimeException('Não foi possível consultar o frete agora. Tente novamente em alguns instantes.');
        }

        return collect($response->json())
            ->filter(fn ($item) => is_array($item)
                && empty($item['error'])
                && isset($item['price'])
                && str_contains(strtolower((string) ($item['company']['name'] ?? '')), 'correios'))
            ->map(fn ($item) => [
                'name' => $item['name'] ?? 'Entrega',
                'company' => $item['company']['name'] ?? 'Correios',
                'price' => (float) ($item['custom_price'] ?? $item['price']),
                'days' => (int) ($item['custom_delivery_time'] ?? $item['delivery_time'] ?? 0) + 1,
            ])->values()->all();
    }
}
