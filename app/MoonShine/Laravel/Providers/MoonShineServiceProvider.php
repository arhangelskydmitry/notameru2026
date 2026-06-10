<?php

namespace MoonShine\Laravel\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Stub service provider to gracefully handle legacy MoonShine references.
 *
 * Когда старый конфиг пытается загрузить MoonShineServiceProvider (например,
 * из кеша config/services), отсутствие пакета приводило к 500 ошибке.
 * Этот пустой провайдер позволяет приложению запускаться, даже если
 * MoonShine окончательно удалён из composer.json.
 */
class MoonShineServiceProvider extends ServiceProvider
{
    /**
     * Register bindings.
     */
    public function register(): void
    {
        // Ничего не делаем — провайдер лишь заглушка.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Намеренно пусто.
    }
}



