<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductMediaAndCategorySeeder extends Seeder
{
    /**
     * Normalize demo product categories and attach at least two relevant dummy images.
     */
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        $categories = ProductCategory::pluck('id', 'name');
        $updatedProducts = 0;
        $updatedImages = 0;

        Product::query()
            ->with(['category', 'images'])
            ->orderBy('name')
            ->get()
            ->each(function ($product) use ($categories, &$updatedProducts, &$updatedImages) {
                $match = $this->matchProduct($product->name, $product->category?->name);
                $categoryId = $categories[$match['category']] ?? $product->product_category_id;

                if ($product->product_category_id !== $categoryId) {
                    $product->forceFill(['product_category_id' => $categoryId])->save();
                }

                $imageUrls = $this->imageUrls($match['query'], $product->slug);

                ProductImage::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'is_primary' => true,
                    ],
                    ['image_path' => $imageUrls[0]]
                );

                ProductImage::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'is_primary' => false,
                    ],
                    ['image_path' => $imageUrls[1]]
                );

                $updatedProducts++;
                $updatedImages += 2;
            });

        $this->command?->info("{$updatedProducts} produk dirapikan dengan minimal {$updatedImages} gambar relevan.");
    }

    private function matchProduct(string $name, ?string $fallbackCategory): array
    {
        $normalizedName = Str::lower($name);

        foreach ($this->keywordMap() as $rule) {
            foreach ($rule['keywords'] as $keyword) {
                if (Str::contains($normalizedName, $keyword)) {
                    return [
                        'category' => $rule['category'],
                        'query' => $rule['query'],
                    ];
                }
            }
        }

        $category = $fallbackCategory ?: 'UMKM';

        return [
            'category' => $category,
            'query' => $this->fallbackQuery($category),
        ];
    }

    private function imageUrls(string $query, string $slug): array
    {
        $safeQuery = collect(explode(',', $query))
            ->map(fn ($part) => Str::slug(trim($part)))
            ->filter()
            ->implode(',');

        $seed = crc32($slug);

        return [
            "https://loremflickr.com/900/900/{$safeQuery}?lock=" . ($seed % 100000),
            "https://loremflickr.com/900/900/{$safeQuery}?lock=" . (($seed + 31) % 100000),
        ];
    }

    private function fallbackQuery(string $category): string
    {
        return match ($category) {
            'Makanan dan Minuman' => 'food,drink,product',
            'Fashion' => 'fashion,clothing,product',
            'Elektronik' => 'electronics,gadget,product',
            'Buku' => 'book,stationery,product',
            'Kerajinan' => 'craft,handmade,product',
            'Properti' => 'home,decor,interior',
            'Otomotif' => 'automotive,vehicle,accessory',
            'Pertanian' => 'plant,garden,farming',
            default => 'small-business,product,market',
        };
    }

    private function keywordMap(): array
    {
        return [
            ['category' => 'Makanan dan Minuman', 'query' => 'coffee,milk,drink', 'keywords' => ['kopi', 'coffee', 'aren']],
            ['category' => 'Makanan dan Minuman', 'query' => 'dim sum,food,steamed', 'keywords' => ['dimsum', 'dim sum']],
            ['category' => 'Makanan dan Minuman', 'query' => 'brownies,cake,chocolate', 'keywords' => ['brownies']],
            ['category' => 'Makanan dan Minuman', 'query' => 'fish cracker,snack,food', 'keywords' => ['amplang']],
            ['category' => 'Makanan dan Minuman', 'query' => 'fruit salad,dessert,food', 'keywords' => ['salad buah']],
            ['category' => 'Makanan dan Minuman', 'query' => 'thai tea,bottle,drink', 'keywords' => ['thai tea']],
            ['category' => 'Makanan dan Minuman', 'query' => 'banana chips,snack,food', 'keywords' => ['keripik pisang']],
            ['category' => 'Makanan dan Minuman', 'query' => 'chili sauce,bottle,food', 'keywords' => ['sambal']],
            ['category' => 'Makanan dan Minuman', 'query' => 'honey,jar,food', 'keywords' => ['madu']],
            ['category' => 'Makanan dan Minuman', 'query' => 'gift hamper,food,basket', 'keywords' => ['hampers']],
            ['category' => 'Makanan dan Minuman', 'query' => 'fried snack,food', 'keywords' => ['cireng']],

            ['category' => 'Fashion', 'query' => 'tshirt,clothing,fashion', 'keywords' => ['kaos']],
            ['category' => 'Fashion', 'query' => 'hoodie,clothing,fashion', 'keywords' => ['hoodie']],
            ['category' => 'Fashion', 'query' => 'batik,shirt,fabric', 'keywords' => ['batik']],
            ['category' => 'Fashion', 'query' => 'hijab,scarf,fashion', 'keywords' => ['hijab']],
            ['category' => 'Fashion', 'query' => 'tote bag,canvas,fashion', 'keywords' => ['totebag', 'tote bag']],
            ['category' => 'Fashion', 'query' => 'chino,pants,fashion', 'keywords' => ['chino', 'celana']],

            ['category' => 'Elektronik', 'query' => 'powerbank,gadget,electronics', 'keywords' => ['powerbank']],
            ['category' => 'Elektronik', 'query' => 'wireless mouse,electronics,gadget', 'keywords' => ['mouse']],
            ['category' => 'Elektronik', 'query' => 'earbuds,bluetooth,electronics', 'keywords' => ['tws', 'earphone', 'bluetooth']],
            ['category' => 'Elektronik', 'query' => 'laptop stand,desk,electronics', 'keywords' => ['stand laptop']],
            ['category' => 'Elektronik', 'query' => 'led desk lamp,electronics', 'keywords' => ['lampu belajar', 'lampu meja led', 'led rechargeable']],

            ['category' => 'Buku', 'query' => 'accounting book,study', 'keywords' => ['akuntansi']],
            ['category' => 'Buku', 'query' => 'finance book,business,study', 'keywords' => ['manajemen keuangan']],
            ['category' => 'Buku', 'query' => 'binder,notebook,stationery', 'keywords' => ['binder']],
            ['category' => 'Buku', 'query' => 'highlighter,stationery,pastel', 'keywords' => ['stabilo', 'highlighter']],
            ['category' => 'Buku', 'query' => 'book,study,education', 'keywords' => ['buku']],

            ['category' => 'Kerajinan', 'query' => 'graduation bouquet,handmade,craft', 'keywords' => ['buket']],
            ['category' => 'Kerajinan', 'query' => 'aromatherapy candle,craft,handmade', 'keywords' => ['lilin']],
            ['category' => 'Kerajinan', 'query' => 'crochet keychain,handmade,craft', 'keywords' => ['gantungan kunci', 'rajut']],
            ['category' => 'Kerajinan', 'query' => 'resin craft,decor,handmade', 'keywords' => ['resin']],

            ['category' => 'Properti', 'query' => 'desk plant,pot,home decor', 'keywords' => ['tanaman meja', 'tanaman hias']],
            ['category' => 'Properti', 'query' => 'wall shelf,home decor,wood', 'keywords' => ['rak dinding']],
            ['category' => 'Properti', 'query' => 'wooden night lamp,home decor', 'keywords' => ['lampu tidur']],

            ['category' => 'Otomotif', 'query' => 'car perfume,automotive,accessory', 'keywords' => ['parfum mobil']],
            ['category' => 'Otomotif', 'query' => 'microfiber cloth,car cleaning,automotive', 'keywords' => ['kanebo', 'microfiber']],
            ['category' => 'Otomotif', 'query' => 'motorcycle cover,automotive,waterproof', 'keywords' => ['cover motor']],

            ['category' => 'Pertanian', 'query' => 'compost fertilizer,garden,farming', 'keywords' => ['pupuk', 'kompos']],
            ['category' => 'Pertanian', 'query' => 'chili seeds,garden,farming', 'keywords' => ['benih cabai', 'cabai rawit']],
            ['category' => 'Pertanian', 'query' => 'potting soil,garden,plant', 'keywords' => ['media tanam']],
        ];
    }
}
