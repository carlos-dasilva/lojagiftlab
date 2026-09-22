<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OptimizeProductImages extends Command
{
    protected $signature = 'images:optimize';

    protected $description = 'Gera versões WebP das imagens antigas que ainda não foram otimizadas';

    public function handle(ImageService $service): int
    {
        $failed = 0;
        $disk = Storage::disk('public');
        foreach (ProductImage::whereNull('catalog_path')->lazyById(100) as $image) {
            $paths = [];
            try {
                if (! $disk->exists($image->path)) {
                    throw new \RuntimeException('Arquivo original ausente.');
                }
                $original = $image->path;
                $paths = $service->store(new UploadedFile($disk->path($original), basename($original), null, null, true));
                $image->update($paths);
                // Keep the original as a recovery copy; never delete it in this batch command.
                $this->info('Imagem '.$image->id.' otimizada.');
            } catch (\Throwable $error) {
                $disk->delete(array_values($paths));
                $this->warn('Imagem '.$image->id.': '.$error->getMessage());
                $failed++;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
