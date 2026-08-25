<?php

namespace App\Http\Requests;

use App\Models\ProductVideo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->input('slug') ?: $this->input('name', ''))]);
    }

    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:160', 'slug' => ['nullable', 'alpha_dash', Rule::unique('products')->ignore($this->route('product'))], 'sku' => ['nullable', 'max:80', Rule::unique('products')->ignore($this->route('product'))],
            'categories' => 'nullable|array', 'categories.*' => 'integer|distinct|exists:categories,id', 'short_description' => 'nullable|string|max:500', 'description' => 'nullable|string',
            'cost_price' => 'required|numeric|min:0|max:9999999999', 'stock' => 'nullable|integer|min:0',
            'weight_kg' => 'nullable|required_with:width_cm,height_cm,length_cm|numeric|min:0.001|max:9999', 'width_cm' => 'nullable|required_with:weight_kg,height_cm,length_cm|numeric|min:1|max:9999', 'height_cm' => 'nullable|required_with:weight_kg,width_cm,length_cm|numeric|min:1|max:9999', 'length_cm' => 'nullable|required_with:weight_kg,width_cm,height_cm|numeric|min:1|max:9999',
            'condition' => 'required|in:new,used,like_new,custom', 'status' => 'required|in:draft,published,unavailable,archived', 'featured' => 'boolean', 'is_new' => 'boolean', 'customizable' => 'boolean', 'made_to_order' => 'boolean', 'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
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
