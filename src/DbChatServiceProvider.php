<?php

namespace Martinko366\LaravelDbChat;

use Illuminate\Support\ServiceProvider;
use Martinko366\LaravelDbChat\Services\ConversationService;
use Martinko366\LaravelDbChat\Services\MessageService;

class DbChatServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package config
        $this->mergeConfigFrom(
            __DIR__.'/../config/dbchat.php', 'dbchat'
        );

        // Register services
        $this->app->singleton(ConversationService::class);
        $this->app->singleton(MessageService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/dbchat.php' => config_path('dbchat.php'),
        ], 'dbchat-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'dbchat-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Register route model bindings
        $this->registerRouteBindings();
    }

    /**
     * Register route model bindings.
     */
    protected function registerRouteBindings(): void
    {
        $this->app->booted(function () {
            $router = $this->app->make('router');

            // Bind conversation model
            $router->bind('conversation', function ($value) {
                return \Martinko366\LaravelDbChat\Models\Conversation::findOrFail($value);
            });

            // Bind message model
            $router->bind('message', function ($value) {
                return \Martinko366\LaravelDbChat\Models\Message::findOrFail($value);
            });
        });
    }
}
