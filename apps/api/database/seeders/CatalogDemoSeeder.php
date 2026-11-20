<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\Region;
use App\Models\Title;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fictitious catalog from the example in docs/domain/catalog.md.
 * Requires the reference seeders (activation services, platforms, regions).
 */
class CatalogDemoSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const array TITLES = [
        'Starfall' => 'A space exploration RPG across a collapsing star system.',
        'Neon Heist' => 'A cyberpunk stealth game about the biggest robbery in Neo Valparaíso.',
        'Pixel Kart' => 'Retro kart racing with sixteen-bit tracks and local multiplayer.',
        'Office Suite Pro' => 'Word processor, spreadsheets and slides for one device.',
        'Shield Antivirus' => 'Antivirus and VPN for up to five devices, one-year license.',
    ];

    /**
     * title, type, slug, activation service slug, region slug, platform slugs, price (CLP), stock, published.
     *
     * @var list<array{string, ProductType, string, string|null, string|null, list<string>, int, int|null, ProductStatus}>
     */
    private const array PRODUCTS = [
        ['Starfall', ProductType::DigitalKey, 'starfall-steam-macos-windows-global', 'steam', 'global', ['windows', 'macos'], 19990, null, ProductStatus::Published],
        ['Starfall', ProductType::DigitalKey, 'starfall-playstation-store-ps5-latam', 'playstation-store', 'latam', ['ps5'], 21990, null, ProductStatus::Published],
        ['Starfall', ProductType::DigitalKey, 'starfall-playstation-store-ps4-ps5-latam', 'playstation-store', 'latam', ['ps4', 'ps5'], 24990, null, ProductStatus::Published],
        ['Starfall', ProductType::DigitalKey, 'starfall-xbox-windows-xbox-series-global', 'xbox', 'global', ['xbox-series', 'windows'], 20990, null, ProductStatus::Published],
        ['Starfall', ProductType::Physical, 'starfall-physical-ps5-north-america', null, 'north-america', ['ps5'], 34990, 5, ProductStatus::Published],
        ['Neon Heist', ProductType::Physical, 'neon-heist-physical-playstation-store-ps5-latam', 'playstation-store', 'latam', ['ps5'], 79990, 12, ProductStatus::Published],
        ['Neon Heist', ProductType::DigitalKey, 'neon-heist-playstation-store-ps5-latam', 'playstation-store', 'latam', ['ps5'], 74990, null, ProductStatus::Published],
        ['Pixel Kart', ProductType::DigitalKey, 'pixel-kart-nintendo-eshop-switch-latam', 'nintendo-eshop', 'latam', ['switch'], 39990, null, ProductStatus::Published],
        ['Pixel Kart', ProductType::Physical, 'pixel-kart-physical-nintendo-eshop-switch-2', 'nintendo-eshop', null, ['switch-2'], 44990, 3, ProductStatus::Draft],
        ['Office Suite Pro', ProductType::License, 'office-suite-pro-microsoft-account-macos-global', 'microsoft-account', 'global', ['macos'], 49990, null, ProductStatus::Published],
        ['Shield Antivirus', ProductType::License, 'shield-antivirus-shield-account-android-ios-macos-windows-global', 'shield-account', 'global', ['windows', 'macos', 'android', 'ios'], 14990, null, ProductStatus::Published],
    ];

    /**
     * Seed the demo catalog. Products and their platforms are written in one
     * transaction because the identity constraint trigger runs at commit.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            ActivationService::query()->firstOrCreate(['slug' => 'shield-account'], ['name' => 'Shield Account']);

            $titles = [];

            foreach (self::TITLES as $name => $description) {
                $titles[$name] = Title::query()->create(['name' => $name, 'description' => $description]);
            }

            foreach (self::PRODUCTS as [$titleName, $type, $slug, $serviceSlug, $regionSlug, $platformSlugs, $price, $stock, $status]) {
                $product = Product::query()->create([
                    'title_id' => $titles[$titleName]->id,
                    'type' => $type,
                    'slug' => $slug,
                    'activation_service_id' => $serviceSlug === null ? null : ActivationService::query()->where('slug', $serviceSlug)->sole()->id,
                    'region_id' => $regionSlug === null ? null : Region::query()->where('slug', $regionSlug)->sole()->id,
                    'price' => $price,
                    'stock' => $stock,
                    'status' => $status,
                ]);

                $product->platforms()->attach(Platform::query()->whereIn('slug', $platformSlugs)->pluck('id'));

                if ($type->isDeliveredByKey()) {
                    ProductKey::factory()->for($product)->count(5)->create();
                    ProductKey::factory()->for($product)->reserved()->create();
                    ProductKey::factory()->for($product)->sold()->create();
                }
            }
        });
    }
}
