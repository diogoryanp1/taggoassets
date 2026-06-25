<?php

namespace App\Providers;

use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetBrand;
use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetCondition;
use App\Domain\Assets\Models\AssetCustomFieldDefinition;
use App\Domain\Assets\Models\AssetModel;
use App\Domain\Assets\Models\AssetType;
use App\Domain\Assets\Models\UnitOfMeasure;
use App\Domain\Audit\Models\AuditLog;
use App\Domain\Documents\Models\PrivateDocument;
use App\Domain\Organizations\Models\Location;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\CurrentTenant;
use App\Models\User;
use App\Policies\AssetBrandPolicy;
use App\Policies\AssetCategoryPolicy;
use App\Policies\AssetConditionPolicy;
use App\Policies\AssetCustomFieldDefinitionPolicy;
use App\Policies\AssetModelPolicy;
use App\Policies\AssetPolicy;
use App\Policies\AssetTypePolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\LocationPolicy;
use App\Policies\OrganizationalUnitPolicy;
use App\Policies\PrivateDocumentPolicy;
use App\Policies\UnitOfMeasurePolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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
        $this->app->singleton(CurrentTenant::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Factory::guessFactoryNamesUsing(fn (string $modelName): string => 'Database\\Factories\\'.class_basename($modelName).'Factory');
        Gate::policy(OrganizationalUnit::class, OrganizationalUnitPolicy::class);
        Gate::policy(Location::class, LocationPolicy::class);
        Gate::policy(PrivateDocument::class, PrivateDocumentPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(AssetCategory::class, AssetCategoryPolicy::class);
        Gate::policy(AssetType::class, AssetTypePolicy::class);
        Gate::policy(AssetBrand::class, AssetBrandPolicy::class);
        Gate::policy(AssetModel::class, AssetModelPolicy::class);
        Gate::policy(UnitOfMeasure::class, UnitOfMeasurePolicy::class);
        Gate::policy(AssetCondition::class, AssetConditionPolicy::class);
        Gate::policy(AssetCustomFieldDefinition::class, AssetCustomFieldDefinitionPolicy::class);
        Gate::policy(Asset::class, AssetPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::before(function ($user, string $ability): ?bool {
            $tenant = app(CurrentTenant::class)->get();

            return $tenant && $user->hasPermission($tenant, 'settings.manage') && str_starts_with($ability, 'platform.') ? true : null;
        });
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });
        RateLimiter::for('password.reset', fn (Request $request) => Limit::perMinute(3)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
