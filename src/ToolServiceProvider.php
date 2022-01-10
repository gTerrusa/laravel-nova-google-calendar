<?php

namespace GTerrusa\LaravelNovaGoogleCalendar;

use GTerrusa\LaravelGoogleCalendar\Http\Controllers\GoogleCalendarController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;
use GTerrusa\LaravelNovaGoogleCalendar\Http\Middleware\Authorize;

class ToolServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laravel-nova-google-calendar.php' => config_path('laravel-nova-google-calendar.php')
            ], 'config');
            $this->publishes([__DIR__.'/../resources/images' => public_path('images')], 'images');
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-nova-google-calendar');

        $this->app->booted(function () {
            $this->routes();
        });

        Nova::serving(function (ServingNova $event) {
            $user = $event->request->user();
            $user_admin_boolean = config('laravel-nova-google-calendar.user_admin_boolean');

            Nova::provideToScript([
                'user' => $user->toArray(),
                'user_is_admin' => $user_admin_boolean ? $user->$user_admin_boolean : true
            ]);
        });
    }

    /**
     * Register the tool's routes.
     *
     * @return void
     */
    protected function routes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova', Authorize::class])
                ->prefix('nova-vendor/laravel-nova-google-calendar')
                ->group(__DIR__.'/../routes/api.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-nova-google-calendar.php', 'laravel-nova-google-calendar');
    }
}
