<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use App\Models\ServiceImage;
use Illuminate\Console\Command;

class FixPlaceholderImages extends Command
{
    protected $signature = 'images:fix-placeholder';
    protected $description = 'Replace broken loremflickr.com placeholder URLs with picsum.photos';

    public function handle(): void
    {
        $replaced = 0;

        foreach ([ProductImage::class, ServiceImage::class] as $model) {
            $model::query()
                ->where('image_path', 'like', '%loremflickr%')
                ->chunk(100, function ($images) use (&$replaced) {
                    foreach ($images as $image) {
                        $newUrl = $this->replaceUrl($image->image_path);
                        if ($newUrl) {
                            $image->forceFill(['image_path' => $newUrl])->save();
                            $replaced++;
                        }
                    }
                });
        }

        $this->info("{$replaced} placeholder image URLs replaced.");
    }

    private function replaceUrl(string $url): ?string
    {
        if (!str_contains($url, 'loremflickr.com')) {
            return null;
        }

        $parsed = parse_url($url);
        $path = trim($parsed['path'] ?? '', '/');
        $segments = explode('/', $path);
        $slug = end($segments);

        parse_str($parsed['query'] ?? '', $query);
        $seed = $query['lock'] ?? rand(1, 99999);

        return "https://picsum.photos/seed/{$seed}/900/900";
    }
}
