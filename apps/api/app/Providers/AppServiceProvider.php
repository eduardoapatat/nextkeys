<?php

namespace App\Providers;

use App\Catalog\CoverArt\CoverArtSource;
use App\Catalog\CoverArt\Igdb\IgdbCoverArt;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Payments\PaymentGateway;
use App\Payments\Transbank\WebpayPlusGateway;
use Carbon\CarbonImmutable;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, fn (): PaymentGateway => new WebpayPlusGateway(
            host: Config::string('payments.webpay_plus.hosts.'.Config::string('payments.webpay_plus.environment')),
            commerceCode: Config::string('payments.webpay_plus.commerce_code'),
            apiKey: Config::string('payments.webpay_plus.api_key'),
            connectTimeoutSeconds: Config::integer('payments.webpay_plus.connect_timeout'),
            timeoutSeconds: Config::integer('payments.webpay_plus.timeout'),
            commitRetries: Config::integer('payments.webpay_plus.commit_retries'),
        ));

        $this->app->singleton(CoverArtSource::class, fn (): CoverArtSource => new IgdbCoverArt(
            clientId: Config::get('services.igdb.client_id') !== null ? Config::string('services.igdb.client_id') : null,
            clientSecret: Config::get('services.igdb.client_secret') !== null ? Config::string('services.igdb.client_secret') : null,
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();
        $this->configureCustomerAuth();
        $this->configureRateLimiting();
        $this->configureDevCommands();
    }

    /**
     * `composer dev` also runs the scheduler, so pending orders expire
     * locally the same way they do in production (`orders:expire`).
     */
    protected function configureDevCommands(): void
    {
        DevCommands::artisan('schedule:work', 'schedule');
    }

    /**
     * The public API allows 120 requests per minute per IP (`throttleApi()`
     * in bootstrap/app.php attaches it to the `api` group). The panel resends
     * an order's confirmation email at most once a minute. Storefront sign in
     * allows 5 attempts a minute per email and IP, registration 3 a minute and
     * password reset requests 5 a minute per IP.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(120)->by((string) $request->ip()));

        RateLimiter::for('customer-login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by('customer-login:'.Str::transliterate(Str::lower(trim($request->string('email')->value()))).'|'.$request->ip()));

        RateLimiter::for('customer-register', fn (Request $request): Limit => Limit::perMinute(3)->by('customer-register:'.$request->ip()));

        RateLimiter::for('customer-password', fn (Request $request): Limit => Limit::perMinute(5)->by('customer-password:'.$request->ip()));

        RateLimiter::for('paid-email-resend', function (Request $request): Limit {
            $order = $request->route('order');

            return Limit::perMinute(1)->by('paid-email-resend:'.($order instanceof Order ? $order->reference : $request->path()));
        });
    }

    /**
     * The `customers` user provider (the `customer` guard and password broker)
     * only finds customers: for the storefront an admin does not exist.
     */
    protected function configureCustomerAuth(): void
    {
        Auth::provider('customers', fn (Application $app, array $config): EloquentUserProvider => (new EloquentUserProvider($app->make('hash'), User::class))
            ->withQuery(fn (Builder $query): Builder => $query->where('role', UserRole::Customer)));
    }

    /**
     * Define the application's authorization gates.
     */
    protected function configureGates(): void
    {
        Gate::define('access-admin-panel', fn (User $user): bool => $user->isAdmin());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
