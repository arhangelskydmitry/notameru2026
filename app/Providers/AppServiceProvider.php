<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Helpers\BannerHelper;
use App\Models\WordPress\Post;
use App\Observers\PostObserver;

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
        // Регистрируем Observer для автопостинга
        Post::observe(PostObserver::class);
        
        // Blade directive для баннеров
        Blade::directive('banner', function ($zone) {
            return "<?php echo \App\Helpers\BannerHelper::show($zone); ?>";
        });
    }
}
