@extends('layouts.admin')
@section('title', $product->exists ? 'Editar produto' : 'Novo produto')
@section('heading', $product->exists ? 'Editar produto' : 'Novo produto')

@section('content')
<form class="admin-form" method="post" enctype="multipart/form-data" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" novalidate>
    @csrf
    @if ($product->exists) @method('put') @endif

    <p class="required-note"><span class="required-mark">*</span> Campos obrigatórios</p>

    <section class="admin-card">
        <h2>Informações principais</h2>
        <div class="form-grid">
            <label class="span-2 @error('name') has-error @enderror">
                <span>Nome <b class="required-mark">*</b></span>
                <input name="name" value="{{ old('name', $product->name) }}" required aria-required="true">
                @error('name') <small class="field-error">{{ $message }}</small> @enderror
            </label>
            <label class="@error('slug') has-error @enderror">
                <span>Endereço amigável (slug)</span>
                <input name="slug" value="{{ old('slug', $product->slug) }}" placeholder="Gerado pelo nome">
                <small class="field-help">Espaços e acentos serão convertidos automaticamente.</small>
                @error('slug') <small class="field-error">{{ $message }}</small> @enderror
            </label>
            <fieldset class="span-2 category-picker @error('categories') has-error @enderror @error('categories.*') has-error @enderror">
                <legend>Categorias</legend>
                <p>Selecione quantas forem necessárias. Elas funcionam como hashtags para organizar e encontrar o produto.</p>
                @php($selectedCategories = collect(old('categories', $product->exists ? $product->categories->pluck('id')->all() : []))->map(fn ($id) => (int) $id))
                <div class="category-options">
                    @forelse ($categories as $category)
                        <label class="category-option">
                            <input type="checkbox" name="categories[]" value="{{ $category->id }}" @checked($selectedCategories->contains($category->id))>
                            <span>#{{ $category->name }}</span>
                        </label>
                    @empty
                        <span class="category-empty">Nenhuma categoria criada ainda.</span>
                    @endforelse
                </div>
                <small class="field-help">Não encontrou a categoria desejada? <a href="{{ route('admin.categories.index') }}">Criar nova categoria</a>.</small>
                @error('categories') <small class="field-error">{{ $message }}</small> @enderror
                @error('categories.*') <small class="field-error">{{ $message }}</small> @enderror
            </fieldset>
            <label class="@error('status') has-error @enderror">
                <span>Status <b class="required-mark">*</b></span>
                <select name="status" required aria-required="true">@foreach (['draft' => 'Rascunho', 'published' => 'Publicado', 'unavailable' => 'Indisponível', 'archived' => 'Arquivado'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $product->status?->value ?? 'draft') === $value)>{{ $label }}</option>@endforeach</select>
                @error('status') <small class="field-error">{{ $message }}</small> @enderror
            </label>
            <label class="span-2 @error('short_description') has-error @enderror">
                <span>Descrição curta</span><textarea name="short_description" rows="2">{{ old('short_description', $product->short_description) }}</textarea>
                <small class="field-help">Também aceita Markdown, como <code>**negrito**</code> e <code>*itálico*</code>.</small>
                @error('short_description') <small class="field-error">{{ $message }}</small> @enderror
            </label>
            <label class="span-2 @error('description') has-error @enderror">
                <span>Descrição completa</span><textarea name="description" rows="8">{{ old('description', $product->description) }}</textarea>
                <small class="field-help">Você pode usar Markdown: <code>**negrito**</code>, <code>*itálico*</code>, listas com <code>- item</code> e títulos com <code>## Título</code>.</small>
                @error('description') <small class="field-error">{{ $message }}</small> @enderror
            </label>
        </div>
    </section>

    <section class="admin-card">
        <h2>Estoque e embalagem</h2>
        <div class="form-grid cols-3">
            <label class="@error('cost_price') has-error @enderror"><span>Custo privado <b class="required-mark">*</b></span><input type="number" step="0.01" min="0" name="cost_price" value="{{ old('cost_price', $product->cost_price ?? 0) }}" required>@error('cost_price') <small class="field-error">{{ $message }}</small> @enderror</label>
            <label class="@error('stock') has-error @enderror"><span>Estoque</span><input type="number" min="0" name="stock" value="{{ old('stock', $product->stock) }}">@error('stock') <small class="field-error">{{ $message }}</small> @enderror</label>
            <label class="@error('condition') has-error @enderror"><span>Condição <b class="required-mark">*</b></span><select name="condition" required>@foreach (['new' => 'Novo', 'used' => 'Usado', 'like_new' => 'Seminovo', 'custom' => 'Personalizado'] as $value => $label)<option value="{{ $value }}" @selected(old('condition', $product->condition ?? 'new') === $value)>{{ $label }}</option>@endforeach</select>@error('condition') <small class="field-error">{{ $message }}</small> @enderror</label>
            <label class="@error('images.*') has-error @enderror"><span>Imagens</span><input type="file" name="images[]" accept="image/png,image/jpeg,image/webp" multiple><small class="field-help">JPG, PNG ou WebP; até 5 MB por imagem.</small>@error('images.*') <small class="field-error">{{ $message }}</small> @enderror</label>
            <label><span>Peso com embalagem (kg)</span><input type="number" step="0.001" min="0.001" name="weight_kg" value="{{ old('weight_kg', $product->weight_kg) }}"></label>
            <label><span>Largura (cm)</span><input type="number" step="0.01" min="1" name="width_cm" value="{{ old('width_cm', $product->width_cm) }}"></label>
            <label><span>Altura (cm)</span><input type="number" step="0.01" min="1" name="height_cm" value="{{ old('height_cm', $product->height_cm) }}"></label>
            <label><span>Comprimento (cm)</span><input type="number" step="0.01" min="1" name="length_cm" value="{{ old('length_cm', $product->length_cm) }}"><small class="field-help">Preencha as quatro medidas para liberar o cálculo de frete.</small></label>
        </div>
        <div class="checks">
            <label><input type="checkbox" name="featured" value="1" @checked(old('featured', $product->featured))> Destaque</label>
            <label><input type="checkbox" name="is_new" value="1" @checked(old('is_new', $product->is_new))> Lançamento</label>
            <label><input type="checkbox" name="customizable" value="1" @checked(old('customizable', $product->customizable))> Personalizável</label>
            <label><input type="checkbox" name="made_to_order" value="1" @checked(old('made_to_order', $product->made_to_order))> Sob encomenda</label>
        </div>
    </section>

    @if ($product->exists && $product->images->count())
    <section class="admin-card product-images-manager" data-images-manager>
        <div class="card-head"><div><span class="kicker">Galeria</span><h2>Imagens do produto</h2><p>Escolha a capa exibida nos cards e remova imagens que não deseja manter.</p></div></div>
        <div class="product-images-grid">
            @foreach ($product->images as $image)
                <article class="product-image-item @if ($image->is_primary) is-primary @endif" data-image-item>
                    <img src="{{ Storage::url($image->path) }}" alt="{{ $image->alt ?: $product->name }}">
                    @if ($image->is_primary)<span class="cover-badge">Capa atual</span>@endif
                    <div>
                        @unless ($image->is_primary)<button type="button" data-image-action="primary" data-url="{{ route('admin.products.images.primary', [$product, $image]) }}">Definir como capa</button>@endunless
                        <button type="button" class="danger" data-image-action="delete" data-url="{{ route('admin.products.images.destroy', [$product, $image]) }}">Excluir</button>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
    @endif

    <section class="admin-card dynamic-editor" data-video-editor>
        <div class="card-head"><div><span class="kicker">Demonstração</span><h2>Vídeos do YouTube</h2><p>Os vídeos aparecerão junto às fotos na galeria do produto.</p></div><button class="btn ghost" type="button" data-add-video>+ Adicionar vídeo</button></div>
        @php($videos = old('videos', $product->exists ? $product->videos->map(fn ($video) => ['url' => $video->watch_url, 'title' => $video->title])->all() : []))
        <div data-video-list>@foreach ($videos as $index => $video)<div class="dynamic-row" data-video-row><label><span>Link do YouTube</span><input type="url" name="videos[{{ $index }}][url]" value="{{ $video['url'] ?? '' }}" placeholder="https://youtu.be/..."></label><label><span>Título opcional</span><input name="videos[{{ $index }}][title]" value="{{ $video['title'] ?? '' }}"></label><button type="button" data-remove-video>Remover</button></div>@endforeach</div>
        @error('videos.*.url') <small class="field-error">{{ $message }}</small> @enderror
    </section>

    <section class="admin-card sales-links-editor" data-sales-links-editor>
        <div class="card-head sales-links-heading">
            <div>
                <span class="kicker">Onde vender</span>
                <h2>Links de compra</h2>
                <p>Adicione o link direto deste produto em cada site onde ele está à venda.</p>
            </div>
            <button class="btn ghost add-sales-link" type="button" data-add-sales-link>+ Adicionar local</button>
        </div>

        @php($salesLinks = old('sales_links', $product->exists ? $product->salesLinks->map(fn ($link) => ['channel' => $link->channel->name, 'url' => $link->url, 'price' => $link->price])->all() : []))

        <datalist id="sales-channel-suggestions">
            @foreach ($salesChannels as $channel)<option value="{{ $channel->name }}"></option>@endforeach
            <option value="Mercado Livre"></option><option value="Shopee"></option><option value="OLX"></option>
        </datalist>

        <div class="sales-links-list" data-sales-links-list>
            @foreach ($salesLinks as $index => $link)
                <div class="sales-link-row" data-sales-link-row>
                    <label><span>Local de venda</span><input name="sales_links[{{ $index }}][channel]" value="{{ $link['channel'] ?? '' }}" list="sales-channel-suggestions" placeholder="Ex.: Mercado Livre"></label>
                    <label><span>Link direto do anúncio</span><input type="url" name="sales_links[{{ $index }}][url]" value="{{ $link['url'] ?? '' }}" placeholder="https://..."></label>
                    <label><span>Valor neste local (R$)</span><input type="number" step="0.01" min="0.01" name="sales_links[{{ $index }}][price]" value="{{ $link['price'] ?? '' }}"></label>
                    <button type="button" class="remove-sales-link" data-remove-sales-link aria-label="Remover este local">Remover</button>
                </div>
            @endforeach
        </div>

        <div class="sales-links-empty" data-sales-links-empty @if (count($salesLinks)) hidden @endif>
            <span>↗</span><p>Nenhum link adicionado. O cliente poderá entrar em contato para saber como comprar.</p>
        </div>
        @error('sales_links.*.channel') <small class="field-error">{{ $message }}</small> @enderror
        @error('sales_links.*.url') <small class="field-error">{{ $message }}</small> @enderror
        @error('sales_links.*.price') <small class="field-error">{{ $message }}</small> @enderror
    </section>

    <div class="sticky-actions">@if ($product->exists)<button class="btn danger-button" type="submit" form="delete-product-form">Excluir produto</button>@endif<a class="btn ghost" href="{{ route('admin.products.index') }}">Cancelar</a><button class="btn primary">Salvar produto</button></div>
</form>
@if ($product->exists)<form id="delete-product-form" method="post" action="{{ route('admin.products.destroy', $product) }}" data-confirm data-confirm-title="Excluir produto?" data-confirm-message="Esta ação removerá o produto e suas imagens permanentemente." data-confirm-label="Excluir produto">@csrf @method('delete')</form>@endif
@endsection
