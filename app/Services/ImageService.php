<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageService
{
    public function store(UploadedFile $file, string $directory = 'products'): array
    {
        if (! function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages(['images' => 'O servidor precisa da extensão GD para processar imagens.']);
        }
        $dimensions = @getimagesize($file->getRealPath());
        if (! $dimensions || $dimensions[0] * $dimensions[1] > 24000000) {
            throw ValidationException::withMessages(['images' => 'Use uma imagem válida de até 24 megapixels.']);
        }
        $source = @imagecreatefromstring($file->get());
        if (! $source) {
            throw ValidationException::withMessages(['images' => 'Não foi possível ler esta imagem.']);
        }
        $paths = [];
        $base = $directory.'/'.Str::uuid();
        try {
            foreach (['path' => 1600, 'catalog_path' => 720, 'thumbnail_path' => 240] as $key => $size) {
                $scale = min(1, $size / max(imagesx($source), imagesy($source)));
                $width = max(1, (int) round(imagesx($source) * $scale));
                $height = max(1, (int) round(imagesy($source) * $scale));
                $target = imagecreatetruecolor($width, $height);
                imagealphablending($target, false);
                imagesavealpha($target, true);
                imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));
                ob_start();
                imagewebp($target, null, 85);
                $bytes = ob_get_clean();
                imagedestroy($target);
                $path = $base.'-'.$size.'.webp';
                if (! Storage::disk('public')->put($path, $bytes)) {
                    throw new \RuntimeException('Não foi possível salvar a imagem.');
                }
                $paths[$key] = $path;
            }
        } catch (\Throwable $error) {
            Storage::disk('public')->delete(array_values($paths));
            throw $error;
        } finally {
            imagedestroy($source);
        }

        return $paths;
    }

    public function single(UploadedFile $file, string $directory): string
    {
        $paths = $this->store($file, $directory);
        Storage::disk('public')->delete([$paths['catalog_path'], $paths['thumbnail_path']]);

        return $paths['path'];
    }
}
