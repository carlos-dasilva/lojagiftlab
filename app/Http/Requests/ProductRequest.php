<?php

namespace App\Http\Requests;

use App\Models\ProductVariant;
use App\Models\ProductVideo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $salesLinks = collect($this->input('sales_links', []))->map(function ($link) {
            if (! is_array($link)) {
                return $link;
            }
            if (Str::slug(is_string($link['channel'] ?? null) ? $link['channel'] : '') === 'direct-do-instagram') {
                $link['url'] = 'https://www.instagram.com/lojagiftlab/';
            }

            return $link;
        })->all();

        $this->merge(['slug' => Str::slug($this->input('slug') ?: $this->input('name', '')), 'sales_links' => $salesLinks]);
    }

    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function after(): array
    {
        return [function ($validator) {
            foreach ((array) $this->input('variants', []) as $key => $variant) {
                if (! is_array($variant)) {
                    continue;
                }
                $sku = $variant['sku'] ?? null;
                if ($sku && ProductVariant::where('sku', $sku)->when($this->route('product'), fn ($query, $product) => $query->where('product_id', '!=', $product->id))->exists()) {
                    $validator->errors()->add("variants.$key.sku", 'Este SKU já pertence a outro produto.');
                }
            }
            $seen = [];
            foreach ((array) $this->input('videos', []) as $key => $video) {
                $url = is_array($video) ? ($video['url'] ?? null) : null;
                $id = ProductVideo::idFromUrl(is_string($url) ? $url : null);
                if ($id && isset($seen[$id])) {
                    $validator->errors()->add("videos.$key.url", 'Este vídeo já foi adicionado.');
                }
                if ($id) {
                    $seen[$id] = true;
                }
            }
        }];
    }

    public function rules(): array
    {
        return [
            'tags_text' => 'nullable|string|max:1000',
            'condition_notes' => 'nullable|string|max:2000',
            'attributes' => 'nullable|array|max:50', 'attributes.*.name' => 'required|string|max:100', 'attributes.*.value' => 'required|string|max:255', 'attributes.*.unit' => 'nullable|string|max:30',
            'variants' => 'nullable|array|max:100', 'variants.*.name' => 'required|string|max:100', 'variants.*.sku' => 'nullable|string|max:80|distinct', 'variants.*.stock' => 'nullable|integer|min:0', 'variants.*.price_adjustment' => 'required|numeric|between:-999999,999999', 'variants.*.active' => 'boolean',
            'image_order' => 'nullable|array', 'image_order.*' => 'integer|min:0',
            'sales_links.*.original_price' => 'nullable|numeric|gt:sales_links.*.price|max:9999999999',
            'name' => 'required|string|max:160', 'slug' => ['nullable', 'alpha_dash', Rule::unique('products')->ignore($this->route('product'))], 'sku' => ['nullable', 'max:80', Rule::unique('products')->ignore($this->route('product'))],
            'categories' => 'nullable|array', 'categories.*' => 'integer|distinct|exists:categories,id', 'short_description' => 'nullable|string|max:500', 'description' => 'nullable|string',
            'cost_price' => 'required|numeric|min:0|max:9999999999', 'stock' => 'nullable|integer|min:0',
            'weight_kg' => 'nullable|required_with:width_cm,height_cm,length_cm|numeric|min:0.001|max:9999', 'width_cm' => 'nullable|required_with:weight_kg,height_cm,length_cm|numeric|min:1|max:9999', 'height_cm' => 'nullable|required_with:weight_kg,width_cm,length_cm|numeric|min:1|max:9999', 'length_cm' => 'nullable|required_with:weight_kg,width_cm,height_cm|numeric|min:1|max:9999',
            'condition' => 'required|in:new,used,like_new,custom', 'status' => 'required|in:draft,published,unavailable,archived', 'featured' => 'boolean', 'is_new' => 'boolean', 'customizable' => 'boolean', 'made_to_order' => 'boolean', 'images' => 'nullable|array|max:20', 'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'videos' => 'nullable|array|max:10', 'videos.*.url' => ['required_with:videos.*.title', 'nullable', 'url:http,https', function ($attribute, $value, $fail) {
                if ($value && ! ProductVideo::idFromUrl($value)) {
                    $fail('Informe um link válido de vídeo do YouTube.');
                }
            }], 'videos.*.title' => 'nullable|string|max:120',
            'sales_links' => 'nullable|array|max:20', 'sales_links.*.channel' => 'required_with:sales_links.*.url,sales_links.*.price|nullable|string|max:80', 'sales_links.*.url' => 'required_with:sales_links.*.channel,sales_links.*.price|nullable|url:http,https|max:2000', 'sales_links.*.price' => 'required_with:sales_links.*.channel,sales_links.*.url|nullable|numeric|min:0.01|max:9999999999',
            'is_bundle' => 'boolean', 'bundle_items' => 'nullable|array', 'bundle_items.*.selected' => 'nullable|boolean', 'bundle_items.*.quantity' => 'nullable|integer|min:1|max:9999',
        ];
    }

    public function messages(): array
    {
        return ['required' => 'O campo :attribute é obrigatório.', 'numeric' => 'O campo :attribute deve ser um número válido.', 'integer' => 'O campo :attribute deve ser um número inteiro.', 'min' => 'O campo :attribute deve ser no mínimo :min.', 'max' => 'O campo :attribute ultrapassou o limite permitido.', 'unique' => 'Este :attribute já está sendo utilizado por outro produto.', 'exists' => 'A opção selecionada em :attribute não é válida.', 'in' => 'A opção selecionada em :attribute não é válida.', 'image' => 'Cada arquivo enviado deve ser uma imagem válida.', 'mimes' => 'As imagens devem estar nos formatos JPG, PNG ou WebP.', 'images.*.max' => 'Cada imagem pode ter no máximo 5 MB.', 'sales_links.*.channel.required_with' => 'Informe o local de venda.', 'sales_links.*.url.required_with' => 'Informe o link direto do anúncio.', 'sales_links.*.url.url' => 'Informe um link completo, começando com http:// ou https://.', 'sales_links.*.price.required_with' => 'Informe o valor cobrado neste local de venda.', 'videos.*.url.url' => 'Informe um link completo do YouTube.', 'required_with' => 'Preencha :attribute para habilitar este recurso.'];
    }

    public function attributes(): array
    {
        return ['name' => 'nome', 'slug' => 'endereço amigável', 'categories.*' => 'categoria', 'short_description' => 'descrição curta', 'description' => 'descrição completa', 'cost_price' => 'preço de custo', 'stock' => 'estoque', 'weight_kg' => 'peso', 'width_cm' => 'largura', 'height_cm' => 'altura', 'length_cm' => 'comprimento', 'condition' => 'condição', 'status' => 'status', 'images.*' => 'imagem', 'videos.*.url' => 'link do vídeo', 'videos.*.title' => 'título do vídeo', 'sales_links.*.channel' => 'local de venda', 'sales_links.*.url' => 'link do anúncio', 'sales_links.*.price' => 'valor neste local', 'bundle_items.*.quantity' => 'quantidade do item no conjunto'];
    }
}
