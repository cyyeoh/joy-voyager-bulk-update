<?php

declare(strict_types=1);

namespace Joy\VoyagerBulkUpdate;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use TCG\Voyager\Facades\Voyager;

/**
 * Class VoyagerBulkUpdateServiceProvider
 *
 * @category  Package
 * @package   JoyVoyagerBulkUpdate
 * @author    Ramakant Gangwar <gangwar.ramakant@gmail.com>
 * @copyright 2021 Copyright (c) Ramakant Gangwar (https://github.com/rxcod9)
 * @license   http://github.com/rxcod9/joy-voyager-bulk-update/blob/main/LICENSE New BSD License
 * @link      https://github.com/rxcod9/joy-voyager-bulk-update
 */
class VoyagerBulkUpdateServiceProvider extends ServiceProvider
{
    /**
     * Boot
     *
     * @return void
     */
    public function boot()
    {
        Voyager::addAction(\Joy\VoyagerBulkUpdate\Actions\BulkUpdateAction::class);
        Voyager::addAction(\Joy\VoyagerBulkUpdate\Actions\BulkAssigneeUpdateAction::class);
        Voyager::addAction(\Joy\VoyagerBulkUpdate\Actions\BulkParentUpdateAction::class);
        // Voyager::addAction(\Joy\VoyagerBulkUpdate\Actions\BulkStatusUpdateAction::class);
        Voyager::addAction(\Joy\VoyagerBulkUpdate\Actions\BulkRoleUpdateAction::class);
        Voyager::addAction(\Joy\VoyagerBulkUpdate\Actions\BulkFeaturedUpdateAction::class);

        $this->registerPublishables();

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'joy-voyager-bulk-update');

        $this->mapApiRoutes();

        $this->mapWebRoutes();

        if (config('joy-voyager-bulk-update.database.autoload_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'joy-voyager-bulk-update');
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/../routes/web.php');
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix(config('joy-voyager-bulk-update.route_prefix', 'api'))
            ->middleware('api')
            ->group(__DIR__ . '/../routes/api.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/voyager-bulk-update.php', 'joy-voyager-bulk-update');

        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }
    }

    /**
     * Register publishables.
     *
     * @return void
     */
    protected function registerPublishables(): void
    {
        $this->publishes([
            __DIR__ . '/../config/voyager-bulk-update.php' => config_path('joy-voyager-bulk-update.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/joy-voyager-bulk-update'),
        ], 'views');

        $this->publishes([
            __DIR__ . '/../resources/views/bread/partials' => resource_path('views/vendor/voyager/bread/partials'),
        ], 'voyager-actions-views');

        $this->publishes([
            __DIR__ . '/../resources/lang' => resource_path('lang/vendor/joy-voyager-bulk-update'),
        ], 'translations');
    }

    protected function registerCommands(): void
    {
        $this->commands([
            //
        ]);
    }
}
