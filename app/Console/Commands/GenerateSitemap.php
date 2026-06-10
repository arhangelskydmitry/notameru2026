<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate and persist the public sitemap.xml file';

    public function handle(): int
    {
        try {
            $controller = app(SitemapController::class);
            $sitemap = $controller->generateAndStoreSitemap();

            $this->info('Sitemap generated successfully.');
            $this->line('Bytes: ' . strlen($sitemap));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to generate sitemap: ' . $e->getMessage());
            report($e);

            return self::FAILURE;
        }
    }
}
