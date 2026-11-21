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
 * Demo catalog from the example in docs/domain/catalog.md: real games and
 * programs with fictitious prices, stock and keys. Their covers come from
 * `catalog:import-covers` (IGDB), never from the repository.
 * Requires the reference seeders (activation services, platforms, regions).
 */
class CatalogDemoSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const array TITLES = [
        'Hades' => 'A rogue-like dungeon crawler: fight your way out of the Underworld as the immortal prince Zagreus.',
        'Elden Ring' => 'An open-world action RPG in the Lands Between, by FromSoftware.',
        'The Legend of Zelda: Tears of the Kingdom' => 'An open-air adventure across the land and skies of Hyrule.',
        'Microsoft Office Home 2024' => 'Word, Excel, PowerPoint and OneNote for one device, one-time purchase.',
        'Norton 360 Deluxe' => 'Antivirus, VPN and password manager for up to five devices, one-year subscription.',
    ];

    /**
     * title, type, slug, activation service slug, region slug, platform slugs, price (CLP), stock, published.
     * Real games and programs; prices, stock and keys are fictitious.
     *
     * @var list<array{string, ProductType, string, string|null, string|null, list<string>, int, int|null, ProductStatus}>
     */
    private const array PRODUCTS = [
        ['Hades', ProductType::DigitalKey, 'hades-steam-macos-windows-global', 'steam', 'global', ['windows', 'macos'], 19990, null, ProductStatus::Published],
        ['Hades', ProductType::DigitalKey, 'hades-playstation-store-ps5-latam', 'playstation-store', 'latam', ['ps5'], 21990, null, ProductStatus::Published],
        ['Hades', ProductType::DigitalKey, 'hades-playstation-store-ps4-ps5-latam', 'playstation-store', 'latam', ['ps4', 'ps5'], 24990, null, ProductStatus::Published],
        ['Hades', ProductType::DigitalKey, 'hades-xbox-windows-xbox-series-global', 'xbox', 'global', ['xbox-series', 'windows'], 20990, null, ProductStatus::Published],
        ['Hades', ProductType::Physical, 'hades-physical-ps5-north-america', null, 'north-america', ['ps5'], 34990, 5, ProductStatus::Published],
        ['Elden Ring', ProductType::Physical, 'elden-ring-physical-playstation-store-ps5-latam', 'playstation-store', 'latam', ['ps5'], 44990, 12, ProductStatus::Published],
        ['Elden Ring', ProductType::DigitalKey, 'elden-ring-playstation-store-ps5-latam', 'playstation-store', 'latam', ['ps5'], 39990, null, ProductStatus::Published],
        ['The Legend of Zelda: Tears of the Kingdom', ProductType::DigitalKey, 'the-legend-of-zelda-tears-of-the-kingdom-nintendo-eshop-switch-latam', 'nintendo-eshop', 'latam', ['switch'], 59990, null, ProductStatus::Published],
        ['The Legend of Zelda: Tears of the Kingdom', ProductType::Physical, 'the-legend-of-zelda-tears-of-the-kingdom-physical-nintendo-eshop-switch-2', 'nintendo-eshop', null, ['switch-2'], 64990, 3, ProductStatus::Draft],
        ['Microsoft Office Home 2024', ProductType::License, 'microsoft-office-home-2024-microsoft-account-macos-global', 'microsoft-account', 'global', ['macos'], 129990, null, ProductStatus::Published],
        ['Norton 360 Deluxe', ProductType::License, 'norton-360-deluxe-norton-account-android-ios-macos-windows-global', 'norton-account', 'global', ['windows', 'macos', 'android', 'ios'], 34990, null, ProductStatus::Published],
    ];

    /**
     * Seed the demo catalog. Products and their platforms are written in one
     * transaction because the identity constraint trigger runs at commit.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            ActivationService::query()->firstOrCreate(['slug' => 'norton-account'], ['name' => 'Norton Account']);

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
