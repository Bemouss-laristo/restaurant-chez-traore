<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('fr');

        $this->defineAuthorizationGates();
        $this->registerBladeDirectives();
        $this->configureRateLimiting();
    }

    /**
     * Anti-spam des commandes en ligne (par adresse IP) :
     * 5 commandes par minute et 20 par heure au maximum.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('orders', fn (Request $request) => [
            Limit::perMinute(5)->by('orders-min|'.$request->ip()),
            Limit::perHour(20)->by('orders-hour|'.$request->ip()),
        ]);
    }

    /**
     * Directive @mru($montant) : affiche un montant en Ouguiya, sans décimale,
     * avec séparateur de milliers. Ex : @mru(12500) => « 12 500 MRU ».
     */
    private function registerBladeDirectives(): void
    {
        Blade::directive('mru', function (string $expression): string {
            return "<?php echo number_format((float) ($expression), 0, ',', ' ').' MRU'; ?>";
        });
    }

    /**
     * Matrice de permissions centralisée.
     *
     * L'administrateur a TOUS les droits (Gate::before).
     * Les autres rôles reçoivent les capacités listées ci-dessous.
     */
    private function defineAuthorizationGates(): void
    {
        // Court-circuit : l'admin passe tous les contrôles.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        /** @var array<string, list<Role>> $abilities */
        $abilities = [
            'manage-users'    => [],                          // admin uniquement
            'manage-products' => [Role::Gerant],
            'manage-stock'    => [Role::Gerant],
            'manage-expenses' => [Role::Gerant],
            'view-reports'    => [Role::Gerant],
            'handle-sales'    => [Role::Gerant, Role::Caissier],
            'handle-cash'     => [Role::Gerant, Role::Caissier],
        ];

        foreach ($abilities as $ability => $roles) {
            Gate::define(
                $ability,
                fn (User $user) => $user->is_active && in_array($user->role, $roles, true),
            );
        }
    }
}
