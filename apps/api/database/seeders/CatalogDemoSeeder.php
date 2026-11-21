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
        'Cyberpunk 2077' => 'An open-world action RPG set in Night City, a megalopolis obsessed with power and body modification.',
        'God of War Ragnarök' => 'Kratos and Atreus journey through the Nine Realms as Ragnarök approaches.',
        'Marvel\'s Spider-Man 2' => 'Peter Parker and Miles Morales swing across New York against Kraven and Venom.',
        'Super Mario Bros. Wonder' => 'A side-scrolling Mario adventure where Wonder Flowers twist every level.',
        'Mario Kart World' => 'Open-world kart racing across one connected world, for Nintendo Switch 2.',
        'Halo Infinite' => 'Master Chief returns to fight the Banished on the ring world Zeta Halo.',
        'Forza Horizon 5' => 'Open-world racing across the landscapes of Mexico.',
        'Baldur\'s Gate 3' => 'A story-rich party RPG set in the world of Dungeons & Dragons.',
        'Hollow Knight' => 'A hand-drawn action adventure through the ruined insect kingdom of Hallownest.',
        'Stardew Valley' => 'Inherit your grandfather\'s farm and build a new life in Pelican Town.',
        'Red Dead Redemption 2' => 'An epic tale of outlaw life in the dying days of America\'s Wild West.',
        'The Witcher 3: Wild Hunt' => 'Geralt of Rivia hunts for his adopted daughter across a war-torn open world.',
        'Animal Crossing: New Horizons' => 'Build your own island paradise and live at your own pace.',
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
        // More real games for the shelves (not in the catalog doc's example table).
        ['Cyberpunk 2077', ProductType::DigitalKey, 'cyberpunk-2077-steam-windows-global', 'steam', 'global', ['windows'], 29990, null, ProductStatus::Published],
        ['Cyberpunk 2077', ProductType::DigitalKey, 'cyberpunk-2077-playstation-store-ps5-latam', 'playstation-store', 'latam', ['ps5'], 32990, null, ProductStatus::Published],
        ['Cyberpunk 2077', ProductType::DigitalKey, 'cyberpunk-2077-xbox-xbox-series-global', 'xbox', 'global', ['xbox-series'], 31990, null, ProductStatus::Published],
        ['God of War Ragnarök', ProductType::Physical, 'god-of-war-ragnarok-physical-playstation-store-ps5-latam', 'playstation-store', 'latam', ['ps5'], 49990, 8, ProductStatus::Published],
        ['God of War Ragnarök', ProductType::DigitalKey, 'god-of-war-ragnarok-playstation-store-ps4-ps5-latam', 'playstation-store', 'latam', ['ps4', 'ps5'], 44990, null, ProductStatus::Published],
        ['Marvel\'s Spider-Man 2', ProductType::DigitalKey, 'marvels-spider-man-2-playstation-store-ps5-latam', 'playstation-store', 'latam', ['ps5'], 54990, null, ProductStatus::Published],
        ['Marvel\'s Spider-Man 2', ProductType::Physical, 'marvels-spider-man-2-physical-ps5-north-america', null, 'north-america', ['ps5'], 57990, 4, ProductStatus::Published],
        ['Super Mario Bros. Wonder', ProductType::DigitalKey, 'super-mario-bros-wonder-nintendo-eshop-switch-latam', 'nintendo-eshop', 'latam', ['switch'], 49990, null, ProductStatus::Published],
        ['Super Mario Bros. Wonder', ProductType::Physical, 'super-mario-bros-wonder-physical-switch', null, null, ['switch'], 52990, 6, ProductStatus::Published],
        ['Mario Kart World', ProductType::Physical, 'mario-kart-world-physical-switch-2', null, null, ['switch-2'], 69990, 10, ProductStatus::Published],
        ['Mario Kart World', ProductType::DigitalKey, 'mario-kart-world-nintendo-eshop-switch-2-latam', 'nintendo-eshop', 'latam', ['switch-2'], 64990, null, ProductStatus::Published],
        ['Halo Infinite', ProductType::DigitalKey, 'halo-infinite-xbox-windows-xbox-series-global', 'xbox', 'global', ['windows', 'xbox-series'], 24990, null, ProductStatus::Published],
        ['Forza Horizon 5', ProductType::DigitalKey, 'forza-horizon-5-xbox-windows-xbox-one-xbox-series-global', 'xbox', 'global', ['windows', 'xbox-one', 'xbox-series'], 34990, null, ProductStatus::Published],
        ['Baldur\'s Gate 3', ProductType::DigitalKey, 'baldurs-gate-3-steam-macos-windows-global', 'steam', 'global', ['windows', 'macos'], 44990, null, ProductStatus::Published],
        ['Baldur\'s Gate 3', ProductType::DigitalKey, 'baldurs-gate-3-playstation-store-ps5-latam', 'playstation-store', 'latam', ['ps5'], 47990, null, ProductStatus::Published],
        ['Hollow Knight', ProductType::DigitalKey, 'hollow-knight-steam-macos-windows-global', 'steam', 'global', ['windows', 'macos'], 9990, null, ProductStatus::Published],
        ['Hollow Knight', ProductType::DigitalKey, 'hollow-knight-nintendo-eshop-switch-latam', 'nintendo-eshop', 'latam', ['switch'], 11990, null, ProductStatus::Published],
        ['Stardew Valley', ProductType::DigitalKey, 'stardew-valley-steam-macos-windows-global', 'steam', 'global', ['windows', 'macos'], 8990, null, ProductStatus::Published],
        ['Stardew Valley', ProductType::DigitalKey, 'stardew-valley-nintendo-eshop-switch-latam', 'nintendo-eshop', 'latam', ['switch'], 10990, null, ProductStatus::Published],
        ['Red Dead Redemption 2', ProductType::DigitalKey, 'red-dead-redemption-2-steam-windows-global', 'steam', 'global', ['windows'], 27990, null, ProductStatus::Published],
        ['Red Dead Redemption 2', ProductType::Physical, 'red-dead-redemption-2-physical-ps4-latam', null, 'latam', ['ps4'], 24990, 7, ProductStatus::Published],
        ['The Witcher 3: Wild Hunt', ProductType::DigitalKey, 'the-witcher-3-wild-hunt-steam-windows-global', 'steam', 'global', ['windows'], 14990, null, ProductStatus::Published],
        ['The Witcher 3: Wild Hunt', ProductType::DigitalKey, 'the-witcher-3-wild-hunt-playstation-store-ps5-latam', 'playstation-store', 'latam', ['ps5'], 19990, null, ProductStatus::Published],
        ['Animal Crossing: New Horizons', ProductType::DigitalKey, 'animal-crossing-new-horizons-nintendo-eshop-switch-latam', 'nintendo-eshop', 'latam', ['switch'], 44990, null, ProductStatus::Published],
    ];

    /**
     * Seed the demo catalog. Products and their platforms are written in one
     * transaction because the identity constraint trigger runs at commit.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            ActivationService::query()->firstOrCreate(['slug' => 'norton-account'], ['name' => 'Norton Account']);

            foreach (self::TITLES as $name => $description) {
                Title::query()->create(['name' => $name, 'description' => $description]);
            }

            foreach (self::PRODUCTS as [$titleName, $type, $slug, $serviceSlug, $regionSlug, $platformSlugs, $price, $stock, $status]) {
                $product = Product::query()->create([
                    'title_id' => Title::query()->where('name', $titleName)->sole()->id,
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
