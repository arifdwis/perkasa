<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * Store uploaded file as WebP format.
     */
    public function storeAsWebP(UploadedFile $file, string $directory, string $disk = 'public', int $quality = 80): string
    {
        $image = $this->createImageFromFile($file);
        if (! $image) {
            return $file->store($directory, $disk);
        }

        $filename = pathinfo($file->hashName(), PATHINFO_FILENAME) . '.webp';
        $tempPath = sys_get_temp_dir() . '/' . $filename;

        imagepalettetotruecolor($image);
        imagewebp($image, $tempPath, $quality);
        imagedestroy($image);

        $storedPath = Storage::disk($disk)->putFileAs($directory, new \Illuminate\Http\File($tempPath), $filename);
        @unlink($tempPath);

        return $storedPath;
    }

    /**
     * Create GD image resource from uploaded file.
     */
    private function createImageFromFile(UploadedFile $file)
    {
        $mime = $file->getMimeType();
        $path = $file->getRealPath();

        return match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => null,
        };
    }
}
