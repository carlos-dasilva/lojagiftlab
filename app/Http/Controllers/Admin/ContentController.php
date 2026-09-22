<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\ContentPage;
use App\Models\FaqItem;
use App\Models\SalesChannel;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContentController extends Controller
{
    private function definition(string $section): array
    {
        return match ($section) {
            'faq' => [FaqItem::class, 'Perguntas frequentes', ['question' => ['Pergunta', 'text', 'required|string|max:255'], 'answer' => ['Resposta', 'textarea', 'required|string|max:20000'], 'category' => ['Categoria', 'text', 'nullable|string|max:100'], 'order' => ['Ordem', 'number', 'required|integer|min:0'], 'active' => ['Ativo', 'checkbox', 'boolean']]],
            'canais' => [SalesChannel::class, 'Canais de venda', ['name' => ['Nome', 'text', 'required|string|max:80'], 'color' => ['Cor', 'color', 'required|regex:/^#[0-9a-fA-F]{6}$/'], 'icon' => ['Ícone ou símbolo', 'text', 'nullable|string|max:10'], 'base_url' => ['URL principal', 'url', 'nullable|url:http,https|max:2000'], 'order' => ['Ordem', 'number', 'required|integer|min:0'], 'active' => ['Ativo', 'checkbox', 'boolean']]],
            'banners' => [Banner::class, 'Banners', ['title' => ['Título', 'text', 'required|string|max:180'], 'subtitle' => ['Subtítulo', 'textarea', 'nullable|string|max:1000'], 'desktop_image' => ['Imagem desktop', 'file', 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120'], 'mobile_image' => ['Imagem mobile', 'file', 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120'], 'button_label' => ['Texto do botão', 'text', 'nullable|string|max:80'], 'url' => ['URL do botão', 'url', 'nullable|url:http,https|max:2000'], 'starts_at' => ['Início', 'datetime-local', 'nullable|date'], 'ends_at' => ['Fim', 'datetime-local', 'nullable|date|after_or_equal:starts_at'], 'order' => ['Ordem', 'number', 'required|integer|min:0'], 'active' => ['Ativo', 'checkbox', 'boolean']]],
            'paginas' => [ContentPage::class, 'Páginas institucionais', ['title' => ['Título', 'text', 'required|string|max:180'], 'subtitle' => ['Subtítulo', 'text', 'nullable|string|max:500'], 'body' => ['Conteúdo (Markdown)', 'textarea', 'required|string|max:100000'], 'meta_description' => ['Descrição para busca', 'textarea', 'nullable|string|max:300']]],
            default => abort(404),
        };
    }

    public function index(string $section)
    {
        [$model, $title, $fields] = $this->definition($section);
        if ($section === 'paginas') {
            foreach (config('content.pages') as $slug => $defaults) {
                ContentPage::firstOrCreate(['slug' => $slug], $defaults);
            }
        }
        $records = $model::orderBy('id')->paginate(20);

        return view('admin.content.index', compact('section', 'title', 'records'));
    }

    public function edit(string $section, ?int $record = null)
    {
        [$model, $title, $fields] = $this->definition($section);
        abort_if($section === 'paginas' && ! $record, 404);
        $item = $record ? $model::findOrFail($record) : new $model(['active' => true, 'order' => 0, 'color' => '#7139C6']);

        return view('admin.content.form', compact('section', 'title', 'fields', 'item'));
    }

    public function save(Request $request, string $section, ?int $record = null)
    {
        [$model, $title, $fields] = $this->definition($section);
        abort_if($section === 'paginas' && ! $record, 404);
        $item = $record ? $model::findOrFail($record) : new $model;
        $rules = array_map(fn ($field) => $field[2], $fields);
        if ($section === 'banners' && ! $request->filled('starts_at')) {
            $rules['ends_at'] = 'nullable|date';
        }
        if ($section === 'canais') {
            $request->merge(['slug' => Str::slug($request->input('name', ''))]);
            $rules['slug'] = ['required', Rule::unique('sales_channels')->ignore($item->id)];
        }
        $data = $request->validate($rules, ['required' => 'Preencha :attribute.', 'unique' => 'Já existe um registro com este nome.', 'after_or_equal' => 'O fim não pode ser anterior ao início.'], array_map(fn ($field) => $field[0], $fields));
        $newPaths = [];
        $oldPaths = [];
        try {
            foreach ($fields as $key => $field) {
                if ($field[1] === 'checkbox') {
                    $data[$key] = $request->boolean($key);
                }
                if ($field[1] === 'file') {
                    unset($data[$key]);
                    if ($request->hasFile($key)) {
                        $data[$key] = app(ImageService::class)->single($request->file($key), 'banners');
                        $newPaths[] = $data[$key];
                        if ($item->{$key}) {
                            $oldPaths[] = $item->{$key};
                        }
                    }
                }
            }
            $item->fill($data)->save();
        } catch (\Throwable $error) {
            Storage::disk('public')->delete($newPaths);
            throw $error;
        }
        Storage::disk('public')->delete($oldPaths);

        return redirect()->route('admin.content.index', $section)->with('success', 'Conteúdo salvo.');
    }

    public function destroy(string $section, int $record)
    {
        [$model] = $this->definition($section);
        abort_if($section === 'paginas', 403);
        $item = $model::findOrFail($record);
        $paths = $section === 'banners' ? array_filter([$item->desktop_image, $item->mobile_image]) : [];
        $item->delete();
        Storage::disk('public')->delete($paths);

        return back()->with('success', 'Registro excluído.');
    }
}
